jQuery(function ($) {
  var form = '#gnetwork-support-report form';

  $(form).submit(function () {
    var $spinner = $(this).find('.spinner');
    var $message = $(this).find('.-message');

    $.ajax({
      type: 'POST',
      url: ajaxurl,
      data: {
        action: 'gnetwork_support',
        what: 'submit_report',
        nonce: $(this).data('nonce'),
        form: $(this).serializeArray()
      },
      beforeSend: function (xhr) {
        $(':input', form).prop('disabled', true);
        $spinner.addClass('is-active');
      },
      success: function (response) {
        $(':input', form).prop('disabled', false);
        $spinner.removeClass('is-active');
        if (response.success) {
          $message.html(response.data);
          $(':input', form).not(':button, :submit, :reset, :hidden, :select').val('').prop('checked', false); // .prop('selected', false);
        } else {
          $message.html(response.data);
        }
      }
    });

    return false;
  });
});
