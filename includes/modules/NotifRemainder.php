<?php

namespace Notif\includes\modules;


use Notif\includes\NotifCron;
use Notif\includes\NotifDB;
use Notif\includes\NotifFunctions;
use Notif\vendor\number_to_word\NumberToWord;



class NotifRemainder
{

    protected static $_instance = null;
    public static function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public static function run( $notif_object )
    {
        NotifDB::get_instance()::handleCronRunningStatus( $notif_object->cron_id ,1 );
        $items = self::remainderTable();
        if ( !empty( $items ) ){
            $filter_course = maybe_unserialize( $notif_object->filters );
            $filter_course = $filter_course['include_p'] ?? '';
            if ( !empty( $filter_course ) && is_array( $filter_course ) ){
                $update_users = [];
                $i_user = 1;
                foreach ( $items as $list ){
                    $similar_key = array_intersect_key( array_keys( (array) $list->logs ) ,$filter_course );
                    if ( !empty( $similar_key ) ){
                        $system_list = NotifCron::getDeliverSystem( $notif_object );
                        if ( $notif_object->more_system == 0 ){
                            if ( $i_user <= $notif_object->per_count ){
                                $recive_status = true;
                                foreach ( $system_list as $sys ){
                                    if ( $recive_status && NotifCron::switchDeliverSystem( $sys ,$notif_object ,$list ) ){
                                        $recive_status = false;
                                        $update_users[]= $list->ID;
                                    }
                                }
                                $i_user++;
                            }
                        }else if ( $i_user <= $notif_object->per_count ){
                            foreach ( $system_list as $sys ){
                                if ( NotifCron::switchDeliverSystem( $sys ,$notif_object ,$list ) ){
                                    if( !isset( $update_users[ $list->ID ] ) ){
                                        $update_users[]= $list->ID;
                                    }
                                }
                            }
                            $i_user++;
                        }
                    }
                }
                NotifDB::get_instance()::updateLastRun( $notif_object->cron_id );
                self::updateRemainderList( $update_users );
            }
        }
        NotifDB::get_instance()::handleCronRunningStatus( $notif_object->cron_id ,0 );
    }


    public static function remainderTable()
    {
        global $wpdb;
        $table_watched  = $wpdb->prefix.'users_watched_list';
        $final_result   = new \stdClass();
        $results = $wpdb->get_results(
            "SELECT watched.* ,user_meta_m.meta_value AS mobile ,posts.post_title AS title ,users.user_email AS email  FROM {$table_watched} AS watched 
                    INNER JOIN {$wpdb->users} AS users ON watched.ID = users.ID 
                    INNER JOIN {$wpdb->usermeta} AS user_meta_m ON watched.ID = user_meta_m.user_id AND user_meta_m.meta_key = 'force_verified_mobile'
                    INNER JOIN {$wpdb->posts} AS posts ON watched.course_id = posts.ID  
                    WHERE watched.log IS NOT NULL ; "
        );
        if ( !is_wp_error( $results ) && !empty( $results ) ){
            foreach ( $results as $result ){
                $userID = $result->ID;
                $final_result->{$userID}  =  new \stdClass();
                $final_result->{$userID}->ID     = $result->ID;
                $final_result->{$userID}->email  = $result->email;
                $final_result->{$userID}->mobile = $result->mobile;
                $final_result->{$userID}->title  = new \stdClass();
                $final_result->{$userID}->logs   = new \stdClass();
                $final_result->{$userID}->title->{$result->course_id} = $result->title;
                $final_result->{$userID}->logs->{$result->course_id}  = $result->log;
            }
            return $final_result;
        }
        return [];
    }


    public static function remainderText( $object )
    {
        $final_text = ' آزمون های ';
        foreach ( $object->logs as $key => $val ){
            $logs = maybe_unserialize( $val );
            if (!empty( $logs ) && is_array( $logs ) ) {
                foreach ( $logs as $course ){
                    $final_text  .= NumberToWord::get_instance()->numberToWords( $course );
                    if ( count( $logs ) > 1 && end($logs ) != $course ){
                        $final_text .= ' و ';
                    }
                }

                $final_text .= ' از '. $object->title->{$key};
            }
        }
        return $final_text;
    }


