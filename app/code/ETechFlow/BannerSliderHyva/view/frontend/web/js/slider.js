/**
 * ETechFlow Banner Slider — Hyvä (Alpine.js) runtime.
 *
 * Self-contained, no RequireJS/jQuery. Registers an Alpine component
 * (etfBannerSlider) whose init() drives the server-rendered markup: autoplay,
 * navigation, lazy video facades, countdowns, A/B split (cookie-sticky),
 * per-visitor targeting and analytics. Mirrors the Luma slider's behaviour but
 * gets its visitor context by fetching the customer-data section directly, so
 * the cached markup stays Full Page Cache / Varnish safe.
 */
(function () {
    'use strict';

    var TARGETING_SECTION = 'etechflow-bannerslider';
    var ATTR_COOKIE = 'etf_bs_attr';
    var UTM_COOKIE = 'etf_utm';
    var UTM_KEYS = ['source', 'medium', 'campaign'];

    function readCookie(name) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : '';
    }

    function writeCookie(name, value, days) {
        var expires = new Date(new Date().getTime() + days * 86400000).toUTCString();
        document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires + '; path=/; SameSite=Lax';
    }

    // ---- shared analytics beacon (one queue per page) -------------------
    var beacon = {
        queue: [],
        url: '',
        attributionDays: 7,
        timer: null,
        bound: false,
        configure: function (url, days) {
            if (url && !this.url) { this.url = url; }
            if (days) { this.attributionDays = parseInt(days, 10) || this.attributionDays; }
            this.bindExit();
        },
        push: function (ev) {
            this.queue.push({
                banner_id: parseInt(ev.bannerId, 10) || 0,
                slider_id: parseInt(ev.sliderId, 10) || 0,
                variant: ev.variant || 'default',
                event_type: ev.type
            });
            if (!this.timer) {
                var self = this;
                this.timer = window.setTimeout(function () { self.flush(); }, 1000);
            }
        },
        flush: function () {
            if (this.timer) { window.clearTimeout(this.timer); this.timer = null; }
            if (!this.queue.length || !this.url) { return; }
            var payload = JSON.stringify({ events: this.queue.splice(0, this.queue.length) });
            var sent = false;
            if (navigator.sendBeacon) {
                try { sent = navigator.sendBeacon(this.url, new Blob([payload], { type: 'application/json' })); } catch (e) { sent = false; }
            }
            if (!sent && window.fetch) {
                window.fetch(this.url, { method: 'POST', body: payload, keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'application/json' } }).catch(function () {});
            }
        },
        attribute: function (ev) {
            try {
                writeCookie(ATTR_COOKIE, JSON.stringify({
                    b: parseInt(ev.bannerId, 10) || 0,
                    s: parseInt(ev.sliderId, 10) || 0,
                    v: ev.variant || 'default'
                }), this.attributionDays);
            } catch (e) { /* cookie blocked */ }
        },
        bindExit: function () {
            if (this.bound) { return; }
            this.bound = true;
            var self = this;
            document.addEventListener('visibilitychange', function () { if (document.hidden) { self.flush(); } });
            window.addEventListener('pagehide', function () { self.flush(); });
        }
    };

    // ---- targeting helpers ----------------------------------------------
    function resolveUtm() {
        var utm = {}, found = false;
        if (window.URLSearchParams) {
            var p = new URLSearchParams(window.location.search);
            UTM_KEYS.forEach(function (k) { var v = p.get('utm_' + k); if (v) { utm[k] = v; found = true; } });
        }
        if (found) { try { writeCookie(UTM_COOKIE, JSON.stringify(utm), 30); } catch (e) {} return utm; }
        var stored = readCookie(UTM_COOKIE);
        if (stored) { try { return JSON.parse(stored) || {}; } catch (e) { return {}; } }
        return {};
    }

    function resolveDevice() {
        if (window.matchMedia) {
            if (window.matchMedia('(max-width: 767px)').matches) { return 'mobile'; }
            if (window.matchMedia('(max-width: 1024px)').matches) { return 'tablet'; }
        } else if (window.innerWidth) {
            if (window.innerWidth <= 767) { return 'mobile'; }
            if (window.innerWidth <= 1024) { return 'tablet'; }
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
        if (typeof min === 'number' && value < min) { return false; }
        if (typeof max === 'number' && value > max) { return false; }
        return true;
    }

    function hourMatches(from, to, hour) {
        if (typeof from !== 'number' && typeof to !== 'number') { return true; }
        var f = typeof from === 'number' ? from : 0;
        var t = typeof to === 'number' ? to : 23;
        return f <= t ? (hour >= f && hour <= t) : (hour >= f || hour <= t);
    }

    function eqi(a, b) { return ('' + a).toLowerCase() === ('' + b).toLowerCase(); }

    function matches(rules, ctx) {
        if (!rules) { return true; }
        if (rules.groups && rules.groups.length && ctx.groupId !== null) {
            if (!rules.groups.some(function (g) { return parseInt(g, 10) === ctx.groupId; })) { return false; }
        }
        if (rules.login === 'logged_in' && !ctx.loggedIn) { return false; }
        if (rules.login === 'guest' && ctx.loggedIn) { return false; }
        if (rules.devices && rules.devices.length && rules.devices.indexOf(ctx.device) === -1) { return false; }
        if (rules.countries && rules.countries.length) {
            if (!ctx.country || !rules.countries.some(function (c) { return eqi(c, ctx.country); })) { return false; }
        }
        if (!withinRange(ctx.cartQty, rules.cart_qty_min, rules.cart_qty_max)) { return false; }
        if (!withinRange(ctx.cartSubtotal, rules.cart_subtotal_min, rules.cart_subtotal_max)) { return false; }
        if (rules.days && rules.days.length && rules.days.indexOf('' + ctx.now.getDay()) === -1) { return false; }
        if (!hourMatches(rules.hour_from, rules.hour_to, ctx.now.getHours())) { return false; }
        if (rules.utm) {
            for (var i = 0; i < UTM_KEYS.length; i++) {
                var k = UTM_KEYS[i];
                if (rules.utm[k] && !eqi(rules.utm[k], ctx.utm[k] || '')) { return false; }
            }
        }
        return true;
    }

    // ---- slider ---------------------------------------------------------
    function EtfSlider(element, config) {
        this.root = element;
        this.config = config || {};
        this.slides = Array.prototype.slice.call(element.querySelectorAll('.etf-bs__slide'));
        this.bullets = Array.prototype.slice.call(element.querySelectorAll('.etf-bs__bullet'));
        this.current = 0;
        this.timer = null;
        this.countdownTimer = null;
        this.countdowns = [];
        this.failTimer = null;
        this.targetingApplied = false;
        this.count = this.slides.length;
        if (this.count > 0) { this.init(); }
    }

    EtfSlider.prototype.init = function () {
        var self = this;
        this.tracking = !!this.config.track;
        if (this.tracking) { beacon.configure(this.config.trackUrl, this.config.attributionDays); this.bindTracking(); }

        this.lazyLoadAround(0);
        this.bindControls();
        this.bindAutoplay();
        this.observeImpressions();
        this.initVideos();
        this.initCountdowns();

        if (this.config.abTest) { this.applyAbTest(); }
        if (this.hasTargeting()) { this.applyTargeting(); } else { this.activateVideo(0); }

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) { self.stop(); } else { self.bindAutoplay(); }
        });
    };

    EtfSlider.prototype.goTo = function (index) {
        if (this.count === 0) { return; }
        if (index < 0) { index = this.config.loop ? this.count - 1 : 0; }
        else if (index >= this.count) { index = this.config.loop ? 0 : this.count - 1; }
        if (index === this.current) { return; }
        this.deactivateVideo(this.current);
        this.slides[this.current].classList.remove('is-active');
        if (this.bullets[this.current]) { this.bullets[this.current].classList.remove('is-active'); }
        this.current = index;
        this.slides[index].classList.add('is-active');
        if (this.bullets[index]) { this.bullets[index].classList.add('is-active'); }
        this.lazyLoadAround(index);
        this.activateVideo(index);
    };

    EtfSlider.prototype.next = function () { this.goTo(this.current + 1); };
    EtfSlider.prototype.prev = function () { this.goTo(this.current - 1); };

    EtfSlider.prototype.lazyLoadAround = function (index) {
        var self = this;
        [index, index + 1, index - 1].forEach(function (i) {
            var slide = self.slides[i];
            if (!slide) { return; }
            var img = slide.querySelector('img[data-src]');
            if (img) { img.src = img.getAttribute('data-src'); img.removeAttribute('data-src'); }
        });
    };

    EtfSlider.prototype.bindControls = function () {
        var self = this;
        var prev = this.root.querySelector('.etf-bs__arrow--prev');
        var next = this.root.querySelector('.etf-bs__arrow--next');
        if (prev) { prev.addEventListener('click', function () { self.prev(); self.restartAutoplay(); }); }
        if (next) { next.addEventListener('click', function () { self.next(); self.restartAutoplay(); }); }
        this.bullets.forEach(function (bullet) {
            bullet.addEventListener('click', function () { self.goTo(self.bullets.indexOf(bullet)); self.restartAutoplay(); });
        });
    };

    EtfSlider.prototype.bindAutoplay = function () {
        var self = this;
        if (!this.config.autoplay || this.count < 2) { return; }
        var speed = parseInt(this.config.autoplaySpeed, 10) || 3500;
        this.stop();
        this.timer = window.setInterval(function () { self.next(); }, speed);
        if (this.config.pauseOnHover) {
            this.root.addEventListener('mouseenter', function () { self.stop(); });
            this.root.addEventListener('mouseleave', function () { self.bindAutoplay(); });
        }
    };

    EtfSlider.prototype.restartAutoplay = function () { this.stop(); this.bindAutoplay(); };
    EtfSlider.prototype.stop = function () { if (this.timer) { window.clearInterval(this.timer); this.timer = null; } };

    EtfSlider.prototype.observeImpressions = function () {
        if (!('IntersectionObserver' in window)) { return; }
        var self = this;
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting && !entry.target.dataset.etfSeen) {
                    entry.target.dataset.etfSeen = '1';
                    if (self.tracking) {
                        beacon.push({ type: 'impression', sliderId: self.config.sliderId, bannerId: entry.target.getAttribute('data-banner-id'), variant: entry.target.getAttribute('data-variant') });
                    }
                }
            });
        }, { threshold: 0.5 });
        this.slides.forEach(function (slide) { observer.observe(slide); });
    };

    EtfSlider.prototype.bindTracking = function () {
        var self = this;
        this.slides.forEach(function (slide) {
            var meta = { sliderId: self.config.sliderId, bannerId: slide.getAttribute('data-banner-id'), variant: slide.getAttribute('data-variant') };
            function on(type) {
                return function () { beacon.push({ type: type, sliderId: meta.sliderId, bannerId: meta.bannerId, variant: meta.variant }); beacon.attribute(meta); };
            }
            Array.prototype.forEach.call(slide.querySelectorAll('[data-etf-track="click"]'), function (el) { el.addEventListener('click', on('click')); });
            var cart = slide.querySelector('.etf-bs__tocart');
            if (cart) { cart.addEventListener('click', on('add_to_cart')); }
        });
    };

    // videos
    EtfSlider.prototype.initVideos = function () {
        var self = this;
        this.slides.forEach(function (slide) {
            var c = slide.querySelector('[data-etf-video]');
            if (!c) { return; }
            if (c.getAttribute('data-embed')) { c._etfFacade = c.innerHTML; }
            c.addEventListener('click', function (e) {
                var btn = e.target.closest && e.target.closest('.etf-bs__video-play');
                if (btn) { e.preventDefault(); e.stopPropagation(); self.injectIframe(c, true); }
            });
        });
    };

    EtfSlider.prototype.injectIframe = function (c, forceAutoplay) {
        if (c.dataset.etfLoaded) { return; }
        var embed = c.getAttribute('data-embed');
        if (!embed) { return; }
        if (forceAutoplay && embed.indexOf('autoplay=') === -1) { embed += (embed.indexOf('?') === -1 ? '?' : '&') + 'autoplay=1'; }
        var iframe = document.createElement('iframe');
        iframe.src = embed;
        iframe.className = 'etf-bs__video-frame w-full h-full';
        iframe.setAttribute('frameborder', '0');
        iframe.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture');
        iframe.setAttribute('allowfullscreen', 'allowfullscreen');
        c.innerHTML = '';
        c.appendChild(iframe);
        c.dataset.etfLoaded = '1';
    };

    EtfSlider.prototype.activateVideo = function (index) {
        var slide = this.slides[index];
        if (!slide) { return; }
        var c = slide.querySelector('[data-etf-video]');
        if (!c || c.getAttribute('data-autoplay') !== '1') { return; }
        var video = c.querySelector('video[data-etf-autoplay]');
        if (video) { var p = video.play(); if (p && p.catch) { p.catch(function () {}); } }
        else if (c.getAttribute('data-embed')) { this.injectIframe(c, true); }
    };

    EtfSlider.prototype.deactivateVideo = function (index) {
        var slide = this.slides[index];
        if (!slide) { return; }
        var c = slide.querySelector('[data-etf-video]');
        if (!c) { return; }
        var video = c.querySelector('video');
        if (video && !video.paused) { video.pause(); }
        if (c.dataset.etfLoaded && c._etfFacade !== undefined) { c.innerHTML = c._etfFacade; c.dataset.etfLoaded = ''; }
    };

    // countdowns
    EtfSlider.prototype.initCountdowns = function () {
        var self = this;
        this.slides.forEach(function (slide) {
            var el = slide.querySelector('[data-etf-countdown]');
            if (!el) { return; }
            self.countdowns.push({ el: el, slide: slide, target: parseInt(el.getAttribute('data-etf-countdown'), 10), hide: el.getAttribute('data-hide-expired') === '1', done: false });
        });
        if (this.countdowns.length) {
            this.tickCountdowns();
            this.countdownTimer = window.setInterval(function () { self.tickCountdowns(); }, 1000);
        }
    };

    EtfSlider.prototype.tickCountdowns = function () {
        var self = this, now = new Date().getTime();
        this.countdowns.forEach(function (cd) {
            if (cd.done) { return; }
            var diff = cd.target - now;
            if (isNaN(cd.target) || diff <= 0) {
                self.setCountdownUnits(cd.el, 0, 0, 0, 0);
                cd.done = true;
                if (cd.hide) { self.hideSlide(cd.slide); } else { cd.el.classList.add('is-expired'); }
                return;
            }
            var t = Math.floor(diff / 1000);
            self.setCountdownUnits(cd.el, Math.floor(t / 86400), Math.floor((t % 86400) / 3600), Math.floor((t % 3600) / 60), t % 60);
        });
        if (this.countdowns.every(function (c) { return c.done; }) && this.countdownTimer) {
            window.clearInterval(this.countdownTimer); this.countdownTimer = null;
        }
    };

    EtfSlider.prototype.setCountdownUnits = function (el, d, h, m, s) {
        var map = { days: d, hours: h, minutes: m, seconds: s };
        Object.keys(map).forEach(function (u) {
            var node = el.querySelector('[data-unit="' + u + '"]');
            if (node) { var v = map[u]; node.textContent = v < 10 ? '0' + v : '' + v; }
        });
    };

    // slide removal / active state
    EtfSlider.prototype.removeSlide = function (slide) {
        var idx = this.slides.indexOf(slide);
        if (idx === -1) { return -1; }
        slide.classList.remove('is-active');
        slide.style.display = 'none';
        this.slides.splice(idx, 1);
        var bullet = this.bullets[idx];
        if (bullet && bullet.parentNode) { bullet.parentNode.removeChild(bullet); this.bullets.splice(idx, 1); }
        this.count = this.slides.length;
        return idx;
    };

    EtfSlider.prototype.hideSlide = function (slide) {
        var wasActive = slide.classList.contains('is-active');
        var idx = this.removeSlide(slide);
        if (idx === -1) { return; }
        if (idx < this.current) { this.current--; }
        if (this.count === 0) { this.stop(); return; }
        if (this.current >= this.count) { this.current = 0; }
        if (wasActive) {
            var active = this.slides[this.current];
            if (active) {
                active.classList.add('is-active');
                if (this.bullets[this.current]) { this.bullets[this.current].classList.add('is-active'); }
                this.activateVideo(this.current);
            }
        }
    };

    EtfSlider.prototype.resetActive = function () {
        this.slides.forEach(function (s) { s.classList.remove('is-active'); });
        this.bullets.forEach(function (b) { b.classList.remove('is-active'); });
        this.current = 0;
        if (this.count > 0) {
            this.slides[0].classList.add('is-active');
            if (this.bullets[0]) { this.bullets[0].classList.add('is-active'); }
            this.lazyLoadAround(0);
        }
    };

    // A/B
    EtfSlider.prototype.applyAbTest = function () {
        var variants = this.config.abVariants || {};
        var keys = Object.keys(variants);
        if (keys.length < 2) { return; }
        var chosen = this.resolveVariant(keys, variants);
        var self = this, toRemove = [];
        this.slides.forEach(function (slide) { if (slide.getAttribute('data-variant') !== chosen) { toRemove.push(slide); } });
        toRemove.forEach(function (slide) { self.removeSlide(slide); });
        this.resetActive();
        this.restartAutoplay();
    };

    EtfSlider.prototype.resolveVariant = function (keys, weights) {
        var cookie = 'etf_bs_ab_' + this.config.sliderId;
        var stored = readCookie(cookie);
        if (stored && keys.indexOf(stored) !== -1) { return stored; }
        var total = 0;
        keys.forEach(function (k) { total += Math.max(1, parseInt(weights[k], 10) || 1); });
        var r = Math.random() * total, chosen = keys[0];
        for (var i = 0; i < keys.length; i++) { r -= Math.max(1, parseInt(weights[keys[i]], 10) || 1); if (r <= 0) { chosen = keys[i]; break; } }
        writeCookie(cookie, chosen, 30);
        return chosen;
    };

    // targeting (fetch the customer-data section directly)
    EtfSlider.prototype.hasTargeting = function () {
        return this.slides.some(function (s) { return s.getAttribute('data-etf-target'); });
    };

    EtfSlider.prototype.applyTargeting = function () {
        var self = this;
        this.failTimer = window.setTimeout(function () {
            if (!self.targetingApplied) { self.targetingApplied = true; self.revealPending(); }
        }, 2000);

        var url = this.config.sectionUrl;
        if (!url || !window.fetch) { return; }
        var sep = url.indexOf('?') === -1 ? '?' : '&';
        window.fetch(url + sep + 'sections=' + TARGETING_SECTION + '&force_new_section_timestamp=true', { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var section = data && data[TARGETING_SECTION] ? data[TARGETING_SECTION] : {};
                if (!self.targetingApplied) { self.runTargeting(section); }
            })
            .catch(function () { /* fail open via timeout */ });
    };

    EtfSlider.prototype.runTargeting = function (section) {
        if (this.targetingApplied) { return; }
        this.targetingApplied = true;
        if (this.failTimer) { window.clearTimeout(this.failTimer); this.failTimer = null; }
        var ctx = buildContext(section), self = this, toRemove = [];
        this.slides.forEach(function (slide) {
            var raw = slide.getAttribute('data-etf-target');
            if (!raw) { return; }
            var rules = null;
            try { rules = JSON.parse(raw); } catch (e) { rules = null; }
            if (matches(rules, ctx)) { slide.classList.remove('is-pending-target'); } else { toRemove.push(slide); }
        });
        toRemove.forEach(function (slide) { self.removeSlide(slide); });
        this.resetActive();
        if (this.count > 0) { this.activateVideo(0); }
        this.restartAutoplay();
    };

    EtfSlider.prototype.revealPending = function () {
        this.slides.forEach(function (s) { s.classList.remove('is-pending-target'); });
        if (this.count > 0) { this.activateVideo(this.current); }
    };

    // ---- Alpine registration -------------------------------------------
    function register() {
        if (window.__etfBsHyvaRegistered || !window.Alpine) { return; }
        window.__etfBsHyvaRegistered = true;
        window.Alpine.data('etfBannerSlider', function (config) {
            return {
                init: function () { this.__etfSlider = new EtfSlider(this.$el, config || {}); }
            };
        });
    }

    if (window.Alpine) { register(); } else { window.addEventListener('alpine:init', register); }
})();
