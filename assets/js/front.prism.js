jQuery(function ($) {
  $('pre[data-prism="yes"]').each(function (index, el) {
    $(this).prepend("<span id='externalWindow'>Source</span>");
    $('#externalWindow').click(function () {
      var codeText = $(this).next('code[class*="language-"]').text();
      var w = window.open('', 'PrismJS', 'menubar=no, status=no, scrollbars=no, menubar=no, width=800, height=600');
      $(w.document.body).html('<textarea style="width: 100%; height: 100%;" readonly>' + codeText + '</textarea>');
    });
  });
});
