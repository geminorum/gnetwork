/**
 * Appends playback buttons to the Audio Player, Video Player & PLaylist.
 * Based on original by Daron Spence & Lewis Cowles
 *
 * Media Playback Speed v1.2.2 - 2024-01-29
 * @source https://wordpress.org/plugins/media-playback-speed/
 */

// contain all JS effects of this plugin so we don't break sites
(function () {
  // window.load is unfortunately the best handler to attach to for
  // allowing media-element-js to initialize
  window.addEventListener('load', function () {
    const buttons = document.createRange().createContextualFragment(
      document.querySelector('#playback-buttons-template').innerHTML
    );

    const els = [].slice.call(document.querySelectorAll('.mejs-container'));

    els.forEach(function (elem, i) {
      const mediaTag = elem.querySelector('audio,video');

      // buttons for me-js element
      [].slice.call(
        buttons.querySelectorAll('.playback-rate-button')
      ).forEach(function (elem) {
        elem.setAttribute('aria-controls', mediaTag.id);
      });

      // parent for controls
      const controls = elem.querySelector('.mejs-controls');

      if (controls) {
        const container = controls.querySelector('.mejs-duration-container');
        const hasSpeedControls = controls.querySelector('.playback-rate-button');

        // Guard to ensure that this only affects as-yet unaffected elements
        if (!hasSpeedControls) {
          // insertAfter container
          container.parentNode.insertBefore(buttons.cloneNode(true), container.nextSibling);
        }
      }
    });
  });

  [].slice.call(document.querySelectorAll('audio,video')).forEach(function (elem) {
    // when media is loaded persist the playback speed currently selected
    elem.addEventListener('loadedmetadata', function (e) {
      const wpPlayer = e.target.closest('.mejs-container');
      let activeSpeed;
      let rate;

      if (wpPlayer) {
        // WordPress Playlist state restore selected speed
        activeSpeed = wpPlayer.querySelector('.playback-rate-button.mejs-active');
        rate = activeSpeed.dataset.value;
      } else {
        // Any media-element, getting the first
        activeSpeed = document.querySelector('.playback-rate-button.mejs-active, .playback-rate-button.active-playback-rate');
        if (activeSpeed) {
          rate = activeSpeed.dataset.value;
        }
      }

      // Guard against failing matchers. The DOM must be fulfilled,
      // but this also means this part maybe doesn't need media-element-js
      if (!rate) return;

      // This is actually the magic. It's basically a more complex
      // document.querySelector('video, audio').playbackRate
      e.target.playbackRate = rate;
    });
  });

  // AJAX / SPA supporting click bind handler
  // Uses data attribute and aria attribute as well as class-names from
  // media-element-js & this plugin.
  // because this binds to body it should always be available in a valid HTML page
  document.body.addEventListener('click', function (e) {
    // Because we're bound to body, we need to guard and only act on
    // HTML elements with the right class
    if (!e.target || !e.target.classList.contains('playback-rate-button')) return;

    // We set aria attributes informing which DOMElement to control
    const targetId = e.target.getAttribute('aria-controls');
    const mediaTag = document.getElementById(targetId);

    // Guard against failing matchers. The DOM must be fulfilled, but
    // this also means this part maybe doesn't need media-element-js
    const rate = e.target.dataset.value;

    // Guard against failing matchers. The DOM must be fulfilled, but
    // this also means this part maybe doesn't need media-element-js
    if (!rate) return;

    // This is actually the magic. It's basically a more complex
    // document.querySelector('video, audio').playbackRate
    if (mediaTag) {
      mediaTag.playbackRate = rate;
    } else {
      [].slice.call(
        document.querySelectorAll('audio, video')
      ).forEach(function (elem) {
        elem.playbackRate = rate;
      });
    }

    let mediaPlaybackContainer;

    if (mediaTag) {
      mediaPlaybackContainer = mediaTag.closest('.mejs-container');
    }

    // This allows use outside of WordPress for this
    if (!mediaTag || !mediaPlaybackContainer) {
      mediaPlaybackContainer = mediaTag || document.body;
    }

    // Clear all active playback rate buttons for this element of the active class
    [].slice.call(
      mediaPlaybackContainer.querySelectorAll('.playback-rate-button')
    ).map(function (elem) {
      elem.classList.remove('mejs-active', 'active-playback-rate');
      return elem;
    });

    // Set the clicked element, or the matching to active rate to be active
    [].slice.call(
      mediaPlaybackContainer.querySelectorAll('.playback-rate-button')
    ).forEach(function (elem) {
      if (rate && elem.dataset.value === rate) {
        elem.classList.add('mejs-active', 'active-playback-rate');
      }
    });
  });
})();
