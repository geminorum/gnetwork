/* global FileReader */

(function ($) {
  var reader = {};
  var file = {};
  var sliceSize = 1000 * 1024;

  var s = {
    action: 'gnetwork_media',
    file: '#gnetwork-media-file-upload',
    name: '#gnetwork-media-file-name',
    submit: '#gnetwork-media-file-submit',
    progress: '#gnetwork-media-file-progress'
  };

  var o = {
    upload: function (start) {
      var sliceNext = start + sliceSize + 1;
      var blob = file.slice(start, sliceNext);

      reader.onloadend = function (event) {
        if (event.target.readyState !== FileReader.DONE) {
          return;
        }

        var $submit = $(s.submit);

        $.ajax({
          url: ajaxurl,
          type: 'POST',
          dataType: 'json',
          cache: false,
          data: {
            action: s.action,
            what: 'upload_chaunk',
            file_data: event.target.result,
            file: file.name,
            file_type: file.type,
            chunk: start,
            nonce: $submit.data('nonce')
          },
          error: function (jqXHR, textStatus, errorThrown) {
            console.log(jqXHR, textStatus, errorThrown);
          },
          success: function (response) {
            if (response.success) {
              var doneSize = start + sliceSize;
              var donePercent = Math.floor((doneSize / file.size) * 100);
              if (sliceNext < file.size) {
                if ($submit.data('locale') === 'fa_IR') {
                  donePercent = u.tP(donePercent);
                }
                u.io(s.progress, u.sp($submit.data('progress'), donePercent));
                o.upload(sliceNext);
              } else {
                $(s.name).html('').hide();
                $(s.submit).prop('disabled', true);
                u.io(s.progress, $submit.data('complete'));
                o.complete(file.name);
              }
            } else {
              u.io(s.progress, response.data);
              console.log(response);
            }
          }
        });
      };

      reader.readAsDataURL(blob);
    },

    complete: function (filename) {
      var $submit = $(s.submit);
      var $spinner = $(s.progress).prev('.spinner');

      $.ajax({
        type: 'GET',
        url: ajaxurl,
        data: {
          action: s.action,
          what: 'upload_complete',
          file: filename,
          nonce: $submit.data('nonce')
        },
        beforeSend: function (xhr) {
          $spinner.addClass('is-active');
        },
        success: function (response) {
          $spinner.removeClass('is-active');
          if (response.success) {
            u.io(s.progress, response.data);
          } else {
            u.io(s.progress, response.data);
            console.log(response);
          }
        }
      });
    }
  };

  var u = {
    // @REF: https://gist.github.com/geminorum/ebb48ff0c0df3876e58610dbb5a60f0f
    sp: function (f) {
      var a = Array.prototype.slice.call(arguments, 1);
      var i = 0;
      return f.replace(/%s/g, function () {
        return a[i++];
      });
    },
    tP: function (n) {
      var p = '۰'.charCodeAt(0);
      return n.toString().replace(/\d+/g, function (m) {
        return m.split('').map(function (n) {
          return String.fromCharCode(p + parseInt(n));
        }).join('');
      });
    },
    io: function (s, h) {
      $(s).fadeOut('fast', function () {
        $(this).html(h).fadeIn();
      });
    }
  };

  $(s.file).change(function () {
    var filename = $(this).val();
    if (filename !== '') {
      $(s.name).html(filename).show();
      $(s.submit).prop('disabled', false);
    } else {
      $(s.name).html('').hide();
      $(s.submit).prop('disabled', true);
    }
  });

  $(s.submit).on('click', function (event) {
    event.preventDefault();
    $(this).prop('disabled', true);

    reader = new FileReader();
    file = document.querySelector(s.file).files[0];

    o.upload(0);
  });
})(jQuery);
