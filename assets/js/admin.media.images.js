/* global jQuery */

(function ($) {
  $('.base-table-list')

    .on('click', 'a.-row-ajax-clean', function (e) {
      e.preventDefault();

      var link = $(this);

      if (link.hasClass('-cleaned')) {
        return;
      }

      $.ajax({
        type: 'GET',
        url: link.attr('href'),
        beforeSend: function (xhr) {
          link.html(link.data('spinner'));
        },
        success: function (response) {
          if (response.success) {
            link.text(response.data).addClass('-cleaned');
          } else {
            link.text($(response.data).text());
            console.log(response);
          }
        }
      });
    })

    .on('click', 'a.-row-ajax-sync', function (e) {
      e.preventDefault();

      var link = $(this);

      if (link.hasClass('-synced')) {
        return;
      }

      $.ajax({
        type: 'GET',
        url: link.attr('href'),
        beforeSend: function (xhr) {
          link.html(link.data('spinner'));
        },
        success: function (response) {
          if (response.success) {
            link.text(response.data).addClass('-synced');
          } else {
            link.text($(response.data).text());
            console.log(response);
          }
        }
      });
    })

    .on('click', 'a.-row-ajax-cache', function (e) {
      e.preventDefault();

      var link = $(this);

      if (link.hasClass('-cached')) {
        return;
      }

      $.ajax({
        type: 'GET',
        url: link.attr('href'),
        beforeSend: function (xhr) {
          link.html(link.data('spinner'));
        },
        success: function (response) {
          if (response.success) {
            link.text(response.data).addClass('-cached');
          } else {
            link.text($(response.data).text());
            console.log(response);
          }
        }
      });
    });
})(jQuery);
