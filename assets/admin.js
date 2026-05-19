(function ($) {
  'use strict';

  var frame = null;
  var searchTimer = null;
  var config = window.MinimalistLoaderAdmin || {};
  var optionName = config.optionName || 'minimalist_loader_settings';

  function selectedIds() {
    return $('#ml-selected-exclusions .ml-selected-item').map(function () {
      return String($(this).data('id'));
    }).get();
  }

  function makeSelectedItem(item) {
    var id = parseInt(item.id, 10);

    if (!id || selectedIds().indexOf(String(id)) !== -1) {
      return;
    }

    var row = $('<div/>', {
      class: 'ml-selected-item',
      'data-id': id
    });
    var hidden = $('<input/>', {
      type: 'hidden',
      name: optionName + '[display][excluded_ids][]',
      value: id
    });
    var text = $('<span/>');
    var title = $('<strong/>').text(item.title || (config.manualIdLabel + ' #' + id));
    var meta = $('<small/>').text((item.meta || config.manualIdLabel) + ' - ID ' + id);
    var remove = $('<button/>', {
      type: 'button',
      class: 'button-link-delete ml-remove-exclusion'
    }).text(config.removeLabel || 'Remove');

    text.append(title, meta);
    row.append(hidden, text, remove);
    $('#ml-selected-exclusions').append(row);
  }

  function renderResults(results) {
    var wrap = $('#ml-search-results').empty();

    if (!results.length) {
      wrap.append($('<p/>', { class: 'ml-empty' }).text(config.noResults));
      return;
    }

    results.forEach(function (item) {
      var button = $('<button/>', {
        type: 'button',
        class: 'ml-result',
        'data-id': item.id,
        'data-title': item.title,
        'data-meta': item.meta
      });

      button.append($('<strong/>').text(item.title));
      button.append($('<small/>').text(item.meta + ' - ID ' + item.id));
      wrap.append(button);
    });
  }

  function searchContent(term) {
    var wrap = $('#ml-search-results');

    if (!term) {
      wrap.empty();
      return;
    }

    wrap.empty().append($('<p/>', { class: 'ml-empty' }).text(config.searching));

    $.ajax({
      url: config.ajaxUrl,
      method: 'GET',
      data: {
        action: 'minimalist_loader_search_content',
        nonce: config.nonce,
        term: term
      }
    }).done(function (response) {
      var results = response && response.success && response.data ? response.data.results : [];
      renderResults(results || []);
    }).fail(function () {
      renderResults([]);
    });
  }

  $('#ml-select-logo').on('click', function () {
    if (frame) {
      frame.open();
      return;
    }

    frame = wp.media({
      title: config.mediaTitle,
      button: { text: config.mediaButton },
      library: { type: 'image' },
      multiple: false
    });

    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;

      $('#ml-logo-id').val(attachment.id);
      $('.ml-logo-preview').addClass('has-logo').empty().append($('<img/>', { src: url, alt: '' }));
    });

    frame.open();
  });

  $('#ml-remove-logo').on('click', function () {
    $('#ml-logo-id').val('');
    $('.ml-logo-preview').removeClass('has-logo').empty();
  });

  $('#ml-content-search').on('input', function () {
    var term = $(this).val();

    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(function () {
      searchContent(term);
    }, 250);
  });

  $('#ml-search-results').on('click', '.ml-result', function () {
    makeSelectedItem({
      id: $(this).data('id'),
      title: $(this).data('title'),
      meta: $(this).data('meta')
    });
  });

  $('#ml-add-manual-id').on('click', function () {
    var input = $('#ml-manual-id');
    var id = parseInt(input.val(), 10);

    if (!id) {
      return;
    }

    makeSelectedItem({
      id: id,
      title: config.manualIdLabel + ' #' + id,
      meta: config.manualIdLabel
    });

    input.val('');
  });

  $('#ml-manual-id').on('keydown', function (event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      $('#ml-add-manual-id').trigger('click');
    }
  });

  $('#ml-selected-exclusions').on('click', '.ml-remove-exclusion', function () {
    $(this).closest('.ml-selected-item').remove();
  });
})(jQuery);
