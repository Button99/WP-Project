jQuery(document).ready(function() {

    if (Workflow.modals.get_url_parameter('success', window.location) && 
            Workflow.modals.get_url_parameter('page', window.location) === 'lingotek-translation_settings' &&
            Workflow.modals.get_url_parameter('sm', window.location) === 'defaults') {
        jQuery('#workflow_id').attr('value','ltk-professional-translation');
        jQuery('#submit').trigger('click');
    }

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
    // var last_select = true;


    set_up();

    function set_up()
    {
        // last_select = true;
        var starting_workflow_select = jQuery('#workflow_id')[0];
        if (starting_workflow_select.options[starting_workflow_select.selectedIndex].text.includes('Project Default')) {
            nullifyWorkflowOverrides();
        } else {
            resetWorkflowOverrides();
        }
        add_payment_later_listener();
        workflow_change_listener();
        jQuery('.payment-portal-image').prop('src', professional_vars.translation_icon);
        jQuery('.payment-portal-loading').prop('src', professional_vars.loading_gif);
        timeout = false;
        jQuery(document).on('click', '.edit-payment-method-already-setup, .edit-payment-method-not-setup', edit_payment_info_click_listener);
    }

    function edit_payment_info_click_listener(e) 
    {
        e.preventDefault();
        show_base_modal();
        show_payment_portal_loading_screen();
        set_payment_portal_timeout();
    }

    function tear_down()
    {
        if (timeout)
        {
            clearTimeout(timeout);
        }
        // if (last_select)
        // {
        //     lastSel.attr('selected', true);
        // }
        jQuery.each(Workflow.workflows, function(key, value) {
            jQuery('#yes-' + key).off();
        });
        jQuery(document).off('click', '.edit-payment-method-already-setup, .edit-payment-method-not-setup', edit_payment_info_click_listener);
        add_payment_later_listener_off();
        workflow_change_listener_off();
        show_normal_screen();

        set_up();
    }


    function show_normal_screen()
    {
        jQuery('#TB_ajaxContent').find('*').not('.payment-portal-element').show();
        jQuery('.payment-portal-element').hide();
    }

    function add_payment_later_listener()
    {
        jQuery('#no-' + professional_vars.workflow_id).on('click', function() {
            console.log('click');
            // last_select = false;
            Workflow.modals.close_modal('TB_closeWindowButton');
        });
    }

    function add_payment_later_listener_off()
    {
        jQuery('#no-' + professional_vars.workflow_id).off();
    }

    function workflow_change_listener()
    {
        jQuery('#workflow_id, select[id^="custom[workflow_id]"]').on('change', function(e) {
            if (Workflow.workflows.hasOwnProperty(this.value))
            {
                var workflow = this.value;

                if (!professional_vars.payment_info.payment_info.payment_profile)
                {
                    modal_init();
                    jQuery(this).after('&nbsp;&nbsp;<a class="edit-payment-info edit-payment-method-not-setup" href="#">Setup Lingotek Payment Method</a>');
                    jQuery('#edit-payment-method-not-setup').on('click', function() {
                        modal_init();
                        show_payment_portal_loading_screen();
                        set_payment_portal_timeout();
                    });
                }
                else
                {
                    jQuery(this).after('&nbsp;&nbsp;<a class="edit-payment-info edit-payment-method-already-setup" href="#">Edit Lingotek Payment Method</a>');
                }
            }
            else
            {
                if (e.target.id === 'workflow_id') {
                    var selectedText = this.options[this.selectedIndex].text;
                    if (selectedText.includes('Project Default')) {
                        nullifyWorkflowOverrides();
                    } else {
                        resetWorkflowOverrides();
                    }
                }
                console.log('remove');
                jQuery(this).closest('td').find('.edit-payment-method-already-setup, .edit-payment-method-not-setup').remove();
            }
        });
    }

    function nullifyWorkflowOverrides()
    {
        var workflow_overrides = jQuery('select[id^="custom[workflow_id]"]');
        for (var select of workflow_overrides) {
            select.selectedIndex = 1; // Project Default
            select.disabled = 'disabled';
        }
    }

    function resetWorkflowOverrides()
    {
        var workflow_overrides = jQuery('select[id^="custom[workflow_id]"]');
        for (var select of workflow_overrides) {
            var selected_index = 0; // Global Default
            select.options[1].disabled = 'disabled';
            if (select.options[0].text.includes('Project Default')) {
                selected_index = 2; // After initial 2 defaults
                select.options[0].disabled = 'disabled';
            }
            select.selectedIndex = selected_index;
            select.disabled = false;
        }
    }

    function workflow_change_listener_off()
    {
        jQuery('#workflow_id, select[id^="custom[workflow_id]"]').off();
    }

    function modal_init()
    {
        jQuery('#yes-' + professional_vars.workflow_id).on('click', function() {
            show_payment_portal_loading_screen();
            set_payment_portal_timeout();
        });
        show_base_modal();
        show_normal_screen();
        
    }

    function set_payment_portal_timeout()
    {
        timeout = setTimeout(function() {
                window.location = professional_vars.bridge_payment + '?redirect_url=' + encodeURIComponent(window.location);
        },3000);
    }

    function show_base_modal()
    {
        tb_show('<img src="' + modal_vars.icon_url +'" style="padding-top:10px; display:inline-block"><span style="position:absolute; padding-top: 15px;"class="Upgrade-to-Lingotek">Lingotek Professional Translation Services</span>', '#TB_inline?width=800&height=400&inlineId=modal-window-id-' + professional_vars.workflow_id);
        jQuery('#TB_title').css('height','55px');
        jQuery('#TB_title').css('background-color','#3c3c3c');
        jQuery('.tb-close-icon').css('color','white');
    }

    function show_payment_portal_loading_screen() 
    {
        jQuery('#TB_ajaxContent').find('*').not('.payment-portal-element').hide();
        jQuery('.payment-portal-element').show();
    }
    // var lastSel = jQuery('#workflow_id option:selected');
    // try new rollback here.
    
});