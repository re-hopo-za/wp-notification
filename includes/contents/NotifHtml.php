<?php


namespace Notif\includes\contents;



use Notif\includes\NotifCron;
use Notif\includes\NotifDB;
use Notif\includes\NotifFunctions;
use Notif\UI\NotifAdminUI;use Notif\UI\NotifIcons;

class NotifHtml{


    protected static  $_instance = null;
    public static function get_instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public  function __construct() {
    }


    public static function notifItems()
    {
        $items  = NotifDB::get_instance()::getAll( 0 ,NotifFunctions::getUrlStatusWhere() );
        if ( !empty( $items ) ){
            foreach ( $items as $item ){
                $raw_users     = NotifFunctions::jsonDecodeDbColumn( $item ,'users' );
                $failed_users  = NotifFunctions::jsonDecodeDbColumn( $item ,'failed_users' );
                $success_users = NotifFunctions::jsonDecodeDbColumn( $item ,'success_users' );
                $all_user      = count( (array) $raw_users ) + count( (array) $failed_users ) + count( (array) $success_users );
                ?>
                    <div class="notif-item status-<?php echo NotifFunctions::indexChecker( $item ,'status' ,0 ) == 1 ? 'play' : 'pause'; ?>" >
                        <a href="<?php echo admin_url('admin.php?page=notifList&id='.$item->id); ?>" >
                            <div class="top">
                                <b><span>#</span><?php echo $item->id; ?></b>
                                <h6><?php echo NotifFunctions::indexChecker( $item ,'label' ,'بدون لیبل'); ?></h6>
                            </div>
                            <div class="middle">
                                <div class="campaign">
                                    <?php if ( empty( $item->prosecc_name ) ){ ?>
                                        <div class="item" title="Pending Users">
                                            <?= NotifIcons::user(); ?>
                                            <span><?php echo count((array) $raw_users ); ?></span>
                                        </div>
                                        <div class="item" title="Success Users">
                                            <?= NotifIcons::userSuccess(); ?>
                                            <span><?php echo count((array) $success_users ); ?></span>
                                        </div>
                                        <div class="item" title="Failed Users">
                                            <?= NotifIcons::userFailed(); ?>
                                            <span><?php echo count ((array) $failed_users ); ?></span>
                                        </div>
                                        <div class="item" title="All Users">
                                            <?= NotifIcons::users(); ?>
                                            <span><?php echo $all_user; ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </a>
                        <div class="bottom" data-notif-id="<?php echo $item->id; ?>" >
                            <i class="delete-notif-item dashicons dashicons-trash "></i>
                            <i class="update-notif-status dashicons  dashicons-controls-<?php echo NotifFunctions::indexChecker( $item ,'status' ,0 ) == 1 ? 'pause' : 'play'; ?>"
                               title=" تغییر وضعیت به <?php echo NotifFunctions::indexChecker( $item ,'status' ,0 ) == 1 ? 'غیر فعال' : 'فعال'; ?> " >
                            </i>
                        </div>
                    </div>
                <?php
            }
        }else{
            NotifAdminUI::emptyList();
        }
    }


    public static function notifSingle()
    {
        $notif_id  = NotifFunctions::indexChecker( $_GET ,'id' ,false );
        $single_ui = NotifAdminUI::notifNewContent();
        if ( $notif_id ){
            $item = NotifDB::get_instance()::getSingle( $notif_id );
            if ( !empty( $item ) ){
                $message   = NotifFunctions::jsonDecodeDbColumn( $item ,'message' );
                $campaign  = NotifFunctions::jsonDecodeDbColumn( $item ,'campaign' );
                $cron      = NotifFunctions::jsonDecodeDbColumn( $item ,'cron' );

                if ( is_object( $message ) && !empty( $message ) && isset( $message->message_title )  ){
                    foreach ( $message as $key => $value ){
                        if ( $key == 'message_cover' && !empty( $value ) ){
                            $single_ui = str_replace( '['.$key.']'  ,NotifFunctions::getNotifCover( $value ) ,$single_ui );
                        }else{
                            $single_ui = str_replace( '['.$key.']'  ,$value ,$single_ui );
                        }
                    }
                }else{
                    $single_ui =
                        str_replace([
                            '[message_title]' ,'[message_sms_text]','[message_sms_template]',
                            '[message_sms_priority]','[message_cover]','[message_ticket_creator]','[message_ticket_course]'
                        ] ,'' ,$single_ui
                    );
                }
                if ( is_object( $campaign ) && !empty( $campaign ) && isset( $campaign->campaign_webmasteran )){
                    foreach ( $campaign as $key => $value ){
                        if ( $key == 'campaign_includes_courses' || $key == 'campaign_excludes_courses' ){
                            $single_ui = str_replace( '['.$key.']' ,self::campaignCourseList( $value ) ,$single_ui );
                        }
                        elseif( $key == 'campaign_from_date_registered' || $key == 'campaign_to_date_registered'  ) {
                            $single_ui = str_replace( '['.$key.']' ,NotifFunctions::dateConvert( $value ,$value ) ,$single_ui );
                        }
                        elseif( $key == 'campaign_additional_users' ) {
                            $single_ui = str_replace( '['.$key.']' ,self::campaignUsersList( $value ) ,$single_ui );
                        }
                        elseif( $key == 'campaign_webmasteran' || $key == 'campaign_instagram') {
                            if ( $value == 'true' ){
                                $single_ui = str_replace( '['.$key.']'  ,'checked="checked"' ,$single_ui );
                            }
                        }
                        else{
                        $single_ui = str_replace( '['.$key.']'  ,$value ,$single_ui );
                       }
                    }

                }else{
                    $single_ui =
                        str_replace([
                            '[campaign_webmasteran]','[campaign_instagram]','[campaign_includes_courses]','[campaign_excludes_courses]','[campaign_users_count]',
                            '[campaign_from_date_registered]','[campaign_to_date_registered]','[campaign_additional_users]','[campaign_additional_users_text]','[campaign_has_process]'
                        ],'' ,$single_ui
                    );
                }
                if ( is_object( $cron ) && !empty( $cron ) && isset( $cron->cron_deliver_system ) ){
                    foreach ( $cron as $key => $value ){
                        if ( $key == 'cron_deliver_system' ) {
                            $single_ui = str_replace('[' . $key . ']', NotifAdminUI::deliverSystemHandler($value), $single_ui);
                        }
                        elseif( ( $key == 'cron_per_time'  ) ) {
                            $single_ui = str_replace( '['.$key.']'  ,NotifAdminUI::returnPerTimeView( $value ) ,$single_ui );
                        }
                        elseif( ( $key == 'cron_start_day'  ) ) {
                            $single_ui = str_replace( '['.$key.']' ,NotifFunctions::dateConvert( $value ,date('Y/m/d') ) ,$single_ui );
                        }else{
                            $single_ui = str_replace( '['.$key.']'  ,$value ,$single_ui );
                        }
                    }
                }else{
                    $single_ui = str_replace( '[cron_deliver_system]' ,NotifAdminUI::deliverSystemHandler() ,$single_ui );
                    $single_ui = str_replace( '[cron_per_time]' ,NotifAdminUI::returnPerTimeView() ,$single_ui );
                    $single_ui = str_replace([
                            '[cron_start_day]','[cron_end_day]','[cron_start_time]',
                            '[cron_per_count]','[cron_end_time]','[cron_status_disable]','[cron_status_enable]'
                        ],'' ,$single_ui
                    );
                }
                $single_ui = str_replace( '[notif_label]'     ,NotifFunctions::indexChecker( $item ,'label') ,$single_ui );
                $single_ui = str_replace( '[status_enable]'   ,NotifFunctions::checkStatusInput( $item->status ,1 ) ,$single_ui );
                $single_ui = str_replace( '[status_disable]'  ,NotifFunctions::checkStatusInput( $item->status ,0 ) ,$single_ui );
                $single_ui = str_replace( '[message_content]' ,NotifFunctions::indexChecker( $item ,'content') ,$single_ui );
                $single_ui = str_replace( '[notif_process_name]'  ,$item->process_name ,$single_ui );
                if ( $item->status == 0 ){
                    $single_ui = str_replace( '[save_button]' ,'<button id="save-notif"> Save Notif</button>',$single_ui );
                }else{
                    $single_ui = str_replace( '[save_button]' ,'<p> در حال اجرا </p>',$single_ui );
                }
            }else{
                $single_ui = '404';
            }
        }
        return $single_ui;
    }


    public static function campaignCourseList( $courses )
    {
        $output = '';
        if ( !empty( $courses ) && is_array( $courses ) ){
            foreach ( $courses as $course ){
                $items   = str_replace('[course_name]' ,NotifFunctions::getCourseNameByID( $course ) ,NotifAdminUI::campaignCourseList() );
                $output .= str_replace('[course_id]'   ,$course ,$items );
            }
        }
        return $output;
    }


    public static function campaignUsersList( $users )
    {
        $output = '';
        if ( !empty( $users ) && is_array( $users ) ){
            foreach ( $users as $user ){
                $items   = str_replace('[course_name]' ,NotifFunctions::getUserNameByID( $user ) ,NotifAdminUI::campaignCourseList() );
                $output .= str_replace('[course_id]'   ,$user ,$items );
            }
        }
        return $output;
    }

    public static function notifNew()
    {
        $new_ui = str_replace( '[message_title]'                  ,'' ,NotifAdminUI::notifNewContent() );
        $new_ui = str_replace( '[message_content]'                ,'' ,$new_ui );
        $new_ui = str_replace( '[message_sms_text]'               ,'' ,$new_ui );
        $new_ui = str_replace( '[message_sms_template]'           ,'' ,$new_ui );
        $new_ui = str_replace( '[message_sms_priority]'           ,'' ,$new_ui );
        $new_ui = str_replace( '[message_cover]'                  ,'' ,$new_ui );
        $new_ui = str_replace( '[message_ticket_creator]'         ,'' ,$new_ui );
        $new_ui = str_replace( '[message_ticket_course]'          ,'' ,$new_ui );
        $new_ui = str_replace( '[campaign_webmasteran]'           ,'' ,$new_ui );
        $new_ui = str_replace( '[campaign_instagram]'             ,'' ,$new_ui );
        $new_ui = str_replace( '[campaign_includes_courses]'      ,'' ,$new_ui );
        $new_ui = str_replace( '[campaign_excludes_courses]'      ,'' ,$new_ui );
        $new_ui = str_replace( '[campaign_from_date_registered]'  ,'' ,$new_ui );
        $new_ui = str_replace( '[campaign_to_date_registered]'    ,'' ,$new_ui );
        $new_ui = str_replace( '[campaign_additional_users]'      ,'' ,$new_ui );
        $new_ui = str_replace( '[campaign_additional_users_text]' ,'' ,$new_ui );
        $new_ui = str_replace( '[campaign_users_count]'           ,'' ,$new_ui );
        $new_ui = str_replace( '[campaign_has_process]'           ,'' ,$new_ui );
        $new_ui = str_replace( '[cron_deliver_system]'            ,NotifAdminUI::deliverSystemHandler() ,$new_ui);
        $new_ui = str_replace( '[cron_start_day]'                 ,date('Y/m/d' ) ,$new_ui );
        $new_ui = str_replace( '[cron_per_time]'                  ,NotifAdminUI::returnPerTimeView() ,$new_ui );
        $new_ui = str_replace( '[cron_end_day]'                   ,'' ,$new_ui );
        $new_ui = str_replace( '[cron_start_time]'                ,date('H:i')  ,$new_ui );
        $new_ui = str_replace( '[cron_per_count]'                 ,'20' ,$new_ui );
        $new_ui = str_replace( '[cron_end_time]'                  ,'' ,$new_ui );
        $new_ui = str_replace( '[status_disable]'                 ,'checked' ,$new_ui );
        $new_ui = str_replace( '[status_enable]'                  ,'' ,$new_ui );
        $new_ui = str_replace( '[notif_label]'                    ,'' ,$new_ui );
        $new_ui = str_replace( '[notif_process_name]'             ,'' ,$new_ui );
        return    str_replace( '[save_button]'                    ,'<button id="save-notif"> Save Notif</button>' ,$new_ui );

    }



}