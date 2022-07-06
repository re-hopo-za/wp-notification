<?php

namespace Notif\includes;


class NotifDB
{

    public static  $charset;
    public static  $itemsLimit;
    public static  $prefix;
    public static  $tableNotif;



    protected static  $_instance = null;
    public static function get_instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public  function __construct()
    {
        global $wpdb;
        self::$prefix        = $wpdb->prefix;
        self::$itemsLimit    = 40;
        self::$charset       = $wpdb->get_charset_collate();
        self::$tableNotif    = self::$prefix.'notif';
    }



    public static function getAll( $page = 0 ,$status = null )
    {
        global $wpdb;
        $limit  = self::$itemsLimit;
        $table  = self::$tableNotif;
        $where  = !empty( $status ) ? "WHERE ".$status : '';
        $result = $wpdb->get_results(
            "SELECT * FROM {$table} {$where} LIMIT {$limit} OFFSET {$page};"
        );
        if ( !is_wp_error( $result ) && !empty( $result ) ) {
            return $result;
        }
        return [];
    }


    public static function getSingle( $id )
    {
        global $wpdb;
        $table  = self::$tableNotif;
        $result = $wpdb->get_row(
             "SELECT * FROM {$table} WHERE id = {$id} ;"
        );
        if ( !is_wp_error( $result ) && !empty( $result ) ) {
            return $result;
        }
        return [];
    }


    public static function initialNotif()
    {
        global $wpdb;
        $wpdb->insert( self::$tableNotif ,[
            'details'       => NotifFunctions::getNewNotifDetails(),
            'users'         => '{}' ,
            'success_users' => '{}' ,
            'failed_users'  => '{}' ,
            'message'       => '{}' ,
            'campaign'      => '{}' ,
            'cron'          => '{}' ,
            'status'        =>  1
        ]);
        $notifID = $wpdb->insert_id;
        if ( is_integer( $notifID ) ){
            return $notifID;
        }
        return false;
    }


    public static function createNotif()
    {
        $notif_id = NotifFunctions::getCurrentNotifID();
        if ( !is_numeric( $notif_id ) ){
            $notif_id = self::initialNotif();
        }
        if( is_numeric( $notif_id ) ){
            $message = [
                'message_title'          => NotifFunctions::indexChecker( $_POST ,'message_title' ) ,
                'message_sms_text'       => NotifFunctions::indexChecker( $_POST ,'message_sms_text' ) ,
                'message_sms_template'   => NotifFunctions::indexChecker( $_POST ,'message_sms_template' ) ,
                'message_sms_priority'   => NotifFunctions::indexChecker( $_POST ,'message_sms_priority' ) ,
                'message_cover'          => NotifFunctions::indexChecker( $_POST ,'message_cover' ) ,
                'message_ticket_creator' => NotifFunctions::indexChecker( $_POST ,'message_ticket_creator' ) ,
                'message_ticket_course'  => NotifFunctions::indexChecker( $_POST ,'message_ticket_course' ) ,
            ];
            $message_status = self::updateNotifColumn( $notif_id ,$message ,'message' );

            $campaign = [
                'campaign_includes_courses'      => NotifFunctions::indexChecker(  $_POST ,'campaign_includes_courses'   ,null ),
                'campaign_excludes_courses'      => NotifFunctions::indexChecker(  $_POST ,'campaign_excludes_courses'   ,null ),
                'campaign_from_date_registered'  => NotifFunctions::indexChecker(  $_POST ,'campaign_from_date_registered'    ,null ),
                'campaign_to_date_registered'    => NotifFunctions::indexChecker(  $_POST ,'campaign_to_date_registered'    ,null ),
                'campaign_webmasteran'           => NotifFunctions::indexChecker(  $_POST ,'campaign_webmasteran' ,null ),
                'campaign_instagram'             => NotifFunctions::indexChecker(  $_POST ,'campaign_instagram' ,null ),
                'campaign_additional_users_text' => NotifFunctions::indexChecker(  $_POST ,'campaign_additional_users_text'  ,null ),
                'campaign_has_process'           => NotifFunctions::indexChecker(  $_POST ,'campaign_has_process'  ),
                'campaign_additional_users'      => NotifFunctions::indexChecker(  $_POST ,'campaign_additional_users' ) ,
                'campaign_users_count'           => ''
            ];
            $campaign_status = self::updateNotifColumn( $notif_id ,$campaign ,'campaign' );

            $cron = [
                'cron_deliver_system' => NotifFunctions::indexChecker( $_POST ,'cron_deliver_system' ,[] ),
                'cron_more_than'      => NotifFunctions::indexChecker( $_POST ,'cron_more_than' ,'false' ),
                'cron_per_time'       => NotifFunctions::indexChecker( $_POST ,'cron_per_time' ,1 )  ,
                'cron_per_count'      => NotifFunctions::indexChecker( $_POST ,'cron_per_count' ,100 ) ,
                'cron_start_time'     => NotifFunctions::indexChecker( $_POST ,'cron_start_time' ,date('H:i') ) ,
                'cron_end_time'       => NotifFunctions::indexChecker( $_POST ,'cron_end_time' ,'21:00' ) ,
                'cron_start_day'      => NotifFunctions::returnCorrectStartDay() ,
                'cron_end_day'        => NotifFunctions::returnCorrectEndDay()   ,
                'cron_last_run'       => NotifFunctions::returnLastRunDateTime() ,
                'cron_running'        => 0
            ];
            $cron_status = self::updateNotifColumn( $notif_id ,$cron ,'cron' );

            if ( $message_status && $campaign_status && $cron_status ) {
                $users = NotifFunctions::usersCalculator( $campaign );
                self::updateNotifColumn( $notif_id ,$users['users'] ,'users' );
                self::updateNotifColumn( $notif_id ,NotifFunctions::indexChecker( $_POST ,'status',0 ) ,'status' );
                self::updateNotifColumn( $notif_id ,NotifFunctions::indexChecker( $_POST ,'message_content' ) ,'content' );
                self::updateNotifColumn( $notif_id ,NotifFunctions::indexChecker( $_POST ,'notif_label','' ) ,'label' );
                self::updateNotifColumn( $notif_id ,NotifFunctions::checkSendJustNotif(),'process_name' );
                self::updateUserCountOnProcessed( $notif_id ,$users['count'] );
                NotifFunctions::sendJsonResult( 200 ,['notif_id' => $notif_id ,'count'=> $users['count'] ] );
            }
        }
        NotifFunctions::sendJsonResult(500 );
    }


