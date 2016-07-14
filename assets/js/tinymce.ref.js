(function() {
	tinymce.PluginManager.add('gnetworkref', function(editor, url) {

		editor.addShortcut('ctrl+q', editor.getLang('gnetwork.gnetworkref-title'), 'gnetworkref');

		editor.addCommand('gnetworkref', function() {
			var text = editor.selection.getContent();
			editor.insertContent('[ref]' + (text ? text : '' ) + '[/ref]');
		});

		editor.addButton('gnetworkref', {

			title: editor.getLang('gnetwork.gnetworkref-attr'),
			icon: 'icon gnetwork-tinymce-icon icon-gnetworkref',

			onclick: function() {

				var selected = editor.selection.getContent();

				editor.windowManager.open({
					title: editor.getLang('gnetwork.gnetworkref-title'),
					minWidth: 450,
					body: [{
						type: 'textbox',
						name: 'text',
						label: editor.getLang('gnetwork.gnetworkref-text'),
						value: selected,
						multiline: true,
						minHeight: 130,
					}, {
						type: 'textbox',
						name: 'url',
						label: editor.getLang('gnetwork.gnetworkref-url'),
						style: 'direction:ltr;text-align:left;',
						autofocus: selected,
					}],
					buttons: [{
						text: 'Insert',
						subtype: 'primary',
						onclick: 'submit'
					}, {
						text: 'Close',
						onclick: 'close'
					}],
					onsubmit: function(e) {
						editor.insertContent((
							e.data.url ? '[ref url="' + encodeURI(e.data.url) + '"]' : '[ref]'
						) + (
							e.data.text ? e.data.text + '[/ref]' : '[/ref]'
						));
					}
				});
			}
		});
	});
})();
