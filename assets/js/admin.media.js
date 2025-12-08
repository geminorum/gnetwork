jQuery(function ($) {
  $('.wp-list-table').on('click', '.media-clean a', function (event) {
    event.preventDefault();

    const link = $(this);

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
