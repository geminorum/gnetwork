(function (tinymce) {
  const htmlEscape = function (str) {
    return str.replace(/&/g, '&amp;') // Removing )
      .replace(/"/g, '&quot;') // Removing "
      .replace(/'/g, '&#39;') // Removing '
      .replace(/</g, '&lt;') // Removing <
      .replace(/>/g, '&gt;') // Removing >
      .replace(/\[/g, '&#91;') // Removing [
      .replace(/\]/g, '&#93;') // Removing ]
      .replace(/\n/g, '<br />') // Replacing \n to <br />
      .replace(/\t/g, '&nbsp;‌‌‌‌‌‌‌‌ &nbsp;‌‌‌‌‌‌‌‌ &nbsp;‌‌‌‌‌‌‌‌ &nbsp;‌‌‌‌‌‌‌‌ '); // A little Wordpress hack to display white spaces without being removed (note the space between each &nbsp;)
  };

  tinymce.PluginManager.add('gnetworkprismjs', function (editor, url) {
    editor.addButton('gnetworkprismjs', {

      title: editor.getLang('gnetwork.gnetworkprismjs-title'),
      icon: 'icon gnetwork-tinymce-icon icon-gnetworkprismjs',

      onclick: function () {
        editor.windowManager.open({
          title: editor.getLang('gnetwork.gnetworkprismjs-window'),
          body: [{
            type: 'textbox',
            name: 'codeInput',
            label: editor.getLang('gnetwork.gnetworkprismjs-input'),
            value: '',
            multiline: true,
            minWidth: 720,
            minHeight: 360,
            style: 'direction:ltr;text-align:left;'
          },
          {
            type: 'listbox',
            name: 'languagesName',
            label: editor.getLang('gnetwork.gnetworkprismjs-lang'),
            values: [
              { text: 'Markup (HTML)', value: 'markup' },
              { text: 'CSS', value: 'css' },
              { text: 'Javascript', value: 'javascript' },
              { text: 'PHP', value: 'php' },
              { text: 'SCSS', value: 'scss' },
              { text: 'Bash', value: 'bash' },
              { text: 'C', value: 'c' },
              { text: 'C++', value: 'cpp' },
              { text: 'Python', value: 'python' },
              { text: 'SQL', value: 'sql' },
              { text: 'Ruby', value: 'ruby' },
              { text: 'C#', value: 'csharp' },
              { text: 'Swift', value: 'swift' }
            ]
          },
          {
            type: 'textbox',
            name: 'prismHeight',
            label: editor.getLang('gnetwork.gnetworkprismjs-height'),
            style: 'direction:ltr;text-align:left;',
            value: '750px'
          },
          {
            type: 'textbox',
            name: 'filename',
            label: editor.getLang('gnetwork.gnetworkprismjs-file'),
            style: 'direction:ltr;text-align:left;',
            value: ''
          }],
          onsubmit: function (e) {
            // editor.insertContent('[prismjs language="' + e.data.languagesName + '" height="' + e.data.prismHeight + '" filename="' + e.data.filename + '"]' + htmlEscape(e.data.codeInput) + '<br /><br />[/prismjs]');
            editor.insertContent('<pre data-prism="yes" class="line-numbers" data-filename="' + e.data.filename + '" style="max-height:' + e.data.prismHeight + '"><code class="language-' + e.data.languagesName + '">' + htmlEscape(e.data.codeInput) + '</code></pre>[prismjs]');
          }
        });
      }
    });
  });
})(window.tinymce);
