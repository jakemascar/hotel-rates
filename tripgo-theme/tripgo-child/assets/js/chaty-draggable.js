(function () {
    'use strict';

    var WIDGET_SELECTOR = '.chaty-widget';
    var TRIGGER_SELECTOR = '.chaty-i-trigger';
    var DRAG_THRESHOLD = 6;
    var VIEWPORT_MARGIN = 8;
    var MOBILE_BREAKPOINT = 768;
    var initializedWidgets = new WeakSet();

    function storageKey() {
        return window.innerWidth < MOBILE_BREAKPOINT
            ? 'island-diaries-chaty-position-mobile'
            : 'island-diaries-chaty-position-desktop';
    }

    function clamp(value, minimum, maximum) {
        return Math.min(Math.max(value, minimum), Math.max(minimum, maximum));
    }

    function getWidgetSize(widget) {
        var rect = widget.getBoundingClientRect();
        var trigger = widget.querySelector(TRIGGER_SELECTOR);
        var triggerRect = trigger ? trigger.getBoundingClientRect() : rect;

        return {
            width: Math.max(rect.width, triggerRect.width, 44),
            height: Math.max(rect.height, triggerRect.height, 44)
        };
    }

    function applyPosition(widget, left, top) {
        var size = getWidgetSize(widget);
        var maxLeft = window.innerWidth - size.width - VIEWPORT_MARGIN;
        var maxTop = window.innerHeight - size.height - VIEWPORT_MARGIN;

        widget.style.setProperty('left', clamp(left, VIEWPORT_MARGIN, maxLeft) + 'px', 'important');
        widget.style.setProperty('top', clamp(top, VIEWPORT_MARGIN, maxTop) + 'px', 'important');
        widget.style.setProperty('right', 'auto', 'important');
        widget.style.setProperty('bottom', 'auto', 'important');
    }

    function clearPosition(widget) {
        widget.style.removeProperty('left');
        widget.style.removeProperty('top');
        widget.style.removeProperty('right');
        widget.style.removeProperty('bottom');
    }

    function savePosition(widget) {
        var rect = widget.getBoundingClientRect();

        try {
            window.localStorage.setItem(storageKey(), JSON.stringify({
                left: Math.round(rect.left),
                top: Math.round(rect.top)
            }));
        } catch (error) {
            // Storage can be unavailable in private or restricted browsers.
        }
    }

    function restorePosition(widget) {
        var storedPosition;

        try {
            storedPosition = JSON.parse(window.localStorage.getItem(storageKey()) || 'null');
        } catch (error) {
            storedPosition = null;
        }

        if (!storedPosition || !Number.isFinite(storedPosition.left) || !Number.isFinite(storedPosition.top)) {
            return;
        }

        applyPosition(widget, storedPosition.left, storedPosition.top);
    }

    function initializeWidget(widget) {
        var trigger;
        var dragState = null;
        var suppressClickUntil = 0;
        var currentStorageKey = storageKey();

        if (initializedWidgets.has(widget)) {
            return;
        }

        trigger = widget.querySelector(TRIGGER_SELECTOR);

        if (!trigger) {
            return;
        }

        initializedWidgets.add(widget);
        widget.classList.add('island-diaries-chaty-draggable');
        trigger.style.touchAction = 'none';
        trigger.style.cursor = 'grab';
        restorePosition(widget);

        trigger.addEventListener('pointerdown', function (event) {
            var rect;

            if (!event.isPrimary || (event.pointerType === 'mouse' && event.button !== 0)) {
                return;
            }

            rect = widget.getBoundingClientRect();
            dragState = {
                pointerId: event.pointerId,
                startX: event.clientX,
                startY: event.clientY,
                startLeft: rect.left,
                startTop: rect.top,
                dragging: false
            };

            if (typeof trigger.setPointerCapture === 'function') {
                trigger.setPointerCapture(event.pointerId);
            }
        });

        trigger.addEventListener('pointermove', function (event) {
            var deltaX;
            var deltaY;

            if (!dragState || event.pointerId !== dragState.pointerId) {
                return;
            }

            deltaX = event.clientX - dragState.startX;
            deltaY = event.clientY - dragState.startY;

            if (!dragState.dragging && Math.hypot(deltaX, deltaY) < DRAG_THRESHOLD) {
                return;
            }

            dragState.dragging = true;
            trigger.style.cursor = 'grabbing';
            widget.classList.add('island-diaries-chaty-is-dragging');
            applyPosition(widget, dragState.startLeft + deltaX, dragState.startTop + deltaY);
            event.preventDefault();
        });

        function finishDrag(event) {
            if (!dragState || event.pointerId !== dragState.pointerId) {
                return;
            }

            if (dragState.dragging) {
                savePosition(widget);
                suppressClickUntil = Date.now() + 500;
                event.preventDefault();
            }

            trigger.style.cursor = 'grab';
            widget.classList.remove('island-diaries-chaty-is-dragging');
            dragState = null;
        }

        trigger.addEventListener('pointerup', finishDrag);
        trigger.addEventListener('pointercancel', finishDrag);

        trigger.addEventListener('click', function (event) {
            if (Date.now() < suppressClickUntil) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, true);

        window.addEventListener('resize', function () {
            var nextStorageKey = storageKey();
            var rect;

            if (nextStorageKey !== currentStorageKey) {
                currentStorageKey = nextStorageKey;
                clearPosition(widget);
                restorePosition(widget);
                return;
            }

            if (!widget.style.left || !widget.style.top) {
                return;
            }

            rect = widget.getBoundingClientRect();
            applyPosition(widget, rect.left, rect.top);
            savePosition(widget);
        });
    }

    function initializeAvailableWidgets() {
        document.querySelectorAll(WIDGET_SELECTOR).forEach(initializeWidget);
    }

    initializeAvailableWidgets();

    new MutationObserver(initializeAvailableWidgets).observe(document.documentElement, {
        childList: true,
        subtree: true
    });
}());
