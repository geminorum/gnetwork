(function() {
   tinymce.create('tinymce.plugins.gnetworkgpeople', {
      init : function(ed, url) {
         ed.addButton('gnetworkgpeople', {
            title : 'People',
            image : url+'/tinymce.gpeople.png',
            onclick : function() {
               var name = prompt("Name", "");
               //var refurl = null;
               var text = ed.selection.getContent();

			   if  ( ! (text != null && text != '') && ! (name != null && name != '' ) )
					return;

               if (text != null && text != ''){
                  if (name != null && name != '' )
                     ed.execCommand('mceInsertContent', false, '[person name="'+name+'"]'+text+'[/person]');
                  else
                     ed.execCommand('mceInsertContent', false, '[person]'+text+'[/person]');
               }
               else{
                  if (name != null && name != '' )
                     ed.execCommand('mceInsertContent', false, '[person name="'+name+'" /]');
                  else
                     ed.execCommand('mceInsertContent', false, '[person]');
               }
            }
         });
      },
      createControl : function(n, cm) {
         return null;
      },
      getInfo : function() {
         return {
            longname : "gNetwork People",
            author : 'geminorum',
            authorurl : 'http://geminorum.ir',
            infourl : 'http://geminorum.ir/wordpress/gnetwork',
            version : "1.0"
         };
      }
   });
   tinymce.PluginManager.add('gnetworkgpeople', tinymce.plugins.gnetworkgpeople);
})();
