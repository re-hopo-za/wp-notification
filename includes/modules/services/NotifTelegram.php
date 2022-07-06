<?php


namespace Notif\includes\modules\services;


class NotifTelegram
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

    }

    public static function terminal_1( $object ,$user )
    {
        return true;
    }



}