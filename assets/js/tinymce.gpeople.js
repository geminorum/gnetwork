(function() {
	tinymce.create('tinymce.plugins.gnetworkgpeople', {

		init : function(editor, url) {
			editor.addButton('gnetworkgpeople', {

				title: editor.getLang('gnetwork.gnetworkgpeople-title'),
				icon:  'icon gnetwork-tinymce-icon icon-gnetworkgpeople',

				onclick : function() {
					editor.windowManager.open( {
						id: 'gnetwork-tinymce-window-gnetworkgpeople',
						title: editor.getLang('gnetwork.gnetworkgpeople-title'),
						body: [{
							type: 'textbox',
							name: 'name',
							label: editor.getLang('gnetwork.gnetworkgpeople-name'),
						}],
						onsubmit: function( e ) {

							var text = editor.selection.getContent(),
								name = e.data.name;

							if  ( ! (text != null && text != '') && ! (name != null && name != '' ) )
								return;

							if (text != null && text != '') {
									if (name != null && name != '' )
									editor.insertContent( '[person name="'+name+'"]'+text+'[/person]' );
								else
									editor.insertContent( '[person]'+text+'[/person]' );
							} else {
								if ( name != null && name != '' )
									editor.insertContent( '[person name="'+name+'" /]' );
								else
									editor.insertContent( '[person]' );
							}
						}
					});
				}
			});
		},

		createControl : function(n, cm) {
			return null;
		},

		getInfo : function() {
			return {
				longname:  "gNetwork People",
				author:    'geminorum',
				authorurl: 'http://geminorum.ir',
				infourl:   'http://geminorum.ir/wordpress/gnetwork',
				version:   "1.0"
			};
		}
	});

	tinymce.PluginManager.add('gnetworkgpeople', tinymce.plugins.gnetworkgpeople);
})();
