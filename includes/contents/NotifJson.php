<?php


namespace Notif\includes\contents;


use Notif\includes\NotifDB;
use Notif\includes\NotifFunctions;

class NotifJson
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

    public static function getAll( $userID )
    {
        $all  = [];
        $items = NotifDB::get_instance()::getAllUserInbox( $userID );
        if ( $items  ){
            foreach ( $items as $item ){
                $all[ $item->id ] = [
                    'title'      => NotifFunctions::indexChecker( $item, 'title' ) ,
                    'plain_text' => NotifFunctions::indexChecker( $item, 'plain_text' ) ,
                    'cover'      => NotifFunctions::indexChecker( $item, 'cover' ) ,
                    'created_at' => NotifFunctions::indexChecker( $item, 'created_at' )
                ];
            }
        }
        return $all;
    }



}