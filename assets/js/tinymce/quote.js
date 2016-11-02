(function(tinymce) {

	tinymce.PluginManager.add('gnetworkquote', function(editor, url) {
		editor.addButton('gnetworkquote', {

			title: editor.getLang('gnetwork.gnetworkquote-attr'),
			icon: 'icon gnetwork-tinymce-icon icon-gnetworkquote',

			onclick: function() {

				var selected = editor.selection.getContent();

				editor.windowManager.open({
					title: editor.getLang('gnetwork.gnetworkquote-title'),
					minWidth: 450,
					body: [{
						type: 'textbox',
						name: 'text',
						label: editor.getLang('gnetwork.gnetworkquote-text'),
						value: selected,
						multiline: true,
						minHeight: 130,
					}, {
						type: 'textbox',
						name: 'cite',
						label: editor.getLang('gnetwork.gnetworkquote-cite'),
						autofocus: selected,
					}, {
						type: 'textbox',
						name: 'url',
						label: editor.getLang('gnetwork.gnetworkquote-url'),
						style: 'direction:ltr;text-align:left;',
					}, {
						type: 'listbox',
						name: 'align',
						label: editor.getLang('gnetwork.gnetworkquote-align'),
						'values': [{
							text: 'None',
							value: 'none'
						}, {
							text: 'Left',
							value: 'left'
						}, {
							text: 'Right',
							value: 'right'
						}, {
							text: 'Center',
							value: 'center'
						}]
					}, {
						type: 'radio',
						name: 'intro',
						label: editor.getLang('gnetwork.gnetworkquote-intro'),
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
						if (e.data.text) {

							var open = '<blockquote>';

							if (e.data.intro) {
								open = 'none' == e.data.align ? '<blockquote class="intro-quote">' : '<blockquote class="intro-quote align' + e.data.align + '">';
							} else {
								open = 'none' == e.data.align ? '<blockquote>' : '<blockquote class="align' + e.data.align + '">';
							}

							if (e.data.cite) {
								if (e.data.url) {
									editor.insertContent(open + e.data.text + '<br /><cite><a href="' + e.data.url + '">' + e.data.cite + '</a></cite></blockquote>');
								} else {
									editor.insertContent(open + e.data.text + '<br /><cite>' + e.data.cite + '</cite></blockquote>');
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
