jQuery(document).ready(function($) {

  $('body').on('click', 'a[data-toggle="tab"]', function(e) {
    e.preventDefault();

    var
      tab = $(this).data('tab'),
      wrap = $(this).parent('.-wrapper'),
      tabs = $(this).parents('.-base');

    wrap.find('a.nav-tab').removeClass('nav-tab-active -active');
    $(this).addClass('nav-tab-active -active');
    tabs.find('.-content').hide();
    tabs.find('[data-tab="' + tab + '"]').fadeIn();
  });
});
