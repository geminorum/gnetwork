jQuery(function ($) {
  $('span.media-url').on('click', 'a.media-url-attachment', function (event) {
    event.preventDefault();
    event.stopPropagation();

    const row = $(this).next('div.media-url-box');

    $(this).toggleClass('media-url-open');
    $(row).addClass('media-url-visible');
    $(row).slideToggle('slow');
    $(row).find('input.media-url-field').trigger('select');

    $('div.media-url-box').not(row).slideUp('slow');
  });

  $('input.media-url-field').on('focus', function () {
    this.select();
  });
});
