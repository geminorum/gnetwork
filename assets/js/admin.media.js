jQuery(function ($) {
  $('table.media td.title div.row-actions').each(function () {
    $(this).find('div.media-url-box').hide();
  });

  $('span.media-url').on('click', 'a.media-url-attachment', function (event) {
    event.preventDefault();
    event.stopPropagation();

    var row = $(this).next('div.media-url-box');

    $(this).toggleClass('media-url-open');
    $(row).addClass('media-url-visible');
    $(row).slideToggle('slow');
    $(row).find('input.media-url-field').select();

    $('div.media-url-box').not(row).slideUp('slow');
  });

  $('input.media-url-field').focus(function () {
    this.select();
  });

  $('.wp-list-table').on('click', '.media-clean a', function (event) {
    event.preventDefault();

    var link = $(this);

    if (link.hasClass('-cleaned')) {
      return;
    }

    $.ajax({
      method: 'POST',
      url: ajaxurl,
      data: {
        action: 'gnetwork_media',
        what: 'clean_attachment',
        attachment: link.data('id'),
        nonce: link.data('nonce')
      },
      beforeSend: function (xhr) {
        link.html(link.data('spinner'));
      },
      success: function (response) {
        if (response.success) {
          link.text(response.data).addClass('-cleaned');
        } else {
          link.text($(response.data).text());
          console.log(response);
        }
      }
    });
  });
});
