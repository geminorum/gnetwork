(function (tinymce) {
  tinymce.PluginManager.add('gnetworkquote', function (editor, url) {
    editor.addButton('gnetworkquote', {

      title: editor.getLang('gnetwork.gnetworkquote-attr'),
      icon: 'icon gnetwork-tinymce-icon icon-gnetworkquote',

      onclick: function () {
        var selected = editor.selection.getContent();

        editor.windowManager.open({
          title: editor.getLang('gnetwork.gnetworkquote-title'),
          minWidth: 450,
          body: [
            {
              type: 'textbox',
              name: 'text',
              label: editor.getLang('gnetwork.gnetworkquote-text'),
              value: selected,
              multiline: true,
              minHeight: 130
            },
            {
              type: 'textbox',
              name: 'cite',
              label: editor.getLang('gnetwork.gnetworkquote-cite'),
              autofocus: selected
            },
            {
              type: 'textbox',
              name: 'url',
              label: editor.getLang('gnetwork.gnetworkquote-url'),
              style: 'direction:ltr;text-align:left;'
            },
            {
              type: 'radio',
              name: 'epigraph',
              label: editor.getLang('gnetwork.gnetworkquote-epigraph')
            },
            {
              type: 'radio',
              name: 'rev',
              label: editor.getLang('gnetwork.gnetworkquote-rev')
            },
            {
              type: 'listbox',
              name: 'align',
              label: editor.getLang('gnetwork.gnetworkquote-align'),
              'values': [
                {
                  text: 'None',
                  value: 'none'
                },
                {
                  text: 'Left',
                  value: 'left'
                },
                {
                  text: 'Right',
                  value: 'right'
                },
                {
                  text: 'Center',
                  value: 'center'
                }
              ]
            }
          ],
          buttons: [
            {
              text: 'Insert',
              subtype: 'primary',
              onclick: 'submit'
            },
            {
              text: 'Close',
              onclick: 'close'
            }
          ],
          onsubmit: function (e) {
            if (e.data.text) {
              var classes = [];

              if (e.data.epigraph) {
                classes.push('epigraph');
                classes.push('-epigraph');
              }

              if (e.data.rev) {
                classes.push('blockquote-reverse');
                classes.push('-reverse');
              }

              if (e.data.align !== 'none') {
                classes.push('align' + e.data.align);
                classes.push('-align-' + e.data.align);
              }

              var open = classes.length > 0 ? '<blockquote class="' + classes.join(' ') + '">' : '<blockquote>';

              if (e.data.cite) {
                if (e.data.url) {
                  editor.insertContent(open + e.data.text + '<footer><cite><a href="' + e.data.url + '">' + e.data.cite + '</a></cite></footer></blockquote>');
                } else {
                  editor.insertContent(open + e.data.text + '<footer><cite>' + e.data.cite + '</cite></footer></blockquote>');
                }
              } else {
                editor.insertContent(open + e.data.text + '</blockquote>');
              }
            }
          }
        });
      }
    });
  });
})(window.tinymce);