    public static function createMessage()
    {
        $notif_id = NotifFunctions::getCurrentNotifID();
        if ( !is_numeric( $notif_id ) ){
            $notif_id = self::initialNotif();
        }
        if( is_numeric( $notif_id ) ){
            $data = [
                'message_title'          => NotifFunctions::indexChecker( $_POST ,'message_title'          ) ,
                'message_sms_text'       => NotifFunctions::indexChecker( $_POST ,'message_sms_text'       ) ,
                'message_sms_template'   => NotifFunctions::indexChecker( $_POST ,'message_sms_template'   ) ,
                'message_sms_priority'   => NotifFunctions::indexChecker( $_POST ,'message_sms_priority'   ) ,
                'message_cover'          => NotifFunctions::indexChecker( $_POST ,'message_cover'          ) ,
                'message_ticket_creator' => NotifFunctions::indexChecker( $_POST ,'message_ticket_creator' ) ,
                'message_ticket_course'  => NotifFunctions::indexChecker( $_POST ,'message_ticket_course'  )
            ];
            $status = self::updateNotifColumn( $notif_id ,$data ,'message' );
            if ( $status ){
                self::updateNotifColumn( $notif_id ,NotifFunctions::indexChecker( $_POST ,'message_content' ) ,'content' );
                NotifFunctions::sendJsonResult( 200 ,['notif_id' => $notif_id ] );
            }
        }
        NotifFunctions::sendJsonResult(500 );
    }



