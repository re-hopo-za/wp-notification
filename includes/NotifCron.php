<?php


namespace Notif\includes;

 


use Notif\includes\modules\services\NotifEmail;
use Notif\includes\modules\services\NotifPush;
use Notif\includes\modules\services\NotifSMS;
use Notif\includes\modules\services\NotifTelegram;
use Notif\includes\modules\services\NotifTicket;

class NotifCron
{

    protected static $_instance = null;
    public static function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        add_action('notif_crons_action' ,[ $this ,'checkNotif'] );
    }


    public static function checkNotif()
    {
        $static = NotifDB::get_instance()::getCronLists(false );
        if  ( !empty( $static ) ){
            NotifStatic::preparingList( $static );
        }
//        $dynamic = NotifDB::get_instance()::getCronLists( true );
//        if ( !is_wp_error( $dynamic ) && !empty( $dynamic ) ){
//            NotifDynamic::get_instance()->preparingList( $dynamic );
//        }
//        $time = date('H:i:s' );
//        if( $time > '02:00:00' && $time < '05:00:00' ){
//            NotifStatic::updateUsersList();
//        }
    }


    public static function createFinalMessage( $user ,$text )
    {
        $final_text = [];
        $explode = explode( ' ' , $text );
        foreach ( $explode as $word ){
            if( strpos( $word, '[{') !== false ){
                $word = str_replace(' ' , '' ,str_replace('[{' , '' ,str_replace('}]' , '' ,$word ) ));
                if ( method_exists('NotifDynamic' ,$word.'Text' ) ){
                    $user_data    = NotifDynamic::{$word.'Text'}( $user );
                    $final_text[] = $user_data;
                }
            }
            else if ( is_object( $user ) && strpos( $word, '[') !== false ){
                $word = str_replace(' ' , '' ,str_replace('[' , '' ,str_replace(']' , '' ,$word ) ));
                if ( isset( $user->{$word} ) ){
                    $final_text[] = $user->{$word};
                }
            }
            else if( is_object( $user ) && strpos( $word, '{') !== false ){
                $word         = str_replace(' ' , '' ,str_replace('{' , '' ,str_replace('}' , '' ,$word ) ));
                $final_text[] = self::checkMetaArrayValue( get_user_meta( $user->ID , $word , true ) );
            }
            else{
                $final_text[] = $word;
            }
        }
        return implode( ' ' , $final_text );
    }



    public static function getDeliverSystem( $cron )
    {
        $system_list = [];
        if ( isset( $cron->cron_deliver_system  ) && !empty( $cron->cron_deliver_system ) ){
            foreach ( $cron->cron_deliver_system as $system ){
                if ( $system->status == "true" ){
                    $system_list[] = $system->id;
                }
            }
        }
        return $system_list;
    }


    public static function switchDeliverSystem( $sys ,$item ,$user )
    {
        switch ( $sys ){
            case 'sms_1' :
                return NotifSMS::terminal_1( $item ,$user );
            case 'email_1' :
                return NotifEmail::terminal_1( $item ,$user );
            case 'ticket' :
                return NotifTicket::terminal_1( $item ,$user );
            case 'telegram' :
                return NotifTelegram::terminal_1( $item ,$user );
            case 'alert' :
                return NotifPush::terminal_1( $item ,$user );
            case 'sms_2' :
                return NotifSMS::terminal_2( $item ,$user );

        }
        return false;
    }


    public static function customIntervalCronTime()
    {
        add_filter( 'cron_schedules' ,function ( $schedules ){
            if( !isset( $schedules['notif_1_minutes'] ) )
            {
                $schedules['notif_1_minutes'] = [
                    'display'  => 'Every 1 Minutes' ,
                    'interval' => 60
                ];
            }
            return $schedules;
        });
    }


    public static function schedule() {
        if ( !wp_next_scheduled('notif_crons_action') ) {
            wp_schedule_event( time(), 'notif_1_minutes', 'notif_crons_action' );
        }
    }


    public static function checkMetaArrayValue( $userMeta )
    {
        $output = '';
        if ( !empty( $userMeta ) ){
            $userMeta = maybe_unserialize( $userMeta );
            if ( is_array( $userMeta ) ){
                $output = '';
                foreach ( $userMeta as $key => $val ){
                    $output .= ' '.$key . ' : ' .$val.' ';
                }
            }
        }
        return $output;
    }

}

