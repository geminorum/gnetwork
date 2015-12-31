(function() {
	tinymce.create('tinymce.plugins.gNetworkAsterisks', {
		init: function(editor, url) {
			editor.addButton('gnetworkasterisks', {
                title:   editor.getLang('gnetwork.gnetworkasterisks-title'),
                icon:    'icon gnetwork-tinymce-icon icon-gnetworkasterisks',
                onclick: function() {
					editor.selection.setContent('[three-asterisks]');
				}
			});
		},

		getInfo: function() {
			return {
				longname:  "gNetwork Asterisks",
				author:    'geminorum',
				authorurl: 'http://geminorum.ir',
				infourl:   'http://geminorum.ir/wordpress/gnetwork',
				version:   "1.0"
			};
		}
	});
	tinymce.PluginManager.add('gnetworkasterisks', tinymce.plugins.gNetworkAsterisks);
})();
