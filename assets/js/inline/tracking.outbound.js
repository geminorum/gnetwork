/* global gtag, gtagCallback */
// /////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////
/// gNetwork: Tracking: Outbound
// @REF: https://www.sitepoint.com/?p=84248
// @REF: https://support.google.com/analytics/answer/7478520?hl=en

// /////////////////////////////////////////////////////////////////////////////
(function () {
  document.addEventListener('click', function (event) {
    if (typeof gtag !== 'function' || event.isDefaultPrevented) {
      return;
    }

    const link = event.target.closest('a');

    if (!link || window.location.host === link.host) {
      return;
    }

    event.preventDefault();

    gtag('event', 'click', {
      event_category: 'outbound',
      event_label: link.href,
      transport_type: 'beacon',
      event_callback: gtagCallback(function () {
        document.location = link.href;
      })
    });
  }, false);
})();
