/**
 * Minimalist Loader - frontend runtime.
 *
 * Render-blocking by design: the scroll lock has to land before the theme paints.
 * The release is a race between "an ad block we care about is ready" and the hard
 * ceiling, floored by the minimum display time.
 */
(() => {
  'use strict';

  const config = window.MinimalistLoaderConfig ?? {};
  const containerId = config.containerId ?? 'minimalist-loader-container';
  const eventName = config.event ?? 'slotRenderEnded';
  const slotIds = (Array.isArray(config.slotIds) ? config.slotIds : [])
    .map((slotId) => String(slotId).toLowerCase().trim())
    .filter(Boolean);
  const minTime = toMilliseconds(config.minTime, 0);
  const maxTime = toMilliseconds(config.maxTime, 4000);
  const fadeDuration = toMilliseconds(config.fadeDuration, 240);
  const scrollLock = toMilliseconds(config.scrollLock, 0);

  const startedAt = performance.now();
  const controller = new AbortController();
  const { signal } = controller;

  /** True while a rebid cascade is re-requesting a block we are waiting on. */
  let holdingForRebid = false;

  window.googletag ??= { cmd: [] };
  window.googletag.cmd ??= [];

  document.documentElement.classList.add('minimalist-loader-active');

  (async () => {
    try {
      await Promise.race([adReady(), delay(maxTime)]);
      await delay(minTime - elapsed());
    } finally {
      // Whatever happened above, the page must not stay behind the loader.
      controller.abort();
      hide();
    }
  })();

  function elapsed() {
    return performance.now() - startedAt;
  }

  function delay(milliseconds) {
    return new Promise((resolve) => {
      if (!(milliseconds > 0)) {
        resolve();
        return;
      }

      const timer = setTimeout(resolve, milliseconds);
      signal.addEventListener('abort', () => clearTimeout(timer), { once: true });
    });
  }

  function toMilliseconds(value, fallback) {
    const parsed = Number.parseInt(value, 10);

    return Number.isFinite(parsed) && parsed >= 0 ? parsed : fallback;
  }

  /** Resolves once a configured ad block has reached the chosen GPT event. */
  function adReady() {
    return new Promise((resolve) => {
      // Nothing configured to wait for, and GPT is already up: do not stall the page.
      if (slotIds.length === 0 && window.googletag.apiReady === true) {
        resolve();
        return;
      }

      window.googletag.cmd.push(() => {
        const pubads = window.googletag.pubads?.();

        if (!pubads) {
          resolve();
          return;
        }

        const listener = (event) => {
          if (!slotMatches(event.slot)) {
            return;
          }

          if (event.isEmpty !== true) {
            resolve();
            return;
          }

          // A rebid cascade is created synchronously inside this same dispatch, so
          // deferring one tick makes the check below see it no matter which
          // slotRenderEnded listener googletag.cmd happened to run first.
          setTimeout(() => onEmptyRender(event.slot, resolve), 0);
        };

        try {
          // GPT's addEventListener predates AbortSignal support, hence the manual teardown.
          pubads.addEventListener(eventName, listener);
          signal.addEventListener('abort', () => {
            try {
              pubads.removeEventListener(eventName, listener);
            } catch {
              /* GPT drops the whole service on destroy; nothing left to detach. */
            }
          }, { once: true });
        } catch {
          resolve();
        }
      });
    });
  }

  /**
   * An unfilled block normally still counts as "ready". The exception is a rebid
   * cascade (SFM wrapper), which re-requests the same div at descending prices — the
   * page should stay behind the loader until that settles.
   */
  function onEmptyRender(slot, resolve) {
    const cascadeId = activeCascadeId(slot);

    if (cascadeId === '') {
      if (!holdingForRebid) {
        resolve();
      }

      return;
    }

    if (holdingForRebid) {
      return;
    }

    holdingForRebid = true;

    const settle = (event) => {
      if (event.detail?.cascadeId !== cascadeId) {
        return;
      }

      holdingForRebid = false;
      resolve();
    };

    document.addEventListener('sfm:rebid_won', settle, { signal });
    document.addEventListener('sfm:rebid_exhausted', settle, { signal });
  }

  /** Div id of the cascade currently rebidding this slot, or '' when there is none. */
  function activeCascadeId(slot) {
    if (typeof window.SFM?.Rebid?.getCascades !== 'function') {
      return '';
    }

    const divId = slotProperty(slot, 'getSlotElementId');

    if (divId === '') {
      return '';
    }

    try {
      return window.SFM.Rebid.getCascades()?.[divId]?.active === true ? divId : '';
    } catch {
      return '';
    }
  }

  function slotMatches(slot) {
    if (slotIds.length === 0) {
      return true;
    }

    const candidates = [
      slotProperty(slot, 'getSlotElementId'),
      slotProperty(slot, 'getAdUnitPath'),
    ].filter(Boolean).map((value) => value.toLowerCase());

    return slotIds.some((slotId) => candidates.some((candidate) => candidate.includes(slotId)));
  }

  function slotProperty(slot, method) {
    try {
      return typeof slot?.[method] === 'function' ? String(slot[method]() ?? '') : '';
    } catch {
      return '';
    }
  }

  function hide() {
    const root = document.documentElement;
    root.classList.add('minimalist-loader-released');

    const container = document.getElementById(containerId);

    // Released before the body markup was parsed; the inline script next to the
    // container reconciles it as soon as it exists.
    if (container === null) {
      unlockScroll();
      return;
    }

    container.classList.add('is-hiding');

    let fallbackTimer = 0;

    const remove = () => {
      clearTimeout(fallbackTimer);
      container.remove();
      unlockScroll();
    };

    container.addEventListener('transitionend', (event) => {
      if (event.target === container && event.propertyName === 'opacity') {
        remove();
      }
    });

    // Covers a container that never transitions: reduced motion, zero fade, or a
    // theme that overrode the transition away.
    fallbackTimer = setTimeout(remove, fadeDuration + 50);
  }

  /**
   * Lifts the loader's scroll lock, or hands it to the optional post-release hold.
   * That hold has its own class because the inline reconcile script next to the
   * container also clears minimalist-loader-active.
   */
  function unlockScroll() {
    const root = document.documentElement;

    if (scrollLock > 0) {
      root.classList.add('minimalist-loader-scroll-locked');
      setTimeout(() => root.classList.remove('minimalist-loader-scroll-locked'), scrollLock);
    }

    root.classList.remove('minimalist-loader-active');
  }
})();
