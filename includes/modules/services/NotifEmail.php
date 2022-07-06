<?php


namespace Notif\includes\modules\services;


use Notif\includes\NotifCron;
use Notif\includes\NotifFunctions;

class NotifEmail
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
    { }

    public static function terminal_1( $item ,$user )
    {
        $message = NotifFunctions::jsonDecodeDbColumn( $item ,'message' );
        $text    = self::beforeSend( $item->content ,$user );
        if ( !empty( $user->user_email ) && is_email( $user->user_email ) ){
            sleep(1 );
            wp_mail( $user->user_email ,$message->message_title ,$text  );
            return true;
        }
        return false;
    }


    public static function beforeSend( $message ,$user )
    {
        if ( !empty( $message ) && !empty( $user ) ){
            return NotifCron::createFinalMessage( $user ,$message );
        }
        return false;
    }



}