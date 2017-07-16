jQuery(document).ready(function($) {
  $("#gnetwork-cron-force-check").on('click', function(e) {
    e.preventDefault();
    var spinner = $(this).prev(".spinner");
    spinner.addClass("is-active");
    $.post(ajaxurl, {
      action: 'gnetwork_cron',
      nonce: $(this).data('nonce')
    }, function() {}, 'json').always(function(data) {
      spinner.removeClass("is-active");
      if (data && data.html) {
        $('#gnetwork-cron-dashboard .inside .-status-container').html(data.html);
      } else {
        $('#gnetwork-cron-dashboard .inside .-status').html('There was a problem getting the status of WP Cron.');
      }
    });
  });
});
