(function() {
   tinymce.create('tinymce.plugins.gnetworkcite', {
      init : function(ed, url) {
         ed.addButton('gnetworkcite', {
            title : 'Cite This',
            image : url+'/tinymce.cite.png',
            onclick : function() {
               var refurl = prompt("URL", "http://");
               var text = ed.selection.getContent();

			   if  ( ! (text != null && text != '') && ! (refurl != null && refurl != '' && refurl != "http://" ) )
				return;

               if (text != null && text != ''){
                  if (refurl != null && refurl != '' && refurl != "http://" )
                     ed.execCommand('mceInsertContent', false, '[ref url="'+encodeURI(refurl)+'"]'+text+'[/ref]');
                  else
                     ed.execCommand('mceInsertContent', false, '[ref]'+text+'[/ref]');
               }
               else{
                  if (refurl != null && refurl != '' && refurl != "http://" )
                     ed.execCommand('mceInsertContent', false, '[ref url="'+encodeURI(refurl)+'" /]');
                  else
                     ed.execCommand('mceInsertContent', false, '[ref]');
               }
            }
         });
      },
      createControl : function(n, cm) {
         return null;
      },
      getInfo : function() {
         return {
            longname : "gNetwork Citation",
            author : 'geminorum',
            authorurl : 'http://geminorum.ir',
            infourl : 'http://geminorum.ir/wordpress/gnetwork',
            version : "1.0"
         };
      }
   });
   tinymce.PluginManager.add('gnetworkcite', tinymce.plugins.gnetworkcite);
})();
