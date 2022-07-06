<?php


namespace Notif\includes\request;




use Notif\includes\modules\NotifRemainder;
use Notif\includes\NotifDB;
use Notif\includes\NotifFunctions;
use Notif\UI\NotifAdminUI;

class NotifAjax
{

    protected static  $_instance = null;
    public static function get_instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public function __construct()
    {
        add_action('wp_ajax_hwp_notif_read_all_sections'  ,[$this , 'notifReads'] );
        add_action('wp_ajax_hwp_notif_create_notif'       ,[$this , 'createNotif'] );
        add_action('wp_ajax_hwp_notif_create_message'     ,[$this , 'createMessage'] );
        add_action('wp_ajax_hwp_notif_create_campaigns'   ,[$this , 'createCampaigns'] );
        add_action('wp_ajax_hwp_notif_create_cron'        ,[$this , 'createCron'] );
        add_action('wp_ajax_hwp_notif_search_products'    ,[$this , 'searchProducts'] );
        add_action('wp_ajax_hwp_notif_search_users'       ,[$this , 'searchUsers'] );
        add_action('wp_ajax_hwp_notif_update_status'      ,[$this , 'updateStatus'] );
        add_action('wp_ajax_hwp_notif_update_bell_seen'   ,[$this , 'updateBellSeen'] );
        add_action('wp_ajax_hwp_notif_test_message'       ,[$this , 'sendTestSMS'] );
        add_action('wp_ajax_hwp_notif_settings'           ,[$this , 'updateSettings'] );



        ///Dynamically Remainder
        add_action('wp_ajax_notif_view_course_remainder' ,[$this , 'viewCourseEvent'] );
        add_action('wp_ajax_notif_exam_done_remainder'   ,[$this , 'doneExamEvent'] );

    }

    public function notifReads()
    {
        NotifFunctions::ajaxRequestValidator(
            ['nonce'=> true ,'which'=> true ,'page'=> false ] , false );
        NotifAdminUI::{$_POST['which']}();
        exit();
    }

    public function createNotif()
    {
        NotifFunctions::ajaxRequestValidator(
            ['nonce'=> true ,'message_title' => true ,'message_content'=> false ,'message_ticket_creator' => false, 'message_ticket_course' => false ,
             'message_sms_text'=> false ,'message_sms_template' => false ,'message_sms_priority'=> false ,'message_cover'=> false ,
             'campaign_from_date_registered'=> false ,'campaign_to_date_registered' => false ,'campaign_additional_users_text'=> false ,
             'campaign_includes_courses'=> false ,'campaign_excludes_courses'=> false ,'campaign_users' => false ,'campaign_webmasteran'=> false ,
             'campaign_instagram'=> false ,'campaign_has_process'=> false ,'cron_start_day'=> false ,
             'cron_end_day'=> false ,'cron_per_time'=> false ,'cron_per_count' => false ,'cron_start_time'=> false ,'cron_end_time'=> false ,
             'cron_more_than'=> false ,'cron_deliver_system'=> true ,'process_name'=> false ,'notif_label' => true , 'status' => true , ]
            ,true
        );
        NotifDB::get_instance()::createNotif();
        exit();
    }


    public function createMessage()
    {
        NotifFunctions::ajaxRequestValidator(
            ['nonce'=> true ,'message_ticket_creator' => false, 'message_ticket_course' => false ,
             'message_title'=> true  ,'message_sms_priority' => false, 'message_sms_text'=> false ,
             'message_sms_template'=> false ,'message_content'=> false ,'message_cover'=> false ] ,
            true
        );
        NotifDB::get_instance()::createMessage();
        exit();
    }


    public function createCampaigns()
    {
        NotifFunctions::ajaxRequestValidator(
            ['nonce'=> true ,'campaign_includes_courses'=> false , 'campaign_additional_users_text' => false ,
            'campaign_excludes_courses'=> false ,'campaign_additional_users'=> false ,'campaign_from_date_registered' => false ,
            'campaign_to_date_registered'=> false ,'campaign_webmasteran' => false ,'campaign_instagram' => false ,'campaign_has_process' => false ],
            true
        );
        NotifDB::get_instance()::createCampaign();
        exit();
    }


    public function createCron()
    {
        NotifFunctions::ajaxRequestValidator(
            ['nonce'=> true ,'cron_deliver_system'=> true ,'cron_status'=> false ,'cron_more_than'=> false ,'cron_start_day'=> false ,
             'cron_end_day'=> false , 'cron_per_time'=> false ,'cron_per_count'=> false ,'cron_start_time'=> false ,'cron_end_time'=> false ] ,
            true
        );
        NotifDB::get_instance()::createCron();
        exit();
    }


    public function searchProducts()
    {
        NotifFunctions::ajaxRequestValidator(
            ['nonce'=> true ,'keyword'=> true ] , false );
        NotifFunctions::searchProducts();
        exit();
    }

    public function searchUsers()
    {
        NotifFunctions::ajaxRequestValidator(
            ['nonce'=> true ,'keyword'=> true ] , false );
        NotifFunctions::searchUser();
        exit();
    }

    public function deleteItem()
    {
        NotifFunctions::ajaxRequestValidator(
            ['nonce'=> true ,'notif_id'=> true ] ,
            true );
        NotifDB::get_instance()::deleteItem( $_POST );
        exit();
    }


    public function updateStatus()
    {
        NotifFunctions::ajaxRequestValidator(
            ['nonce'=> true ,'notif_id'=> true ,'status'=> true  ] , true );
        NotifDB::get_instance()::updateNotifStatus( $_POST );
        exit();
    }


    public function updateSettings()
    {
        NotifFunctions::ajaxRequestValidator(
            ['nonce'=> true ,'settings'=> true ] , true );
        NotifFunctions::get_instance()::updateSettings();
        exit();
    }



    public function updateBellSeen()
    {
        NotifFunctions::ajaxRequestValidator(
            [ 'nonce'=> true ,'notif_id'=> true ] , false );
        NotifFunctions::get_instance()::updateListOfUserSeen( $_POST );
        exit();
    }

    public function sendTestSMS()
    {
        NotifFunctions::ajaxRequestValidator(
            [ 'nonce'=> true ,'message_id'=> true ] , false );
        NotifFunctions::get_instance()::sendTestMessage( $_POST );
        exit();
    }



    ////  Dynamically Remainder
    public function viewCourseEvent()
    {
        NotifFunctions::ajaxRequestValidator(
            [ 'nonce'=> true ,'user_id'=> true ,'course_id'=> true ,'exam_id'=> true ] , false );
        NotifRemainder::get_instance()::viewCourseEvent( $_POST );
        exit();
    }

    public function doneExamEvent()
    {
        NotifFunctions::ajaxRequestValidator(
            [ 'nonce'=> true ,'user_id'=> true ,'course_id'=> true ,'exam_id'=> true ] , false );
        NotifRemainder::get_instance()::doneExamEvenet( $_POST );
        exit();
    }



}