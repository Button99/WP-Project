jQuery(document).ready(function() {

    /**
     * Re-attaches event handlers to icons every second. This allows the professional workflow to work with the 
     * auto updater.js
     */
    setInterval(function() { 
        if (jQuery('#TB_window').find('.request-table').length > 0)
        {
            jQuery('#TB_window').removeAttr('style');
            jQuery('#TB_window').addClass('ltk-thickbox');
        }
        else if (jQuery('#TB_window').find('.professional-upload-warning-header-container').length > 0)
        {
            jQuery('#TB_window').removeAttr('style');
            jQuery('#TB_window').addClass('ltk-thickbox-warning');
        }
    },1000);

    Workflow.reload_list.push(function() {
        if (jQuery('#TB_ajaxWindowTitle').length === 0 )
            tear_down();
    });

    const MINIMUM = 59.99;
    const DEFAULT_MINIMUM_DELIVERY_DAYS = 3;
    const DEFAULT_MAXIMUM_DELIVERY_DAYS = 5;
    const DELIVERY_WORDS_INCREASE = 2000;
    var yes = '#yes-' + workflow_vars.id;
    var no = '#no-' + workflow_vars.id;
    var yes_request = yes + '-request';
    var no_request = no + '-request';

    /**
     * This is a reduntant variable that ensures that a customer is not charged twice due to a duplicate API call.
     */
    var dispatched = false;
    
    /**
     * The Wordpress tb_unload event fires twice. This code ensures that the reset code only runs once.
     */
    var twice = false;
    jQuery(document).on('tb_unload', function(event) {
        if (twice)
        {
            tear_down();
            twice = false;
            dispatched = false;
        }
        else
        {
            twice = true;
        }
    });


    var global_locale_list = [];
    var global_ajax_requests_list = [];

    /**
     * This is a failsafe to guard against the user attempting to click 'apply' rapidly when executing bulk actions.
     * When a bulk action has started this flag is switched to true so that a bulk action cannot be requested until the tear_down()
     * method has been called when the modal is destroyed.
     */
    var start_bulk = false;

    /**
     * This is a failsafe to guard against the user closing the modal after ajax requests have been launched. We don't want any left-over 
     * ajax requests to come back and alter the modal. This acts as a sort of 'lock' so that any any ajax requests coming back aren't allowed to 
     * touch the modal.
     */
    var abort_ajax = false;

    var buy_now_clicked = false;
    /**
     * As requests come back this buffer is filled with rows to render. 
     */
    var document_id_to_row_buffer = {};

    var document_id_to_post_id = {};

    var loading_exceptions = '.payment-portal-element, .row-table-hidden, .header-table-hidden, .requesting-element, .payment-not-setup, .payment-method-setup, .professional-translation-request-success-element, .professional-translation-request-error-element';

    var global_timeout = false;

    set_up(); // set event listeners.

    /**
     *  Attaches event listeners to the upload and request icons.
    * 
     */
    function set_up()
    {
        init_handlers();
        disable_buy_now_button();
        disable_confirm_warning_button();
        disable_confirm_warning_checkbox();
        init_globals();
        init_lock_flags();
        modal_refresh();
    }


    /**
     * Removes all listeners and re-attaches them. This needs to happen because 
     * all HTTP communication is happening via ajax so it is crucial that we don't 
     * leave the frontend in an invalid state.
     * 
     */
    function tear_down()
    {
        if (global_timeout)
        {
            clearTimeout(global_timeout);
        }
        global_timeout = false;
        clear_ajax_requests();
        detach_handlers();
        clear_global_lists();
        set_up();
        check_buy_now_clicked();
    }

    function check_buy_now_clicked()
    {
        if (click_bulk_action)
        {
            jQuery('#doaction').trigger('click');
        } 
        else if (buy_now_clicked)
        {
            location.reload();
        }
        click_bulk_action = false;
    }

    function clear_global_lists()
    {
        /**
         * Clear all of our lists.
         */
        document_id_list = [];
        // locale_list = [];
        global_locale_list = [];
        active_locale_list = [];
        shown_headers = [];
        shown_columns = {};
        document_id_to_row_buffer = {};
        document_id_to_post_id = {};
    }

    function detach_handlers()
    {
        jQuery(yes_request).off();
        jQuery(no_request).off();
        jQuery(yes).off();
        jQuery(no).off();
        jQuery('.checkable').off();
        jQuery('#next-language-set').off();
        jQuery('#doaction, #doaction2').off();
        jQuery.each(item_ids.ids, function(item_id, valid_locales) {
            get_post_or_taxonomy_row(item_id).find('.dashicons-plus-lingotek').each(function(index, element) {
                var url = jQuery(element).attr('href');
                var locale = Workflow.modals.get_url_parameter('locale', url);
                if (is_valid_locale(locale, valid_locales))
                {
                    Workflow.modals.replace_href(element);
                    jQuery(element).off();
                }
            });
            get_post_or_taxonomy_row(item_id).find('.lingotek-request').off();
            get_post_or_taxonomy_row(item_id).find('.lingotek-upload').each(function(index, element) {
                jQuery(jQuery(element).children()[0]).off();
            });

            get_string_group_row(item_id).find('.dashicons-plus-lingotek').each(function(index, element) {
                var url = jQuery(element).attr('href'); 
                var locale = Workflow.modals.get_url_parameter('locale', url);
                if (is_valid_locale(locale, valid_locales))
                {
                    Workflow.modals.replace_href(element);
                    jQuery(element).off();
                }
            });
            get_string_group_row(item_id).find('.lingotek-request').off();
            get_string_group_row(item_id).find('.lingotek-upload').each(function(index, element) {
                jQuery(jQuery(element).children()[0]).off();
            });
        });

        jQuery.each(global_locale_list, function(index,value) {
            jQuery(document).off('click', '.' + value + '-header');
            jQuery('.' + value + '-header').remove();
        });

        jQuery.each(document_id_list, function(index,value) {
            jQuery(document).off('click', '.' + value);
        });

        jQuery(document).off('click', '.checkable');

        jQuery(document).off('ajaxStop');

        jQuery(document).off('click','#yes-' + workflow_vars.workflow_id + '-request-add-payment');

        jQuery('#bulk-lingotek-request').off();

        jQuery('.dashicons-upload-lingotek').off();

        terms_and_conditions_listener_off();
        accept_terms_and_conditions_listener_off();
        buy_now_listener_off();

        documents_could_be_overwritten_handler_off();

    }

    function clear_ajax_requests()
    {
        /**
         * Ajax requests are stored as they are fired. The ones that come back successfully remove themselves from the list. If there
         * are any ajax requests left in this list then we know that they didn't finish when the modal was closed so we turn on our ajax lock and abort 
         * all of the left over requests.
         */
        jQuery.each(global_ajax_requests_list, function(index,request) {
            abort_ajax = true;
            request.abort();
        });
    }


    function modal_refresh()
    {
        /**
         * Removes any left over table items.
         */
        jQuery('.request-table-item').remove();
    }

    function init_lock_flags()
    {
        /**
         * Reset our flags.
         */
        start_bulk = false;
        abort_ajax = false;
    }

    function init_globals()
    {
        /**
         * The enabled_langs property is an object that maps a wordpress locale to a lingotek locale.
         * For compatibilty with older browsers we extract the lingotek locales this way.
         */
        for (var key in workflow_vars.enabled_langs) 
        {
            if (Object.prototype.hasOwnProperty.call(workflow_vars.enabled_langs, key)) {
                var val = workflow_vars.enabled_langs[key];
                global_locale_list.push(val.lingotek_locale);
            }
        }  

        global_ajax_requests_list = [];

    }


    

    function init_handlers()
    {
         /**
         * A list of post Ids are sent from the server indicating which posts have professional translation enabled.
         * We iterate over that list and attach listeners to those icons.
         */
        jQuery.each(item_ids.ids, function(post_id, valid_locales) {
            var row;
            if (workflow_vars.curr_item_type === 'string')
            {
                row = get_string_group_row(post_id);
            }
            else
            {
                row = get_post_or_taxonomy_row(post_id);
            }

            row.find('.dashicons-plus-lingotek').each(function(index, element) {
                var href = jQuery(element).attr('href') !== '#' ? jQuery(element).attr('href') : jQuery(element).attr('url');
                var locale = Workflow.modals.get_url_parameter('locale', href);
                if (is_valid_locale(locale, valid_locales))
                {
                    jQuery(element).off();
                    jQuery(element).on('click', request_handler);
                    var url = Workflow.modals.replace_href(element);
                    var doc_id = Workflow.modals.get_url_parameter('document_id', url);
                    document_id_to_post_id[doc_id] = post_id;
                }

            });



            /**
             * Attaches an event handler the the 'Request translations' row action.
             */
            row.find('.lingotek-request').on('click', request_translation_row_handler);


            /**
             * The 'lingotek-request' span tag wraps an href. We want to replace this href with '#' so that there is no way
             * for it to get executed.
             */
            row.find('.lingotek-request').each(function(index, element) {
                Workflow.modals.replace_href(jQuery(element).children()[0]);
            });

            row.find('.lingotek-upload').each(function(index, element) {
                if (documents_could_be_overwritten(jQuery(element).children()[0]))
                {
                    Workflow.modals.replace_href(jQuery(element).children()[0]);
                    jQuery( jQuery(element).children()[0] ).attr('onclick', '');
                    jQuery(jQuery(element).children()[0]).on('click', documents_could_be_overwritten_handler);
                }
            });
            
            row.find('.dashicons-upload-lingotek').each(function(index, element) {
                if (documents_could_be_overwritten(element))
                {
                    Workflow.modals.replace_href(element);
                    jQuery(element).on('click', documents_could_be_overwritten_handler);
                }
            });
        });     

        jQuery('#doaction, #doaction2').on('click', bulk_request_handler);
        jQuery('#doaction, #doaction2').on('click', bulk_upload_handler);

        jQuery(document).on('click','#yes-' + workflow_vars.workflow_id + '-request-add-payment', add_payment_method_handler);

        jQuery('#professional-post-transaction-button, #professional-post-transaction-failure').on('click', function() {
            Workflow.modals.close_modal('TB_closeWindowButton');
        });
    }

    function is_valid_locale(locale, valid_locale_list)
    {
        return jQuery.inArray(locale, valid_locale_list) !== -1
    }

    function get_post_or_taxonomy_row(id)
    {
        return jQuery('#post-' + id + ', #tag-' + id);
    }

    function get_string_group_row(id)
    {
        return jQuery('#string-select-' + id).closest('tr')
    }

    function documents_could_be_overwritten(element)
    {
        return jQuery(element).closest('tr').find('.dashicons-download-lingotek, .lingotek-professional-icon').length > 0;
    }

    function documents_could_be_overwritten_handler(event)
    {
        Workflow.modals.show_modal(workflow_vars.workflow_id + '-warning', 'Lingotek Professional Translation Services', 400, 800);
        jQuery(document).on('click', '#cancel-warning-' + workflow_vars.workflow_id + '-warning', function() {
            Workflow.modals.close_modal('TB_closeWindowButton');
        });

        jQuery(document).on('click', '#professional-warning-checkbox', function() {
            if (jQuery(this).prop('checked'))
            {
                enable_confirm_warning_button();
            }
            else 
            {
                disable_confirm_warning_button();
            }
        });

        var element = this;
        jQuery(document).on('click', '#ok-warning-' + workflow_vars.workflow_id + '-warning', function() {
            jQuery('#professional-warning-loading-spinner').show();
            window.location = Workflow.modals.replace_href(element);
        });
    }

    function enable_confirm_warning_button()
    {
        jQuery('#ok-warning-'+workflow_vars.workflow_id+'-warning').removeClass('professional-okay-warning-button-disabled');
        jQuery('#ok-warning-'+workflow_vars.workflow_id+'-warning').addClass('professional-okay-warning-button');
        jQuery('#professional-warning-okay-text').removeClass('ADD-PAYMENT-DISABLED').addClass('ADD-PAYMENT');
        jQuery('#ok-warning-'+workflow_vars.workflow_id+'-warning').prop('disabled', false);
    }

    function disable_confirm_warning_button()
    {
        jQuery('#ok-warning-'+workflow_vars.workflow_id+'-warning').removeClass('professional-okay-warning-button');
        jQuery('#ok-warning-'+workflow_vars.workflow_id+'-warning').addClass('professional-okay-warning-button-disabled');
        jQuery('#professional-warning-okay-text').removeClass('ADD-PAYMENT').addClass('ADD-PAYMENT-DISABLED');
        jQuery('#ok-warning-'+workflow_vars.workflow_id+'-warning').prop('disabled', true);
    }

    function disable_confirm_warning_checkbox()
    {
        jQuery('#professional-warning-checkbox').prop('checked', false);
    }

    function documents_could_be_overwritten_handler_off() 
    {
        jQuery(document).off('click', '#cancel-warning-' + workflow_vars.workflow_id + '-warning');
        jQuery(document).off('click', '#professional-warning-checkbox');
        jQuery(document).off('click', '#ok-warning-' + workflow_vars.workflow_id + '-warning');
    }

    function add_payment_method_handler()
    {
        payment_portal_loading_screen();
        global_timeout = setTimeout(function() {
                window.location = workflow_vars.bridge_payment_redirect + '?redirect_url=' + encodeURIComponent(window.location);
        },3000);
    }

    function terms_and_conditions_listener()
    {
        jQuery(document).on('click', '#terms-and-conditions-href', function() {
            show_terms_and_conditions();
        });
    }

    function terms_and_conditions_listener_off()
    {
        jQuery(document).off('click', '#terms-and-conditions-href');
        jQuery(document).off('click', '#close-terms-and-conditions');
            
    }

    function show_terms_and_conditions()
    {
        var ajax_url = Workflow.modals.get_ajax_url('get_ltk_terms_and_conditions');
        Workflow.modals.ajax_request('GET', ajax_url, {'_lingotek_nonce' : workflow_vars.nonce}, function(response) {
            jQuery('.terms-and-conditions-content').html(response.data);
            jQuery('.terms-and-conditions-content').find('ul').each(function (i, el) {
                jQuery(el).replaceWith('<ol>' + jQuery(el).html() + '</ol>');
            });
        });
        jQuery('#professional-table-content').hide();
        jQuery('#professional-terms-and-conditions').show();
        jQuery('#professional-terms-and-conditions').height(jQuery('#modal-body-'+ workflow_vars.workflow_id +'-request').height() - 50);

        jQuery(document).on('click', '#close-terms-and-conditions', function() {
            hide_terms_and_conditions();
        });
    }

    
    function set_up_side_panel_height()
    {
        jQuery('#sidebar').height(jQuery('#modal-body-'+ workflow_vars.workflow_id +'-request').height() - 50);
    }

    function hide_terms_and_conditions()
    {
        jQuery('#professional-table-content').show();
        jQuery('#professional-terms-and-conditions').hide();
    }

    function buy_now_listener()
    {
        jQuery(document).on('click', '#yes-'+ workflow_vars.workflow_id +'-request-buy-now', function() {
            buy_now_clicked = true;
            show_requesting_translation_screen();
            request_translations();
        });
    }

    function buy_now_listener_off()
    {
        jQuery(document).off('click', '#yes-'+ workflow_vars.workflow_id +'-request-buy-now');
    }

    function request_translations()
    {
        var translations = {};
        jQuery.each(document_id_list, function(index, value) {
            translations[value] = [];
        });
        jQuery('.checkable:checked').each(function(index, value) {
            var class_string = jQuery(this).attr('class');
            var doc_id = get_item_from_class(class_string, document_id_list);
            var locale = get_item_from_class(class_string, global_locale_list);
            if (jQuery.inArray(locale, translations[doc_id]) === -1)
            {
                translations[doc_id].push(locale);
            }
        });
        ajax_request_bulk_translation(translations);
    }

    function ajax_request_bulk_translation(translations)
    {
        var ajax_url = Workflow.modals.get_ajax_url('request_professional_translation');
        var total = jQuery('.lingotek-total-amount').html().slice(1).replace(',','');
        var request_translation_body = {
            'ids' : document_id_to_post_id,
            'translations' : translations,
            'workflow_id' : workflow_vars.workflow_id,
            'lingotek_locale_to_wp_locale' : get_lingotek_locale_to_wp_locale_list(),
            'type' : workflow_vars.curr_item_type,
            'summary': get_translation_summary(),
            'total_estimate': total,
            '_lingotek_nonce' : workflow_vars.nonce
        };
        var request = Workflow.modals.ajax_request('POST', ajax_url, request_translation_body, function(response) {
            if (response.data.transaction_approved)
            {
                show_display_invoice_screen_success(response);
            }
            else
            {
                show_error_screen();
            }
            global_ajax_requests_list.splice(global_ajax_requests_list.indexOf(request), 1);
        });
        global_ajax_requests_list.push(request);
    }

    function get_translation_summary()
    {
        var summary = [];
        jQuery('.translation-summary-list-text-ltk').each(function(index,value) {
            summary.push(jQuery(value).text());
        });
        return summary;
    }


    function accept_terms_and_conditions_listener()
    {
        jQuery(document).on('click', '#accept-terms-and-conditions-input', function() {
            if (jQuery(this).prop('checked'))
            {
                enable_buy_now_button();
            }
            else 
            {
                disable_buy_now_button();
            }
        });
    }

    function enable_buy_now_button()
    {
        jQuery('#yes-'+ workflow_vars.workflow_id +'-request-buy-now').removeClass('professional-action-button-disabled').addClass('professional-action-button');
        jQuery('#professional-buy-now').removeClass('ADD-PAYMENT-DISABLED').addClass('ADD-PAYMENT');
        jQuery('#yes-'+ workflow_vars.workflow_id +'-request-buy-now').prop('disabled', false);
    }

    function disable_buy_now_button()
    {
        jQuery('#yes-'+ workflow_vars.workflow_id +'-request-buy-now').addClass('professional-action-button-disabled').removeClass('professional-action-button');
        jQuery('#professional-buy-now').addClass('ADD-PAYMENT-DISABLED').removeClass('ADD-PAYMENT');
        jQuery('#yes-'+ workflow_vars.workflow_id +'-request-buy-now').prop('disabled', true);
        jQuery('#accept-terms-and-conditions-input').prop('checked', false);
    }

    function accept_terms_and_conditions_listener_off()
    {
        jQuery(document).off('click', '#accept-terms-and-conditions-input');
    }

    /**
     * Displays the first slide.
     * 
     */
    function show_requesting_translation_screen()
    {
        jQuery('#modal-body-'+workflow_vars.workflow_id+'-request').find('*').not('.requesting-element').hide();
        jQuery('.requesting-element').show();
    }

    function show_display_invoice_screen_success(response)
    {
        jQuery('#modal-body-'+workflow_vars.workflow_id+'-request').find('*').not('.professional-translation-request-success-element').hide();
        jQuery('#professional-translation-cost-success').html(escape_html( '$' + number_with_commas( response.data.total_price.toFixed(2) ) ));
        jQuery('.professional-translation-request-success-element').show();
    }

    function show_error_screen()
    {
        jQuery('#modal-body-'+workflow_vars.workflow_id+'-request').find('*').not('.professional-translation-request-error-element').hide();
        jQuery('.professional-translation-request-error-element').show();
    }

    function show_loading_error()
    {
        jQuery('#error-requesting-translation-ltk').text('There was an error requesting the price quotes.');
        jQuery('#error-requesting-translation-ltk-2').text('Please verify that your document exists on Lingotek TMS.').css('font-size','16px');
    }

    /**
     * Displays failure slide.
     * 
     */
    function showFailureSlide()
    {
        jQuery('#' + workflow_vars.workflow_id+ '-success').hide();
        jQuery('#' + workflow_vars.workflow_id+ '-first').hide();
    }

    /**
     * Displays success slide.
     * 
     */
    function showSuccessSlide()
    {
        jQuery('#' + workflow_vars.workflow_id+ '-failure').hide();
        jQuery('#' + workflow_vars.workflow_id+ '-first').hide();
    }

    jQuery(no + ',' + no_request).on('click', function() {
            Workflow.modals.close_modal('TB_closeWindowButton');
    });



    function start_progress_bar()
    {
        jQuery('.loading-progress-percent').html(escape_html('0%'));
        jQuery('.loading-progress-bar-inner').css('width', 0);
    }

    function update_progress_bar()
    {
        var finished = 0;
        var total = 0;

        for (var key in document_id_to_row_buffer) 
        {
            if (Object.prototype.hasOwnProperty.call(document_id_to_row_buffer, key)) 
            {
                var val = document_id_to_row_buffer[key];
                if (val)
                {
                    finished++;
                }
                total++;
            }
        }
        var percent = (finished / total) * 100;
        jQuery('.loading-progress-percent').html(escape_html(parseInt(percent) + '%'));
        jQuery('.loading-progress-bar-inner').css('width', percent + '%');
    }

    // /**
    //  * Launches a modal notifying the user of the chosen workflow.
    //  * 
    //  * @param {any} event 
    //  */
    // function upload_handler(event)
    // {
    //     event.preventDefault();
    //     Workflow.modals.stop_loading(workflow_vars.id);
    //     jQuery(this).addClass('thickbox');
    //     var url = Workflow.modals.replace_href(this);
    //     jQuery(yes).attr('href', url);
    //     jQuery(this).attr('href', '#TB_inline?width=800&height=300&inlineId=modal-window-id-' + workflow_vars.id);
    //     jQuery(yes).on('click', function() {
    //         Workflow.modals.loading(workflow_vars.id);
    //     });
    //     tb_show('<img src="' + workflow_vars.icon_url +'" style="padding-top:10px; display:inline-block"><span class="Upgrade-to-Lingotek header-professional">Upload Document To Lingotek</span>', '#TB_inline?width=800&height=300&inlineId=modal-window-id-' + workflow_vars.id);
    //     Workflow.modals.add_header_modal(workflow_vars.id);
    //      // tb_show('Upload Translation', '#TB_inline?width=500&height=300&inlineId=modal-window-id-' + workflow_vars.id);
    // }



    var click_bulk_action = false;

    /**
     * This is the event that is fired when a user clicks on the 'apply' button. It starts by checking that the option selected
     * is the bulk request option. Then it goes through each of the valid post ids and checks if that row has been checked. If it has
     * it looks to see if translation can be requested. If they can it pulls the essencial data off of the element and renders a row on the table.
     * 
     * When a bulk action begins the start_bulk lock is enabled and doesn't allow subsequent requests to be processed until the modal is destroyed (closed)
     * This prevents spam clicking errors.
     * 
     * @param {event} event 
     * @returns 
     */
    function bulk_request_handler(event)
    {
        if (jQuery('#bulk-action-selector-top').attr('value') === 'bulk-lingotek-request' 
            || jQuery('#bulk-action-selector-bottom').attr('value') === 'bulk-lingotek-request')
        {

            /**
             * If a bulk action has already been initiated we want to abort this request.
             */
            if (start_bulk) { return; }
            var ajax_request_made = false;

            jQuery.each(item_ids.ids, function(item_id, valid_locales) {

                var row_checked;
                var row;
                if (workflow_vars.curr_item_type === 'string')
                {
                    row = get_string_group_row(item_id);
                    row_checked = jQuery('#string-select-' + item_id).prop('checked');
                }
                else
                {
                    row = get_post_or_taxonomy_row(item_id);
                    row_checked = get_post_or_taxonomy_row(item_id).find('#cb-select-' + item_id).prop('checked');
                }


                if (row_checked) // If the row has been selected.
                {
                    if (row_has_professional_translatable_items(row, item_id)) // If translations can be requested.
                    {
                        event.preventDefault();
                        /**
                         * Lock the operation.
                         */
                        start_bulk = true;
                        click_bulk_action = true;

                        /**
                         * Loading gif.
                         */
                        Workflow.modals.loading(workflow_vars.workflow_id+ '-request');
                        start_progress_bar();
                        var locale_list = [];
                        var locale_list_string = '';
                        var url = '';

                        /**
                         * We go through each 'requestable' locale and build a locale array and locale string.
                         */
                        row.find('.dashicons-plus-lingotek').each(function(index, element) {
                            url = (jQuery(element).attr('url')) ? jQuery(element).attr('url') : jQuery(element).attr('href');
                            var locale = Workflow.modals.get_url_parameter('locale', url);
                            if (is_valid_locale(locale, valid_locales))
                            {
                                Workflow.modals.replace_href(element);
                                var locale_code = workflow_vars.enabled_langs[locale].lingotek_locale;
                                if (jQuery.inArray(locale_code, locale_list) === -1)
                                {
                                    locale_list_string += locale_code + ',';
                                    locale_list.push(locale_code);
                                }
                            }
                        });

                        /**
                         * request
                         * If it's a valid url we render a row on the table.
                         */
                        if (url && url.length > 0)
                        {
                            /**
                             * Unlock the ajaxStop listener.
                             */
                            ajax_request_made = true;
                            var doc_id = Workflow.modals.get_url_parameter('document_id', url);
                            add_to_row_buffer(doc_id, locale_list_string, false, locale_list, true);
                        }

                        
                    }
                }
                if (workflow_vars.curr_item_type === 'string')
                {
                    jQuery('#string-select-' + item_id).prop('checked', false);
                }
                else
                {
                    get_post_or_taxonomy_row(item_id).find('#cb-select-' + item_id).prop('checked', false);
                }
            });

            /**
             * This ensures that the ajaxStop event is enabled only if an ajax request has been made. Otherwise the events will
             * pile up and the modal will be really slow when updating itself.
             */
            if (ajax_request_made)
            {
                set_payment_information();
                jQuery(document).ajaxStop(function() {

                    /**
                     * This checks if ajax calls have been aborted. If they have this event will still fire because technically ajaxRequests have stopped
                     * but we don't want to attach any listeners or render anything if the modal has been closed.
                     */
                    if (abort_ajax) { return; }
                    try
                    {
                        render_rows_from_buffer();
                        var glob_list_copy = global_locale_list.slice(0);
                        render_table_headers(false, glob_list_copy, true);

                        /**
                         * Hides the loading gif and shows everything but the hidden items on the table.
                         */
                        Workflow.modals.stop_loading(workflow_vars.workflow_id+ '-request', loading_exceptions);
                        init_table();
                    }
                    catch (err)
                    {
                        Workflow.modals.stop_loading(workflow_vars.workflow_id+ '-request', loading_exceptions);
                        show_error_screen();
                        show_loading_error();
                    }
                    jQuery(document).off('ajaxStop');
                });
            }
        }
    }

    function row_has_professional_translatable_items(jQueryObjectRow, item_id)
    {
        var has_translatable_items = false;
        jQueryObjectRow.find('.dashicons-plus-lingotek').each(function(index, element) {
            var url = jQuery(element).attr('url');
            var locale = Workflow.modals.get_url_parameter('locale', url);
            if (is_valid_locale(locale, item_ids.ids[item_id]))
            {
                has_translatable_items = true;
            }
        });
        return has_translatable_items;
    }


    function bulk_upload_handler(event)
    {
        if (jQuery('#bulk-action-selector-top').attr('value') === 'bulk-lingotek-upload' 
            || jQuery('#bulk-action-selector-bottom').attr('value') === 'bulk-lingotek-upload')
        {
            jQuery.each(item_ids.ids, function(id, lang) {
                if (jQuery('#post-' + id + ', #tag-' + id).find('#cb-select-' + id).prop('checked'))
                {
                    if (jQuery('#post-' + id + ', #tag-' + id).find('.dashicons-download-lingotek, .lingotek-professional-icon').length > 0)
                    {
                        jQuery('#post-' + id + ', #tag-' + id).find('#cb-select-' + id).prop('checked', false);
                    }
                }
            });
        }
    }

    

    /**
     * This method renders all rows that are in the buffer.
     * The buffer is cleared everytime the modal is closed.
     * 
     */
    function render_rows_from_buffer()
    {
        jQuery.each(document_id_to_row_buffer, function(doc_id, object) {
            if (object)
            {
                render_table_row(object.response, object.specific_locale, object.locale_list, object.response.data.document_id, object.check_all);
            }
        });
    }

    function set_payment_information()
    {
        var ajax_url = Workflow.modals.get_ajax_url('get_user_payment_information');

        var request = Workflow.modals.ajax_request('GET', ajax_url, {'_lingotek_nonce' : workflow_vars.nonce}, function(response) {
            if (response.payment_info && response.payment_info.payment_profile)
            {
                 show_does_have_payment_info(response);
            }
            else
            {
                show_doesnt_have_payment_info();
            }
            // set up stuff here
            global_ajax_requests_list.splice(global_ajax_requests_list.indexOf(request), 1);
        });

        global_ajax_requests_list.push(request);
    }

    function show_does_have_payment_info(response)
    {
        jQuery('.payment-not-setup').hide();
        jQuery('.payment-method-setup').show();
        // jQuery('#blue-radio-button').attr('src', workflow_vars.blue_radio_button_url);
        // jQuery('#credit-card-dots').attr('src', workflow_vars.credit_dots_url);
        jQuery('#last-four-digits').html(escape_html( response.payment_info.payment_profile.cc.split('X').join('') ));
        jQuery('#credit-card-image').attr('src', get_cc_type_asset_url(response.payment_info.payment_profile.cc_type));
        jQuery('.header-professional').html(escape_html( 'Lingotek Professional Translation Services' ));
    }

    function get_cc_type_asset_url(cc_type)
    {
        return workflow_vars.cc_type_map[ cc_type ] 
            ? workflow_vars.cc_type_map[ cc_type ] 
            : workflow_vars.cc_type_map[ workflow_vars.default_cc_type ];
    }

    function show_doesnt_have_payment_info()
    {
        jQuery('.payment-not-setup').show();
        jQuery('.payment-method-setup').hide();
        jQuery('.header-professional').html(escape_html( 'Lingotek Professional Translation Quote Calculator' ));
    }

    function payment_portal_loading_screen()
    {
        jQuery('#TB_ajaxContent').find('*').not('.payment-portal-element').hide();
        jQuery('.payment-portal-element').show();
    }

    function payment_portal_loading_screen_off()
    {
        jQuery('#TB_ajaxContent').find('*').not('.payment-portal-element').hide();
        jQuery('.payment-portal-element').show();
    }


    /**
     * This function is shared between the bulk request and single request user actions. It requires a document_id
     * to send to bridge to get info about it. It also requires a comma separated string with the locales it would like to know about as far
     * as cost goes, finally it takes in a locale list of locales that have been enabled for that row (profile)
     * 
     * @param {string} document_id 
     * @param {string} comma_separated_locales 
     * @param {array} locale_list 
     * @param {boolean} [check_all=false] 
     */
    function add_to_row_buffer(document_id, comma_separated_locales, specific_locale, locale_list, check_all = false)
    {
        document_id_to_row_buffer[document_id] = false;

        var estimate_body = {
            'document_id' : document_id,
            'locale' : comma_separated_locales,
            '_lingotek_nonce' : workflow_vars.nonce
        };

        var estimate_ajax_url = Workflow.modals.get_ajax_url('estimate_cost');

        /**
         * We store the ajax_request in a list as they are sent off. When they return they remove themselves from the list after they complete their execution.
         * We do this because if there are left over ajax requests after the modal is closed we don't want them manipulating the modal. All left over ajax requests
         * are aborted as the modal is closed.
         */
        var request = Workflow.modals.ajax_request('GET', estimate_ajax_url, estimate_body, function(response) {

            document_id_to_row_buffer[response.data.document_id] = {
                'response' : response,
                'specific_locale' : specific_locale,
                'locale_list' : locale_list,
                'check_all' : check_all
            };
            global_ajax_requests_list.splice(global_ajax_requests_list.indexOf(request), 1);
            update_progress_bar();
        });
        global_ajax_requests_list.push(request);
    }


    /**
     * Launches the modal that displays the cost of a single document. Allows the user to request translation.
     * 
     * @param {any} event 
     */
    function request_handler(event)
    {
        event.preventDefault();

        // var _this = this;
        var url = Workflow.modals.replace_href(this);
        // var doc_id = Workflow.modals.get_url_parameter('document_id', url);

        /**
         * We store the clicked locale because we want to display this first in the list so that the user doesn't have to click in order
         * to see that it is selected.
         */
        var clicked_locale_code = workflow_vars.enabled_langs[Workflow.modals.get_url_parameter('locale', url)].lingotek_locale;

        render_post_row(this, clicked_locale_code);
    }

    /**
     * When the Request translation row action is selected the render_post_method_with_locales method is delegated
     * with the options to:
     * 1. Not care about any specific row ordering.
     * 2. Check all of the header boxes.
     * 3. Check all of the row boxes.
     * 
     * @param {event} event 
     */
    function request_translation_row_handler(event)
    {
        event.preventDefault();
        render_post_row(jQuery(this).children()[0], false, true, true);
    }

    /**
     * This method renders a table with a single row. This is used for requesting a single translation and requesting translations
     * for an entire row. It requires the caller to pass in the element that contains the url. It requires the user to specify whether
     * there is a specific locale ordering. It allows the caller to decide whether all headers and / or all rows should be checked.
     * 
     * @param {object} element 
     * @param {string || boolean} clicked_locale_code 
     * @param {boolean} [check_all_headers=false] 
     * @param {boolean} [check_all_rows=false] 
     */
    function render_post_row(element, clicked_locale_code, check_all_headers = false, check_all_rows = false)
    {
        var url = Workflow.modals.replace_href(element);
        var doc_id = Workflow.modals.get_url_parameter('document_id', url);

        var locale_data = get_row_locale_data(jQuery(element), clicked_locale_code);
        /**
         * Attach out listeners.
         */

        Workflow.modals.loading(workflow_vars.workflow_id+ '-request');
        start_progress_bar();

        /**
         * The parameters we pass to the render table row method indicates that we care about which item is displayed first and that
         * we DON'T want every box checked.
         */
        add_to_row_buffer(doc_id, locale_data.csv, clicked_locale_code, locale_data.locale_list, check_all_rows);
        set_payment_information();
        jQuery(document).ajaxStop(function() {
            try
            {
                render_rows_from_buffer();

                /**
                 * Again, this method call indicates that we want the clicked locale to be displayed first in the header.
                 */
                var glob_list_copy = global_locale_list.slice(0);
                render_table_headers(clicked_locale_code, glob_list_copy, check_all_headers);
                Workflow.modals.stop_loading(workflow_vars.workflow_id+ '-request', loading_exceptions);


                init_table();
            }
            catch(err)
            {
                Workflow.modals.stop_loading(workflow_vars.workflow_id+ '-request', loading_exceptions);
                show_error_screen();
                show_loading_error();
            }
            
            jQuery(document).off('ajaxStop');
        });
    }

    /**
     * This method is used to find all of the locales on a given row. Because we want to grab all of the valid locales on a given
     * row we need to go up a few levels until we find the current row ('tr'), then we can find all of the .dashicons-plus elements 
     * and extract their locale codes from their hrefs.
     * 
     * @param {object} jQueryObject 
     * @param {string} clicked_locale_code 
     * @returns 
     */
    function get_row_locale_data(jQueryObject, clicked_locale_code)
    {
        var locale_list = [];
        var comma_separated_locales = '';
        if (clicked_locale_code)
        {
            comma_separated_locales += clicked_locale_code + ',';
            locale_list.push(clicked_locale_code);
        }
        var item_id = get_row_item_id(jQueryObject);
        jQueryObject.closest('tr').find('.dashicons-plus').each(function(index, elem) {
            var url = jQuery(elem).attr('url');
            var locale = Workflow.modals.get_url_parameter('locale', url);
            if (is_valid_locale(locale, item_ids.ids[item_id]))
            {
                Workflow.modals.replace_href(elem);
                var locale_code = workflow_vars.enabled_langs[locale].lingotek_locale;
                if (clicked_locale_code !== locale_code)
                {
                    comma_separated_locales += locale_code + ',';
                    locale_list.push(locale_code);
                }
            }
        });

        return {
            'csv' : comma_separated_locales,
            'locale_list' : locale_list
        };
    }

    function get_row_item_id(jQueryObject)
    {
        if (workflow_vars.curr_item_type === 'string')
        {
            return jQueryObject.closest('tr').find('input[id*="string-select-"]').attr('id').split('-')[2];
        }
        else
        {
            return jQueryObject.closest('tr').attr('id').split('-')[1];;
        }
    }

    function close()
    {
        Workflow.modals.close_modal('TB_closeWindowButton');
    }

    function init_table()
    {
        terms_and_conditions_listener();
        accept_terms_and_conditions_listener();
        buy_now_listener();
        listen_for_language_cycle_click();
        attach_row_total_listeners();
        attach_table_change_listener();
        attach_header_listeners();
        update_table();
        update_row_totals();
        hide_terms_and_conditions();
        set_up_side_panel_height();
    }

    var document_id_list = [];
    var active_locale_list = [];

    /**
     * This value indicates how many columns will be shown on the table at one time.
     */
    var table_element_limit = 3;

    /**
     * These two variables are how we track what has already been shown on the modal. When a header or column has 
     * been displayed on the modal it is added to this list. After all headers and columns have been shown or the modal is destroyed
     * these lists is clear.
     */
    var shown_headers = [];
    var shown_columns = {};

    /**
     * This attaches an event listener to the button that allows users to click through different languages.
     * 
     */
    function listen_for_language_cycle_click() 
    {
        if (can_cycle_langauges())
        {
            remove_extra_columns();
            jQuery('#next-language-set').on('click', function() { 
                remove_extra_columns();
                cycle_through_headers();
                jQuery.each(document_id_list, function(index, value) {
                    cycle_through_columns(value);
                });
            });
        }
        else
        {
            jQuery('#next-language-set').hide();
        }
    }

    /**
     * Columns are added as padding for when the number of columns left to display (the ones that haven't been shown yet) is 
     * less than the number of elements that are meant to be shown after each click. These extra columns are removed after the button
     * is clicked.
     */
    function remove_extra_columns()
    {
        jQuery('.extra-column').remove();
    }

    /**
     * Extra padding columns are added to the table if there aren't enough elements left to show on the current view of the modal.
     * 
     * For example: If the user has 5 languages enabled and the modal is currently only displaying 3, when the user clicks on the 'more' button
     * to see the rest of their languages there will only be two left to show. To avoid the table changing column length and moving things around annoyingly
     * an extra column is added as padding.
     * 
     * @param {object} jQueryObject 
     */
    function add_extra_columns(jQueryObject)
    {
        if (jQueryObject.length < table_element_limit)
        {
            var extra_columns = table_element_limit - jQueryObject.length;
            for (var i = 0; i < extra_columns; i++)
            jQueryObject.last().after(jQuery.parseHTML("<td class='extra-column'></td>"));
        }
    }

    /**
     * This is the method responsible for hiding and showing different columns in order to allow the user to 'click' through their enabled languages.
     * It takes in a document_id and finds the row associated with that id. Then it goes through each entry in that row and determines whether to hide it or show it.
     * It is important to note that there is a global list that maps document ids to table elements in order to keep track of which items have already been
     * shown to the user. This method only acts on one row at a time.
     * 
     * @param {string} document_id 
     */
    function cycle_through_columns(document_id)
    {
        var counter = 0;
        jQuery('.' + document_id + '-row-table').each(function(index, val) {
            if (! jQuery(val).hasClass('row-table-hidden'))
            {
                jQuery(val).addClass('row-table-hidden');
            }
            else if (jQuery(val).hasClass('row-table-hidden'))
            {
                if (counter < table_element_limit && jQuery.inArray(val, shown_columns[document_id]) === -1) 
                {
                    jQuery(val).removeClass('row-table-hidden');
                    shown_columns[document_id].push(val);
                    counter++;
                }
            }
        });
        if (jQuery('.' + document_id + '-row-table').length <= shown_columns[document_id].length)
        {
            shown_columns[document_id] = [];
        }

        add_extra_columns(jQuery('.' + document_id + '-row-table').not('.row-table-hidden'));
    }

    /**
     * This method is responsible for shuffling through the headers (language names) as the user clicks 
     * through the table. Like the cycle_though_columns() method there is a global list keeping track of which
     * headers have already been seen.
     * 
     */
    function cycle_through_headers()
    {
        var counter = 0;
        jQuery('.header-table, .header-table-hidden').each(function(index, val) {
            if (jQuery(val).hasClass('header-table'))
            {
                jQuery(val).removeClass('header-table').addClass('header-table-hidden');
            }
            else if (jQuery(val).hasClass('header-table-hidden'))
            {
                if (counter < table_element_limit && jQuery.inArray(val, shown_headers) === -1)
                {
                    jQuery(val).removeClass('header-table-hidden').addClass('header-table');
                    shown_headers.push(val);
                    counter++;
                }
            }
        });
        if (jQuery('.header-table, .header-table-hidden').length <= shown_headers.length)
        {
            shown_headers = [];
        }

        add_extra_columns(jQuery('.header-table'));
    }

    function can_cycle_langauges()
    {
        return jQuery('.header-table, .header-table-hidden').length > table_element_limit;
    }
    

    /**
     * This is the method that renders the table in the modal. It embeds a bit of tracking information into each html element
     * in order to keep track of clicks in order to update the 'total' fields on the table dynamically.
     * 
     * @param {obj} response 
     * @param {string} locale 
     * @param {array} locales 
     * @param {string} document_id 
     * 
     */
    function render_table_row(response, specific_locale_code, locales, document_id, check_all_on_row = false)
    {
        if (!response.success) throw response;

        if (jQuery.inArray(document_id, document_id_list) === -1) { document_id_list.push(document_id); } // keep track of our document ids. 

        var glob_locale_copy = global_locale_list.slice(0);

        var element = "<tr class='bordered-bottom request-table-item'><td class='document-title' title='"+ escape_html(response.data.document_title) +"'>"+ escape_html(response.data.document_title) +"</td><td class='word-count words-"+ escape_html(document_id) +"'>"+ escape_html(response.data.word_count) +"</td>";
        var row_total = 0;
        var counter = 0;
        var iteration_length = global_locale_list.length;

        /**
         * This method supports rendering a row starting with a certain locale code. If that variable has been set then we will use it to render the first row.
         */
        if (specific_locale_code)
        {
            if (response.data.costs[specific_locale_code] !== false)
            {
                element += "<td class='"+ escape_html(document_id) +"-row-table cost-font-size'><input class='"+ escape_html(specific_locale_code) +" checkable "+ escape_html(document_id) +"' type='checkbox' name='confirm-request' value='"+ escape_html(response.data.costs[specific_locale_code].estimated_cost) +"'>$"+ escape_html(response.data.costs[specific_locale_code].estimated_cost) +"</td>";
                row_total = parseFloat(response.data.costs[specific_locale_code].estimated_cost.replace(',', ''));
            }
            else
            {
                element += "<td class='"+ escape_html(document_id) +"-row-table cost-font-size "+ escape_html(display_class) +"'><span ltk-data-tooltip-ctr='This element is unavailable for translation ...'><input type='checkbox' disabled></span></td>";
            }
            glob_locale_copy.splice(glob_locale_copy.indexOf(specific_locale_code), 1);
            counter = 1;
            iteration_length--;
        }


        for (var i = 0; i < iteration_length; i++)
        {
            var next_local = get_next_locale(glob_locale_copy);
            var display_class = '';

            /**
             * If we are already showing the max number of columns we will still render the rest of the columns but we will hide them.
             */
            if (counter >= table_element_limit) { display_class = 'row-table-hidden'; }

            /**
             * The next_locale variable is coming from our global list of locales. We are checking to see if that locale is contained in the locales list that was 
             * passed into the method. The locale list passed into the method contains the languages that have been enabled for this particular document.
             * If the next_locale variable is not found in our enabled locales list then we add a row that doesn't contain any sort of input.
             */
            if (next_local && jQuery.inArray(next_local, locales) !== -1 && response.data.costs[next_local] !== false)
            {
                element += "<td class='"+ escape_html(document_id) +"-row-table cost-font-size "+ escape_html(display_class) +"'><input class='"+ escape_html(next_local) +" checkable "+ escape_html(document_id) +"' type='checkbox' name='confirm-request' value='"+ escape_html(response.data.costs[next_local].estimated_cost) +"'>$"+ escape_html(response.data.costs[next_local].estimated_cost) +"</td>"
            }
            else 
            {
                element += "<td class='"+ escape_html(document_id) +"-row-table cost-font-size "+ escape_html(display_class) +"'><span ltk-data-tooltip-ctr='This element is unavailable for translation ...'><input type='checkbox' disabled></span></td>";
            }
            counter++;
        }
        element += "<td></td><td class='invisible row-total-" + escape_html(document_id) + " row-total'>$"+ escape_html(row_total.toFixed(2)) +"</td></tr>"; // Add the row total.

        jQuery('.request-table > tbody').append(jQuery.parseHTML(element)); // Add the element to our document.

        /**
         * This method supports the option to check all of the items on this particular row.
         */
        if (check_all_on_row)
        {
            jQuery('.' + document_id).prop('checked', true);
        }

        /**
         * If a specific locale code is passed in then that row and header is checked.
         */
        if (specific_locale_code)
        {
            jQuery('.' + specific_locale_code + ', .' + specific_locale_code + '-header').prop('checked', true);
        }
        

        /**
         * Here we keep track of which comments are being shown so that we don't show them again until all columns have been seen at least once.
         */
        shown_columns[document_id] = [];
        jQuery('.' + document_id + '-row-table').not('.row-table-hidden').each(function(index, value) {
            shown_columns[document_id].push(value);
        });
    }

    /**
     * Renders the table header.
     * 
     * @param {string} locale_code 
     * @param {array} locales 
     */
    function render_table_headers(specific_locale_code, locales, check_all_headers = false)
    {
        var header = '';
        var counter = 0;

        /**
         * This method - like render_table_row - supports the option to start the header with a specific locale.
         */
        if (specific_locale_code)
        {
            locales.splice(locales.indexOf(specific_locale_code), 1);
            var language = escape_html(get_language_from_locale(specific_locale_code));
            var country = escape_html(get_country_from_locale(specific_locale_code));
            var header_text = get_lang_with_country_string(language, country, specific_locale_code);
            header += "<th class='appended header-table' title='" + header_text + "'><div class='document-title language-header'>"+header_text+"</div><input class='"+ escape_html(specific_locale_code) +"-header' type='checkbox' name='confirm-request' value='request-translation'><span class='"+escape_html(specific_locale_code)+"-header-cost cost-header'>" + escape_html('$40.99') + "</span></th>";
            counter = 1;
        }
         
        var next_local;
        while (next_local = get_next_locale(locales))
        {
            var display_class = '';
            if (counter >= table_element_limit) { display_class = '-hidden'; }
            var language = escape_html(get_language_from_locale(next_local));
            var country = escape_html(get_country_from_locale(next_local));
            var header_text = get_lang_with_country_string(language, country, next_local);
            header += "<th class='appended header-table"+ escape_html(display_class) +"' title='"+ header_text +"'><div class='document-title language-header'>"+header_text+"</div><input class='"+ escape_html(next_local) +"-header' type='checkbox' name='confirm-request' value='request-translation'><span class='"+escape_html(next_local)+"-header-cost cost-header'>" + escape_html('$40.99') + "</span></th>";
            counter++;
        }

        /**
         * Any left over appended columns are removed. This prevents left over data from being present on the modal after it is closed.
         */
        jQuery('.appended').remove();


        jQuery('#words-column').after(jQuery.parseHTML(header));

        /**
         * This method supports the option to mark all of the headers as checked.
         */
        if (check_all_headers)
        {
            jQuery.each(global_locale_list, function(index, value) {
                if (jQuery('.' + value).length > 0)
                jQuery('.' + value + '-header').prop('checked', true);
            });
        }

        /**
         * Check the header if a specific locale was passed in.
         */
        if (specific_locale_code)
        {
            jQuery('.' + specific_locale_code + '-header').prop('checked', true);
        }

        /**
         * Keeping track of which headers are currently being shown.
         */
        jQuery('.header-table').each(function(index, value) {
            shown_headers.push(value);
        });
    }

    /**
     * Sets the checked property of a given element.
     * 
     * @param {string} element_class 
     * @param {boolean} isChecked 
     */
    function set_input_check(element_class, isChecked)
    {
        jQuery(element_class).prop('checked', isChecked);
    }

    /**
     * Attaches click listeners to the headers of the table. When the header checkboxes are
     * clicked, the table is updated.
     * 
     */
    function attach_header_listeners()
    {
        jQuery.each(global_locale_list, function(index,value) {
            jQuery(document).on('click', '.' + value + '-header', function() {
                set_input_check('.' + value, jQuery('.' + value + '-header').prop('checked'));
                update_table();
                update_row_totals();
            });
        });
    }

    /**
     * Updates the row total amount when an element is clicked.
     * 
     */
    function attach_row_total_listeners()
    {
        // TODO: optimize row listener
        jQuery.each(document_id_list, function(index,value) {
            jQuery(document).on('click', '.' + value, function() {
                update_row_total(value);
            })
        });
    }

    /**
     * Updates a row total amount based on boxes that have been checked.
     * 
     * @param {string} document_id 
     */
    function update_row_total(document_id)
    {
        var total = 0.0;
        jQuery('.' + document_id + ':checked').each(function(key, item) {
            total += parseFloat(jQuery(item).attr('value').replace(',', ''));
        });
        jQuery('.row-total-' + document_id).html(escape_html( '$' + total.toFixed(2) ));
    }

    /**
     * Updates all row totals.
     * 
     */
    function update_row_totals()
    {
        jQuery.each(document_id_list, function(index, value) {
            update_row_total(value);
        });
    }

    /**
     * Updates the word count on the modal.
     * 
     */
    // function update_word_count()
    // {
    //     var total = 0.0;
    //     jQuery.each(document_id_list, function(index,value) {
    //         var multiplier = jQuery('.' + value + ':checked').length;
    //         var doc_word_count = parseInt(jQuery('.words-' + value).html());
    //         total += multiplier * doc_word_count;
    //     });
    //     var word_string = total === 1 ? ' Word Translated' : ' Words Translated';
    //     jQuery('#number-of-words').html(escape_html( total + word_string ));
    // }

    /**
     * Updates the page count on the modal.
     * 
     */
    // function update_page_count()
    // {
    //     var page_number = 0;
    //     jQuery.each(document_id_list, function(index,value) {
    //         if (jQuery('.' + value + ':checked').length > 0)
    //         {
    //             ++page_number;
    //         }
    //     });
    //     var page_string = page_number === 1 ? ' Post' : ' Posts';
    //     jQuery('#number-of-pages').html(escape_html( page_number + page_string ));
    // }

    /**
     * Updates the table when a 'checkable' element has been checked.
     * 
     */
    function attach_table_change_listener()
    {
        jQuery(document).on('click', '.checkable', function() {
            if (! jQuery(this).prop('checked')) 
            {
                var locale_code = get_item_from_class(jQuery(this).attr('class'), global_locale_list);
                set_input_check('.' + locale_code + '-header', false); 
            }
            update_table();
        })
    }

    /**
     * Retrieves the locale code from an element class.
     * 
     * @param {string} class_string 
     * @returns 
     */
    function get_item_from_class(class_string, list)
    {
        var arr = class_string.split(' ');
        for (var i = 0; i < arr.length; i++)
        {
            var value = arr[i];
            if (jQuery.inArray(value, list) !== -1)
            {
                return value;
            }
        }
    }

    /**
     * Updates the total cost element based on which boxes have been checked.
     */
    function update_total_cost()
    {
        var total_cost = 0.00;
        jQuery.each(workflow_vars.enabled_langs, function(index, item) {
            total_cost += get_cost_by_locale(item.lingotek_locale, false);
        });
        jQuery('.lingotek-total-amount').html(escape_html( '$' + number_with_commas(total_cost.toFixed(2))));
    }

    /**
     * Updates the "Translated into: " {list of languages} portion of the modal.
     * 
     */
    // function update_translated_into()
    // {
    //     var locale_list = [];
    //     var translated_into = '';
    //     jQuery('.checkable:checked').each(function(key, item) {
    //         var locale_code = get_item_from_class(jQuery(item).attr('class'), global_locale_list);
    //         if (jQuery.inArray(locale_code, locale_list) === -1) 
    //         { 
    //             locale_list.push(locale_code); 
    //             translated_into += get_language_from_locale(locale_code) + ', ';
    //         }
    //     });

    //     var string = '';
    //     if (locale_list.length > 0)
    //     {
    //         string = 'Translated into: ' + translated_into.slice(0,-2) + '.';
    //     }
    //     else 
    //     {
    //         string = 'No translations selected!'
    //     }
    //     jQuery('#translated-into').html(escape_html( string ));
    // }

    function update_request_summary()
    {
        jQuery('.translation-summary-list-ltk').empty();
        var list = get_request_summary_list();
        jQuery.each(list, function(index, item) {
            var css_class = get_cost_by_locale(item.locale) < MINIMUM ? 'minimum-warning' : '';
            var minimum_star = get_cost_by_locale(item.locale) < MINIMUM ? '*' : '';
            var element = "<li><span class='translation-summary-list-text translation-summary-list-text-ltk'>"+item.name + ': ' + item.count + ' words ' + "<span class='translation-summary-cost "+css_class+"'>$"+ number_with_commas(item.cost.toFixed(2)) + minimum_star +"</span></li>";
            jQuery('.translation-summary-list-ltk').append(element);
        });
    }

    function get_request_summary_list()
    {
        var list = [];
        jQuery.each(workflow_vars.enabled_langs, function(index, item) {
            if (jQuery('.' + item.lingotek_locale + ':checked').length > 0)
            {
                list.push({
                    'locale' : item.lingotek_locale,
                    'name' : get_lang_with_country_string(item.language, item.country_name, item.lingotek_locale),
                    'count' : get_word_count_by_locale(item.lingotek_locale),
                    'cost' : get_cost_by_locale(item.lingotek_locale, false)
                });
            }
        });

        return list;
    }
    function get_lang_with_country_string(language, country, locale)
    {
        return (language == null || country == null) || (language == 'null' || country == 'null') ? locale : language + ' (' + country + ')';
    }

    function get_word_count_by_locale(locale)
    {
        var word_count = 0;
        jQuery('.' + locale).each(function(index, item) {
            if (!jQuery(item).prop('checked')) { return; }
            var doc_id = get_item_from_class(jQuery(item).attr('class'), document_id_list);
            word_count += parseInt(jQuery('.words-' + doc_id).html());
        });
        return word_count;
    }

    function get_cost_by_locale(locale, raw = true)
    {
        var cost = 0.0;
        jQuery('.' + locale).each(function(index, item) {
            if (jQuery(item).prop('checked'))
            {
                cost += parseFloat(jQuery(item).val().replace(/,/g, ''));
            }
        });

        if (!raw && cost > 0.0) 
        {
            cost = cost < MINIMUM ? MINIMUM : cost;
        }
        return cost;
    }

    function update_estimated_delivery()
    {
        var max_word_count = 0;
        jQuery.each(workflow_vars.enabled_langs, function(index, item) {
            var word_count = get_word_count_by_locale(item.lingotek_locale);
            if (word_count > max_word_count) { max_word_count = word_count; }
        });
        var additional_days = parseInt(max_word_count / DELIVERY_WORDS_INCREASE);
        var min_delivery = DEFAULT_MINIMUM_DELIVERY_DAYS + additional_days;
        var max_delivery = DEFAULT_MAXIMUM_DELIVERY_DAYS + additional_days;
        jQuery('.business-days').html(min_delivery + ' - ' + max_delivery + ' business days.');
    }

    function update_header_values()
    {
        jQuery.each(workflow_vars.enabled_langs, function(index, item) {
            var cost = get_cost_by_locale(item.lingotek_locale);
            var not_meeting_minimum = cost < MINIMUM && cost > 0;
            var html_cost = '$' + number_with_commas(cost.toFixed(2));

            if (not_meeting_minimum)
            {
                html_cost += '*';
            }

            jQuery('.' + item.lingotek_locale + '-header-cost').html(html_cost);

            if (not_meeting_minimum)
            {
                jQuery('.' + item.lingotek_locale + '-header-cost').addClass('minimum-warning');
            }
            else
            {
                jQuery('.' + item.lingotek_locale + '-header-cost').removeClass('minimum-warning');
            }
        });
    }

    function check_minimums()
    {
        var mins_met = true;
        jQuery.each(workflow_vars.enabled_langs, function(index, item) {
            var cost = get_cost_by_locale(item.lingotek_locale);
            mins_met = mins_met && (cost >= MINIMUM || cost === 0);
        });
        if (mins_met)
        {
            jQuery('.minimum-per-language-warning').css('visibility','hidden');
        }
        else
        {
            jQuery('.minimum-per-language-warning').css('visibility','visible');
        }
    }

    /**
     * This method takes in a locale (one of the locales embedded into the icon href) and returns the english translated of that 
     * langauge
     * 
     * @param {string} locale 
     * @returns 
     */
    function get_language_from_locale(locale)
    {
        for (var key in workflow_vars.enabled_langs) 
        {
            if (Object.prototype.hasOwnProperty.call(workflow_vars.enabled_langs, key)) 
            {
                var val = workflow_vars.enabled_langs[key];
                if (val.lingotek_locale === locale)
                {
                    return val.language;
                }
            }
        }
    }

    function get_country_from_locale(locale) 
    {
        for (var key in workflow_vars.enabled_langs) 
        {
            if (Object.prototype.hasOwnProperty.call(workflow_vars.enabled_langs, key)) 
            {
                var val = workflow_vars.enabled_langs[key];
                if (val.lingotek_locale === locale)
                {
                    return val.country_name;
                }
            }
        }
    }

    function get_wp_locale_from_lingotek_locale(locale)
    {
        for (var key in workflow_vars.enabled_langs) 
        {
            if (Object.prototype.hasOwnProperty.call(workflow_vars.enabled_langs, key)) 
            {
                var val = workflow_vars.enabled_langs[key];
                if (val.lingotek_locale === locale)
                {
                    return key;
                }
            }
        }
    }

    function get_lingotek_locale_to_wp_locale_list()
    {
        var list = {};
        for (var key in workflow_vars.enabled_langs) 
        {
            if (Object.prototype.hasOwnProperty.call(workflow_vars.enabled_langs, key)) 
            {
                var val = workflow_vars.enabled_langs[key];
                list[val.lingotek_locale] = key;
            }
        }
        return list;
    }

    /**
     * Delegates this call to several updater methods.
     * 
     */
    function update_table()
    {
        // update_word_count();
        update_total_cost();
        update_request_summary();
        update_estimated_delivery();
        update_header_values();
        check_minimums();
        // update_page_count();
        // update_translated_into();
    }

    /**
     * Cycles through a list of locales and gives one back while removing it from the list.
     * 
     * @param {array} locales 
     * @returns 
     */
    function get_next_locale(locales)
    {
        if (locales.length > 0)
        {
            var locale = locales[0];
            locales.splice(0,1);
            return locale;
        }
        else
        {
            return false;
        }
    }

    var entityMap = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
        '/': '&#x2F;',
        '`': '&#x60;',
        '=': '&#x3D;'
    };

    function escape_html (string) 
    {
        return String(string).replace(/[&<>"'`=\/]/g, function (s) {
            return entityMap[s];
        });
    }

    function number_with_commas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

});
