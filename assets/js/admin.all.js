/* eslint-disable */
/**
  autosize 6.0.1 - 2023-02-19
  license: MIT
  http://www.jacklmoore.com/autosize
*/
!function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t():"function"==typeof define&&define.amd?define(t):(e||self).autosize=t()}(this,function(){var e=new Map;function t(t){var o=e.get(t);o&&o.destroy()}function o(t){var o=e.get(t);o&&o.update()}var r=null;return"undefined"==typeof window?((r=function(e){return e}).destroy=function(e){return e},r.update=function(e){return e}):((r=function(t,o){return t&&Array.prototype.forEach.call(t.length?t:[t],function(t){return function(t){if(t&&t.nodeName&&"TEXTAREA"===t.nodeName&&!e.has(t)){var o,r=null,n=window.getComputedStyle(t),i=(o=t.value,function(){s({testForHeightReduction:""===o||!t.value.startsWith(o),restoreTextAlign:null}),o=t.value}),l=function(o){t.removeEventListener("autosize:destroy",l),t.removeEventListener("autosize:update",a),t.removeEventListener("input",i),window.removeEventListener("resize",a),Object.keys(o).forEach(function(e){return t.style[e]=o[e]}),e.delete(t)}.bind(t,{height:t.style.height,resize:t.style.resize,textAlign:t.style.textAlign,overflowY:t.style.overflowY,overflowX:t.style.overflowX,wordWrap:t.style.wordWrap});t.addEventListener("autosize:destroy",l),t.addEventListener("autosize:update",a),t.addEventListener("input",i),window.addEventListener("resize",a),t.style.overflowX="hidden",t.style.wordWrap="break-word",e.set(t,{destroy:l,update:a}),a()}function s(e){var o,i,l=e.restoreTextAlign,a=void 0===l?null:l,d=e.testForHeightReduction,u=void 0===d||d,f=n.overflowY;if(0!==t.scrollHeight&&("vertical"===n.resize?t.style.resize="none":"both"===n.resize&&(t.style.resize="horizontal"),u&&(o=function(e){for(var t=[];e&&e.parentNode&&e.parentNode instanceof Element;)e.parentNode.scrollTop&&t.push([e.parentNode,e.parentNode.scrollTop]),e=e.parentNode;return function(){return t.forEach(function(e){var t=e[0],o=e[1];t.style.scrollBehavior="auto",t.scrollTop=o,t.style.scrollBehavior=null})}}(t),t.style.height=""),i="content-box"===n.boxSizing?t.scrollHeight-(parseFloat(n.paddingTop)+parseFloat(n.paddingBottom)):t.scrollHeight+parseFloat(n.borderTopWidth)+parseFloat(n.borderBottomWidth),"none"!==n.maxHeight&&i>parseFloat(n.maxHeight)?("hidden"===n.overflowY&&(t.style.overflow="scroll"),i=parseFloat(n.maxHeight)):"hidden"!==n.overflowY&&(t.style.overflow="hidden"),t.style.height=i+"px",a&&(t.style.textAlign=a),o&&o(),r!==i&&(t.dispatchEvent(new Event("autosize:resized",{bubbles:!0})),r=i),f!==n.overflow&&!a)){var c=n.textAlign;"hidden"===n.overflow&&(t.style.textAlign="start"===c?"end":"start"),s({restoreTextAlign:c,testForHeightReduction:!0})}}function a(){s({testForHeightReduction:!0,restoreTextAlign:null})}}(t)}),t}).destroy=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],t),e},r.update=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],o),e}),r});
/* eslint-enable */

jQuery(function ($) {
  autosize($('#excerpt, #description, .textarea-autosize, textarea.large-text'));

  $('body').on('click', 'a[data-toggle="tab"]', function (e) {
    e.preventDefault();

    const $list = $(this).parents('.-base');
    const $target = $list.find('[data-tab="' + $(this).data('tab') + '"]');

    $list.find('.-wrapper a.nav-tab').removeClass('nav-tab-active -active');
    $list.find('.-content').hide();

    $(this).addClass('nav-tab-active -active');
    $target.fadeIn();

    autosize.update($target.find('#excerpt, #description, .textarea-autosize, textarea.large-text'));
  });

  $('a[data-tab="' + window.location.hash.slice(1) + '"]', '.-base').trigger('click');

  // FIXME: must trigger core to store current state
  if ($('#screen-meta-links').length && $('.postbox-container .postbox').length) {
    // $('<div id="gnetwork-admin-metabox-controls" class="screen-meta-toggle gnetwork-admin-metabox-controls"><button type="button" id="gnetwork-admin-metabox-toggle" class="button show-settings">' + gNetwork.metabox_controls_toggle + '</button></div>')
    //   .appendTo('#screen-meta-links');

    $('<div class="screen-meta-toggle gnetwork-admin-metabox-controls"><button type="button" id="gnetwork-admin-metabox-collapse" class="button show-settings"><span>' + gNetwork.metabox_controls_collapse + '</span></button></div>')
      .appendTo('#screen-meta-links');

    $('<div class="screen-meta-toggle gnetwork-admin-metabox-controls"><button type="button" id="gnetwork-admin-metabox-expand" class="button show-settings"><span>' + gNetwork.metabox_controls_expand + '</span></button></div>')
      .appendTo('#screen-meta-links');

    // $('body').on('click', '#gnetwork-admin-metabox-toggle', function (e) {
    //   e.preventDefault();
    //   $('.postbox-container .postbox').toggleClass('closed');
    //   // $('.postbox-container .postbox:not(.closed) .gnetwork-chosen').trigger('chosen:updated'); // FIXME: not the correct event
    // });

    $('body').on('click', '#gnetwork-admin-metabox-collapse', function (e) {
      e.preventDefault();
      $('.postbox-container .postbox:not(.closed)').addClass('closed');
    });

    $('body').on('click', '#gnetwork-admin-metabox-expand', function (e) {
      e.preventDefault();
      $('.postbox-container .postbox.closed').removeClass('closed');
      // $('.postbox-container .postbox:not(.closed) .gnetwork-chosen').trigger('chosen:updated'); // FIXME: not the correct event
    });
  }

  // Adopted from: WP Reset Filters by John James Jacoby - 0.1.0 - 20171208
  // @REF: https://wordpress.org/plugins/wp-reset-filters/
  $('#post-query-submit').addClass('button-primary')
    .after('<button class="button" id="gnetwork-reset-filters" ' + gNetwork.reset_button_disabled + '>' + gNetwork.reset_button_text + '</button>');

  $('#gnetwork-reset-filters').on('click', function (e) {
    e.preventDefault();
    window.location.href = $('#adminmenu li.current a.current').attr('href');
  });

  function populateWidgets (i, id) {
    const e = $('#' + id + ' div.inside:visible').find('.widget-loading');

    if (e.length) {
      const p = e.parent();
      setTimeout(function () {
        p.load(ajaxurl + '?action=gnetwork_dashboard&widget=' + id, '', function () {
          p.hide().slideDown('normal', function () {
            $(this).css('display', '');
          });
        });
      }, i * 500);
    }
  }

  populateWidgets(1, 'gnetwork_dashboard_external_feed');
});
