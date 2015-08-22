(function() {
	tinymce.create('tinymce.plugins.gnetworkasterisks', {
		init: function(editor, url) {
			editor.addButton('gnetworkasterisks', {
				title:   editor.getLang('gnetwork.gnetworkasterisks-title'),
				icon:    'icon geditorial-tinymce-icon icon-gnetworkasterisks',
				icon:  'icon gnetwork-tinymce-icon icon-gnetworkasterisks',
				onclick: function() {
					editor.selection.setContent('[three-asterisks]');
				}
			});
		},
		createControl: function(n, cm) {
			return null;
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
	tinymce.PluginManager.add('gnetworkasterisks', tinymce.plugins.gnetworkasterisks);
})();
