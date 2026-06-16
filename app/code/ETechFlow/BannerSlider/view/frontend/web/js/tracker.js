/**
 * ETechFlow Banner Slider — analytics beacon (Feature 4).
 *
 * A single page-level queue shared by every slider on the page. Events are
 * batched and flushed with navigator.sendBeacon (fetch keepalive fallback) so
 * tracking never blocks rendering or navigation. Clicks also drop a last-click
 * attribution cookie that the server reads on order placement to credit
 * revenue back to the banner.
 */
define([], function () {
    'use strict';

    var ATTR_COOKIE = 'etf_bs_attr';
    var queue = [];
    var trackUrl = '';
    var attributionDays = 7;
    var flushTimer = null;
    var exitBound = false;

    function writeCookie(name, value, days) {
        var expires = new Date(new Date().getTime() + days * 86400000).toUTCString();
        document.cookie = name + '=' + encodeURIComponent(value) +
            '; expires=' + expires + '; path=/; SameSite=Lax';
    }

    function send() {
        if (flushTimer) {
            window.clearTimeout(flushTimer);
            flushTimer = null;
        }
        if (!queue.length || !trackUrl) {
            return;
        }
        var payload = JSON.stringify({ events: queue.splice(0, queue.length) });
        var sent = false;
        if (navigator.sendBeacon) {
            try {
                sent = navigator.sendBeacon(trackUrl, new Blob([payload], { type: 'application/json' }));
            } catch (e) {
                sent = false;
            }
        }
        if (!sent && window.fetch) {
            window.fetch(trackUrl, {
                method: 'POST',
                body: payload,
                keepalive: true,
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' }
            }).catch(function () {});
        }
    }

    function scheduleFlush() {
        if (flushTimer) {
            return;
        }
        flushTimer = window.setTimeout(send, 1000);
    }

    function bindExitFlush() {
        if (exitBound) {
            return;
        }
        exitBound = true;
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                send();
            }
        });
        window.addEventListener('pagehide', send);
    }

    return {
        init: function (config) {
            if (!trackUrl && config && config.trackUrl) {
                trackUrl = config.trackUrl;
            }
            if (config && config.attributionDays) {
                attributionDays = parseInt(config.attributionDays, 10) || attributionDays;
            }
            bindExitFlush();
        },

        push: function (event) {
            if (!event || !event.type) {
                return;
            }
            queue.push({
                banner_id: parseInt(event.bannerId, 10) || 0,
                slider_id: parseInt(event.sliderId, 10) || 0,
                variant: event.variant || 'default',
                event_type: event.type
            });
            scheduleFlush();
        },

        /** Persist the last clicked banner for server-side order attribution. */
        attribute: function (event) {
            try {
                writeCookie(ATTR_COOKIE, JSON.stringify({
                    b: parseInt(event.bannerId, 10) || 0,
                    s: parseInt(event.sliderId, 10) || 0,
                    v: event.variant || 'default'
                }), attributionDays);
            } catch (e) {
                // Cookie blocked — click is still counted, attribution skipped.
            }
        },

        flush: send
    };
});
