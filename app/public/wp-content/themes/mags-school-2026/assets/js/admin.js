jQuery(function ($) {
  $('.mags-upload-document').on('click', function (event) {
    event.preventDefault();
    var picker = wp.media({ title: 'Choose a document', button: { text: 'Use this document' }, multiple: false });
    picker.on('select', function () { $('#mags_document_url').val(picker.state().get('selection').first().toJSON().url); });
    picker.open();
  });
});
