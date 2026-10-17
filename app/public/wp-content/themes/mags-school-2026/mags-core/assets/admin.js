jQuery(function ($) {
  $('.mags-upload-document').on('click', function (event) {
    event.preventDefault();
    var picker = wp.media({ title: 'Choose a document', button: { text: 'Use this document' }, multiple: false, library: { type: 'application/pdf' } });
    picker.on('select', function () { $('#mags_document_url').val(picker.state().get('selection').first().toJSON().url); });
    picker.open();
  });

  function selectedPhotoIds() {
    return ($('#mags_gallery_image_ids').val() || '').split(',').map(function (id) { return parseInt(id, 10); }).filter(Boolean);
  }

  function renderPhotos(attachments) {
    var $preview = $('#mags-gallery-preview').empty();
    attachments.forEach(function (attachment) {
      var data = attachment.toJSON ? attachment.toJSON() : attachment;
      var source = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;
      $('<span>').attr('data-id', data.id).append($('<img>').attr({ src: source, alt: '' })).append($('<button>').attr({ type: 'button', class: 'mags-remove-photo', 'aria-label': 'Remove photograph' }).text('×')).appendTo($preview);
    });
  }

  $('.mags-upload-photos').on('click', function (event) {
    event.preventDefault();
    var picker = wp.media({ title: 'Select event or album photographs', button: { text: 'Use selected photographs' }, multiple: true, library: { type: 'image' } });
    picker.on('open', function () {
      var selection = picker.state().get('selection');
      selectedPhotoIds().forEach(function (id) { var attachment = wp.media.attachment(id); attachment.fetch(); selection.add(attachment); });
    });
    picker.on('select', function () {
      var attachments = picker.state().get('selection').toArray();
      $('#mags_gallery_image_ids').val(attachments.map(function (attachment) { return attachment.id; }).join(','));
      renderPhotos(attachments);
    });
    picker.open();
  });

  $('#mags-gallery-preview').on('click', '.mags-remove-photo', function () {
    var id = parseInt($(this).parent().attr('data-id'), 10);
    $('#mags_gallery_image_ids').val(selectedPhotoIds().filter(function (item) { return item !== id; }).join(','));
    $(this).parent().remove();
  });

  $('.mags-clear-photos').on('click', function () {
    $('#mags_gallery_image_ids').val('');
    $('#mags-gallery-preview').empty();
  });
});
