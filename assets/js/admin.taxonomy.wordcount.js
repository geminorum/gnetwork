/* global wp, _ */

(function ($, counter) {
  $(function () {
    var $content = $('#html-tag-description');
    var $chars = $('#description-editor-counts').find('.char-count');
    var $words = $('#description-editor-counts').find('.word-count');
    var prevChars = 0;
    var prevWords = 0;
    var contentEditor;

    function update () {
      var text, chars, words, lang;

      if (!contentEditor || contentEditor.isHidden()) {
        text = $content.val();
        lang = $('html').attr('lang');
      } else {
        text = contentEditor.getContent({format: 'raw'});
        lang = contentEditor.settings.wp_lang_attr;
      }

      chars = counter.count(text, 'characters_including_spaces');
      words = counter.count(text, 'words');

      if (chars !== prevChars) {
        $chars.text((lang === 'fa-IR' ? toPersian(chars) : chars));
      }

      if (words !== prevWords) {
        $words.text((lang === 'fa-IR' ? toPersian(words) : words));
      }

      prevChars = chars;
      prevWords = words;
    }

    function toPersian (n) {
      var p = '۰'.charCodeAt(0);
      return n.toString().replace(/\d+/g, function (m) {
        return m.split('').map(function (n) {
          return String.fromCharCode(p + parseInt(n));
        }).join('');
      });
    }

    $(document).on('tinymce-editor-init', function (event, editor) {
      // $(editor.targetElm).hasClass('editor-status-counts')

      if (editor.id !== 'html-tag-description') {
        return;
      }

      contentEditor = editor;

      editor.on('nodechange keyup', _.debounce(update, 1000));
    });

    $content.on('input keyup', _.debounce(update, 1000));

    update();
  });
})(jQuery, new wp.utils.WordCounter());
