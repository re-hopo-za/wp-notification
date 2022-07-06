jQuery(function ($) {

    const obj = notif_objects;
    const admin_url = obj.admin_url;
    const nonce = obj.nonce;
    const campaign_filter = obj.campaign_filter;
    const main_loader = obj.main_loader;
    const btn_loader = obj.btn_loader;

    let settings             = obj.settings;
    let save_notif_status    = true;
    let save_message_status  = true;
    let save_campaign_status = true;
    let save_cron_status     = true;


    initial_date_picker('#from-date-registered');
    initial_date_picker('#to-date-registered');
    initial_date_picker('#start-day');
    initial_date_picker('#end-day');
    initial_time_picker('#start-time');
    initial_time_picker('#end-time');
    include_products_filter('#includes-courses-select', 'includes');
    include_products_filter('#excludes-courses-select', 'excludes');
    includes_users_filter('#include-users-select');
    initial_sortable();
    test_sms_users_filter('#test-sms-select-user');
    init_reply_editor();




    $(document).on('click', '#save-notif', function (e) {

        let message  = $(document).find('.message-section');
        let campaign = $(document).find('.campaign-section');
        let cron     = $(document).find('.cron-section');

        let message_title = message.find('#message-form-title').val(),
            message_content = editor.getData(),
            message_sms_text_var = message.find('.content-sms #sms-text-variable').val(),
            message_sms_template = message.find('.content-sms #sms-template-name').val(),
            message_sms_priority = message.find('.content-sms #sms-priority').val(),
            message_cover = message.find('.message-file-con li img').attr('src') ,
            message_ticket_creator = message.find('#message-ticket-creator').val() ,
            message_ticket_course  = message.find('#message-ticket-creator').val();



        let campaign_date_f = campaign.find('#from-date-registered').val() ,
            campaign_date_t = campaign.find('#to-date-registered').val()  ,
            campaign_pl_users = campaign.find('#additional-users-plain-text').val() ,
            campaign_include = get_filter_list('.includes-courses li') ,
            campaign_exclude = get_filter_list('.excludes-courses li') ,
            campaign_users = get_filter_list('.additional-users li') ,
            campaign_group_web = campaign.find('#include-group-course-webmasteran').is(":checked") ,
            campaign_group_ins = campaign.find('#include-group-course-instagram').is(":checked") ,
            campaign_has_proc = campaign.find('#has-process-task').is(":checked");

        let cron_start_date  = cron.find('#start-day').val() ,
            cron_end_date    = cron.find('#end-day').val() ,
            cron_per_time    = cron.find('#per-time').val() ,
            cron_per_count   = cron.find('#per-count').val() ,
            cron_start_time  = cron.find('#start-time').val() ,
            cron_end_time    = cron.find('#end-time').val() ,
            cron_delivers    = cron.find('#system-add-list>div'),
            cron_more_than   = cron.find('#system-add-more-than-one input').is(":checked"),
            cron_deliver_sys = [];
        $.each( cron_delivers, function ( index ,element ) {
            cron_deliver_sys.push({id: $(element).attr('id'), status: $(element).find('input').is(":checked")} );
        });

        let process_name = $(document).find('#processName').val();
        let notif_label  = $(document).find('#cron-label').val();
        let status       = $(document).find('.status input[name="select-status"]:checked').val();
        if ( save_notif_status ) {
            let $this = $(this);
            $this.html(btn_loader);
            save_notif_status = false;
            $.ajax({
                url: admin_url,
                method: 'POST',
                data: {
                    action                         : 'hwp_notif_create_notif',
                    nonce                          : nonce,
                    message_title                  : message_title ,
                    message_content                : message_content ,
                    message_sms_text               : message_sms_text_var ,
                    message_sms_template           : message_sms_template ,
                    message_sms_priority           : message_sms_priority ,
                    message_ticket_creator         : message_ticket_creator ,
                    message_ticket_course          : message_ticket_course ,
                    message_cover                  : message_cover ,
                    campaign_includes_courses      : campaign_include,
                    campaign_excludes_courses      : campaign_exclude,
                    campaign_from_date_registered  : campaign_date_f ,
                    campaign_to_date_registered    : campaign_date_t,
                    campaign_webmasteran           : campaign_group_web,
                    campaign_instagram             : campaign_group_ins,
                    campaign_additional_users_text : campaign_pl_users,
                    campaign_has_process           : campaign_has_proc,
                    campaign_additional_users      : campaign_users,
                    cron_deliver_system            : cron_deliver_sys ,
                    cron_more_than                 : cron_more_than  ,
                    cron_start_day                 : cron_start_date,
                    cron_end_day                   : cron_end_date ,
                    cron_per_time                  : cron_per_time ,
                    cron_per_count                 : cron_per_count ,
                    cron_start_time                : cron_start_time ,
                    cron_end_time                  : cron_end_time ,
                    process_name                   : process_name,
                    notif_label                    : notif_label,
                    status                         : status ,
                    notif_id                       : get_current_notif_id()
                },
            }).always(function ( jqXHR ,textStatus ,jqXHR2 ) {
                if ( textStatus === 'success' ) {
                    let notif_id = getNotifIDFromAjaxResult( jqXHR );
                    if ( notif_id !==  false ){
                        console.log(notif_id);
                        iziToast.success({
                            title: 'اعلان',
                            message: 'پیام ذخیره شد!',
                        });
                        window.history.replaceState( null , null, 'admin.php?page=notifList&id=' + notif_id );
                    }
                }else{
                    iziToast.error({
                        title: 'خطا',
                        message:jqXHR.result  ,
                    });
                }
                save_notif_status = true;
                $this.html('Save Notif');
            });
        }
    });



    $(document).on('click', '#save-message', function (e) {
        e.preventDefault();
        let $form = $(document).find('.message-section');
        let title = $form.find('#message-form-title').val(),
            message_content = editor.getData(),
            sms_text_var = $form.find('.content-sms #sms-text-variable').val(),
            sms_template = $form.find('.content-sms #sms-template-name').val(),
            sms_priority = $form.find('.content-sms #sms-priority').val(),
            cover = $form.find('.message-file-con li img').attr('src') ,
            ticket_creator = $form.find('#message-ticket-creator').val() ,
            ticket_course  = $form.find('#message-ticket-course').val();

        if ( save_message_status ) {
            let $this = $(this);
            $this.html(btn_loader);
            save_message_status = false;
            $.ajax({
                url: admin_url,
                method: 'POST',
                data: {
                    action                  : 'hwp_notif_create_message',
                    nonce                   : nonce,
                    message_title           : title ,
                    message_content         : message_content ,
                    message_sms_text        : sms_text_var ,
                    message_sms_template    : sms_template ,
                    message_sms_priority    : sms_priority ,
                    message_cover           : cover ,
                    message_ticket_creator  : ticket_creator ,
                    message_ticket_course   : ticket_course  ,
                    notif_id                : get_current_notif_id()
                },
            }).always(function ( jqXHR, textStatus, jqXHR2 ) {
                if ( textStatus === 'success' ) {
                    let notif_id = getNotifIDFromAjaxResult( jqXHR );
                    if ( notif_id !==  false ){
                        console.log(notif_id);
                        iziToast.success({
                            title: 'اعلان',
                            message: 'پیام ذخیره شد!',
                        });
                        window.history.replaceState( null , null, 'admin.php?page=notifList&id=' + notif_id);
                    }
                }else{
                    iziToast.error({
                        title: 'خطا',
                        message:jqXHR.result  ,
                    });
                }
                save_message_status = true;
                $this.html('Save Message');
            });
        }
    });


    $(document).on('click', '#save-campaign', function (){
        if (save_campaign_status) {
            let $this = $(this);
            let form = $(document).find('.campaign-section');
            let date_f = form.find('#from-date-registered').val(),
                date_t = form.find('#to-date-registered').val(),
                pl_users = form.find('#additional-users-plain-text').val(),
                include = get_filter_list('.includes-courses li'),
                exclude = get_filter_list('.excludes-courses li'),
                users = get_filter_list('.additional-users li'),
                group_web = form.find('#include-group-course-webmasteran').is(":checked"),
                group_ins = form.find('#include-group-course-instagram').is(":checked"),
                has_proc = form.find('#has-process-task').is(":checked"); 

            save_campaign_status = false;
            $this.html( btn_loader );
            $.ajax({
                url: admin_url,
                method: 'POST',
                data: {
                    action                         : 'hwp_notif_create_campaigns',
                    nonce                          : nonce,
                    campaign_includes_courses      : include,
                    campaign_excludes_courses      : exclude,
                    campaign_from_date_registered  : date_f ,
                    campaign_to_date_registered    : date_t,
                    campaign_webmasteran           : group_web,
                    campaign_instagram             : group_ins,
                    campaign_additional_users_text : pl_users,
                    campaign_has_process           : has_proc,
                    campaign_additional_users      : users,
                    notif_id                       : get_current_notif_id()
                },
            }).always(function (jqXHR, textStatus, jqXHR2) {
                if ( textStatus === 'success' ) {
                    let notif_id = getNotifIDFromAjaxResult( jqXHR );
                    if ( notif_id !==  false ){
                        iziToast.success({
                            title: 'اعلان',
                            message: 'کمپین ذخیره شد!',
                        });
                        $(document).find('.save-users .processing-con b').text( jqXHR.result.count );
                        window.history.replaceState( null , null, 'admin.php?page=notifList&id=' + notif_id);
                    }
                }else{
                    iziToast.error({
                        title: 'خطا',
                        message:jqXHR.result  ,
                    });
                }
                save_campaign_status = true;
                $this.html('Save Campaign');
            });
        }
    });


    $(document).on('click', '#save-cron', function () {
        let $this = $(this);
        let form        = $(document).find('.cron-section') ,
            start_date  = form.find('#start-day').val(),
            end_date    = form.find('#end-day').val(),
            per_time    = form.find('#per-time').val() ,
            per_count   = form.find('#per-count').val() ,
            start_time  = form.find('#start-time').val() ,
            end_time    = form.find('#end-time').val() ,
            delivers    = form.find('#system-add-list>div'),
            more_than   = form.find('#system-add-more-than-one input').is(":checked"),
            deliver_sys = [];
            $.each( delivers, function ( index ,element ) {
                deliver_sys.push({id: $(element).attr('id'), status: $(element).find('input').is(":checked")} );
            });

        if ( save_cron_status ){
            save_cron_status = false;
            $this.html( btn_loader );
            $.ajax({
                url: admin_url,
                method: 'POST',
                data: {
                    action               : 'hwp_notif_create_cron',
                    nonce                : nonce,
                    cron_deliver_system  : deliver_sys ,
                    cron_status          : status ,
                    cron_more_than       : more_than  ,
                    cron_start_day       : start_date,
                    cron_end_day         : end_date ,
                    cron_per_time        : per_time ,
                    cron_per_count       : per_count ,
                    cron_start_time      : start_time ,
                    cron_end_time        : end_time ,
                    notif_id             : get_current_notif_id()
                },
            }).always(function (jqXHR, textStatus, jqXHR2) {
                if ( textStatus === 'success' ) {
                    let notif_id = getNotifIDFromAjaxResult( jqXHR );
                    if ( notif_id !==  false ){
                        iziToast.success({
                            title: 'اعلان',
                            message: 'کران ذخیره شد!',
                        });
                        $(document).find('.bottom-con b').text(jqXHR.result.count);
                        window.history.replaceState( null , null, 'admin.php?page=notifList&id=' + notif_id);
                    }
                }else{
                    iziToast.error({
                        title: 'خطا',
                        message:jqXHR.result
                    });
                }
                save_cron_status = true;
                $this.html('Save Cron');
            });
        }
    });



    $(document).on('click', '#add-image-file', function (e) {
        e.preventDefault();
        let custom_uploader = wp.media({
            title: 'Insert file',
            library: {
                type: 'image'
            },
            button: {
                text: 'Use this image'
            },
            multiple: false
        }).on('select', function () {
            let attachment = custom_uploader.state().get('selection').first().toJSON();
            $(document).find('.message-file-con').append(
                '    <li>' +
                '        <span data-message-remover=""> حذف </span>' +
                '        <img src="' + attachment.url + '" alt="">' +
                '    </li>');
            $(document).find('.message-file-con span').data('message-remover', attachment.id);
            $(document).find('#add-image-file').hide();
        }).open();
    });
    $(document).on('click', '.message-file-con li span', function (e) {
        $(this).parent().remove();
        $(document).find('#add-image-file').show();
    });
    $(document).on('click', '.message-form-con .content-sms input', function (e) {
        let val = $(this).val();
        if (val == 'template') {
            $(document).find('.message-form-con .content-sms textarea').addClass('template');
        } else {
            $(document).find('.message-form-con .content-sms textarea').removeClass('template');

        }
    });






///// notif action
    $(document).on('click', '.notif-main-con .icons-list i', function () {
        let which_section = $(this).attr('id');
        let this_section = $(this).parent().siblings('.sections-list');
        if ($(this).hasClass('active')) {
            $(document).find('.icon-closer').hide();
            $(this).removeClass('active');
            $(this_section).find('.' + which_section).removeClass('active');
        } else {
            $(document).find('.icon-closer').show();
            $(this).parent().find('i').removeClass('active');
            $(this).addClass('active');
            $(this_section).find('section').removeClass('active');
            $(this_section).find('.' + which_section).addClass('active');
        }
    });
    $(document).on('click', '.icon-closer', function () {
        $(document).find('.notif-list .sections-list section').removeClass('active');
        $(document).find('.notif-list .icons-list i').removeClass('active');
        $(this).hide();
    });


    ////remove notif item
    $(document).on('click', '.delete-notif-item', function () {
        let $this = $(this);
        if (confirm('حذف شود؟') ) {
            let notif_id = $this.parent().data('notif-id');
            if ( notif_id ){
                $.ajax({
                    url: admin_url,
                    method: 'POST',
                    data: {
                        action: 'hwp_notif_update_status',
                        nonce: nonce,
                        notif_id: notif_id ,
                        status:3
                    },
                }).always(function (jqXHR, textStatus, jqXHR2) {
                    if (textStatus === 'success') {
                        $this.parent().parent().remove();
                    }
                });
            }
        }
    });


    ////update notif item
    $(document).on('click', '.update-notif-status', function () {
        let $this = $(this);
        let notif_id = $this.parent().data('notif-id');
        let status = $this.hasClass('dashicons-controls-play') ? 1 : 0;
        if (notif_id  && confirm('وضعیت تغییر کند؟ ') ) {
            $this.addClass('loading-on-update-status');
            $.ajax({
                url: admin_url,
                method: 'POST',
                data: {
                    action: 'hwp_notif_update_status',
                    nonce: nonce,
                    notif_id: notif_id,
                    status: status
                },
            }).always(function (jqXHR, textStatus) {
                if (textStatus === 'success') {
                    $this.removeClass('loading-on-update-status');
                    if ( status === 0 ){
                        $this.removeClass('dashicons-controls-pause').addClass('dashicons-controls-play');
                    }else{
                        $this.removeClass('dashicons-controls-play').addClass('dashicons-controls-pause');
                    }
                }
            });
        }
    });


    function initial_sortable() {
        if ($(document).find('#system-add-list').length) {
            new Sortable(document.getElementById('system-add-list'), {
                handle: '.dashicons',
                animation: 150
            });
        }
    }

    $(document).on('click', '#system-add-more-than-one input', function () {
        if ($(this).is(":checked")) {
            $(document).find('#system-add-list i').hide();
        } else {
            $(document).find('#system-add-list i').show();
        }
    });



    ///// campaign
    function include_products_filter(element, which) {
        if ($(document).find(element).length) {
            let product_search_includes = $(document).find(element);
            let options = [];
            product_search_includes.select2({
                placeholder: 'انتخاب دوره',
                allowClear: false,
                width: '100%',
                ajax: {
                    url: admin_url,
                    dataType: 'json',
                    method: 'post',
                    delay: 250,
                    data: function (params) {
                        return {
                            keyword: params.term,
                            action: 'hwp_notif_search_products',
                            nonce: nonce
                        };
                    },
                    processResults: function (data) {
                        if (data.result) {
                            $.each(data.result, function (index, text) {
                                options.push({id: text.id, text: text.title, price: text.price});
                            });
                        }
                        return {
                            results: options
                        };
                    },
                    cache: false
                },
                minimumInputLength: 3
            });
            product_search_includes.on("change", function () {
                let include_item =
                    filter_item(product_search_includes.select2('data')[0].id, product_search_includes.select2('data')[0].text);
                $(document).find('.' + which + '-courses>ul').append(include_item);
                $(document).find('.' + which + '-courses-search').hide();
                closer_area(false);
            });
            $(document).on('click', '#' + which + '-courses-search-btn', function () {
                $(document).find('.' + which + '-courses-search').show();
                closer_area();
            });
            $(document).on('click', '.close-' + which + '-product-search', function () {
                $(this).parent().hide();
                closer_area(false);
            });
            $(document).on('click', '.product-filters-items svg', function () {
                $(this).parent().remove();
            });
        }
    }

    function initial_date_picker(element) {
        if ($(element).length) {
            let val = $(document).find(element).val();
            $(document).find(element).persianDatepicker({
                persianNumbers: false,
                selectedBefore: !0,
                prevArrow: '\u25c4',
                nextArrow: '\u25ba',
                formatDate: "YYYY/MM/DD",
                isRTL: !1,
                onShow: function () {
                    closer_area();
                },
                onHide: function () {
                    closer_area(false);
                },
                onSelect: function (date) {
                    let d = new Date(date);
                    let date_string = d.getFullYear() + "-" + (d.getMonth()+1) + "-" + d.getDate();
                    $(element).val(date_string);
                    $(element).data('jdate', date);
                }
            });
            if( val ){
                let d = new Date( val );
                let date_string = d.getFullYear() + "-" + (d.getMonth()+1) + "-" + d.getDate();
                $(document).find(element).val(date_string);
            }else{
                $(document).find(element).val('');
            }
        }
    }

    function initial_time_picker(element) {
        if ($(document).find(element).length) {
            $(document).find(element).timepicker();
        }
    }


    $(document).on('click', '.message-item .middle #send-tesst-message', function () {
        let $this = $(this);
        let message_id = $this.parent().parent().data('message-id');
        if (confirm('تست شود؟')) {
            $.ajax({
                url: admin_url,
                method: 'POST',
                data: {
                    action: 'hwp_notif_test_message',
                    nonce: nonce,
                    message_id: message_id
                },
            }).always(function (jqXHR, textStatus, jqXHR2) {
                if (textStatus === 'success') {
                    alert('ارسال شد');
                } else {
                    console.log(jqXHR, textStatus, jqXHR2);
                    alert('خطا');
                }
            });
        }
    });

    function includes_users_filter(element) {
        if ($(document).find(element).length) {
            let include_users_search = $(document).find(element);
            let options = [];
            include_users_search.select2({
                placeholder: 'انتخاب کاربر',
                allowClear: false,
                width: '100%',
                ajax: {
                    url: admin_url,
                    dataType: 'json',
                    method: 'post',
                    delay: 250,
                    data: function (params) {
                        let exclude_users = [];
                        $($('.additional-users ul li')).each(function () {
                            exclude_users.push(parseInt($(this).data('item-id')));
                        });
                        return {
                            keyword: params.term,
                            action: 'hwp_notif_search_users',
                            nonce: nonce,
                            exc: exclude_users
                        };
                    },
                    processResults: function (data) {
                        if (data.result) {
                            $.each(data.result, function (index, text) {
                                options.push({id: text.id, text: text.title});
                            });
                        }
                        return {
                            results: options
                        };
                    },
                    cache: false
                },
                minimumInputLength: 3
            });

            include_users_search.on("change", function () {
                if ( include_users_search.select2('data')[0] ){
                    let include_item = filter_item(include_users_search.select2('data')[0].id, include_users_search.select2('data')[0].text);
                    $(document).find('.additional-users ul').append(include_item);
                    $(document).find('.include-users-search').hide();
                    closer_area(false);
                }
            });
            $(document).on('click', '#include-users-search-btn', function () {
                $(document).find('.include-users-search').show();
                closer_area();
            });
            $(document).on('click', '.close-include-users-search', function () {
                $(this).parent().hide();
                closer_area(false);
            });
            $(document).on('click', '.include-users-items svg', function () {
                $(this).parent().remove();
            });
        }
    }


    $(document).on('click', '.condition-sign ,.condition-sign-between', function () {
        let $this = $(this);
        if ($this.hasClass('and')) {
            $this.removeClass('and');
            $this.text('Or');
        } else {
            $this.text('And');
            $this.addClass('and');
        }
    });


    $(document).on('click', '.course-condition-sign', function () {
        let $this = $(this);
        if ($this.hasClass('or')) {
            $this.removeClass('or');
            $this.text('And');
        } else {
            $this.text('Or');
            $this.addClass('or');
        }
    });
    ////remove section end


    function get_filter_list(element) {
        let list = [];
        $($(element)).each(function () {
            list.push(parseInt($(this).data('item-id')));
        });
        return list;
    }


    function validator_inputs( input, length ) {
        if ( input ) {
            if (input.length >= length) {
                return true;
            }
        }
        return false;
    }


    function closer_area(status = true) {
        let closer_element = $(document).find('.closer-area');
        if (status) {
            closer_element.show();
        } else {
            closer_element.hide();
        }
    }


    function filter_item(id, txt) {
        if (validator_inputs(id, 1) && validator_inputs(txt, 1)) {
            let include_item = campaign_filter.replace('[id]', id);
            return include_item.replace('[text]', txt);
        }
        return '';
    }

    function select2_placeholder(which) {
        switch (which) {
            case 'messages' :
                return 'انتخاب پیام ';
            case 'campaigns' :
                return 'انتخاب کمپین ';
            case 'crons' :
                return 'انتخاب کران ';
        }
    }


    ///setting section
    function test_sms_users_filter(element) {
        if ($(document).find(element).length) {
            let test_sms_users_filter = $(document).find(element);
            let options = [];
            test_sms_users_filter.select2({
                placeholder: 'انتخاب کاربر',
                allowClear: false,
                width: '100%',
                ajax: {
                    url: admin_url,
                    dataType: 'json',
                    method: 'post',
                    delay: 250,
                    data: function (params) {
                        return {
                            keyword: params.term,
                            action: 'hwp_notif_search_users',
                            nonce: nonce
                        };
                    },
                    processResults: function (data) {
                        if (data.result) {
                            $.each(data.result, function (index, text) {
                                options.push({id: text.id, text: text.title});
                            });
                        }
                        return {
                            results: options
                        };
                    },
                    cache: false
                },
                minimumInputLength: 3
            });

        }
    }


    function init_reply_editor() {
        let html_editor = $(document).find('#notif-html-message-editor');
        if (html_editor.length) {
            editor_creator('#notif-html-message-editor');
        }
    }


    function editor_creator(editor_creator) {
        if (window.editor) {
            window.editor.destroy()
                .then(() => {
                    editor.ui.view.toolbar.element.remove();
                    editor.ui.view.editable.element.remove();
                });
        }
        ClassicEditor
            .create(document.querySelector(editor_creator), {
                toolbar: {
                    items: [
                        'heading',
                        'bold',
                        'code',
                        '|',
                        'codeBlock',
                        'specialCharacters',
                        '|',
                        'alignment'
                    ]
                },
                wordCount: {},
                language: 'fa',
                licenseKey: '',
                entitiesLatin: false,
                entitiesGreek: false
            })
            .then(editor => {
                window.editor = editor;
            })
            .catch(error => {
                console.error('Oops, something went wrong!' + error);
            });
    }


    function getNotifIDFromAjaxResult( jqXHR  ){
        if ( jqXHR.result.notif_id ) {
            return jqXHR.result.notif_id;
        } else {
            return false;
        }
    }

    function get_current_notif_id(){
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        return  urlParams.get('id');
    }


    function initial_setting(){
       for ( const key in settings ){
           $(document).find('.setting-item-con [data-setting-name="'+key+'"]').val( settings[key] );
       }
    }
    initial_setting();


    $(document).on('click', '#save-setting', function () {
        let setting_items = $(document).find('.setting-content .setting-item');
        $( setting_items ).each( function( index ,ele ) {
            let key = $(ele).data('setting-name');
            settings[key] = $(ele).val();
        });
        let $this = $(this);
            $this.html(btn_loader);
        $.ajax({
            url: admin_url,
            method: 'POST',
            data: {
                action   : 'hwp_notif_settings',
                nonce    : nonce,
                settings : settings
            },
        }).always(function (jqXHR, textStatus, jqXHR2) {
            if (textStatus === 'success') {
                iziToast.success({
                    title: 'اعلان',
                    message: 'تنظیمات ذخیره شد!',
                });
            }else {
                iziToast.error({
                    title: 'خطا',
                    message:jqXHR.result
                });
            }
            $this.html('save');
        });

    });




})