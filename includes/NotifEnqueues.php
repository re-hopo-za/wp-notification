<?php

namespace Notif\includes;

use Notif\UI\NotifAdminUI;

class NotifEnqueues
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
        add_action('wp_enqueue_scripts'    ,[ $this ,'frontEnqueues'  ] ,99 );
        add_action('admin_enqueue_scripts' ,[ $this ,'adminEnqueues'  ] ,99 );
    }


    public function frontEnqueues()
    {
        if (strpos( $_SERVER['REQUEST_URI']  ,'/pm') ){
            wp_enqueue_script(
                'notif_public_js' ,
                HAMYAR_NOTIFICATION_ASSETS.'public/notif-public.js' ,
                [ 'jquery'] ,
                HAMYAR_NOTIFICATION_SCRIPTS_VERSION
            );
            wp_localize_script(
                'notif_public_js' ,
                'notif_objects' ,
                [
                    'home_url'   => home_url(),
                    'ajax_url'   => admin_url( 'admin-ajax.php' ),
                    'nonce'      => wp_create_nonce('notif_public_nonce') ,
                    'btn_loader' => NotifAdminUI::loaderElement( false )
                ]
            );
            wp_enqueue_style(
                'notif_public_css' ,
                HAMYAR_NOTIFICATION_ASSETS.'public/notif-public.css' ,
                false,
                HAMYAR_NOTIFICATION_SCRIPTS_VERSION
            );
        }
    }


    public static function adminEnqueues()
    {
        if ( isset( $_GET['page'] ) && in_array( $_GET['page'] ,['notifList','notifNew','notifStatus','notifSettings','notifLogs'] ) ){
            wp_enqueue_script(
                'notif_persian_datepicker_js' ,
                HAMYAR_NOTIFICATION_ASSETS.'admin/library/persianDatepicker.js'
            );
            wp_enqueue_script(
                'notif_timepicker_js' ,
                HAMYAR_NOTIFICATION_ASSETS.'admin/library/timepicker.min.js'
            );
            wp_enqueue_script(
                'notif_sortable_js' ,
                HAMYAR_NOTIFICATION_ASSETS.'admin/library/sortable.min.js'
            );
            wp_enqueue_script(
                'notif_ckeditor_js' ,
                HAMYAR_NOTIFICATION_ASSETS.'admin/library/ckeditor.js'
            );
            wp_enqueue_script(
                'notif_izi_toast_js' ,
                HAMYAR_NOTIFICATION_ASSETS.'admin/library/izi-toast.min.js'
            );
            wp_enqueue_script(
                'notif_admin_js' ,
                HAMYAR_NOTIFICATION_ASSETS.'admin/notif-admin.js' ,
                [ 'jquery' ,'notif_persian_datepicker_js' ,'notif_timepicker_js' ,'notif_sortable_js' ,'notif_ckeditor_js','notif_izi_toast_js'] ,
                HAMYAR_NOTIFICATION_SCRIPTS_VERSION ,
                true
            );

            wp_localize_script(
                'notif_admin_js' ,
                'notif_objects' ,
                [
                    'home_url'        => home_url(),
                    'admin_url'       => admin_url( 'admin-ajax.php' ),
                    'settings'        => NotifFunctions::getSettings(),
                    'nonce'           => wp_create_nonce('notif_admin_nonce') ,
                    'campaign_filter' => NotifAdminUI::filterItemInCampaign() ,
                    'main_loader'     => NotifAdminUI::loaderElement() ,
                    'btn_loader'      => NotifAdminUI::loaderElement( false )
                ]
            );

            wp_enqueue_style(
                'notif_izi_toast_css' ,
                HAMYAR_NOTIFICATION_ASSETS.'admin/library/izi-toast.min.css'
            );
            wp_enqueue_style(
                'notif_timepicker_css' ,
                HAMYAR_NOTIFICATION_ASSETS.'admin/library/timepicker.min.css'
            );
            wp_enqueue_style(
                'notif_admin_css' ,
                HAMYAR_NOTIFICATION_ASSETS.'admin/notif-admin.css' ,
                ['select2'],
                HAMYAR_NOTIFICATION_SCRIPTS_VERSION
            );
        }
    }

}