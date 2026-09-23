(function () {
        'use strict';

        var layoutFrame = null;
        var boundaryGap = 16;

        function getHeaderOffset() {
                var bottom = 0;

                document.querySelectorAll('#wpadminbar, .header_sticky.active_sticky, .elementor-sticky--active').forEach(function (element) {
                        var rect = element.getBoundingClientRect();
                        var position = window.getComputedStyle(element).position;

                        if ((position === 'fixed' || position === 'sticky') && rect.height > 0 && rect.bottom > 0 && rect.top < window.innerHeight) {
                                bottom = Math.max(bottom, rect.bottom);
                        }
                });

                return Math.max(16, Math.ceil(bottom) + boundaryGap);
        }

        function getFooterTop() {
                var footerTop = Infinity;

                document.querySelectorAll('.wrap_footer, footer.footer, footer, .elementor-location-footer, [data-elementor-type=footer], #footer, .site-footer').forEach(function (footer) {
                        var rect = footer.getBoundingClientRect();
                        var style = window.getComputedStyle(footer);

                        if (style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0) {
                                footerTop = Math.min(footerTop, rect.top);
                        }
                });

                return footerTop;
        }

        function getFormsWrapper(card) {
                var child = card.firstElementChild;

                while (child) {
                        if (child.classList.contains('forms-wrapper')) {
                                return child;
                        }

                        child = child.nextElementSibling;
                }

                return card;
        }

        function syncFooterBoundary() {
                var footerTop;
                var topOffset;
                var viewportHeight;

                layoutFrame = null;

                if (!window.matchMedia('(min-width: 1025px)').matches) {
                        return;
                }

                footerTop = getFooterTop();

                if (!Number.isFinite(footerTop)) {
                        return;
                }

                topOffset = getHeaderOffset();
                viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;

                document.querySelectorAll('body > .hr-booking-sticky-target.hr-booking-sticky-target--active').forEach(function (card) {
                        var panel = getFormsWrapper(card);
                        var panelRect = panel.getBoundingClientRect();
                        var configuredMaxHeight = parseFloat(window.getComputedStyle(card).getPropertyValue('--hr-booking-sticky-max-height'));
                        var maxHeight = Number.isFinite(configuredMaxHeight)
                                ? configuredMaxHeight
                                : Math.max(240, viewportHeight - topOffset - boundaryGap);
                        var stickyHeight = Math.min(
                                maxHeight,
                                Math.ceil(Math.max(panelRect.height, panel.scrollHeight || 0))
                        );
                        var stickyTop = Math.min(
                                topOffset,
                                footerTop - boundaryGap - stickyHeight
                        );

                        card.style.setProperty('--hr-booking-sticky-top', Math.round(stickyTop) + 'px');
                });
        }

        function scheduleFooterBoundary() {
                if (layoutFrame !== null) {
                        return;
                }

                layoutFrame = window.requestAnimationFrame(function () {
                        layoutFrame = window.requestAnimationFrame(syncFooterBoundary);
                });
        }

        function initFooterBoundary() {
                window.addEventListener('scroll', scheduleFooterBoundary, { passive: true });
                window.addEventListener('resize', scheduleFooterBoundary);
                window.addEventListener('orientationchange', scheduleFooterBoundary);

                [0, 100, 350, 800, 1500].forEach(function (delay) {
                        window.setTimeout(scheduleFooterBoundary, delay);
                });
        }

        if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initFooterBoundary);
        } else {
                initFooterBoundary();
        }
}());
