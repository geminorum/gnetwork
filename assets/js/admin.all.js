/* eslint-disable */
/**
  autosize 4.0.3 - 2021-05-16
  license: MIT
  http://www.jacklmoore.com/autosize
*/
!function(e,t){"function"==typeof define&&define.amd?define(["module","exports"],t):"undefined"!=typeof exports?t(module,exports):(t(t={exports:{}},t.exports),e.autosize=t.exports)}(this,function(e,t){"use strict";var n,o,a="function"==typeof Map?new Map:(n=[],o=[],{has:function(e){return-1<n.indexOf(e)},get:function(e){return o[n.indexOf(e)]},set:function(e,t){-1===n.indexOf(e)&&(n.push(e),o.push(t))},delete:function(e){e=n.indexOf(e);-1<e&&(n.splice(e,1),o.splice(e,1))}}),p=function(e){return new Event(e,{bubbles:!0})};try{new Event("test")}catch(e){p=function(e){var t=document.createEvent("Event");return t.initEvent(e,!0,!1),t}}function r(o){var n,r,i,e,d,t;function l(e){var t=o.style.width;o.style.width="0px",o.offsetWidth,o.style.width=t,o.style.overflowY=e}function s(){var e,t;0!==o.scrollHeight&&(e=function(e){for(var t=[];e&&e.parentNode&&e.parentNode instanceof Element;)e.parentNode.scrollTop&&t.push({node:e.parentNode,scrollTop:e.parentNode.scrollTop}),e=e.parentNode;return t}(o),t=document.documentElement&&document.documentElement.scrollTop,o.style.height="",o.style.height=o.scrollHeight+n+"px",r=o.clientWidth,e.forEach(function(e){e.node.scrollTop=e.scrollTop}),t&&(document.documentElement.scrollTop=t))}function u(){s();var e=Math.round(parseFloat(o.style.height)),t=window.getComputedStyle(o,null),n="content-box"===t.boxSizing?Math.round(parseFloat(t.height)):o.offsetHeight;if(n<e?"hidden"===t.overflowY&&(l("scroll"),s(),n="content-box"===t.boxSizing?Math.round(parseFloat(window.getComputedStyle(o,null).height)):o.offsetHeight):"hidden"!==t.overflowY&&(l("hidden"),s(),n="content-box"===t.boxSizing?Math.round(parseFloat(window.getComputedStyle(o,null).height)):o.offsetHeight),i!==n){i=n;n=p("autosize:resized");try{o.dispatchEvent(n)}catch(e){}}}o&&o.nodeName&&"TEXTAREA"===o.nodeName&&!a.has(o)&&(i=r=n=null,e=function(){o.clientWidth!==r&&u()},d=function(t){window.removeEventListener("resize",e,!1),o.removeEventListener("input",u,!1),o.removeEventListener("keyup",u,!1),o.removeEventListener("autosize:destroy",d,!1),o.removeEventListener("autosize:update",u,!1),Object.keys(t).forEach(function(e){o.style[e]=t[e]}),a.delete(o)}.bind(o,{height:o.style.height,resize:o.style.resize,overflowY:o.style.overflowY,overflowX:o.style.overflowX,wordWrap:o.style.wordWrap}),o.addEventListener("autosize:destroy",d,!1),"onpropertychange"in o&&"oninput"in o&&o.addEventListener("keyup",u,!1),window.addEventListener("resize",e,!1),o.addEventListener("input",u,!1),o.addEventListener("autosize:update",u,!1),o.style.overflowX="hidden",o.style.wordWrap="break-word",a.set(o,{destroy:d,update:u}),"vertical"===(t=window.getComputedStyle(o,null)).resize?o.style.resize="none":"both"===t.resize&&(o.style.resize="horizontal"),n="content-box"===t.boxSizing?-(parseFloat(t.paddingTop)+parseFloat(t.paddingBottom)):parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),isNaN(n)&&(n=0),u())}function i(e){e=a.get(e);e&&e.destroy()}function d(e){e=a.get(e);e&&e.update()}var l=null;"undefined"==typeof window||"function"!=typeof window.getComputedStyle?((l=function(e){return e}).destroy=function(e){return e},l.update=function(e){return e}):((l=function(e,t){return e&&Array.prototype.forEach.call(e.length?e:[e],r),e}).destroy=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],i),e},l.update=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],d),e}),t.default=l,e.exports=t.default});
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
