<?php


namespace Notif\includes\modules\services;


use Notif\includes\NotifStatic;
use Notif\includes\NotifFunctions;

class NotifSMS
{


    public static $tester_phone   = '09355882099';
    public static $tester_user_id = 57370;
    protected static $_instance   = null;
    public static function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public static function terminal_1( $item ,$user )
    {
        $message      = NotifFunctions::jsonDecodeDbColumn( $item ,'message' );
        $sms_text     = NotifFunctions::indexChecker( $message ,'message_sms_text' );
        $sms_template = NotifFunctions::indexChecker( $message ,'message_sms_template' );
        $sms_priority = NotifFunctions::indexChecker( $message ,'message_sms_priority' );

        $tokens = self::prepareMessage( $sms_text ,$user );

        if ( !empty( $user->mobile ) && is_numeric( \get_valid_mobile( $user->mobile ) ) ){
            cron_send_sms( $user->mobile ,$sms_template ,$sms_priority ,$tokens[1] ,$tokens[2] ,$tokens[3] );
            return true;
        }
        return false;
    }

    public static function prepareMessage( $smsText ,$user )
    {
        $token        = [ 1 => '' , 2 => '' , 3 => '' ];
        $sms_tokens   = [];
        $tokens_count = 0;
        if ( !empty( $smsText ) ){
            $sms_tokens = explode('||' ,$smsText );
            if ( !empty( $sms_tokens ) && is_array( $sms_tokens ) ){
                $tokens_count = count( $sms_tokens );
            }
        }
        if ( $tokens_count > 0 ){
            if ( $sms_tokens[0] && !empty( $sms_tokens[0] ) ){
                $token[1] = NotifStatic::getUserDate( $user ,$sms_tokens[0] );
            }
            if ( isset( $sms_tokens[1] ) && $sms_tokens[1] ){
                $token[2] = NotifStatic::getUserDate( $user ,$sms_tokens[1] );
            }
            if ( isset( $sms_tokens[2] ) && $sms_tokens[2] ){
                $token[3] = NotifStatic::getUserDate( $user ,$sms_tokens[2] );
            }
        }
        return $token;
    }


    public static function testSMS( $object )
    {
        $user_object = get_user_by('id' , self::$tester_user_id );
        $tokens = self::prepareMessage( $object->sms_text ,$user_object );
        if ( self::$tester_phone && is_numeric( \get_valid_mobile( self::$tester_phone ) ) ){
            cron_send_sms( self::$tester_phone ,$object->sms_template  ,$object->sms_priority ,$tokens[1] ,$tokens[2] ,$tokens[3] );
        }
        return true;
    }


    public static function terminal_2( $item ,$user )
    {
        if ( !empty( $user->mobile ) && is_numeric( \get_valid_mobile( $user->mobile ) ) ){
            cron_send_sms( $user->mobile ,'notifmessage1' ,99 ,$user->display_name ,home_url('/my/pm') );
            return true;
        }
        return false;
    }




}