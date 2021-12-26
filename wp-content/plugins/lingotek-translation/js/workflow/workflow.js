Workflow = {};

Workflow.modals = {};

Workflow.modals.get_ajax_url = function(action) {
    return get_relative_url() + '/admin-ajax.php?action=' + action;
}

Workflow.modals.close_modal = function(id) {
    jQuery('#' + id).trigger('click');
}

Workflow.modals.replace_href = function(element) {
    if (!jQuery(element).attr('url'))
    {
        jQuery(element).attr('url', jQuery(element).attr('href'));
        jQuery(element).attr('href','#');
    }
    return jQuery(element).attr('url');
}

Workflow.modals.get_url_parameter = function(name, url) {
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);
    return (results) ? results[1] : null;
}

Workflow.modals.ajax_request = function(type, ajax_url, body, callback) {
    var request = jQuery.ajax({
        url : ajax_url,
        data : body,
        type : type,
        success : function(result) {
            result = JSON.parse(result);
            callback(result);
        }
    });
    return request;
}

Workflow.modals.loading = function(id) {
    Workflow.modals.show_modal(id, 'Lingotek Professional Translation Services', 550, 950);
    jQuery('#TB_ajaxContent').find('*').not('.loading-element').hide();
    jQuery('.loading-element').show();
}

Workflow.modals.show_modal = function(id, header_text, height, width) {
    tb_show('<img src="' + workflow_vars.icon_url +'" style="padding-top:10px; display:inline-block"><span class="Upgrade-to-Lingotek header-professional">'+ header_text +'</span>', '#TB_inline?width='+ width +'&height='+ height +'&inlineId=modal-window-id-' + id);
    Workflow.modals.add_header_modal(id);
}

Workflow.modals.add_header_modal = function(id) {
    jQuery('#TB_title').css('height','55px');
    jQuery('#TB_title').css('background-color','#3c3c3c');
    jQuery('.tb-close-icon').css('color','white');
}

Workflow.modals.stop_loading = function(id, exceptions = '') {
    jQuery('.loading-element').hide();
    jQuery('#TB_ajaxContent').find('*').not('.loading-element' + ',' + exceptions).show();
}

Workflow.reset = function() {
    jQuery('#TB_ajaxContent').find('*').remove();
}

Workflow.modals.get_relative_url = function() {
    return get_relative_url();
}

Workflow.workflows = {
    'ltk-professional-translation' : 'https://www.lingotek.com/'
}

Workflow.modals.show_payment_portal_loading_modal = function() {
    tb_show('<img src="' + modal_vars.icon_url +'" style="padding-top:10px; display:inline-block"><span style="position:absolute; padding-top: 15px;"class="Upgrade-to-Lingotek">Lingotek Professional Translation Services</span>', '#TB_inline?width=800&height=400&inlineId=modal-window-id-' + professional_vars.workflow_id);
    jQuery('#TB_title').css('height','55px');
    jQuery('#TB_title').css('background-color','#3c3c3c');
    jQuery('.tb-close-icon').css('color','white');
}

/**
 * This is a list of functions that can be executed via the Workflow.reload() function. The updater removes and adds elements 
 * to the DOM and if you have any handlers on any elements they could be lost during the update.
 */
Workflow.reload_list = [];

/**
 * Refer to Workflow.reload_list[]. Any script can add a method to the reload_list array that will be executed as soon as the updater finishes.
 */
Workflow.reload = function() {
   jQuery.each(Workflow.reload_list, function(index, func) {
       if (typeof func === 'function') {
           func();
       }
   });
}


function get_relative_url() {
    var url = window.location.href;
    var end = url.indexOf('wp-admin') + 'wp-admin'.length;
    return url.substring(0,end);
}

