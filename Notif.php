<?php
/**
 * Plugin Name:       Notif
 * Version:           1.6
 * Author:            reza hossein pour
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */


namespace Notif;


use Notif\includes\NotifEnqueues;
use Notif\includes\NotifCron;
use Notif\includes\NotifPermission;
use Notif\includes\request\NotifAjax;
use Notif\UI\NotifAdminUI;
use Notif\UI\NotifFrontUI;

if (!defined('WPINC')) die();

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

class Notif
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
        self::defines();
        self::includes();
        NotifCron::customIntervalCronTime();
        register_activation_hook(__FILE__ ,[$this , 'install']);
        add_action('init', [$this,'run']);
    }

    public static function defines()
    {
        define( 'HAMYAR_NOTIFICATION_DEVELOPER_MODE'  , false );
        define( 'HAMYAR_NOTIFICATION_VERSION'         , '1.7.1' );
        define( 'HAMYAR_NOTIFICATION_ROOT'            , plugin_dir_path(__FILE__) );
        define( 'HAMYAR_NOTIFICATION_INCLUDES'        , plugin_dir_path(__FILE__) . 'includes/' );
        define( 'HAMYAR_NOTIFICATION_ASSETS'          , plugin_dir_url(__FILE__)  . 'assets/' );
        define( 'HAMYAR_NOTIFICATION_PAGES'           , plugin_dir_path(__FILE__) . 'pages/' );
        define( 'HAMYAR_NOTIFICATION_SCRIPTS_VERSION' ,
            HAMYAR_NOTIFICATION_DEVELOPER_MODE
                ? time()
                : HAMYAR_NOTIFICATION_VERSION
        );
        date_default_timezone_set('Asia/Tehran');
    }

    public static function includes()
    {
//        require_once HAMYAR_NOTIFICATION_ROOT . '/vendor/autoload.php';
    }

    public function install()
    {
        NotifPermission::registration();
        NotifCron::schedule();
    }

    public function run()
    {

        NotifCron::get_instance();
//        NotifRest::get_instance();
        NotifAjax::get_instance();

        NotifEnqueues :: get_instance();
        NotifAdminUI  :: get_instance();
        NotifFrontUI  :: get_instance();

//        $user = get_user_by( 'id' ,get_current_user_id() );
//        var_dump(NotifCron::createFinalMessage($user , '[user_login] سلام [display_name] {wi1ip_capabilities}  {first_name}  {last_name} داداش [{remainder}]'));
    }


}

Notif::get_instance();





















