(function() {
	tinymce.create('tinymce.plugins.gnetworkemail', {
		init : function(editor, url) {
			editor.addButton('gnetworkemail', {

				title: editor.getLang('gnetwork.gnetworkemail-title'),
				icon:  'icon gnetwork-tinymce-icon icon-gnetworkemail',

				onclick: function() {
					editor.windowManager.open( {
						id: 'gnetwork-tinymce-window-gnetworkemail',
						title: editor.getLang('gnetwork.gnetworkemail-title'),
						body: [{
							id: 'gnetwork-tinymce-input-gnetworkemail-subject',
							type: 'textbox',
							name: 'subject',
							label: editor.getLang('gnetwork.gnetworkemail-subject'),
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

	tinymce.PluginManager.add('gnetworkemail', tinymce.plugins.gnetworkemail);
})();
