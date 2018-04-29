jQuery(function ($) {
  $('#menu-to-edit').on('click', 'a.item-edit', function () {
    var settings = $(this).closest('.menu-item-bar').next('.menu-item-settings');
    var cssClass = settings.find('.edit-menu-item-classes');

    if (cssClass.val().indexOf('gnetwork-menu') === 0) {
      cssClass.attr('readonly', 'readonly');
      settings.find('.field-url').css('display', 'none');
    }
  });
});
