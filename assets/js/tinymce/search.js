(function (tinymce) {
  tinymce.PluginManager.add('gnetworksearch', function (editor, url) {
    editor.addShortcut('ctrl+3', editor.getLang('gnetwork.gnetworksearch-title'), 'gnetworksearch');

    editor.addCommand('gnetworksearch', function () {
      var text = editor.selection.getContent();
      editor.insertContent('[search]' + (text ? text + '[/search]' : ''));
    });

    editor.addButton('gnetworksearch', {

      title: editor.getLang('gnetwork.gnetworksearch-attr'),
      icon: 'icon gnetwork-tinymce-icon icon-gnetworksearch',

      onclick: function () {
        var selected = editor.selection.getContent();

        editor.windowManager.open({
          title: editor.getLang('gnetwork.gnetworksearch-title'),
          minWidth: 450,
          body: [{
            type: 'textbox',
            name: 'text',
            label: editor.getLang('gnetwork.gnetworksearch-text'),
            value: selected
          }, {
            type: 'textbox',
            name: 'query',
            label: editor.getLang('gnetwork.gnetworksearch-query'),
            autofocus: selected
          }],
          buttons: [{
            text: 'Insert',
            subtype: 'primary',
            onclick: 'submit'
          }, {
            text: 'Close',
            onclick: 'close'
          }],
          onsubmit: function (e) {
            editor.insertContent((
              e.data.query ? '[search for="' + e.data.query + '"]' : '[search]'
            ) + (
              e.data.text ? e.data.text + '[/search]' : ''
            ));
          }
        });
      }
    });
  });
})(window.tinymce);
