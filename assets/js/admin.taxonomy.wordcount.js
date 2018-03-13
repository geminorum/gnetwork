/* global jQuery, wp, _ */

(function ($, counter) {
  $(function () {
    var $content = $('#html-tag-description');
    var $chars = $('#description-editor-counts').find('.char-count');
    var $words = $('#description-editor-counts').find('.word-count');
    var prevChars = 0;
    var prevWords = 0;
    var contentEditor;

    function update () {
      var text, chars, words;

      if (!contentEditor || contentEditor.isHidden()) {
        text = $content.val();
      } else {
        text = contentEditor.getContent({format: 'raw'});
      }

      chars = counter.count(text, 'characters_including_spaces');
      words = counter.count(text, 'words');

      if (chars !== prevChars) {
        $chars.text(chars);
      }

      if (words !== prevWords) {
        $words.text(words);
      }

      prevChars = chars;
      prevWords = words;
    }

    $(document).on('tinymce-editor-init', function (event, editor) {
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
