(function() {
	tinymce.create('tinymce.plugins.gNetworkCite', {
		init : function(editor, url) {

			editor.addShortcut('ctrl+q', editor.getLang('gnetwork.gnetworkcite-title'), 'gnetworkcite');

			editor.addCommand('gnetworkcite', function() {
				var text = editor.selection.getContent();

				if ( '' == text )
					editor.insertContent('[ref]');
				else
					editor.insertContent('[ref]'+text+'[/ref]');
			});

			editor.addButton('gnetworkcite', {

				title: editor.getLang('gnetwork.gnetworkcite-title'),
				icon:  'icon gnetwork-tinymce-icon icon-gnetworkcite',

				onclick: function() {

					editor.windowManager.open( {
                        id:    'gnetwork-tinymce-window-gnetworkcite',
                        title: editor.getLang('gnetwork.gnetworkcite-title'),
                        body:  [{
                            id:    'gnetwork-tinymce-input-gnetworkcite-refurl',
                            type:  'textbox',
                            name:  'refurl',
                            label: editor.getLang('gnetwork.gnetworkcite-url'),
                            value: 'http://',
						}],
						onsubmit: function(e) {

							var text = editor.selection.getContent(),
								refurl = e.data.refurl;

							if (!(text != null && text != '') && !(refurl != null && refurl != '' && refurl != "http://"))
								return;

							if (text != null && text != '') {
								if (refurl != null && refurl != '' && refurl != "http://")
									editor.insertContent('[ref url="'+encodeURI(refurl)+'"]'+text+'[/ref]');
								else
									editor.insertContent('[ref]'+text+'[/ref]');
							} else {
								if (refurl != null && refurl != '' && refurl != "http://")
									editor.insertContent('[ref url="'+encodeURI(refurl)+'" /]');
								else
									editor.insertContent('[ref]');
							}
						}
					});
				}
			});
		},

		getInfo: function() {
			return {
				longname:  "gNetwork Citation",
				author:    'geminorum',
				authorurl: 'http://geminorum.ir',
				infourl:   'http://geminorum.ir/wordpress/gnetwork',
				version:   "1.0"
			};
		}
	});

	tinymce.PluginManager.add('gnetworkcite', tinymce.plugins.gNetworkCite);
})();
