/* global jQuery, ajaxurl, autosize, gNetwork */

/**
  Autosize 4.0.0 - 2017-07-25
  license: MIT
  http://www.jacklmoore.com/autosize
*/
!function(e,t){if("function"==typeof define&&define.amd)define(["exports","module"],t);else if("undefined"!=typeof exports&&"undefined"!=typeof module)t(exports,module);else{var n={exports:{}};t(n.exports,n),e.autosize=n.exports}}(this,function(e,t){"use strict";function n(e){function t(){var t=window.getComputedStyle(e,null);"vertical"===t.resize?e.style.resize="none":"both"===t.resize&&(e.style.resize="horizontal"),s="content-box"===t.boxSizing?-(parseFloat(t.paddingTop)+parseFloat(t.paddingBottom)):parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),isNaN(s)&&(s=0),l()}function n(t){var n=e.style.width;e.style.width="0px",e.offsetWidth,e.style.width=n,e.style.overflowY=t}function o(e){for(var t=[];e&&e.parentNode&&e.parentNode instanceof Element;)e.parentNode.scrollTop&&t.push({node:e.parentNode,scrollTop:e.parentNode.scrollTop}),e=e.parentNode;return t}function r(){var t=e.style.height,n=o(e),r=document.documentElement&&document.documentElement.scrollTop;e.style.height="";var i=e.scrollHeight+s;return 0===e.scrollHeight?void(e.style.height=t):(e.style.height=i+"px",u=e.clientWidth,n.forEach(function(e){e.node.scrollTop=e.scrollTop}),void(r&&(document.documentElement.scrollTop=r)))}function l(){r();var t=Math.round(parseFloat(e.style.height)),o=window.getComputedStyle(e,null),i="content-box"===o.boxSizing?Math.round(parseFloat(o.height)):e.offsetHeight;if(i!==t?"hidden"===o.overflowY&&(n("scroll"),r(),i="content-box"===o.boxSizing?Math.round(parseFloat(window.getComputedStyle(e,null).height)):e.offsetHeight):"hidden"!==o.overflowY&&(n("hidden"),r(),i="content-box"===o.boxSizing?Math.round(parseFloat(window.getComputedStyle(e,null).height)):e.offsetHeight),a!==i){a=i;var l=d("autosize:resized");try{e.dispatchEvent(l)}catch(e){}}}if(e&&e.nodeName&&"TEXTAREA"===e.nodeName&&!i.has(e)){var s=null,u=e.clientWidth,a=null,c=function(){e.clientWidth!==u&&l()},p=function(t){window.removeEventListener("resize",c,!1),e.removeEventListener("input",l,!1),e.removeEventListener("keyup",l,!1),e.removeEventListener("autosize:destroy",p,!1),e.removeEventListener("autosize:update",l,!1),Object.keys(t).forEach(function(n){e.style[n]=t[n]}),i.delete(e)}.bind(e,{height:e.style.height,resize:e.style.resize,overflowY:e.style.overflowY,overflowX:e.style.overflowX,wordWrap:e.style.wordWrap});e.addEventListener("autosize:destroy",p,!1),"onpropertychange"in e&&"oninput"in e&&e.addEventListener("keyup",l,!1),window.addEventListener("resize",c,!1),e.addEventListener("input",l,!1),e.addEventListener("autosize:update",l,!1),e.style.overflowX="hidden",e.style.wordWrap="break-word",i.set(e,{destroy:p,update:l}),t()}}function o(e){var t=i.get(e);t&&t.destroy()}function r(e){var t=i.get(e);t&&t.update()}var i="function"==typeof Map?new Map:function(){var e=[],t=[];return{has:function(t){return e.indexOf(t)>-1},get:function(n){return t[e.indexOf(n)]},set:function(n,o){e.indexOf(n)===-1&&(e.push(n),t.push(o))},delete:function(n){var o=e.indexOf(n);o>-1&&(e.splice(o,1),t.splice(o,1))}}}(),d=function(e){return new Event(e,{bubbles:!0})};try{new Event("test")}catch(e){d=function(e){var t=document.createEvent("Event");return t.initEvent(e,!0,!1),t}}var l=null;"undefined"==typeof window||"function"!=typeof window.getComputedStyle?(l=function(e){return e},l.destroy=function(e){return e},l.update=function(e){return e}):(l=function(e,t){return e&&Array.prototype.forEach.call(e.length?e:[e],function(e){return n(e,t)}),e},l.destroy=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],o),e},l.update=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],r),e}),t.exports=l});

jQuery(function ($) {
  autosize($('#excerpt, #description, .textarea-autosize, textarea.large-text'));

  $('body').on('click', 'a[data-toggle="tab"]', function (e) {
    e.preventDefault();

    var $list = $(this).parents('.-base');
    var $target = $list.find('[data-tab="' + $(this).data('tab') + '"]');

    $list.find('.-wrapper a.nav-tab').removeClass('nav-tab-active -active');
    $list.find('.-content').hide();

    $(this).addClass('nav-tab-active -active');
    $target.fadeIn();

    autosize.update($target.find('#excerpt, #description, .textarea-autosize, textarea.large-text'));
  });

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
    var p;
    var e = $('#' + id + ' div.inside:visible').find('.widget-loading');

    if (e.length) {
      p = e.parent();
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
