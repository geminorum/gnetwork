jQuery(function($) {

  $('#menu-to-edit').on('click', 'a.item-edit', function() {

    var settings = $(this).closest('.menu-item-bar').next('.menu-item-settings'),
      css_class = settings.find('.edit-menu-item-classes');

    if (css_class.val().indexOf('gnetwork-menu') === 0) {
      css_class.attr('readonly', 'readonly');
      settings.find('.field-url').css('display', 'none');
    }
  });
});
