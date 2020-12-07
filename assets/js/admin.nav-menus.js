jQuery(function ($) {
  $('#menu-to-edit').on('click', 'a.item-edit', function () {
    const settings = $(this).closest('.menu-item-bar').next('.menu-item-settings');
    const cssClass = settings.find('.edit-menu-item-classes');

    if (cssClass.val().indexOf('gnetwork-menu') === 0) {
      cssClass.attr('readonly', 'readonly');
      settings.find('.field-url').css('display', 'none');
    }
  });
});
