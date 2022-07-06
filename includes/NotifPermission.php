<?php


namespace Notif\includes;


class NotifPermission
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

    public static function registration()
    {
    }


    public static function AccessChecker( $creatorChecker = false )
    {
        $user_capability = get_user_meta('hwp_user_notif_capability' );
        if ( $creatorChecker && ( current_user_can('administrator' ) || $user_capability === 'creator' ) ){
            return (object)['error' => false ];
        }
        elseif ( !$creatorChecker && ( current_user_can('administrator' ) || $user_capability === 'reader' || $user_capability == 'creator') ){
            return (object)['error' => false ];
        }
        return (object) ['error' => true , 'message' => 'You do not have permission to access this page' ];
    }


    public static function nonceChecker()
    {
        if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'] ,'notif_admin_nonce')  ){
            return (object)['error' => false ];
        }
        return (object)['error' => false , 'message' => 'nonce error'];
    }



}