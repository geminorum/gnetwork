jQuery(function ($) {
  $('.gnetwork-form').on('click', '[data-action="import-remote-content"]', function (event) {
    const $target = $('#' + $(this).data('target'));
    if ($target.length) {
      event.preventDefault();
      event.stopPropagation();

      $.get($(this).data('remote'), function (response) {
        // TODO: add visual indicator
        // console.log(response);
        $target.val(response);
        if (typeof autosize === 'function') {
          autosize.update($target);
        }
      });
    }
  });
});
