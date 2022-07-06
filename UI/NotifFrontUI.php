<?php


namespace Notif\UI;


use Notif\includes\NotifCron;
use Notif\includes\NotifDB;
use Notif\includes\NotifFunctions;

class NotifFrontUI
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
        add_shortcode( 'hwp_notifications_list'      ,[$this ,'notificationsList'] );
        add_action('wp_ajax_notif_single_loader'          ,[$this ,'notifSingle'] );
        add_action('wp_ajax_notif_all_loader'             ,[$this ,'notifAll'] );
        add_action('wp_ajax_hwp_notif_update_user_action' ,[$this , 'updateUserAction'] );
    }



    public function updateUserAction()
    {
        $notif_id = NotifFunctions::indexChecker( $_POST ,'notif_id' );
        $user_id  = get_current_user_id();
        if ( is_numeric( $notif_id ) ){
            $status   = NotifDB::get_instance()::updateUserAction( $notif_id ,$user_id );
            if ( $status ){
                NotifFunctions::sendJsonResult( 200 ,['result' => 'updated' ] );
            }
            NotifFunctions::sendJsonResult( 403 ,['result' => 'error on updating' ] );
        }
    }



    public static function notificationsList()
    {
        return
            '<div class="notif-list-con" id="notif-container">
                '.self::inboxList().'  
            </div> 
        ';
    }


    public static function inboxList()
    {
        $user_id = get_current_user_id();
        $data    = NotifFunctions::getUserNotifOnFront( $user_id );
        $final_output   = '';
        $output_read    = '';
        if ( !$data['empty'] ){
            foreach ( $data as $status => $items ){
                if ( !empty( $items ) ){
                    if ( $status == 'success_users' ){
                        foreach ( $items as  $notif_id => $details ){
                            $date   = self::dateConvert( $details['date'] );
                            $output = str_replace( '[notif-id]'     ,$notif_id ,self::notifItem() );
                            $output = str_replace( '[cover]'        ,self::getNotifCover( $details ) ,$output );
                            $output = str_replace( '[date]'         ,$date ,$output );
                            $output = str_replace( '[title]'        ,$details['title'] ,$output );
                            $output = str_replace( '[unread-notif]' ,NotifFunctions::notifReadStatus( $details['success'] ,$user_id ) ,$output );
                            $output = str_replace( '[url]'          ,home_url('my/pm/'.$notif_id ) ,$output );
                            $final_output .= $output;
                        }
                    }
                }
            }
        }else{
            $final_output .= self::notifNoItem();
        }
        return str_replace( '[items]' ,$final_output ,self::notifRootUi() );
    }



    public static function notifSingle()
    {
        if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'] ,'notif_public_nonce')  ){
            $user_id  = get_current_user_id();
            $notif_id = NotifFunctions::indexChecker( $_POST ,'notif_id');
            if ( is_numeric( $notif_id ) ){
                $single = NotifDB::get_instance()::getSingle( $notif_id );
                if ( !empty( $single )  ){
                    if( !empty( NotifDB::get_instance()::getSpecificUserItemsByNotifID( $user_id ,$notif_id ) ) ){
                        $message = json_decode( $single->message );
                        $details = json_decode( $single->details );
                        $user    = get_user_by('id' , $user_id );
                        $text    = NotifCron::createFinalMessage( $user ,$single->content );
                        $output  = str_replace( '[notif-id]'   ,$single->id ,self::notifSingleUi() );
                        $output  = str_replace( '[url-return]' ,home_url('my/notifications/') ,$output );
                        $output  = str_replace( '[url-ticket]' ,home_url('ticket/new/?title='.$message->message_title ) ,$output );
                        $output  = str_replace( '[title]'      ,$message->message_title ,$output );
                        $output  = str_replace( '[date]'       ,self::dateConvert( $details->created_date ) ,$output );
                        $output  = str_replace( '[content]'    ,$text ,$output );
                        NotifFunctions::updateUserSeenHandler( $user_id ,[$notif_id] );
                        wp_send_json( ['result' => $output ] ,200 );
                    }
                }
            }
        }
        wp_send_json( [ 'result' => 'error' ] ,403 );
    }


    public static function notifAll()
    {
        if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'] ,'notif_public_nonce')  ){
            $user_id = get_current_user_id();
            $list    = NotifFunctions::getUserNotifOnFront( $user_id );
            $final_output   = '';
            if ( !$list['empty'] ){
                foreach ( $list as $status => $items ){
                    if ( $status == 'success_users' ){
                        if ( !empty( $items ) ){
                            foreach ( $items as $notif_id => $details ){
                                $date   = self::dateConvert( $details['date'] );
                                $output = str_replace( '[notif-id]'     ,$notif_id ,self::notifItem() );
                                $output = str_replace( '[cover]'        ,self::getNotifCover( $details ) ,$output );
                                $output = str_replace( '[date]'         ,$date ,$output );
                                $output = str_replace( '[title]'        ,$details['title'] ,$output );
                                $output = str_replace( '[unread-notif]' ,NotifFunctions::notifReadStatus( $details['success'] ,$user_id ) ,$output );
                                $output = str_replace( '[url]'          ,home_url('my/pm/'.$notif_id ) ,$output );
                                $list_to_update[$notif_id] = $notif_id;
                                $final_output .= $output;
                            }
                        }
                    }
                }
            }else{
                return self::notifNoItem();
            }
            wp_send_json( ['result' => str_replace( '[items]' ,$final_output ,self::notifRootUi() ) ] ,200 );
        }
        wp_send_json( ['result' => '' ] ,403 );
    }


    public static function dateConvert( $date )
    {
        if ( !empty( $date ) ){
            $tehran_date = date_i18n( 'Y/m/d' ,strtotime( $date ) ,'Asia/Tehran' );
            $tehran_time =  date_i18n( 'H:i:s ' ,strtotime( $date ) ,'Asia/Tehran' );
            return  $tehran_date .'<span class="spacer"></span>'.$tehran_time;
        }
        return '-';
    }


    public static function getNotifCover( $item )
    {
        if ( !empty( $item ) && !empty( $item['cover'] ) ){
            return '<img src="'.$item['cover'].'" alt="notif-cover">';
        }
        return NotifIcons::itemCoverPlaceholder();
    }


    public static function widget()
    {
        ?>
            <div class="notif-widget-con">
                <div class="icon">
                    <svg id="open-bell-list" height="60" viewBox="-11 0 512 512" width="60" xmlns="http://www.w3.org/2000/svg">
                        <path d="m298.667969 426.667969c0 47.128906-38.207031 85.332031-85.335938 85.332031-47.128906 0-85.332031-38.203125-85.332031-85.332031 0-47.128907 38.203125-85.335938 85.332031-85.335938 47.128907 0 85.335938 38.207031 85.335938 85.335938zm0 0"
                              fill="#ffa000"/>
                        <path d="m362.835938 254.316406c-72.320313-10.328125-128.167969-72.515625-128.167969-147.648437 0-21.335938 4.5625-41.578125 12.648437-59.949219-10.921875-2.558594-22.269531-4.050781-33.984375-4.050781-82.34375 0-149.332031 66.984375-149.332031 149.332031v59.476562c0 42.21875-18.496094 82.070313-50.945312 109.503907-8.296876 7.082031-13.054688 17.429687-13.054688 28.351562 0 20.589844 16.746094 37.335938 37.332031 37.335938h352c20.589844 0 37.335938-16.746094 37.335938-37.335938 0-10.921875-4.757813-21.269531-13.269531-28.542969-31.488282-26.644531-49.75-65.324218-50.5625-106.472656zm0 0"
                              fill="#ffc107"/>
                        <path d="m490.667969 106.667969c0 58.910156-47.757813 106.664062-106.667969 106.664062s-106.667969-47.753906-106.667969-106.664062c0-58.910157 47.757813-106.667969 106.667969-106.667969s106.667969 47.757812 106.667969 106.667969zm0 0"
                              fill="#f44336"/>
                    </svg>
                </div>
            </div>
        <?php
    }


    public static function notifRootUi()
    {
        return
            '<div class="top">
                    <h3> لیست پیام ها</h3> 
                </div>
                <ul>
                    [items]
                </ul>  
            </div> 
        ';
    }


    public static function notifItem()
    {
        return
            '<li class="notif-item" data-id="[notif-id]">
                <div class="root">
                    <div class="cover [unread-notif]">
                       [cover] 
                    </div>
                    <div class="middle">
                        <div class="top">
                            <p class="title">[title]</p>
                            <span class="created-at">[date]</span>
                        </div> 
                        <div class="bottom">
                            <span style="display: none" class="created-at">[date]</span>
                            <a target="_blank" onclick="return false;" href="[url]" class="notif-single-button" data-id="[notif-id]"> نمایش </a> 
                        </div>
                    </div>  
                </div> 
            </li> 
        ';
    }


    public static function notifSingleUi()
    {
        return
            '<div class="notif-single" data-notif-id="[notif-id]">
                <div class="header"> 
                    <a target="_self" onclick="return false;" href="[url-return]" id="get-all-notif-items" > بازگشت</a>
                </div>
                <div class="content">
                    <div class="top">
                        <p class="title">[title]</p>
                        <span class="created-at">[date]</span>
                    </div>
                    <div class="bottom">
                        <p>[content]</p>
                    </div>
                </div>
                <div class="action">
                     <a target="_blank"  href="[url-ticket]" class="reply"> پاسخ </a>
                </div> 
            </div>'
        ;
    }


    public static function notifNoItem()
    {
        return
            '<li class="notif-item empty-inbox">
                <h4 class="title">
                    موردی یافت نشد
                </h4>
                '.NotifIcons::notItems().'
            </li>
        ';
    }


}