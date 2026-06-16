/**
 * ETechFlow Banner Slider — client-side targeting engine.
 *
 * Evaluating rules in the browser is what keeps the slider Full Page Cache
 * safe: the server renders every candidate banner with its rules attached, and
 * this module decides per-visitor which ones to show. Visitor context comes
 * from a customer-data section (group/login/cart/country) plus locally derived
 * device, UTM and time.
 */
define([], function () {
    'use strict';

    var UTM_COOKIE = 'etf_utm';
    var UTM_KEYS = ['source', 'medium', 'campaign'];

    function readCookie(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : '';
    }

    function writeCookie(name, value, days) {
        var expires = new Date(new Date().getTime() + days * 86400000).toUTCString();
        document.cookie = name + '=' + encodeURIComponent(value) +
            '; expires=' + expires + '; path=/; SameSite=Lax';
    }

    /** UTM params persist for the session in a cookie so they survive navigation. */
    function resolveUtm() {
        var utm = {};
        var found = false;
        if (window.URLSearchParams) {
            var params = new URLSearchParams(window.location.search);
            UTM_KEYS.forEach(function (key) {
                var value = params.get('utm_' + key);
                if (value) {
                    utm[key] = value;
                    found = true;
                }
            });
        }
        if (found) {
            try {
                writeCookie(UTM_COOKIE, JSON.stringify(utm), 30);
            } catch (e) {
                // Cookie write blocked — keep the in-memory values for this page.
            }
            return utm;
        }
        var stored = readCookie(UTM_COOKIE);
        if (stored) {
            try {
                return JSON.parse(stored) || {};
            } catch (e) {
                return {};
            }
        }
        return {};
    }

    function resolveDevice() {
        if (window.matchMedia) {
            if (window.matchMedia('(max-width: 767px)').matches) {
                return 'mobile';
            }
            if (window.matchMedia('(max-width: 1024px)').matches) {
                return 'tablet';
            }
        } else if (window.innerWidth) {
            if (window.innerWidth <= 767) {
                return 'mobile';
            }
            if (window.innerWidth <= 1024) {
                return 'tablet';
            }
        }
        return 'desktop';
    }

    function buildContext(section) {
        section = section || {};
        return {
            groupId: typeof section.group_id !== 'undefined' ? parseInt(section.group_id, 10) : null,
            loggedIn: !!section.logged_in,
            cartQty: parseFloat(section.cart_qty) || 0,
            cartSubtotal: parseFloat(section.cart_subtotal) || 0,
            country: section.country || null,
            device: resolveDevice(),
            utm: resolveUtm(),
            now: new Date()
        };
    }

    function withinRange(value, min, max) {
        if (typeof min === 'number' && value < min) {
            return false;
        }
        if (typeof max === 'number' && value > max) {
            return false;
        }
        return true;
    }

    function hourMatches(from, to, hour) {
        if (typeof from !== 'number' && typeof to !== 'number') {
            return true;
        }
        var f = typeof from === 'number' ? from : 0;
        var t = typeof to === 'number' ? to : 23;
        if (f <= t) {
            return hour >= f && hour <= t;
        }
        return hour >= f || hour <= t; // window spans midnight
    }

    function eqi(a, b) {
        return ('' + a).toLowerCase() === ('' + b).toLowerCase();
    }

    /**
     * Returns true when every configured rule matches (unset rules are skipped).
     */
    function matches(rules, ctx) {
        if (!rules) {
            return true;
        }

        if (rules.groups && rules.groups.length && ctx.groupId !== null) {
            var inGroup = rules.groups.some(function (g) {
                return parseInt(g, 10) === ctx.groupId;
            });
            if (!inGroup) {
                return false;
            }
        }

        if (rules.login === 'logged_in' && !ctx.loggedIn) {
            return false;
        }
        if (rules.login === 'guest' && ctx.loggedIn) {
            return false;
        }

        if (rules.devices && rules.devices.length && rules.devices.indexOf(ctx.device) === -1) {
            return false;
        }

        if (rules.countries && rules.countries.length) {
            if (!ctx.country) {
                return false;
            }
            var hit = rules.countries.some(function (c) {
                return eqi(c, ctx.country);
            });
            if (!hit) {
                return false;
            }
        }

        if (!withinRange(ctx.cartQty, rules.cart_qty_min, rules.cart_qty_max)) {
            return false;
        }
        if (!withinRange(ctx.cartSubtotal, rules.cart_subtotal_min, rules.cart_subtotal_max)) {
            return false;
        }

        if (rules.days && rules.days.length && rules.days.indexOf('' + ctx.now.getDay()) === -1) {
            return false;
        }
        if (!hourMatches(rules.hour_from, rules.hour_to, ctx.now.getHours())) {
            return false;
        }

        if (rules.utm) {
            for (var i = 0; i < UTM_KEYS.length; i++) {
                var key = UTM_KEYS[i];
                if (rules.utm[key] && !eqi(rules.utm[key], ctx.utm[key] || '')) {
                    return false;
                }
            }
        }

        return true;
    }

    return {
        buildContext: buildContext,
        matches: matches
    };
});
