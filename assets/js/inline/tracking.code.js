// /////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////
/// gNetwork: Tracking: Code
// @REF: https://www.sitepoint.com/?p=84248
// @REF: https://support.google.com/analytics/answer/7478520?hl=en

// /////////////////////////////////////////////////////////////////////////////
// function gtag () {
//   if (typeof window.dataLayer === 'object' && typeof window.dataLayer.push === 'function') {
//     window.dataLayer.push(arguments);
//   } else {
//     window.dataLayer = [];
//     window.dataLayer.push(arguments);
//   }
// }

window.dataLayer = window.dataLayer || [];
function gtag () {
  window.dataLayer.push(arguments);
}

// @REF: https://developers.google.com/analytics/devguides/collection/gtagjs/sending-data
/* eslint-disable-next-line no-unused-vars */
function gtagCallback (callback, timeout) {
  let called = false;
  function fn () {
    if (!called) {
      called = true;
      callback();
    }
  }
  setTimeout(fn, timeout || 1000);
  return fn;
}

gtag('js', new Date());