    public static function createCampaign()
    {
        $notif_id = NotifFunctions::getCurrentNotifID();
        if ( !is_numeric( $notif_id ) ){
            $notif_id = self::initialNotif();
        }
        if( is_numeric( $notif_id ) ){
            $data = [
                'campaign_includes_courses'      => NotifFunctions::indexChecker(  $_POST ,'campaign_includes_courses'       ),
                'campaign_excludes_courses'      => NotifFunctions::indexChecker(  $_POST ,'campaign_excludes_courses'       ),
                'campaign_from_date_registered'  => NotifFunctions::indexChecker(  $_POST ,'campaign_from_date_registered'   ),
                'campaign_to_date_registered'    => NotifFunctions::indexChecker(  $_POST ,'campaign_to_date_registered'     ),
                'campaign_webmasteran'           => NotifFunctions::indexChecker(  $_POST ,'campaign_webmasteran'            ),
                'campaign_instagram'             => NotifFunctions::indexChecker(  $_POST ,'campaign_instagram'              ),
                'campaign_additional_users_text' => NotifFunctions::indexChecker(  $_POST ,'campaign_additional_users_text'  ),
                'campaign_has_process'           => NotifFunctions::indexChecker(  $_POST ,'campaign_has_process'            ),
                'campaign_additional_users'      => NotifFunctions::indexChecker(  $_POST ,'campaign_additional_users'       ) ,
                'campaign_users_count'           => ''
            ];
            $status = self::updateNotifColumn( $notif_id ,$data ,'campaign' );
            if ( $status ){
                $users = NotifFunctions::usersCalculator( $data );
                self::updateNotifColumn( $notif_id ,$users['users'] ,'users' );
                self::updateNotifColumn( $notif_id ,NotifFunctions::indexChecker( $_POST ,'message_content' ) ,'content' );
                self::updateUserCountOnProcessed( $notif_id ,$users['count'] );
                NotifFunctions::sendJsonResult( 200 ,['notif_id' => $notif_id ,'count'=> $users['count'] ] );
            }
        }
         NotifFunctions::sendJsonResult(500 );
    }


    public static function createCron()
    {
        $notif_id = NotifFunctions::getCurrentNotifID();
        if ( !is_numeric( $notif_id ) ){
            $notif_id = self::initialNotif();
        }
        if( is_numeric( $notif_id ) ){
            $data = [
                'cron_deliver_system' => NotifFunctions::indexChecker( $_POST ,'cron_deliver_system' ,[] ),
                'cron_more_than'      => NotifFunctions::indexChecker( $_POST ,'cron_more_than' ,'false'  ),
                'cron_per_time'       => NotifFunctions::indexChecker( $_POST ,'cron_per_time' ,1 )  ,
                'cron_per_count'      => NotifFunctions::indexChecker( $_POST ,'cron_per_count' ,100 ) ,
                'cron_start_time'     => NotifFunctions::indexChecker( $_POST ,'cron_start_time' ,date('H:i') ) ,
                'cron_end_time'       => NotifFunctions::indexChecker( $_POST ,'cron_end_time' ,'21:00' ) ,
                'cron_start_day'      => NotifFunctions::returnCorrectStartDay() ,
                'cron_end_day'        => NotifFunctions::returnCorrectEndDay()   ,
                'cron_last_run'       => NotifFunctions::returnLastRunDateTime() ,
                'cron_running'        => 0
            ];
            $status = self::updateNotifColumn( $notif_id ,$data ,'cron' );
            if ( $status ){
                self::updateNotifColumn( $notif_id ,NotifFunctions::checkSendJustNotif(),'process_name' );
                NotifFunctions::sendJsonResult( 200 ,['notif_id' => $notif_id ] );
            }
        }
        NotifFunctions::sendJsonResult(500 );
    }


    public static function updateNotifColumn( $notifID ,$data ,$column )
    {
        global $wpdb;
        $data   = [ $column => NotifFunctions::clearDataBeforeSave( $data ,$column ) ];
        $where  = [ 'id' => $notifID ];
        $wpdb->update( self::$tableNotif ,$data ,$where );
        if ( empty( $wpdb->last_error ) ){
            return true;
        }
        return false;
    }


    public static function deleteItem( $parameters )
    {
        global $wpdb;
        $wpdb->update(
            self::$tableNotif ,
            [ 'status' => 2 ] ,
            [ 'id' => NotifFunctions::indexChecker( $parameters ,'notif_id' ) ] ,
            [ '%d' ] ,
            [ '%d' ]
        );
        if ( empty( $wpdb->last_error ) ){
            NotifFunctions::sendJsonResult( true , 200 );
        }else{
            NotifFunctions::sendJsonResult( true , 500 );
        }
    }


