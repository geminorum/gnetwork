/* global gtag, gtagCallback */
// /////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////
/// gNetwork: Tracking: Login
// @REF: https://developers.google.com/analytics/devguides/collection/gtagjs/sending-data

// /////////////////////////////////////////////////////////////////////////////
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('loginform');

    form.addEventListener('submit', function (event) {
      event.preventDefault();

      gtag('event', 'login', {
        'transport_type': 'beacon',
        'event_callback': gtagCallback(function () {
          form.submit();
        })
      });
    });
  });
})();
