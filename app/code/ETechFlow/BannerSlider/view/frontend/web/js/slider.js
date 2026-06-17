/**
 * ETechFlow Banner Slider — lightweight vanilla-JS slider.
 *
 * The slider mechanics carry no jQuery/Knockout dependency so the same logic
 * can be mirrored on Hyvä. Targeting (Feature 1) reads the customer-data
 * section for per-visitor context, which is what keeps the rendered markup
 * Full Page Cache safe. Compatible with Magento's text/x-magento-init: the
 * module returns a function invoked as (config, element).
 */
define([
    'Magento_Customer/js/customer-data',
    'ETechFlow_BannerSlider/js/targeting',
    'ETechFlow_BannerSlider/js/tracker'
], function (customerData, targeting, tracker) {
    'use strict';

    var TARGETING_SECTION = 'etechflow-bannerslider';

    function readCookie(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : '';
    }

    function writeCookie(name, value, days) {
        var expires = new Date(new Date().getTime() + days * 86400000).toUTCString();
        document.cookie = name + '=' + encodeURIComponent(value) +
            '; expires=' + expires + '; path=/; SameSite=Lax';
    }

    function Slider(element, config) {
        this.root = element;
        this.config = config || {};
        this.track = element.querySelector('.etf-bannerslider__track');
        this.slides = Array.prototype.slice.call(
            element.querySelectorAll('.etf-bannerslider__slide')
        );
        this.bullets = Array.prototype.slice.call(
            element.querySelectorAll('.etf-bannerslider__bullet')
        );
        this.current = 0;
        this.timer = null;
        this.countdownTimer = null;
        this.countdowns = [];
        this.failTimer = null;
        this.targetingApplied = false;
        this.sectionApplied = false;
        this.count = this.slides.length;

        if (this.count > 0) {
            this.init();
        }
    }

    Slider.prototype.init = function () {
        var self = this;

        this.tracking = !!this.config.track;
        if (this.tracking) {
            tracker.init(this.config);
            this.bindTracking();
        }

        this.lazyLoadAround(0);
        this.bindControls();
        this.bindAutoplay();
        this.observeImpressions();
        this.initVideos();
        this.initCountdowns();

        // A/B split first (synchronous, cookie-sticky), then per-visitor
        // targeting (async). Whichever runs last leaves a valid active slide.
        if (this.config.abTest) {
            this.applyAbTest();
        }
        if (this.hasTargeting()) {
            this.applyTargeting();
        } else {
            this.activateVideo(0);
        }

        // Pause when the tab is hidden to avoid wasted work.
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                self.stop();
            } else {
                self.bindAutoplay();
            }
        });
    };

    Slider.prototype.goTo = function (index) {
        if (this.count === 0) {
            return;
        }
        if (index < 0) {
            index = this.config.loop ? this.count - 1 : 0;
        } else if (index >= this.count) {
            index = this.config.loop ? 0 : this.count - 1;
        }
        if (index === this.current) {
            return;
        }

        // Leaving the slide: any playing video is torn down below, so allow
        // autoplay to resume once the user has navigated away.
        this.videoPlaying = false;

        this.deactivateVideo(this.current);
        this.slides[this.current].classList.remove('is-active');
        if (this.bullets[this.current]) {
            this.bullets[this.current].classList.remove('is-active');
        }

        this.current = index;
        this.slides[index].classList.add('is-active');
        if (this.bullets[index]) {
            this.bullets[index].classList.add('is-active');
        }

        this.lazyLoadAround(index);
        this.activateVideo(index);
    };

    Slider.prototype.next = function () {
        this.goTo(this.current + 1);
    };

    Slider.prototype.prev = function () {
        this.goTo(this.current - 1);
    };

    /** Swap data-src -> src for the current slide and its immediate neighbour. */
    Slider.prototype.lazyLoadAround = function (index) {
        var targets = [index, index + 1, index - 1];
        var self = this;
        targets.forEach(function (i) {
            var slide = self.slides[i];
            if (!slide) {
                return;
            }
            var img = slide.querySelector('img[data-src]');
            if (img) {
                img.src = img.getAttribute('data-src');
                img.removeAttribute('data-src');
            }
        });
    };

    Slider.prototype.bindControls = function () {
        var self = this;
        var prev = this.root.querySelector('.etf-bannerslider__arrow--prev');
        var next = this.root.querySelector('.etf-bannerslider__arrow--next');

        if (prev) {
            prev.addEventListener('click', function () {
                self.prev();
                self.restartAutoplay();
            });
        }
        if (next) {
            next.addEventListener('click', function () {
                self.next();
                self.restartAutoplay();
            });
        }
        this.bullets.forEach(function (bullet) {
            bullet.addEventListener('click', function () {
                // Use the bullet's live position so navigation stays correct
                // even after a slide (e.g. an expired countdown) is removed.
                self.goTo(self.bullets.indexOf(bullet));
                self.restartAutoplay();
            });
        });
    };

    Slider.prototype.bindAutoplay = function () {
        var self = this;
        if (!this.config.autoplay || this.count < 2 || this.videoPlaying) {
            return;
        }
        var speed = parseInt(this.config.autoplaySpeed, 10) || 3500;
        this.stop();
        this.timer = window.setInterval(function () {
            self.next();
        }, speed);

        if (this.config.pauseOnHover) {
            this.root.addEventListener('mouseenter', function () {
                self.stop();
            });
            this.root.addEventListener('mouseleave', function () {
                self.bindAutoplay();
            });
        }
    };

    Slider.prototype.restartAutoplay = function () {
        this.stop();
        this.bindAutoplay();
    };

    Slider.prototype.stop = function () {
        if (this.timer) {
            window.clearInterval(this.timer);
            this.timer = null;
        }
    };

    /** Fire a one-time impression hook per slide as it becomes visible. */
    Slider.prototype.observeImpressions = function () {
        if (!('IntersectionObserver' in window)) {
            return;
        }
        var self = this;
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting && !entry.target.dataset.etfSeen) {
                    entry.target.dataset.etfSeen = '1';
                    var detail = {
                        sliderId: self.config.sliderId,
                        bannerId: entry.target.getAttribute('data-banner-id'),
                        variant: entry.target.getAttribute('data-variant')
                    };
                    self.root.dispatchEvent(new CustomEvent('etf:impression', { detail: detail }));
                    if (self.tracking) {
                        tracker.push({
                            type: 'impression',
                            sliderId: detail.sliderId,
                            bannerId: detail.bannerId,
                            variant: detail.variant
                        });
                    }
                }
            });
        }, { threshold: 0.5 });

        this.slides.forEach(function (slide) {
            observer.observe(slide);
        });
    };

    /** Record click / add-to-cart events and set last-click attribution. */
    Slider.prototype.bindTracking = function () {
        var self = this;
        this.slides.forEach(function (slide) {
            var meta = {
                sliderId: self.config.sliderId,
                bannerId: slide.getAttribute('data-banner-id'),
                variant: slide.getAttribute('data-variant')
            };

            function onClick(type) {
                return function () {
                    tracker.push({ type: type, sliderId: meta.sliderId, bannerId: meta.bannerId, variant: meta.variant });
                    tracker.attribute(meta);
                };
            }

            // Link banners (image / html / countdown) and product CTAs.
            var clickTargets = slide.querySelectorAll(
                '[data-etf-track="click"], .etf-bannerslider__product-media,' +
                ' .etf-bannerslider__product-name, .etf-bannerslider__view'
            );
            Array.prototype.forEach.call(clickTargets, function (el) {
                el.addEventListener('click', onClick('click'));
            });

            var cart = slide.querySelector('.etf-bannerslider__tocart');
            if (cart) {
                cart.addEventListener('click', onClick('add_to_cart'));
            }
        });
    };

    // ---------------------------------------------------------------------
    //  Video banners (lazy: heavy players are only created when needed)
    // ---------------------------------------------------------------------

    Slider.prototype.initVideos = function () {
        var self = this;
        this.slides.forEach(function (slide) {
            var container = slide.querySelector('[data-etf-video]');
            if (!container) {
                return;
            }
            // Remember the facade so playback can be torn down on slide change.
            if (container.getAttribute('data-embed')) {
                container._etfFacade = container.innerHTML;
            }
            // Delegate so the handler survives facade restore/teardown.
            container.addEventListener('click', function (e) {
                var btn = e.target.closest && e.target.closest('.etf-bannerslider__video-play');
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.injectIframe(container, true);
                }
            });
        });
    };

    Slider.prototype.injectIframe = function (container, forceAutoplay) {
        if (container.dataset.etfLoaded) {
            return;
        }
        var embed = container.getAttribute('data-embed');
        if (!embed) {
            return;
        }
        if (forceAutoplay && embed.indexOf('autoplay=') === -1) {
            embed += (embed.indexOf('?') === -1 ? '?' : '&') + 'autoplay=1';
        }
        if (forceAutoplay) {
            // Keep the carousel on this slide while the video is playing — the
            // flag also stops pauseOnHover/mouseleave from re-arming autoplay.
            this.videoPlaying = true;
            this.stop();
        }
        var iframe = document.createElement('iframe');
        iframe.src = embed;
        iframe.className = 'etf-bannerslider__video-frame';
        iframe.setAttribute('frameborder', '0');
        iframe.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture');
        iframe.setAttribute('allowfullscreen', 'allowfullscreen');
        container.innerHTML = '';
        container.appendChild(iframe);
        container.dataset.etfLoaded = '1';
    };

    Slider.prototype.activateVideo = function (index) {
        var slide = this.slides[index];
        if (!slide) {
            return;
        }
        var container = slide.querySelector('[data-etf-video]');
        if (!container || container.getAttribute('data-autoplay') !== '1') {
            return;
        }
        var video = container.querySelector('video[data-etf-autoplay]');
        if (video) {
            var playing = video.play();
            if (playing && playing.catch) {
                playing.catch(function () {});
            }
        } else if (container.getAttribute('data-embed')) {
            this.injectIframe(container, true);
        }
    };

    Slider.prototype.deactivateVideo = function (index) {
        var slide = this.slides[index];
        if (!slide) {
            return;
        }
        var container = slide.querySelector('[data-etf-video]');
        if (!container) {
            return;
        }
        var video = container.querySelector('video');
        if (video && !video.paused) {
            video.pause();
        }
        // Tear the iframe down so off-screen players stop their audio, and
        // restore the facade so it can be replayed.
        if (container.dataset.etfLoaded && container._etfFacade !== undefined) {
            container.innerHTML = container._etfFacade;
            container.dataset.etfLoaded = '';
        }
    };

    // ---------------------------------------------------------------------
    //  Countdown banners
    // ---------------------------------------------------------------------

    Slider.prototype.initCountdowns = function () {
        var self = this;
        this.slides.forEach(function (slide) {
            var el = slide.querySelector('[data-etf-countdown]');
            if (!el) {
                return;
            }
            self.countdowns.push({
                el: el,
                slide: slide,
                target: parseInt(el.getAttribute('data-etf-countdown'), 10),
                hide: el.getAttribute('data-hide-expired') === '1',
                done: false
            });
        });
        if (this.countdowns.length) {
            this.tickCountdowns();
            this.countdownTimer = window.setInterval(function () {
                self.tickCountdowns();
            }, 1000);
        }
    };

    Slider.prototype.tickCountdowns = function () {
        var self = this;
        var now = new Date().getTime();
        this.countdowns.forEach(function (cd) {
            if (cd.done) {
                return;
            }
            var diff = cd.target - now;
            if (isNaN(cd.target) || diff <= 0) {
                self.setCountdownUnits(cd.el, 0, 0, 0, 0);
                cd.done = true;
                if (cd.hide) {
                    self.hideSlide(cd.slide);
                } else {
                    cd.el.classList.add('is-expired');
                }
                return;
            }
            var total = Math.floor(diff / 1000);
            self.setCountdownUnits(
                cd.el,
                Math.floor(total / 86400),
                Math.floor((total % 86400) / 3600),
                Math.floor((total % 3600) / 60),
                total % 60
            );
        });

        var allDone = this.countdowns.every(function (c) {
            return c.done;
        });
        if (allDone && this.countdownTimer) {
            window.clearInterval(this.countdownTimer);
            this.countdownTimer = null;
        }
    };

    Slider.prototype.setCountdownUnits = function (el, days, hours, minutes, seconds) {
        var map = { days: days, hours: hours, minutes: minutes, seconds: seconds };
        Object.keys(map).forEach(function (unit) {
            var node = el.querySelector('[data-unit="' + unit + '"]');
            if (node) {
                var value = map[unit];
                node.textContent = value < 10 ? '0' + value : '' + value;
            }
        });
    };

    /** Drop a slide + its bullet from the DOM and the internal arrays. */
    Slider.prototype.removeSlide = function (slide) {
        var idx = this.slides.indexOf(slide);
        if (idx === -1) {
            return -1;
        }
        slide.classList.remove('is-active');
        slide.style.display = 'none';
        this.slides.splice(idx, 1);

        var bullet = this.bullets[idx];
        if (bullet && bullet.parentNode) {
            bullet.parentNode.removeChild(bullet);
            this.bullets.splice(idx, 1);
        }
        this.count = this.slides.length;
        return idx;
    };

    /** Remove a single slide (e.g. an expired countdown) and keep navigation sane. */
    Slider.prototype.hideSlide = function (slide) {
        var wasActive = slide.classList.contains('is-active');
        var idx = this.removeSlide(slide);
        if (idx === -1) {
            return;
        }
        if (idx < this.current) {
            this.current--;
        }
        if (this.count === 0) {
            this.stop();
            return;
        }
        if (this.current >= this.count) {
            this.current = 0;
        }
        if (wasActive) {
            var active = this.slides[this.current];
            if (active) {
                active.classList.add('is-active');
                if (this.bullets[this.current]) {
                    this.bullets[this.current].classList.add('is-active');
                }
                this.activateVideo(this.current);
            }
        }
    };

    // ---------------------------------------------------------------------
    //  Targeting (Feature 1) — FPC-safe, decided in the browser
    // ---------------------------------------------------------------------

    Slider.prototype.hasTargeting = function () {
        return this.slides.some(function (slide) {
            return slide.getAttribute('data-etf-target');
        });
    };

    Slider.prototype.applyTargeting = function () {
        var self = this;
        // device / UTM / time are derived locally — no request needed.
        var ctx = targeting.buildContext(null);

        // Phase 1 (immediate): apply LOCAL rules (device / day / hour / UTM).
        // These need no network, so device targeting is correct instantly — even
        // on slow mobile where the customer-data section can arrive late.
        var deferred = [];
        var dropNow = [];
        this.slides.forEach(function (slide) {
            var raw = slide.getAttribute('data-etf-target');
            if (!raw) {
                return;
            }
            var rules = null;
            try {
                rules = JSON.parse(raw);
            } catch (e) {
                rules = null;
            }
            if (!targeting.matchesLocal(rules, ctx)) {
                dropNow.push(slide);          // can never match this visitor
            } else if (targeting.needsSection(rules)) {
                slide._etfRules = rules;      // still needs group/login/cart/country
                deferred.push(slide);
            } else {
                slide.classList.remove('is-pending-target');
            }
        });
        dropNow.forEach(function (slide) {
            self.removeSlide(slide);
        });
        this.resetActive();
        if (this.count > 0) {
            this.activateVideo(0);
        }
        this.restartAutoplay();

        if (!deferred.length) {
            this.targetingApplied = true;
            return;
        }

        // Phase 2: dimensions that need customer-data. If the section is slow,
        // reveal the deferred banners so they are never stuck hidden — but keep
        // listening, so a late section can still prune the ones that don't match.
        this.failTimer = window.setTimeout(function () {
            self.revealDeferred(deferred);
        }, 3000);

        if (!customerData || typeof customerData.get !== 'function') {
            return;
        }
        var section = customerData.get(TARGETING_SECTION);
        section.subscribe(function (data) {
            if (!self.sectionApplied && data && typeof data.group_id !== 'undefined') {
                self.applySection(deferred, data);
            }
        });
        var current = section();
        if (current && typeof current.group_id !== 'undefined') {
            this.applySection(deferred, current);
        } else if (typeof customerData.reload === 'function') {
            // The section isn't in local storage yet (e.g. a browser that cached
            // sections before this module was installed). Force a fetch so the
            // subscribe above fires with real login/group/country data.
            customerData.reload([TARGETING_SECTION], false);
        }
    };

    Slider.prototype.applySection = function (deferred, section) {
        if (this.sectionApplied || !section) {
            return;
        }
        this.sectionApplied = true;
        this.targetingApplied = true;
        if (this.failTimer) {
            window.clearTimeout(this.failTimer);
            this.failTimer = null;
        }

        var self = this;
        var toRemove = [];
        var ctx = targeting.buildContext(section);
        deferred.forEach(function (slide) {
            if (targeting.matches(slide._etfRules, ctx)) {
                slide.classList.remove('is-pending-target'); // may already be visible
            } else {
                toRemove.push(slide);                         // prune (even if fail-open revealed it)
            }
        });
        toRemove.forEach(function (slide) {
            self.removeSlide(slide);
        });

        this.resetActive();
        if (this.count > 0) {
            this.activateVideo(0);
        }
        this.restartAutoplay();
    };

    /**
     * Fail-open for section-dependent banners: reveal them so they are not stuck
     * hidden when customer-data is slow. We do NOT mark targeting as applied, so a
     * late-arriving section can still prune the ones that do not match.
     */
    Slider.prototype.revealDeferred = function (deferred) {
        var changed = false;
        deferred.forEach(function (slide) {
            if (slide.classList.contains('is-pending-target')) {
                slide.classList.remove('is-pending-target');
                changed = true;
            }
        });
        this.failTimer = null;
        if (changed) {
            this.resetActive();
            if (this.count > 0) {
                this.activateVideo(0);
            }
            this.restartAutoplay();
        }
    };

    /** Clear all active states and promote the first remaining slide. */
    Slider.prototype.resetActive = function () {
        this.slides.forEach(function (slide) {
            slide.classList.remove('is-active');
        });
        this.bullets.forEach(function (bullet) {
            bullet.classList.remove('is-active');
        });
        // Promote the first slide that is not still pending a targeting decision,
        // so a deferred (hidden) banner is never shown as a blank active slide.
        var first = 0;
        for (var i = 0; i < this.slides.length; i++) {
            if (!this.slides[i].classList.contains('is-pending-target')) {
                first = i;
                break;
            }
        }
        this.current = first;
        if (this.count > 0) {
            this.slides[first].classList.add('is-active');
            if (this.bullets[first]) {
                this.bullets[first].classList.add('is-active');
            }
            this.lazyLoadAround(first);
        }
    };

    // ---------------------------------------------------------------------
    //  A/B testing (Feature 3) — weighted split, sticky per visitor
    // ---------------------------------------------------------------------

    Slider.prototype.applyAbTest = function () {
        var variants = this.config.abVariants || {};
        var keys = Object.keys(variants);
        if (keys.length < 2) {
            return;
        }
        var chosen = this.resolveVariant(keys, variants);
        var self = this;
        var toRemove = [];
        this.slides.forEach(function (slide) {
            if (slide.getAttribute('data-variant') !== chosen) {
                toRemove.push(slide);
            }
        });
        toRemove.forEach(function (slide) {
            self.removeSlide(slide);
        });
        this.resetActive();
        this.restartAutoplay();
    };

    /** Sticky weighted variant choice, remembered in a per-slider cookie. */
    Slider.prototype.resolveVariant = function (keys, weights) {
        var cookie = 'etf_bs_ab_' + this.config.sliderId;
        var stored = readCookie(cookie);
        if (stored && keys.indexOf(stored) !== -1) {
            return stored;
        }
        var total = 0;
        keys.forEach(function (k) {
            total += Math.max(1, parseInt(weights[k], 10) || 1);
        });
        var r = Math.random() * total;
        var chosen = keys[0];
        for (var i = 0; i < keys.length; i++) {
            r -= Math.max(1, parseInt(weights[keys[i]], 10) || 1);
            if (r <= 0) {
                chosen = keys[i];
                break;
            }
        }
        writeCookie(cookie, chosen, 30);
        return chosen;
    };

    /** Fail-open / match path: show any slides still waiting on targeting. */
    Slider.prototype.revealPending = function () {
        this.slides.forEach(function (slide) {
            slide.classList.remove('is-pending-target');
        });
        if (this.count > 0) {
            this.activateVideo(this.current);
        }
    };

    return function (config, element) {
        return new Slider(element, config);
    };
});
