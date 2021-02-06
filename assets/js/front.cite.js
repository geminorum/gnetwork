jQuery(function ($) {
  $('a.cite-scroll').on('click', function (event) {
    event.preventDefault();
    $('html,body').animate({
      scrollTop: $(this.hash).offset().top - 30
    }, 500);
  });
});
