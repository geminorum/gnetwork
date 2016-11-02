(function(tinymce) {
	
	tinymce.PluginManager.add('gnetworkasterisks', function(editor, url) {
		
		editor.addButton('gnetworkasterisks', {
			
			title: editor.getLang('gnetwork.gnetworkasterisks-title'),
			icon: 'icon gnetwork-tinymce-icon icon-gnetworkasterisks',

			onclick: function() {
				editor.selection.setContent('[three-asterisks]');
			}
		});
	});
})(window.tinymce);
