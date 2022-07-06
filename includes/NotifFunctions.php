<?php


namespace Notif\includes;


use Hashids\Hashids;
use Notif\includes\modules\services\NotifSMS;
use stdClass;

class NotifFunctions
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


    public static function ajaxRequestValidator($inputs, $creatorChecker = false)
    {
        $result = NotifFunctions::parametersChecker($inputs);
        if ($result->error) {
            self::sendJsonResult(428 ,$result->message );
        }
        $result = NotifPermission::nonceChecker();
        if ($result->error) {
            self::sendJsonResult(401 ,$result->message );
        }
        $result = NotifPermission::AccessChecker($creatorChecker);
        if ($result->error) {
            self::sendJsonResult(403 ,$result->message );
        }
        return true;
    }


    public static function getCurrentNotifID()
    {
        if (!empty($_POST) && isset($_POST['notif_id'])) {
            return $_POST['notif_id'];
        }
        return false;
    }


    public static function getNewNotifDetails()
    {
        return json_encode(['user_id' => get_current_user_id(), 'created_date' => date('Y-m-d H:i:s')]);
    }


    public static function setNotifDetails($oldData, $newData)
    {
        $old_data = (array)json_decode($oldData, true);
        if (!isset($old_data['change_status'])) {
            $old_data['change_status'] = [];
        }
        $old_data['change_status'][time()] = [get_current_user_id() => $newData];
        return json_encode($old_data);
    }


    public static function parametersChecker($inputs)
    {
        foreach ( $inputs as $key => $val ) {
            if ( $val ) {
                if (!isset( $_POST[ $key ] ) ) {
                    return (object)['error' => true, 'message' => 'Missing parameter : ' . $key];
                }
            }
            if ( isset( $_POST[ $key ] ) && !empty( $_POST[ $key ] ) && !is_array( $_POST[ $key ] ) && $key != 'message_content' ){
                $_POST[$key] = sanitize_textarea_field($_POST[$key]);
            }
        }
        return (object)['error' => false];
    }

    public static function sendJsonResult($statusCode, $data = null)
    {
        if (empty($data)) {
            $data = self::statusCode($statusCode);
        }
        wp_send_json(['result' => $data], $statusCode);
        die();
    }

    public static function getStatusParameterFromQuery( $params ,$specific )
    {
        if (!empty($params) && isset($params['status']) && is_numeric($params['status']) && $params['status'] == $specific) {
            return 'active';
        } else if ((!isset($params['status']) || !is_numeric($params['status'])) && $specific == '1') {
            return 'active';
        }
        return '';
    }

    public static function statusCode($status)
    {
        switch ($status) {
            case 200 :
                return ' فرایند با موفقعیت انجام شد.  ';
            case 201 :
                return ' دیتا مورد نظر ذخیره شد.  ';
            case 204 :
                return ' فرایند با موفقعیت انجام شد ولی داده ای یافت نشد.  ';
            case 401 :
                return 'برای دریافت اطلاعات بایستی وارد شوید.  ';
            case 403 :
                return 'شما به این قسمت دسترسی نداری.  ';
            case 404 :
                return ' دیتای مورد نظر یافت نشد.  ';
            case 428 :
                return ' خطای دیتای اجباری.  ';
            case 500 :
                return 'خطای داخلی رخ داده است.  ';
        }
        return 'خطای نامشخص رخ داده است.  ';
    }


    public static function indexChecker( $data, $index, $default = '')
    {
        if ( is_object( $data ) && isset( $data->$index ) && !empty( $data->$index ) ) {
            return $data->$index;
        }
        if ( is_array( $data ) && isset( $data[ $index ] ) && !empty( $data[ $index ] ) ) {
            return $data[$index];
        }
        return $default;
    }


    public static function prepareUsersItemsToRemove( $usersItems )
    {
        $items = '';
        if ( !empty( $usersItems  ) && ( is_array( $usersItems ) || is_object( $usersItems ) ) ) {
            foreach ( $usersItems as $user_id => $system ){
                $items .= ',' . '\'$.' . $user_id . '\' ';
            }
        }
        return $items;
    }


    public static function prepareUsersItemsToInsert( $usersItems ,$success = 1 )
    {
        $items = '';
        if (!empty( $usersItems ) && ( is_array( $usersItems ) || is_object( $usersItems ) ) ) {
            $success_users  = '';
            $failed_users   = '';
            foreach ( $usersItems as $user_id => $system ){
                $failed_list    = '';
                $success_status = false;
                foreach ( $system as $sys => $status ) {
                    if ( $success == 1 && $status == 1 ) {
                        $success_status = true;
                    }elseif (  $success == 2 &&  $status == 2   ){
                        $failed_list .= '\''.$sys.'\',';
                    }
                }
                if ( $success_status ) {
                    $success_users .= ' ,' . '\'$.' . $user_id . '\' ,JSON_ARRAY( false ,false )';
                }else if ( !empty( $failed_list ) ){
                    $failed_list = rtrim( $failed_list ,',' );
                    $failed_users  .= ' ,' . '\'$.' . $user_id . '\' ,JSON_ARRAY( '. $failed_list .' )';
                }
            }
            if ( $success == 1 && !empty( $success_users ) ){
                return " , success_users = JSON_INSERT( success_users {$success_users} ) ";
            }elseif ( $success == 2 && !empty( $failed_users )   ){
                return " , failed_users = JSON_INSERT( failed_users {$failed_users} ) ";
            }
        }
        return $items;
    }


    public static function searchProducts()
    {
        global $wpdb;
        $keyword = NotifFunctions::indexChecker($_POST, 'keyword');
        $products = $wpdb->get_results(
            "SELECT post_title , ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status <> 'trash' AND (post_title LIKE '%$keyword%' or ID='{$keyword}') ",
            ARRAY_A
        );
        $result = [];
        if (!empty($products)) {
            foreach ((array)$products as $product) {
                $result  [] = [
                    'id' => $product['ID'],
                    'title' => $product['post_title']
                ];
            }
        }
        if (!empty($result)) {
            self::sendJsonResult(true, $result, $result);
        }
        self::sendJsonResult(true, 204);
    }


    public static function searchUser()
    {
        global $wpdb;
        $exclude_users = NotifFunctions::indexChecker($_POST, 'exc');
        $who = NotifFunctions::indexChecker($_POST, 'keyword');
        $extra_where = $extra_meta_query = '';
        if (!empty($exclude_users) && is_array($exclude_users)) {
            $exclude_users = implode("','", $exclude_users);
            $extra_where = "AND id NOT IN ('$exclude_users')";
            $extra_meta_query = "AND user_id NOT IN ('$exclude_users')";
        }
        $mobile_key = \hf_user_mobile_meta_key();
        $usersID = $wpdb->get_results(
            $wpdb->prepare("SELECT ID FROM $wpdb->users WHERE ID = %s OR user_login LIKE %s 
                        OR user_nicename LIKE %s OR user_email LIKE %s OR display_name LIKE %s " . $extra_where . " limit 100  ;"
                , $who, '%' . $who . '%', '%' . $who . '%', '%' . $who . '%', '%' . $who . '%')
        );
        if (empty($usersID)) {
            $usersID = $wpdb->get_results(
                $wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE ( meta_key ='first_name' AND meta_value LIKE %s ) 
                     OR (meta_key ='last_name' AND meta_value LIKE %s ) OR (meta_key = '{$mobile_key}'
                     AND meta_value LIKE %s )" . $extra_meta_query . " limit 100  ;"
                    , '%' . $who . '%', '%' . $who . '%', '%' . $who . '%'));
            $usersID = array_column($usersID, 'user_id');
        } else {
            $usersID = array_column($usersID, 'ID');
        }
        $usersID = array_unique($usersID);

        if (!empty($usersID)) {
            $result = [];
            foreach ($usersID as $u) {
                $user = get_user_by('id', (int)$u);
                $result  [] = [
                    'id' => $user->ID,
                    'title' => $user->first_name . ' ' . $user->last_name,
                    'slug' => $user->user_nicename
                ];
            }
            self::sendJsonResult(true, $result );
        }
        self::sendJsonResult(true, []);
    }


    public static function usersCalculator( $filter )
    {
        if ( $filter['has_process'] == 'true' ) {
            return [
                'users' => [] ,
                'count' => 0
            ];
        }
        $users_list = NotifDB::calculateUsers( $filter );
        $users_list = json_decode("{" . $users_list->ids . "}");
        if ( !empty( $users_list ) && is_object( $users_list ) ) {
            return [
                'users' => $users_list ,
                'count' => count( (array) $users_list )
            ];
        }
        self::sendJsonResult(500, 'خطا هنگام ساخت لیست کاربران');
    }


    public static function jsonDecodeDbColumn($data, $column)
    {
        if (!empty($data) && isset($data->$column) && !empty($data->$column)) {
            return json_decode($data->$column);
        }
        return [];
    }


    public static function get( $data, $column )
    {
        if (!empty($data) && isset($data->$column) && !empty($data->$column)) {
            return json_decode($data->$column);
        }
        return new stdClass();
    }


    public static function getCourseNameByID($courseID)
    {
        if (is_numeric($courseID)) {
            $name = get_post($courseID);
            if (!empty($name) && isset($name->post_title)) {
                return $name->post_title;
            }
        }
        return 'بدون نام';
    }


    public static function getNotifCover($url)
    {
        if (!empty($url)) {
            return
                '<li>       
                    <span data-message-remover=""> حذف </span>   
                    <img src="' . $url . '" alt="cover" >   
                </li>
            ';
        }
        return '';
    }


    public static function getUserNameByID($userID)
    {
        if (is_numeric($userID)) {
            $name = get_user_by('id', $userID);
            if (!empty($name) && isset($name->display_name)) {
                return $name->display_name;
            }
        }
        return 'بدون نام';
    }


    public static function encryptID($id)
    {
        $endOfDay = strtotime("tomorrow", strtotime("today", time()));
        $key = get_option('hamfy_token_options', true);
        $hashID = new Hashids($endOfDay + (int)$key);
        return $hashID->encode($id . $key);
    }


    public static function decryptID($hashedID)
    {
        $endOfDay = strtotime("tomorrow", strtotime("today", time()));
        $key = get_option('notif_token_key', true);
        $hashID = new Hashids($endOfDay + (int)$key);
        $user_hashed_id = $hashID->decode($hashedID)[0];
        $outputUser = (int)str_replace($key, '', $user_hashed_id);
        if (is_numeric($outputUser) and $outputUser > 0) {
            return $outputUser;
        } else {
            return false;
        }
    }

    public static function sanitizer($value, $functions)
    {
        $functions = explode(',', $functions);
        foreach ($functions as $function) {
            if (function_exists($function)) {
                $value = $function($value);
            }
        }
        return $value;
    }

    public static function getListOfUserSeen($userID)
    {
        $meta = get_user_meta($userID, 'notif_user_bell_list', true);
        $meta = maybe_unserialize($meta);
        if (is_array($meta) && !empty($meta)) {
            return $meta;
        }
        add_user_meta($userID, 'notif_user_bell_list', []);
        return [];
    }

    public static function updateListOfUserSeen($parameters)
    {
        $userID = get_current_user_id();
        $bellID = (int)$parameters['notif_id'];

        $meta = self::getListOfUserSeen($userID);
        if (is_array($meta)) {
            if (!isset($meta[$bellID])) {
                $meta[] = $bellID;
                update_user_meta( $userID, 'notif_user_bell_list' ,$meta );
                return true;
            }
        }
        return false;
    }


    public static function sendTestMessage($parameters)
    {
        NotifSMS::get_instance()::testSMS(
            NotifDB::get_instance()::getCronTestItem(
                NotifFunctions::indexChecker($parameters, 'message_id')
            )
        );
    }


    public static function returnEndDay($selectedDay)
    {
        if (!empty($selectedDay)) {
            return date('Y-m-d', $selectedDay);
        }
        return '';
    }


    public static function getUserNotifOnFront( $userID )
    {
        $notif = NotifDB::get_instance()::getSpecificUserItems( $userID );
        $items = ['users' => [], 'success_users' => [], 'failed_users' => [], 'empty' => true];
        if ( !empty( $notif )  ) {
            foreach ( $notif as $item ) {
                $raw_users     = json_decode( $item->users );
                $success_users = json_decode( $item->success_users );
                $failed_users  = json_decode( $item->failed_users );
                $message       = json_decode( $item->message );
                $details       = json_decode( $item->details );
                $cron          = json_decode( $item->cron );
                if ( self::checkNotifShowStatusInPanel( $cron ) ){
                    if (!empty( $raw_users ) && isset( $raw_users->$userID ) ) {
                        $items['users'][$item->id] = [
                            'date'      => NotifFunctions::indexChecker($details, 'created_date'),
                            'title'     => NotifFunctions::indexChecker($message, 'message_title'),
                            'cover'     => NotifFunctions::indexChecker($message, 'message_cover') ,
                            'delivers'  => NotifFunctions::indexChecker($cron   , 'cron_deliver_system') ,
                            'content'   => NotifFunctions::indexChecker($item   , 'message_content'),
                            'success'   => $success_users,
                        ];
                        $items['empty'] = false;
                    }
                    if ( !empty( $success_users ) && isset( $success_users->$userID ) ) {
                        $items['success_users'][$item->id] = [
                            'date'     => NotifFunctions::indexChecker($details, 'created_date'),
                            'title'    => NotifFunctions::indexChecker($message, 'message_title'),
                            'cover'    => NotifFunctions::indexChecker($message, 'message_cover'),
                            'delivers' => NotifFunctions::indexChecker($cron   , 'cron_deliver_system'),
                            'content'  => NotifFunctions::indexChecker($item   , 'message_content'),
                            'success'  => $success_users,
                        ];
                        $items['empty'] = false;
                    }
                    if ( !empty( $failed_users ) && isset( $failed_users->$userID ) ) {
                        $items['failed_users'][$item->id] = [
                            'date'     => NotifFunctions::indexChecker($details, 'created_date'),
                            'title'    => NotifFunctions::indexChecker($message, 'message_title'),
                            'cover'    => NotifFunctions::indexChecker($message, 'message_cover'),
                            'delivers' => NotifFunctions::indexChecker($cron   , 'cron_deliver_system'),
                            'content'  => NotifFunctions::indexChecker($item   , 'message_content'),
                            'success'  => $success_users,
                        ];
                        $items['empty'] = false;
                    }
                }
            }
        }
        return $items;
    }


    public static function checkNotifShowStatusInPanel( $cron )
    {
        if ( !empty( $cron ) && isset( $cron->cron_deliver_system ) ){
            foreach ( $cron->cron_deliver_system  as $index => $system ){
                if ( isset( $system->id ) && $system->id == 'alert' && $system->status == 'true' ){
                    return true;
                }
            }
        }
        return false;
    }


    public static function createNotifDirectly( $title, $content, $userID )
    {
        return NotifDB::get_instance()::createNotifDirectly($title, $content, $userID);
    }


    public static function updateUserSeenHandler( $userID, $notifIDs )
    {
        if ( !empty( $notifIDs ) && is_array( $notifIDs ) ) {
            foreach ( $notifIDs as $id ) {
                self::updateUserSeen( $userID ,$id );
            }
        }
    }


    public static function updateUserSeen( $userID ,$notifID )
    {
        $single = NotifDB::get_instance()::getSingle( $notifID );
        if ( !empty( $single ) ){
            if( $single->process_name === 'assignment' ){
                NotifDB::updateUserSeenOnAssignment(  $userID ,$notifID );
            }
            elseif ( empty( $single->process_name ) ){
                NotifDB::updateUserSeenOnEmptyProcess(  $userID ,$notifID );
            }
        }

    }


    public static function cronDeliverSystemDefault()
    {
        return [
            0 => (object) ['id' => 'alert'   , 'status' =>  'true'  ] ,
            1 => (object) ['id' => 'sms_1'   , 'status' =>  'false' ] ,
            2 => (object) ['id' => 'email_1' , 'status' =>  'false' ] ,
            3 => (object) ['id' => 'ticket'  , 'status' =>  'false' ] ,
            4 => (object) ['id' => 'sms_2'   , 'status' =>  'false' ] ,
        ];
    }

    public static function getSystemTranslate( $id )
    {
        switch ( $id ){
            case 'alert':
                return 'اعلان';
            case 'sms_1':
                return 'پیامک (کاوه نگار)';
            case 'email_1':
                return 'ایمیل (اصلی)';
            case 'ticket':
                return 'تیکت';
            case 'sms_2':
                return 'پیامک اطلاع رسانی';
            case 'email_2':
                return 'ایمیل (دوم)';
            default:
                return 'بدون نام';
        }
    }


    public static function getSystemStatusChecked( $status )
    {
        if ( $status == 'true' ){
            return 'checked="checked"';
        }
        return '';
    }


    public static function checkStatusInput( $status ,$inputActive )
    {
        if( $status == 1 && $inputActive  ){
            return 'checked';
        }
        elseif ( !$inputActive && $status == 0 ){
            return 'checked';
        }
        return '';
    }


    public static function getUsersIDFromPlainText( $usersID )
    {
        $final_users = [];
        if ( !empty( $usersID ) ){
            $users = explode( ',' , $usersID );
            if ( !empty( $users ) && is_array( $users ) ){
                $separator = '';
                foreach ( $users as $user ){
                    if ( !empty( $user ) && is_numeric( $user ) ){
                        $final_users[$user] = $user;
                    }
                }
            }
        }
        return $final_users;
    }


    public static function getUrlStatusWhere()
    {
        if ( isset( $_GET['status'] ) && is_numeric( $_GET['status'] ) && $_GET['status'] == 4 ){
            return " ( process_name <> 'assignment' OR process_name IS NULL ) AND status >= 0";
        }
        elseif ( isset( $_GET['status'] ) && is_numeric( $_GET['status'] ) && $_GET['status'] < 4 ){
            return "( process_name <> 'assignment' OR process_name IS NULL ) AND status = ".$_GET['status'];
        }
        elseif ( isset( $_GET['status'] ) && is_numeric( $_GET['status'] ) && $_GET['status'] == 5 ){
            return " process_name ='assignment' ";
        }
        return  " ( process_name <> 'assignment' OR process_name IS NULL ) AND status = 1 ";
    }


    public static function dateConvert( $unixTime ,$default )
    {
        if ( !empty( $unixTime ) ){
            if ( is_numeric( $unixTime ) ){
                return date('Y/m/d' ,$unixTime );
            }
            return $unixTime;
        }
        return $default;
    }


    public static function checkSendJustNotif()
    {
        $delivers = NotifFunctions::indexChecker( $_POST ,'cron_deliver_system' ,[] );
        if ( !empty( $delivers ) ){
            foreach ( $delivers as $index => $deliver ){
                if ( isset( $deliver['status']) && $deliver['status'] == 'true' ){
                    return null;
                }
            }
        }
        return 'alert';
    }


    public static function returnCorrectStartDay()
    {
       $start_day = NotifFunctions::indexChecker( $_POST ,'cron_start_day' );
        if ( !empty( $start_day ) ){
            return date('Y-m-d' ,strtotime( $start_day ) );
        }
        return date('Y-m-d' );
    }


    public static function returnCorrectEndDay()
    {
        $end_day = NotifFunctions::indexChecker( $_POST ,'cron_end_day' );
        if ( !empty( $end_day ) ){
            return date('Y-m-d' ,strtotime( $end_day ) );
        }
        return '';
    }

    public static function returnLastRunDateTime()
    {
        $last_run = NotifFunctions::indexChecker( $_POST ,'cron_per_time' ,5 );
        if ( is_numeric( $last_run ) ){
            return date('Y-m-d H:i:s' ,strtotime('-'.$last_run.' minutes') );
        }
        return date('Y-m-d H:i:s' ,strtotime('-5 minutes') );
    }


    public static function getSettings()
    {
        $settings = NotifDB::getSettings();
        if ( !empty( $settings ) ){
            return json_decode( $settings );
        }
        return (object) self::getDefaultSetting();
    }


    public static function updateSettings()
    {
        $settings = NotifFunctions::indexChecker( $_POST ,'settings' ,[] );
        if ( !empty( $settings ) && is_array( $settings ) ){
            $settings = json_encode( $settings ,JSON_UNESCAPED_UNICODE );
            NotifDB::get_instance()::updateSettings( $settings );
            NotifFunctions::sendJsonResult( 200 ,['result' => 'recorded' ] );
        }
        NotifFunctions::sendJsonResult( 403 ,['result' => 'setting data is require' ] );
    }


    public static function getDefaultSetting()
    {
        return
            [
                'message-alert-text' => '666متن اعلان '
            ]
        ;
    }


    public static function clearDataBeforeSave( $data ,$column )
    {
        if ( !in_array( $column ,['label' ,'content' ,'status' ,'process_name'] ) ){
            return json_encode( $data ,JSON_UNESCAPED_UNICODE );
        }
        return $data;
    }



    public static function notifReadStatus( $successColumn ,$userID  )
    {
        if ( !empty( $successColumn ) && isset( $successColumn->$userID ) ){
            $current_details = $successColumn->$userID;
            if ( is_array( $successColumn->$userID ) ){
                $read_date   = $current_details[0];
                $action_date = $current_details[1];
                if ( empty( $read_date ) ){
                    return 'current-notif-read';
                }
            }
        }
        return '';
    }


    public static function getUsersByIDs( $item )
    {
        $final = [ 'users' => [] ,'success_users' => [] ,'failed_users' => [] ,'all_users' => [] ];
        if ( !empty( $item ) ){
            $users   = NotifFunctions::indexChecker( $item ,'users' );
            $success = NotifFunctions::indexChecker( $item ,'success_users' );
            $failed  = NotifFunctions::indexChecker( $item ,'failed_users' );
            $users   = json_decode( $users );
            $success = json_decode( $success );
            $failed  = json_decode( $failed );
            if ( !empty( $users ) && is_object( $users ) ){
                foreach ( $users as $user ){
                    $final['all_users'][ $user ] = $user;
                }
                $final['users'] = $users;
            }
            if ( !empty( $success ) && is_object( $success ) ){
                foreach ( $success as $suc_user_id => $suc_details  ){
                    $final['all_users'][ $suc_user_id ] = $suc_user_id;
                }
                $final['success_users'] = $success;
            }
            if ( !empty( $failed ) && is_object( $failed ) ){
                foreach ( $failed as  $fail_user_id => $fail_details  ){
                    $final['all_users'][ $fail_user_id ] = $fail_user_id;
                }
                $final['failed_users'] = $failed;
            }
            if ( !empty( $final['all_users'] ) && is_array( $final['all_users'] ) ){
                $final['all_users'] = NotifDB::getUsersByIDs( array_keys( $final['all_users'] ) );
            }
        }
        return $final;
    }


    public static function implodeForQuery( $array )
    {
        if ( !empty( $array ) && is_array( $array) ){
            $array  = implode("','"  , $array );
            return "IN ('$array')";
        }
        return '';
    }



}