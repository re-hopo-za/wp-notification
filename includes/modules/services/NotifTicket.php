<?php



namespace Notif\includes\modules\services;


use HWP_Ticket\core\includes\Functions;
use Notif\includes\NotifCron;
use Notif\includes\NotifFunctions;

class NotifTicket
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
        return self::createTicket( $message ,$user );
    }


    public static function createTicket( $item ,$user )
    {
        if( is_plugin_active('hwp-ticket/Ticket.php') ) {
            $data = [
                'title'        => NotifFunctions::indexChecker( $item  ,'message_title' , 'بدون عنوان' )  ,
                'content'      => NotifCron::createFinalMessage( $user ,NotifFunctions::indexChecker( $item  ,'message_text' , 'بدون عنوان' )  ) ,
                'creator'      => NotifFunctions::indexChecker( $item  ,'message_ticket_creator' , 0 ) ,
                'destination'  => 'tango_support'   ,
                'main_object'  => NotifFunctions::indexChecker( $item  ,'message_ticket_course' , 'notif' ) ,
                'assign_to'    => $user->ID         ,
            ];
            $new_id =  Functions::createTicketDirectly( $data ,$user->ID ,NotifFunctions::indexChecker( $item  ,'message_ticket_creator' , 0 ) );
            if ( $new_id ){
                return true;
            }
        }
        return false;
    }


}