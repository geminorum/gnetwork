(function (tinymce) {
  tinymce.PluginManager.add('gnetworkgpeople', function (editor, url) {
    editor.addButton('gnetworkgpeople', {

      title: editor.getLang('gnetwork.gnetworkgpeople-attr'),
      icon: 'icon gnetwork-tinymce-icon icon-gnetworkgpeople',

      onclick: function () {
        var selected = editor.selection.getContent();

        editor.windowManager.open({
          title: editor.getLang('gnetwork.gnetworkgpeople-title'),
          minWidth: 450,
          body: [{
            type: 'textbox',
            name: 'name',
            label: editor.getLang('gnetwork.gnetworkgpeople-name'),
            value: selected
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
              e.data.name ? '[person name="' + e.data.name + '"]' : '[person]'
            ) + (
              selected ? selected + '[/person]' : ''
            ));
          }
        });
      }
    });
  });
})(window.tinymce);