    public static function updateRemainderList( $users )
    {
        global $wpdb;
        $table_watched  = $wpdb->prefix.'users_watched_list';
        $imploded_users = implode( ',' ,array_unique( $users ) );
        $wpdb->query("
            UPDATE {$table_watched}
            SET log = NULL   
            WHERE ID IN ($imploded_users)"
        );
        return $wpdb;
    }


    public static function viewCourseEvent( $parameters )
    {
        global $wpdb;
        $table_watched  = $wpdb->prefix.'users_watched_list';
        $courseID = (int) $parameters['course_id'];
        $examID   = (int) $parameters['exam_id'];
        $userID   = (int) $parameters['user_id'];
        $item = self::getItem( $userID ,$courseID );
        if ( empty( $item ) ){
            $item = self::addItem( $userID ,$courseID );
        }
        if ( !empty( $item ) ){
            $log = !empty( $item->log ) ? $item->log : [];
            $courses = maybe_unserialize( $log );
            if ( !in_array( $examID ,$courses ) ){
                $courses[] = $examID;
                $courses   = serialize( $courses );
                $up_result = $wpdb->query(
                    "UPDATE {$table_watched} SET log ='$courses' WHERE ID={$userID} AND course_id = {$courseID}"
                );
                if( !empty( $wpdb->last_error ) && $up_result ){
                    NotifFunctions::sendJsonResult( true ,200 );
                }
                NotifFunctions::sendJsonResult( true ,500 );
            }
            NotifFunctions::sendJsonResult( true ,200 );
        }
    }


    public static function doneExamEvenet( $parameters )
    {
        global $wpdb;
        date_default_timezone_set('Asia/Tehran');
        $table_watched  = $wpdb->prefix.'users_watched_list';
        $courseID = $parameters['course_id'];
        $examID   = (int) $parameters['exam_id'];
        $userID   = $parameters['user_id'];
        $item = self::getItem( $userID ,$courseID );
        if ( empty( $item ) ){
            $item = self::addItem( $userID ,$courseID );
        }
        if ( !empty( $item ) ){
            $courses = unserialize( $item->log );
            if ( is_array( $courses ) && in_array( $examID ,$courses ) ){
                $courses = array_flip( $courses );
                unset( $courses[$examID] );
                $courses = array_flip( $courses );
                if( !empty( $courses ) ){
                    $courses   = serialize( $courses );
                }else{
                    $courses = NULL;
                }
                $now = date('Y-m-d H:i:s' ,strtotime("now", time() ) );
                $up_result = $wpdb->query(
                    "UPDATE {$table_watched} SET log = '$courses' ,updated_at ={$now} WHERE ID = {$userID} AND course_id = {$courseID}"
                );
                if( !empty( $wpdb->last_error ) && $up_result ){
                    NotifFunctions::sendJsonResult( true ,200 );
                }
                NotifFunctions::sendJsonResult( true ,500 );
            }
            NotifFunctions::sendJsonResult( true ,200 );
        }
    }


    public static function getItem( $userID ,$courseID )
    {
        global $wpdb;
        $table_watched  = $wpdb->prefix.'users_watched_list';
        $results = $wpdb->get_results(
            "SELECT * FROM {$table_watched} WHERE ID = {$userID} AND course_id = {$courseID}; "
        );
        if ( !is_wp_error( $results ) && !empty( $results ) ){
            return $results[0];
        }
        return [];
    }


    public static function addItem( $userID ,$courseID )
    {
        date_default_timezone_set('Asia/Tehran');
        global $wpdb;
        $table_watched  = $wpdb->prefix.'users_watched_list';
        $data   = ['ID' => $userID, 'course_id' => $courseID ,'updated_at' => date('Y-m-d H:i:s' ,strtotime("now", time() ) ) ];
        $format = ['%d','%d'];
        $wpdb->insert( $table_watched ,$data ,$format);
        $insertedID = $wpdb->insert_id;
        if ( !is_wp_error( $insertedID ) && is_numeric( $insertedID ) ){
            $new_item = new \stdClass();
            $new_item->ID = $userID;
            $new_item->course_id = $userID;
            $new_item->log = [];
            return $new_item;
        }
        return false;
    }


}