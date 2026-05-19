(function () {
  'use strict';

  var config = window.MinimalistLoaderConfig || {};
  var startTime = Date.now();
  var released = false;
  var listenerRegistered = false;
  var eventName = config.event || 'slotRenderEnded';
  var slotIds = Array.isArray(config.slotIds)
    ? config.slotIds.map(function (slotId) { return String(slotId).toLowerCase(); }).filter(Boolean)
    : [];
  var minTime = parseInt(config.minTime, 10) || 0;
  var maxTime = parseInt(config.maxTime, 10) || 4000;
  var fadeDuration = parseInt(config.fadeDuration, 10) || 240;

  window.googletag = window.googletag || { cmd: [] };
  window.googletag.cmd = window.googletag.cmd || [];
  document.documentElement.classList.add('minimalist-loader-active');

  function hasMinimumTimePassed() {
    return Date.now() - startTime >= minTime;
  }

  function releaseWhenReady() {
    if (released) {
      return;
    }

    if (!hasMinimumTimePassed()) {
      window.setTimeout(releaseWhenReady, Math.max(0, minTime - (Date.now() - startTime)));
      return;
    }

    released = true;
    hideLoader('release-condition-met');
  }

  function hideLoader(reason) {
    var container = document.getElementById('minimalist-loader-container');

    if (!container) {
      document.documentElement.classList.add('minimalist-loader-released');
      document.documentElement.classList.remove('minimalist-loader-active');
      return;
    }

    document.documentElement.classList.add('minimalist-loader-released');
    container.classList.add('is-hiding');

    window.setTimeout(function () {
      if (container.parentNode) {
        container.parentNode.removeChild(container);
      }

      document.documentElement.classList.remove('minimalist-loader-active');
    }, fadeDuration);
  }

  function normalizeSlotId(slot) {
    if (!slot || typeof slot.getSlotElementId !== 'function') {
      return '';
    }

    return String(slot.getSlotElementId() || '').toLowerCase();
  }

  function normalizeValue(value) {
    return String(value || '').toLowerCase().trim();
  }

  function getSlotDebug(slot) {
    var elementId = '';
    var adUnitPath = '';

    try {
      elementId = slot && typeof slot.getSlotElementId === 'function' ? slot.getSlotElementId() : '';
    } catch (error) {
      elementId = 'error:' + error.message;
    }

    try {
      adUnitPath = slot && typeof slot.getAdUnitPath === 'function' ? slot.getAdUnitPath() : '';
    } catch (error) {
      adUnitPath = 'error:' + error.message;
    }

    return {
      slotElementId: elementId,
      slotElementIdLowercase: String(elementId || '').toLowerCase(),
      adUnitPath: adUnitPath,
      adUnitPathLowercase: String(adUnitPath || '').toLowerCase()
    };
  }

  function slotMatches(slot) {
    if (!slotIds.length) {
      return true;
    }

    var slotDebug = getSlotDebug(slot);
    var candidates = [
      slotDebug.slotElementIdLowercase,
      slotDebug.adUnitPathLowercase
    ];

    for (var configuredIndex = 0; configuredIndex < slotIds.length; configuredIndex += 1) {
      var configuredSlotId = normalizeValue(slotIds[configuredIndex]);

      for (var candidateIndex = 0; candidateIndex < candidates.length; candidateIndex += 1) {
        if (normalizeValue(candidates[candidateIndex]).indexOf(configuredSlotId) !== -1) {
          return true;
        }
      }
    }

    return false;
  }

  function registerGamListener() {
    if (listenerRegistered || !window.googletag || !window.googletag.cmd) {
      return false;
    }

    listenerRegistered = true;

    window.googletag.cmd.push(function () {
      if (!window.googletag.pubads) {
        return;
      }

      try {
        window.googletag.pubads().addEventListener(eventName, function (event) {
          if (slotMatches(event.slot)) {
            releaseWhenReady();
          }
        });
      } catch (error) {
        releaseWhenReady();
      }
    });

    return true;
  }

  function watchGam() {
    if (released) {
      return;
    }

    if (window.googletag && window.googletag.apiReady === true && !slotIds.length) {
      releaseWhenReady();
      return;
    }

    if (!registerGamListener()) {
      window.setTimeout(watchGam, 50);
    }
  }

  window.setTimeout(function () {
    releaseWhenReady();
  }, maxTime);
  watchGam();
})();
