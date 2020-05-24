jQuery(function ($) {
  $('#gnetwork-cron-force-check').on('click', function (event) {
    event.preventDefault();
    var $button = $(this);
    var $spinner = $button.prev('.spinner');
    $.ajax({
      type: 'GET',
      url: ajaxurl,
      data: {
        action: 'gnetwork_cron',
        nonce: $button.data('nonce')
      },
      beforeSend: function (xhr) {
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
      },
      success: function (response) {
        $spinner.removeClass('is-active');
        $button.prop('disabled', false);
        if (response.success) {
          $('#gnetwork-cron-status-check .-status-container').html(response.data);
        } else {
          $('#gnetwork-cron-status-check .-status').removeClass('-success').addClass('-error').html($button.data('error'));
          console.log(response);
        }
      }
    });
  });
});
