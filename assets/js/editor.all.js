/* global wp, _ */

// almost exact copy of: Default Media Uploader View v1.0.7 by leemon
// @REF: https://wordpress.org/plugins/default-media-uploader-view/

(function ($, _) {
  var media = wp.media;

  if (media) {
    media.view.MediaFrame.Select.prototype.initialize = function () {
      media.view.MediaFrame.prototype.initialize.apply(this, arguments);

      // Fix for WooCommerce Product Gallery
      this.states.forEach(function (state) {
        var library = state.get('library');
        if (library) {
          library.props.set('uploadedTo', media.view.settings.post.id);
          library.props.set('orderby', 'menuOrder');
          library.props.set('order', 'ASC');
        }
      });

      _.defaults(this.options, {
        selection: [],
        library: {
          uploadedTo: media.view.settings.post.id,
          orderby: 'menuOrder',
          order: 'ASC'
        },
        multiple: false,
        state: 'library'
      });

      this.createSelection();
      this.createStates();
      this.bindHandlers();
    };

    media.controller.FeaturedImage.prototype.initialize = function () {
      var library;
      var comparator;

      if (!this.get('library')) {
        this.set('library', media.query({type: 'image', uploadedTo: media.view.settings.post.id, orderby: 'menuOrder', order: 'ASC'}));
      }

      media.controller.Library.prototype.initialize.apply(this, arguments);

      library = this.get('library');
      comparator = library.comparator;

      library.comparator = function (a, b) {
        var aInQuery = !!this.mirroring.get(a.cid);
        var bInQuery = !!this.mirroring.get(b.cid);

        if (!aInQuery && bInQuery) {
          return -1;
        } else if (aInQuery && !bInQuery) {
          return 1;
        } else {
          return comparator.apply(this, arguments);
        }
      };

      library.observe(this.get('selection'));
    };
  }
}(jQuery, _));
