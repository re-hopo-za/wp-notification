<?php

namespace Notif\includes\request;



use Notif\includes\contents\NotifJson;
use Notif\includes\NotifFunctions;
use WP_REST_Request;
use WP_REST_Server;


class NotifRest{


    protected static  $_instance = null;
    public static function get_instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public $NAMESPACE;
    public $VERSION;
    public $ENDPOINT;
    public $API;
    public $PARAMS;
    public $userID;



    public function __construct(){

        add_action('rest_api_init' , [ $this , 'routes' ] ) ;

        $this->NAMESPACE = 'hamfy';
        $this->VERSION   = 'v1.1';
        $this->ENDPOINT  = 'notif';
        $this->API       = $this->NAMESPACE.'/'.$this->VERSION.'/'.$this->ENDPOINT ;
    }


    public function routes(){

        register_rest_route(  $this->NAMESPACE , $this->VERSION.'/'.$this->ENDPOINT , [
            'methods'  => WP_REST_Server::READABLE     ,
            'callback' => [ $this , 'read' ]           ,
            'args'     => $this->argsValidator('READABLE')   ,
            'permission_callback' => [ $this , 'authentication' ],
        ]);
        register_rest_route(   $this->NAMESPACE , $this->VERSION.'/'.$this->ENDPOINT , [
            'methods'  => WP_REST_Server::EDITABLE      ,
            'callback' => [ $this , 'update' ]          ,
            'args'     => $this->argsValidator('EDITABLE')   ,
            'permission_callback' => [ $this   , 'authentication' ],
        ]);

    }


    public function authentication( WP_REST_Request $request ){
        $this->PARAMS  = (object) $request->get_params();
        $this->userID  = NotifFunctions::decryptID( $request->get_headers()['usertoken'][0]);
        return is_numeric( $this->userID );
    }


    public function read(){
        $bell_list = NotifJson::getAll( $this->userID );
        if ( !empty( $bell_list ) ){
            wp_send_json( ['result' => $bell_list ] , 200  );
        }else{
            wp_send_json( ['result' => [] ] , 204  );
        }
    }

    public function argsValidator( $which ){
        $args=[];
        if ( $which == 'EDITABLE' ){
            $args['bell_id']=[
                'required'           => false        ,
                'description'        => 'شناسه اعلان' ,
                'type'               => 'int'        ,
                'sanitize_callback'  => function( $value ){
                    return NotifFunctions::sanitizer( $value ,'sanitize_text_field,trim' );
                },
                'validate_callback'  => function( $value ){
                    return is_numeric( $value );
                },
            ];
        }
        return $args;
    }


}


