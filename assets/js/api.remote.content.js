jQuery(function ($) {
  $('.gnetwork-form').on('click', '[data-action="import-remote-content"]', function (event) {
    event.preventDefault();
    event.stopPropagation();

    var $button = $(this);

    $.get($button.data('remote'), function (response) {
      // TODO: add visual indicator
      console.log(response);
      $('#' + $button.data('target')).val(response);
    });
  });
});
