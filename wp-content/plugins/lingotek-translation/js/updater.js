jQuery(document).ready(function($) {
  $('.edit-php a.submitdelete, .post-php a.submitdelete').click( function( event ) {
    if( ! confirm( 'Content that is put into the trash will be cancelled in your Lingotek community. Are you sure you want to proceed?' ) ) {
        event.preventDefault();
    }
  });
  var current_ids = {};
  var post_data = {"check_ids" : current_ids};
  var url = window.location.href;
  var end = url.indexOf('wp-admin') + 'wp-admin'.length;
  var relative_url = url.substring(0,end);
  var ajax_url = relative_url + '/admin-ajax.php?action=get_current_status';
  var page_params = '/edit.php?';
  var tr_id = '#post-';
  var object_type = 'post';
  var taxonomy_type = 'post';
  if(url.indexOf('taxonomy') > -1){
    var begin = url.indexOf('taxonomy=') + 'taxonomy='.length;
    taxonomy_type = url.substring(begin);
    post_data['taxonomy'] = taxonomy_type;
  }

  if($('.edit-tags-php').length > 0){
    post_data['terms_translations'] = true;
    page_params = '/edit-tags.php?taxonomy=' + taxonomy_type + '&';
    tr_id = '#tag-';
    object_type = 'term';
  }

  var interval = updater_data.interval === undefined ? 10 : updater_data.interval;
  if (interval > 0) {
    setInterval(function() {
      var rows = $('#the-list').find('tr');
      $(rows).each(function() {
        if($(this).attr('id') && $(this).attr('id').length > 1) {
          var id = $(this).attr('id');
          var object_id = id.replace( /^\D+/g, '');
          current_ids[object_id] = object_id;
        }
      });
      $.ajax({
        type: 'POST',
        url: ajax_url,
        data: post_data,
        dataType: 'json',
        success: function (data) {
          if (data !== null) {
            update_indicators(data);
          }
        }
      });
    }, interval * 1000);
  }

  function update_indicators(data){
    ignoreClicks();
    var doc_id_present = false;
    for(var key in data){
      if(data[key].doc_id !== null && data[key].doc_id !== undefined && data[key].source_status === 'current'){
        doc_id_present = true;
      }
      var source_id = key != data[key]['source_id'] && data[key]['source_id'] !== null
        ? data[key]['source_id']
          : key;
      var tr = $(tr_id + key);
      if(data[key]['source'] === false){
        updateUploadBulkLink(tr, data, source_id, 'upload' , 'Upload this item to Lingotek TMS', 'Upload to Lingotek');
        continue;
      }
      if(key.indexOf('_nonce') > -1) {
        continue;
      }
      if (doc_id_present === true) {
        // Get rid of these if the document id is present and the source status is current
        $(tr).find($('.lingotek-request')).remove();
        $(tr).find($('.lingotek-status')).remove();
        $(tr).find($('.lingotek-upload')).remove();
        $(tr).find($('.lingotek-download')).remove();
      }
      for(var locale in data[key]){
        if(locale === 'source' || locale === 'doc_id' || locale === 'source_id' || locale === 'source_status' || locale === 'existing_trans'){
          continue;
        }
        var td = $(tr).find('td.language_' + locale);
        switch(data[key][locale]['status']){
          case 'current':
            updateCurrentIndicator(td,data,key,locale, source_id);
            break;
          case 'pending':
            updateGenericBulkLink(tr, data, key, 'status' , 'Update translations status of this item in Lingotek TMS', 'Update translations status ');
            updateWorkbenchIcon(td, data, key, locale, 'In Progress', 'clock');
            break;
          case 'interim':
            updateGenericBulkLink(tr, data, key, 'status' , 'Update translations status of this item in Lingotek TMS', 'Update translations status ');
            updateInterimIcon(td, data, key, locale);
            break;
          case 'importing':
            $(td).find('.pll_icon_edit').remove();
            updateGenericBulkLink(tr, data, key, 'status' , 'Update translations status of this item in Lingotek TMS', 'Update translations status ');
            updateIndicator(td, data, key, locale, 'status', 'Importing Source', 'clock');
            break;
          case 'edited':
            $(td).find('.pll_icon_edit').remove();
            updateUploadBulkLink(tr, data, source_id, 'upload' , 'Upload this item to Lingotek TMS', 'Upload to Lingotek');
            updateUploadIndicator(td, data, source_id, locale);
            break;
          case 'ready':
            updateGenericBulkLink(tr, data, key, 'download' , 'Download translations of this item from Lingotek TMS', 'Download translations');
            updateIndicator(td, data, key, locale, 'download', 'Ready to download', 'download');
            break;
          case 'deleted':
            if (locale === data[key]['source']){
              updateUploadBulkLink(tr, data, key, 'upload' , 'Upload deleted item to Lingotek TMS', 'Upload to Lingotek');
              updateIndicator(td, data, key, locale, 'deleted', 'Upload deleted item', 'remove');
            } else {
              updateIndicator(td, data, key, locale, 'deleted', 'Target is deleted', 'remove');
            }
            break;
          case 'cancelled':
            if (locale === data[key]['source']){
              updateUploadBulkLink(tr, data, key, 'upload' , 'Upload cancelled item to Lingotek TMS', 'Upload to Lingotek');
              updateIndicator(td, data, key, locale, 'cancelled', 'Upload cancelled item', 'warning');
            } else {
              updateIndicator(td, data, key, locale, 'cancelled', 'Target is cancelled', 'warning');
            }
            break;
          case 'failed':
            updateFailedIndicator(td, data, key, locale);
            break;
          case 'disabled':
            break;
          default:
            var source = data[key]['source'];
            if(locale === data[key]['source']){
              $(td).find('.lingotek-color').remove();
              $(td).find('.lingotek-interim-color').remove();
              $(td).find('.pll_icon_edit').remove();
              updateUploadIndicator(td, data, key, locale);
            }
            else if (data[key][locale]['status'] === 'disabled' || data[key]['source'] === 'disabled') {
              $(td).find('.lingotek-color').remove();
              $(td).find('.lingotek-interim-color').remove();
            }
            else if ($(td).find('.pll_icon_add').length > 0 && (data[key][data[key]['source']]['status'] === 'none' || data[key][data[key]['source']]['status'] === 'cancelled')){
              break;
            }
            else if (data[key]['source_status'] === 'failed') {
              updateFailedIndicator(td, data, key, locale);
              break;
            }
            else if(source !== false && data[key]['source_status'] === 'current'){
              $(td).find('.pll_icon_add').remove();
              $(td).find('.pll_icon_tick').remove();
              $(td).find('.pll_icon_edit').remove();
              $(td).find('.lingotek-color').remove();
              $(td).find('.lingotek-interim-color').remove();
              updateIndicator(td, data, key, locale, 'request', 'Request a translation', 'plus');
              updateGenericBulkLink(tr, data, key, 'request' , 'Request translations of this item to Lingotek TMS', 'Request translations');
            }
            else {
              $(td).find('.pll_icon_add').remove();
              $(td).find('.pll_icon_tick').remove();
              $(td).find('.pll_icon_edit').remove();
              $(td).find('.lingotek-color').remove();
              $(td).find('.lingotek-interim-color').remove();
              var indicator = $('<div></div>').addClass('lingotek-color dashicons dashicons-no');
              $(td).prepend(indicator);
            }
            break;
        }
      }
    }
    Workflow.reload();
    listenForClicks();
  }

  function ignoreClicks()
  {
    $(document).on('click', '.lingotek-color', ignoreClickHandler)
  }
  function listenForClicks()
  {
    $(document).off('click', '.lingotek-color', ignoreClickHandler);
  }
  function ignoreClickHandler(e)
  {
    e.preventDefault();
  }

  function updateWorkbenchIcon(td, data, key, locale, title, icon){
    if ('clock' === icon && $(td).find('.lingotek-professional-icon').length > 0)
    {
      return;
    }
    else
    {
      $(td).find('.lingotek-professional-icon').remove();
    }
    $(td).find('.pll_icon_edit').remove();
    $(td).find('.lingotek-color').remove();
    $(td).find('.lingotek-interim-color').remove();
    var request_link = $('<a></a>').attr('href', data[key][locale]['workbench_link'])
      .attr('title',title)
      .attr('target','_blank')
      .addClass('lingotek-color dashicons dashicons-' + icon + ' dashicons-' + icon + '-lingotek');
    $(td).prepend(request_link);
  }

  function updateInterimIcon(td, data, key, locale) {
    $(td).find('.lingotek-color').remove();
    $(td).find('.lingotek-interim-color').remove();
    if ($(td).find('dashicons-no').length > 0) {
      $(td).find('.dashicons-no').remove();
    } else if ($(td).find('.dashicons-clock').length > 0) {
      $(td).find('.dashicons-clock').remove();
    }
    $(td).find('.lingotek-professional-icon').remove();
    var icon = 'edit';
    $(td).find('.lingotek-interim-color').remove();
    var request_link = $('<a></a>').attr('href', data[key][locale]['workbench_link'])
      .attr('title', 'Interim Translation Downloaded')
      .attr('target','_blank')
      .addClass('lingotek-interim-color dashicons dashicons-' + icon + ' dashicons-' + icon + '-lingotek');
    $(td).prepend(request_link);
  }

  function updateGenericBulkLink(tr, data, key, action, title, text){
    var row_actions = $(tr).find('.row-actions');
    if($(row_actions).find('.lingotek-' + action).length === 0){
      var status_update_link = $('<span class="lingotek-'+ action +'"><a class="lingotek-color"'
      + ' title="' + title + '" '
      + 'href="?document_id=' + data[key]['doc_id']
      + '&action=lingotek-' + action
      + '&noheader=1'
      + '&_wpnonce=' + data[action + '_nonce']
      + '">' + text + '</a> | </span>');
      var disassociate = $(row_actions).find('.lingotek-delete');
      var cancel = $(row_actions).find('.lingotek-cancel');
      var disassociate_translation = $(row_actions).find('.lingotek-delete-translation');
      var cancel_translation = $(row_actions).find('.lingotek-cancel-translation');
      if($(cancel_translation).length > 0){
        $(cancel_translation).off("click");
        $(cancel).click(function(){
          return confirm('You are about to cancel existing translations in your Lingotek community. Are you sure?');
        });
        $(cancel_translation).before(status_update_link);
      } else if($(cancel).length > 0){
        $(cancel).off("click");
        $(cancel).click(function(){
          return confirm('You are about to cancel this translation in your Lingotek community. Are you sure?');
        });
        $(cancel).before(status_update_link);
      } else if($(disassociate).length > 0 ){
        $(disassociate).off("click");
        $(disassociate).click(function(){
          return confirm('You are about to delete existing translations in your WordPress Site and cancel existing translations in your Lingotek community. Are you sure?');
        });
        $(disassociate).before(status_update_link);
      } else if($(disassociate_translation).length > 0){
        $(disassociate_translation).off("click");
        $(disassociate_translation).click(function(){
          return confirm('You are about to cancel this translation in your Lingotek community. Are you sure?');
        });
        $(disassociate_translation).before(status_update_link);
      } else {
        $(row_actions).append(status_update_link);
      }
     }
  }

  function updateUploadBulkLink(tr, data, key, action, title, text){
      var row_actions = $(tr).find('.row-actions');
      if($(row_actions).find('.lingotek-' + action).length === 0){
        var pipe_separator = data[key].doc_id == null || data[key].source_status == 'cancelled' ? '">' + text + '</a> </span>' : '">' + text + '</a> | </span>';
        var status_update_link = $('<span class="lingotek-'+ action +'"><a class="lingotek-color"'
        + ' title="' + title + '" '
        + 'href="?'
        + 'taxonomy=' + taxonomy_type
        + '&' + object_type + '=' + key
        + '&action=lingotek-' + action
        + '&noheader=1'
        + '&_wpnonce=' + data[action + '_nonce']
        + pipe_separator);
        var disassociate = $(row_actions).find('.lingotek-delete');
        if($(disassociate).length > 0){
          $(disassociate).before(status_update_link);
        }
        else {
          if(data[key]['existing_trans'] === true && data[key]['source_id'] === null){
            $(status_update_link).click(function(){
              return confirm('You are about to overwrite existing translations. Are you sure?');
            });
          }
          $(row_actions).append(status_update_link);
        }
     }
  }

  function updateUploadIndicator(td, data, key, locale){
    $(td).find('.lingotek-color').remove();
    $(td).find('.lingotek-interim-color').remove();
    var request_link = $('<a></a>').attr('href', relative_url
      + page_params + 'post=' + key
      + '&locale=' + locale
      + '&action=lingotek-upload'
      + '&noheader=1'
      + '&_wpnonce=' + data['upload_nonce'])
      .attr('title','Upload Now')
      .addClass('lingotek-color dashicons dashicons-upload dashicons-upload-lingotek');
    $(td).prepend(request_link);
  }

  function updateFailedIndicator(td, data, key, locale) {
    $(td).find('.lingotek-color').remove();
    $(td).find('.lingotek-interim-color').remove();
    $(td).find('.lingotek-failed-color').remove();
    var reupload_failed_doc = $('<a></a>').attr('title', 'Upload Failed. Click to re-upload')
                                          .attr('href', relative_url
                                          + page_params + 'post=' + key
                                          + '&locale=' + locale + '&action=lingotek-upload'
                                          + '&noheader=1&_wpnonce=' + data['upload_nonce'])
                                          .addClass('lingotek-failed-color dashicons dashicons-no');
    $(td).prepend(reupload_failed_doc);
  }

  function updateIndicator(td, data, key, locale, action, title, dashicon){
    if ('download' === dashicon && $(td).find('.lingotek-professional-icon').length > 0)
    {
      $(td).find('.lingotek-professional-icon').remove();
    }
    $(td).find('.lingotek-color').remove();
    $(td).find('.lingotek-interim-color').remove();
    var request_link = $('<a></a>').attr('href', relative_url
            + page_params + 'document_id=' + data[key]['doc_id']
            + '&locale=' + locale
            + '&action=lingotek-' + action
            + '&noheader=1'
            + '&_wpnonce='+data[action + '_nonce'])
      .attr('title', title)
      .addClass('lingotek-color dashicons dashicons-' + dashicon + ' dashicons-' + dashicon + '-lingotek');
    $(td).prepend(request_link);
  }

  function updateCurrentIndicator(td,data,key,locale, source_id){
    $(td).find('.pll_icon_edit').remove();
    if(locale === data[key]['source']){
      $(td).find('.lingotek-color').remove();
      $(td).find('.lingotek-interim-color').remove();
      if(post_data['terms_translations'] === true){
        var request_link = $('<a></a>').attr('href', relative_url
              + '/edit-tags.php?action=edit'
              + '&taxonomy=' + taxonomy_type
              + '&tag_ID=' + key
              + '&post_type=post');
      }
      else {
        var request_link = $('<a></a>').attr('href', relative_url
              + '/post.php?post=' + source_id
              + '&action=edit');
      }
      $(request_link).attr('title','Source uploaded')
        .addClass('lingotek-color dashicons dashicons-yes');
      $(td).prepend(request_link);
    }
    else {
      updateWorkbenchIcon(td, data, key, locale, 'Current', 'edit');
    }
  }
});
