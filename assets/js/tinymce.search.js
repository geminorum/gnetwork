(function() {
	tinymce.create('tinymce.plugins.gNetworkSearch', {
		init: function(editor, url) {

			editor.addShortcut('ctrl+3', editor.getLang('gnetwork.gnetworksearch-title'), 'gnetworksearch');

			editor.addCommand('gnetworksearch', function() {
				var text = editor.selection.getContent();

				if ( '' == text )
					editor.insertContent('[search]');
				else
					editor.insertContent('[search]'+text+'[/search]');
			});

			editor.addButton('gnetworksearch', {
                title:   editor.getLang('gnetwork.gnetworksearch-title'),
                icon:    'icon gnetwork-tinymce-icon icon-gnetworksearch',
                onclick: function() {
					editor.execCommand('gnetworksearch');
				}
			});
		},
		
		getInfo: function() {
			return {
				longname:  "gNetwork Search",
				author:    'geminorum',
				authorurl: 'http://geminorum.ir',
				infourl:   'http://geminorum.ir/wordpress/gnetwork',
				version:   "1.0"
			};
		}
	});
	tinymce.PluginManager.add('gnetworksearch', tinymce.plugins.gNetworkSearch);
})();
