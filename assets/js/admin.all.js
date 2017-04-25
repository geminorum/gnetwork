/**
  Autosize v3.0.20 - 2016-12-04
  license: MIT
  http://www.jacklmoore.com/autosize
*/
(function(c,a){if(typeof define==="function"&&define.amd){define(["exports","module"],a)}else{if(typeof exports!=="undefined"&&typeof module!=="undefined"){a(exports,module)}else{var b={exports:{}};a(b.exports,b);c.autosize=b.exports}}})(this,function(d,b){var a=typeof Map==="function"?new Map():(function(){var n=[];var k=[];return{has:function m(p){return n.indexOf(p)>-1},get:function l(p){return k[n.indexOf(p)]},set:function o(p,q){if(n.indexOf(p)===-1){n.push(p);k.push(q)}},"delete":function e(q){var p=n.indexOf(q);if(p>-1){n.splice(p,1);k.splice(p,1)}}}})();var h=function h(e){return new Event(e,{bubbles:true})};try{new Event("test")}catch(f){h=function(k){var e=document.createEvent("Event");e.initEvent(k,true,false);return e}}function j(n){if(!n||!n.nodeName||n.nodeName!=="TEXTAREA"||a.has(n)){return}var e=null;var r=n.clientWidth;var k=null;function t(){var u=window.getComputedStyle(n,null);if(u.resize==="vertical"){n.style.resize="none"}else{if(u.resize==="both"){n.style.resize="horizontal"}}if(u.boxSizing==="content-box"){e=-(parseFloat(u.paddingTop)+parseFloat(u.paddingBottom))}else{e=parseFloat(u.borderTopWidth)+parseFloat(u.borderBottomWidth)}if(isNaN(e)){e=0}m()}function p(v){var u=n.style.width;n.style.width="0px";n.offsetWidth;n.style.width=u;n.style.overflowY=v}function s(v){var u=[];while(v&&v.parentNode&&v.parentNode instanceof Element){if(v.parentNode.scrollTop){u.push({node:v.parentNode,scrollTop:v.parentNode.scrollTop})}v=v.parentNode}return u}function l(){var w=n.style.height;var x=s(n);var u=document.documentElement&&document.documentElement.scrollTop;n.style.height="auto";var v=n.scrollHeight+e;if(n.scrollHeight===0){n.style.height=w;return}n.style.height=v+"px";r=n.clientWidth;x.forEach(function(y){y.node.scrollTop=y.scrollTop});if(u){document.documentElement.scrollTop=u}}function m(){l();var y=Math.round(parseFloat(n.style.height));var w=window.getComputedStyle(n,null);var x=Math.round(parseFloat(w.height));if(x!==y){if(w.overflowY!=="visible"){p("visible");l();x=Math.round(parseFloat(window.getComputedStyle(n,null).height))}}else{if(w.overflowY!=="hidden"){p("hidden");l();x=Math.round(parseFloat(window.getComputedStyle(n,null).height))}}if(k!==x){k=x;var u=h("autosize:resized");try{n.dispatchEvent(u)}catch(v){}}}var o=function o(){if(n.clientWidth!==r){m()}};var q=(function(u){window.removeEventListener("resize",o,false);n.removeEventListener("input",m,false);n.removeEventListener("keyup",m,false);n.removeEventListener("autosize:destroy",q,false);n.removeEventListener("autosize:update",m,false);Object.keys(u).forEach(function(v){n.style[v]=u[v]});a["delete"](n)}).bind(n,{height:n.style.height,resize:n.style.resize,overflowY:n.style.overflowY,overflowX:n.style.overflowX,wordWrap:n.style.wordWrap});n.addEventListener("autosize:destroy",q,false);if("onpropertychange" in n&&"oninput" in n){n.addEventListener("keyup",m,false)}window.addEventListener("resize",o,false);n.addEventListener("input",m,false);n.addEventListener("autosize:update",m,false);n.style.overflowX="hidden";n.style.wordWrap="break-word";a.set(n,{destroy:q,update:m});t()}function g(k){var e=a.get(k);if(e){e.destroy()}}function c(k){var e=a.get(k);if(e){e.update()}}var i=null;if(typeof window==="undefined"||typeof window.getComputedStyle!=="function"){i=function(e){return e};i.destroy=function(e){return e};i.update=function(e){return e}}else{i=function(k,e){if(k){Array.prototype.forEach.call(k.length?k:[k],function(l){return j(l,e)})}return k};i.destroy=function(e){if(e){Array.prototype.forEach.call(e.length?e:[e],g)}return e};i.update=function(e){if(e){Array.prototype.forEach.call(e.length?e:[e],c)}return e}}b.exports=i});

jQuery(document).ready(function($) {

  autosize($('#excerpt, #description, .textarea-autosize, textarea.large-text'));

  function populateWidgets(i, id) {
    var p, e = $('#' + id + ' div.inside:visible').find('.widget-loading');
    if (e.length) {
      p = e.parent();
      setTimeout(function() {
        p.load(ajaxurl + '?action=gnetwork_dashboard&widget=' + id, '', function() {
          p.hide().slideDown('normal', function() {
            $(this).css('display', '');
          });
        });
      }, i * 500);
    }
  }

  populateWidgets(1, 'gnetwork_dashboard_external_feed');
});
