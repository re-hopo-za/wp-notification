<?php


namespace Notif\includes;



class NotifStatic
{


    protected static $_instance = null;
    public static function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public static function preparingList( $items )
    {
        foreach ( $items as $item ){
            if ( is_object( $item ) ){
                $cron      = NotifFunctions::jsonDecodeDbColumn( $item ,'cron' );
                $per_count = NotifFunctions::indexChecker( $cron ,'cron_per_count' ,20 );

                $users      = self::getUsers( $item );
                $users_info = self::prepareToGetUsersInfo( $users ,$per_count );

                NotifDB::get_instance()::handleCronRunningStatus( $item->id ,1 );
                if ( empty( $users ) || empty( $users_info ) ){
                    NotifDB::get_instance()::updateNotifToStop( $item->id );
                    continue;
                }else{
                    $users_info = self::getUsersInfo( $users_info );
                    if ( empty( $users_info ) ) {
                        NotifDB::get_instance()::updateNotifToStop( $item->id );
                        continue;
                    }else{
                        $system_list = NotifCron::getDeliverSystem( $cron );
                        if ( empty( $system_list ) ){
                            NotifDB::get_instance()::updateNotifToStop( $item->id );
                            continue;
                        }else{
                            $effected_users = [];
                            if ( !isset( $cron->cron_more_than ) || $cron->cron_more_than == "false" ){
                                foreach ( $users_info as $user ){
                                    $receive_status = true;
                                    foreach ( $system_list as $sys ){
                                        if ( NotifCron::switchDeliverSystem( $sys ,$item ,$user ) ){
                                            if ( $receive_status ){
                                                if ( count( $system_list ) > 1  && $sys != 'alert' ) {
                                                    $receive_status = false;
                                                    $effected_users[$user->ID][ $sys ] = 1;
                                                }elseif ( count( $system_list ) < 2 && $sys == 'alert'  ){
                                                    $receive_status = false;
                                                    $effected_users[$user->ID][ $sys ] = 1;
                                                }
                                            }
                                        }else{
                                            $effected_users[$user->ID][ $sys ] = 2;
                                        }
                                    }
                                }
                            }else{
                                foreach ( $users_info as $user ){
                                    foreach ( $system_list as $sys ){
                                        if ( NotifCron::switchDeliverSystem( $sys ,$item ,$user ) == true ){
                                            $effected_users[$user->ID][ $sys ] = 1;
                                        }else{
                                            $effected_users[$user->ID][ $sys ] = 2;
                                        }
                                    }
                                }
                            }
                            if ( count( $users ) <= $per_count ){
                                NotifDB::get_instance()::updateNotifToStop( $item->id );
                            }
                            NotifDB::get_instance()::handleCronRunningStatus( $item->id ,0 );
                            NotifDB::get_instance()::updateUsersStatusOnCron( $item->id ,$effected_users );
                        }
                    }
                }
            }
            NotifDB::get_instance()::handleCronRunningStatus( $item->id ,0 );
        }
    }


    public static function getUsers( $users )
    {
        if ( !empty( NotifFunctions::indexChecker( $users ,'users' ) ) ){
            $users = json_decode( NotifFunctions::indexChecker( $users ,'users' ) );
            if ( is_object( $users ) ){
                return (array) $users;
            }
        }
        return [];
    }


    public static function getUserDate( $userObject ,$key )
    {
        if ( strpos( $key, '##') !== false ) {
            $key = str_replace( '##' ,'' ,$key );
            $user_data = get_user_meta( $userObject->ID , $key ,true );
            return !empty( $user_data ) ? $user_data : '';

        }elseif (strpos( $key, '#' )  !== false ){
            $key = str_replace( '#' ,'' ,$key );
            return NotifFunctions::indexChecker( $userObject ,$key  );
        }
        return $key;
    }



    public static function prepareToGetUsersInfo( $users ,$per_count )
    {
        $final_users = [];
        if ( !empty( $users ) ){
            asort( $users );
            $i_user = 0;
            foreach ( $users as $key => $val ){
                if ( is_numeric( $val ) && $val == 0 ){
                    $final_users[ $key ] = $val;
                    $i_user++;
                }
                if ( count( $final_users ) >= (int) $per_count ){
                    break;
                }
            }
        }
        return $final_users;
    }


    public static function getUsersInfo( $usersIDs )
    {
        $users_ids  = implode( ',' ,array_keys( $usersIDs ) );
        $mobile_key = \hf_user_mobile_meta_key();
        global $wpdb;
        $result = $wpdb->get_results( "SELECT users.* ,user_meta_m.meta_value AS mobile FROM {$wpdb->users} AS users
                    LEFT JOIN {$wpdb->usermeta} AS user_meta_m ON users.ID = user_meta_m.user_id AND user_meta_m.meta_key = '{$mobile_key}'  
                    WHERE users.ID IN ({$users_ids});"
        );
        if ( empty( $wpdb->last_error ) && !empty( $result ) ){
            return $result;
        }
        return [];
    }


    public static function updateUsersList()
    {
        $items = NotifDB::get_instance()::getUsersList();
        if ( !empty( $items ) ){
            foreach ( $items as $item ){
                if ( !empty( $item->filters ) ) {
                    $filters     = unserialize( $item->filters );
                    $old_users   = unserialize( $item->users_list );
                    $final_users = [];
                    if ( is_array( $filters ) ){
                        $new_users = NotifDB::calculateUsers( $filters );
                        $user_list = array_fill_keys( array_keys( array_flip( json_decode( $new_users[0]->IDs) ) ),0 );
                        foreach ( $user_list as $key => $val ){
                            $final_users[$key] = $old_users[$key];
                        }
                        NotifDB::updateCampaignUsersList( $item->campaign_id ,$final_users );
                    }
                }
            }
        }
    }


}
