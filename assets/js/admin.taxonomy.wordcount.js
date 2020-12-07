/* global wp, _ */

(function ($, _, counter) {
  $(function () {
    const $content = $('#html-tag-description');
    const $chars = $('#description-editor-counts').find('.char-count');
    const $words = $('#description-editor-counts').find('.word-count');
    let prevChars = 0;
    let prevWords = 0;
    let contentEditor;

    function update () {
      let text;
      let lang;

      if (!contentEditor || contentEditor.isHidden()) {
        text = $content.val();
        lang = $('html').attr('lang');
      } else {
        text = contentEditor.getContent({ format: 'raw' });
        lang = contentEditor.settings.wp_lang_attr;
      }

      const chars = counter.count(text, 'characters_including_spaces');
      const words = counter.count(text, 'words');

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
      const p = '۰'.charCodeAt(0);
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
})(jQuery, _, new wp.utils.WordCounter());
