/**
 * Gridxflex Announcement Bars with CTA - Frontend JavaScript
 * v1.0.0
 */

(function () {
  "use strict";

  class GABC_Stack {
    constructor() {
      this.cookiePrefix = "gabc_dismissed_";
      this.cookieExpiration = 30;
      this.topBars = [];
      this.bottomBars = []; // sticky bottom — pushes body padding
      this.bottomBarsFixed = []; // non-sticky bottom — fixed but no body padding
      this.adminBarHeight = 0;

      // Trigger state
      this._scrollListenerBound = null;
      this._exitListenerBound = null;
      this._pendingTriggerBars = []; // bars waiting for a trigger to fire
      this._animOnLoad = []; // non-triggered bars with entrance animation
    }

    init() {
      this.setupEventListeners();
      this.detectAdminBar();
      this.collectBars();

      if (
        this.topBars.length === 0 &&
        this.bottomBars.length === 0 &&
        this.bottomBarsFixed.length === 0 &&
        this._pendingTriggerBars.length === 0
      ) {
        return;
      }

      this.stackBars();
      this.updateBodyPadding();
      this.setupTriggers();

      // Play entrance animations for non-triggered bars after layout settles.
      if (this._animOnLoad.length > 0) {
        const barsToAnimate = this._animOnLoad.slice();
        this._animOnLoad = [];
        requestAnimationFrame(() => {
          requestAnimationFrame(() => {
            barsToAnimate.forEach((bar) => this._playEntrance(bar));
          });
        });
      }

      window.addEventListener("resize", () => {
        this.detectAdminBar();
        this.stackBars();
        this.updateBodyPadding();
      });
    }

    // ─── Event listeners (close button) ─────────────────────────────────────

    setupEventListeners() {
      document.addEventListener("click", (e) => {
        const closeBtn = e.target.closest(".gabc-notice-bar__close");
        if (closeBtn) {
          e.preventDefault();
          const bar = closeBtn.closest(".gabc-notice-bar");
          if (bar) this.dismiss(bar);
        }

        // CTA button click tracking.
        const ctaBtn = e.target.closest(".gabc-notice-bar__button");
        if (ctaBtn) {
          const bar = ctaBtn.closest(".gabc-notice-bar");
          if (bar) {
            const noticeId = bar.getAttribute("data-notice-id");
            if (noticeId) this.trackClick(noticeId);
          }
        }
      });

      document.addEventListener("keydown", (e) => {
        const closeBtn = e.target.closest(".gabc-notice-bar__close");
        if (closeBtn && (e.key === "Enter" || e.key === " ")) {
          e.preventDefault();
          const bar = closeBtn.closest(".gabc-notice-bar");
          if (bar) this.dismiss(bar);
        }
      });
    }

    // ─── Admin bar ───────────────────────────────────────────────────────────

    detectAdminBar() {
      const adminBar = document.getElementById("wpadminbar");
      this.adminBarHeight = adminBar ? adminBar.offsetHeight : 0;
    }

    // ─── Collect bars ────────────────────────────────────────────────────────

    collectBars() {
      const bars = document.querySelectorAll(".gabc-notice-bar");

      bars.forEach((bar) => {
        const noticeId = bar.getAttribute("data-notice-id");
        const dismissible = bar.getAttribute("data-dismissible") === "true";

        if (dismissible && this.isDismissed(noticeId)) {
          bar.style.display = "none";
          return;
        }

        // If the bar has a trigger, keep it hidden and queue it.
        if (bar.classList.contains("gabc-trigger-hidden")) {
          this._pendingTriggerBars.push(bar);
          return; // not added to topBars/bottomBars yet
        }

        bar.style.display = "";

        const sticky = bar.getAttribute("data-sticky") === "true";
        const position = bar.getAttribute("data-position");

        // Queue entrance animation for non-triggered animated bars.
        if (bar.classList.contains("gabc-anim-ready")) {
          this._animOnLoad.push(bar);
        }

        if (position === "top" && sticky) {
          this.topBars.push(bar);
        } else if (position === "bottom" && sticky) {
          this.bottomBars.push(bar);
        } else if (position === "bottom" && !sticky) {
          this.bottomBarsFixed.push(bar);
        }
        // non-sticky top: rendered inline in flow, no stacking needed
      });
    }

    // ─── Trigger setup ───────────────────────────────────────────────────────

    setupTriggers() {
      if (this._pendingTriggerBars.length === 0) return;

      let needsScroll = false;
      let needsExitIntent = false;

      this._pendingTriggerBars.forEach((bar) => {
        const delay = parseInt(bar.getAttribute("data-trigger-delay"), 10) || 0;
        const scroll =
          parseInt(bar.getAttribute("data-trigger-scroll"), 10) || 0;
        const exitIntent =
          bar.getAttribute("data-trigger-exit-intent") === "true";

        // Priority: exit intent > scroll > delay
        // Each bar only needs one trigger to fire.

        if (exitIntent) {
          needsExitIntent = true;
          bar._triggerMode = "exit";
        } else if (scroll > 0) {
          needsScroll = true;
          bar._triggerMode = "scroll";
          bar._triggerScrollPct = scroll;
        } else {
          // delay (0 = immediate on next tick, >0 = after N seconds)
          bar._triggerMode = "delay";
          bar._triggerDelay = delay;
          setTimeout(() => this.revealBar(bar), delay * 1000);
        }
      });

      if (needsScroll) {
        this._scrollListenerBound = this._onScroll.bind(this);
        window.addEventListener("scroll", this._scrollListenerBound, {
          passive: true,
        });
        // Check once immediately in case the page loaded already scrolled.
        this._onScroll();
      }

      if (needsExitIntent) {
        this._exitListenerBound = this._onExitIntent.bind(this);
        document.addEventListener("mouseleave", this._exitListenerBound);
      }
    }

    _onScroll() {
      const scrollTop = window.scrollY || document.documentElement.scrollTop;
      const docHeight =
        document.documentElement.scrollHeight - window.innerHeight;
      const scrolledPct = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;

      const remaining = [];
      this._pendingTriggerBars.forEach((bar) => {
        if (bar._triggerMode !== "scroll") {
          remaining.push(bar);
          return;
        }
        if (scrolledPct >= bar._triggerScrollPct) {
          this.revealBar(bar);
          // Don't push back to remaining — it's been revealed.
        } else {
          remaining.push(bar);
        }
      });

      this._pendingTriggerBars = remaining;

      // Clean up listener once all scroll-triggered bars have fired.
      if (!this._pendingTriggerBars.some((b) => b._triggerMode === "scroll")) {
        if (this._scrollListenerBound) {
          window.removeEventListener("scroll", this._scrollListenerBound);
          this._scrollListenerBound = null;
        }
      }
    }

    _onExitIntent(e) {
      // Only fire when cursor leaves through the top of the viewport.
      if (e.clientY > 10) return;

      const toReveal = this._pendingTriggerBars.filter(
        (b) => b._triggerMode === "exit",
      );
      toReveal.forEach((bar) => this.revealBar(bar));

      this._pendingTriggerBars = this._pendingTriggerBars.filter(
        (b) => b._triggerMode !== "exit",
      );

      // Remove listener — exit intent fires once per page load.
      if (this._exitListenerBound) {
        document.removeEventListener("mouseleave", this._exitListenerBound);
        this._exitListenerBound = null;
      }
    }

    // ─── Reveal a triggered bar ──────────────────────────────────────────────

    revealBar(bar) {
      const anim = bar.getAttribute("data-animation");

      if (anim && anim !== "none") {
        this._playEntrance(bar);
      } else {
        bar.classList.remove("gabc-trigger-hidden");
        bar.classList.add("gabc-trigger-visible");
      }

      const sticky = bar.getAttribute("data-sticky") === "true";
      const position = bar.getAttribute("data-position");

      if (position === "top" && sticky) {
        this.topBars.push(bar);
        this.stackBars();
        this.updateBodyPadding();
      } else if (position === "bottom" && sticky) {
        this.bottomBars.push(bar);
        this.stackBars();
        this.updateBodyPadding();
      } else if (position === "bottom" && !sticky) {
        this.bottomBarsFixed.push(bar);
        this.stackBars();
        this.updateBodyPadding(); // FIX: non-sticky bottom also needs padding update
      }
    }

    _playEntrance(bar) {
      const duration =
        parseInt(bar.getAttribute("data-animation-duration"), 10) || 400;

      // Set animation-duration directly — more reliable than CSS variable
      bar.style.animationDuration = duration + "ms";

      bar.classList.add("gabc-entrance");
      bar.classList.remove("gabc-anim-ready");
      bar.classList.remove("gabc-trigger-hidden");
      bar.style.pointerEvents = "";

      const cleanup = () => {
        bar.classList.remove("gabc-entrance");
        bar.style.opacity = "1";
        bar.style.transform = "";
        bar.style.animationDuration = ""; // clean up
      };

      bar.addEventListener("animationend", cleanup, { once: true });
      setTimeout(cleanup, duration + 100);
    }

    // ─── Stacking & padding ──────────────────────────────────────────────────

    stackBars() {
      let topOffset = this.adminBarHeight;
      this.topBars.forEach((bar) => {
        bar.style.top = topOffset + "px";
        topOffset += bar.offsetHeight;
      });

      // Sticky bottom bars — stacked from the bottom edge upward.
      let bottomOffset = 0;
      this.bottomBars.forEach((bar) => {
        bar.style.bottom = bottomOffset + "px";
        bottomOffset += bar.offsetHeight;
      });

      // Non-sticky bottom bars — also fixed at bottom, stacked above sticky ones.
      this.bottomBarsFixed.forEach((bar) => {
        bar.style.bottom = bottomOffset + "px";
        bottomOffset += bar.offsetHeight;
      });
    }

    updateBodyPadding() {
      let totalTop = this.adminBarHeight;
      let totalBottom = 0;

      this.topBars.forEach((bar) => (totalTop += bar.offsetHeight));

      // FIX: Both sticky and non-sticky bottom bars are position:fixed at the
      // bottom of the viewport, so both must contribute to body padding-bottom.
      // Without this, fixed bottom bars overlap and hide the page footer.
      this.bottomBars.forEach((bar) => (totalBottom += bar.offsetHeight));
      this.bottomBarsFixed.forEach((bar) => (totalBottom += bar.offsetHeight));

      document.body.style.setProperty("--gabc-top-bar-height", totalTop + "px");
      document.body.style.setProperty(
        "--gabc-bottom-bar-height",
        totalBottom + "px",
      );

      if (totalTop > this.adminBarHeight) {
        document.body.classList.add("gabc-has-top-bar");
      } else {
        document.body.classList.remove("gabc-has-top-bar");
      }

      if (totalBottom > 0) {
        document.body.classList.add("gabc-has-bottom-bar");
      } else {
        document.body.classList.remove("gabc-has-bottom-bar");
      }
    }

    // ─── Dismiss ─────────────────────────────────────────────────────────────

    dismiss(bar) {
      const noticeId = bar.getAttribute("data-notice-id");
      const dismissible = bar.getAttribute("data-dismissible") === "true";

      if (!dismissible || !noticeId) return;

      const sticky = bar.getAttribute("data-sticky") === "true";
      const position = bar.getAttribute("data-position");

      bar.classList.add("gabc-sliding-out");

      setTimeout(() => {
        bar.style.display = "none";
        this.setCookie(noticeId);

        if (position === "top" && sticky) {
          const idx = this.topBars.indexOf(bar);
          if (idx !== -1) this.topBars.splice(idx, 1);
          this.stackBars();
          this.updateBodyPadding();
        } else if (position === "bottom" && sticky) {
          const idx = this.bottomBars.indexOf(bar);
          if (idx !== -1) this.bottomBars.splice(idx, 1);
          this.stackBars();
          this.updateBodyPadding();
        } else if (position === "bottom" && !sticky) {
          const idx = this.bottomBarsFixed.indexOf(bar);
          if (idx !== -1) this.bottomBarsFixed.splice(idx, 1);
          this.stackBars();
          this.updateBodyPadding(); // FIX: recalculate padding after dismiss too
        } else {
          // non-sticky top: remove from DOM
          bar.remove();
        }
      }, 300);
    }

    // ─── Click tracking ──────────────────────────────────────────────────────

    trackClick(noticeId) {
      if (!window.gabcData || !window.gabcData.nonce) return;

      const data = new FormData();
      data.append("action", "gabc_track_click");
      data.append("nonce", window.gabcData.nonce);
      data.append("notice_id", noticeId);

      // Use sendBeacon when available so the request survives page navigation
      // (e.g. button opens link in same tab).
      const url = window.gabcData.ajaxurl || "/wp-admin/admin-ajax.php";
      if (navigator.sendBeacon) {
        navigator.sendBeacon(url, data);
      } else {
        fetch(url, { method: "POST", body: data, keepalive: true }).catch(
          () => {},
        );
      }
    }

    // ─── Cookie helpers ──────────────────────────────────────────────────────

    isDismissed(noticeId) {
      const cookieName = this.cookiePrefix + noticeId;
      return document.cookie.split(";").some((c) => {
        return c.trim().startsWith(cookieName + "=");
      });
    }

    setCookie(noticeId) {
      const date = new Date();
      date.setTime(
        date.getTime() + this.cookieExpiration * 24 * 60 * 60 * 1000,
      );
      document.cookie =
        this.cookiePrefix +
        noticeId +
        "=true; expires=" +
        date.toUTCString() +
        "; path=/";
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () =>
      new GABC_Stack().init(),
    );
  } else {
    new GABC_Stack().init();
  }
})();
