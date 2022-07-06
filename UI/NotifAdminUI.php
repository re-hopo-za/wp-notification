<?php


namespace Notif\UI;


use Notif\includes\contents\NotifHtml;
use Notif\includes\NotifFunctions;

class NotifAdminUI
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
        add_action('admin_menu'  ,[ $this ,'notifSubmenu'] ,99 );
    }



    public function notifSubmenu()
    {
        add_menu_page(
            'همه نوتیف ها' ,
            'همه نوتیف ها' ,
            'manage_options',
            'notifList' ,
            [$this ,'notifList']  ,
            'dashicons-format-status',
            100
        );
        foreach ( self::submenuList() as $name => $slug ){
            add_submenu_page(
                'notifList' ,$name ,$name , 'manage_options' ,$slug ,[ $this ,$slug]
            );
        }
    }

    public static function notifNew()
    {
        ?>
        <div class="notif-root">
            <div class="nav-container notif-new">
                <?php self::header('dashicons-plus-alt' ,'افزودن نوتیف' ); ?>
                <?php self::menu('notifNew'); ?>
            </div>
            <div class="notif-new-body">
                <?php echo NotifHtml::notifNew(); ?>
            </div>
            <div class="closer-area"></div>
        </div>
        <?php
    }


    public static function notifList()
    {
        if ( isset( $_GET['id'] ) && is_numeric( $_GET['id'] ) ) {
            ?>
                <div class="notif-root">
                    <div class="nav-container">
                        <?php self::header('dashicons-megaphone' ,'اعلان' ); ?>
                        <?php self::menu('notifList'); ?>
                    </div>
                    <div class="notif-item-body notif-single">
                        <?php echo NotifHtml::notifSingle(); ?>
                    </div>
                    <div class="closer-area"></div>
                </div>
            <?php
        }else{
            ?>
                <div class="notif-root">
                    <div class="nav-container">
                        <?php self::header('dashicons-megaphone' ,'لیست اعلان ها' ); ?>
                        <?php self::menu('notifList'); ?>
                    </div>
                    <div class="notif-list-menu">
                        <a href="<?php echo admin_url('admin.php?page=notifList&status=1'); ?>" class="<?= NotifFunctions::getStatusParameterFromQuery( $_GET ,'1' ); ?>"> played </a>
                        <a href="<?php echo admin_url('admin.php?page=notifList&status=0'); ?>" class="<?= NotifFunctions::getStatusParameterFromQuery( $_GET ,'0' ); ?>"> paused </a>
                        <a href="<?php echo admin_url('admin.php?page=notifList&status=2'); ?>" class="<?= NotifFunctions::getStatusParameterFromQuery( $_GET ,'2' ); ?>"> disabled </a>
                        <a href="<?php echo admin_url('admin.php?page=notifList&status=3'); ?>" class="<?= NotifFunctions::getStatusParameterFromQuery( $_GET ,'3' ); ?>"> deleted </a>
                        <a href="<?php echo admin_url('admin.php?page=notifList&status=4'); ?>" class="<?= NotifFunctions::getStatusParameterFromQuery( $_GET ,'4'  ); ?>"> all </a>
                        <a href="<?php echo admin_url('admin.php?page=notifList&status=5'); ?>" class="<?= NotifFunctions::getStatusParameterFromQuery( $_GET ,'5'  ); ?>"> assignment </a>
                    </div>
                    <div class="notif-item-body notif-list">
                        <?php NotifHtml::notifItems(); ?>
                    </div>
                </div>
            <?php
        }
    }


    public static function notifStatus()
    {
        ?>
        <div class="notif-root">
            <div class="nav-container">
                <?php self::header('dashicons-plus-alt' ,'افزودن نوتیف' ); ?>
                <?php self::menu('notifStatus'); ?>
            </div>
            <div class="notif-item-body">
                <?php self::notifNewContent(); ?>
            </div>
            <div class="closer-area"></div>
        </div>
        <?php
    }


    public static function notifSettings()
    {
        ?>
        <div class="notif-root">
            <div class="nav-container">
                <?php self::header('dashicons-plus-alt' ,'تنظیمات' ); ?>
                <?php self::menu('notifSettings'); ?>
            </div>
            <div class="notif-item-body">
                <?php self::notifSettingsContent(); ?>
            </div>
        </div>
        <?php
    }


    public static function notifSettingsContent()
    {
        ?>
            <div class="setting-content">
                <div class="root">
                    <div class="setting-item-con">
                        <label>
                            <b>متن ارسال پیامک اطلاع رسانی </b>
                            <textarea class="setting-item" cols="30" rows="5" data-setting-name="message-alert-text" >متن پیام </textarea>
                        </label>
                    </div>
                </div>
                <div class="setting-action">
                    <button id="save-setting"> save </button>
                </div>
            </div>
        <?php
    }




    public static function notifLogs()
    {
        ?>
        <div class="notif-root">
            <div class="nav-container">
                <?php self::header('dashicons-plus-alt' ,'افزودن نوتیف' ); ?>
                <?php self::menu('notifLogs'); ?>
            </div>
            <div class="notif-item-body">
                <?php self::notifNewContent(); ?>
            </div>
            <div class="closer-area"></div>
        </div>
        <?php
    }


     public static function notifNewContent()
    {
        return
            '<div class="new-notif-con">
                <div class="message-section">
                    <div class="horizontal-line">
                        <h5>ساخت پیام</h5>
                    </div>
                    <div class="title">
                        <div class="description">
                            <p> عنوان پیام </p> 
                        </div>
                        <input type="text" id="message-form-title" placeholder="عنوان را وارد کنید" value="[message_title]" >
                    </div>
                    <div class="content-html">
                        <div class="description">
                            <p> محتوای HTML </p>
                            <code>[<span>user</span>] {<span>usermeta</span>} </code>
                            <div class="text-includes-list">
                                <span> email </span> 
                            </div>
                        </div>
                        <div id="notif-html-message-editor" style="display: block">
                            [message_content]
                        </div>
                    </div> 
                    <div class="content-sms">
                        <div class="description">
                            <p>  SMS </p>
                            <div class="text-includes-list">
                                <span> sms </span>
                            </div>
                        </div>
                        <div>
                            <textarea type="text" id="sms-text-variable" placeholder="#user_nicename||##first_name||باتشکر">[message_sms_text]</textarea>
                            <input type="text" id="sms-template-name" placeholder="Template Name" value="[message_sms_template]">
                            <input type="number" placeholder="الویت ارسال" id="sms-priority" value="[message_sms_priority]">
                        </div>
                    </div>
                    <div class="save-message">
                        <div class="uploads">
                            <div class="description">
                                <p> انتخاب تصویر </p>
                            </div>
                            <div>
                                <ul class="message-file-con">
                                    [message_cover]
                                </ul>
                                '. NotifIcons::addIcon('add-image-file') .'
                            </div>
                        </div>
                        <div class="creator-ticket">
                            <h5> سازنده تیکت </h5>
                            <div>
                                <input id="message-ticket-creator" value="[message_ticket_creator]" placeholder=" user ID ">
                            </div>
                        </div> 
                        <div class="course-ticket">
                            <h5> انتخاب دوره برای تیکت </h5>
                            <div>
                                <input id="message-ticket-course" value="[message_ticket_course]" placeholder=" ticket course "> 
                            </div>
                        </div>
                        <div class="save-action">
                            <button id="save-message"> Save Message </button>
                        </div>
                    </div>
                </div>
                <div class="campaign-section">
                    <div class="horizontal-line">
                        <h5>ساخت کمپین</h5>
                    </div>
                    <div class="filter-con">
                        <div class="filter-group-course">
                            <h5>جزو گروه های خاص</h5>
                            <div>
                                <label>
                                    خریداران وبمستران
                                    <input type="checkbox" id="include-group-course-webmasteran" autocomplete="off" [campaign_webmasteran]>
                                </label>
                                <label>
                                    خریداران اینستاگرام
                                    <input type="checkbox" id="include-group-course-instagram"  autocomplete="off" [campaign_instagram]>
                                </label>
                            </div>
                        </div> 
                        <div class="filter-item">
                            <h5>  خریدار این دوره ها</h5>
                            <div class="includes-courses">
                                <ul>
                                    [campaign_includes_courses]
                                </ul>
                                 '. NotifIcons::addIcon('includes-courses-search-btn') .'
                                <div class="includes-courses-search">
                                    <select id="includes-courses-select"> </select>
                                    <span class="close-includes-product-search">بستن</span>
                                </div>
                            </div>
                        </div>
                        <div class="filter-item">
                            <h5> بجز خریداران این دوره ها</h5>
                            <div class="excludes-courses">
                                <ul>
                                    [campaign_excludes_courses]
                                </ul>
                                '. NotifIcons::addIcon('excludes-courses-search-btn') .'
                                <div class="excludes-courses-search">
                                    <select id="excludes-courses-select"> </select>
                                    <span class="close-excludes-product-search">بستن</span>
                                </div>
                            </div>
                        </div>
                        <div class="filter-date">
                            <h5> فیلتر زمان ثبت نام </h5>
                            <div>
                                <label>
                                    کاربران ثبت نامی از تاریخ
                                    <input type="text" id="from-date-registered" placeholder="ثبت فیلتر تاریخ" autocomplete="off" data-jdate="[campaign_from_date_registered]" value="[campaign_from_date_registered]">
                                </label>
                                <label>
                                    کاربران ثبت نامی تا تاریخ
                                    <input type="text" id="to-date-registered" placeholder="ثبت فیلتر تاریخ" autocomplete="off" data-jdate="[campaign_to_date_registered]" value="[campaign_to_date_registered]">
                                </label>
                            </div>
                        </div>
                        <div class="additional-users">
                            <h5>افزودن کاربران به صورت تکی</h5>
                            <div>
                                <ul>
                                    [campaign_additional_users]
                                </ul>
                                 '. NotifIcons::addIcon('include-users-search-btn') .'
                                <div class="include-users-search">
                                    <select id="include-users-select"> </select>
                                    <span class="close-include-users-search">بستن</span>
                                </div>
                            </div>
                        </div>
                        <div class="additional-users-plain-text">
                            <h5>افزودن کاربران به صورت متنی</h5>
                            <textarea id="additional-users-plain-text" placeholder="برای جدا کردن از کاما(,) استفاده شود" autocomplete="off" >[campaign_additional_users_text]</textarea>
                        </div>
                    </div>
                    <div class="save-users">
                        <div class="processing-con">
                            <p>  تعداد : <b>[campaign_users_count]</b>  </p>
                            <div class="has-process-task" style="display: none">
                                <label for="has-process-task">
                                    <span>این نوتیف داراری پروسه میباشد</span>
                                    <input type="checkbox" name="has-process-task" id="has-process-task" [campaign_has_process]>
                                </label>
                            </div>
                        </div>
                        <div class="save-action">
                            <button id="save-campaign"> Save Users</button>
                        </div>
                    </div>
                </div>
                <div class="cron-section">
                    <div class="horizontal-line">
                        <h5>ساخت کران</h5>
                    </div>
                    <div class="inputs">
                        <div class="left">
                            <div class="deliver-system">
                                [cron_deliver_system] 
                            </div>
                        </div>
                        <div class="right">
                           <div class="root-cron">
                               <div class="cron-days-inputs">
                                   <label>
                                       شروع (روز)
                                       <input type="text" value="[cron_start_day]" id="start-day">
                                   </label>
                                   <label>
                                       هر چند دقیقه
                                       <select id="per-time">
                                           [cron_per_time]  
                                       </select>
                                   </label>
                                   <label>
                                       اتمام (روز)
                                       <input type="text" id="end-day" value="[cron_end_day]">
                                   </label>
                               </div>
                               <div class="cron-time-inputs">
                                   <label>
                                       شروع (ساعت)
                                       <input id="start-time" type="text" value="[cron_start_time]">
                                   </label>
                                   <label>
                                       هر چند تا
                                       <input type="text" id="per-count" value="[cron_per_count]" >
                                   </label>
                                   <label>
                                       اتمام (ساعت)
                                       <input id="end-time" type="text" value="[cron_end_time]">
                                   </label>
                               </div>
                           </div> 
                            <div class="save-action">
                                <button id="save-cron"> Save Cron </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="main-save-section">
                    <div class="horizontal-line">
                        <h5>ذخیره کلی </h5>
                    </div>
                    <div class="inputs">
                    <div class="title-notif">
                        <div class="label-notif">
                            <label>
                                 برچسب
                                <input id="cron-label" type="text" value="[notif_label]" placeholder="[notif_label]">
                            </label>
                        </div>
                        <div class="process-con" style="display: none">
                            <select id="processName" >
                                [notif_process_name]
                                <option value="" selected >بدون پروسه</option>
                                <option value="remainder">یادآوری تمرینات</option>
                            </select>
                        </div> 
                    </div>
                    <div class="status">
                        <label for="select-status">وضعیت</label>
                        <div>
                            <span>غیر فعال</span>
                            <input type="radio" name="select-status" id="select-status" value="0" [status_disable] >
                            <span>فعال</span>
                            <input type="radio" name="select-status" id="select-status" value="1" [status_enable] >
                        </div>
                    </div> 
                    <div class="save-action">
                        [save_button]
                    </div>
                    </div>
                </div>
            </div>
        ';
    }


    public static function menu( $active )
    {
        ?> 
        <div class="notif-menu">
            <div class="icon-list menu-items">
                <a href="<?php echo admin_url('admin.php?page=notifNew') ?>" class="<?php echo $active == 'notifNew' ? 'active' : ''; ?>" >
                    <span title="افزودن نوتیف" >
                        <i class="dashicons dashicons-plus-alt" ></i>
                    </span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=notifList') ?>" class="<?php echo $active == 'notifList' ? 'active' : ''; ?>" >
                    <span title="همه نوتیف ها" >
                        <i class="dashicons dashicons-megaphone" ></i>
                    </span>
                </a>
<!--                <a href="--><?php //echo admin_url('admin.php?page=notifStatus') ?><!--" class="--><?php //echo $active == 'notifStatus' ? 'active' : ''; ?><!--" >-->
<!--                    <span title="وضعیت ارسالی ها" >-->
<!--                        <i class="dashicons dashicons-list-view" ></i>-->
<!--                    </span>-->
<!--                </a>-->
                <a href="<?php echo admin_url('admin.php?page=notifSettings') ?>" class="<?php echo $active == 'notifSettings' ? 'active' : ''; ?>" >
                    <span title="تنظیمات" >
                        <i class="dashicons dashicons-admin-generic" ></i>
                    </span>
                </a>
<!--                <a href="--><?php //echo admin_url('admin.php?page=notifLogs') ?><!--" class="--><?php //echo $active == 'notifLogs' ? 'active' : ''; ?><!--" >-->
<!--                    <span title="گزارش سیستمی" >-->
<!--                        <i class="dashicons dashicons-bell" ></i>-->
<!--                    </span>-->
<!--                </a>-->
            </div>
        </div>
        <?php
    }


    public static function emptyList()
    {
        ?>
            <div class="empty-list">
                <p> لیستی یافت نشد </p>
            </div>
        <?php
    }


    public static function filterItemInCampaign()
    {
        return
            '<li class="product-filters-items" data-item-id="[id]">
                '.NotifIcons::close().'
                <p> [text] </p> 
            </li>';
    }


    public static function loaderElement( $main = true )
    {
        if ( $main ){
            return
                '<div class="loader-container"> 
                     '. NotifIcons::loader().'
                </div>';
        }else{
            return NotifIcons::loader();
        }
    }



    public static function deliverSystemHandler( $list = [] )
    {
        $items = NotifFunctions::cronDeliverSystemDefault();
        if ( !empty( $list ) ){
            $items = $list ;
        }
        $output  = '<div class="deliver-system-root">  <div id="system-add-list">';
        if ( is_array( $items ) && !empty( $items ) ){
            foreach ( $items as $id => $data ){
                $output .=
                    '<div id="'.$data->id.'">
                        <i class="dashicons dashicons-move"></i>
                        <p>'.NotifFunctions::getSystemTranslate( $data->id ) .'</p>
                        <div class="additional-delivery">
                            <label for=""> </label>
                            <input type="checkbox" value="ok" '.NotifFunctions::getSystemStatusChecked( $data->status ).'>
                        </div>
                    </div>
                ';
            }
        }
        $output .=
            '</div>
                <div id="system-add-more-than-one">
                    <label>
                        <span> ارسال با بیش از یک سیستم</span>
                        <input type="checkbox">
                    </label>
                </div>
            </div>
        ';
        return $output;
    }


    public static function campaignCourseList()
    {
        return
            '<li class="product-filters-items" data-item-id="[course_id]">
                <svg height="20" viewBox="0 0 512 512" width="20">
                    <path d="m437.019531 74.980469c-48.351562-48.351563-112.640625-74.980469-181.019531-74.980469s-132.667969
                         26.628906-181.019531 74.980469c-48.351563 48.351562-74.980469 112.640625-74.980469 181.019531 0
                        68.382812 26.628906 132.667969 74.980469 181.019531 48.351562 48.351563 112.640625 74.980469 181.019531
                         74.980469s132.667969-26.628906 181.019531-74.980469c48.351563-48.351562 74.980469-112.636719 74.980469-181.019531
                        0-68.378906-26.628906-132.667969-74.980469-181.019531zm-70.292969 256.386719c9.761719 9.765624 9.761719 25.59375 0 35.355468-4.882812
                        4.882813-11.28125 7.324219-17.679687 7.324219s-12.796875-2.441406-17.679687-7.324219l-75.367188-75.367187-75.367188 75.371093c-4.882812
                         4.878907-11.28125 7.320313-17.679687 7.320313s-12.796875-2.441406-17.679687-7.320313c-9.761719-9.765624-9.761719-25.59375
                        0-35.355468l75.371093-75.371094-75.371093-75.367188c-9.761719-9.765624-9.761719-25.59375 0-35.355468 9.765624-9.765625 25.59375-9.765625
                        35.355468 0l75.371094 75.367187 75.367188-75.367187c9.765624-9.761719 25.59375-9.765625 35.355468 0 9.765625 9.761718 9.765625 25.589844 0
                        35.355468l-75.367187 75.367188zm0 0"> 
                    </path>
                </svg> 
                <p> [course_name] </p>
            </li>
        ';
    }



    public static function header( $icon ,$title )
    {
        ?>
            <div class="header">
                <i class="dashicons <?php echo $icon; ?>"></i>
                <h3><?php echo $title; ?></h3>
            </div>
        <?php
    }

    public static function submenuList()
    {
        return [
            'افزودن نوتیف' => 'notifNew' ,
            'تنظیمات'      => 'notifSettings',
        ];
        /*
        'وضعیت ارسالی ها'  => 'notifStatus',
        'تنظیمات'          => 'notifSettings',
        'گزارش سیستمی'     => 'notifLogs',
        */
    }


    public static function returnPerTimeView( $perTime = 5 )
    {
        $output  = '';
        $options = [
            1   => '<option value="1" [selected]>1 دقیقه</option>' ,
            5   => '<option value="5" [selected]>5 دقیقه</option>' ,
            30  => '<option value="30" [selected]>30 دقیقه</option>',
            60  => '<option value="60" [selected]>1 ساعت</option>',
            300 => '<option value="300" [selected]>5 ساعت</option>'
        ];

        foreach ( $options as $time => $view   ){
            if ( $time == $perTime ){
                $output .= str_replace( '[selected]' ,'selected' ,$view );
            }else{
                $output .= str_replace( '[selected]' ,'' ,$view );
            }
        }
        return $output;
    }



}
