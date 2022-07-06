<?php

namespace Notif\includes;


use Notif\includes\modules\NotifRemainder;

class NotifDynamic
{


    protected static $_instance = null;
    public static function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function preparingList( $items )
    {
        foreach ( $items as $item ){
            if ( !empty( $item->process_name ) && method_exists( $this , $item->process_name ) ){
                self::{$item->process_name}( $item );
            }
        }
    }

    public static function remainder( $object )
    {
         NotifRemainder::run( $object );
    }

    public static function remainderText( $object )
    {
        NotifRemainder::remainderText( $object );
    }







}



