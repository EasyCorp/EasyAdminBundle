// loads the contents of lazy dashboard widgets and reloads the ones that refresh periodically;
// the HTML of each widget comes from its own URL (data-ea-widget-url) on the same origin
(() => {
    // this script is included by every widget that needs it, so it can appear more than once per page
    const INIT_FLAG = Symbol.for('easyadmin.widgets');
    if (window[INIT_FLAG]) {
        return;
    }
    window[INIT_FLAG] = true;

    const WIDGET_SELECTOR = '[data-ea-widget][data-ea-widget-url]';
    const MAX_TIMER_DELAY = 2 ** 31 - 1;
    const REQUEST_TIMEOUT_MS = 30_000;
    const MIN_REFRESH_SECONDS = 10;
    const LAZY_ROOT_MARGIN = '200px 0px';

    const states = new WeakMap();
    const refreshingStates = new Set();
    const inFlightStates = new Set();
    let liveRegion = null;
    let announcedState = null;
    let lazyObserver = null;
    let pendingLazyWidgets = 0;

    const resolveUrl = (rawUrl) => {
        if (!rawUrl) {
            return null;
        }

        try {
            const url = new URL(rawUrl, window.location.href);

            return url.origin === window.location.origin ? url.href : null;
        } catch {
            return null;
        }
    };

    const parseRefreshMs = (rawSeconds) => {
        const seconds = Number.parseInt(rawSeconds ?? '', 10);
        if (!Number.isFinite(seconds) || seconds <= 0) {
            return null;
        }

        return Math.max(MIN_REFRESH_SECONDS, seconds) * 1000;
    };

    const stateFor = (widget) => {
        if (states.has(widget)) {
            return states.get(widget);
        }

        const body = widget.querySelector('[data-ea-widget-body]');
        if (null === body) {
            return null;
        }

        const state = {
            widget,
            body,
            name: widget.getAttribute('data-ea-widget'),
            url: resolveUrl(widget.getAttribute('data-ea-widget-url')),
            refreshMs: parseRefreshMs(widget.getAttribute('data-ea-widget-refresh')),
            controller: null,
            interrupted: false,
            fromRetry: false,
            refreshTimer: null,
            lastSettledAt: null,
        };
        states.set(widget, state);

        return state;
    };

    // a single live region for the whole page that only announces errors: if widget bodies were
    // live regions, screen readers would read again the whole widget after each refresh
    const createLiveRegion = () => {
        liveRegion = document.createElement('div');
        liveRegion.className = 'visually-hidden';
        liveRegion.setAttribute('role', 'status');
        document.body.append(liveRegion);
    };

    const announce = (state, message) => {
        const labelId = state.widget.getAttribute('aria-labelledby');
        const label = labelId ? document.getElementById(labelId)?.textContent.trim() : '';
        liveRegion.textContent = label ? `${label}: ${message}` : message;
        announcedState = state;
    };

    // other widgets must not clear the error of this one before screen readers announce it
    const clearAnnouncement = (state) => {
        if (state === announcedState) {
            liveRegion.textContent = '';
            announcedState = null;
        }
    };

    const forget = (state) => {
        clearTimeout(state.refreshTimer);
        state.refreshTimer = null;
        refreshingStates.delete(state);
    };

    const scheduleRefresh = (state, delay = state.refreshMs) => {
        clearTimeout(state.refreshTimer);
        state.refreshTimer = null;
        if (null === state.refreshMs || null === state.url || null === state.lastSettledAt || document.hidden) {
            return;
        }

        // browsers store the delay as a signed 32-bit integer; longer delays (25 days or more) would run immediately
        state.refreshTimer = setTimeout(() => load(state), Math.min(Math.max(0, delay), MAX_TIMER_DELAY));
    };

    const swapBody = (state, content, focusBody) => {
        const { body } = state;
        // replacing the contents would drop the keyboard focus to the start of the page
        const hadFocus = focusBody || body.contains(document.activeElement);
        body.replaceChildren(content);
        if (!hadFocus) {
            return;
        }

        const target = body.querySelector('[data-ea-widget-retry]') ?? body;
        // scrollable bodies already have tabindex="0" to be reachable with the keyboard
        if (target === body && !body.hasAttribute('tabindex')) {
            body.setAttribute('tabindex', '-1');
        }
        target.focus({ preventScroll: true });
    };

    // the parsing algorithm of <template> marks <script> elements as already started, so they never run
    const parseHtml = (html) => {
        const template = document.createElement('template');
        template.innerHTML = html;

        return template.content;
    };

    const errorContent = (state) => {
        const template = state.widget.querySelector(':scope > template[data-ea-widget-error-template]');

        return null === template ? document.createDocumentFragment() : template.content.cloneNode(true);
    };

    const stopLoading = (state, isBusy) => {
        state.controller = null;
        inFlightStates.delete(state);
        state.body.setAttribute('aria-busy', isBusy ? 'true' : 'false');
        state.widget.classList.remove('ea-widget-loading');
    };

    const settle = (state, eventName) => {
        stopLoading(state, false);
        state.lastSettledAt = Date.now();
        state.widget.removeAttribute('data-ea-widget-lazy');
        state.widget.dispatchEvent(
            new CustomEvent(eventName, { bubbles: true, detail: { name: state.name, widget: state.widget } })
        );
        scheduleRefresh(state);
    };

    const fail = (state, focusBody, isRefresh) => {
        // a failed periodic refresh keeps the last contents and the next refresh tries again
        if (!isRefresh) {
            swapBody(state, errorContent(state), focusBody);
            const message = state.body.querySelector('.ea-empty-state-message')?.textContent.trim();
            if (message) {
                announce(state, message);
            }
        }
        settle(state, 'ea.widget.error');
    };

    // promise chains instead of async/await: the latter makes the build include a polyfill runtime
    function load(state, { fromRetry = false } = {}) {
        if (!state.widget.isConnected) {
            forget(state);

            return;
        }

        if (null !== state.controller) {
            return;
        }

        const isRefresh = !fromRetry && null !== state.lastSettledAt;
        // replacing the contents while the user is inside the widget would move their focus
        if (isRefresh && state.widget.contains(document.activeElement)) {
            scheduleRefresh(state);

            return;
        }

        clearTimeout(state.refreshTimer);
        state.refreshTimer = null;

        const controller = new AbortController();
        state.controller = controller;
        state.interrupted = false;
        state.fromRetry = fromRetry;
        inFlightStates.add(state);
        state.body.setAttribute('aria-busy', 'true');
        state.widget.classList.add('ea-widget-loading');
        if (fromRetry) {
            clearAnnouncement(state);
        }

        const timeout = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);
        const request =
            null === state.url
                ? Promise.resolve(null)
                : fetch(state.url, {
                      credentials: 'same-origin',
                      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                      signal: controller.signal,
                  });

        request
            .then((response) => {
                // a redirect usually means the session expired and the response is the login page
                if (response?.ok && !response.redirected && response.headers.get('X-EasyAdmin-Widget') === state.name) {
                    return response.text();
                }

                return null;
            })
            .catch(() => null)
            .then((html) => {
                clearTimeout(timeout);

                // the request was aborted because the page went into the back/forward cache
                if (state.interrupted) {
                    stopLoading(state, null === state.lastSettledAt);

                    return;
                }

                // the timeout can abort the request after its response was read
                if (null === html || controller.signal.aborted) {
                    fail(state, fromRetry, isRefresh);

                    return;
                }

                swapBody(state, parseHtml(html), fromRetry);
                clearAnnouncement(state);
                settle(state, 'ea.widget.loaded');
            })
            .catch((error) => {
                // without this, the widget would wait forever for a request that already finished
                if (controller === state.controller) {
                    settle(state, 'ea.widget.error');
                }
                console.error(error);
            });
    }

    const observeLazyWidget = (state) => {
        if (!('IntersectionObserver' in window)) {
            load(state);

            return;
        }

        if (null === lazyObserver) {
            lazyObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        lazyObserver.unobserve(entry.target);
                        pendingLazyWidgets--;
                        load(states.get(entry.target));
                    });

                    if (0 === pendingLazyWidgets) {
                        lazyObserver.disconnect();
                        lazyObserver = null;
                    }
                },
                { rootMargin: LAZY_ROOT_MARGIN }
            );
        }

        pendingLazyWidgets++;
        lazyObserver.observe(state.widget);
    };

    const onVisibilityChange = () => {
        refreshingStates.forEach((state) => {
            if (document.hidden) {
                clearTimeout(state.refreshTimer);
                state.refreshTimer = null;

                return;
            }

            if (null !== state.controller || null === state.lastSettledAt) {
                return;
            }

            scheduleRefresh(state, state.lastSettledAt + state.refreshMs - Date.now());
        });
    };

    // in-flight requests prevent the page from entering the back/forward cache
    const onPageHide = (event) => {
        if (!event.persisted) {
            return;
        }

        inFlightStates.forEach((state) => {
            state.interrupted = true;
            state.controller.abort();
        });
    };

    const onPageShow = (event) => {
        if (!event.persisted) {
            return;
        }

        document.querySelectorAll(WIDGET_SELECTOR).forEach((widget) => {
            const state = states.get(widget);
            if (state?.interrupted) {
                load(state, { fromRetry: state.fromRetry });
            }
        });
        onVisibilityChange();
    };

    const init = () => {
        const now = Date.now();
        createLiveRegion();
        document.querySelectorAll(WIDGET_SELECTOR).forEach((widget) => {
            const state = stateFor(widget);
            if (null === state) {
                return;
            }

            if (null !== state.refreshMs) {
                refreshingStates.add(state);
            }

            if (widget.hasAttribute('data-ea-widget-lazy')) {
                observeLazyWidget(state);

                return;
            }

            // the server already rendered the contents of this widget
            state.lastSettledAt = now;
            scheduleRefresh(state);
        });

        if (refreshingStates.size > 0) {
            document.addEventListener('visibilitychange', onVisibilityChange);
        }
        window.addEventListener('pagehide', onPageHide);
        window.addEventListener('pageshow', onPageShow);

        document.addEventListener('click', (event) => {
            const retryButton = event.target.closest?.('[data-ea-widget-retry]');
            const widget = retryButton?.closest(WIDGET_SELECTOR);
            if (!widget) {
                return;
            }

            const state = stateFor(widget);
            if (null !== state) {
                load(state, { fromRetry: true });
            }
        });
    };

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
