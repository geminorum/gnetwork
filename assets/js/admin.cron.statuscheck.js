jQuery(function ($) {
  $('#gnetwork-cron-force-check').on('click', function (e) {
    e.preventDefault();
    var $spinner = $(this).prev('.spinner');
    $.ajax({
      type: 'GET',
      url: ajaxurl,
      data: {
        action: 'gnetwork_cron',
        nonce: $(this).data('nonce')
      },
      beforeSend: function (xhr) {
        $spinner.addClass('is-active');
      },
      success: function (response) {
        $spinner.removeClass('is-active');
        if (response.success) {
          $('#gnetwork-cron-status-check .-status-container').html(response.html);
        } else {
          $('#gnetwork-cron-status-check .-status').html('There was a problem getting the status of WP Cron.');
          console.log(response);
        }
      }
    });
  });
});
