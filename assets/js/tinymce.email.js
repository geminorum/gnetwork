(function() {
	tinymce.create('tinymce.plugins.gnetworkgemail', {
		init : function(editor, url) {
			editor.addButton('gnetworkgemail', {

				title: editor.getLang('gnetwork.gnetworkgemail-title'),
				icon:  'icon gnetwork-tinymce-icon icon-gnetworkgemail',

				onclick: function() {
					editor.windowManager.open( {
						id: 'gnetwork-tinymce-window-gnetworkgemail',
				        title: editor.getLang('gnetwork.gnetworkgemail-title'),
				        body: [{
							id: 'gnetwork-tinymce-input-gnetworkgemail-subject',
				            type: 'textbox',
				            name: 'subject',
				            label: editor.getLang('gnetwork.gnetworkgemail-subject'),
				        }],
				        onsubmit: function( e ) {

							var text = editor.selection.getContent(),
								subject = e.data.subject;

							if  ( ! ( text != null && text != '') && ! ( subject != null && subject != '' ) )
								return;

							if ( text != null && text != '' ) {
								if ( subject != null && subject != '')
									editor.insertContent( '[email subject="'+subject+'"]'+text+'[/email]' );
								else
									editor.insertContent( '[email]'+text+'[/email]' );
							} else {
								if ( subject != null && subject != '' )
									editor.insertContent( '[email subject="'+subject+'" /]' );
								else
									editor.insertContent( '[email]' );
							}
				        }
				    });
				}
			});
		},
		createControl: function(n, cm) {
			return null;
		},
		getInfo: function() {
			return {
				longname:  "gNetwork Email",
				author:    'geminorum',
				authorurl: 'http://geminorum.ir',
				infourl:   'http://geminorum.ir/wordpress/gnetwork',
				version:   "1.0"
			};
		}
	});

	tinymce.PluginManager.add('gnetworkgemail', tinymce.plugins.gnetworkgemail);
})();
