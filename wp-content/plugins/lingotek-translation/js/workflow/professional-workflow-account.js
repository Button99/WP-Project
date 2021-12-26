jQuery(document).ready(function() {
    var twice = false;
    jQuery(document).on('tb_unload', function(event) {
        if (twice)
        {
            console.log('unload');
            tear_down();
            twice = false;
        }
        else
        {
            twice = true;
        }
    });

    var timeout = false;

    set_up();


    function set_up()
    {
        payment_method_click_listener();
        timeout = false;
    }

    function tear_down()
    {
        if (timeout)
        {
            clearTimeout(timeout);
        }
        payment_method_click_listener_off();
        set_up();
    }

    function payment_method_click_listener()
    {
        jQuery('#professional-payment-info-link').on('click', function(e) {
            console.log('clicker');
            e.preventDefault();
            tb_show('<img src="' + modal_vars.icon_url +'" style="padding-top:10px; display:inline-block"><span style="position:absolute; padding-top: 15px;"class="Upgrade-to-Lingotek">Lingotek Professional Translation Services</span>', '#TB_inline?width=800&height=400&inlineId=modal-window-id-' + account_vars.modal_id);
            jQuery('#TB_title').css('height','55px');
            jQuery('#TB_title').css('background-color','#3c3c3c');
            jQuery('.tb-close-icon').css('color','white');
            set_payment_portal_timeout();
        });
    }

    function payment_method_click_listener_off() 
    {
        jQuery('#professional-payment-info-link').off('click');
    }


    function set_payment_portal_timeout()
    {
        timeout = setTimeout(function() {
                window.location = account_vars.bridge_payment + '?redirect_url=' + encodeURIComponent(window.location);
        },3000);
    }
});