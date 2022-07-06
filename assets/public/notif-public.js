
jQuery(function ($){
    const obj      = notif_objects;
    const nonce    = obj.nonce;
    const ajax_url = obj.ajax_url;
    const loader   = obj.btn_loader;


    $(document).on('click' ,'.notif-single-button' ,function (e){
        let $this    = $(this);
        let notif_id = $this.data('id');
        if ( notif_id ){
            $this.html( loader );
            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action   : 'notif_single_loader' ,
                    nonce    : nonce,
                    notif_id : notif_id,
                },
            }).always(function (jqXHR, textStatus, jqXHR2) {
                if ( textStatus === 'success' ) {
                    $(document).find('#notif-container').html( jqXHR.result );
                }else{
                    $this.html( 'نمایش' );
                }
            });
        }
    });



    $(document).on('click' ,'#get-all-notif-items' ,function (e){
        $(this).html( loader );
        $.ajax({
            url: ajax_url,
            method: 'POST',
            data: {
                action : 'notif_all_loader',
                nonce  : nonce,
            },
        }).always(function (jqXHR, textStatus, jqXHR2) {
            if ( textStatus === 'success' ) {
                $(document).find('#notif-container').html( jqXHR.result );
            }else{
                $this.html( 'بازگشت' );
            }
        });
    });


    $(document).on('click' ,'.notif-list-con .notif-single .content .bottom a' ,function (e)
    {
        e.preventDefault();
        let $this    = $(this);
        let notif_id = $(document).find('.notif-single').data('notif-id');
        let href     = $this.attr('href');
        if ( notif_id ){
            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action   : 'hwp_notif_update_user_action',
                    nonce    : nonce,
                    notif_id : notif_id
                },
            }).always(function (jqXHR, textStatus, jqXHR2) {
                window.location = href;
            });
        }
    });



});