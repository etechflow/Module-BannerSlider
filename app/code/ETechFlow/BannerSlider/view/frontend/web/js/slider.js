/**
 * ETechFlow Banner Slider — lightweight vanilla-JS slider.
 *
 * No jQuery/Knockout dependency so the same component works on Luma and can be
 * mirrored on Hyvä. Compatible with Magento's text/x-magento-init: the module
 * returns a function invoked as (config, element).
 */
define([], function () {
    'use strict';

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
        this.count = this.slides.length;

        if (this.count > 0) {
            this.init();
        }
    }

    Slider.prototype.init = function () {
        var self = this;

        this.lazyLoadAround(0);
        this.bindControls();
        this.bindAutoplay();
        this.observeImpressions();

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
                self.goTo(parseInt(bullet.getAttribute('data-index'), 10));
                self.restartAutoplay();
            });
        });
    };

    Slider.prototype.bindAutoplay = function () {
        var self = this;
        if (!this.config.autoplay || this.count < 2) {
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
                    // Phase 7 wires this hook to the tracking beacon.
                    self.root.dispatchEvent(new CustomEvent('etf:impression', {
                        detail: {
                            sliderId: self.config.sliderId,
                            bannerId: entry.target.getAttribute('data-banner-id'),
                            variant: entry.target.getAttribute('data-variant')
                        }
                    }));
                }
            });
        }, { threshold: 0.5 });

        this.slides.forEach(function (slide) {
            observer.observe(slide);
        });
    };

    return function (config, element) {
        return new Slider(element, config);
    };
});
