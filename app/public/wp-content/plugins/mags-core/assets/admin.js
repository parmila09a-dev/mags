jQuery(function ($) {
  $('.mags-upload-document').on('click', function (event) {
    event.preventDefault();
    var picker = wp.media({ title: 'Choose a document', button: { text: 'Use this document' }, multiple: false, library: { type: 'application/pdf' } });
    picker.on('select', function () { $('#mags_document_url').val(picker.state().get('selection').first().toJSON().url); });
    picker.open();
  });

  var $preview = $('#mags-gallery-preview');
  var $imageIds = $('#mags_gallery_image_ids');
  var $heroEditor = $('#mags-hero-slide-editor');
  var $heroCount = $('.mags-hero-slide-count');
  var maxHeroSlides = 5;
  var heroFieldLimits = {
    badge_title: 80,
    badge_detail: 100
  };

  function selectedPhotoIds() {
    return ($imageIds.val() || '').split(',').map(function (id) { return parseInt(id, 10); }).filter(Boolean);
  }

  function syncPhotoOrder() {
    var ids = $preview.children('[data-id]').map(function () { return parseInt($(this).attr('data-id'), 10); }).get().filter(Boolean);
    $imageIds.val(ids.join(','));
    syncHeroEditors(ids);
  }

  function heroField(id, key, label, type, wide) {
    var fieldId = 'mags_hero_' + key + '_' + id;
    var name = 'mags_hero_slides[' + id + '][' + key + ']';
    var $field = $('<p>').addClass('mags-hero-field').toggleClass('mags-hero-field--wide', Boolean(wide));
    var $label = $('<label>').attr('for', fieldId).append($('<strong>').text(label));
    var $control = type === 'textarea'
      ? $('<textarea>').attr({ id: fieldId, name: name, rows: 3, maxlength: heroFieldLimits[key], class: 'widefat' })
      : $('<input>').attr({ id: fieldId, name: name, type: type || 'text', maxlength: heroFieldLimits[key], class: 'widefat' });

    return $field.append($label, $control);
  }

  function buildHeroSlideEditor(id, source, index) {
    var badgeName = 'mags_hero_slides[' + id + '][badge_enabled]';
    var $row = $('<section>').addClass('mags-hero-slide-fields').attr('data-hero-slide-id', id);
    var $header = $('<header>').addClass('mags-hero-slide-fields__header')
      .append($('<img>').attr({ src: source || '', alt: '' }))
      .append($('<div>')
        .append($('<strong>').addClass('mags-hero-slide-number').text('Slide ' + (index + 1)))
        .append($('<span>').text('Selected photograph')));
    var $grid = $('<div>').addClass('mags-hero-slide-fields__grid');
    var $badge = $('<fieldset>').addClass('mags-hero-badge-fields mags-hero-field--wide')
      .append($('<legend>').append($('<strong>').text('Contextual achievement badge')))
      .append($('<input>').attr({ type: 'hidden', name: badgeName, value: '0' }))
      .append($('<label>').addClass('mags-hero-badge-toggle')
        .append($('<input>').attr({ type: 'checkbox', name: badgeName, value: '1' }).prop('checked', index === 0))
        .append(document.createTextNode(' Show badge on this slide')))
      .append($('<div>').addClass('mags-hero-badge-fields__grid')
        .append(heroField(id, 'badge_title', 'Badge title', 'text'))
        .append(heroField(id, 'badge_detail', 'Badge detail', 'text')));

    return $row.append($header, $grid.append($badge));
  }

  function syncHeroEditors(ids) {
    if (!$heroEditor.length) {
      return;
    }

    ids = ids.slice(0, maxHeroSlides);
    var allowed = {};
    var $empty = $heroEditor.children('.mags-hero-slide-empty');

    ids.forEach(function (id, index) {
      allowed[id] = true;
      var $row = $heroEditor.children('[data-hero-slide-id="' + id + '"]');

      if (!$row.length) {
        var source = $preview.children('[data-id="' + id + '"]').find('img').attr('src') || '';
        $row = buildHeroSlideEditor(id, source, index);
      }

      $row.find('.mags-hero-slide-number').text('Slide ' + (index + 1));
      $row.insertBefore($empty);
    });

    $heroEditor.children('[data-hero-slide-id]').each(function () {
      if (!allowed[parseInt($(this).attr('data-hero-slide-id'), 10)]) {
        $(this).remove();
      }
    });

    $empty.prop('hidden', ids.length > 0);
    $heroCount.text(ids.length);
    $('.mags-upload-photos').prop('disabled', ids.length >= maxHeroSlides);

    $preview.children('[data-id]').each(function (index) {
      var total = ids.length;
      $(this).find('.mags-move-photo-earlier').prop('disabled', index === 0).attr('aria-label', 'Move slide ' + (index + 1) + ' earlier');
      $(this).find('.mags-move-photo-later').prop('disabled', index === total - 1).attr('aria-label', 'Move slide ' + (index + 1) + ' later');
      $(this).find('.mags-remove-photo').attr('aria-label', 'Remove slide ' + (index + 1));
    });
  }

  function photoActions() {
    return $('<span>').addClass('mags-photo-actions')
      .append($('<button>').attr({ type: 'button', class: 'mags-move-photo mags-move-photo-earlier', 'aria-label': 'Move photograph earlier' }).text('←'))
      .append($('<button>').attr({ type: 'button', class: 'mags-move-photo mags-move-photo-later', 'aria-label': 'Move photograph later' }).text('→'))
      .append($('<button>').attr({ type: 'button', class: 'mags-remove-photo', 'aria-label': 'Remove photograph' }).text('×'));
  }

  function appendPhoto(attachment) {
    var data = attachment.toJSON ? attachment.toJSON() : attachment;
    var id = parseInt(data.id, 10);

    if (!id || $preview.children('[data-id="' + id + '"]').length) {
      return;
    }

    if ($heroEditor.length && $preview.children('[data-id]').length >= maxHeroSlides) {
      return;
    }

    var source = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;
    $('<span>').attr('data-id', id).append($('<img>').attr({ src: source, alt: '' })).append(photoActions()).appendTo($preview);
  }

  if ($preview.length && $.fn.sortable) {
    $preview.sortable({
      items: '> [data-id]',
      cancel: 'button',
      cursor: 'move',
      placeholder: 'mags-gallery-sort-placeholder',
      tolerance: 'pointer',
      update: syncPhotoOrder
    });
  }

  $('.mags-upload-photos').on('click', function (event) {
    event.preventDefault();
    var picker = wp.media({ title: 'Add event or album photographs', button: { text: 'Add selected photographs' }, multiple: true, library: { type: 'image' } });

    picker.on('open', function () {
      var selection = picker.state().get('selection');
      selectedPhotoIds().forEach(function (id) { selection.add(wp.media.attachment(id)); });
    });

    picker.on('select', function () {
      picker.state().get('selection').toArray().forEach(appendPhoto);
      syncPhotoOrder();
    });

    picker.open();
  });

  $preview.on('click', '.mags-remove-photo', function () {
    $(this).closest('[data-id]').remove();
    syncPhotoOrder();
  });

  $preview.on('click', '.mags-move-photo-earlier', function () {
    var $item = $(this).closest('[data-id]');
    var $previous = $item.prev('[data-id]');
    if ($previous.length) { $item.insertBefore($previous); syncPhotoOrder(); }
  });

  $preview.on('click', '.mags-move-photo-later', function () {
    var $item = $(this).closest('[data-id]');
    var $next = $item.next('[data-id]');
    if ($next.length) { $item.insertAfter($next); syncPhotoOrder(); }
  });

  $('.mags-clear-photos').on('click', function () {
    $preview.empty();
    syncPhotoOrder();
  });

  syncHeroEditors(selectedPhotoIds());
});
