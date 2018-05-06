/* global autosize */

/**
  autosize 4.0.2 - 2018-05-06
  license: MIT
  http://www.jacklmoore.com/autosize
*/
!function(e,t){if("function"==typeof define&&define.amd)define(["module","exports"],t);else if("undefined"!=typeof exports)t(module,exports);else{var n={exports:{}};t(n,n.exports),e.autosize=n.exports}}(this,function(e,t){"use strict";var n,o,p="function"==typeof Map?new Map:(n=[],o=[],{has:function(e){return-1<n.indexOf(e)},get:function(e){return o[n.indexOf(e)]},set:function(e,t){-1===n.indexOf(e)&&(n.push(e),o.push(t))},delete:function(e){var t=n.indexOf(e);-1<t&&(n.splice(t,1),o.splice(t,1))}}),c=function(e){return new Event(e,{bubbles:!0})};try{new Event("test")}catch(e){c=function(e){var t=document.createEvent("Event");return t.initEvent(e,!0,!1),t}}function r(r){if(r&&r.nodeName&&"TEXTAREA"===r.nodeName&&!p.has(r)){var e,n=null,o=null,i=null,d=function(){r.clientWidth!==o&&a()},l=function(t){window.removeEventListener("resize",d,!1),r.removeEventListener("input",a,!1),r.removeEventListener("keyup",a,!1),r.removeEventListener("autosize:destroy",l,!1),r.removeEventListener("autosize:update",a,!1),Object.keys(t).forEach(function(e){r.style[e]=t[e]}),p.delete(r)}.bind(r,{height:r.style.height,resize:r.style.resize,overflowY:r.style.overflowY,overflowX:r.style.overflowX,wordWrap:r.style.wordWrap});r.addEventListener("autosize:destroy",l,!1),"onpropertychange"in r&&"oninput"in r&&r.addEventListener("keyup",a,!1),window.addEventListener("resize",d,!1),r.addEventListener("input",a,!1),r.addEventListener("autosize:update",a,!1),r.style.overflowX="hidden",r.style.wordWrap="break-word",p.set(r,{destroy:l,update:a}),"vertical"===(e=window.getComputedStyle(r,null)).resize?r.style.resize="none":"both"===e.resize&&(r.style.resize="horizontal"),n="content-box"===e.boxSizing?-(parseFloat(e.paddingTop)+parseFloat(e.paddingBottom)):parseFloat(e.borderTopWidth)+parseFloat(e.borderBottomWidth),isNaN(n)&&(n=0),a()}function s(e){var t=r.style.width;r.style.width="0px",r.offsetWidth,r.style.width=t,r.style.overflowY=e}function u(){if(0!==r.scrollHeight){var e=function(e){for(var t=[];e&&e.parentNode&&e.parentNode instanceof Element;)e.parentNode.scrollTop&&t.push({node:e.parentNode,scrollTop:e.parentNode.scrollTop}),e=e.parentNode;return t}(r),t=document.documentElement&&document.documentElement.scrollTop;r.style.height="",r.style.height=r.scrollHeight+n+"px",o=r.clientWidth,e.forEach(function(e){e.node.scrollTop=e.scrollTop}),t&&(document.documentElement.scrollTop=t)}}function a(){u();var e=Math.round(parseFloat(r.style.height)),t=window.getComputedStyle(r,null),n="content-box"===t.boxSizing?Math.round(parseFloat(t.height)):r.offsetHeight;if(n<e?"hidden"===t.overflowY&&(s("scroll"),u(),n="content-box"===t.boxSizing?Math.round(parseFloat(window.getComputedStyle(r,null).height)):r.offsetHeight):"hidden"!==t.overflowY&&(s("hidden"),u(),n="content-box"===t.boxSizing?Math.round(parseFloat(window.getComputedStyle(r,null).height)):r.offsetHeight),i!==n){i=n;var o=c("autosize:resized");try{r.dispatchEvent(o)}catch(e){}}}}function i(e){var t=p.get(e);t&&t.destroy()}function d(e){var t=p.get(e);t&&t.update()}var l=null;"undefined"==typeof window||"function"!=typeof window.getComputedStyle?((l=function(e){return e}).destroy=function(e){return e},l.update=function(e){return e}):((l=function(e,t){return e&&Array.prototype.forEach.call(e.length?e:[e],function(e){return r(e)}),e}).destroy=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],i),e},l.update=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],d),e}),t.default=l,e.exports=t.default});

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