    public static function updateNotifStatus( $parameters )
    {
        global $wpdb;
        $status   = $parameters['status'] ?? 3;
        $notif_id = NotifFunctions::indexChecker( $parameters ,'notif_id' );
        $notif    = self::getSingle( $notif_id );
        if( !empty( $notif ) ){
            $details = NotifFunctions::setNotifDetails( $notif->details ,$status );
            $data   = [ 'status' => $status ,'details' => $details ];
            $format = [ '%d' ,'%s' ];
            $where  = [ 'id' => $notif->id ];
            $where_format = [ '%d' ];
            $wpdb->update( self::$tableNotif ,$data ,$where ,$format ,$where_format );
            if ( empty( $wpdb->last_error ) ){
                NotifFunctions::sendJsonResult( true , 200 );
            }
        }
        NotifFunctions::sendJsonResult( true , 500 );
    }


    public static function getCronLists( $has_process )
    {
        global $wpdb;
        $notif  = self::$tableNotif;
        $where  = $has_process ? 'NOT' : '';
        $result =
            $wpdb->get_results(
            "
                SELECT * FROM {$notif} WHERE status = 1 AND process_name IS {$where} NULL 
                AND (
                    JSON_EXISTS( cron ,\"$.cron_start_day\" ) = 0 OR JSON_VALUE( cron , \"$.cron_start_day\") = '' OR JSON_VALUE( cron , \"$.cron_start_day\") <= DATE_FORMAT(NOW(),'%Y-%m-%d') 
                 )
                AND ( 
                    JSON_EXISTS( cron ,\"$.cron_end_day\" ) = 0 OR JSON_VALUE( cron , \"$.cron_end_day\") = '' OR ( JSON_VALUE( cron , \"$.cron_end_day\")  >= DATE_FORMAT(NOW(),'%Y-%m-%d') )
                )
                AND ( 
                    JSON_EXISTS( cron ,\"$.cron_start_time\" ) = 0 OR JSON_VALUE( cron , \"$.cron_start_time\") = ''  OR JSON_VALUE( cron , \"$.cron_start_time\") < DATE_FORMAT( NOW(),'%H:%i:%s')
                )
                AND ( 
                    JSON_EXISTS( cron ,\"$.cron_end_time\" ) = 0 OR JSON_VALUE( cron , \"$.cron_end_time\") = ''  OR ( JSON_VALUE( cron , \"$.cron_end_time\" ) > DATE_FORMAT(NOW(),'%H:%i:%s')  )
                )
                AND ( 
                    JSON_EXISTS( cron ,\"$.cron_last_run\" ) = 0 OR JSON_VALUE( cron , \"$.cron_last_run\") = '' OR NOW() > JSON_VALUE( cron , \"$.cron_last_run\" ) + INTERVAL JSON_VALUE( cron , \"$.cron_per_time\" ) MINUTE 
                )
                AND ( 
                    JSON_EXISTS( cron ,\"$.cron_running\" ) = 0 OR JSON_VALUE( cron , \"$.cron_running\") = ''  OR JSON_VALUE( cron , \"$.cron_running\" ) = 0
                );  
            "
        );
        if ( empty( $wpdb->last_error ) && !is_wp_error( $wpdb ) ){
            return $result;
        }
        return [];
    }


    public static function getCronTestItem( $messageID )
    {
        global $wpdb;
        $message  = self::$TableMessage;
        $query    = "SELECT * FROM {$message} WHERE message_id = {$messageID} ;";
        return $wpdb->get_results( $query )[0];
    }


    public static function handleCronRunningStatus( $notifID ,$status )
    {
        global $wpdb;
        $table  = self::$tableNotif;
        $result = $wpdb->get_results(
            "UPDATE {$table} SET 
                 cron = JSON_SET( cron, '$.cron_last_run' ,DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i:%s')  ) ,
                 cron = JSON_SET( cron, '$.cron_running' ,{$status} )
                 WHERE id ={$notifID}"
        );
        if( !is_wp_error( $result ) && $wpdb->rows_affected > 0 ){
            return true;
        }
        return false;
    }


    public static function updateUserCountOnProcessed( $notifID ,$count )
    {
        global $wpdb;
        $table  = self::$tableNotif;
        $result = $wpdb->get_results(
            "UPDATE {$table} SET 
                 campaign = JSON_SET( campaign, '$.campaign_users_count' ,{$count} )  
                 WHERE id ={$notifID}"
        );
        if( !is_wp_error( $result ) && $wpdb->rows_affected > 0 ){
            return true;
        }
        return false;
    }


    public static function updateNotifToStop( $notifID )
    {
        global $wpdb;
        $notif_table = self::$tableNotif;
        $data   = [ 'status' => 2 ];
        $format = [ '%d' ];
        $where  = [ 'id' => $notifID ];
        $where_format = [ '%d' ];
        $wpdb->update( $notif_table ,$data ,$where ,$format ,$where_format );
    }


    public static function getUsersList()
    {
        global $wpdb;
        $notif  = self::$tableNotif;
        $query  = "SELECT * FROM {$notif} WHERE status = 1 AND process_name IS NOT NULL;";
        $result =  $wpdb->get_results( $query );
        if ( !empty( $wpdb->last_error ) && !empty( $result ) ){
            return $result;
        }
         return [];
    }


    public static function calculateUsers( $filters )
    {
        global $wpdb;
        $users_table            = $wpdb->users;
        $users_meta_table       = $wpdb->usermeta;
        $users_table_ID         = $users_table . '.ID';
        $users_table_registered = $users_table . '.user_registered';
        $users_meta_table_user_id  = $users_meta_table . '.user_id';
        $users_meta_table_meta_key = $users_meta_table . '.meta_key';
        $users_meta_table_meta_val = $users_meta_table . '.meta_value';

        $where = "{$users_meta_table_meta_key} = 'all_purchased_id_full' AND ( ";
        $user_in_list = false;
        $inc_p_status = false;

        if (!empty( $filters['campaign_includes_courses'] ) || $filters['campaign_webmasteran'] == 'true' || $filters['campaign_instagram'] == 'true'  ) {
            $webmasteran_list = get_option('_is_webmasteran_course' );
            $instagram_list   = get_option('_is_instagram_group' );
            $all_group_list   = [];

            if ( $filters['campaign_webmasteran'] == 'true' ){
                $web_group_list = array_values( $webmasteran_list );
                $all_group_list = array_merge( $all_group_list ,array_values( $web_group_list ) );
            }

            if ( $filters['campaign_instagram'] == 'true' ){
                $ins_group_list = array_values( $instagram_list );
                $all_group_list = array_merge( $all_group_list ,array_values( $ins_group_list ) );
            }
            if ( !empty( $filters['campaign_includes_courses'] ) ){
                $all_group_list = array_merge( $all_group_list ,$filters['campaign_includes_courses'] );
            }
            $where .= " (";
            $include_count = 0;
            foreach ( $all_group_list as $include ) {
                $condition = $include_count > 0 ? 'OR' : '';
                $where .= " {$condition} {$users_meta_table_meta_val} LIKE '%{$include}%' ";
                $include_count++;
            }
            $where .= ")";
            $user_in_list = true;
            $inc_p_status = true;
        }


        if ( !empty( $filters['campaign_additional_users'] ) || !empty( $filters['campaign_additional_users_text'] ) ){
            $all_users      = [];
            $selected_users = $filters['campaign_additional_users'] ?? [];
            $plain_users    = $filters['campaign_additional_users_text'] ?? [];
            if ( !empty( $selected_users ) && is_array( $selected_users ) ){
                $all_users = $selected_users;
            }
            if ( !empty( $plain_users ) ){
                $plain_users = NotifFunctions::getUsersIDFromPlainText( $plain_users  );
                if ( !empty( $plain_users ) && is_array( $plain_users ) ){
                    $all_users = array_merge( $plain_users ,$all_users );
                }
            }
            if ( !empty( $all_users ) ){
                $all_users = implode("," , $all_users );
                if ( $user_in_list ){
                    $where .= " OR (";
                }
                $where .= " {$users_table_ID} IN ($all_users) ";
                if ( $user_in_list ){
                    $where .= " )";
                }
                $inc_p_status = true;
            }
        }

        if( !$inc_p_status ){
            $where .= " 1 = 1 ) ";
        }else{
            $where .= " ) ";
        }

        if ( !empty( $filters['campaign_excludes_courses'] ) ) {
            $where .= " AND (";
            $exclude_count = 0;
            foreach ( $filters['campaign_excludes_courses'] as $exclude) {
                $exclude_condition = $exclude_count > 0 ? ' AND ' : '';
                $where .= " {$exclude_condition} {$users_meta_table_meta_val} NOT LIKE '%{$exclude}%' ";
                $exclude_count++;
            }
            $where .= ")";
        }
        if ( !empty( $filters['campaign_from_date_registered'] ) && !empty( $filters['campaign_to_date_registered'] )) {
            $from_register  = date( $filters['campaign_from_date_registered'] );
            $until_register = date( $filters['campaign_to_date_registered'] );
            $where .= " AND ( {$users_table_registered} BETWEEN '{$from_register}' AND '{$until_register}' )";

        }elseif (!empty( $filters['campaign_from_date_registered'] )) {
            $from_register = date( $filters['campaign_from_date_registered']);
            $where .= " AND ( {$users_table_registered} > '{$from_register}' )";

        }elseif (!empty( $filters['campaign_to_date_registered'] )) {
            $until_register = date( $filters['campaign_to_date_registered'] );
            $where .= " AND ( {$users_table_registered} < '{$until_register}' )";
        }
        $result =
            $wpdb->get_row(
            "SELECT GROUP_CONCAT( CONCAT( '\"',id,'\":0' )) AS ids FROM wi1ip_users
                   INNER JOIN {$users_meta_table} ON {$users_table_ID} = {$users_meta_table_user_id}
                   WHERE {$where};"
            );
        if ( !is_wp_error( $result ) && !empty( $result ) ){
            return $result;
        }
        return false;
    }


    public static function getSpecificUserItems( $userID )
    {
        global $wpdb;
        $notif  = self::$tableNotif;
        $result =  $wpdb->get_results(
            "SELECT * FROM {$notif} WHERE ( JSON_EXISTS( users ,'$.{$userID}') || JSON_EXISTS( success_users ,'$.{$userID}' ) || JSON_EXISTS( failed_users ,'$.{$userID}' ) ) AND status IN( 1 ,2 );"
        );
        if ( !is_wp_error( $result ) && !empty( $result ) ){
            return $result;
        }
        return [];
    }


    public static function updateUserSeenOnAssignment( $userID ,$notifID )
    {
        global $wpdb;
        $notif  = self::$tableNotif;
        $wpdb->get_results(
            "UPDATE {$notif} SET    
                   users = IF( JSON_EXISTS( users ,'$.{$userID}') ,JSON_REMOVE( users ,'$.{$userID}') ,users ) , 
                   success_users = JSON_INSERT( success_users, '$.{$userID}',JSON_ARRAY( CURRENT_TIMESTAMP ,false ) ) ,
                   status        = 2
                   WHERE id ={$notifID}; 
            ;"
        );
        if ( !is_wp_error( $wpdb ) && !empty( $wpdb->rows_affected ) ){
            return true;
        }
        return false;
    }


    public static function updateUserSeenOnEmptyProcess( $userID ,$notifID )
    {
        global $wpdb;
        $notif  = self::$tableNotif;
        $wpdb->get_results(
            "UPDATE {$notif} SET      
                   success_users = IF( JSON_EXISTS( success_users ,'$.{$userID}') ,JSON_SET( success_users ,'$.{$userID}[0]' ,CURRENT_TIMESTAMP ) ,success_users )    
                   WHERE id ={$notifID}
            ;"
        );
        if ( !is_wp_error( $wpdb ) && !empty( $wpdb->rows_affected ) ){
            return true;
        }
        return false;
    }



    public static function createNotifDirectly( $title ,$content ,$userID  )
    {
        global $wpdb;
        $message = [
            'message_title'         => $title ,
            'message_sms_text'      => '' ,
            'message_sms_template'  => '' ,
            'message_sms_priority'  => '' ,
            'message_cover'         => ''
        ];
        $campaign = [
            'campaign_includes_courses'      => '',
            'campaign_excludes_courses'      => '',
            'campaign_from_date_registered'  => '',
            'campaign_to_date_registered'    => '',
            'campaign_webmasteran'           => '',
            'campaign_instagram'             => '',
            'campaign_additional_users_text' => '',
            'campaign_has_process'           => '',
            'campaign_additional_users'      => ''
        ];
        $cron = [
            'cron_deliver_system' => '' ,
            'cron_status'         => '' ,
            'cron_more_than'      => '' ,
            'cron_start_day'      => '' ,
            'cron_end_day'        => '' ,
            'cron_per_time'       => '' ,
            'cron_per_count'      => '' ,
            'cron_start_time'     => '' ,
            'cron_end_time'       => '' ,
            'cron_last_run'       => ''
        ];
        $wpdb->insert( self::$tableNotif ,[
            'label'          => 'Created Directly By User ID:'. get_current_user_id() ,
            'status'         => 1 ,
            'process_name'   => 'assignment' ,
            'users'          => json_encode( [ "$userID" => 0 ] ),
            'success_users'  => '{}' ,
            'failed_users'   => '{}' ,
            'details'        => NotifFunctions::getNewNotifDetails(),
            'message'        => json_encode( $message ,JSON_UNESCAPED_UNICODE ),
            'campaign'       => json_encode( $campaign ),
            'cron'           => json_encode( $cron ) ,
            'content'        => $content
        ]);
        if ( !is_wp_error( $wpdb ) && !empty( $wpdb->insert_id ) ){
            return true;
        }
        return false;
    }



    public static function updateUsersStatusOnCron( $notifID ,$usersList )
    {
        global $wpdb;
        $notif = self::$tableNotif;
        $users   = NotifFunctions::prepareUsersItemsToRemove( $usersList );
        $success = NotifFunctions::prepareUsersItemsToInsert( $usersList  );
        $failed  = NotifFunctions::prepareUsersItemsToInsert( $usersList ,2 );
        if ( !empty( $users ) ){
            $wpdb->get_results(
                "UPDATE {$notif} SET 
                 users = JSON_REMOVE( users {$users} )  
                 {$success}  
                 {$failed} 
                WHERE id ={$notifID};"
            );
            if ( !is_wp_error( $wpdb ) && !empty( $wpdb->rows_affected ) ){
                return true;
            }
        }
        return false;
    }


    public static function getSpecificUserItemsByNotifID( $userID ,$notifID )
    {
        global $wpdb;
        $notif  = self::$tableNotif;
        $result =  $wpdb->get_results(
            "SELECT * FROM {$notif} WHERE id = {$notifID} 
                      AND ( JSON_EXISTS( users ,'$.{$userID}') || JSON_EXISTS( success_users ,'$.{$userID}' ) || JSON_EXISTS( failed_users ,'$.{$userID}' ) ) 
                      AND status IN( 1 ,2 )
                ;"
            );
        if ( !is_wp_error( $userID ) && !empty( $result ) ){
            return $result;
        }
        return [];
    }


    public static function updateUserAction( $notifID ,$userID )
    {
        global $wpdb;
        $notif = self::$tableNotif;
        if ( is_numeric( $notifID ) && is_numeric( $userID ) ){
            $wpdb->get_results(
            "UPDATE {$notif}  SET
                   success_users = JSON_SET( success_users , '$.{$userID}[1]' ,CURRENT_TIMESTAMP ) 
                   WHERE id ={$notifID}
                ;"
            );
            if ( !is_wp_error( $wpdb ) && !empty( $wpdb->rows_affected ) ){
                return true;
            }
        }
        return false;
    }


    public static function getSettings()
    {
        return get_option( 'hwp_notif_settings' );
    }


    public static function updateSettings( $settings )
    {
        return update_option( 'hwp_notif_settings' ,$settings ,false );
    }




    public static function getUsersByIDs( $IDs )
    {
        global $wpdb;
        $IDs = NotifFunctions::implodeForQuery( array_unique( $IDs ) );
        $mobile_key = function_exists('hf_user_mobile_meta_key' ) ? \hf_user_mobile_meta_key() : '';
        $result =
            $wpdb->get_results( "
                SELECT users.* ,user_meta_mobile.meta_value AS mobile ,user_meta_profile AS profile FROM {$wpdb->users} AS users
                LEFT JOIN {$wpdb->usermeta} AS user_meta_mobile  ON users.ID = user_meta_mobile.user_id  AND user_meta_mobile.meta_key  = '{$mobile_key}'  
                LEFT JOIN {$wpdb->usermeta} AS user_meta_profile ON users.ID = user_meta_profile.user_id AND user_meta_profile.meta_key = 'profile_pic'  
                WHERE users.ID {$IDs}
            ;"
        );
        if ( empty( $wpdb->last_error ) && !empty( $result ) ){
            return $result;
        }
        return [];
    }


}

