(function(tinymce) {

	tinymce.PluginManager.add('gnetworkemail', function(editor, url) {
		editor.addShortcut('ctrl+e', editor.getLang('gnetwork.gnetworkemail-title'), 'gnetworkemail');

		editor.addCommand('gnetworkemail', function() {
			var text = editor.selection.getContent();
			editor.insertContent('[email]' + (text ? text + '[/email]' : ''));
		});

		editor.addButton('gnetworkemail', {

			title: editor.getLang('gnetwork.gnetworkemail-attr'),
			icon: 'icon gnetwork-tinymce-icon icon-gnetworkemail',

			onclick: function() {

				var selected = editor.selection.getContent();

				editor.windowManager.open({
					title: editor.getLang('gnetwork.gnetworkemail-title'),
					minWidth: 450,
					body: [{
						type: 'textbox',
						name: 'email',
						label: editor.getLang('gnetwork.gnetworkemail-email'),
						value: selected,
						style: 'direction:ltr;text-align:left;',
					}, {
						type: 'textbox',
						name: 'text',
						label: editor.getLang('gnetwork.gnetworkemail-text'),
						value: selected,
					}, {
						type: 'textbox',
						name: 'subject',
						label: editor.getLang('gnetwork.gnetworkemail-subject'),
						autofocus: selected,
					}, {
						type: 'textbox',
						name: 'hover',
						label: editor.getLang('gnetwork.gnetworkemail-hover'),
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

						var open = '[email' + (
							e.data.subject ? ' subject="' + e.data.subject + '"' : ''
						) + (
							e.data.hover ? ' title="' + e.data.hover + '"' : ''
						);

						if (e.data.email && e.data.text && e.data.text == e.data.email)
							editor.insertContent(open + ']' + e.data.email + '[/email]');

						else if (!e.data.text)
							editor.insertContent(open + ']' + e.data.email + '[/email]');

						else if (!e.data.email)
							editor.insertContent(open + ' content="' + e.data.text + '" /]');

						else
							editor.insertContent(open + ' email="' + e.data.email + '"]' + e.data.text + '[/email]');
					}
				});
			}
		});
	});
})(window.tinymce);
