(function ($) {
        'use strict';

        var GUEST_INPUT_SELECTOR = 'input[name="ovabrw_adults"], input[name="ovabrw_childrens"], input[name="ovabrw_babies"]';
        var PACKAGE_START_DATE_INPUT_SELECTOR = [
                'input[name="ovabrw_pickup_date"]',
                'input[name="ovabrw_pickup_date[]"]',
                'input[name="pickup_date"]',
                'input[name="checkin_date"]',
                'input[name="tour_date"]',
                'input[name="rental_date"]',
                'input[name="booking_date"]',
                'input[name*="pickup_date"]',
                'input[name*="pick_up_date"]',
                'input[name*="tour_date"]',
                'input[name*="rental_date"]',
                'input[name*="booking_date"]',
                'input[name*="checkin"]',
                'input[name*="start_date"]'
        ].join(',');
        var PACKAGE_END_DATE_INPUT_SELECTOR = [
                'input[name="ovabrw_pickoff_date"]',
                'input[name="ovabrw_pickoff_date[]"]',
                'input[name="ovabrw_dropoff_date"]',
                'input[name="ovabrw_dropoff_date[]"]',
                'input[name="dropoff_date"]',
                'input[name="checkout_date"]',
                'input[name*="pickoff_date"]',
                'input[name*="dropoff_date"]',
                'input[name*="drop_off_date"]',
                'input[name*="checkout"]',
                'input[name*="end_date"]'
        ].join(',');
        var PACKAGE_FREE_ACTIVITY_OPTIONS = [
                { value: 'hr_airport_seaport_pickup', label: 'Airport/Seaport Pick-up' },
                { value: 'hr_airport_seaport_dropoff', label: 'Airport/Seaport Drop-off' },
                { value: 'hr_free_time', label: 'Free Time' }
        ];
        var motorbikeDebugEnabled = isMotorbikeDebugEnabled();
        var selfDriveDebugEnabled = isSelfDriveDebugEnabled();
        var packageCalendarRangeActiveForm = null;
        var packageCalendarRangeDocumentHooksBound = false;

        installSafeClosestPolyfill();

        if (motorbikeDebugEnabled) {
                installMotorbikeGlobalDebugHandlers();
        }

        window.hrMotorbikeDebugDump = function () {
                var dumps = [];

                getBookingForms().filter(function () {
                        return $(this).find('.hr-motorbike-booking-field').length > 0;
                }).each(function () {
                        dumps.push(dumpMotorbikeDebugState($(this), 'manual console dump'));
                });

                return dumps;
        };

        window.hrSelfDriveDebugDump = function () {
                var dumps = [];

                getBookingForms().filter(function () {
                        return $(this).find('.hr-self-drive-booking-field').length > 0;
                }).each(function () {
                        dumps.push(dumpSelfDriveDebugState($(this), 'manual console dump'));
                });

                return dumps;
        };

        window.hrSelfDriveDatePickerProbe = function () {
                var probes = [];

                getBookingForms().filter(function () {
                        return $(this).find('.hr-self-drive-booking-field').length > 0;
                }).each(function () {
                        var $form = $(this);
                        var $returnInput = ensureSelfDriveReturnDateField($form);

                        probes.push(showSelfDriveReturnDatePicker($form, $returnInput, 'manual console probe'));
                });

                return probes;
        };

        function isMotorbikeDebugEnabled() {
                var enabledByQuery = /(?:^|[?&])hr_motorbike_debug=1(?:&|$)/.test(window.location.search || '');
                var enabledByStorage = false;

                try {
                        enabledByStorage = window.localStorage && window.localStorage.getItem('hrMotorbikeDebug') === '1';

                        if (enabledByQuery && window.localStorage) {
                                window.localStorage.setItem('hrMotorbikeDebug', '1');
                        }
                } catch (error) {
                        enabledByStorage = false;
                }

                return enabledByQuery || enabledByStorage;
        }

        function isSelfDriveDebugEnabled() {
                var enabledByQuery = /(?:^|[?&])hr_self_drive_debug=1(?:&|$)/.test(window.location.search || '');
                var enabledByStorage = false;

                try {
                        enabledByStorage = window.localStorage && window.localStorage.getItem('hrSelfDriveDebug') === '1';

                        if (enabledByQuery && window.localStorage) {
                                window.localStorage.setItem('hrSelfDriveDebug', '1');
                        }
                } catch (error) {
                        enabledByStorage = false;
                }

                return enabledByQuery || enabledByStorage;
        }

        function selfDriveDebugLog(label, payload) {
                if (!selfDriveDebugEnabled || !window.console || !window.console.log) {
                        return;
                }

                window.console.log('[HR Self Drive Debug] ' + label, payload || '');
        }

        function installMotorbikeGlobalDebugHandlers() {
                installMotorbikeJqueryWriteDebug();

                window.addEventListener('error', function (event) {
                        var details = {
                                message: event.message || '',
                                source: event.filename || '',
                                line: event.lineno || '',
                                column: event.colno || '',
                                error: event.error && event.error.stack ? event.error.stack : String(event.error || '')
                        };

                        if (!/(booking|closest|field|required|motorbike|ovabrw)/i.test([details.message, details.source, details.error].join(' '))) {
                                return;
                        }

                        motorbikeDebugLog('global error', details);
                        window.setTimeout(function () {
                                window.hrMotorbikeDebugDump();
                        }, 0);
                }, true);

                window.addEventListener('unhandledrejection', function (event) {
                        motorbikeDebugLog('unhandled promise rejection', {
                                reason: event.reason && event.reason.stack ? event.reason.stack : String(event.reason || '')
                        });
                }, true);
        }

        function getMotorbikeDebugArgumentText(value) {
                if (typeof value === 'string') {
                        return value;
                }

                if (value && value.jquery) {
                        return $.trim(value.text()).replace(/\s+/g, ' ');
                }

                if (value && value.nodeType) {
                        return getMotorbikeDebugNodeText(value);
                }

                if (typeof value === 'function') {
                        return value.toString();
                }

                try {
                        return JSON.stringify(value);
                } catch (error) {
                        return String(value || '');
                }
        }

        function getMotorbikeDebugTargetSummary($targets) {
                $targets = $targets && $targets.jquery ? $targets : $($targets);

                var $first = $targets.first();

                return {
                        count: $targets.length,
                        firstTag: $first.length && $first[0].tagName ? $first[0].tagName.toLowerCase() : '',
                        firstClasses: $first.length ? String($first[0].className || '') : '',
                        firstText: $first.length ? truncateMotorbikeDebugValue($.trim($first.text()).replace(/\s+/g, ' '), 180) : ''
                };
        }

        function installMotorbikeJqueryWriteDebug() {
                ['html', 'text', 'append', 'prepend', 'before', 'after'].forEach(function (method) {
                        var original = $.fn[method];
                        var wrapped;

                        if (!original || original.hrMotorbikeDebugWrapped) {
                                return;
                        }

                        wrapped = function () {
                                var argsText;

                                if (arguments.length) {
                                        argsText = $.map(arguments, getMotorbikeDebugArgumentText).join(' ');

                                        if (isMotorbikeDebugValidationText(argsText)) {
                                                motorbikeDebugLog('jQuery .' + method + ' wrote validation-like text', {
                                                        targets: getMotorbikeDebugTargetSummary(this),
                                                        value: truncateMotorbikeDebugValue(argsText, 300),
                                                        stack: (new Error()).stack || ''
                                                });
                                        }
                                }

                                return original.apply(this, arguments);
                        };

                        wrapped.hrMotorbikeDebugWrapped = true;
                        $.fn[method] = wrapped;
                });
        }

        function motorbikeDebugLog(label, payload) {
                if (!motorbikeDebugEnabled || !window.console || !window.console.log) {
                        return;
                }

                window.console.log('[HR Motorbike Debug] ' + label, payload || '');
        }

        function truncateMotorbikeDebugValue(value, maxLength) {
                value = typeof value === 'undefined' || value === null ? '' : String(value);
                maxLength = maxLength || 160;

                return value.length > maxLength ? value.substring(0, maxLength) + '...' : value;
        }

        function getMotorbikeDebugControlValue(control, $control) {
                var type = String($control.attr('type') || '').toLowerCase();
                var value;

                if (type === 'file') {
                        if (!control.files || !control.files.length) {
                                return '';
                        }

                        return $.map(control.files, function (file) {
                                return file.name;
                        }).join(', ');
                }

                value = $control.val();

                if ($.isArray(value)) {
                        value = value.join(', ');
                }

                return truncateMotorbikeDebugValue(value, 220);
        }

        function getMotorbikeDebugControls($form) {
                var controls = [];

                $form.find('input, select, textarea, button').each(function (index) {
                        var control = this;
                        var $control = $(control);
                        var name = String($control.attr('name') || '');
                        var className = String(control.className || '');
                        var type = String($control.attr('type') || control.tagName || '').toLowerCase();
                        var watched = $control.is('.ovabrw-input-required, .ovabrw-required, [required], [aria-required="true"], .error, .hr-motorbike-model-qty, .hr-motorbike-start-time-select, .hr-motorbike-days-select, .hr-motorbike-document-input, .hr-motorbike-handover-option, .hr-motorbike-delivery-address') ||
                                /(?:ovabrw|motorbike|quantity|add-to-cart|product_id|data_custom_ckf|recaptcha)/i.test(name);

                        if (!watched) {
                                return;
                        }

                        controls.push({
                                index: index,
                                tag: control.tagName ? control.tagName.toLowerCase() : '',
                                type: type,
                                name: name,
                                id: $control.attr('id') || '',
                                value: getMotorbikeDebugControlValue(control, $control),
                                checked: $control.is(':checkbox, :radio') ? $control.prop('checked') : '',
                                required: $control.prop('required') || $control.attr('required') === 'required',
                                ariaRequired: $control.attr('aria-required') || '',
                                disabled: $control.prop('disabled'),
                                visible: $control.is(':visible'),
                                classes: truncateMotorbikeDebugValue(className, 180),
                                dataError: $control.attr('data-error') || '',
                                containerText: truncateMotorbikeDebugValue(getDirectText(getLegacyAirportTransferFieldContainer($control)), 180)
                        });
                });

                return controls;
        }

        function getMotorbikeDebugRequiredProblems(controls) {
                var problems = [];
                var radioGroups = {};

                controls.forEach(function (control) {
                        if (!control.required || control.disabled || !control.visible) {
                                return;
                        }

                        if (control.type === 'radio') {
                                if (!radioGroups[control.name]) {
                                        radioGroups[control.name] = {
                                                name: control.name,
                                                label: control.containerText,
                                                checked: false
                                        };
                                }

                                radioGroups[control.name].checked = radioGroups[control.name].checked || control.checked === true;
                                return;
                        }

                        if (control.type === 'checkbox' && control.checked !== true) {
                                problems.push($.extend({}, control, {
                                        reason: 'required checkbox is unchecked'
                                }));
                                return;
                        }

                        if (!control.value) {
                                problems.push($.extend({}, control, {
                                        reason: 'required field is empty'
                                }));
                        }
                });

                Object.keys(radioGroups).forEach(function (name) {
                        if (radioGroups[name].checked) {
                                return;
                        }

                        problems.push({
                                tag: 'input',
                                type: 'radio',
                                name: name,
                                value: '',
                                checked: false,
                                required: true,
                                visible: true,
                                reason: 'required radio group has no selection',
                                containerText: radioGroups[name].label
                        });
                });

                return problems;
        }

        function getMotorbikeDebugMessages($form) {
                var messages = [];

                $form.find('.ajax-error, .woocommerce-error, .woocommerce-message, .woocommerce-notices-wrapper, .error, [role="alert"]').each(function (index) {
                        var $message = $(this);
                        var text = $.trim($message.text()).replace(/\s+/g, ' ');

                        if (!text && !$message.attr('class')) {
                                return;
                        }

                        messages.push({
                                index: index,
                                tag: this.tagName ? this.tagName.toLowerCase() : '',
                                classes: String(this.className || ''),
                                visible: $message.is(':visible'),
                                text: truncateMotorbikeDebugValue(text, 260)
                        });
                });

                $('.woocommerce-error, .woocommerce-message, .woocommerce-notices-wrapper, [role="alert"]').not($form.find('*')).each(function (index) {
                        var $message = $(this);
                        var text = $.trim($message.text()).replace(/\s+/g, ' ');

                        if (!/field\s+is\s+required|required|guest|motorbike|ovabrw/i.test(text)) {
                                return;
                        }

                        messages.push({
                                index: 'page-' + index,
                                tag: this.tagName ? this.tagName.toLowerCase() : '',
                                classes: String(this.className || ''),
                                visible: $message.is(':visible'),
                                text: truncateMotorbikeDebugValue(text, 260)
                        });
                });

                return messages;
        }

        function getMotorbikeDebugState($form, reason) {
                var $field = $form.find('.hr-motorbike-booking-field');
                var documentInput = $field.find('.hr-motorbike-document-input')[0];
                var files = documentInput && documentInput.files ? $.map(documentInput.files, function (file) {
                        return file.name;
                }) : [];
                var controls = getMotorbikeDebugControls($form);
                var requiredProblems = getMotorbikeDebugRequiredProblems(controls);

                return {
                        reason: reason,
                        time: new Date().toISOString(),
                        url: window.location.href,
                        form: {
                                action: $form.attr('action') || '',
                                method: $form.attr('method') || '',
                                enctype: $form.attr('enctype') || '',
                                classes: String($form[0] && $form[0].className || '')
                        },
                        motorbike: {
                                selectedCount: $field.find('.hr-motorbike-model-qty').map(function () {
                                        return parseInt($(this).val(), 10) || 0;
                                }).get().reduce(function (total, qty) {
                                        return total + qty;
                                }, 0),
                                startTime: $field.find('.hr-motorbike-start-time-select').val() || '',
                                days: $field.find('.hr-motorbike-days-select').val() || '',
                                handover: $field.find('.hr-motorbike-handover-option:checked').val() || '',
                                deliveryAddress: $field.find('.hr-motorbike-delivery-address').val() || '',
                                fileNames: files,
                                payload: $field.find('input[name="hr_motorbike_selection_payload"]').val() || '',
                                hiddenTotal: $field.find('input[name="hr_motorbike_total"]').val() || '',
                                formTotal: $form.find('input[name="hr_motorbike_total"]').last().val() || '',
                                dataTotal: $form.data('hr-motorbike-total') || '',
                                dataTotalValid: $form.data('hr-motorbike-total-valid') === true
                        },
                        messages: getMotorbikeDebugMessages($form),
                        requiredProblems: requiredProblems,
                        requiredVisibleEmpty: controls.filter(function (control) {
                                return control.required && control.visible && !control.disabled && !control.value && control.checked !== false;
                        }),
                        requiredHidden: controls.filter(function (control) {
                                return control.required && !control.visible;
                        }),
                        controls: controls,
                        lastMutation: $form.data('hr-motorbike-debug-last-mutation') || []
                };
        }

        function logMotorbikeDebugSummary(state) {
                if (!state || !window.console || !/(validation|prepare|submit|click|manual)/i.test(state.reason || '')) {
                        return;
                }

                motorbikeDebugLog('summary: ' + state.reason, {
                        motorbike: state.motorbike,
                        messages: state.messages,
                        requiredProblems: state.requiredProblems,
                        lastMutation: state.lastMutation
                });

                if (state.requiredProblems && state.requiredProblems.length && window.console.table) {
                        window.console.table(state.requiredProblems);
                }
        }

        function dumpMotorbikeDebugState($form, reason) {
                var state;

                if (!motorbikeDebugEnabled || !$form || !$form.length || !$form.find('.hr-motorbike-booking-field').length) {
                        return null;
                }

                state = getMotorbikeDebugState($form, reason);
                window.hrMotorbikeDebugLastDump = state;

                if (window.console && window.console.groupCollapsed) {
                        window.console.groupCollapsed('[HR Motorbike Debug] ' + reason);
                        window.console.log(state);
                        if (window.console.table) {
                                window.console.table(state.controls);
                        }
                        window.console.groupEnd();
                } else {
                        motorbikeDebugLog(reason, state);
                }

                logMotorbikeDebugSummary(state);

                return state;
        }

        function getMotorbikeDebugNodeText(node) {
                if (!node) {
                        return '';
                }

                if (node.nodeType === 3) {
                        return $.trim(node.nodeValue || '').replace(/\s+/g, ' ');
                }

                if (node.nodeType !== 1) {
                        return '';
                }

                if ($(node).closest('script, style').length) {
                        return '';
                }

                return $.trim($(node).text()).replace(/\s+/g, ' ');
        }

        function isMotorbikeDebugValidationText(text) {
                return /field\s+is\s+required|number\s+of\s+guests|guests?\s+is\s+required|guest.*required|required\s+field|total.*greater\s+than\s+0/i.test(String(text || ''));
        }

        function installMotorbikeDebugObserver($form) {
                var observer;

                if (!motorbikeDebugEnabled || !$form.length || !$form[0] || $form.data('hr-motorbike-debug-observer-bound') || !window.MutationObserver || !document.body) {
                        return;
                }

                $form.data('hr-motorbike-debug-observer-bound', true);
                observer = new window.MutationObserver(function (mutations) {
                        var matches = [];

                        mutations.forEach(function (mutation) {
                                var text = getMotorbikeDebugNodeText(mutation.target);

                                if (isMotorbikeDebugValidationText(text)) {
                                        matches.push({
                                                type: mutation.type,
                                                target: mutation.target && mutation.target.nodeName ? mutation.target.nodeName.toLowerCase() : '',
                                                text: truncateMotorbikeDebugValue(text, 220)
                                        });
                                }

                                $(mutation.addedNodes || []).each(function () {
                                        var addedText = getMotorbikeDebugNodeText(this);

                                        if (isMotorbikeDebugValidationText(addedText)) {
                                                matches.push({
                                                        type: 'addedNode',
                                                        target: this.nodeName ? this.nodeName.toLowerCase() : '',
                                                        text: truncateMotorbikeDebugValue(addedText, 220)
                                                });
                                        }
                                });
                        });

                        if (!matches.length) {
                                return;
                        }

                        $form.data('hr-motorbike-debug-last-mutation', matches.slice(0, 8));
                        window.setTimeout(function () {
                                dumpMotorbikeDebugState($form, 'validation message inserted/changed');
                        }, 0);
                });

                observer.observe(document.body, {
                        childList: true,
                        characterData: true,
                        subtree: true
                });

                $form.data('hr-motorbike-debug-observer', observer);
        }

        function installSafeClosestPolyfill() {
                if (window.Document && window.Document.prototype && !window.Document.prototype.closest) {
                        window.Document.prototype.closest = function () {
                                return null;
                        };
                }

                if (window.Text && window.Text.prototype && !window.Text.prototype.closest) {
                        window.Text.prototype.closest = function (selector) {
                                return this.parentElement && this.parentElement.closest ? this.parentElement.closest(selector) : null;
                        };
                }

                if (window.Window && window.Window.prototype && !window.Window.prototype.closest) {
                        window.Window.prototype.closest = function () {
                                return null;
                        };
                }
        }

        function getSourceRows() {
                var $table = $('[data-hr-hotel-table="1"]').first();
                var rows = [];

                if (!$table.length) {
                        return rows;
                }

                $table.find('tbody tr[data-rate-id]').each(function () {
                        var $row = $(this);
                        var prices = {};

                        try {
                                prices = JSON.parse($row.attr('data-prices') || '{}');
                        } catch (error) {
                                prices = {};
                        }

                        rows.push({
                                rateId: String($row.data('rate-id') || ''),
                                hotelId: String($row.data('hotel-id') || ''),
                                hotelName: String($row.data('hotel-name') || ''),
                                bookingLabel: String($row.data('booking-label') || $row.data('hotel-name') || ''),
                                prices: prices
                        });
                });

                return rows;
        }

        function getBookingForms() {
                var $forms = $('form.booking-form');

                if ($forms.length) {
                        return $forms;
                }

                return $('.hr-package-booking-field, .hr-tour-booking-field, .hr-airport-transfer-booking-field, .hr-motorbike-booking-field, .hr-boat-booking-field, .hr-self-drive-booking-field, .hr-destination-tour-booking-field, .hr-pickup-dropoff-booking-field, .hr-port-transfer-booking-field, .hr-booking-rate-field').closest('form');
        }

        function getGuestCount($form) {
                var total = 0;

                ['ovabrw_adults', 'ovabrw_childrens', 'ovabrw_babies'].forEach(function (name) {
                        var value = parseInt($form.find('[name="' + name + '"]').val(), 10);

                        if (!Number.isNaN(value)) {
                                total += value;
                        }
                });

                return total;
        }

        function formatPrice(amount) {
                var locale = (window.hrBooking && window.hrBooking.locale) ? window.hrBooking.locale : undefined;
                var number = Number(amount);

                if (!Number.isFinite(number)) {
                        return '';
                }

                return (window.hrBooking ? window.hrBooking.currencySymbol : '') + number.toLocaleString(locale, {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2
                });
        }

        function escapeHtml(value) {
                return $('<div>').text(value === null || typeof value === 'undefined' ? '' : String(value)).html();
        }

        function escapeAttribute(value) {
                return escapeHtml(value).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function padDatePart(value) {
                value = String(value);

                return value.length < 2 ? '0' + value : value;
        }

        function isDisplayedPackageRangeValue(value) {
                return /\s(?:-|to|\u2013)\s/i.test(String(value || ''));
        }

        function isBookingDateCandidate() {
                var name = String(this.name || '').toLowerCase();

                return !/(adult|children|baby|quantity|pax|guest)/.test(name);
        }

        function findBookingDateInput($form, selector) {
                var $inputs = $form.find(selector).filter(isBookingDateCandidate);
                var $visible = $inputs.filter(':visible').first();

                return $visible.length ? $visible : $inputs.first();
        }

        function findSelfDriveRentalDateInput($form) {
                var $specificInput = $form.find([
                        '.ovabrw_checkin_field input.checkin-date[name="ovabrw_pickup_date"]',
                        '.ovabrw_checkin_field input[name="ovabrw_pickup_date"]',
                        'input.checkin-date[name="ovabrw_pickup_date"]',
                        '.ovabrw_checkin_field input.ovabrw-datepicker'
                ].join(',')).not('.hr-self-drive-return-date-input').filter(isBookingDateCandidate);
                var $specificVisible = $specificInput.filter(':visible').first();
                var $input = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR).not('.hr-self-drive-return-date-input').first();
                var $candidates;
                var $visible;

                if ($specificInput.length) {
                        return $specificVisible.length ? $specificVisible : $specificInput.first();
                }

                if ($input.length) {
                        return $input;
                }

                $candidates = $form.find('input').not('.hr-self-drive-return-date-input').filter(function () {
                        var $candidate = $(this);
                        var type = String($candidate.attr('type') || '').toLowerCase();
                        var $container;
                        var fieldText;

                        if (/^(hidden|file|checkbox|radio|button|submit|number)$/.test(type) || !isBookingDateCandidate.call(this)) {
                                return false;
                        }

                        $container = getLegacyAirportTransferFieldContainer($candidate);
                        fieldText = [
                                $candidate.attr('name') || '',
                                $candidate.attr('id') || '',
                                $candidate.attr('placeholder') || '',
                                $candidate.attr('aria-label') || '',
                                $container.text()
                        ].join(' ');

                        return /tour\s+date|rental\s+date|booking\s+date|pick[\s-]*up\s+date|pickup\s+date|start\s+date/i.test(fieldText);
                });
                $visible = $candidates.filter(':visible').first();

                return $visible.length ? $visible : $candidates.first();
        }

        function getDatepickerFormat($input) {
                try {
                        if ($.fn.datepicker && $input.data('datepicker')) {
                                return $input.datepicker('option', 'dateFormat');
                        }
                } catch (error) {
                        return '';
                }

                return '';
        }

        function normalizeDate(date) {
                if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
                        return null;
                }

                return new Date(date.getFullYear(), date.getMonth(), date.getDate());
        }

        function parseDateParts(value) {
                var match;
                var first;
                var second;
                var year;
                var month;
                var day;

                value = String(value || '').trim();

                match = value.match(/(\d{4})[\/.-](\d{1,2})[\/.-](\d{1,2})/);
                if (match) {
                        return normalizeDate(new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])));
                }

                match = value.match(/(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})/);
                if (!match) {
                        return null;
                }

                first = Number(match[1]);
                second = Number(match[2]);
                year = Number(match[3]);

                if (first > 12) {
                        day = first;
                        month = second;
                } else if (second > 12) {
                        month = first;
                        day = second;
                } else {
                        day = first;
                        month = second;
                }

                return normalizeDate(new Date(year, month - 1, day));
        }

        function parseBookingDate($input) {
                var value = $input.val();
                var dateFormat = getDatepickerFormat($input);
                var parsed;

                if (!$input.length || !value) {
                        return null;
                }

                if (isDisplayedPackageRangeValue(value) && $input.data('hr-package-start-date-value')) {
                        value = $input.data('hr-package-start-date-value');
                }

                if ($input[0]._flatpickr && $input[0]._flatpickr.selectedDates && $input[0]._flatpickr.selectedDates.length) {
                        return normalizeDate($input[0]._flatpickr.selectedDates[0]);
                }

                if ($input[0].valueAsDate) {
                        parsed = normalizeDate($input[0].valueAsDate);
                        if (parsed) {
                                return parsed;
                        }
                }

                if (dateFormat && $.datepicker && $.datepicker.parseDate) {
                        try {
                                parsed = normalizeDate($.datepicker.parseDate(dateFormat, value));
                                if (parsed) {
                                        return parsed;
                                }
                        } catch (error) {
                                parsed = null;
                        }
                }

                return parseDateParts(value) || normalizeDate(new Date(value));
        }

        function addCalendarDays(date, days) {
                var next = new Date(date.getFullYear(), date.getMonth(), date.getDate());

                next.setDate(next.getDate() + days);

                return next;
        }

        function getDateValueSuffix(value) {
                var match;

                value = String(value || '').trim();
                match = value.match(/^\d{4}[\/.-]\d{1,2}[\/.-]\d{1,2}(.*)$/) || value.match(/^\d{1,2}[\/.-]\d{1,2}[\/.-]\d{4}(.*)$/);

                return match && match[1] ? match[1] : '';
        }

        function formatDisplayDate(date) {
                var locale = (window.hrBooking && window.hrBooking.locale) ? window.hrBooking.locale : undefined;

                return date.toLocaleDateString(locale, {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                });
        }

        function formatPackageRangeInputDate(date) {
                var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

                date = normalizeDate(date);

                if (!date) {
                        return '';
                }

                return monthNames[date.getMonth()] + '-' + padDatePart(date.getDate()) + '-' + date.getFullYear();
        }

        function formatDateForField(date, $sourceInput, $targetInput) {
                var format = getDatepickerFormat($targetInput) || getDatepickerFormat($sourceInput);
                var sourceValue = String($sourceInput.data('hr-package-start-date-value') || $sourceInput.val() || '');
                var targetValue = String($targetInput.val() || '');
                var suffix = isDisplayedPackageRangeValue(targetValue) ? '' : getDateValueSuffix(targetValue);
                var separatorMatch = sourceValue.match(/[\/.-]/);
                var separator = separatorMatch ? separatorMatch[0] : '-';
                var formatted;

                suffix = suffix || getDateValueSuffix(sourceValue);

                if ($targetInput.attr('type') === 'date') {
                        return [
                                date.getFullYear(),
                                padDatePart(date.getMonth() + 1),
                                padDatePart(date.getDate())
                        ].join('-');
                }

                if (format && $.datepicker && $.datepicker.formatDate) {
                        try {
                                return $.datepicker.formatDate(format, date) + suffix;
                        } catch (error) {
                                return '';
                        }
                }

                if (/^\d{4}[\/.-]\d{1,2}[\/.-]\d{1,2}/.test(sourceValue)) {
                        formatted = [
                                date.getFullYear(),
                                padDatePart(date.getMonth() + 1),
                                padDatePart(date.getDate())
                        ].join(separator);

                        return formatted + suffix;
                }

                formatted = [
                        padDatePart(date.getDate()),
                        padDatePart(date.getMonth() + 1),
                        date.getFullYear()
                ].join(separator);

                return formatted + suffix;
        }

        function setPackageStartDateRangeField($startInput, startDate, endDate) {
                var startValue;
                var endValue;
                var rangeValue;
                var $display;

                if (!$startInput.length || $startInput.attr('type') === 'date') {
                        return;
                }

                $startInput.data('hr-package-start-date-value', $startInput.val());

                startValue = formatPackageRangeInputDate(startDate);
                endValue = formatPackageRangeInputDate(endDate);

                if (!startValue || !endValue) {
                        return;
                }

                rangeValue = startValue + ' - ' + endValue;

                if (!$startInput.parent().hasClass('hr-package-date-range-input-wrap')) {
                        $startInput.wrap('<span class="hr-package-date-range-input-wrap"></span>');
                        $startInput.after('<span class="hr-package-date-range-input-display" aria-hidden="true"></span>');
                }

                $display = $startInput.siblings('.hr-package-date-range-input-display').first();
                $display.text(rangeValue);
                $startInput.addClass('hr-package-date-range-source');
        }

        function restorePackageStartDateInput($form) {
                var $startInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);
                var startValue = $startInput.data('hr-package-start-date-value');

                if (!$startInput.length || !startValue) {
                        return;
                }

                $startInput.val(startValue);
        }

        function clearPackageStartDateRangeField($form) {
                var $startInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);

                if (!$startInput.length) {
                        return;
                }

                $startInput.removeClass('hr-package-date-range-source');
                $startInput.siblings('.hr-package-date-range-input-display').text('');
        }

        function getPackageCalendarDateKey(date) {
                date = normalizeDate(date);

                if (!date) {
                        return '';
                }

                return [
                        date.getFullYear(),
                        padDatePart(date.getMonth() + 1),
                        padDatePart(date.getDate())
                ].join('-');
        }

        function getPackageCalendarRangeState($form) {
                var startDate = $form.data('hr-package-calendar-range-start');
                var endDate = $form.data('hr-package-calendar-range-end');

                startDate = startDate instanceof Date ? normalizeDate(startDate) : null;
                endDate = endDate instanceof Date ? normalizeDate(endDate) : null;

                if (!startDate || !endDate) {
                        return null;
                }

                return {
                        startDate: startDate,
                        endDate: endDate,
                        startTime: startDate.getTime(),
                        endTime: endDate.getTime(),
                        startKey: getPackageCalendarDateKey(startDate),
                        endKey: getPackageCalendarDateKey(endDate)
                };
        }

        function setPackageCalendarRangeState($form, startDate, endDate) {
                $form
                        .data('hr-package-calendar-range-start', normalizeDate(startDate))
                        .data('hr-package-calendar-range-end', normalizeDate(endDate));
        }

        function resetPackageCalendarRangeElement(element) {
                if (!element) {
                        return;
                }

                $(element).removeClass('hr-package-calendar-range-marked hr-package-calendar-date-in-range hr-package-calendar-date-start hr-package-calendar-date-end');

                if ($(element).attr('data-hr-package-calendar-inline-style') !== '1') {
                        return;
                }

                element.style.removeProperty('background');
                element.style.removeProperty('border-color');
                element.style.removeProperty('color');
                element.style.removeProperty('box-shadow');
                $(element).removeAttr('data-hr-package-calendar-inline-style');
        }

        function clearPackageCalendarRangeClasses() {
                $('.hr-package-calendar-range-marked').each(function () {
                        resetPackageCalendarRangeElement(this);
                });

                $('easepick-wrapper, .easepick-wrapper').each(function () {
                        if (!this.shadowRoot || !this.shadowRoot.querySelectorAll) {
                                return;
                        }

                        $(this.shadowRoot.querySelectorAll('.hr-package-calendar-range-marked')).each(function () {
                                resetPackageCalendarRangeElement(this);
                        });
                });
        }

        function clearPackageCalendarRangeState($form) {
                $form
                        .removeData('hr-package-calendar-range-start')
                        .removeData('hr-package-calendar-range-end');
                clearPackageCalendarRangeClasses();
        }

        function setActivePackageCalendarRangeForm($form) {
                packageCalendarRangeActiveForm = $form && $form.length ? $form : null;
        }

        function getActivePackageCalendarRangeForm() {
                if (!packageCalendarRangeActiveForm || !packageCalendarRangeActiveForm.length || !packageCalendarRangeActiveForm.closest('body').length) {
                        return $();
                }

                return packageCalendarRangeActiveForm;
        }

        function getPackageCalendarDateFromParts($element) {
                var rawYear = $element.attr('data-year');
                var rawMonth = $element.attr('data-month');
                var rawDay = $element.attr('data-date') || $element.attr('data-day');
                var year;
                var month;
                var day;

                if (typeof rawYear === 'undefined' || typeof rawMonth === 'undefined' || typeof rawDay === 'undefined') {
                        return null;
                }

                year = Number(rawYear);
                month = Number(rawMonth);
                day = Number(rawDay);

                if (!Number.isFinite(year) || !Number.isFinite(month) || !Number.isFinite(day)) {
                        return null;
                }

                return normalizeDate(new Date(year, month, day));
        }

        function getPackageCalendarDateFromData($element) {
                var rawValue = $element.attr('data-hr-calendar-date') || $element.attr('data-date') || $element.attr('data-time');
                var numericValue;
                var parsed;

                if (!rawValue) {
                        return null;
                }

                if (/^-?\d+$/.test(String(rawValue))) {
                        numericValue = Number(rawValue);

                        if (Number.isFinite(numericValue) && Math.abs(numericValue) > 10000000) {
                                return normalizeDate(new Date(Math.abs(numericValue) < 10000000000 ? numericValue * 1000 : numericValue));
                        }

                        return null;
                }

                parsed = parseDateParts(rawValue);

                if (parsed) {
                        return parsed;
                }

                return normalizeDate(new Date(rawValue));
        }

        function parsePackageCalendarMonthLabel(label) {
                var parsed = normalizeDate(new Date('1 ' + String(label || '').replace(/\s+/g, ' ').trim()));

                if (!parsed) {
                        return null;
                }

                return new Date(parsed.getFullYear(), parsed.getMonth(), 1);
        }

        function getPackageCalendarVisibleMonth($calendar) {
                var label = $.trim($calendar.find('.datepicker-switch:visible, .ui-datepicker-title:visible, .xdsoft_month span:visible, .xdsoft_year span:visible').map(function () {
                        return $(this).text();
                }).get().join(' '));

                return parsePackageCalendarMonthLabel(label);
        }

        function getPackageCalendarDayDate(element, $calendar) {
                var $element = $(element);
                var date = element && element.dateObj ? normalizeDate(element.dateObj) : null;
                var monthDate;
                var day;
                var month;
                var year;

                if (date) {
                        return date;
                }

                date = getPackageCalendarDateFromParts($element) || getPackageCalendarDateFromData($element);

                if (date) {
                        return date;
                }

                day = parseInt($.trim($element.text()), 10);

                if (!Number.isFinite(day)) {
                        return null;
                }

                monthDate = getPackageCalendarVisibleMonth($calendar);

                if (!monthDate) {
                        return null;
                }

                year = monthDate.getFullYear();
                month = monthDate.getMonth();

                if ($element.hasClass('old') || $element.hasClass('prev') || $element.hasClass('prevMonthDay')) {
                        month -= 1;
                } else if ($element.hasClass('new') || $element.hasClass('next') || $element.hasClass('nextMonthDay')) {
                        month += 1;
                }

                return normalizeDate(new Date(year, month, day));
        }

        function markPackageCalendarDay($element, date, rangeState) {
                var key = getPackageCalendarDateKey(date);
                var time;

                if (!$element || !$element.length || !key || !rangeState) {
                        return;
                }

                time = normalizeDate(date).getTime();

                if (time < rangeState.startTime || time > rangeState.endTime) {
                        return;
                }

                $element.addClass('hr-package-calendar-range-marked hr-package-calendar-date-in-range');

                if (key === rangeState.startKey) {
                        $element.addClass('hr-package-calendar-date-start');
                }

                if (key === rangeState.endKey) {
                        $element.addClass('hr-package-calendar-date-end');
                }

                if (isPackageEasepickCalendarElement($element[0])) {
                        applyPackageCalendarInlineStyle($element[0]);
                }
        }

        function isPackageEasepickCalendarElement(element) {
                var root = element && element.getRootNode ? element.getRootNode() : null;

                return !!(
                        element &&
                        (
                                $(element).closest('.easepick-container').length ||
                                (root && root.host && $(root.host).is('easepick-wrapper, .easepick-wrapper'))
                        )
                );
        }

        function applyPackageCalendarInlineStyle(element) {
                if (!element || !element.style) {
                        return;
                }

                element.style.setProperty('background', '#0687c1', 'important');
                element.style.setProperty('border-color', '#0687c1', 'important');
                element.style.setProperty('color', '#ffffff', 'important');
                element.style.setProperty('box-shadow', 'none', 'important');
                $(element).attr('data-hr-package-calendar-inline-style', '1');
        }

        function bindPackageFlatpickrRangeHooks($form) {
                var $startInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);
                var input = $startInput[0];
                var flatpickrInstance = input && input._flatpickr ? input._flatpickr : null;
                var scheduleHighlight;

                if (!flatpickrInstance || flatpickrInstance.hrPackageRangeHooksBound) {
                        return;
                }

                scheduleHighlight = function () {
                        setActivePackageCalendarRangeForm($form);
                        schedulePackageCalendarRangeHighlight($form);
                };

                ['onReady', 'onOpen', 'onMonthChange', 'onYearChange', 'onValueUpdate'].forEach(function (hookName) {
                        if ($.isArray(flatpickrInstance.config[hookName])) {
                                flatpickrInstance.config[hookName].push(scheduleHighlight);
                        } else if (typeof flatpickrInstance.config[hookName] === 'function') {
                                flatpickrInstance.config[hookName] = [flatpickrInstance.config[hookName], scheduleHighlight];
                        } else {
                                flatpickrInstance.config[hookName] = [scheduleHighlight];
                        }
                });

                flatpickrInstance.hrPackageRangeHooksBound = true;
        }

        function getPackageCalendarContainers($form) {
                var $startInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);
                var input = $startInput[0];
                var flatpickrInstance = input && input._flatpickr ? input._flatpickr : null;
                var $activeForm = getActivePackageCalendarRangeForm();
                var $calendars = $();

                if (flatpickrInstance && flatpickrInstance.calendarContainer) {
                        $calendars = $calendars.add(flatpickrInstance.calendarContainer);
                }

                if ($activeForm.length && $activeForm[0] === $form[0]) {
                        $calendars = $calendars.add($('.flatpickr-calendar.open, .ui-datepicker:visible, .xdsoft_datetimepicker:visible, .datepicker:visible, .datepicker-dropdown:visible, .daterangepicker:visible'));
                }

                return $calendars;
        }

        function getPackageEasepickCalendarRoots($form) {
                var roots = [];
                var $activeForm = getActivePackageCalendarRangeForm();
                var $wrappers = $form.find('easepick-wrapper, .easepick-wrapper');

                if ($activeForm.length && $activeForm[0] === $form[0]) {
                        $wrappers = $wrappers.add($('easepick-wrapper, .easepick-wrapper'));
                }

                $wrappers.each(function () {
                        var root = this.shadowRoot || this;

                        if (root && roots.indexOf(root) === -1) {
                                roots.push(root);
                        }
                });

                return roots;
        }

        function highlightPackageCalendarRoot(root, rangeState, daySelector) {
                if (!root || !root.querySelectorAll) {
                        return;
                }

                $(root.querySelectorAll(daySelector)).each(function () {
                        var $day = $(this);
                        var date = getPackageCalendarDayDate(this, $(root.host || root));

                        if (!date) {
                                return;
                        }

                        markPackageCalendarDay($day, date, rangeState);

                        if ($day.is('td')) {
                                markPackageCalendarDay($day.children('a, span, div').first(), date, rangeState);
                        }
                });
        }

        function highlightPackageCalendarRange($form) {
                var rangeState;
                var daySelector = [
                        '.flatpickr-day',
                        '.easepick-container .day[data-time]',
                        '.day[data-time]',
                        '.xdsoft_calendar td',
                        '.ui-datepicker-calendar td[data-year][data-month]',
                        '.ui-datepicker-calendar td[data-handler="selectDay"]',
                        '[data-hr-calendar-date]',
                        'td[data-year][data-month]',
                        'td[data-date]',
                        '.datepicker-days td.day',
                        '.datepicker table tr td.day'
                ].join(',');

                $form = $form && $form.length ? $form : getActivePackageCalendarRangeForm();

                clearPackageCalendarRangeClasses();

                if (!$form.length || !$form.hasClass('hr-package-booking-form')) {
                        return;
                }

                rangeState = getPackageCalendarRangeState($form);

                if (!rangeState) {
                        return;
                }

                bindPackageFlatpickrRangeHooks($form);

                getPackageCalendarContainers($form).each(function () {
                        var $calendar = $(this);

                        $calendar.find(daySelector).each(function () {
                                var $day = $(this);
                                var date = getPackageCalendarDayDate(this, $calendar);

                                if (!date) {
                                        return;
                                }

                                markPackageCalendarDay($day, date, rangeState);

                                if ($day.is('td')) {
                                        markPackageCalendarDay($day.children('a, span, div').first(), date, rangeState);
                                }
                        });
                });

                getPackageEasepickCalendarRoots($form).forEach(function (root) {
                        highlightPackageCalendarRoot(root, rangeState, daySelector);
                });
        }

        function schedulePackageCalendarRangeHighlight($form) {
                [0, 50, 120, 250, 500].forEach(function (delay) {
                        window.setTimeout(function () {
                                highlightPackageCalendarRange($form);
                        }, delay);
                });
        }

        function installPackageCalendarRangeDocumentHooks() {
                if (packageCalendarRangeDocumentHooksBound) {
                        return;
                }

                packageCalendarRangeDocumentHooksBound = true;

                $(document).on('click.hrPackageCalendarRange change.hrPackageCalendarRange', 'easepick-wrapper, .easepick-wrapper, .flatpickr-calendar, .ui-datepicker, .xdsoft_datetimepicker, .datepicker, .datepicker-dropdown, .daterangepicker', function () {
                        var $activeForm = getActivePackageCalendarRangeForm();

                        if ($activeForm.length) {
                                schedulePackageCalendarRangeHighlight($activeForm);
                        }
                });

                $(document).on('focusin.hrPackageCalendarRange', PACKAGE_START_DATE_INPUT_SELECTOR + ',' + PACKAGE_END_DATE_INPUT_SELECTOR, function () {
                        var $form = $(this).closest('form');

                        if (!$form.hasClass('hr-package-booking-form')) {
                                setActivePackageCalendarRangeForm(null);
                                clearPackageCalendarRangeClasses();
                        }
                });
        }

        function bindPackageCalendarRangeHooks($form) {
                installPackageCalendarRangeDocumentHooks();
                bindPackageFlatpickrRangeHooks($form);

                if ($form.data('hr-package-calendar-range-bound')) {
                        return;
                }

                $form.data('hr-package-calendar-range-bound', true);

                $form.on('focus.hrPackageCalendarRange click.hrPackageCalendarRange', PACKAGE_START_DATE_INPUT_SELECTOR, function () {
                        setActivePackageCalendarRangeForm($form);
                        bindPackageFlatpickrRangeHooks($form);
                        schedulePackageCalendarRangeHighlight($form);
                });
        }

        function syncPackageEndDateField($form, $startInput, endDate) {
                var $endInput = findBookingDateInput($form, PACKAGE_END_DATE_INPUT_SELECTOR);
                var endValue;

                if (!$endInput.length || $endInput[0] === $startInput[0]) {
                        return;
                }

                endValue = formatDateForField(endDate, $startInput, $endInput);

                if (!endValue || $endInput.val() === endValue) {
                        return;
                }

                if ($.fn.datepicker && $endInput.data('datepicker')) {
                        try {
                                $endInput.datepicker('setDate', endDate);
                        } catch (error) {
                                $endInput.val(endValue);
                        }
                } else {
                        $endInput.val(endValue);
                }

                $endInput.trigger('change');
        }

        function syncPackageDateRange($form, packageData) {
                var $field = $form.find('.hr-package-booking-field');
                var $range = $field.find('.hr-package-booking-date-range');
                var $startInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);
                var packageDays = parseInt(packageData && packageData.days, 10);
                var startDate;
                var endDate;
                var label;

                if (!$field.length || !$range.length || !packageDays) {
                        return;
                }

                startDate = parseBookingDate($startInput);

                if (!startDate) {
                        $range.text(window.hrBooking ? window.hrBooking.packageDateRangePlaceholder : '');
                        clearPackageStartDateRangeField($form);
                        clearPackageCalendarRangeState($form);
                        return;
                }

                endDate = addCalendarDays(startDate, Math.max(packageDays - 1, 0));
                label = window.hrBooking ? window.hrBooking.packageDateRangeLabel : 'Package dates: %1$s to %2$s';

                $range.text(label.replace('%1$s', formatDisplayDate(startDate)).replace('%2$s', formatDisplayDate(endDate)));
                setPackageStartDateRangeField($startInput, startDate, endDate);
                setPackageCalendarRangeState($form, startDate, endDate);
                bindPackageFlatpickrRangeHooks($form);
                schedulePackageCalendarRangeHighlight($form);
                syncPackageEndDateField($form, $startInput, endDate);
        }

        function schedulePackageDateRangeSync($form, packageData) {
                [0, 100, 350].forEach(function (delay) {
                        window.setTimeout(function () {
                                syncPackageDateRange($form, packageData);
                        }, delay);
                });
        }

        function triggerBookingRecalculation($form) {
                var $quantity = $form.find('input[name="ovabrw_quantity"]');

                if ($quantity.length) {
                        $quantity.trigger('change');
                }
        }

        function getPackageAjaxTotal($form) {
                return $form.find('.ajax-show-total .show-total-number, .ovabrw-ajax-total .show-total-number').first();
        }

        function syncPackageAjaxTotal($form) {
                var total = Number($form.data('hr-package-total') || 0);
                var valid = $form.data('hr-package-total-valid') === true;
                var $ajaxTotal = getPackageAjaxTotal($form);

                if (!$ajaxTotal.length) {
                        return;
                }

                $ajaxTotal.text(valid && total > 0 && Number.isFinite(total) ? formatPrice(total) : '');
        }

        function schedulePackageAjaxTotalSync($form) {
                [0, 100, 350, 800].forEach(function (delay) {
                        window.setTimeout(function () {
                                syncPackageAjaxTotal($form);
                        }, delay);
                });
        }

        function hidePackageAjaxTotal($form) {
                var $ajaxTotal = $form.find('.ajax-show-total');

                $form.addClass('hr-package-booking-form');

                $ajaxTotal.each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });

                $ajaxTotal.find('.ovabrw-ajax-total').each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });
        }

        function hidePackageSpecialRequestField($form) {
                $form.find('textarea').each(function () {
                        var $textarea = $(this);
                        var $container = $textarea.closest('.rental_item, .ovabrw_custom_ckf, .ovabrw-form-field, .ova-form-field, .form-group, p');
                        var fieldText = [
                                $textarea.attr('name') || '',
                                $textarea.attr('id') || '',
                                $textarea.attr('placeholder') || '',
                                $textarea.attr('aria-label') || '',
                                $textarea.closest('label').text() || '',
                                $textarea.prev('label').text() || '',
                                $container.find('label').first().text() || ''
                        ].join(' ').toLowerCase();

                        if (!/(?:special[\s_-]*request)/i.test(fieldText)) {
                                return;
                        }

                        if (!$container.length || $container.is('textarea')) {
                                $container = $textarea;
                        }

                        $textarea
                                .prop('required', false)
                                .prop('disabled', true)
                                .removeAttr('aria-required')
                                .val('');

                        $container.each(function () {
                                this.style.setProperty('display', 'none', 'important');
                        });
                });
        }

        function schedulePackageSpecialRequestHide($form) {
                [0, 100, 350, 800, 1500].forEach(function (delay) {
                        window.setTimeout(function () {
                                hidePackageSpecialRequestField($form);
                        }, delay);
                });
        }

        function getTourData($form) {
                var $field = $form.find('.hr-tour-booking-field');
                var raw = $field.attr('data-tour');

                if (!$field.length || !raw) {
                        return null;
                }

                try {
                        return JSON.parse(raw);
                } catch (error) {
                        return null;
                }
        }

        function syncTourAjaxTotal($form) {
                var total = Number($form.data('hr-tour-total') || 0);
                var valid = $form.data('hr-tour-total-valid') === true;
                var $ajaxTotal = getPackageAjaxTotal($form);

                if (!$ajaxTotal.length) {
                        return;
                }

                $ajaxTotal.text(valid && total > 0 && Number.isFinite(total) ? formatPrice(total) : '');
        }

        function scheduleTourAjaxTotalSync($form) {
                [0, 100, 350, 800].forEach(function (delay) {
                        window.setTimeout(function () {
                                syncTourAjaxTotal($form);
                                syncTourBookingTotalDisplay($form);
                                syncTourGuestRateDisplays($form);
                        }, delay);
                });
        }

        function hideTourAjaxTotal($form) {
                var $ajaxTotal = $form.find('.ajax-show-total');

                $form.addClass('hr-tour-booking-form');

                $ajaxTotal.each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });

                $ajaxTotal.find('.ovabrw-ajax-total').each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });
        }

        function getAirportTransferData($form) {
                var $field = $form.find('.hr-airport-transfer-booking-field');
                var raw = $field.attr('data-airport-transfer');

                if (!$field.length || !raw) {
                        return null;
                }

                try {
                        return JSON.parse(raw);
                } catch (error) {
                        return null;
                }
        }

        function hideAirportTransferAjaxTotal($form) {
                var $ajaxTotal = $form.find('.ajax-show-total');

                $form.addClass('hr-airport-transfer-booking-form');

                $ajaxTotal.each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });

                $ajaxTotal.find('.ovabrw-ajax-total').each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });
        }

        function syncAirportTransferAjaxTotal($form) {
                var total = Number($form.data('hr-airport-transfer-total') || 0);
                var valid = $form.data('hr-airport-transfer-total-valid') === true;
                var $ajaxTotal = getPackageAjaxTotal($form);

                if (!$ajaxTotal.length) {
                        return;
                }

                $ajaxTotal.text(valid && Number.isFinite(total) ? formatPrice(total) : '');
        }

        function scheduleAirportTransferAjaxTotalSync($form) {
                [0, 100, 350, 800].forEach(function (delay) {
                        window.setTimeout(function () {
                                syncAirportTransferAjaxTotal($form);
                        }, delay);
                });
        }

        function getBoatData($form) {
                var $field = $form.find('.hr-boat-booking-field');
                var raw = $field.attr('data-boat');

                if (!$field.length || !raw) {
                        return null;
                }

                try {
                        return JSON.parse(raw);
                } catch (error) {
                        return null;
                }
        }

        function hideBoatAjaxTotal($form) {
                var $ajaxTotal = $form.find('.ajax-show-total');

                $form.addClass('hr-boat-booking-form');

                $ajaxTotal.each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });

                $ajaxTotal.find('.ovabrw-ajax-total').each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });
        }

        function syncBoatAjaxTotal($form) {
                var total = Number($form.data('hr-boat-total') || 0);
                var valid = $form.data('hr-boat-total-valid') === true;
                var $ajaxTotal = getPackageAjaxTotal($form);

                if (!$ajaxTotal.length) {
                        return;
                }

                $ajaxTotal
                        .text(valid && Number.isFinite(total) ? String(total) : '')
                        .attr('data-total', valid && Number.isFinite(total) ? String(total) : '');
        }

        function scheduleBoatAjaxTotalSync($form) {
                [0, 100, 350, 800].forEach(function (delay) {
                        window.setTimeout(function () {
                                syncBoatAjaxTotal($form);
                        }, delay);
                });
        }

        function renderBoatSelectors($form, boatData) {
                var $field = $form.find('.hr-boat-booking-field');
                var $wrap = $field.find('.hr-boat-selectors');
                var rates = boatData && $.isArray(boatData.rates) ? boatData.rates : [];
                var boardingTimes = boatData && $.isArray(boatData.boardingTimes) ? boatData.boardingTimes : [];
                var upgradeFeeAmount = boatData ? Number(boatData.upgradeFeeAmount || 0) : 0;
                var upgradeFeeLabel = boatData && boatData.upgradeFeeLabel ? String(boatData.upgradeFeeLabel) : 'Upgrade to Bigger Boat Fee';
                var maxPassengers = 0;
                var passengerId = 'hr-boat-passengers-' + Math.floor(Math.random() * 1000000);
                var html = '';

                if (!$wrap.length) {
                        return;
                }

                rates.forEach(function (rate) {
                        maxPassengers = Math.max(maxPassengers, parseInt(rate.maxPax, 10) || 0);
                });

                html += '<div class="hr-boat-passenger-row">';
                html += '<label class="hr-boat-passenger-label" for="' + passengerId + '">' + escapeHtml(window.hrBooking ? window.hrBooking.boatPassengersLabel : 'No. of Passengers') + '</label>';
                html += '<button type="button" class="hr-boat-qty-btn hr-boat-passenger-minus" aria-label="Decrease passengers">-</button>';
                html += '<input type="number" id="' + passengerId + '" class="hr-boat-passenger-count ovabrw-input-required" name="hr_boat_passengers_display" value="1" min="1" step="1" inputmode="numeric" required aria-required="true">';
                html += '<button type="button" class="hr-boat-qty-btn hr-boat-passenger-plus" aria-label="Increase passengers">+</button>';
                html += '</div>';

                html += '<div class="hr-boat-field-group">';
                html += '<select class="hr-boat-rate-select ovabrw-input-required" required aria-required="true">';
                html += '<option value="">' + escapeHtml(window.hrBooking ? window.hrBooking.boatRateCapacityPlaceholder : 'Boat Rate and Capacity') + '</option>';
                rates.forEach(function (rate, index) {
                        var label = rate.label || '';
                        var optionText = label;
                        var price = Number(rate.rate);

                        if (Number.isFinite(price)) {
                                optionText += ' = ' + formatPrice(price);
                        }

                        html += '<option value="' + index + '" data-label="' + escapeHtml(label) + '" data-min-pax="' + escapeHtml(rate.minPax || 0) + '" data-max-pax="' + escapeHtml(rate.maxPax || 0) + '" data-rate="' + escapeHtml(rate.rate || 0) + '">' + escapeHtml(optionText) + '</option>';
                });
                html += '</select>';
                html += '</div>';

                html += '<div class="hr-boat-field-group">';
                html += '<select class="hr-boat-boarding-time-select ovabrw-input-required" required aria-required="true">';
                html += '<option value="">' + escapeHtml(window.hrBooking ? window.hrBooking.boatBoardingTimePlaceholder : 'Choose Boarding Time') + '</option>';
                boardingTimes.forEach(function (time) {
                        html += '<option value="' + escapeHtml(time) + '">' + escapeHtml(time) + '</option>';
                });
                html += '</select>';
                html += '</div>';

                if (Number.isFinite(upgradeFeeAmount) && upgradeFeeAmount > 0) {
                        html += '<label class="hr-boat-upgrade-fee-label">';
                        html += '<input type="checkbox" class="hr-boat-upgrade-fee-option" value="1" data-label="' + escapeHtml(upgradeFeeLabel) + '" data-amount="' + escapeHtml(upgradeFeeAmount) + '">';
                        html += '<span>' + escapeHtml(upgradeFeeLabel) + ' (+' + formatPrice(upgradeFeeAmount) + ')</span>';
                        html += '</label>';
                }

                $wrap.html(html);

                if (maxPassengers > 0) {
                        $wrap.find('.hr-boat-passenger-count').attr('max', String(maxPassengers));
                }
        }

        function labelBoatDateField($form) {
                var $dateInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);
                var labelText = window.hrBooking && window.hrBooking.boatRentalDateLabel ? window.hrBooking.boatRentalDateLabel : 'Rental Date';
                var $container;
                var $label;

                if (!$dateInput.length) {
                        return;
                }

                $container = getLegacyAirportTransferFieldContainer($dateInput);
                if ($container.is('input, textarea, select')) {
                        $container = $dateInput.parent();
                }

                $label = $container.find('label').first();
                if ($label.length) {
                        $label.contents().filter(function () {
                                return this.nodeType === 3;
                        }).first().replaceWith(labelText + ' ');
                }
        }

        function getBoatNativeMinDate() {
                var today = normalizeDate(new Date());

                return [
                        today.getFullYear(),
                        padDatePart(today.getMonth() + 1),
                        padDatePart(today.getDate())
                ].join('-');
        }

        function applyBoatDateMinimum($form) {
                var $dateInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);
                var today = normalizeDate(new Date());
                var selectedDate;

                if (!$dateInput.length || !today) {
                        return;
                }

                $dateInput.attr('min', getBoatNativeMinDate());

                if ($dateInput[0]._flatpickr) {
                        $dateInput[0]._flatpickr.set('minDate', 'today');
                }

                try {
                        if ($.fn.datepicker && $dateInput.data('datepicker')) {
                                $dateInput.datepicker('option', 'minDate', 0);
                        }
                } catch (error) {
                        // Keep the field usable if a theme datepicker rejects runtime options.
                }

                selectedDate = parseBookingDate($dateInput);
                if (selectedDate && selectedDate.getTime() < today.getTime()) {
                        $dateInput.val('').attr('value', '');

                        if ($dateInput[0]._flatpickr) {
                                $dateInput[0]._flatpickr.clear();
                        }

                        try {
                                if ($.fn.datepicker && $dateInput.data('datepicker')) {
                                        $dateInput.datepicker('setDate', null);
                                }
                        } catch (error) {
                                // The visible value has already been cleared.
                        }
                }
        }

        function getBoatRentalDateValue($form) {
                var $dateInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);

                return $dateInput.length ? String($dateInput.val() || '') : '';
        }

        function getBoatPassengerCount($form) {
                var $passengerInput = $form.find('.hr-boat-passenger-count').first();
                var value = parseInt($passengerInput.length ? $passengerInput.val() : $form.find('input[name="hr_boat_passengers"]').val(), 10);

                if (!Number.isFinite(value) || value < 1) {
                        return 0;
                }

                return value;
        }

        function getSelectedBoatRate($field) {
                var $select = $field.find('.hr-boat-rate-select').first();
                var value = $select.val();
                var $option;
                var rate;

                if (!$select.length || value === '') {
                        return null;
                }

                $option = $select.find('option:selected');
                rate = Number($option.data('rate'));

                return {
                        index: parseInt(value, 10),
                        label: String($option.data('label') || $option.text() || ''),
                        minPax: parseInt($option.data('min-pax'), 10) || 1,
                        maxPax: parseInt($option.data('max-pax'), 10) || 1,
                        rate: rate
                };
        }

        function setBoatFormValue($form, name, value) {
                var $fields = $form.find('[name="' + name + '"]');

                if (!$fields.length) {
                        $fields = $('<input>', {
                                type: 'hidden',
                                name: name
                        }).appendTo($form);
                }

                $fields.val(value).attr('value', value);
        }

        function syncBoatGuestFields($form) {
                var passengerCount = getBoatPassengerCount($form);

                if (!passengerCount) {
                        setBoatFormValue($form, 'hr_boat_passengers', '');
                        return;
                }

                setBoatFormValue($form, 'ovabrw_adults', String(passengerCount));
                setBoatFormValue($form, 'ovabrw_childrens', '0');
                setBoatFormValue($form, 'ovabrw_babies', '0');
                setBoatFormValue($form, 'hr_boat_passengers', String(passengerCount));
        }

        function hideBoatGuestFields($form) {
                var passengerCount = Math.max(getBoatPassengerCount($form), 1);

                $form.find(GUEST_INPUT_SELECTOR).each(function () {
                        var $input = $(this);
                        var name = String($input.attr('name') || '');
                        var value = name === 'ovabrw_adults' ? String(passengerCount) : '0';

                        $input
                                .val(value)
                                .attr('value', value)
                                .prop('required', false)
                                .removeAttr('required')
                                .removeAttr('aria-required')
                                .removeClass('ovabrw-input-required error');

                        hideLegacyAirportTransferField($input);
                });

                setBoatFormValue($form, 'ovabrw_adults', String(passengerCount));
                setBoatFormValue($form, 'ovabrw_childrens', '0');
                setBoatFormValue($form, 'ovabrw_babies', '0');
        }

        function hasBoatValidationAttempted($form) {
                return $form.data('hr-boat-validation-attempted') === true;
        }

        function markBoatValidationAttempted($form) {
                $form.data('hr-boat-validation-attempted', true);
                $form.addClass('hr-boat-validation-attempted');
        }

        function setBoatValidationMessage($form, $target, message) {
                $target.text(hasBoatValidationAttempted($form) ? (message || '') : '');
        }

        function clearBoatBaseValidation($form) {
                var baseErrorPattern = /(?:^|\b)field\s+is\s+required\.?$|number\s+of\s+guests|guests?\s+is\s+required|passengers?\s+is\s+required|passenger.*required|total\s+must\s+be\s+greater\s+than\s+0|total.*greater\s+than\s+0/i;
                var boatTotal = Number($form.find('input[name="hr_boat_total"]').val() || 0);
                var totalIsValid = Number.isFinite(boatTotal) && boatTotal > 0;

                $form.find('label, span, p, div, li').each(function () {
                        var $element = $(this);
                        var directText = getDirectText($element);
                        var fullText = $.trim($element.text()).replace(/\s+/g, ' ');
                        var isAjaxError = $element.hasClass('ajax-error');

                        if (!baseErrorPattern.test(directText) && !(isAjaxError && baseErrorPattern.test(fullText))) {
                                return;
                        }

                        if (isAjaxError) {
                                $element.empty().hide();
                                return;
                        }

                        $element.remove();
                });

                if (!hasBoatValidationAttempted($form) || totalIsValid) {
                        $form.find('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit')
                                .prop('disabled', false)
                                .removeAttr('disabled')
                                .removeClass('disabled loading');
                }
        }

        function neutralizeBoatHiddenRequiredFields($form) {
                var $boatField = $form.find('.hr-boat-booking-field');

                if (!$boatField.length) {
                        return;
                }

                $form.find('.ovabrw-input-required, [required], [aria-required="true"]').not($boatField.find('.ovabrw-input-required, [required], [aria-required="true"]')).each(function () {
                        var $control = $(this);
                        var type = String($control.attr('type') || '').toLowerCase();
                        var hidden = type === 'hidden' ||
                                !$control.is(':visible') ||
                                $control.closest('.hr-legacy-airport-transfer-field-hidden, [aria-hidden="true"], .ovabrw-wrapper-guestspicker').length > 0;

                        if (!hidden) {
                                return;
                        }

                        $control
                                .prop('required', false)
                                .removeAttr('required')
                                .removeAttr('aria-required')
                                .removeAttr('data-error')
                                .removeClass('ovabrw-input-required ovabrw-required error');

                        if ($control.is('input, textarea') && !$control.val()) {
                                $control.val(type === 'checkbox' || type === 'radio' ? 'yes' : '1').attr('value', type === 'checkbox' || type === 'radio' ? 'yes' : '1');
                        }
                });

                $form.find('.hr-legacy-airport-transfer-field-hidden .ovabrw-required, .ovabrw-wrapper-guestspicker .ovabrw-required')
                        .removeClass('ovabrw-required');
        }

        function scheduleBoatBaseValidationClear($form) {
                [0, 50, 100, 250, 500, 800, 1200, 2000].forEach(function (delay) {
                        window.setTimeout(function () {
                                clearBoatBaseValidation($form);
                        }, delay);
                });
        }

        function syncBoatBaseBookingState($form, total, passengerCount, valid) {
                var safeTotal = Number(total);
                var safePassengerCount = Math.max(parseInt(passengerCount, 10) || 0, 0);
                var totalValue = valid && Number.isFinite(safeTotal) && safeTotal > 0 ? String(safeTotal) : '';
                var dataValue = valid && Number.isFinite(safeTotal) && safeTotal > 0 ? safeTotal : '';

                setBoatFormValue($form, 'quantity', '1');
                setBoatFormValue($form, 'ovabrw_quantity', '1');
                setBoatFormValue($form, 'ovabrw_adults', String(Math.max(safePassengerCount, 1)));
                setBoatFormValue($form, 'ovabrw_childrens', '0');
                setBoatFormValue($form, 'ovabrw_babies', '0');
                setBoatFormValue($form, 'hr_boat_total', totalValue);
                setBoatFormValue($form, 'ovabrw_total', totalValue);
                setBoatFormValue($form, 'ovabrw_total_price', totalValue);
                setBoatFormValue($form, 'ovabrw_amount', totalValue);
                setBoatFormValue($form, 'total_price', totalValue);

                $form.find('.ajax-show-total, .ovabrw-ajax-total')
                        .attr('data-total', totalValue)
                        .attr('data-price', totalValue)
                        .attr('data-amount', totalValue)
                        .data('total', dataValue)
                        .data('price', dataValue)
                        .data('amount', dataValue);

                $form.find('.ajax-show-total .show-total-number, .ovabrw-ajax-total .show-total-number')
                        .text(totalValue)
                        .attr('data-total', totalValue)
                        .attr('data-price', totalValue)
                        .attr('data-amount', totalValue)
                        .data('total', dataValue)
                        .data('price', dataValue)
                        .data('amount', dataValue);

                if (valid && totalValue) {
                        clearBoatBaseValidation($form);
                        $form.find('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit')
                                .prop('disabled', false)
                                .removeAttr('disabled')
                                .removeClass('disabled loading');
                }
        }

        function hideLegacyBoatFields($form) {
                var $boatField = $form.find('.hr-boat-booking-field');

                if (!$boatField.length) {
                        return;
                }

                labelBoatDateField($form);
                applyBoatDateMinimum($form);
                hideBoatGuestFields($form);
                neutralizeBoatHiddenRequiredFields($form);
                clearBoatBaseValidation($form);
        }

        function scheduleBoatFormSync($form) {
                [0, 100, 350, 800, 1500].forEach(function (delay) {
                        window.setTimeout(function () {
                                hideLegacyBoatFields($form);
                        }, delay);
                });
        }

        function updateBoatSelection($form, boatData) {
                var $field = $form.find('.hr-boat-booking-field');
                var $total = $field.find('.hr-boat-booking-total');
                var $rentalDateInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);
                var rentalDate;
                var rentalDateObject;
                var today = normalizeDate(new Date());
                var passengers = getBoatPassengerCount($form);
                var selectedRate = getSelectedBoatRate($field);
                var boardingTime = $field.find('.hr-boat-boarding-time-select').val();
                var upgradeFeeAmount = boatData ? Number(boatData.upgradeFeeAmount || 0) : 0;
                var upgradeFeeLabel = boatData && boatData.upgradeFeeLabel ? String(boatData.upgradeFeeLabel) : 'Upgrade to Bigger Boat Fee';
                var upgradeFeeSelected = $field.find('.hr-boat-upgrade-fee-option').is(':checked') && Number.isFinite(upgradeFeeAmount) && upgradeFeeAmount > 0;
                var selectedUpgradeFeeAmount = upgradeFeeSelected ? upgradeFeeAmount : 0;
                var baseTotal = selectedRate ? Number(selectedRate.rate) : 0;
                var total = baseTotal + selectedUpgradeFeeAmount;
                var valid = false;

                if (!boatData || !$field.length) {
                        return false;
                }

                syncBoatGuestFields($form);
                applyBoatDateMinimum($form);
                rentalDate = getBoatRentalDateValue($form);
                rentalDateObject = parseBookingDate($rentalDateInput);

                $field.find('input[name="hr_boat_rental_date"]').val(rentalDate);
                $field.find('input[name="hr_boat_passengers"]').val(passengers ? String(passengers) : '');
                $field.find('input[name="hr_boat_rate_index"]').val(selectedRate ? String(selectedRate.index) : '');
                $field.find('input[name="hr_boat_rate_label"]').val(selectedRate ? selectedRate.label : '');
                $field.find('input[name="hr_boat_boarding_time"]').val(boardingTime || '');
                $field.find('input[name="hr_boat_selection_payload"]').val('');
                $field.find('input[name="hr_boat_base_total"]').val('');
                $field.find('input[name="hr_boat_upgrade_fee_selected"]').val('');
                $field.find('input[name="hr_boat_upgrade_fee_label"]').val('');
                $field.find('input[name="hr_boat_upgrade_fee_amount"]').val('');
                $field.find('input[name="hr_boat_total"]').val('');

                if (!rentalDate) {
                        setBoatValidationMessage($form, $total, window.hrBooking ? window.hrBooking.boatChooseRentalDate : '');
                        $form.data('hr-boat-total', 0).data('hr-boat-total-valid', false);
                        syncBoatBaseBookingState($form, 0, passengers, false);
                        syncBoatAjaxTotal($form);
                        return false;
                }

                if (!rentalDateObject || (today && rentalDateObject.getTime() < today.getTime())) {
                        setBoatValidationMessage($form, $total, window.hrBooking ? window.hrBooking.boatChooseRentalDate : '');
                        $form.data('hr-boat-total', 0).data('hr-boat-total-valid', false);
                        syncBoatBaseBookingState($form, 0, passengers, false);
                        syncBoatAjaxTotal($form);
                        return false;
                }

                if (!passengers) {
                        setBoatValidationMessage($form, $total, window.hrBooking ? window.hrBooking.boatChoosePassengers : '');
                        $form.data('hr-boat-total', 0).data('hr-boat-total-valid', false);
                        syncBoatBaseBookingState($form, 0, passengers, false);
                        syncBoatAjaxTotal($form);
                        return false;
                }

                if (!selectedRate || !Number.isFinite(baseTotal) || baseTotal <= 0) {
                        setBoatValidationMessage($form, $total, window.hrBooking ? window.hrBooking.boatChooseRate : '');
                        $form.data('hr-boat-total', 0).data('hr-boat-total-valid', false);
                        syncBoatBaseBookingState($form, 0, passengers, false);
                        syncBoatAjaxTotal($form);
                        return false;
                }

                if (passengers < selectedRate.minPax || passengers > selectedRate.maxPax) {
                        setBoatValidationMessage($form, $total, window.hrBooking ? window.hrBooking.boatInvalidLabel : '');
                        $form.data('hr-boat-total', 0).data('hr-boat-total-valid', false);
                        syncBoatBaseBookingState($form, 0, passengers, false);
                        syncBoatAjaxTotal($form);
                        return false;
                }

                if (!boardingTime) {
                        setBoatValidationMessage($form, $total, window.hrBooking ? window.hrBooking.boatChooseBoardingTime : '');
                        $form.data('hr-boat-total', 0).data('hr-boat-total-valid', false);
                        syncBoatBaseBookingState($form, 0, passengers, false);
                        syncBoatAjaxTotal($form);
                        return false;
                }

                valid = true;
                $field.find('input[name="hr_boat_selection_payload"]').val(JSON.stringify({
                        rateIndex: selectedRate.index,
                        passengers: passengers,
                        upgradeFeeSelected: upgradeFeeSelected
                }));
                $field.find('input[name="hr_boat_base_total"]').val(String(baseTotal));
                $field.find('input[name="hr_boat_upgrade_fee_selected"]').val(upgradeFeeSelected ? '1' : '');
                $field.find('input[name="hr_boat_upgrade_fee_label"]').val(upgradeFeeSelected ? upgradeFeeLabel : '');
                $field.find('input[name="hr_boat_upgrade_fee_amount"]').val(upgradeFeeSelected ? String(selectedUpgradeFeeAmount) : '');
                $field.find('input[name="hr_boat_total"]').val(String(total));
                $total.text((window.hrBooking ? window.hrBooking.boatTotalLabel : 'Boat rental total: %s').replace('%s', formatPrice(total)));
                $form.data('hr-boat-total', total).data('hr-boat-total-valid', valid);
                syncBoatBaseBookingState($form, total, passengers, valid);
                syncBoatAjaxTotal($form);
                return true;
        }

        function prepareBoatValidationState($form, boatData) {
                var valid;

                markBoatValidationAttempted($form);
                hideLegacyBoatFields($form);
                valid = updateBoatSelection($form, boatData);
                syncBoatAjaxTotal($form);
                scheduleBoatBaseValidationClear($form);

                return valid;
        }

        function stopBoatInvalidSubmit(event) {
                if (!event) {
                        return;
                }

                if (event.preventDefault) {
                        event.preventDefault();
                }

                if (event.stopImmediatePropagation) {
                        event.stopImmediatePropagation();
                } else if (event.stopPropagation) {
                        event.stopPropagation();
                }
        }

        function handleBoatValidationAttempt($form, boatData, event) {
                var valid = prepareBoatValidationState($form, boatData);

                if (!valid) {
                        stopBoatInvalidSubmit(event);
                }

                return valid;
        }

        function handleBoatPassengerButton($button, boatData) {
                var $form = $button.closest('form');
                var $qty = $form.find('.hr-boat-passenger-count').first();
                var current = parseInt($qty.val(), 10) || 0;
                var min = parseInt($qty.attr('min'), 10) || 0;
                var max = parseInt($qty.attr('max'), 10) || 99;

                if (!$form.length || !$qty.length) {
                        return;
                }

                if ($button.hasClass('hr-boat-passenger-plus')) {
                        current += 1;
                } else {
                        current -= 1;
                }

                current = Math.max(min, Math.min(current, max));
                $qty.val(String(current)).trigger('change');

                if (boatData) {
                        updateBoatSelection($form, boatData);
                        scheduleBoatAjaxTotalSync($form);
                }
        }

        function getDestinationTourData($form) {
                var $field = $form.find('.hr-destination-tour-booking-field');
                var raw = $field.attr('data-destination-tour');

                if (!$field.length || !raw) {
                        return null;
                }

                try {
                        return JSON.parse(raw);
                } catch (error) {
                        return null;
                }
        }

        function hideDestinationTourAjaxTotal($form) {
                var $ajaxTotal = $form.find('.ajax-show-total');

                $form.addClass('hr-destination-tour-booking-form');

                $ajaxTotal.each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });

                $ajaxTotal.find('.ovabrw-ajax-total').each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });
        }

        function syncDestinationTourAjaxTotal($form) {
                var total = Number($form.data('hr-destination-tour-total') || 0);
                var valid = $form.data('hr-destination-tour-total-valid') === true;
                var $ajaxTotal = getPackageAjaxTotal($form);

                if (!$ajaxTotal.length) {
                        return;
                }

                $ajaxTotal
                        .text(valid && Number.isFinite(total) ? String(total) : '')
                        .attr('data-total', valid && Number.isFinite(total) ? String(total) : '');
        }

        function scheduleDestinationTourAjaxTotalSync($form) {
                [0, 100, 350, 800].forEach(function (delay) {
                        window.setTimeout(function () {
                                syncDestinationTourAjaxTotal($form);
                        }, delay);
                });
        }

        function renderDestinationTourSelectors($form, destinationTourData) {
                var $field = $form.find('.hr-destination-tour-booking-field');
                var $wrap = $field.find('.hr-destination-tour-selectors');
                var destinations = destinationTourData && $.isArray(destinationTourData.destinations) ? destinationTourData.destinations : [];
                var pickupTimes = destinationTourData && $.isArray(destinationTourData.pickupTimes) ? destinationTourData.pickupTimes : [];
                var pickupAddons = destinationTourData && $.isArray(destinationTourData.pickupAddons) ? destinationTourData.pickupAddons : [];
                var dateId = 'hr-destination-tour-date-' + Math.floor(Math.random() * 1000000);
                var guestId = 'hr-destination-tour-guests-' + Math.floor(Math.random() * 1000000);
                var html = '';

                if (!$wrap.length) {
                        return;
                }

                if ($wrap.find('.hr-destination-tour-date-input, .hr-destination-tour-guest-count, .hr-destination-tour-destination-select').length) {
                        syncDestinationTourDisplayDateFromLegacy($form);
                        initDestinationTourDateInputs($form);
                        return;
                }

                html += '<div class="hr-destination-tour-field-group hr-destination-tour-date-field">';
                html += '<label for="' + dateId + '">' + escapeHtml(window.hrBooking ? window.hrBooking.destinationTourRentalDateLabel : 'Rental Date') + ' <span class="required">*</span></label>';
                html += '<input type="text" id="' + dateId + '" class="hr-destination-tour-date-input checkout-date ovabrw-datepicker ovabrw-input-required" name="hr_destination_tour_rental_date_display" placeholder="DD-MM-YYYY" required aria-required="true" autocomplete="off">';
                html += '</div>';

                if (destinationTourData && destinationTourData.tourName) {
                        html += '<div class="hr-destination-tour-service-name">' + escapeHtml(destinationTourData.tourName) + '</div>';
                }

                html += '<div class="hr-destination-tour-guest-row">';
                html += '<label class="hr-destination-tour-guest-label" for="' + guestId + '">' + escapeHtml(window.hrBooking ? window.hrBooking.destinationTourGuestsLabel : 'No. of Guest') + '</label>';
                html += '<button type="button" class="hr-destination-tour-qty-btn hr-destination-tour-guest-minus" aria-label="Decrease guests">-</button>';
                html += '<input type="number" id="' + guestId + '" class="hr-destination-tour-guest-count ovabrw-input-required" name="hr_destination_tour_guests_display" value="1" min="1" step="1" inputmode="numeric" required aria-required="true">';
                html += '<button type="button" class="hr-destination-tour-qty-btn hr-destination-tour-guest-plus" aria-label="Increase guests">+</button>';
                html += '</div>';

                html += '<div class="hr-destination-tour-pickup-title">' + escapeHtml(window.hrBooking ? window.hrBooking.destinationTourPickupInfoLabel : 'Pick-up Information') + '</div>';

                html += '<div class="hr-destination-tour-field-group">';
                html += '<input type="text" class="hr-destination-tour-hotel-input ovabrw-input-required" name="hr_destination_tour_hotel_name_display" placeholder="' + escapeHtml(window.hrBooking ? window.hrBooking.destinationTourHotelPlaceholder : 'Hotel/Resort Name') + '" required aria-required="true" autocomplete="off">';
                html += '</div>';

                html += '<div class="hr-destination-tour-field-group">';
                html += '<select class="hr-destination-tour-pickup-time-select ovabrw-input-required" name="hr_destination_tour_pickup_time_display" required aria-required="true">';
                html += '<option value="">' + escapeHtml(window.hrBooking ? window.hrBooking.destinationTourPickupTimePlaceholder : 'Pick-up Time') + '</option>';
                pickupTimes.forEach(function (time) {
                        html += '<option value="' + escapeHtml(time) + '">' + escapeHtml(time) + '</option>';
                });
                html += '</select>';
                html += '</div>';

                html += '<div class="hr-destination-tour-field-group">';
                html += '<select class="hr-destination-tour-destination-select ovabrw-input-required" name="hr_destination_tour_destination_display" required aria-required="true">';
                html += '<option value="">' + escapeHtml(window.hrBooking ? window.hrBooking.destinationTourDestinationPlaceholder : 'Tourist Destinations:') + '</option>';
                destinations.forEach(function (destination, index) {
                        var label = destination.label || '';
                        var duration = destination.duration || '';
                        var rate = Number(destination.rate || 0);
                        var optionText = label;

                        if (duration) {
                                optionText += ' - ' + duration;
                        }

                        if (Number.isFinite(rate) && rate > 0) {
                                optionText += ' = ' + formatPrice(rate);
                        }

                        html += '<option value="' + index + '" data-label="' + escapeHtml(label) + '" data-duration="' + escapeHtml(duration) + '" data-rate="' + escapeHtml(rate) + '">' + escapeHtml(optionText) + '</option>';
                });
                html += '</select>';
                html += '</div>';

                if (pickupAddons.length) {
                        html += '<div class="hr-destination-tour-addons">';
                        html += '<div class="hr-destination-tour-addons-title">' + escapeHtml(window.hrBooking ? window.hrBooking.destinationTourAddonsLabel : 'Add-ons:') + '</div>';
                        html += '<select class="hr-destination-tour-pickup-addon-select" name="hr_destination_tour_pickup_addon_display">';
                        html += '<option value="">' + escapeHtml(window.hrBooking ? window.hrBooking.destinationTourAddonPlaceholder : 'For Pick-up Outside Dumaguete City') + '</option>';
                        pickupAddons.forEach(function (addon, index) {
                                var label = addon.label || '';
                                var rate = Number(addon.rate || 0);
                                var optionLabel = label;

                                if (Number.isFinite(rate) && rate > 0) {
                                        optionLabel += ' - ' + formatPrice(rate);
                                }

                                html += '<option value="' + index + '" data-label="' + escapeHtml(label) + '" data-rate="' + escapeHtml(rate) + '">' + escapeHtml(optionLabel) + '</option>';
                        });
                        html += '</select>';
                        html += '</div>';
                }

                $wrap.html(html);
                syncDestinationTourDisplayDateFromLegacy($form);
                initDestinationTourDateInputs($form);
        }

        function initDestinationTourDateInputs($form) {
                var $sourceInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);
                var dateFormat = getDatepickerFormat($sourceInput) || 'dd-mm-yy';

                $form.find('.hr-destination-tour-date-input').each(function () {
                        var $input = $(this);

                        if ($input.data('hr-destination-tour-datepicker-bound')) {
                                return;
                        }

                        if (window.flatpickr && !this._flatpickr) {
                                try {
                                        window.flatpickr(this, {
                                                dateFormat: 'd-m-Y',
                                                minDate: 'today',
                                                allowInput: true,
                                                onChange: function () {
                                                        $input.trigger('input').trigger('change');
                                                }
                                        });
                                        $input.data('hr-destination-tour-datepicker-bound', 'flatpickr');
                                        return;
                                } catch (error) {
                                        // Fall through to jQuery UI/native text input.
                                }
                        }

                        if ($.fn.datepicker) {
                                try {
                                        $input.datepicker({
                                                dateFormat: dateFormat,
                                                minDate: 0,
                                                onSelect: function () {
                                                        $(this).trigger('input').trigger('change');
                                                }
                                        });
                                        $input.data('hr-destination-tour-datepicker-bound', 'jquery-ui');
                                } catch (error) {
                                        bindDestinationTourFallbackDateCalendar($form, $input);
                                }
                        } else {
                                bindDestinationTourFallbackDateCalendar($form, $input);
                        }
                });
        }

        function hideDestinationTourFallbackDateCalendar() {
                $('.hr-destination-tour-calendar').prop('hidden', true).removeClass('is-open');
                $(document).off('.hrDestinationTourCalendar');
                $(window).off('.hrDestinationTourCalendar');
        }

        function bindDestinationTourFallbackDateCalendar($form, $input) {
                $input
                        .attr('inputmode', 'numeric')
                        .data('hr-destination-tour-datepicker-bound', 'fallback-calendar')
                        .off('.hrDestinationTourCalendar')
                        .on('focus.hrDestinationTourCalendar click.hrDestinationTourCalendar', function () {
                                showDestinationTourFallbackDateCalendar($form, $input);
                        });
        }

        function showDestinationTourFallbackDateCalendar($form, $input) {
                var $calendar = $('.hr-destination-tour-calendar').first();
                var selectedDate = parseBookingDate($input);
                var today = normalizeDate(new Date());
                var viewDate;
                var selectedKey = selectedDate ? getSelfDriveFallbackCalendarDateKey(selectedDate) : '';
                var minKey = today ? getSelfDriveFallbackCalendarDateKey(today) : '';

                if (selectedDate && today && selectedDate.getTime() < today.getTime()) {
                        selectedDate = null;
                        selectedKey = '';
                }

                viewDate = selectedDate || today || normalizeDate(new Date());

                if (!$calendar.length) {
                        $calendar = $('<div>', {
                                class: 'hr-self-drive-return-calendar hr-destination-tour-calendar',
                                role: 'dialog',
                                'aria-label': window.hrBooking && window.hrBooking.destinationTourRentalDateLabel ? window.hrBooking.destinationTourRentalDateLabel : 'Rental Date',
                                hidden: true
                        }).appendTo(document.body);
                }

                $calendar
                        .data('form', $form)
                        .data('input', $input)
                        .data('view-year', viewDate.getFullYear())
                        .data('view-month', viewDate.getMonth())
                        .data('selected-key', selectedKey)
                        .data('min-key', minKey)
                        .prop('hidden', false)
                        .addClass('is-open');

                renderSelfDriveFallbackReturnDateCalendar($calendar);
                positionSelfDriveFallbackReturnDateCalendar($calendar, $input);

                $calendar.off('.hrDestinationTourCalendar')
                        .on('mousedown.hrDestinationTourCalendar touchstart.hrDestinationTourCalendar', function (event) {
                                event.stopPropagation();
                        })
                        .on('click.hrDestinationTourCalendar', '[data-hr-calendar-nav]', function () {
                                var offset = Number($(this).attr('data-hr-calendar-nav') || 0);
                                var nextDate = new Date(Number($calendar.data('view-year')), Number($calendar.data('view-month')) + offset, 1);

                                $calendar
                                        .data('view-year', nextDate.getFullYear())
                                        .data('view-month', nextDate.getMonth());
                                renderSelfDriveFallbackReturnDateCalendar($calendar);
                        })
                        .on('click.hrDestinationTourCalendar', '[data-hr-calendar-date]', function () {
                                var date = parseSelfDriveFallbackCalendarDateKey($(this).attr('data-hr-calendar-date'));
                                var value;

                                if (!date || $(this).prop('disabled')) {
                                        return;
                                }

                                value = formatDateForField(date, $input, $input);
                                $input.val(value).attr('value', value).trigger('input').trigger('change');
                                hideDestinationTourFallbackDateCalendar();
                        });

                $(document).off('.hrDestinationTourCalendar').on('mousedown.hrDestinationTourCalendar touchstart.hrDestinationTourCalendar', function (event) {
                        if ($(event.target).closest('.hr-destination-tour-calendar, .hr-destination-tour-date-input').length) {
                                return;
                        }

                        hideDestinationTourFallbackDateCalendar();
                });

                $(window).off('.hrDestinationTourCalendar').on('resize.hrDestinationTourCalendar scroll.hrDestinationTourCalendar', function () {
                        if (!$calendar.prop('hidden')) {
                                positionSelfDriveFallbackReturnDateCalendar($calendar, $input);
                        }
                });
        }

        function getDestinationTourRentalDateValue($form) {
                var $displayInput = $form.find('.hr-destination-tour-date-input').first();
                var $dateInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);

                if ($displayInput.length) {
                        return String($displayInput.val() || '');
                }

                return $dateInput.length ? String($dateInput.val() || '') : '';
        }

        function syncDestinationTourDisplayDateFromLegacy($form) {
                var $displayInput = $form.find('.hr-destination-tour-date-input').first();
                var $dateInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);

                if (!$displayInput.length || !$dateInput.length || $displayInput.val()) {
                        return;
                }

                $displayInput.val($dateInput.val() || '').attr('value', $dateInput.val() || '');
        }

        function syncDestinationTourLegacyDateField($form) {
                var $displayInput = $form.find('.hr-destination-tour-date-input').first();
                var $dateInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);
                var value = $displayInput.length ? String($displayInput.val() || '') : '';

                if ($dateInput.length && value && $dateInput.val() !== value) {
                        $dateInput.val(value).attr('value', value).trigger('change');
                }
        }

        function getDestinationTourGuestCount($form) {
                var $guestInput = $form.find('.hr-destination-tour-guest-count').first();
                var value = parseInt($guestInput.length ? $guestInput.val() : $form.find('input[name="hr_destination_tour_guests"]').val(), 10);

                if (!Number.isFinite(value) || value < 1) {
                        return 0;
                }

                return value;
        }

        function getSelectedDestinationTourDestination($field) {
                var $select = $field.find('.hr-destination-tour-destination-select').first();
                var value = $select.val();
                var $option;
                var rate;

                if (!$select.length || value === '') {
                        return null;
                }

                $option = $select.find('option:selected');
                rate = Number($option.data('rate'));

                return {
                        index: parseInt(value, 10),
                        label: String($option.data('label') || $option.text() || ''),
                        duration: String($option.data('duration') || ''),
                        rate: rate
                };
        }

        function getSelectedDestinationTourPickupAddon($field) {
                var $select = $field.find('.hr-destination-tour-pickup-addon-select').first();
                var value = $select.val();
                var $option;
                var rate;

                if (!$select.length || value === '') {
                        return {
                                index: '',
                                label: '',
                                rate: 0
                        };
                }

                $option = $select.find('option:selected');
                rate = Number($option.data('rate'));

                return {
                        index: parseInt(value, 10),
                        label: String($option.data('label') || $option.text() || ''),
                        rate: Number.isFinite(rate) ? rate : 0
                };
        }

        function setDestinationTourFormValue($form, name, value) {
                var $fields = $form.find('[name="' + name + '"]');

                if (!$fields.length) {
                        $fields = $('<input>', {
                                type: 'hidden',
                                name: name
                        }).appendTo($form);
                }

                $fields.val(value).attr('value', value);
        }

        function syncDestinationTourGuestFields($form) {
                var guestCount = getDestinationTourGuestCount($form);

                if (!guestCount) {
                        setDestinationTourFormValue($form, 'hr_destination_tour_guests', '');
                        return;
                }

                setDestinationTourFormValue($form, 'ovabrw_adults', String(guestCount));
                setDestinationTourFormValue($form, 'ovabrw_childrens', '0');
                setDestinationTourFormValue($form, 'ovabrw_babies', '0');
                setDestinationTourFormValue($form, 'hr_destination_tour_guests', String(guestCount));
        }

        function hideDestinationTourGuestFields($form) {
                var guestCount = Math.max(getDestinationTourGuestCount($form), 1);

                $form.find(GUEST_INPUT_SELECTOR).each(function () {
                        var $input = $(this);
                        var name = String($input.attr('name') || '');
                        var value = name === 'ovabrw_adults' ? String(guestCount) : '0';

                        $input
                                .val(value)
                                .attr('value', value)
                                .prop('required', false)
                                .removeAttr('required')
                                .removeAttr('aria-required')
                                .removeClass('ovabrw-input-required error');

                        hideLegacyAirportTransferField($input);
                });

                setDestinationTourFormValue($form, 'ovabrw_adults', String(guestCount));
                setDestinationTourFormValue($form, 'ovabrw_childrens', '0');
                setDestinationTourFormValue($form, 'ovabrw_babies', '0');
        }

        function hasDestinationTourValidationAttempted($form) {
                return $form.data('hr-destination-tour-validation-attempted') === true;
        }

        function markDestinationTourValidationAttempted($form) {
                $form.data('hr-destination-tour-validation-attempted', true);
                $form.addClass('hr-destination-tour-validation-attempted');
        }

        function setDestinationTourValidationMessage($form, $target, message) {
                $target.text(hasDestinationTourValidationAttempted($form) ? (message || '') : '');
        }

        function clearDestinationTourBaseValidation($form) {
                var baseErrorPattern = /(?:^|\b)field\s+is\s+required\.?$|number\s+of\s+guests|guests?\s+is\s+required|total\s+must\s+be\s+greater\s+than\s+0|total.*greater\s+than\s+0/i;
                var destinationTourTotal = Number($form.find('input[name="hr_destination_tour_total"]').val() || 0);
                var totalIsValid = Number.isFinite(destinationTourTotal) && destinationTourTotal > 0;

                $form.find('label, span, p, div, li').each(function () {
                        var $element = $(this);
                        var directText = getDirectText($element);
                        var fullText = $.trim($element.text()).replace(/\s+/g, ' ');
                        var isAjaxError = $element.hasClass('ajax-error');

                        if (!baseErrorPattern.test(directText) && !(isAjaxError && baseErrorPattern.test(fullText))) {
                                return;
                        }

                        if (isAjaxError) {
                                $element.empty().hide();
                                return;
                        }

                        $element.remove();
                });

                if (!hasDestinationTourValidationAttempted($form) || totalIsValid) {
                        $form.find('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit')
                                .prop('disabled', false)
                                .removeAttr('disabled')
                                .removeClass('disabled loading');
                }
        }

        function neutralizeDestinationTourHiddenRequiredFields($form) {
                var $destinationTourField = $form.find('.hr-destination-tour-booking-field');

                if (!$destinationTourField.length) {
                        return;
                }

                $form.find('.ovabrw-input-required, [required], [aria-required="true"]').not($destinationTourField.find('.ovabrw-input-required, [required], [aria-required="true"]')).each(function () {
                        var $control = $(this);
                        var type = String($control.attr('type') || '').toLowerCase();
                        var hidden = type === 'hidden' ||
                                !$control.is(':visible') ||
                                $control.closest('.hr-legacy-airport-transfer-field-hidden, [aria-hidden="true"], .ovabrw-wrapper-guestspicker').length > 0;

                        if (!hidden) {
                                return;
                        }

                        $control
                                .prop('required', false)
                                .removeAttr('required')
                                .removeAttr('aria-required')
                                .removeAttr('data-error')
                                .removeClass('ovabrw-input-required ovabrw-required error');

                        if ($control.is('input, textarea') && !$control.val()) {
                                $control.val(type === 'checkbox' || type === 'radio' ? 'yes' : '1').attr('value', type === 'checkbox' || type === 'radio' ? 'yes' : '1');
                        }
                });

                $form.find('.hr-legacy-airport-transfer-field-hidden .ovabrw-required, .ovabrw-wrapper-guestspicker .ovabrw-required')
                        .removeClass('ovabrw-required');
        }

        function scheduleDestinationTourBaseValidationClear($form) {
                [0, 50, 100, 250, 500, 800, 1200, 2000].forEach(function (delay) {
                        window.setTimeout(function () {
                                clearDestinationTourBaseValidation($form);
                        }, delay);
                });
        }

        function syncDestinationTourBaseBookingState($form, total, guestCount, valid) {
                var safeTotal = Number(total);
                var safeGuestCount = Math.max(parseInt(guestCount, 10) || 0, 0);
                var totalValue = valid && Number.isFinite(safeTotal) && safeTotal > 0 ? String(safeTotal) : '';
                var dataValue = valid && Number.isFinite(safeTotal) && safeTotal > 0 ? safeTotal : '';

                setDestinationTourFormValue($form, 'quantity', '1');
                setDestinationTourFormValue($form, 'ovabrw_quantity', '1');
                setDestinationTourFormValue($form, 'ovabrw_adults', String(Math.max(safeGuestCount, 1)));
                setDestinationTourFormValue($form, 'ovabrw_childrens', '0');
                setDestinationTourFormValue($form, 'ovabrw_babies', '0');
                setDestinationTourFormValue($form, 'hr_destination_tour_total', totalValue);
                setDestinationTourFormValue($form, 'ovabrw_total', totalValue);
                setDestinationTourFormValue($form, 'ovabrw_total_price', totalValue);
                setDestinationTourFormValue($form, 'ovabrw_amount', totalValue);
                setDestinationTourFormValue($form, 'total_price', totalValue);

                $form.find('.ajax-show-total, .ovabrw-ajax-total')
                        .attr('data-total', totalValue)
                        .attr('data-price', totalValue)
                        .attr('data-amount', totalValue)
                        .data('total', dataValue)
                        .data('price', dataValue)
                        .data('amount', dataValue);

                $form.find('.ajax-show-total .show-total-number, .ovabrw-ajax-total .show-total-number')
                        .text(totalValue)
                        .attr('data-total', totalValue)
                        .attr('data-price', totalValue)
                        .attr('data-amount', totalValue)
                        .data('total', dataValue)
                        .data('price', dataValue)
                        .data('amount', dataValue);

                if (valid && totalValue) {
                        clearDestinationTourBaseValidation($form);
                        $form.find('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit')
                                .prop('disabled', false)
                                .removeAttr('disabled')
                                .removeClass('disabled loading');
                }
        }

        function hideLegacyDestinationTourFields($form) {
                var $destinationTourField = $form.find('.hr-destination-tour-booking-field');
                var legacyDateInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR)[0];

                if (!$destinationTourField.length) {
                        return;
                }

                hideDestinationTourGuestFields($form);
                neutralizeDestinationTourHiddenRequiredFields($form);
                clearDestinationTourBaseValidation($form);

                $form.find('input, textarea, select').not($destinationTourField.find('input, textarea, select')).each(function () {
                        var $control = $(this);
                        var fieldText = [
                                $control.attr('name') || '',
                                $control.attr('id') || '',
                                $control.attr('placeholder') || '',
                                $control.attr('aria-label') || '',
                                getLegacyAirportTransferFieldContainer($control).text()
                        ].join(' ');

                        if (this === legacyDateInput || /rental\s+date|tour\s+date|booking\s+date|pick[\s-]*up\s+date|pickup\s+date|pick[\s-]*up\s+time|pickup\s+time|hotel\s+name|hotel\s*\/\s*resort|resort\s+name|guests?|tourist\s+destination|destination|add[\s-]*ons?|outside\s+dumaguete/i.test(fieldText)) {
                                hideLegacyAirportTransferField($control);
                        }
                });
        }

        function scheduleDestinationTourFormSync($form) {
                [0, 100, 350, 800, 1500].forEach(function (delay) {
                        window.setTimeout(function () {
                                hideLegacyDestinationTourFields($form);
                        }, delay);
                });
        }

        function updateDestinationTourSelection($form, destinationTourData) {
                var $field = $form.find('.hr-destination-tour-booking-field');
                var $total = $field.find('.hr-destination-tour-booking-total');
                var $rentalDateInput = $field.find('.hr-destination-tour-date-input').first();
                var rentalDate = getDestinationTourRentalDateValue($form);
                var rentalDateObject = parseBookingDate($rentalDateInput);
                var today = normalizeDate(new Date());
                var guests = getDestinationTourGuestCount($form);
                var hotelName = $.trim($field.find('.hr-destination-tour-hotel-input').val() || '');
                var pickupTime = $field.find('.hr-destination-tour-pickup-time-select').val();
                var selectedDestination = getSelectedDestinationTourDestination($field);
                var selectedPickupAddon = getSelectedDestinationTourPickupAddon($field);
                var total = selectedDestination ? Number(selectedDestination.rate) + Number(selectedPickupAddon.rate || 0) : 0;

                function fail(message) {
                        setDestinationTourValidationMessage($form, $total, message);
                        $form.data('hr-destination-tour-total', 0).data('hr-destination-tour-total-valid', false);
                        syncDestinationTourBaseBookingState($form, 0, guests, false);
                        syncDestinationTourAjaxTotal($form);
                        return false;
                }

                if (!destinationTourData || !$field.length) {
                        return false;
                }

                syncDestinationTourLegacyDateField($form);
                syncDestinationTourGuestFields($form);

                $field.find('input[name="hr_destination_tour_rental_date"]').val(rentalDate);
                $field.find('input[name="hr_destination_tour_guests"]').val(guests ? String(guests) : '');
                $field.find('input[name="hr_destination_tour_hotel_name"]').val(hotelName);
                $field.find('input[name="hr_destination_tour_pickup_time"]').val(pickupTime || '');
                $field.find('input[name="hr_destination_tour_destination_index"]').val(selectedDestination ? String(selectedDestination.index) : '');
                $field.find('input[name="hr_destination_tour_destination_label"]').val(selectedDestination ? selectedDestination.label : '');
                $field.find('input[name="hr_destination_tour_destination_duration"]').val(selectedDestination ? selectedDestination.duration : '');
                $field.find('input[name="hr_destination_tour_rental_rate"]').val(selectedDestination && Number.isFinite(Number(selectedDestination.rate)) ? String(selectedDestination.rate) : '');
                $field.find('input[name="hr_destination_tour_pickup_addon_index"]').val(selectedPickupAddon && selectedPickupAddon.index !== '' ? String(selectedPickupAddon.index) : '');
                $field.find('input[name="hr_destination_tour_pickup_addon_label"]').val(selectedPickupAddon ? selectedPickupAddon.label : '');
                $field.find('input[name="hr_destination_tour_pickup_addon_rate"]').val(selectedPickupAddon && Number.isFinite(Number(selectedPickupAddon.rate)) && selectedPickupAddon.rate > 0 ? String(selectedPickupAddon.rate) : '');
                $field.find('input[name="hr_destination_tour_selection_payload"]').val('');
                $field.find('input[name="hr_destination_tour_total"]').val('');

                if (!rentalDate) {
                        return fail(window.hrBooking ? window.hrBooking.destinationTourChooseRentalDate : '');
                }

                if (!rentalDateObject || (today && rentalDateObject.getTime() < today.getTime())) {
                        return fail(window.hrBooking ? window.hrBooking.destinationTourChooseRentalDate : '');
                }

                if (!guests) {
                        return fail(window.hrBooking ? window.hrBooking.destinationTourChooseGuests : '');
                }

                if (!hotelName) {
                        return fail(window.hrBooking ? window.hrBooking.destinationTourChooseHotelName : '');
                }

                if (!pickupTime) {
                        return fail(window.hrBooking ? window.hrBooking.destinationTourChoosePickupTime : '');
                }

                if (!selectedDestination || !Number.isFinite(total) || total <= 0) {
                        return fail(window.hrBooking ? window.hrBooking.destinationTourChooseDestination : '');
                }

                $field.find('input[name="hr_destination_tour_selection_payload"]').val(JSON.stringify({
                        destinationIndex: selectedDestination.index,
                        pickupAddonIndex: selectedPickupAddon && selectedPickupAddon.index !== '' ? selectedPickupAddon.index : ''
                }));
                $field.find('input[name="hr_destination_tour_total"]').val(String(total));
                $total.text((window.hrBooking ? window.hrBooking.destinationTourTotalLabel : 'Destination tour total: %s').replace('%s', formatPrice(total)));
                $form.data('hr-destination-tour-total', total).data('hr-destination-tour-total-valid', true);
                syncDestinationTourBaseBookingState($form, total, guests, true);
                syncDestinationTourAjaxTotal($form);
                return true;
        }

        function prepareDestinationTourValidationState($form, destinationTourData) {
                var valid;

                markDestinationTourValidationAttempted($form);
                hideLegacyDestinationTourFields($form);
                valid = updateDestinationTourSelection($form, destinationTourData);
                syncDestinationTourAjaxTotal($form);
                scheduleDestinationTourBaseValidationClear($form);

                return valid;
        }

        function stopDestinationTourInvalidSubmit(event) {
                if (!event) {
                        return;
                }

                if (event.preventDefault) {
                        event.preventDefault();
                }

                if (event.stopImmediatePropagation) {
                        event.stopImmediatePropagation();
                } else if (event.stopPropagation) {
                        event.stopPropagation();
                }
        }

        function handleDestinationTourValidationAttempt($form, destinationTourData, event) {
                var valid = prepareDestinationTourValidationState($form, destinationTourData);

                if (!valid) {
                        stopDestinationTourInvalidSubmit(event);
                }

                return valid;
        }

        function handleDestinationTourGuestButton($button, destinationTourData) {
                var $form = $button.closest('form');
                var $qty = $form.find('.hr-destination-tour-guest-count').first();
                var current = parseInt($qty.val(), 10) || 0;
                var min = parseInt($qty.attr('min'), 10) || 0;

                if (!$form.length || !$qty.length) {
                        return;
                }

                if ($button.hasClass('hr-destination-tour-guest-plus')) {
                        current += 1;
                } else {
                        current -= 1;
                }

                current = Math.max(min, current);
                $qty.val(String(current)).trigger('change');

                if (destinationTourData) {
                        updateDestinationTourSelection($form, destinationTourData);
                        scheduleDestinationTourAjaxTotalSync($form);
                }
        }

        function getPortTransferData($form) {
                var $field = $form.find('.hr-port-transfer-booking-field');
                var raw = $field.attr('data-port-transfer');

                if (!$field.length || !raw) {
                        return null;
                }

                try {
                        return JSON.parse(raw);
                } catch (error) {
                        return null;
                }
        }

        function hidePortTransferAjaxTotal($form) {
                var $ajaxTotal = $form.find('.ajax-show-total');

                $form.addClass('hr-port-transfer-booking-form');

                $ajaxTotal.each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });

                $ajaxTotal.find('.ovabrw-ajax-total').each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });
        }

        function syncPortTransferAjaxTotal($form) {
                var total = Number($form.data('hr-port-transfer-total') || 0);
                var valid = $form.data('hr-port-transfer-total-valid') === true;
                var $ajaxTotal = getPackageAjaxTotal($form);

                if (!$ajaxTotal.length) {
                        return;
                }

                $ajaxTotal
                        .text(valid && Number.isFinite(total) ? String(total) : '')
                        .attr('data-total', valid && Number.isFinite(total) ? String(total) : '');
        }

        function schedulePortTransferAjaxTotalSync($form) {
                [0, 100, 350, 800].forEach(function (delay) {
                        window.setTimeout(function () {
                                syncPortTransferAjaxTotal($form);
                        }, delay);
                });
        }

        function renderPortTransferSelectors($form, portTransferData) {
                var $field = $form.find('.hr-port-transfer-booking-field');
                var $wrap = $field.find('.hr-port-transfer-selectors');
                var fromOptions = portTransferData && $.isArray(portTransferData.fromOptions) ? portTransferData.fromOptions : [];
                var toOptions = portTransferData && $.isArray(portTransferData.toOptions) ? portTransferData.toOptions : [];
                var transferTimes = portTransferData && $.isArray(portTransferData.transferTimes) ? portTransferData.transferTimes : [];
                var addons = portTransferData && $.isArray(portTransferData.addons) ? portTransferData.addons : [];
                var passengerId = 'hr-port-transfer-passengers-' + Math.floor(Math.random() * 1000000);
                var dateId = 'hr-port-transfer-date-' + Math.floor(Math.random() * 1000000);
                var returnDateId = 'hr-port-transfer-return-date-' + Math.floor(Math.random() * 1000000);
                var html = '';

                if (!$wrap.length) {
                        return;
                }

                html += '<div class="hr-port-transfer-field-group">';
                html += '<select class="hr-port-transfer-type-select ovabrw-input-required" required aria-required="true">';
                html += '<option value="one_way" selected>' + escapeHtml(window.hrBooking ? window.hrBooking.portTransferOneWayLabel : 'One-way Transfer') + '</option>';
                html += '<option value="roundtrip">' + escapeHtml(window.hrBooking ? window.hrBooking.portTransferRoundtripLabel : 'Round-trip Transfer') + '</option>';
                html += '</select>';
                html += '</div>';

                html += '<div class="hr-port-transfer-location-grid">';
                html += '<div class="hr-port-transfer-field-group hr-port-transfer-location-field">';
                html += '<label>' + escapeHtml(window.hrBooking ? window.hrBooking.portTransferFromLabel : 'From:') + '</label>';
                html += '<select class="hr-port-transfer-from-select ovabrw-input-required" required aria-required="true"></select>';
                html += '</div>';
                html += '<button type="button" class="hr-port-transfer-swap-button" aria-label="Switch From and To">&#8596;</button>';
                html += '<div class="hr-port-transfer-field-group hr-port-transfer-location-field">';
                html += '<label>' + escapeHtml(window.hrBooking ? window.hrBooking.portTransferToLabel : 'To:') + '</label>';
                html += '<select class="hr-port-transfer-to-select ovabrw-input-required" required aria-required="true"></select>';
                html += '</div>';
                html += '</div>';

                html += '<div class="hr-port-transfer-date-time-row">';
                html += '<div class="hr-port-transfer-field-group">';
                html += '<label class="screen-reader-text" for="' + dateId + '">' + escapeHtml(window.hrBooking ? window.hrBooking.portTransferDateLabel : 'Arrival Date') + '</label>';
                html += '<input type="date" id="' + dateId + '" class="hr-port-transfer-date-input ovabrw-input-required" name="hr_port_transfer_date_display" required aria-required="true" autocomplete="off">';
                html += '</div>';
                html += '<div class="hr-port-transfer-field-group">';
                html += '<input type="text" class="hr-port-transfer-time-input ovabrw-input-required" name="hr_port_transfer_time_display" placeholder="' + escapeHtml(window.hrBooking ? window.hrBooking.portTransferTimePlaceholder : 'Time') + '" required aria-required="true" autocomplete="off">';
                html += '</div>';
                html += '</div>';

                html += '<div class="hr-port-transfer-field-group">';
                html += '<input type="text" class="hr-port-transfer-flight-input" name="hr_port_transfer_flight_number_display" placeholder="' + escapeHtml(window.hrBooking ? window.hrBooking.portTransferFlightPlaceholder : 'Flight #') + '" autocomplete="off">';
                html += '</div>';

                html += '<div class="hr-port-transfer-field-group">';
                html += '<input type="text" class="hr-port-transfer-hotel-input ovabrw-input-required" name="hr_port_transfer_hotel_name_display" placeholder="' + escapeHtml(window.hrBooking ? window.hrBooking.portTransferHotelPlaceholder : 'Hotel Name') + '" required aria-required="true" autocomplete="off">';
                html += '</div>';

                html += '<div class="hr-port-transfer-roundtrip-fields" hidden>';
                html += '<div class="hr-port-transfer-date-time-row">';
                html += '<div class="hr-port-transfer-field-group hr-port-transfer-return-date-field">';
                html += '<label for="' + returnDateId + '">' + escapeHtml(window.hrBooking ? window.hrBooking.portTransferReturnDateLabel : 'Return Date') + '</label>';
                html += '<input type="date" id="' + returnDateId + '" class="hr-port-transfer-return-date-input ovabrw-input-required" name="hr_port_transfer_return_date_display" required aria-required="true" autocomplete="off">';
                html += '</div>';
                html += '<div class="hr-port-transfer-field-group">';
                html += '<select class="hr-port-transfer-return-time-select ovabrw-input-required" required aria-required="true">';
                html += '<option value="">' + escapeHtml(window.hrBooking ? window.hrBooking.portTransferReturnTimePlaceholder : 'Pick-up Time') + '</option>';
                transferTimes.forEach(function (time) {
                        html += '<option value="' + escapeHtml(time) + '">' + escapeHtml(time) + '</option>';
                });
                html += '</select>';
                html += '</div>';
                html += '</div>';
                html += '<div class="hr-port-transfer-field-group hr-port-transfer-return-dropoff-field">';
                html += '<label>' + escapeHtml(window.hrBooking ? window.hrBooking.portTransferReturnDropoffLabel : 'Drop-off To:') + '</label>';
                html += '<select class="hr-port-transfer-return-dropoff-select ovabrw-input-required" name="hr_port_transfer_return_dropoff_display" required aria-required="true"></select>';
                html += '</div>';
                html += '</div>';

                html += '<div class="hr-port-transfer-passenger-row">';
                html += '<label class="hr-port-transfer-passenger-label" for="' + passengerId + '">' + escapeHtml(window.hrBooking ? window.hrBooking.portTransferPassengersLabel : 'No. of Passengers') + '</label>';
                html += '<button type="button" class="hr-port-transfer-qty-btn hr-port-transfer-passenger-minus" aria-label="Decrease passengers">-</button>';
                html += '<input type="number" id="' + passengerId + '" class="hr-port-transfer-passenger-count ovabrw-input-required" name="hr_port_transfer_passengers_display" value="1" min="1" step="1" inputmode="numeric" required aria-required="true">';
                html += '<button type="button" class="hr-port-transfer-qty-btn hr-port-transfer-passenger-plus" aria-label="Increase passengers">+</button>';
                html += '</div>';

                if (addons.length) {
                        html += '<div class="hr-port-transfer-addons">';
                        html += '<div class="hr-port-transfer-addons-title">' + escapeHtml(window.hrBooking ? window.hrBooking.portTransferAddonsLabel : 'ADD-ONS') + '</div>';
                        addons.forEach(function (addon, index) {
                                var label = addon.label || '';
                                var rate = Number(addon.rate || 0);
                                var optionLabel = label;

                                if (Number.isFinite(rate) && rate > 0) {
                                        optionLabel += ' (+' + formatPrice(rate) + ')';
                                }

                                html += '<label class="hr-port-transfer-addon-label"><input type="checkbox" class="hr-port-transfer-addon-option" value="' + index + '" data-label="' + escapeHtml(label) + '" data-rate="' + escapeHtml(rate) + '"> ' + escapeHtml(optionLabel) + '</label>';
                        });
                        html += '</div>';
                }

                $wrap.html(html);
                $field.data('hr-port-transfer-direction', 'from_to');
                refreshPortTransferLocationSelects($field, portTransferData);
                syncPortTransferDisplayDateFromLegacy($form);
                initPortTransferDateInputs($form);

        }

        function getPortTransferOptionHtml(options, placeholder, selectedValue) {
                var html = '<option value="">' + escapeHtml(placeholder || '') + '</option>';

                options.forEach(function (option) {
                        html += '<option value="' + escapeHtml(option) + '"' + (String(option) === String(selectedValue || '') ? ' selected' : '') + '>' + escapeHtml(option) + '</option>';
                });

                return html;
        }

        function refreshPortTransferLocationSelects($field, portTransferData, selectedFrom, selectedTo) {
                var direction = $field.data('hr-port-transfer-direction') || 'from_to';
                var fromOptions = portTransferData && $.isArray(portTransferData.fromOptions) ? portTransferData.fromOptions : [];
                var toOptions = portTransferData && $.isArray(portTransferData.toOptions) ? portTransferData.toOptions : [];
                var visibleFromOptions = direction === 'to_from' ? toOptions : fromOptions;
                var visibleToOptions = direction === 'to_from' ? fromOptions : toOptions;
                var fromPlaceholder = window.hrBooking ? window.hrBooking.portTransferFromPlaceholder : 'From';
                var toPlaceholder = window.hrBooking ? window.hrBooking.portTransferToPlaceholder : 'To';
                var $fromSelect = $field.find('.hr-port-transfer-from-select');
                var $toSelect = $field.find('.hr-port-transfer-to-select');

                selectedFrom = typeof selectedFrom === 'undefined' ? $fromSelect.val() : selectedFrom;
                selectedTo = typeof selectedTo === 'undefined' ? $toSelect.val() : selectedTo;

                $fromSelect.html(getPortTransferOptionHtml(visibleFromOptions, fromPlaceholder, selectedFrom));
                $toSelect.html(getPortTransferOptionHtml(visibleToOptions, toPlaceholder, selectedTo));
                refreshPortTransferReturnDropoffOptions($field, visibleFromOptions);
                syncPortTransferReturnDropoffFromFrom($field, false);
        }

        function refreshPortTransferReturnDropoffOptions($field, options, selectedValue) {
                var $select = $field.find('.hr-port-transfer-return-dropoff-select');
                var placeholder = window.hrBooking ? window.hrBooking.portTransferReturnDropoffPlaceholder : 'Drop-off To';

                if (!$select.length) {
                        return;
                }

                selectedValue = typeof selectedValue === 'undefined' ? $select.val() : selectedValue;
                $select.html(getPortTransferOptionHtml(options || [], placeholder, selectedValue));
        }

        function syncPortTransferReturnDropoffFromFrom($field, force) {
                var from = $field.find('.hr-port-transfer-from-select').val() || '';
                var $select = $field.find('.hr-port-transfer-return-dropoff-select');

                if (!$select.length || !from) {
                        return;
                }

                if (force || !$select.val()) {
                        $select.val(from).attr('value', from);
                }
        }

        function labelPortTransferDateField($form) {
                var $dateInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);
                var labelText = window.hrBooking && window.hrBooking.portTransferDateLabel ? window.hrBooking.portTransferDateLabel : 'Arrival Date';
                var $container;
                var $label;

                if (!$dateInput.length) {
                        return;
                }

                $container = getLegacyAirportTransferFieldContainer($dateInput);
                if ($container.is('input, textarea, select')) {
                        $container = $dateInput.parent();
                }

                $label = $container.find('label').first();
                if ($label.length) {
                        $label.contents().filter(function () {
                                return this.nodeType === 3;
                        }).first().replaceWith(labelText + ' ');
                }
        }

        function getPortTransferDateValue($form) {
                var $displayInput = $form.find('.hr-port-transfer-date-input').first();
                var $dateInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);

                if ($displayInput.length) {
                        return String($displayInput.val() || '');
                }

                return $dateInput.length ? String($dateInput.val() || '') : '';
        }

        function syncPortTransferDisplayDateFromLegacy($form) {
                var $displayInput = $form.find('.hr-port-transfer-date-input').first();
                var $dateInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);

                if (!$displayInput.length || !$dateInput.length || $displayInput.val()) {
                        return;
                }

                if ($dateInput.length && parseBookingDate($dateInput)) {
                        $displayInput.val(formatDateForField(parseBookingDate($dateInput), $dateInput, $displayInput)).attr('value', $displayInput.val());
                        return;
                }

                $displayInput.val($dateInput.val() || '').attr('value', $dateInput.val() || '');
        }

        function syncPortTransferLegacyDateField($form) {
                var $displayInput = $form.find('.hr-port-transfer-date-input').first();
                var $dateInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);
                var value = $displayInput.length ? String($displayInput.val() || '') : '';

                if ($dateInput.length && value && $dateInput.val() !== value) {
                        $dateInput.val(value).attr('value', value).trigger('change');
                }
        }

        function initPortTransferDateInputs($form) {
                var $sourceInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);
                var dateFormat = getDatepickerFormat($sourceInput) || 'dd-mm-yy';

                $form.find('.hr-port-transfer-date-input, .hr-port-transfer-return-date-input').each(function () {
                        var $input = $(this);

                        if ($input.data('hr-port-transfer-datepicker-bound')) {
                                return;
                        }

                        if (String($input.attr('type') || '').toLowerCase() === 'date') {
                                $input.data('hr-port-transfer-datepicker-bound', 'native-date');
                                return;
                        }

                        if (window.flatpickr && !this._flatpickr) {
                                try {
                                        window.flatpickr(this, {
                                                dateFormat: 'd-m-Y',
                                                allowInput: true,
                                                onChange: function () {
                                                        $input.trigger('change');
                                                }
                                        });
                                        $input.data('hr-port-transfer-datepicker-bound', 'flatpickr');
                                        return;
                                } catch (error) {
                                        // Fall through to jQuery UI/native text input.
                                }
                        }

                        if ($.fn.datepicker) {
                                try {
                                        $input.datepicker({
                                                dateFormat: dateFormat,
                                                onSelect: function () {
                                                        $(this).trigger('change');
                                                }
                                        });
                                        $input.data('hr-port-transfer-datepicker-bound', 'jquery-ui');
                                } catch (error) {
                                        $input.data('hr-port-transfer-datepicker-bound', 'none');
                                }
                        }
                });
        }

        function getPortTransferPassengerCount($form) {
                var $passengerInput = $form.find('.hr-port-transfer-passenger-count').first();
                var value = parseInt($passengerInput.length ? $passengerInput.val() : $form.find('input[name="hr_port_transfer_passengers"]').val(), 10);

                if (!Number.isFinite(value) || value < 1) {
                        return 0;
                }

                return value;
        }

        function getSelectedPortTransferRoute($field) {
                var portTransferData = getPortTransferData($field.closest('form'));
                var routes = portTransferData && $.isArray(portTransferData.routes) ? portTransferData.routes : [];
                var direction = $field.data('hr-port-transfer-direction') || 'from_to';
                var from = $field.find('.hr-port-transfer-from-select').val();
                var to = $field.find('.hr-port-transfer-to-select').val();
                var matrixFrom = direction === 'to_from' ? to : from;
                var matrixTo = direction === 'to_from' ? from : to;
                var selected = null;

                if (!from || !to) {
                        return null;
                }

                routes.forEach(function (route, index) {
                        if (selected) {
                                return;
                        }

                        if (String(route.from || '') === String(matrixFrom) && String(route.to || '') === String(matrixTo)) {
                                selected = {
                                        index: index,
                                        from: from,
                                        to: to,
                                        matrixFrom: matrixFrom,
                                        matrixTo: matrixTo,
                                        direction: direction,
                                        label: from + ' to ' + to,
                                        rate: Number(route.rate)
                                };
                        }
                });

                return selected;
        }

        function getSelectedPortTransferAddons($field) {
                var addons = [];
                var total = 0;

                $field.find('.hr-port-transfer-addon-option:checked').each(function () {
                        var $option = $(this);
                        var index = parseInt($option.val(), 10);
                        var rate = Number($option.data('rate') || 0);
                        var label = String($option.data('label') || '');

                        if (!Number.isFinite(rate)) {
                                rate = 0;
                        }

                        addons.push({
                                index: index,
                                label: label,
                                rate: rate
                        });
                        total += rate;
                });

                return {
                        items: addons,
                        total: total
                };
        }

        function setPortTransferFormValue($form, name, value) {
                var $fields = $form.find('[name="' + name + '"]');

                if (!$fields.length) {
                        $fields = $('<input>', {
                                type: 'hidden',
                                name: name
                        }).appendTo($form);
                }

                $fields.val(value).attr('value', value);
        }

        function syncPortTransferGuestFields($form) {
                var passengerCount = getPortTransferPassengerCount($form);

                if (!passengerCount) {
                        setPortTransferFormValue($form, 'hr_port_transfer_passengers', '');
                        return;
                }

                setPortTransferFormValue($form, 'ovabrw_adults', String(passengerCount));
                setPortTransferFormValue($form, 'ovabrw_childrens', '0');
                setPortTransferFormValue($form, 'ovabrw_babies', '0');
                setPortTransferFormValue($form, 'hr_port_transfer_passengers', String(passengerCount));
        }

        function hidePortTransferGuestFields($form) {
                var passengerCount = Math.max(getPortTransferPassengerCount($form), 1);

                $form.find(GUEST_INPUT_SELECTOR).each(function () {
                        var $input = $(this);
                        var name = String($input.attr('name') || '');
                        var value = name === 'ovabrw_adults' ? String(passengerCount) : '0';

                        $input
                                .val(value)
                                .attr('value', value)
                                .prop('required', false)
                                .removeAttr('required')
                                .removeAttr('aria-required')
                                .removeClass('ovabrw-input-required error');

                        hideLegacyAirportTransferField($input);
                });

                setPortTransferFormValue($form, 'ovabrw_adults', String(passengerCount));
                setPortTransferFormValue($form, 'ovabrw_childrens', '0');
                setPortTransferFormValue($form, 'ovabrw_babies', '0');
        }

        function hasPortTransferValidationAttempted($form) {
                return $form.data('hr-port-transfer-validation-attempted') === true;
        }

        function markPortTransferValidationAttempted($form) {
                $form.data('hr-port-transfer-validation-attempted', true);
                $form.addClass('hr-port-transfer-validation-attempted');
        }

        function setPortTransferValidationMessage($form, $target, message) {
                $target.text(hasPortTransferValidationAttempted($form) ? (message || '') : '');
        }

        function clearPortTransferBaseValidation($form) {
                var baseErrorPattern = /(?:^|\b)field\s+is\s+required\.?$|number\s+of\s+guests|guests?\s+is\s+required|passengers?\s+is\s+required|passenger.*required|total\s+must\s+be\s+greater\s+than\s+0|total.*greater\s+than\s+0/i;
                var portTransferTotal = Number($form.find('input[name="hr_port_transfer_total"]').val() || 0);
                var totalIsValid = Number.isFinite(portTransferTotal) && portTransferTotal > 0;

                $form.find('label, span, p, div, li').each(function () {
                        var $element = $(this);
                        var directText = getDirectText($element);
                        var fullText = $.trim($element.text()).replace(/\s+/g, ' ');
                        var isAjaxError = $element.hasClass('ajax-error');

                        if (!baseErrorPattern.test(directText) && !(isAjaxError && baseErrorPattern.test(fullText))) {
                                return;
                        }

                        if (isAjaxError) {
                                $element.empty().hide();
                                return;
                        }

                        $element.remove();
                });

                if (!hasPortTransferValidationAttempted($form) || totalIsValid) {
                        $form.find('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit')
                                .prop('disabled', false)
                                .removeAttr('disabled')
                                .removeClass('disabled loading');
                }
        }

        function neutralizePortTransferHiddenRequiredFields($form) {
                var $portTransferField = $form.find('.hr-port-transfer-booking-field');

                if (!$portTransferField.length) {
                        return;
                }

                $form.find('.ovabrw-input-required, [required], [aria-required="true"]').not($portTransferField.find('.ovabrw-input-required, [required], [aria-required="true"]')).each(function () {
                        var $control = $(this);
                        var type = String($control.attr('type') || '').toLowerCase();
                        var hidden = type === 'hidden' ||
                                !$control.is(':visible') ||
                                $control.closest('.hr-legacy-airport-transfer-field-hidden, [aria-hidden="true"], .ovabrw-wrapper-guestspicker').length > 0;

                        if (!hidden) {
                                return;
                        }

                        $control
                                .prop('required', false)
                                .removeAttr('required')
                                .removeAttr('aria-required')
                                .removeAttr('data-error')
                                .removeClass('ovabrw-input-required ovabrw-required error');

                        if ($control.is('input, textarea') && !$control.val()) {
                                $control.val(type === 'checkbox' || type === 'radio' ? 'yes' : '1').attr('value', type === 'checkbox' || type === 'radio' ? 'yes' : '1');
                        }
                });

                $form.find('.hr-legacy-airport-transfer-field-hidden .ovabrw-required, .ovabrw-wrapper-guestspicker .ovabrw-required')
                        .removeClass('ovabrw-required');
        }

        function schedulePortTransferBaseValidationClear($form) {
                [0, 50, 100, 250, 500, 800, 1200, 2000].forEach(function (delay) {
                        window.setTimeout(function () {
                                clearPortTransferBaseValidation($form);
                        }, delay);
                });
        }

        function syncPortTransferBaseBookingState($form, total, passengerCount, valid) {
                var safeTotal = Number(total);
                var safePassengerCount = Math.max(parseInt(passengerCount, 10) || 0, 0);
                var totalValue = valid && Number.isFinite(safeTotal) && safeTotal > 0 ? String(safeTotal) : '';
                var dataValue = valid && Number.isFinite(safeTotal) && safeTotal > 0 ? safeTotal : '';

                setPortTransferFormValue($form, 'quantity', '1');
                setPortTransferFormValue($form, 'ovabrw_quantity', '1');
                setPortTransferFormValue($form, 'ovabrw_adults', String(Math.max(safePassengerCount, 1)));
                setPortTransferFormValue($form, 'ovabrw_childrens', '0');
                setPortTransferFormValue($form, 'ovabrw_babies', '0');
                setPortTransferFormValue($form, 'hr_port_transfer_total', totalValue);
                setPortTransferFormValue($form, 'ovabrw_total', totalValue);
                setPortTransferFormValue($form, 'ovabrw_total_price', totalValue);
                setPortTransferFormValue($form, 'ovabrw_amount', totalValue);
                setPortTransferFormValue($form, 'total_price', totalValue);

                $form.find('.ajax-show-total, .ovabrw-ajax-total')
                        .attr('data-total', totalValue)
                        .attr('data-price', totalValue)
                        .attr('data-amount', totalValue)
                        .data('total', dataValue)
                        .data('price', dataValue)
                        .data('amount', dataValue);

                $form.find('.ajax-show-total .show-total-number, .ovabrw-ajax-total .show-total-number')
                        .text(totalValue)
                        .attr('data-total', totalValue)
                        .attr('data-price', totalValue)
                        .attr('data-amount', totalValue)
                        .data('total', dataValue)
                        .data('price', dataValue)
                        .data('amount', dataValue);

                if (valid && totalValue) {
                        clearPortTransferBaseValidation($form);
                        $form.find('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit')
                                .prop('disabled', false)
                                .removeAttr('disabled')
                                .removeClass('disabled loading');
                }
        }

        function hideLegacyPortTransferFields($form) {
                var $portTransferField = $form.find('.hr-port-transfer-booking-field');
                var legacyDateInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR)[0];

                if (!$portTransferField.length) {
                        return;
                }

                labelPortTransferDateField($form);
                hidePortTransferGuestFields($form);
                neutralizePortTransferHiddenRequiredFields($form);
                clearPortTransferBaseValidation($form);

                $form.find('input, textarea, select').not($portTransferField.find('input, textarea, select')).each(function () {
                        var $control = $(this);
                        var fieldText = [
                                $control.attr('name') || '',
                                $control.attr('id') || '',
                                $control.attr('placeholder') || '',
                                $control.attr('aria-label') || '',
                                getLegacyAirportTransferFieldContainer($control).text()
                        ].join(' ');

                        if (this === legacyDateInput || /arrival\s+date|transfer\s+date|pick[\s-]*up\s+date|pickup\s+date|booking\s+date|drop[\s-]*off|dropoff|flight\s*#|flight|hotel\s+name|name\s+of\s+hotel\s*\/\s*resort|passengers?|route\s+and\s+capacity|transfer\s+type|return\s+time/i.test(fieldText)) {
                                hideLegacyAirportTransferField($control);
                        }
                });
        }

        function schedulePortTransferFormSync($form) {
                [0, 100, 350, 800, 1500].forEach(function (delay) {
                        window.setTimeout(function () {
                                hideLegacyPortTransferFields($form);
                        }, delay);
                });
        }

        function updatePortTransferRoundtripState($field) {
                var transferType = $field.find('.hr-port-transfer-type-select').val();
                var roundtrip = transferType === 'roundtrip';
                var $roundtripFields = $field.find('.hr-port-transfer-roundtrip-fields');

                $roundtripFields.prop('hidden', !roundtrip);
                $roundtripFields.find('input, select, textarea')
                        .toggleClass('ovabrw-input-required', roundtrip)
                        .prop('required', roundtrip)
                        .attr('aria-required', roundtrip ? 'true' : null);

                if (!roundtrip) {
                        $roundtripFields.find('input, select, textarea').val('');
                }
        }

        function updatePortTransferSelection($form, portTransferData) {
                var $field = $form.find('.hr-port-transfer-booking-field');
                var $total = $field.find('.hr-port-transfer-booking-total');
                var transferDate = getPortTransferDateValue($form);
                var passengers = getPortTransferPassengerCount($form);
                var selectedRoute = getSelectedPortTransferRoute($field);
                var transferType = $field.find('.hr-port-transfer-type-select').val();
                var from = $field.find('.hr-port-transfer-from-select').val();
                var to = $field.find('.hr-port-transfer-to-select').val();
                var direction = $field.data('hr-port-transfer-direction') || 'from_to';
                var transferTime = $.trim($field.find('.hr-port-transfer-time-input').val() || '');
                var flightNumber = $.trim($field.find('.hr-port-transfer-flight-input').val() || '');
                var hotelName = $.trim($field.find('.hr-port-transfer-hotel-input').val() || '');
                var dropoff = to || '';
                var returnDate = $.trim($field.find('.hr-port-transfer-return-date-input').val() || '');
                var startDateObject = parseBookingDate($field.find('.hr-port-transfer-date-input').first());
                var returnDateObject = parseBookingDate($field.find('.hr-port-transfer-return-date-input').first());
                var returnTime = $field.find('.hr-port-transfer-return-time-select').val();
                var returnDropoff = $.trim($field.find('.hr-port-transfer-return-dropoff-select').val() || '');
                var selectedAddons = getSelectedPortTransferAddons($field);
                var multiplier = transferType === 'roundtrip' ? 2 : 1;
                var total = selectedRoute ? (Number(selectedRoute.rate) * multiplier) + selectedAddons.total : 0;
                var valid = false;

                if (!portTransferData || !$field.length) {
                        return false;
                }

                updatePortTransferRoundtripState($field);
                syncPortTransferReturnDropoffFromFrom($field, false);
                returnDropoff = $.trim($field.find('.hr-port-transfer-return-dropoff-select').val() || '');
                syncPortTransferLegacyDateField($form);
                syncPortTransferGuestFields($form);

                $field.find('input[name="hr_port_transfer_date"]').val(transferDate);
                $field.find('input[name="hr_port_transfer_passengers"]').val(passengers ? String(passengers) : '');
                $field.find('input[name="hr_port_transfer_route_index"]').val(selectedRoute ? String(selectedRoute.index) : '');
                $field.find('input[name="hr_port_transfer_route_label"]').val(selectedRoute ? selectedRoute.label : '');
                $field.find('input[name="hr_port_transfer_from"]').val(from || '');
                $field.find('input[name="hr_port_transfer_to"]').val(to || '');
                $field.find('input[name="hr_port_transfer_direction"]').val(direction);
                $field.find('input[name="hr_port_transfer_type"]').val(transferType || '');
                $field.find('input[name="hr_port_transfer_time"]').val(transferTime || '');
                $field.find('input[name="hr_port_transfer_flight_number"]').val(flightNumber);
                $field.find('input[name="hr_port_transfer_hotel_name"]').val(hotelName);
                $field.find('input[name="hr_port_transfer_dropoff"]').val(dropoff);
                $field.find('input[name="hr_port_transfer_return_date"]').val(transferType === 'roundtrip' ? returnDate : '');
                $field.find('input[name="hr_port_transfer_return_time"]').val(transferType === 'roundtrip' ? (returnTime || '') : '');
                $field.find('input[name="hr_port_transfer_return_dropoff"]').val(transferType === 'roundtrip' ? returnDropoff : '');
                $field.find('input[name="hr_port_transfer_unit_price"]').val(selectedRoute && Number.isFinite(Number(selectedRoute.rate)) ? String(selectedRoute.rate) : '');
                $field.find('input[name="hr_port_transfer_addons"]').val(JSON.stringify(selectedAddons.items));
                $field.find('input[name="hr_port_transfer_addons_total"]').val(Number.isFinite(selectedAddons.total) && selectedAddons.total > 0 ? String(selectedAddons.total) : '');
                $field.find('input[name="hr_port_transfer_selection_payload"]').val('');
                $field.find('input[name="hr_port_transfer_total"]').val('');

                if (!transferDate) {
                        setPortTransferValidationMessage($form, $total, window.hrBooking ? window.hrBooking.portTransferChooseDate : '');
                        $form.data('hr-port-transfer-total', 0).data('hr-port-transfer-total-valid', false);
                        syncPortTransferBaseBookingState($form, 0, passengers, false);
                        syncPortTransferAjaxTotal($form);
                        return false;
                }

                if (!passengers) {
                        setPortTransferValidationMessage($form, $total, window.hrBooking ? window.hrBooking.portTransferChoosePassengers : '');
                        $form.data('hr-port-transfer-total', 0).data('hr-port-transfer-total-valid', false);
                        syncPortTransferBaseBookingState($form, 0, passengers, false);
                        syncPortTransferAjaxTotal($form);
                        return false;
                }

                if (!from || !to || from === to) {
                        setPortTransferValidationMessage($form, $total, window.hrBooking ? window.hrBooking.portTransferChooseRoute : '');
                        $form.data('hr-port-transfer-total', 0).data('hr-port-transfer-total-valid', false);
                        syncPortTransferBaseBookingState($form, 0, passengers, false);
                        syncPortTransferAjaxTotal($form);
                        return false;
                }

                if (!transferType) {
                        setPortTransferValidationMessage($form, $total, window.hrBooking ? window.hrBooking.portTransferChooseType : '');
                        $form.data('hr-port-transfer-total', 0).data('hr-port-transfer-total-valid', false);
                        syncPortTransferBaseBookingState($form, 0, passengers, false);
                        syncPortTransferAjaxTotal($form);
                        return false;
                }

                if (!selectedRoute || !Number.isFinite(total) || total <= 0) {
                        setPortTransferValidationMessage($form, $total, window.hrBooking ? window.hrBooking.portTransferChooseRoute : '');
                        $form.data('hr-port-transfer-total', 0).data('hr-port-transfer-total-valid', false);
                        syncPortTransferBaseBookingState($form, 0, passengers, false);
                        syncPortTransferAjaxTotal($form);
                        return false;
                }

                if (!transferTime) {
                        setPortTransferValidationMessage($form, $total, window.hrBooking ? window.hrBooking.portTransferChooseTime : '');
                        $form.data('hr-port-transfer-total', 0).data('hr-port-transfer-total-valid', false);
                        syncPortTransferBaseBookingState($form, 0, passengers, false);
                        syncPortTransferAjaxTotal($form);
                        return false;
                }

                if (!hotelName) {
                        setPortTransferValidationMessage($form, $total, window.hrBooking ? window.hrBooking.portTransferChooseHotelName : '');
                        $form.data('hr-port-transfer-total', 0).data('hr-port-transfer-total-valid', false);
                        syncPortTransferBaseBookingState($form, 0, passengers, false);
                        syncPortTransferAjaxTotal($form);
                        return false;
                }

                if (transferType === 'roundtrip' && !returnDate) {
                        setPortTransferValidationMessage($form, $total, window.hrBooking ? window.hrBooking.portTransferChooseReturnDate : '');
                        $form.data('hr-port-transfer-total', 0).data('hr-port-transfer-total-valid', false);
                        syncPortTransferBaseBookingState($form, 0, passengers, false);
                        syncPortTransferAjaxTotal($form);
                        return false;
                }

                if (transferType === 'roundtrip' && startDateObject && returnDateObject && returnDateObject.getTime() < startDateObject.getTime()) {
                        setPortTransferValidationMessage($form, $total, window.hrBooking ? window.hrBooking.portTransferChooseReturnDate : '');
                        $form.data('hr-port-transfer-total', 0).data('hr-port-transfer-total-valid', false);
                        syncPortTransferBaseBookingState($form, 0, passengers, false);
                        syncPortTransferAjaxTotal($form);
                        return false;
                }

                if (transferType === 'roundtrip' && !returnTime) {
                        setPortTransferValidationMessage($form, $total, window.hrBooking ? window.hrBooking.portTransferChooseReturnTime : '');
                        $form.data('hr-port-transfer-total', 0).data('hr-port-transfer-total-valid', false);
                        syncPortTransferBaseBookingState($form, 0, passengers, false);
                        syncPortTransferAjaxTotal($form);
                        return false;
                }

                if (transferType === 'roundtrip' && !returnDropoff) {
                        setPortTransferValidationMessage($form, $total, window.hrBooking ? window.hrBooking.portTransferChooseReturnDropoff : '');
                        $form.data('hr-port-transfer-total', 0).data('hr-port-transfer-total-valid', false);
                        syncPortTransferBaseBookingState($form, 0, passengers, false);
                        syncPortTransferAjaxTotal($form);
                        return false;
                }

                valid = true;
                $field.find('input[name="hr_port_transfer_selection_payload"]').val(JSON.stringify({
                        routeIndex: selectedRoute.index,
                        passengers: passengers,
                        transferType: transferType,
                        from: from,
                        to: to,
                        direction: direction,
                        addons: $.map(selectedAddons.items, function (addon) {
                                return addon.index;
                        })
                }));
                $field.find('input[name="hr_port_transfer_total"]').val(String(total));
                $total.text((window.hrBooking ? window.hrBooking.portTransferTotalLabel : 'Port transfer total: %s').replace('%s', formatPrice(total)));
                $form.data('hr-port-transfer-total', total).data('hr-port-transfer-total-valid', valid);
                syncPortTransferBaseBookingState($form, total, passengers, valid);
                syncPortTransferAjaxTotal($form);
                return true;
        }

        function preparePortTransferValidationState($form, portTransferData) {
                var valid;

                markPortTransferValidationAttempted($form);
                hideLegacyPortTransferFields($form);
                valid = updatePortTransferSelection($form, portTransferData);
                syncPortTransferAjaxTotal($form);
                schedulePortTransferBaseValidationClear($form);

                return valid;
        }

        function stopPortTransferInvalidSubmit(event) {
                if (!event) {
                        return;
                }

                if (event.preventDefault) {
                        event.preventDefault();
                }

                if (event.stopImmediatePropagation) {
                        event.stopImmediatePropagation();
                } else if (event.stopPropagation) {
                        event.stopPropagation();
                }
        }

        function handlePortTransferValidationAttempt($form, portTransferData, event) {
                var valid = preparePortTransferValidationState($form, portTransferData);

                if (!valid) {
                        stopPortTransferInvalidSubmit(event);
                }

                return valid;
        }

        function handlePortTransferPassengerButton($button, portTransferData) {
                var $form = $button.closest('form');
                var $qty = $form.find('.hr-port-transfer-passenger-count').first();
                var current = parseInt($qty.val(), 10) || 0;
                var min = parseInt($qty.attr('min'), 10) || 0;
                var max = parseInt($qty.attr('max'), 10) || 99;

                if (!$form.length || !$qty.length) {
                        return;
                }

                if ($button.hasClass('hr-port-transfer-passenger-plus')) {
                        current += 1;
                } else {
                        current -= 1;
                }

                current = Math.max(min, Math.min(current, max));
                $qty.val(String(current)).trigger('change');

                if (portTransferData) {
                        updatePortTransferSelection($form, portTransferData);
                        schedulePortTransferAjaxTotalSync($form);
                }
        }

        function handlePortTransferSwapButton($button, portTransferData) {
                var $form = $button.closest('form');
                var $field = $form.find('.hr-port-transfer-booking-field');
                var currentFrom = $field.find('.hr-port-transfer-from-select').val();
                var currentTo = $field.find('.hr-port-transfer-to-select').val();
                var direction = $field.data('hr-port-transfer-direction') || 'from_to';

                $field.data('hr-port-transfer-direction', direction === 'from_to' ? 'to_from' : 'from_to');
                refreshPortTransferLocationSelects($field, portTransferData, currentTo, currentFrom);
                syncPortTransferReturnDropoffFromFrom($field, true);
                updatePortTransferSelection($form, portTransferData);
                schedulePortTransferAjaxTotalSync($form);
        }

        function getPickupDropoffData($form) {
                var $field = $form.find('.hr-pickup-dropoff-booking-field');
                var raw = $field.attr('data-pickup-dropoff');

                if (!$field.length || !raw) {
                        return null;
                }

                try {
                        return JSON.parse(raw);
                } catch (error) {
                        return null;
                }
        }

        function hidePickupDropoffAjaxTotal($form) {
                var $ajaxTotal = $form.find('.ajax-show-total');

                $form.addClass('hr-pickup-dropoff-booking-form');

                $ajaxTotal.each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });

                $ajaxTotal.find('.ovabrw-ajax-total').each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });
        }

        function syncPickupDropoffAjaxTotal($form) {
                var total = Number($form.data('hr-pickup-dropoff-total') || 0);
                var valid = $form.data('hr-pickup-dropoff-total-valid') === true;
                var $ajaxTotal = getPackageAjaxTotal($form);

                if (!$ajaxTotal.length) {
                        return;
                }

                $ajaxTotal
                        .text(valid && Number.isFinite(total) ? String(total) : '')
                        .attr('data-total', valid && Number.isFinite(total) ? String(total) : '');
        }

        function schedulePickupDropoffAjaxTotalSync($form) {
                [0, 100, 350, 800].forEach(function (delay) {
                        window.setTimeout(function () {
                                syncPickupDropoffAjaxTotal($form);
                        }, delay);
                });
        }

        function getPickupDropoffNativeMinDate() {
                var today = normalizeDate(new Date());

                return [
                        today.getFullYear(),
                        padDatePart(today.getMonth() + 1),
                        padDatePart(today.getDate())
                ].join('-');
        }

        function hidePickupDropoffFallbackDateCalendar() {
                $('.hr-pickup-dropoff-calendar').prop('hidden', true).removeClass('is-open');
                $(document).off('.hrPickupDropoffCalendar');
                $(window).off('.hrPickupDropoffCalendar');
        }

        function showPickupDropoffFallbackDateCalendar($form, $input) {
                var $calendar = $('.hr-pickup-dropoff-calendar').first();
                var selectedDate = parseBookingDate($input);
                var today = normalizeDate(new Date());
                var viewDate;
                var selectedKey = selectedDate ? getSelfDriveFallbackCalendarDateKey(selectedDate) : '';
                var minKey = today ? getSelfDriveFallbackCalendarDateKey(today) : '';

                if (selectedDate && today && selectedDate.getTime() < today.getTime()) {
                        selectedDate = null;
                        selectedKey = '';
                }

                viewDate = selectedDate || today || normalizeDate(new Date());

                if (!$calendar.length) {
                        $calendar = $('<div>', {
                                class: 'hr-self-drive-return-calendar hr-pickup-dropoff-calendar',
                                role: 'dialog',
                                'aria-label': window.hrBooking && window.hrBooking.pickupDropoffRentalDateLabel ? window.hrBooking.pickupDropoffRentalDateLabel : 'Rental Date',
                                hidden: true
                        }).appendTo(document.body);
                }

                $calendar
                        .data('form', $form)
                        .data('input', $input)
                        .data('view-year', viewDate.getFullYear())
                        .data('view-month', viewDate.getMonth())
                        .data('selected-key', selectedKey)
                        .data('min-key', minKey)
                        .prop('hidden', false)
                        .addClass('is-open');

                renderSelfDriveFallbackReturnDateCalendar($calendar);
                positionSelfDriveFallbackReturnDateCalendar($calendar, $input);

                $calendar.off('.hrPickupDropoffCalendar')
                        .on('mousedown.hrPickupDropoffCalendar touchstart.hrPickupDropoffCalendar', function (event) {
                                event.stopPropagation();
                        })
                        .on('click.hrPickupDropoffCalendar', '[data-hr-calendar-nav]', function () {
                                var offset = Number($(this).attr('data-hr-calendar-nav') || 0);
                                var nextDate = new Date(Number($calendar.data('view-year')), Number($calendar.data('view-month')) + offset, 1);

                                $calendar
                                        .data('view-year', nextDate.getFullYear())
                                        .data('view-month', nextDate.getMonth());
                                renderSelfDriveFallbackReturnDateCalendar($calendar);
                        })
                        .on('click.hrPickupDropoffCalendar', '[data-hr-calendar-date]', function () {
                                var date = parseSelfDriveFallbackCalendarDateKey($(this).attr('data-hr-calendar-date'));
                                var value;

                                if (!date || $(this).prop('disabled')) {
                                        return;
                                }

                                value = formatDateForField(date, $input, $input);
                                $input.val(value).attr('value', value).trigger('input').trigger('change');
                                hidePickupDropoffFallbackDateCalendar();
                        });

                $(document).off('.hrPickupDropoffCalendar').on('mousedown.hrPickupDropoffCalendar touchstart.hrPickupDropoffCalendar', function (event) {
                        if ($(event.target).closest('.hr-pickup-dropoff-calendar, .hr-pickup-dropoff-date-input').length) {
                                return;
                        }

                        hidePickupDropoffFallbackDateCalendar();
                });

                $(window).off('.hrPickupDropoffCalendar').on('resize.hrPickupDropoffCalendar scroll.hrPickupDropoffCalendar', function () {
                        if (!$calendar.prop('hidden')) {
                                positionSelfDriveFallbackReturnDateCalendar($calendar, $input);
                        }
                });
        }

        function showPickupDropoffDatePicker($form, $input) {
                if (!$input || !$input.length) {
                        return;
                }

                if ($input[0]._flatpickr) {
                        try {
                                $input[0]._flatpickr.open();
                                if ($('.flatpickr-calendar.open').length) {
                                        return;
                                }
                        } catch (error) {
                                // Fall through to the plugin fallback calendar.
                        }
                }

                if ($.fn.datepicker && $input.data('datepicker')) {
                        try {
                                $input.datepicker('show');
                                if ($('.ui-datepicker:visible, .datepicker-dropdown:visible').length) {
                                        return;
                                }
                        } catch (error) {
                                // Fall through to the plugin fallback calendar.
                        }
                }

                if ($input[0].showPicker && String($input.attr('type') || '').toLowerCase() === 'date') {
                        try {
                                $input[0].showPicker();
                                return;
                        } catch (error) {
                                // Fall through to the plugin fallback calendar.
                        }
                }

                showPickupDropoffFallbackDateCalendar($form, $input);
        }

        function bindPickupDropoffDatePickerOpen($form, $input) {
                if ($input.data('hr-pickup-dropoff-datepicker-opener-bound')) {
                        return;
                }

                $input
                        .data('hr-pickup-dropoff-datepicker-opener-bound', true)
                        .attr('inputmode', 'numeric')
                        .on('focus.hrPickupDropoffDatePicker click.hrPickupDropoffDatePicker', function () {
                                showPickupDropoffDatePicker($form, $input);
                        });
        }

        function bindPickupDropoffFallbackDateCalendar($form, $input) {
                $input.data('hr-pickup-dropoff-datepicker-bound', 'fallback-calendar');
                bindPickupDropoffDatePickerOpen($form, $input);
        }

        function initPickupDropoffDateInputs($form) {
                var $sourceInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);
                var dateFormat = getDatepickerFormat($sourceInput) || 'dd-mm-yy';

                $form.find('.hr-pickup-dropoff-date-input').each(function () {
                        var $input = $(this);

                        $input.attr('min', getPickupDropoffNativeMinDate());
                        bindPickupDropoffDatePickerOpen($form, $input);

                        if ($input.data('hr-pickup-dropoff-datepicker-bound')) {
                                return;
                        }

                        if (this._flatpickr) {
                                try {
                                        this._flatpickr.set('minDate', 'today');
                                } catch (error) {
                                        // Existing picker did not accept the option.
                                }
                                $input.data('hr-pickup-dropoff-datepicker-bound', 'flatpickr-existing');
                                return;
                        }

                        if (window.flatpickr) {
                                try {
                                        window.flatpickr(this, {
                                                dateFormat: 'd-m-Y',
                                                minDate: 'today',
                                                allowInput: true,
                                                onChange: function () {
                                                        $input.trigger('input').trigger('change');
                                                }
                                        });
                                        $input.data('hr-pickup-dropoff-datepicker-bound', 'flatpickr');
                                        return;
                                } catch (error) {
                                        // Fall through to jQuery UI/native text input.
                                }
                        }

                        if ($.fn.datepicker) {
                                try {
                                        $input.datepicker({
                                                dateFormat: dateFormat,
                                                minDate: 0,
                                                onSelect: function () {
                                                        $(this).trigger('input').trigger('change');
                                                }
                                        });
                                        $input.data('hr-pickup-dropoff-datepicker-bound', 'jquery-ui');
                                        return;
                                } catch (error) {
                                        bindPickupDropoffFallbackDateCalendar($form, $input);
                                        return;
                                }
                        }

                        bindPickupDropoffFallbackDateCalendar($form, $input);
                });
        }

        function getPickupDropoffPairKey(from, to) {
                var pair = [String(from || ''), String(to || '')];

                pair.sort(function (a, b) {
                        return a.toLowerCase().localeCompare(b.toLowerCase());
                });

                return pair[0].toLowerCase() + '\u0000' + pair[1].toLowerCase();
        }

        function getSelectedPickupDropoffRoute($field, pickupDropoffData) {
                var routes = pickupDropoffData && $.isArray(pickupDropoffData.routes) ? pickupDropoffData.routes : [];
                var from = $field.find('.hr-pickup-dropoff-from-select').val();
                var to = $field.find('.hr-pickup-dropoff-to-select').val();
                var pairKey = getPickupDropoffPairKey(from, to);
                var selected = null;

                if (!from || !to || from === to) {
                        return null;
                }

                routes.forEach(function (route, index) {
                        if (selected) {
                                return;
                        }

                        if (pairKey === getPickupDropoffPairKey(route.from, route.to)) {
                                selected = {
                                        index: index,
                                        from: from,
                                        to: to,
                                        label: from + ' to ' + to,
                                        rate: Number(route.rate)
                                };
                        }
                });

                return selected;
        }

        function getSelectedPickupDropoffAddons($field) {
                var addons = [];
                var total = 0;

                $field.find('.hr-pickup-dropoff-addon-option:checked').each(function () {
                        var $option = $(this);
                        var index = parseInt($option.val(), 10);
                        var rate = Number($option.data('rate') || 0);
                        var label = String($option.data('label') || '');

                        if (!Number.isFinite(rate)) {
                                rate = 0;
                        }

                        addons.push({
                                index: index,
                                label: label,
                                rate: rate
                        });
                        total += rate;
                });

                return {
                        items: addons,
                        total: total
                };
        }

        function getPickupDropoffRentalDateValue($form) {
                var $displayInput = $form.find('.hr-pickup-dropoff-date-input').first();

                if ($displayInput.length) {
                        return String($displayInput.val() || '');
                }

                return $form.find('input[name="hr_pickup_dropoff_rental_date"]').val() || '';
        }

        function setPickupDropoffFormValue($form, name, value) {
                var $fields = $form.find('[name="' + name + '"]');

                if (!$fields.length) {
                        $fields = $('<input>', {
                                type: 'hidden',
                                name: name
                        }).appendTo($form);
                }

                $fields.val(value).attr('value', value);
        }

        function syncPickupDropoffGuestFields($form) {
                var $passengerInput = $form.find('.hr-pickup-dropoff-passenger-count').first();
                var passengers = parseInt($passengerInput.val(), 10) || 0;

                if (passengers < 1) {
                        setPickupDropoffFormValue($form, 'hr_pickup_dropoff_passengers', '');
                        return;
                }

                setPickupDropoffFormValue($form, 'ovabrw_adults', String(passengers));
                setPickupDropoffFormValue($form, 'ovabrw_childrens', '0');
                setPickupDropoffFormValue($form, 'ovabrw_babies', '0');
                setPickupDropoffFormValue($form, 'hr_pickup_dropoff_passengers', String(passengers));
        }

        function hidePickupDropoffGuestFields($form) {
                var passengers = Math.max(parseInt($form.find('.hr-pickup-dropoff-passenger-count').first().val(), 10) || 1, 1);

                $form.find(GUEST_INPUT_SELECTOR).each(function () {
                        var $input = $(this);
                        var name = String($input.attr('name') || '');
                        var value = name === 'ovabrw_adults' ? String(passengers) : '0';

                        $input
                                .val(value)
                                .attr('value', value)
                                .prop('required', false)
                                .removeAttr('required')
                                .removeAttr('aria-required')
                                .removeClass('ovabrw-input-required error');

                        hideLegacyAirportTransferField($input);
                });

                setPickupDropoffFormValue($form, 'ovabrw_adults', String(passengers));
                setPickupDropoffFormValue($form, 'ovabrw_childrens', '0');
                setPickupDropoffFormValue($form, 'ovabrw_babies', '0');
        }

        function hasPickupDropoffValidationAttempted($form) {
                return $form.data('hr-pickup-dropoff-validation-attempted') === true;
        }

        function markPickupDropoffValidationAttempted($form) {
                $form.data('hr-pickup-dropoff-validation-attempted', true);
                $form.addClass('hr-pickup-dropoff-validation-attempted');
        }

        function setPickupDropoffValidationMessage($form, $target, message) {
                $target.text(hasPickupDropoffValidationAttempted($form) ? (message || '') : '');
        }

        function clearPickupDropoffBaseValidation($form) {
                var baseErrorPattern = /(?:^|\b)field\s+is\s+required\.?$|number\s+of\s+guests|guests?\s+is\s+required|passengers?\s+is\s+required|passenger.*required|total\s+must\s+be\s+greater\s+than\s+0|total.*greater\s+than\s+0/i;
                var total = Number($form.find('input[name="hr_pickup_dropoff_total"]').val() || 0);
                var totalIsValid = Number.isFinite(total) && total > 0;

                $form.find('label, span, p, div, li').each(function () {
                        var $element = $(this);
                        var directText = getDirectText($element);
                        var fullText = $.trim($element.text()).replace(/\s+/g, ' ');
                        var isAjaxError = $element.hasClass('ajax-error');

                        if (!baseErrorPattern.test(directText) && !(isAjaxError && baseErrorPattern.test(fullText))) {
                                return;
                        }

                        if (isAjaxError) {
                                $element.empty().hide();
                                return;
                        }

                        $element.remove();
                });

                if (!hasPickupDropoffValidationAttempted($form) || totalIsValid) {
                        $form.find('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit')
                                .prop('disabled', false)
                                .removeAttr('disabled')
                                .removeClass('disabled loading');
                }
        }

        function neutralizePickupDropoffHiddenRequiredFields($form) {
                var $pickupDropoffField = $form.find('.hr-pickup-dropoff-booking-field');

                if (!$pickupDropoffField.length) {
                        return;
                }

                $form.find('.ovabrw-input-required, [required], [aria-required="true"]').not($pickupDropoffField.find('.ovabrw-input-required, [required], [aria-required="true"]')).each(function () {
                        var $control = $(this);
                        var type = String($control.attr('type') || '').toLowerCase();
                        var hidden = type === 'hidden' ||
                                !$control.is(':visible') ||
                                $control.closest('.hr-legacy-airport-transfer-field-hidden, [aria-hidden="true"], .ovabrw-wrapper-guestspicker').length > 0;

                        if (!hidden) {
                                return;
                        }

                        $control
                                .prop('required', false)
                                .removeAttr('required')
                                .removeAttr('aria-required')
                                .removeAttr('data-error')
                                .removeClass('ovabrw-input-required ovabrw-required error');

                        if ($control.is('input, textarea') && !$control.val()) {
                                $control.val(type === 'checkbox' || type === 'radio' ? 'yes' : '1').attr('value', type === 'checkbox' || type === 'radio' ? 'yes' : '1');
                        }
                });

                $form.find('.hr-legacy-airport-transfer-field-hidden .ovabrw-required, .ovabrw-wrapper-guestspicker .ovabrw-required')
                        .removeClass('ovabrw-required');
        }

        function hideLegacyPickupDropoffFields($form) {
                if (!$form.find('.hr-pickup-dropoff-booking-field').length) {
                        return;
                }

                hidePickupDropoffGuestFields($form);
                neutralizePickupDropoffHiddenRequiredFields($form);
                clearPickupDropoffBaseValidation($form);
        }

        function schedulePickupDropoffFormSync($form) {
                [0, 100, 350, 800, 1500].forEach(function (delay) {
                        window.setTimeout(function () {
                                initPickupDropoffDateInputs($form);
                                hideLegacyPickupDropoffFields($form);
                        }, delay);
                });
        }

        function schedulePickupDropoffBaseValidationClear($form) {
                [0, 50, 100, 250, 500, 800, 1200, 2000].forEach(function (delay) {
                        window.setTimeout(function () {
                                clearPickupDropoffBaseValidation($form);
                        }, delay);
                });
        }

        function syncPickupDropoffBaseBookingState($form, total, passengers, valid) {
                var safeTotal = Number(total);
                var safePassengers = Math.max(parseInt(passengers, 10) || 0, 0);
                var totalValue = valid && Number.isFinite(safeTotal) && safeTotal > 0 ? String(safeTotal) : '';
                var dataValue = valid && Number.isFinite(safeTotal) && safeTotal > 0 ? safeTotal : '';

                setPickupDropoffFormValue($form, 'quantity', '1');
                setPickupDropoffFormValue($form, 'ovabrw_quantity', '1');
                setPickupDropoffFormValue($form, 'ovabrw_adults', String(Math.max(safePassengers, 1)));
                setPickupDropoffFormValue($form, 'ovabrw_childrens', '0');
                setPickupDropoffFormValue($form, 'ovabrw_babies', '0');
                setPickupDropoffFormValue($form, 'hr_pickup_dropoff_total', totalValue);
                setPickupDropoffFormValue($form, 'ovabrw_total', totalValue);
                setPickupDropoffFormValue($form, 'ovabrw_total_price', totalValue);
                setPickupDropoffFormValue($form, 'ovabrw_amount', totalValue);
                setPickupDropoffFormValue($form, 'total_price', totalValue);

                $form.find('.ajax-show-total, .ovabrw-ajax-total')
                        .attr('data-total', totalValue)
                        .attr('data-price', totalValue)
                        .attr('data-amount', totalValue)
                        .data('total', dataValue)
                        .data('price', dataValue)
                        .data('amount', dataValue);

                $form.find('.ajax-show-total .show-total-number, .ovabrw-ajax-total .show-total-number')
                        .text(totalValue)
                        .attr('data-total', totalValue)
                        .attr('data-price', totalValue)
                        .attr('data-amount', totalValue)
                        .data('total', dataValue)
                        .data('price', dataValue)
                        .data('amount', dataValue);

                if (valid && totalValue) {
                        clearPickupDropoffBaseValidation($form);
                        $form.find('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit')
                                .prop('disabled', false)
                                .removeAttr('disabled')
                                .removeClass('disabled loading');
                }
        }

        function updatePickupDropoffSelection($form, pickupDropoffData) {
                var $field = $form.find('.hr-pickup-dropoff-booking-field');
                var $total = $field.find('.hr-pickup-dropoff-booking-total');
                var rentalDate = getPickupDropoffRentalDateValue($form);
                var rentalDateObject = parseBookingDate($field.find('.hr-pickup-dropoff-date-input').first());
                var today = normalizeDate(new Date());
                var passengers = parseInt($field.find('.hr-pickup-dropoff-passenger-count').val(), 10) || 0;
                var from = $field.find('.hr-pickup-dropoff-from-select').val();
                var to = $field.find('.hr-pickup-dropoff-to-select').val();
                var pickupTime = $field.find('.hr-pickup-dropoff-pickup-time-select').val();
                var hotelName = $.trim($field.find('.hr-pickup-dropoff-hotel-input').val() || '');
                var selectedRoute = getSelectedPickupDropoffRoute($field, pickupDropoffData);
                var selectedAddons = getSelectedPickupDropoffAddons($field);
                var total = selectedRoute ? Number(selectedRoute.rate) + selectedAddons.total : 0;

                function fail(message) {
                        setPickupDropoffValidationMessage($form, $total, message);
                        $form.data('hr-pickup-dropoff-total', 0).data('hr-pickup-dropoff-total-valid', false);
                        syncPickupDropoffBaseBookingState($form, 0, passengers, false);
                        syncPickupDropoffAjaxTotal($form);
                        return false;
                }

                if (!pickupDropoffData || !$field.length) {
                        return false;
                }

                syncPickupDropoffGuestFields($form);

                $field.find('input[name="hr_pickup_dropoff_rental_date"]').val(rentalDate);
                $field.find('input[name="hr_pickup_dropoff_passengers"]').val(passengers ? String(passengers) : '');
                $field.find('input[name="hr_pickup_dropoff_from"]').val(from || '');
                $field.find('input[name="hr_pickup_dropoff_to"]').val(to || '');
                $field.find('input[name="hr_pickup_dropoff_route_index"]').val(selectedRoute ? String(selectedRoute.index) : '');
                $field.find('input[name="hr_pickup_dropoff_route_label"]').val(selectedRoute ? selectedRoute.label : '');
                $field.find('input[name="hr_pickup_dropoff_pickup_time"]').val(pickupTime || '');
                $field.find('input[name="hr_pickup_dropoff_hotel_name"]').val(hotelName);
                $field.find('input[name="hr_pickup_dropoff_unit_price"]').val(selectedRoute && Number.isFinite(Number(selectedRoute.rate)) ? String(selectedRoute.rate) : '');
                $field.find('input[name="hr_pickup_dropoff_addons"]').val(JSON.stringify(selectedAddons.items));
                $field.find('input[name="hr_pickup_dropoff_addons_total"]').val(Number.isFinite(selectedAddons.total) && selectedAddons.total > 0 ? String(selectedAddons.total) : '');
                $field.find('input[name="hr_pickup_dropoff_selection_payload"]').val('');
                $field.find('input[name="hr_pickup_dropoff_total"]').val('');

                if (!rentalDate) {
                        return fail(window.hrBooking ? window.hrBooking.pickupDropoffChooseRentalDate : '');
                }

                if (!rentalDateObject || (today && rentalDateObject.getTime() < today.getTime())) {
                        return fail(window.hrBooking ? window.hrBooking.pickupDropoffChooseRentalDate : '');
                }

                if (!from || !to || from === to || !selectedRoute) {
                        return fail(window.hrBooking ? window.hrBooking.pickupDropoffChooseRoute : '');
                }

                if (!pickupTime) {
                        return fail(window.hrBooking ? window.hrBooking.pickupDropoffChoosePickupTime : '');
                }

                if (!hotelName) {
                        return fail(window.hrBooking ? window.hrBooking.pickupDropoffChooseHotelName : '');
                }

                if (!passengers) {
                        return fail(window.hrBooking ? window.hrBooking.pickupDropoffChoosePassengers : '');
                }

                if (!selectedRoute || !Number.isFinite(total) || total <= 0) {
                        return fail(window.hrBooking ? window.hrBooking.pickupDropoffInvalidLabel : '');
                }

                $field.find('input[name="hr_pickup_dropoff_selection_payload"]').val(JSON.stringify({
                        from: from,
                        to: to,
                        routeIndex: selectedRoute.index,
                        addons: $.map(selectedAddons.items, function (addon) {
                                return addon.index;
                        })
                }));
                $field.find('input[name="hr_pickup_dropoff_total"]').val(String(total));
                $total.text((window.hrBooking ? window.hrBooking.pickupDropoffTotalLabel : 'Pick-up and drop-off total: %s').replace('%s', formatPrice(total)));
                $form.data('hr-pickup-dropoff-total', total).data('hr-pickup-dropoff-total-valid', true);
                syncPickupDropoffBaseBookingState($form, total, passengers, true);
                syncPickupDropoffAjaxTotal($form);
                return true;
        }

        function preparePickupDropoffValidationState($form, pickupDropoffData) {
                var valid;

                markPickupDropoffValidationAttempted($form);
                hideLegacyPickupDropoffFields($form);
                valid = updatePickupDropoffSelection($form, pickupDropoffData);
                syncPickupDropoffAjaxTotal($form);
                schedulePickupDropoffBaseValidationClear($form);

                return valid;
        }

        function stopPickupDropoffInvalidSubmit(event) {
                if (!event) {
                        return;
                }

                if (event.preventDefault) {
                        event.preventDefault();
                }

                if (event.stopImmediatePropagation) {
                        event.stopImmediatePropagation();
                } else if (event.stopPropagation) {
                        event.stopPropagation();
                }
        }

        function handlePickupDropoffValidationAttempt($form, pickupDropoffData, event) {
                var valid = preparePickupDropoffValidationState($form, pickupDropoffData);

                if (!valid) {
                        stopPickupDropoffInvalidSubmit(event);
                }

                return valid;
        }

        function handlePickupDropoffPassengerButton($button, pickupDropoffData) {
                var $form = $button.closest('form');
                var $qty = $form.find('.hr-pickup-dropoff-passenger-count').first();
                var current = parseInt($qty.val(), 10) || 0;
                var min = parseInt($qty.attr('min'), 10) || 0;

                if (!$form.length || !$qty.length) {
                        return;
                }

                if ($button.hasClass('hr-pickup-dropoff-passenger-plus')) {
                        current += 1;
                } else {
                        current -= 1;
                }

                current = Math.max(min, current);
                $qty.val(String(current)).trigger('change');

                if (pickupDropoffData) {
                        updatePickupDropoffSelection($form, pickupDropoffData);
                        schedulePickupDropoffAjaxTotalSync($form);
                }
        }

        function handlePickupDropoffSwapButton($button, pickupDropoffData) {
                var $form = $button.closest('form');
                var $field = $form.find('.hr-pickup-dropoff-booking-field');
                var currentFrom = $field.find('.hr-pickup-dropoff-from-select').val();
                var currentTo = $field.find('.hr-pickup-dropoff-to-select').val();

                $field.find('.hr-pickup-dropoff-from-select').val(currentTo);
                $field.find('.hr-pickup-dropoff-to-select').val(currentFrom);
                updatePickupDropoffSelection($form, pickupDropoffData);
                schedulePickupDropoffAjaxTotalSync($form);
        }

        function getMotorbikeData($form) {
                var $field = $form.find('.hr-motorbike-booking-field');
                var raw = $field.attr('data-motorbike');

                if (!$field.length || !raw) {
                        return null;
                }

                try {
                        return JSON.parse(raw);
                } catch (error) {
                        return null;
                }
        }

        function hideMotorbikeAjaxTotal($form) {
                var $ajaxTotal = $form.find('.ajax-show-total');

                $form.addClass('hr-motorbike-booking-form').attr('enctype', 'multipart/form-data');

                $ajaxTotal.each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });

                $ajaxTotal.find('.ovabrw-ajax-total').each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });
        }

        function syncMotorbikeAjaxTotal($form) {
                var total = Number($form.data('hr-motorbike-total') || 0);
                var valid = $form.data('hr-motorbike-total-valid') === true;
                var $ajaxTotal = getPackageAjaxTotal($form);

                if (!$ajaxTotal.length) {
                        return;
                }

                $ajaxTotal
                        .text(valid && Number.isFinite(total) ? String(total) : '')
                        .attr('data-total', valid && Number.isFinite(total) ? String(total) : '');
        }

        function scheduleMotorbikeAjaxTotalSync($form) {
                [0, 100, 350, 800].forEach(function (delay) {
                        window.setTimeout(function () {
                                syncMotorbikeAjaxTotal($form);
                        }, delay);
                });
        }

        function renderMotorbikeSelectors($form, motorbikeData) {
                var $field = $form.find('.hr-motorbike-booking-field');
                var $wrap = $field.find('.hr-motorbike-model-selectors');
                var models = motorbikeData && $.isArray(motorbikeData.models) ? motorbikeData.models : [];
                var startTimes = motorbikeData && $.isArray(motorbikeData.startTimes) ? motorbikeData.startTimes : [];
                var dayOptions = motorbikeData && $.isArray(motorbikeData.dayOptions) ? motorbikeData.dayOptions : [];
                var deliveryFee = Number(motorbikeData && motorbikeData.deliveryFee ? motorbikeData.deliveryFee : 0);
                var deliveryLabel = window.hrBooking ? window.hrBooking.motorbikeDeliveryLabel : 'Deliver to Address (%s additional charge)';
                var html = '';

                if (!$wrap.length) {
                        return;
                }

                html += '<div class="hr-motorbike-models-title">' + escapeHtml(window.hrBooking ? window.hrBooking.motorbikeModelsLabel : 'Select Motorbike Model & Quantity') + '</div>';
                html += '<div class="hr-motorbike-model-list">';
                models.forEach(function (model, index) {
                        var stock = Math.max(parseInt(model.stock, 10) || 0, 0);
                        html += '<div class="hr-motorbike-model-row" data-model-index="' + index + '" data-rate="' + escapeHtml(model.rate || 0) + '" data-stock="' + stock + '">';
                        html += '<div class="hr-motorbike-model-name">' + escapeHtml(model.name || 'Motorbike') + '</div>';
                        html += '<div class="hr-motorbike-model-rate">' + escapeHtml(formatPrice(model.rate || 0)) + '</div>';
                        html += '<button type="button" class="hr-motorbike-qty-btn hr-motorbike-qty-minus" aria-label="Decrease quantity">-</button>';
                        html += '<input type="number" class="hr-motorbike-model-qty" value="0" min="0" max="' + stock + '" step="1" inputmode="numeric">';
                        html += '<button type="button" class="hr-motorbike-qty-btn hr-motorbike-qty-plus" aria-label="Increase quantity">+</button>';
                        html += '</div>';
                });
                html += '</div>';

                html += '<div class="hr-motorbike-field-group">';
                html += '<select class="hr-motorbike-start-time-select ovabrw-input-required" required aria-required="true">';
                html += '<option value="">' + escapeHtml(window.hrBooking ? window.hrBooking.motorbikeStartTimePlaceholder : 'Pick-up/Delivery Time') + '</option>';
                startTimes.forEach(function (time) {
                        html += '<option value="' + escapeHtml(time) + '">' + escapeHtml(time) + '</option>';
                });
                html += '</select>';
                html += '</div>';

                html += '<div class="hr-motorbike-field-group">';
                html += '<select class="hr-motorbike-days-select ovabrw-input-required" required aria-required="true">';
                html += '<option value="">' + escapeHtml(window.hrBooking ? window.hrBooking.motorbikeDaysPlaceholder : 'No. of Days') + '</option>';
                dayOptions.forEach(function (days) {
                        html += '<option value="' + escapeHtml(days) + '">' + escapeHtml(days) + '</option>';
                });
                html += '</select>';
                html += '</div>';

                html += '<div class="hr-motorbike-field-group">';
                html += '<label class="hr-motorbike-document-label">' + escapeHtml(window.hrBooking ? window.hrBooking.motorbikeDocumentLabel : "Drop your Driver's License & Proof Billing:") + '</label>';
                html += '<input type="file" name="hr_motorbike_document" class="hr-motorbike-document-input ovabrw-input-required" accept=".jpg,.jpeg,.png,.pdf" required aria-required="true">';
                html += '</div>';

                html += '<div class="hr-motorbike-handover-options">';
                html += '<div class="hr-motorbike-handover-title">' + escapeHtml(window.hrBooking ? window.hrBooking.motorbikeHandoverLabel : 'Motorbike Handover Options:') + '</div>';
                html += '<label><input type="radio" name="hr_motorbike_handover_display" class="hr-motorbike-handover-option" value="pickup" required> ' + escapeHtml(window.hrBooking ? window.hrBooking.motorbikePickupLabel : 'Pick-up') + '</label>';
                html += '<label><input type="radio" name="hr_motorbike_handover_display" class="hr-motorbike-handover-option" value="delivery" required> ' + escapeHtml(deliveryLabel.replace('%s', formatPrice(deliveryFee))) + '</label>';
                html += '</div>';

                html += '<textarea class="hr-motorbike-delivery-address" rows="2" placeholder="' + escapeHtml(window.hrBooking ? window.hrBooking.motorbikeAddressPlaceholder : 'Delivery Address') + '" hidden></textarea>';

                $wrap.html(html);
        }

        function labelMotorbikeDateField($form) {
                var $dateInput = findBookingDateInput($form, PACKAGE_START_DATE_INPUT_SELECTOR);
                var labelText = window.hrBooking && window.hrBooking.motorbikeRentalDateLabel ? window.hrBooking.motorbikeRentalDateLabel : 'Rental Date';
                var $container;
                var $label;

                if (!$dateInput.length) {
                        return;
                }

                $container = getLegacyAirportTransferFieldContainer($dateInput);
                if ($container.is('input, textarea, select')) {
                        $container = $dateInput.parent();
                }

                $label = $container.find('label').first();
                if ($label.length) {
                        $label.contents().filter(function () {
                                return this.nodeType === 3;
                        }).first().replaceWith(labelText + ' ');
                }
        }

        function hideMotorbikeGuestFields($form) {
                $form.find(GUEST_INPUT_SELECTOR).each(function () {
                        var $input = $(this);
                        var name = String($input.attr('name') || '');
                        var value = name === 'ovabrw_adults' ? '1' : '0';

                        $input
                                .val(value)
                                .attr('value', value)
                                .prop('required', false)
                                .removeAttr('required')
                                .removeAttr('aria-required')
                                .removeClass('ovabrw-input-required error');

                        hideLegacyAirportTransferField($input);
                });

                setMotorbikeFormValue($form, 'ovabrw_adults', '1');
                setMotorbikeFormValue($form, 'ovabrw_childrens', '0');
                setMotorbikeFormValue($form, 'ovabrw_babies', '0');
        }

        function setMotorbikeFormValue($form, name, value) {
                var $fields = $form.find('[name="' + name + '"]');

                if (!$fields.length) {
                        $fields = $('<input>', {
                                type: 'hidden',
                                name: name
                        }).appendTo($form);
                }

                $fields.val(value).attr('value', value);
        }

        function clearMotorbikeBaseValidation($form) {
                var baseErrorPattern = /(?:^|\b)field\s+is\s+required\.?$|number\s+of\s+guests|guests?\s+is\s+required|guest.*required|total\s+must\s+be\s+greater\s+than\s+0|total.*greater\s+than\s+0/i;
                var motorbikeTotal = Number($form.find('input[name="hr_motorbike_total"]').val() || 0);

                $form.find('label, span, p, div, li').each(function () {
                        var $element = $(this);
                        var text = getDirectText($element);

                        if (!baseErrorPattern.test(text)) {
                                return;
                        }

                        if ($element.hasClass('ajax-error')) {
                                $element.empty().hide();
                                return;
                        }

                        $element.remove();
                });

                if (Number.isFinite(motorbikeTotal) && motorbikeTotal > 0) {
                        $form.find('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit')
                                .prop('disabled', false)
                                .removeAttr('disabled')
                                .removeClass('disabled loading');
                }
        }

        function neutralizeMotorbikeHiddenRequiredFields($form) {
                var $motorbikeField = $form.find('.hr-motorbike-booking-field');

                if (!$motorbikeField.length) {
                        return;
                }

                $form.find('.ovabrw-input-required, [required], [aria-required="true"]').not($motorbikeField.find('.ovabrw-input-required, [required], [aria-required="true"]')).each(function () {
                        var $control = $(this);
                        var type = String($control.attr('type') || '').toLowerCase();
                        var hidden = type === 'hidden' ||
                                !$control.is(':visible') ||
                                $control.closest('.hr-legacy-airport-transfer-field-hidden, [aria-hidden="true"], .ovabrw-wrapper-guestspicker').length > 0;

                        if (!hidden) {
                                return;
                        }

                        $control
                                .prop('required', false)
                                .removeAttr('required')
                                .removeAttr('aria-required')
                                .removeAttr('data-error')
                                .removeClass('ovabrw-input-required ovabrw-required error');

                        if ($control.is('input, textarea') && !$control.val()) {
                                $control.val(type === 'checkbox' || type === 'radio' ? 'yes' : '1').attr('value', type === 'checkbox' || type === 'radio' ? 'yes' : '1');
                        }
                });

                $form.find('.hr-legacy-airport-transfer-field-hidden .ovabrw-required, .ovabrw-wrapper-guestspicker .ovabrw-required')
                        .removeClass('ovabrw-required');
        }

        function scheduleMotorbikeBaseValidationClear($form) {
                [0, 100, 350, 800].forEach(function (delay) {
                        window.setTimeout(function () {
                                clearMotorbikeBaseValidation($form);
                                dumpMotorbikeDebugState($form, 'after delayed base validation clear ' + delay + 'ms');
                        }, delay);
                });
        }

        function syncMotorbikeBaseBookingState($form, total, selectedCount, valid) {
                var safeTotal = Number(total);
                var safeSelectedCount = Math.max(parseInt(selectedCount, 10) || 0, 0);
                var totalValue = valid && Number.isFinite(safeTotal) && safeTotal > 0 ? String(safeTotal) : '';

                setMotorbikeFormValue($form, 'quantity', '1');
                setMotorbikeFormValue($form, 'ovabrw_quantity', String(Math.max(safeSelectedCount, 1)));
                setMotorbikeFormValue($form, 'hr_motorbike_total', totalValue);
                setMotorbikeFormValue($form, 'ovabrw_total', totalValue);
                setMotorbikeFormValue($form, 'ovabrw_total_price', totalValue);
                setMotorbikeFormValue($form, 'ovabrw_amount', totalValue);
                setMotorbikeFormValue($form, 'total_price', totalValue);

                $form.find('.ajax-show-total, .ovabrw-ajax-total')
                        .attr('data-total', totalValue)
                        .attr('data-price', totalValue)
                        .attr('data-amount', totalValue);

                $form.find('.ajax-show-total .show-total-number, .ovabrw-ajax-total .show-total-number')
                        .text(totalValue)
                        .attr('data-total', totalValue)
                        .attr('data-price', totalValue)
                        .attr('data-amount', totalValue);

                if (valid && totalValue) {
                        clearMotorbikeBaseValidation($form);
                        $form.find('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit')
                                .prop('disabled', false)
                                .removeAttr('disabled')
                                .removeClass('disabled loading');
                }
        }

        function hideLegacyMotorbikeFields($form) {
                var $motorbikeField = $form.find('.hr-motorbike-booking-field');
                var modelPattern = /choose\s+motorbike\s+model|motorbike\s+model/i;
                var timePattern = /pick[\s-]*up\s*\/\s*delivery\s*time|pickup\s*\/\s*delivery\s*time|pick[\s-]*up\s*time|delivery\s*time/i;
                var addressPattern = /delivery\s+address/i;
                var handoverPattern = /motorbike\s+handover|office\s+pick[\s-]*up\s*&\s*drop[\s-]*off|delivery\s*&\s*pick[\s-]*up/i;

                if (!$motorbikeField.length) {
                        return;
                }

                hideMotorbikeGuestFields($form);
                neutralizeMotorbikeHiddenRequiredFields($form);
                clearMotorbikeBaseValidation($form);

                $form.find('select').not($motorbikeField.find('select')).each(function () {
                        var $select = $(this);
                        var $container = getLegacyAirportTransferFieldContainer($select);
                        var optionText = $select.find('option').map(function () {
                                return $(this).text();
                        }).get().join(' ');
                        var fieldText = [
                                $select.attr('name') || '',
                                $select.attr('id') || '',
                                $select.attr('aria-label') || '',
                                optionText,
                                $container.text()
                        ].join(' ');

                        if (modelPattern.test(fieldText)) {
                                hideLegacyAirportTransferField($select);
                        }
                });

                $form.find('input, textarea').not($motorbikeField.find('input, textarea')).each(function () {
                        var $input = $(this);
                        var type = String($input.attr('type') || '').toLowerCase();
                        var fieldText = [
                                $input.attr('name') || '',
                                $input.attr('id') || '',
                                $input.attr('placeholder') || '',
                                $input.attr('aria-label') || '',
                                getLegacyAirportTransferFieldContainer($input).text()
                        ].join(' ');

                        if (timePattern.test(fieldText) || addressPattern.test(fieldText) || (/^(checkbox|radio)$/.test(type) && handoverPattern.test(fieldText))) {
                                hideLegacyAirportTransferField($input);
                        }
                });

                $form.find('label, h1, h2, h3, h4, h5, h6, p, span').not($motorbikeField.find('label, h1, h2, h3, h4, h5, h6, p, span')).each(function () {
                        var $element = $(this);
                        var directText = getDirectText($element);
                        var nearbyText = [
                                $element.next().text(),
                                $element.next('input, textarea, select').attr('placeholder') || '',
                                $element.parent().text()
                        ].join(' ');

                        if (/^motorbike\s+handover\s+options:?$/i.test(directText) || handoverPattern.test(directText) || modelPattern.test(directText)) {
                                hideLegacyAirportTransferField($element);
                                return;
                        }

                        if (/^\*$/.test(directText) && timePattern.test(nearbyText)) {
                                hideLegacyAirportTransferField($element);
                        }
                });
        }

        function scheduleMotorbikeFormSync($form) {
                [0, 100, 350, 800, 1500].forEach(function (delay) {
                        window.setTimeout(function () {
                                labelMotorbikeDateField($form);
                                hideLegacyMotorbikeFields($form);
                        }, delay);
                });
        }

        function updateMotorbikeDeliveryAddressState($field) {
                var handover = $field.find('.hr-motorbike-handover-option:checked').val();
                var $address = $field.find('.hr-motorbike-delivery-address');
                var delivery = handover === 'delivery';

                $address.prop('hidden', !delivery).prop('required', delivery).attr('aria-required', delivery ? 'true' : null);

                if (!delivery) {
                        $address.val('');
                }
        }

        function updateMotorbikeSelection($form, motorbikeData) {
                var $field = $form.find('.hr-motorbike-booking-field');
                var $total = $field.find('.hr-motorbike-booking-total');
                var payload = { models: {} };
                var days = parseInt($field.find('.hr-motorbike-days-select').val(), 10) || 0;
                var startTime = $field.find('.hr-motorbike-start-time-select').val();
                var handover = $field.find('.hr-motorbike-handover-option:checked').val() || '';
                var deliveryAddress = $.trim($field.find('.hr-motorbike-delivery-address').val() || '');
                var deliveryFee = Number(motorbikeData && motorbikeData.deliveryFee ? motorbikeData.deliveryFee : 0);
                var selectedCount = 0;
                var total = 0;
                var invalid = false;

                if (!motorbikeData || !$field.length) {
                        return false;
                }

                updateMotorbikeDeliveryAddressState($field);

                $field.find('.hr-motorbike-model-row').each(function () {
                        var $row = $(this);
                        var index = String($row.data('model-index'));
                        var rate = Number($row.data('rate') || 0);
                        var stock = parseInt($row.data('stock'), 10) || 0;
                        var qty = parseInt($row.find('.hr-motorbike-model-qty').val(), 10) || 0;

                        qty = Math.max(0, Math.min(qty, stock));
                        $row.find('.hr-motorbike-model-qty').val(String(qty));

                        if (!qty) {
                                return;
                        }

                        selectedCount += qty;
                        payload.models[index] = qty;

                        if (!days || !Number.isFinite(rate)) {
                                invalid = true;
                                return;
                        }

                        total += rate * qty * days;
                });

                if (!selectedCount) {
                        $field.find('input[name="hr_motorbike_selection_payload"]').val('');
                        $field.find('input[name="hr_motorbike_total"]').val('');
                        $total.text(window.hrBooking ? window.hrBooking.motorbikeChooseModel : '');
                        $form.data('hr-motorbike-total', 0).data('hr-motorbike-total-valid', false);
                        syncMotorbikeBaseBookingState($form, 0, selectedCount, false);
                        syncMotorbikeAjaxTotal($form);
                        return false;
                }

                if (!startTime) {
                        $field.find('input[name="hr_motorbike_selection_payload"]').val('');
                        $field.find('input[name="hr_motorbike_total"]').val('');
                        $total.text(window.hrBooking ? window.hrBooking.motorbikeChooseStartTime : '');
                        $form.data('hr-motorbike-total', 0).data('hr-motorbike-total-valid', false);
                        syncMotorbikeBaseBookingState($form, 0, selectedCount, false);
                        syncMotorbikeAjaxTotal($form);
                        return false;
                }

                if (!days) {
                        $field.find('input[name="hr_motorbike_selection_payload"]').val('');
                        $field.find('input[name="hr_motorbike_total"]').val('');
                        $total.text(window.hrBooking ? window.hrBooking.motorbikeChooseDays : '');
                        $form.data('hr-motorbike-total', 0).data('hr-motorbike-total-valid', false);
                        syncMotorbikeBaseBookingState($form, 0, selectedCount, false);
                        syncMotorbikeAjaxTotal($form);
                        return false;
                }

                if (!handover || (handover === 'delivery' && !deliveryAddress) || invalid) {
                        $field.find('input[name="hr_motorbike_selection_payload"]').val('');
                        $field.find('input[name="hr_motorbike_total"]').val('');
                        $total.text(window.hrBooking ? window.hrBooking.motorbikeInvalidLabel : '');
                        $form.data('hr-motorbike-total', 0).data('hr-motorbike-total-valid', false);
                        syncMotorbikeBaseBookingState($form, 0, selectedCount, false);
                        syncMotorbikeAjaxTotal($form);
                        return false;
                }

                if (handover === 'delivery' && Number.isFinite(deliveryFee)) {
                        total += deliveryFee;
                }

                $field.find('input[name="hr_motorbike_selection_payload"]').val(JSON.stringify(payload));
                $field.find('input[name="hr_motorbike_days"]').val(String(days));
                $field.find('input[name="hr_motorbike_start_time"]').val(startTime);
                $field.find('input[name="hr_motorbike_handover_option"]').val(handover);
                $field.find('input[name="hr_motorbike_delivery_address"]').val(handover === 'delivery' ? deliveryAddress : '');
                $field.find('input[name="hr_motorbike_total"]').val(Number.isFinite(total) ? String(total) : '');
                $total.text(Number.isFinite(total) ? (window.hrBooking ? window.hrBooking.motorbikeTotalLabel : 'Motorbike rental total: %s').replace('%s', formatPrice(total)) : '');
                $form.data('hr-motorbike-total', total).data('hr-motorbike-total-valid', Number.isFinite(total));
                syncMotorbikeBaseBookingState($form, total, selectedCount, Number.isFinite(total) && total > 0);
                syncMotorbikeAjaxTotal($form);
                return true;
        }

        function prepareMotorbikeValidationState($form, motorbikeData) {
                dumpMotorbikeDebugState($form, 'before prepare validation state');
                hideLegacyMotorbikeFields($form);
                updateMotorbikeSelection($form, motorbikeData);
                syncMotorbikeAjaxTotal($form);
                scheduleMotorbikeBaseValidationClear($form);
                dumpMotorbikeDebugState($form, 'after prepare validation state');
        }

        function getSelfDriveData($form) {
                var $field = $form.find('.hr-self-drive-booking-field');
                var raw = $field.attr('data-self-drive');

                if (!$field.length || !raw) {
                        return null;
                }

                try {
                        return JSON.parse(raw);
                } catch (error) {
                        return null;
                }
        }

        function hideSelfDriveAjaxTotal($form) {
                var $ajaxTotal = $form.find('.ajax-show-total');

                $form.addClass('hr-self-drive-booking-form').attr('enctype', 'multipart/form-data');

                $ajaxTotal.each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });

                $ajaxTotal.find('.ovabrw-ajax-total').each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });
        }

        function syncSelfDriveAjaxTotal($form) {
                var total = Number($form.data('hr-self-drive-total') || 0);
                var valid = $form.data('hr-self-drive-total-valid') === true;
                var $ajaxTotal = getPackageAjaxTotal($form);

                if (!$ajaxTotal.length) {
                        return;
                }

                $ajaxTotal
                        .text(valid && Number.isFinite(total) ? String(total) : '')
                        .attr('data-total', valid && Number.isFinite(total) ? String(total) : '');
        }

        function scheduleSelfDriveAjaxTotalSync($form) {
                [0, 100, 350, 800].forEach(function (delay) {
                        window.setTimeout(function () {
                                syncSelfDriveAjaxTotal($form);
                        }, delay);
                });
        }

        function renderSelfDriveSelectors($form, selfDriveData) {
                var $field = $form.find('.hr-self-drive-booking-field');
                var $wrap = $field.find('.hr-self-drive-vehicle-selectors');
                var vehicles = selfDriveData && $.isArray(selfDriveData.vehicles) ? selfDriveData.vehicles : [];
                var startTimes = selfDriveData && $.isArray(selfDriveData.startTimes) ? selfDriveData.startTimes : [];
                var deliveryFee = Number(selfDriveData && selfDriveData.deliveryFee ? selfDriveData.deliveryFee : 0);
                var deliveryLabel = window.hrBooking ? window.hrBooking.selfDriveDeliveryLabel : 'Deliver to Address (%s additional charge)';
                var html = '';

                if (!$wrap.length) {
                        return;
                }

                html += '<div class="hr-self-drive-vehicles-title">' + escapeHtml(window.hrBooking ? window.hrBooking.selfDriveVehiclesLabel : 'Select Vehicle Type & Quantity') + '</div>';
                html += '<div class="hr-self-drive-vehicle-list">';
                vehicles.forEach(function (vehicle, index) {
                        var stock = Math.max(parseInt(vehicle.stock, 10) || 0, 0);
                        if (stock < 1) {
                                return;
                        }
                        html += '<div class="hr-self-drive-vehicle-row" data-vehicle-index="' + index + '" data-rate="' + escapeHtml(vehicle.rate || 0) + '" data-stock="' + stock + '">';
                        html += '<div class="hr-self-drive-vehicle-name">' + escapeHtml(vehicle.name || 'Vehicle') + '</div>';
                        html += '<div class="hr-self-drive-vehicle-rate">' + escapeHtml(formatPrice(vehicle.rate || 0)) + '</div>';
                        html += '<button type="button" class="hr-self-drive-qty-btn hr-self-drive-qty-minus" aria-label="Decrease quantity">-</button>';
                        html += '<input type="number" class="hr-self-drive-vehicle-qty" value="0" min="0" max="' + stock + '" step="1" inputmode="numeric">';
                        html += '<button type="button" class="hr-self-drive-qty-btn hr-self-drive-qty-plus" aria-label="Increase quantity">+</button>';
                        html += '</div>';
                });
                html += '</div>';

                html += '<div class="hr-self-drive-field-group">';
                html += '<select class="hr-self-drive-start-time-select ovabrw-input-required" required aria-required="true">';
                html += '<option value="">' + escapeHtml(window.hrBooking ? window.hrBooking.selfDriveStartTimePlaceholder : 'Pick-up/Delivery Time') + '</option>';
                startTimes.forEach(function (time) {
                        html += '<option value="' + escapeHtml(time) + '">' + escapeHtml(time) + '</option>';
                });
                html += '</select>';
                html += '</div>';

                html += '<div class="hr-self-drive-field-group">';
                html += '<select class="hr-self-drive-return-time-select ovabrw-input-required" required aria-required="true">';
                html += '<option value="">' + escapeHtml(window.hrBooking ? window.hrBooking.selfDriveReturnTimePlaceholder : 'Return Time') + '</option>';
                startTimes.forEach(function (time) {
                        html += '<option value="' + escapeHtml(time) + '">' + escapeHtml(time) + '</option>';
                });
                html += '</select>';
                html += '</div>';

                html += '<div class="hr-self-drive-handover-options">';
                html += '<div class="hr-self-drive-handover-title">' + escapeHtml(window.hrBooking ? window.hrBooking.selfDriveHandoverLabel : 'Vehicle Handover Options:') + '</div>';
                html += '<label><input type="radio" name="hr_self_drive_handover_display" class="hr-self-drive-handover-option" value="pickup" required> ' + escapeHtml(window.hrBooking ? window.hrBooking.selfDrivePickupLabel : 'Office/Garage Pick-up') + '</label>';
                html += '<label><input type="radio" name="hr_self_drive_handover_display" class="hr-self-drive-handover-option" value="delivery" required> ' + escapeHtml(deliveryLabel.replace('%s', formatPrice(deliveryFee))) + '</label>';
                html += '</div>';

                html += '<textarea class="hr-self-drive-delivery-address" rows="2" placeholder="' + escapeHtml(window.hrBooking ? window.hrBooking.selfDriveAddressPlaceholder : 'Delivery Address') + '" hidden></textarea>';

                $wrap.html(html);
        }

        function labelSelfDriveDateField($form) {
                var $dateInput = findSelfDriveRentalDateInput($form);
                var labelText = window.hrBooking && window.hrBooking.selfDriveRentalDateLabel ? window.hrBooking.selfDriveRentalDateLabel : 'Rental Date';
                var $container;
                var $label;

                if (!$dateInput.length) {
                        return;
                }

                $container = getLegacyAirportTransferFieldContainer($dateInput);
                if ($container.is('input, textarea, select')) {
                        $container = $dateInput.parent();
                }

                $label = $container.find('label').first();
                if ($label.length) {
                        $label.contents().filter(function () {
                                return this.nodeType === 3;
                        }).first().replaceWith(labelText + ' ');
                }
        }

        function getSelfDriveReturnDateInput($form) {
                var $fieldInput = $form.find('.hr-self-drive-booking-field .hr-self-drive-return-date-input').first();

                return $fieldInput.length ? $fieldInput : $form.find('.hr-self-drive-return-date-input').first();
        }

        function getSelfDriveReturnDateInputs($form) {
                return getSelfDriveReturnDateInput($form).add($form.find([
                        '.hr-self-drive-return-date-field input',
                        '.ovabrw_checkout_field input.checkout-date[name="ovabrw_pickoff_date"]',
                        '.ovabrw_checkout_field input[name="ovabrw_pickoff_date"]',
                        'input.checkout-date[name="ovabrw_pickoff_date"]',
                        'input[name="ovabrw_pickoff_date"]',
                        'input[name="ovabrw_pickoff_date[]"]',
                        'input[name="ovabrw_dropoff_date"]',
                        'input[name="ovabrw_dropoff_date[]"]'
                ].join(','))).not('[type="hidden"]');
        }

        function getSelfDriveThemeReturnDateInput($form) {
                var $field = $form.find('.hr-self-drive-booking-field');
                var $inputs = $form.find(PACKAGE_END_DATE_INPUT_SELECTOR).filter(isBookingDateCandidate);
                var $namedInputs;

                if ($field.length) {
                        $inputs = $inputs.not($field.find('input'));
                }

                if (!$inputs.length) {
                        return $();
                }

                $namedInputs = $inputs.filter(function () {
                        return /^(?:ovabrw_pickoff_date|ovabrw_dropoff_date)(?:\[\])?$/.test(String(this.name || ''));
                });

                return $namedInputs.length ? $namedInputs.first() : $inputs.first();
        }

        function getSelfDriveReturnDateLabel() {
                return window.hrBooking && window.hrBooking.selfDriveReturnDateLabel ? window.hrBooking.selfDriveReturnDateLabel : 'Return Date';
        }

        function adoptSelfDriveThemeReturnDateField($form, $details, $currentInput, $dateInput) {
                var $themeInput = getSelfDriveThemeReturnDateInput($form);
                var $themeContainer;
                var $label;
                var inputId;
                var placeholder;
                var currentValue;

                if (!$details.length || !$themeInput.length) {
                        return $currentInput;
                }

                if ($currentInput && $currentInput.length && $currentInput[0] === $themeInput[0]) {
                        return $currentInput;
                }

                $themeContainer = getLegacyAirportTransferFieldContainer($themeInput);
                if (!$themeContainer.length || $themeContainer.is('input, textarea, select')) {
                        $themeContainer = $themeInput.parent();
                }

                inputId = $themeInput.attr('id') || 'hr-self-drive-return-date-' + Math.floor(Math.random() * 1000000);
                placeholder = ($currentInput && $currentInput.length ? $currentInput.attr('placeholder') : '') ||
                        ($dateInput && $dateInput.length ? $dateInput.attr('placeholder') : '') ||
                        $themeInput.attr('placeholder') ||
                        'DD-MM-YYYY';
                currentValue = $currentInput && $currentInput.length ? String($currentInput.val() || '') : '';

                if (currentValue && !$themeInput.val()) {
                        $themeInput.val(currentValue).attr('value', currentValue);
                }

                $themeContainer
                        .addClass('rental_item hr-self-drive-return-date-field')
                        .removeClass('ovabrw-hide-field hr-legacy-airport-transfer-field-hidden')
                        .removeAttr('aria-hidden hidden')
                        .prop('hidden', false)
                        .show();

                $themeContainer.each(function () {
                        this.style.removeProperty('display');
                        this.style.removeProperty('visibility');
                        this.style.removeProperty('opacity');
                });

                $themeInput
                        .attr('id', inputId)
                        .attr('placeholder', placeholder)
                        .attr('autocomplete', 'off')
                        .addClass('hr-self-drive-return-date-input checkout-date ovabrw-datepicker ovabrw-input-required')
                        .prop('required', true)
                        .prop('disabled', false)
                        .prop('hidden', false)
                        .removeAttr('disabled hidden')
                        .attr('aria-required', 'true');

                $label = $themeContainer.children('label').first();
                if (!$label.length) {
                        $label = $('<label>').insertBefore($themeInput);
                }

                $label
                        .attr('for', inputId)
                        .addClass('ovabrw-required')
                        .text(getSelfDriveReturnDateLabel());

                if ($details[0] !== $themeContainer[0] && !$.contains($details[0], $themeContainer[0])) {
                        if ($details.find('.hr-self-drive-return-date-field').length) {
                                $themeContainer.insertBefore($details.find('.hr-self-drive-return-date-field').first());
                        } else {
                                $details.prepend($themeContainer);
                        }
                }

                $details.find('.hr-self-drive-return-date-field').not($themeContainer).remove();

                selfDriveDebugLog('adopted booking checkout date field for self-drive return date', getSelfDriveDebugElementState($themeInput));

                return $themeInput;
        }

        function getSelfDriveGuestInput($form) {
                return $form.find('.hr-self-drive-guest-count').first();
        }

        function getSelfDriveHotelNameInput($form) {
                return $form.find('.hr-self-drive-hotel-name-input').first();
        }

        function getSelfDriveDebugRect(element) {
                var rect;

                if (!element || !element.getBoundingClientRect) {
                        return null;
                }

                rect = element.getBoundingClientRect();

                return {
                        left: Math.round(rect.left),
                        top: Math.round(rect.top),
                        right: Math.round(rect.right),
                        bottom: Math.round(rect.bottom),
                        width: Math.round(rect.width),
                        height: Math.round(rect.height)
                };
        }

        function getSelfDriveDebugNodeSummary(node) {
                var $node;

                if (!node) {
                        return null;
                }

                if (node === window) {
                        return {
                                tag: 'window'
                        };
                }

                if (node === document) {
                        return {
                                tag: 'document'
                        };
                }

                if (node.nodeType !== 1) {
                        return {
                                nodeType: node.nodeType,
                                text: truncateMotorbikeDebugValue(node.nodeValue || '', 80)
                        };
                }

                $node = $(node);

                return {
                        tag: node.tagName ? node.tagName.toLowerCase() : '',
                        id: node.id || '',
                        name: $node.attr('name') || '',
                        classes: String(node.className || ''),
                        visible: $node.is(':visible'),
                        rect: getSelfDriveDebugRect(node),
                        text: truncateMotorbikeDebugValue($.trim($node.text()).replace(/\s+/g, ' '), 120)
                };
        }

        function getSelfDriveDebugComputedState($element) {
                var style;

                if (!$element || !$element.length || !window.getComputedStyle) {
                        return {};
                }

                style = window.getComputedStyle($element[0]);

                return {
                        display: style.display,
                        visibility: style.visibility,
                        opacity: style.opacity,
                        pointerEvents: style.pointerEvents,
                        position: style.position,
                        zIndex: style.zIndex,
                        overflow: style.overflow
                };
        }

        function getSelfDriveDebugElementsAtInputCenter($input) {
                var rect = $input && $input.length ? $input[0].getBoundingClientRect() : null;
                var x;
                var y;
                var elements;

                if (!rect || !document.elementFromPoint) {
                        return null;
                }

                x = Math.round(rect.left + (rect.width / 2));
                y = Math.round(rect.top + (rect.height / 2));

                if (x < 0 || y < 0 || x > window.innerWidth || y > window.innerHeight) {
                        return {
                                point: {
                                        x: x,
                                        y: y
                                },
                                inViewport: false
                        };
                }

                elements = document.elementsFromPoint ? document.elementsFromPoint(x, y) : [document.elementFromPoint(x, y)];

                return {
                        point: {
                                x: x,
                                y: y
                        },
                        inViewport: true,
                        top: getSelfDriveDebugNodeSummary(elements[0]),
                        stack: $.map(elements.slice(0, 8), getSelfDriveDebugNodeSummary)
                };
        }

        function getSelfDrivePickerObjectState($input) {
                var picker = $input && $input.length ? $input.data('hr-self-drive-easepick') : null;
                var state = {
                        exists: !!picker
                };

                if (!picker) {
                        return state;
                }

                try {
                        state.type = Object.prototype.toString.call(picker);
                        state.keys = Object.keys(picker).slice(0, 40);
                } catch (error) {
                        state.keysError = error.message || String(error);
                }

                state.showType = typeof picker.show;
                state.hideType = typeof picker.hide;
                state.renderType = typeof picker.renderAll;
                state.destroyType = typeof picker.destroy;
                state.gotoDateType = typeof picker.gotoDate;

                if (picker.ui) {
                        try {
                                state.uiType = Object.prototype.toString.call(picker.ui);
                                state.uiKeys = Object.keys(picker.ui).slice(0, 40);
                        } catch (error) {
                                state.uiKeysError = error.message || String(error);
                        }
                }

                if (picker.options) {
                        state.optionsElementMatches = picker.options.element === $input[0];
                }

                return state;
        }

        function getSelfDrivePickerSurfaceStates($input) {
                var surfaces = [];
                var selector = [
                        'easepick-wrapper',
                        '.easepick-wrapper',
                        '.easepick',
                        '.easepick-root',
                        '.easepick-calendar',
                        '.ui-datepicker',
                        '.xdsoft_datetimepicker',
                        '.flatpickr-calendar',
                        '.datepicker',
                        '.datepicker-dropdown',
                        '.daterangepicker',
                        '.hr-self-drive-return-calendar'
                ].join(',');

                $(selector).each(function (index) {
                        var $surface = $(this);
                        var shadowText = '';

                        if (this.shadowRoot) {
                                shadowText = truncateMotorbikeDebugValue($.trim(this.shadowRoot.textContent || '').replace(/\s+/g, ' '), 160);
                        }

                        surfaces.push({
                                index: index,
                                tag: this.tagName ? this.tagName.toLowerCase() : '',
                                id: this.id || '',
                                classes: String(this.className || ''),
                                visible: $surface.is(':visible'),
                                rect: getSelfDriveDebugRect(this),
                                computed: getSelfDriveDebugComputedState($surface),
                                hiddenAttr: $surface.prop('hidden') === true,
                                ariaHidden: $surface.attr('aria-hidden') || '',
                                parentClasses: String($surface.parent()[0] && $surface.parent()[0].className || ''),
                                hasShadowRoot: !!this.shadowRoot,
                                shadowText: shadowText
                        });
                });

                return surfaces.slice(0, 16);
        }

        function getSelfDriveDatePickerDebugSnapshot($form, $input, reason, event, extra, includeHistory) {
                var $safeInput = $input && $input.length ? $input : getSelfDriveReturnDateInput($form);
                var eventInfo = null;

                if (event) {
                        eventInfo = {
                                type: event.type || '',
                                isTrusted: event.isTrusted === true,
                                defaultPrevented: event.defaultPrevented === true,
                                button: typeof event.button === 'undefined' ? '' : event.button,
                                key: event.key || '',
                                target: getSelfDriveDebugNodeSummary(event.target),
                                currentTarget: getSelfDriveDebugNodeSummary(event.currentTarget)
                        };
                }

                return {
                        reason: reason,
                        time: new Date().toISOString(),
                        input: getSelfDriveDebugElementState($safeInput),
                        inputRect: $safeInput.length ? getSelfDriveDebugRect($safeInput[0]) : null,
                        inputComputed: getSelfDriveDebugComputedState($safeInput),
                        activeElement: getSelfDriveDebugNodeSummary(document.activeElement),
                        elementsAtInputCenter: getSelfDriveDebugElementsAtInputCenter($safeInput),
                        libraries: {
                                easepick: !!window.easepick,
                                easepickCreateType: window.easepick ? typeof window.easepick.create : '',
                                flatpickr: typeof window.flatpickr,
                                jqueryDatepicker: $.fn.datepicker ? typeof $.fn.datepicker : '',
                                jqueryDatetimepicker: $.fn.datetimepicker ? typeof $.fn.datetimepicker : ''
                        },
                        picker: getSelfDrivePickerObjectState($safeInput),
                        surfaces: getSelfDrivePickerSurfaceStates($safeInput),
                        event: eventInfo,
                        extra: extra || null,
                        recentEvents: includeHistory === false ? [] : ($form.data('hr-self-drive-return-date-events') || []),
                        openAttempts: includeHistory === false ? [] : ($form.data('hr-self-drive-return-date-open-attempts') || [])
                };
        }

        function pushSelfDriveDatePickerDebugHistory($form, key, entry) {
                var entries = $form.data(key) || [];

                entries.push(entry);
                entries = entries.slice(-12);

                $form.data(key, entries);
                $form.data('hr-self-drive-return-date-last-debug', entry);
                window.hrSelfDriveReturnDateLastDebug = entry;
        }

        function isSelfDriveDebugPointInsideElement(event, element) {
                var rect;

                if (!event || typeof event.clientX === 'undefined' || typeof event.clientY === 'undefined' || !element || !element.getBoundingClientRect) {
                        return false;
                }

                rect = element.getBoundingClientRect();

                return event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom;
        }

        function installSelfDriveReturnDatePickerDebug($form, $returnInput) {
                var input = $returnInput && $returnInput.length ? $returnInput[0] : null;
                var eventTypes = ['pointerdown', 'mousedown', 'mouseup', 'click', 'focus', 'focusin', 'keydown'];

                if (!selfDriveDebugEnabled || !input || $returnInput.data('hr-self-drive-datepicker-debug-bound')) {
                        return;
                }

                $returnInput.data('hr-self-drive-datepicker-debug-bound', true);

                eventTypes.forEach(function (eventType) {
                        input.addEventListener(eventType, function (event) {
                                var snapshot = getSelfDriveDatePickerDebugSnapshot($form, $returnInput, 'input capture ' + eventType, event, null, false);

                                pushSelfDriveDatePickerDebugHistory($form, 'hr-self-drive-return-date-events', snapshot);
                                selfDriveDebugLog('return date input capture ' + eventType, snapshot);
                        }, true);
                });

                if ($(document).data('hr-self-drive-return-date-document-debug-bound')) {
                        return;
                }

                $(document).data('hr-self-drive-return-date-document-debug-bound', true);

                ['pointerdown', 'click'].forEach(function (eventType) {
                        document.addEventListener(eventType, function (event) {
                                getBookingForms().filter(function () {
                                        return $(this).find('.hr-self-drive-booking-field').length > 0;
                                }).each(function () {
                                        var $debugForm = $(this);
                                        var $debugInput = getSelfDriveReturnDateInput($debugForm);
                                        var nearReturnDate;
                                        var snapshot;

                                        if (!$debugInput.length) {
                                                return;
                                        }

                                        nearReturnDate = $(event.target).closest('.hr-self-drive-return-date-field, .hr-self-drive-return-date-input').length > 0 ||
                                                isSelfDriveDebugPointInsideElement(event, $debugInput[0]);

                                        if (!nearReturnDate) {
                                                return;
                                        }

                                        snapshot = getSelfDriveDatePickerDebugSnapshot($debugForm, $debugInput, 'document capture ' + eventType, event, null, false);
                                        pushSelfDriveDatePickerDebugHistory($debugForm, 'hr-self-drive-return-date-events', snapshot);
                                        selfDriveDebugLog('return date document capture ' + eventType, snapshot);
                                });
                        }, true);
                });
        }

        function hideSelfDriveFallbackReturnDateCalendar() {
                $('.hr-self-drive-return-calendar').prop('hidden', true).removeClass('is-open');
                $(document).off('.hrSelfDriveReturnCalendar');
                $(window).off('.hrSelfDriveReturnCalendar');
        }

        function formatSelfDriveFallbackCalendarMonth(date) {
                return date.toLocaleDateString(window.hrBooking && window.hrBooking.locale ? window.hrBooking.locale : undefined, {
                        month: 'long',
                        year: 'numeric'
                });
        }

        function getSelfDriveFallbackCalendarDateKey(date) {
                return [
                        date.getFullYear(),
                        padDatePart(date.getMonth() + 1),
                        padDatePart(date.getDate())
                ].join('-');
        }

        function parseSelfDriveFallbackCalendarDateKey(value) {
                var parts = String(value || '').split('-');

                if (parts.length !== 3) {
                        return null;
                }

                return normalizeDate(new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2])));
        }

        function positionSelfDriveFallbackReturnDateCalendar($calendar, $input) {
                var rect = $input[0].getBoundingClientRect();
                var calendarWidth = Math.max(rect.width, 300);
                var left = rect.left + window.pageXOffset;
                var top = rect.bottom + window.pageYOffset + 6;
                var viewportRight = window.pageXOffset + window.innerWidth - 12;

                if (left + calendarWidth > viewportRight) {
                        left = Math.max(12 + window.pageXOffset, viewportRight - calendarWidth);
                }

                $calendar.css({
                        left: Math.round(left) + 'px',
                        top: Math.round(top) + 'px',
                        width: Math.round(calendarWidth) + 'px'
                });
        }

        function renderSelfDriveFallbackReturnDateCalendar($calendar) {
                var viewYear = Number($calendar.data('view-year'));
                var viewMonth = Number($calendar.data('view-month'));
                var selectedKey = String($calendar.data('selected-key') || '');
                var minKey = String($calendar.data('min-key') || '');
                var monthStart = new Date(viewYear, viewMonth, 1);
                var firstDayOffset = monthStart.getDay();
                var daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
                var dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                var html = '';
                var cellIndex;
                var day;
                var date;
                var key;
                var disabled;

                html += '<div class="hr-self-drive-return-calendar-header">';
                html += '<button type="button" class="hr-self-drive-return-calendar-nav" data-hr-calendar-nav="-1" aria-label="Previous month">&lt;</button>';
                html += '<div class="hr-self-drive-return-calendar-title">' + escapeHtml(formatSelfDriveFallbackCalendarMonth(monthStart)) + '</div>';
                html += '<button type="button" class="hr-self-drive-return-calendar-nav" data-hr-calendar-nav="1" aria-label="Next month">&gt;</button>';
                html += '</div>';
                html += '<div class="hr-self-drive-return-calendar-grid hr-self-drive-return-calendar-weekdays">';
                dayNames.forEach(function (dayName) {
                        html += '<div>' + escapeHtml(dayName) + '</div>';
                });
                html += '</div>';
                html += '<div class="hr-self-drive-return-calendar-grid">';

                for (cellIndex = 0; cellIndex < firstDayOffset; cellIndex += 1) {
                        html += '<span class="hr-self-drive-return-calendar-empty"></span>';
                }

                for (day = 1; day <= daysInMonth; day += 1) {
                        date = new Date(viewYear, viewMonth, day);
                        key = getSelfDriveFallbackCalendarDateKey(date);
                        disabled = minKey && key < minKey;

                        html += '<button type="button" class="hr-self-drive-return-calendar-day' +
                                (key === selectedKey ? ' is-selected' : '') +
                                (disabled ? ' is-disabled' : '') +
                                '" data-hr-calendar-date="' + escapeHtml(key) + '"' +
                                (disabled ? ' disabled aria-disabled="true"' : '') +
                                '>' + day + '</button>';
                }

                html += '</div>';

                $calendar.html(html);
        }

        function showSelfDriveFallbackReturnDateCalendar($form, $input) {
                var $calendar = $('.hr-self-drive-return-calendar').first();
                var $startInput = findSelfDriveRentalDateInput($form);
                var selectedDate = parseBookingDate($input);
                var minDate = parseBookingDate($startInput);
                var viewDate = selectedDate || minDate || normalizeDate(new Date());
                var selectedKey = selectedDate ? getSelfDriveFallbackCalendarDateKey(selectedDate) : '';
                var minKey = minDate ? getSelfDriveFallbackCalendarDateKey(minDate) : '';

                if (!$calendar.length) {
                        $calendar = $('<div>', {
                                class: 'hr-self-drive-return-calendar',
                                role: 'dialog',
                                'aria-label': getSelfDriveReturnDateLabel(),
                                hidden: true
                        }).appendTo(document.body);
                }

                $calendar
                        .data('form', $form)
                        .data('input', $input)
                        .data('view-year', viewDate.getFullYear())
                        .data('view-month', viewDate.getMonth())
                        .data('selected-key', selectedKey)
                        .data('min-key', minKey)
                        .prop('hidden', false)
                        .addClass('is-open');

                renderSelfDriveFallbackReturnDateCalendar($calendar);
                positionSelfDriveFallbackReturnDateCalendar($calendar, $input);

                $calendar.off('.hrSelfDriveReturnCalendar')
                        .on('click.hrSelfDriveReturnCalendar', '[data-hr-calendar-nav]', function () {
                                var offset = Number($(this).attr('data-hr-calendar-nav') || 0);
                                var nextDate = new Date(Number($calendar.data('view-year')), Number($calendar.data('view-month')) + offset, 1);

                                $calendar
                                        .data('view-year', nextDate.getFullYear())
                                        .data('view-month', nextDate.getMonth());
                                renderSelfDriveFallbackReturnDateCalendar($calendar);
                        })
                        .on('click.hrSelfDriveReturnCalendar', '[data-hr-calendar-date]', function () {
                                var date = parseSelfDriveFallbackCalendarDateKey($(this).attr('data-hr-calendar-date'));
                                var value;

                                if (!date || $(this).prop('disabled')) {
                                        return;
                                }

                                value = formatDateForField(date, $startInput.length ? $startInput : $input, $input);
                                $input.val(value).attr('value', value).trigger('input').trigger('change');
                                hideSelfDriveFallbackReturnDateCalendar();
                        });

                $(document).off('.hrSelfDriveReturnCalendar').on('mousedown.hrSelfDriveReturnCalendar touchstart.hrSelfDriveReturnCalendar', function (event) {
                        if ($(event.target).closest('.hr-self-drive-return-calendar, .hr-self-drive-return-date-input').length) {
                                return;
                        }

                        hideSelfDriveFallbackReturnDateCalendar();
                });

                $(window).off('.hrSelfDriveReturnCalendar').on('resize.hrSelfDriveReturnCalendar scroll.hrSelfDriveReturnCalendar', function () {
                        if (!$calendar.prop('hidden')) {
                                positionSelfDriveFallbackReturnDateCalendar($calendar, $input);
                        }
                });
        }

        function showSelfDriveReturnDatePicker($form, $returnInput, reason, event) {
                var picker = $returnInput && $returnInput.length ? $returnInput.data('hr-self-drive-easepick') : null;
                var attempts = [];
                var snapshot;

                function recordAttempt(method, callback) {
                        var attempt = {
                                method: method
                        };

                        try {
                                callback();
                                attempt.ok = true;
                        } catch (error) {
                                attempt.ok = false;
                                attempt.error = error.message || String(error);
                        }

                        attempts.push(attempt);
                        return attempt.ok;
                }

                if (!$returnInput || !$returnInput.length) {
                        return getSelfDriveDatePickerDebugSnapshot($form, $returnInput, reason, event, {
                                attempts: [{
                                        method: 'none',
                                        ok: false,
                                        error: 'missing return input'
                                }]
                        }, true);
                }

                recordAttempt('hr-fallback-calendar.show', function () {
                        showSelfDriveFallbackReturnDateCalendar($form, $returnInput);
                });

                if (!attempts[0].ok && $returnInput[0]._flatpickr) {
                        recordAttempt('flatpickr.open', function () {
                                $returnInput[0]._flatpickr.open();
                        });
                } else if (!attempts[0].ok && picker) {
                        if (typeof picker.show === 'function') {
                                recordAttempt('easepick.show', function () {
                                        picker.show();
                                });
                        } else if (picker.ui && typeof picker.ui.show === 'function') {
                                recordAttempt('easepick.ui.show', function () {
                                        picker.ui.show();
                                });
                        } else if (typeof picker.renderAll === 'function') {
                                recordAttempt('easepick.renderAll', function () {
                                        picker.renderAll();
                                });
                        } else {
                                attempts.push({
                                        method: 'easepick',
                                        ok: false,
                                        error: 'picker object has no known open method'
                                });
                        }
                } else if (!attempts[0].ok && $.fn.datetimepicker && ($returnInput.data('xdsoft_datetimepicker') || $returnInput.hasClass('xdsoft_input') || $returnInput.data('hr-self-drive-datepicker-bound') === 'jquery-datetimepicker')) {
                        recordAttempt('jquery.datetimepicker.show', function () {
                                $returnInput.datetimepicker('show');
                        });
                } else if (!attempts[0].ok && $returnInput.data('datepicker') && $.fn.datepicker) {
                        recordAttempt('jquery-ui.datepicker.show', function () {
                                $returnInput.datepicker('show');
                        });
                } else if (!attempts[0].ok && $returnInput[0].showPicker) {
                        recordAttempt('native.showPicker', function () {
                                $returnInput[0].showPicker();
                        });
                } else if (!attempts[0].ok) {
                        attempts.push({
                                method: 'none',
                                ok: false,
                                error: 'no recognized picker open method'
                        });
                }

                snapshot = getSelfDriveDatePickerDebugSnapshot($form, $returnInput, reason, event, {
                        attempts: attempts
                }, false);
                pushSelfDriveDatePickerDebugHistory($form, 'hr-self-drive-return-date-open-attempts', snapshot);
                selfDriveDebugLog('return date picker open attempt: ' + reason, snapshot);

                [80, 300].forEach(function (delay) {
                        window.setTimeout(function () {
                                var delayedSnapshot = getSelfDriveDatePickerDebugSnapshot($form, $returnInput, reason + ' after ' + delay + 'ms', event, {
                                        attempts: attempts
                                }, false);

                                pushSelfDriveDatePickerDebugHistory($form, 'hr-self-drive-return-date-open-attempts', delayedSnapshot);
                                selfDriveDebugLog('return date picker after open attempt ' + delay + 'ms', delayedSnapshot);
                        }, delay);
                });

                return snapshot;
        }

        function getSelfDriveDebugElementState($element) {
                if (!$element || !$element.length) {
                        return {
                                exists: false
                        };
                }

                return {
                        exists: true,
                        visible: $element.is(':visible'),
                        tag: $element[0].tagName ? $element[0].tagName.toLowerCase() : '',
                        name: $element.attr('name') || '',
                        id: $element.attr('id') || '',
                        classes: String($element[0].className || ''),
                        value: $element.val ? truncateMotorbikeDebugValue($element.val(), 120) : '',
                        inputType: $element.attr('type') || '',
                        readonly: $element.prop('readonly') === true,
                        disabled: $element.prop('disabled') === true,
                        hiddenAttr: $element.prop('hidden') === true,
                        displayCss: $element.css('display') || '',
                        datepickerBound: String($element.data('hr-self-drive-datepicker-bound') || ''),
                        hasFlatpickr: !!($element[0] && $element[0]._flatpickr),
                        hasEasepick: !!$element.data('hr-self-drive-easepick'),
                        hasJqueryDatepicker: !!$element.data('datepicker'),
                        hasJqueryDateTimePicker: !!($element.data('xdsoft_datetimepicker') || $element.hasClass('xdsoft_input')),
                        hasNativeShowPicker: !!($element[0] && $element[0].showPicker),
                        parentClasses: String($element.parent()[0] && $element.parent()[0].className || ''),
                        closestHiddenClasses: String($element.closest('[hidden], [aria-hidden="true"], .hr-legacy-airport-transfer-field-hidden, .ovabrw-hide-field').attr('class') || '')
                };
        }

        function getSelfDriveDebugState($form, reason) {
                var $field = $form.find('.hr-self-drive-booking-field');
                var $details = $field.find('.hr-self-drive-trip-details').first();
                var $externalDetails = $form.find('.hr-self-drive-trip-details').not($details);
                var $dateInput = findSelfDriveRentalDateInput($form);
                var raw = $field.attr('data-self-drive') || '';
                var parsedKeys = [];

                try {
                        parsedKeys = Object.keys(JSON.parse(raw || '{}'));
                } catch (error) {
                        parsedKeys = ['parse-error: ' + error.message];
                }

                return {
                        reason: reason,
                        time: new Date().toISOString(),
                        url: window.location.href,
                        scriptVersion: window.hrBooking && window.hrBooking.version ? window.hrBooking.version : '',
                        form: {
                                count: $form.length,
                                classes: String($form[0] && $form[0].className || ''),
                                enctype: $form.attr('enctype') || ''
                        },
                        selfDriveField: getSelfDriveDebugElementState($field),
                        dataSelfDriveLength: raw.length,
                        dataSelfDriveKeys: parsedKeys,
                        vehicleWrapCount: $field.find('.hr-self-drive-vehicle-selectors').length,
                        vehicleRows: $field.find('.hr-self-drive-vehicle-row').length,
                        details: {
                                countInsideField: $field.find('.hr-self-drive-trip-details').length,
                                countOutsideField: $externalDetails.length,
                                exists: $details.length > 0,
                                visible: $details.is(':visible'),
                                html: truncateMotorbikeDebugValue($details.html() || '', 600),
                                parentClasses: String($details.parent()[0] && $details.parent()[0].className || '')
                        },
                        rentalDateInput: getSelfDriveDebugElementState($dateInput),
                        returnDateInput: getSelfDriveDebugElementState(getSelfDriveReturnDateInput($form)),
                        returnDatePickerDebug: getSelfDriveDatePickerDebugSnapshot($form, getSelfDriveReturnDateInput($form), 'debug dump'),
                        guestInput: getSelfDriveDebugElementState(getSelfDriveGuestInput($form)),
                        hotelInput: getSelfDriveDebugElementState(getSelfDriveHotelNameInput($form)),
                        hiddenValues: {
                                guests: $form.find('input[name="hr_self_drive_guests"]').val() || '',
                                hotelName: $form.find('input[name="hr_self_drive_hotel_name"]').val() || '',
                                returnDate: $form.find('input[name="hr_self_drive_return_date"]').val() || ''
                        }
                };
        }

        function dumpSelfDriveDebugState($form, reason) {
                var state;

                if (!selfDriveDebugEnabled || !$form || !$form.length || !$form.find('.hr-self-drive-booking-field').length) {
                        return null;
                }

                state = getSelfDriveDebugState($form, reason);
                window.hrSelfDriveDebugLastDump = state;

                if (window.console && window.console.groupCollapsed) {
                        window.console.groupCollapsed('[HR Self Drive Debug] ' + reason);
                        window.console.log(state);
                        window.console.groupEnd();
                } else {
                        selfDriveDebugLog(reason, state);
                }

                return state;
        }

        function ensureSelfDriveReturnDateField($form) {
                var $field = $form.find('.hr-self-drive-booking-field');
                var $dateInput = findSelfDriveRentalDateInput($form);
                var $returnInput = getSelfDriveReturnDateInput($form);
                var $guestInput = getSelfDriveGuestInput($form);
                var $hotelInput = getSelfDriveHotelNameInput($form);
                var $details = $field.find('.hr-self-drive-trip-details').first();
                var $returnField;
                var $guestField;
                var $hotelField;
                var placeholder;
                var returnId;
                var guestId;

                if (!$field.length) {
                        dumpSelfDriveDebugState($form, 'ensure skipped: missing self-drive field');
                        return $returnInput;
                }

                if (!$details.length) {
                        $details = $('<div class="hr-self-drive-trip-details"></div>');
                        $details.prependTo($field);
                        selfDriveDebugLog('created missing trip details container', getSelfDriveDebugState($form, 'created trip details'));
                }

                $returnInput = adoptSelfDriveThemeReturnDateField($form, $details, $returnInput, $dateInput);

                if (!$returnInput.length) {
                        placeholder = window.hrBooking && window.hrBooking.selfDriveReturnDatePlaceholder ? window.hrBooking.selfDriveReturnDatePlaceholder : ($dateInput.length ? $dateInput.attr('placeholder') : '') || 'DD-MM-YYYY';
                        returnId = 'hr-self-drive-return-date-' + Math.floor(Math.random() * 1000000);
                        $returnField = $('<div class="rental_item hr-self-drive-return-date-field"></div>');
                        $returnField.append(
                                $('<label>', {
                                        for: returnId,
                                        text: getSelfDriveReturnDateLabel()
                                })
                        );
                        $returnInput = $('<input>', {
                                type: 'text',
                                id: returnId,
                                class: 'hr-self-drive-return-date-input checkout-date ovabrw-datepicker ovabrw-input-required',
                                name: 'hr_self_drive_return_date_display',
                                placeholder: placeholder,
                                required: true,
                                'aria-required': 'true',
                                autocomplete: 'off'
                        });
                        $returnField.append($returnInput);
                        $details.append($returnField);
                }

                if (!$guestInput.length) {
                        guestId = 'hr-self-drive-guests-' + Math.floor(Math.random() * 1000000);
                        $guestField = $('<div class="hr-self-drive-guest-row"></div>');
                        $guestField.append(
                                $('<label>', {
                                        class: 'hr-self-drive-guest-label',
                                        for: guestId,
                                        text: window.hrBooking && window.hrBooking.selfDriveGuestLabel ? window.hrBooking.selfDriveGuestLabel : 'No. of Guest'
                                })
                        );
                        $guestField.append('<button type="button" class="hr-self-drive-qty-btn hr-self-drive-guest-minus" aria-label="Decrease guests">-</button>');
                        $guestInput = $('<input>', {
                                type: 'number',
                                id: guestId,
                                class: 'hr-self-drive-guest-count ovabrw-input-required',
                                name: 'hr_self_drive_guests_display',
                                value: '1',
                                min: '1',
                                step: '1',
                                inputmode: 'numeric',
                                required: true,
                                'aria-required': 'true'
                        });
                        $guestField.append($guestInput);
                        $guestField.append('<button type="button" class="hr-self-drive-qty-btn hr-self-drive-guest-plus" aria-label="Increase guests">+</button>');
                        $details.append($guestField);
                }

                if (!$hotelInput.length) {
                        $hotelField = $('<div class="hr-self-drive-field-group hr-self-drive-hotel-name-field"></div>');
                        $hotelInput = $('<input>', {
                                type: 'text',
                                class: 'hr-self-drive-hotel-name-input ovabrw-input-required',
                                name: 'hr_self_drive_hotel_name_display',
                                placeholder: window.hrBooking && window.hrBooking.selfDriveHotelNamePlaceholder ? window.hrBooking.selfDriveHotelNamePlaceholder : 'Hotel Name',
                                required: true,
                                'aria-required': 'true',
                                autocomplete: 'off'
                        });
                        $hotelField.append($hotelInput);
                        $details.append($hotelField);
                }

                initSelfDriveReturnDatePicker($returnInput, $dateInput);

                dumpSelfDriveDebugState($form, 'after ensure trip detail fields');

                return $returnInput;
        }

        function initSelfDriveReturnDatePicker($returnInput, $dateInput) {
                var dateFormat;
                var dateTimeFormat = 'd-m-Y';
                var nativeDateSupported;

                if (!$returnInput.length) {
                        return;
                }

                $returnInput
                        .addClass('checkout-date ovabrw-datepicker')
                        .attr('autocomplete', 'off');

                if (!$returnInput.data('hr-self-drive-datepicker-bound')) {
                        if ($returnInput[0]._flatpickr) {
                                $returnInput.data('hr-self-drive-datepicker-bound', 'flatpickr-existing');
                        } else if ($returnInput.data('datepicker')) {
                                $returnInput.data('hr-self-drive-datepicker-bound', 'jquery-ui-existing');
                        } else if ($returnInput.data('xdsoft_datetimepicker') || $returnInput.hasClass('xdsoft_input')) {
                                $returnInput.data('hr-self-drive-datepicker-bound', 'jquery-datetimepicker-existing');
                        }
                }

                if ($dateInput && $dateInput.length && $dateInput.attr('readonly')) {
                        $returnInput.attr('readonly', 'readonly');
                }

                dateFormat = getDatepickerFormat($dateInput) || 'dd-mm-yy';

                if (!$returnInput.data('hr-self-drive-datepicker-bound') && window.flatpickr && !$returnInput[0]._flatpickr) {
                        try {
                                window.flatpickr($returnInput[0], {
                                        dateFormat: 'd-m-Y',
                                        allowInput: true,
                                        onChange: function () {
                                                $returnInput.trigger('change');
                                        }
                                });
                                $returnInput.data('hr-self-drive-datepicker-bound', 'flatpickr');
                        } catch (error) {
                                selfDriveDebugLog('flatpickr return date init failed', error.message || error);
                        }
                }

                if (!$returnInput.data('hr-self-drive-datepicker-bound') && window.easepick && window.easepick.create) {
                        try {
                                $returnInput.data('hr-self-drive-easepick', new window.easepick.create({
                                        element: $returnInput[0],
                                        format: 'DD-MM-YYYY',
                                        zIndex: 999999,
                                        setup: function (picker) {
                                                picker.on('select', function () {
                                                        $returnInput.trigger('change');
                                                });
                                        }
                                }));
                                $returnInput.data('hr-self-drive-datepicker-bound', 'easepick');
                        } catch (error) {
                                selfDriveDebugLog('easypick return date init failed', error.message || error);
                        }
                }

                if (!$returnInput.data('hr-self-drive-datepicker-bound') && $.fn.datetimepicker) {
                        try {
                                $returnInput.datetimepicker({
                                        timepicker: false,
                                        format: dateTimeFormat,
                                        scrollInput: false,
                                        onSelectDate: function () {
                                                $returnInput.trigger('change');
                                        },
                                        onChangeDateTime: function () {
                                                $returnInput.trigger('change');
                                        }
                                });
                                $returnInput.data('hr-self-drive-datepicker-bound', 'jquery-datetimepicker');
                        } catch (error) {
                                selfDriveDebugLog('jquery datetimepicker return date init failed', error.message || error);
                        }
                }

                if (!$returnInput.data('hr-self-drive-datepicker-bound') && $.fn.datepicker) {
                        try {
                                $returnInput.datepicker({
                                        dateFormat: dateFormat,
                                        onSelect: function () {
                                                $(this).trigger('change');
                                        }
                                });
                                $returnInput.data('hr-self-drive-datepicker-bound', 'jquery-ui');
                        } catch (error) {
                                selfDriveDebugLog('jquery datepicker return date init failed', error.message || error);
                        }
                }

                if (!$returnInput.data('hr-self-drive-datepicker-bound')) {
                        nativeDateSupported = (function () {
                                var input = document.createElement('input');
                                input.setAttribute('type', 'date');
                                return input.type === 'date';
                        })();

                        if (nativeDateSupported) {
                                $returnInput
                                        .removeAttr('readonly')
                                        .attr('type', 'date')
                                        .data('hr-self-drive-datepicker-bound', 'native-date');
                        }
                }

                installSelfDriveReturnDatePickerDebug($returnInput.closest('form'), $returnInput);

                $returnInput.off('focus.hrSelfDriveDate click.hrSelfDriveDate').on('focus.hrSelfDriveDate click.hrSelfDriveDate', function (event) {
                        showSelfDriveReturnDatePicker($returnInput.closest('form'), $returnInput, event.type, event);
                });

                selfDriveDebugLog('return date picker initialized', {
                        type: $returnInput.data('hr-self-drive-datepicker-bound') || 'none',
                        classes: String($returnInput[0].className || ''),
                        readonly: $returnInput.attr('readonly') || '',
                        inputType: $returnInput.attr('type') || ''
                });
        }

        function getSelfDriveRentalDateValue($form) {
                var $dateInput = findSelfDriveRentalDateInput($form);

                return $dateInput.length ? String($dateInput.val() || '') : '';
        }

        function getSelfDriveReturnDateValue($form) {
                var $returnInput = ensureSelfDriveReturnDateField($form);

                return $returnInput.length ? String($returnInput.val() || '') : '';
        }

        function rememberSelfDriveRentalDateValue($form) {
                var $dateInput = findSelfDriveRentalDateInput($form);
                var value;

                if ($dateInput.length) {
                        value = String($dateInput.val() || '');
                        $dateInput.data('hr-self-drive-rental-date-value', value);
                        $form.data('hr-self-drive-rental-date-value', value);
                }
        }

        function clearSelfDriveReturnDateField($form) {
                var $returnInputs = getSelfDriveReturnDateInputs($form);
                var picker;

                if (!$returnInputs.length) {
                        return;
                }

                hideSelfDriveFallbackReturnDateCalendar();

                $returnInputs.each(function () {
                        var input = this;
                        var $returnInput = $(input);

                        if (input._flatpickr && typeof input._flatpickr.clear === 'function') {
                                try {
                                        input._flatpickr.clear(false);
                                } catch (error) {
                                        selfDriveDebugLog('flatpickr return date clear failed', error.message || error);
                                }
                        }

                        if ($returnInput.data('datepicker') && $.fn.datepicker) {
                                try {
                                        $returnInput.datepicker('setDate', null);
                                } catch (error) {
                                        selfDriveDebugLog('jquery datepicker return date clear failed', error.message || error);
                                }
                        }

                        if (($returnInput.data('xdsoft_datetimepicker') || $returnInput.hasClass('xdsoft_input')) && $.fn.datetimepicker) {
                                try {
                                        $returnInput.datetimepicker('reset');
                                } catch (error) {
                                        selfDriveDebugLog('jquery datetimepicker return date clear failed', error.message || error);
                                }
                        }

                        picker = $returnInput.data('hr-self-drive-easepick');
                        if (picker && typeof picker.clear === 'function') {
                                try {
                                        picker.clear();
                                } catch (error) {
                                        selfDriveDebugLog('easepick return date clear failed', error.message || error);
                                }
                        }

                        input.value = '';
                        $returnInput.val('').attr('value', '').removeAttr('value').trigger('input').trigger('change');
                });

                $form.find('input[name="hr_self_drive_return_date"]').val('').removeAttr('value');
        }

        function scheduleSelfDriveReturnDateReset($form) {
                var now = Date.now ? Date.now() : new Date().getTime();
                var resetUntil = now + 1400;

                $form.data('hr-self-drive-return-date-reset-until', resetUntil);

                [0, 50, 150, 350, 700, 1200].forEach(function (delay) {
                        window.setTimeout(function () {
                                var currentTime = Date.now ? Date.now() : new Date().getTime();

                                if (delay > 0 && currentTime > Number($form.data('hr-self-drive-return-date-reset-until') || 0)) {
                                        return;
                                }

                                clearSelfDriveReturnDateField($form);
                        }, delay);
                });
        }

        function clearSelfDriveReturnDateIfRentalDateChanged($form) {
                var $dateInput = findSelfDriveRentalDateInput($form);
                var currentValue;
                var previousValue;

                if (!$dateInput.length) {
                        return false;
                }

                currentValue = String($dateInput.val() || '');
                previousValue = $form.data('hr-self-drive-rental-date-value');

                if (typeof previousValue === 'undefined') {
                        previousValue = $dateInput.data('hr-self-drive-rental-date-value');
                }

                if (typeof previousValue === 'undefined') {
                        $dateInput.data('hr-self-drive-rental-date-value', currentValue);
                        $form.data('hr-self-drive-rental-date-value', currentValue);
                        return false;
                }

                if (String(previousValue || '') === currentValue) {
                        return false;
                }

                $dateInput.data('hr-self-drive-rental-date-value', currentValue);
                $form.data('hr-self-drive-rental-date-value', currentValue);
                scheduleSelfDriveReturnDateReset($form);

                return true;
        }

        function installSelfDriveRentalDateWatcher($form) {
                var intervalId;
                var $dateInput = findSelfDriveRentalDateInput($form);

                if (!$form.length || $form.data('hr-self-drive-rental-date-watcher-bound')) {
                        return;
                }

                $form.data('hr-self-drive-rental-date-watcher-bound', true);
                rememberSelfDriveRentalDateValue($form);

                if ($dateInput.length && !$dateInput.data('hr-self-drive-rental-date-listeners-bound')) {
                        $dateInput.data('hr-self-drive-rental-date-listeners-bound', true);

                        ['change', 'input', 'blur', 'keyup', 'paste'].forEach(function (eventType) {
                                $dateInput[0].addEventListener(eventType, function () {
                                        window.setTimeout(function () {
                                                clearSelfDriveReturnDateIfRentalDateChanged($form);
                                        }, 0);
                                }, true);
                        });
                }

                intervalId = window.setInterval(function () {
                        var resetUntil = Number($form.data('hr-self-drive-return-date-reset-until') || 0);
                        var now = Date.now ? Date.now() : new Date().getTime();

                        if (!document.body || !$form[0] || !document.body.contains($form[0])) {
                                window.clearInterval(intervalId);
                                return;
                        }

                        clearSelfDriveReturnDateIfRentalDateChanged($form);

                        if (resetUntil && now <= resetUntil && getSelfDriveReturnDateInputs($form).filter(function () {
                                return String($(this).val() || '') !== '';
                        }).length) {
                                clearSelfDriveReturnDateField($form);
                        }
                }, 250);

                $form.data('hr-self-drive-rental-date-watcher-interval', intervalId);
        }

        function calculateSelfDriveDays($form) {
                var $startInput = findSelfDriveRentalDateInput($form);
                var $returnInput = ensureSelfDriveReturnDateField($form);
                var startDate = parseBookingDate($startInput);
                var returnDate = parseBookingDate($returnInput);
                var diffDays;

                if (!startDate || !returnDate) {
                        return 0;
                }

                diffDays = Math.round((returnDate.getTime() - startDate.getTime()) / 86400000);

                if (diffDays < 0) {
                        return 0;
                }

                return Math.max(1, diffDays);
        }

        function getSelfDriveGuestCount($form) {
                var $guestInput = getSelfDriveGuestInput($form);
                var value = parseInt($guestInput.length ? $guestInput.val() : $form.find('input[name="hr_self_drive_guests"]').val(), 10);

                if (!Number.isFinite(value) || value < 1) {
                        return 0;
                }

                return value;
        }

        function getSelfDriveHotelName($form) {
                var $hotelInput = getSelfDriveHotelNameInput($form);

                return $.trim($hotelInput.length ? $hotelInput.val() : $form.find('input[name="hr_self_drive_hotel_name"]').val() || '');
        }

        function syncSelfDriveGuestFields($form) {
                var guestCount = getSelfDriveGuestCount($form);

                if (!guestCount) {
                        return;
                }

                setSelfDriveFormValue($form, 'ovabrw_adults', String(guestCount));
                setSelfDriveFormValue($form, 'ovabrw_childrens', '0');
                setSelfDriveFormValue($form, 'ovabrw_babies', '0');
                setSelfDriveFormValue($form, 'hr_self_drive_guests', String(guestCount));
        }

        function hideSelfDriveGuestFields($form) {
                var guestCount = Math.max(getSelfDriveGuestCount($form), 1);

                $form.find(GUEST_INPUT_SELECTOR).each(function () {
                        var $input = $(this);
                        var name = String($input.attr('name') || '');
                        var value = name === 'ovabrw_adults' ? String(guestCount) : '0';

                        $input
                                .val(value)
                                .attr('value', value)
                                .prop('required', false)
                                .removeAttr('required')
                                .removeAttr('aria-required')
                                .removeClass('ovabrw-input-required error');

                        hideLegacyAirportTransferField($input);
                });

                setSelfDriveFormValue($form, 'ovabrw_adults', String(guestCount));
                setSelfDriveFormValue($form, 'ovabrw_childrens', '0');
                setSelfDriveFormValue($form, 'ovabrw_babies', '0');
        }

        function setSelfDriveFormValue($form, name, value) {
                var $fields = $form.find('[name="' + name + '"]');

                if (!$fields.length) {
                        $fields = $('<input>', {
                                type: 'hidden',
                                name: name
                        }).appendTo($form);
                }

                $fields.val(value).attr('value', value);
        }

        function hasSelfDriveValidationAttempted($form) {
                return $form.data('hr-self-drive-validation-attempted') === true;
        }

        function markSelfDriveValidationAttempted($form) {
                $form.data('hr-self-drive-validation-attempted', true);
                $form.addClass('hr-self-drive-validation-attempted');
        }

        function setSelfDriveValidationMessage($form, $target, message) {
                $target.text(hasSelfDriveValidationAttempted($form) ? (message || '') : '');
        }

        function clearSelfDriveBaseValidation($form) {
                var baseErrorPattern = /(?:^|\b)field\s+is\s+required\.?$|number\s+of\s+guests|guests?\s+is\s+required|guest.*required|total\s+must\s+be\s+greater\s+than\s+0|total.*greater\s+than\s+0/i;
                var selfDriveTotal = Number($form.find('input[name="hr_self_drive_total"]').val() || 0);
                var totalIsValid = Number.isFinite(selfDriveTotal) && selfDriveTotal > 0;

                $form.find('label, span, p, div, li').each(function () {
                        var $element = $(this);
                        var directText = getDirectText($element);
                        var fullText = $.trim($element.text()).replace(/\s+/g, ' ');
                        var isAjaxError = $element.hasClass('ajax-error');

                        if (!baseErrorPattern.test(directText) && !(isAjaxError && baseErrorPattern.test(fullText))) {
                                return;
                        }

                        if (isAjaxError) {
                                $element.empty().hide();
                                return;
                        }

                        $element.remove();
                });

                $form.find('.ajax-error').each(function () {
                        var $error = $(this);
                        var text = $.trim($error.text()).replace(/\s+/g, ' ');

                        if (baseErrorPattern.test(text)) {
                                $error.empty().hide();
                        }
                });

                if (!hasSelfDriveValidationAttempted($form) || totalIsValid) {
                        $form.find('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit')
                                .prop('disabled', false)
                                .removeAttr('disabled')
                                .removeClass('disabled loading');
                }
        }

        function neutralizeSelfDriveHiddenRequiredFields($form) {
                var $selfDriveField = $form.find('.hr-self-drive-booking-field');

                if (!$selfDriveField.length) {
                        return;
                }

                $form.find('.ovabrw-input-required, [required], [aria-required="true"]').not($selfDriveField.find('.ovabrw-input-required, [required], [aria-required="true"]')).each(function () {
                        var $control = $(this);
                        var type = String($control.attr('type') || '').toLowerCase();
                        var hidden = type === 'hidden' ||
                                !$control.is(':visible') ||
                                $control.closest('.hr-legacy-airport-transfer-field-hidden, [aria-hidden="true"], .ovabrw-wrapper-guestspicker').length > 0;

                        if (!hidden) {
                                return;
                        }

                        $control
                                .prop('required', false)
                                .removeAttr('required')
                                .removeAttr('aria-required')
                                .removeAttr('data-error')
                                .removeClass('ovabrw-input-required ovabrw-required error');

                        if ($control.is('input, textarea') && !$control.val()) {
                                $control.val(type === 'checkbox' || type === 'radio' ? 'yes' : '1').attr('value', type === 'checkbox' || type === 'radio' ? 'yes' : '1');
                        }
                });

                $form.find('.hr-legacy-airport-transfer-field-hidden .ovabrw-required, .ovabrw-wrapper-guestspicker .ovabrw-required')
                        .removeClass('ovabrw-required');
        }

        function scheduleSelfDriveBaseValidationClear($form) {
                [0, 50, 100, 250, 500, 800, 1200, 2000, 3500].forEach(function (delay) {
                        window.setTimeout(function () {
                                clearSelfDriveBaseValidation($form);
                        }, delay);
                });
        }

        function syncSelfDriveBaseBookingState($form, total, selectedCount, valid) {
                var safeTotal = Number(total);
                var safeSelectedCount;
                var totalValue;
                var dataValue;

                if (typeof valid === 'undefined') {
                        valid = selectedCount === true;
                        selectedCount = 1;
                }

                safeSelectedCount = Math.max(parseInt(selectedCount, 10) || 0, 0);
                totalValue = valid && Number.isFinite(safeTotal) && safeTotal > 0 ? String(safeTotal) : '';
                dataValue = valid && Number.isFinite(safeTotal) && safeTotal > 0 ? safeTotal : '';

                setSelfDriveFormValue($form, 'quantity', '1');
                setSelfDriveFormValue($form, 'ovabrw_quantity', String(Math.max(safeSelectedCount, 1)));
                setSelfDriveFormValue($form, 'hr_self_drive_total', totalValue);
                setSelfDriveFormValue($form, 'ovabrw_total', totalValue);
                setSelfDriveFormValue($form, 'ovabrw_total_price', totalValue);
                setSelfDriveFormValue($form, 'ovabrw_amount', totalValue);
                setSelfDriveFormValue($form, 'total_price', totalValue);

                $form.find('.ajax-show-total, .ovabrw-ajax-total')
                        .attr('data-total', totalValue)
                        .attr('data-price', totalValue)
                        .attr('data-amount', totalValue)
                        .data('total', dataValue)
                        .data('price', dataValue)
                        .data('amount', dataValue);

                $form.find('.ajax-show-total .show-total-number, .ovabrw-ajax-total .show-total-number')
                        .text(totalValue)
                        .attr('data-total', totalValue)
                        .attr('data-price', totalValue)
                        .attr('data-amount', totalValue)
                        .data('total', dataValue)
                        .data('price', dataValue)
                        .data('amount', dataValue);

                if (valid && totalValue) {
                        clearSelfDriveBaseValidation($form);
                        $form.find('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit')
                                .prop('disabled', false)
                                .removeAttr('disabled')
                                .removeClass('disabled loading');
                }
        }

        function hideLegacySelfDriveFields($form) {
                var $selfDriveField = $form.find('.hr-self-drive-booking-field');
                var vehiclePattern = /choose\s+vehicle\s+type|vehicle\s+type|self[\s-]*drive\s+vehicle/i;
                var timePattern = /pick[\s-]*up\s*\/\s*delivery\s*time|pickup\s*\/\s*delivery\s*time|pick[\s-]*up\s*time|delivery\s*time/i;
                var addressPattern = /delivery\s+address/i;
                var handoverPattern = /vehicle\s+handover|office\s*\/\s*garage\s+pick[\s-]*up|deliver\s+to\s+address|delivery\s*&\s*pick[\s-]*up/i;

                if (!$selfDriveField.length) {
                        return;
                }

                hideSelfDriveGuestFields($form);
                neutralizeSelfDriveHiddenRequiredFields($form);
                clearSelfDriveBaseValidation($form);

                $form.find('select').not($selfDriveField.find('select')).each(function () {
                        var $select = $(this);
                        var $container = getLegacyAirportTransferFieldContainer($select);
                        var optionText = $select.find('option').map(function () {
                                return $(this).text();
                        }).get().join(' ');
                        var fieldText = [
                                $select.attr('name') || '',
                                $select.attr('id') || '',
                                $select.attr('aria-label') || '',
                                optionText,
                                $container.text()
                        ].join(' ');

                        if (vehiclePattern.test(fieldText)) {
                                hideLegacyAirportTransferField($select);
                        }
                });

                $form.find('input, textarea').not($selfDriveField.find('input, textarea')).each(function () {
                        var $input = $(this);
                        var type = String($input.attr('type') || '').toLowerCase();
                        var fieldText = [
                                $input.attr('name') || '',
                                $input.attr('id') || '',
                                $input.attr('placeholder') || '',
                                $input.attr('aria-label') || '',
                                getLegacyAirportTransferFieldContainer($input).text()
                        ].join(' ');

                        if (timePattern.test(fieldText) || addressPattern.test(fieldText) || (/^(checkbox|radio)$/.test(type) && handoverPattern.test(fieldText))) {
                                hideLegacyAirportTransferField($input);
                        }
                });

                $form.find('label, h1, h2, h3, h4, h5, h6, p, span').not($selfDriveField.find('label, h1, h2, h3, h4, h5, h6, p, span')).each(function () {
                        var $element = $(this);
                        var directText = getDirectText($element);
                        var nearbyText = [
                                $element.next().text(),
                                $element.next('input, textarea, select').attr('placeholder') || '',
                                $element.parent().text()
                        ].join(' ');

                        if (/^vehicle\s+handover\s+options:?$/i.test(directText) || handoverPattern.test(directText) || vehiclePattern.test(directText)) {
                                hideLegacyAirportTransferField($element);
                                return;
                        }

                        if (/^\*$/.test(directText) && timePattern.test(nearbyText)) {
                                hideLegacyAirportTransferField($element);
                        }
                });
        }

        function scheduleSelfDriveFormSync($form) {
                [0, 100, 350, 800, 1500].forEach(function (delay) {
                        window.setTimeout(function () {
                                labelSelfDriveDateField($form);
                                ensureSelfDriveReturnDateField($form);
                                hideLegacySelfDriveFields($form);
                        }, delay);
                });
        }

        function updateSelfDriveDeliveryAddressState($field) {
                var handover = $field.find('.hr-self-drive-handover-option:checked').val();
                var $address = $field.find('.hr-self-drive-delivery-address');
                var delivery = handover === 'delivery';

                $address.prop('hidden', !delivery).prop('required', delivery).attr('aria-required', delivery ? 'true' : null);

                if (!delivery) {
                        $address.val('');
                }
        }

        function handleSelfDriveQuantityButton($button, selfDriveData) {
                var $form = $button.closest('form');
                var $row;
                var $qty;
                var current;
                var stock;

                if (!$form.length) {
                        return;
                }

                if ($button.hasClass('hr-self-drive-guest-plus') || $button.hasClass('hr-self-drive-guest-minus')) {
                        $qty = getSelfDriveGuestInput($form);
                        current = parseInt($qty.val(), 10) || 1;

                        if ($button.hasClass('hr-self-drive-guest-plus')) {
                                current += 1;
                        } else {
                                current -= 1;
                        }

                        current = Math.max(1, Math.min(current, 99));
                        $qty.val(String(current)).trigger('change');
                } else {
                        $row = $button.closest('.hr-self-drive-vehicle-row');
                        $qty = $row.find('.hr-self-drive-vehicle-qty');
                        current = parseInt($qty.val(), 10) || 0;
                        stock = parseInt($row.data('stock'), 10) || 0;

                        if ($button.hasClass('hr-self-drive-qty-plus')) {
                                current += 1;
                        } else {
                                current -= 1;
                        }

                        current = Math.max(0, Math.min(current, stock));
                        $qty.val(String(current)).trigger('change');
                }

                if (!selfDriveData) {
                        selfDriveData = getSelfDriveData($form);
                }

                if (selfDriveData) {
                        updateSelfDriveSelection($form, selfDriveData);
                        scheduleSelfDriveAjaxTotalSync($form);
                }
        }

        function installSelfDriveQuantityHandlers() {
                if ($(document).data('hr-self-drive-quantity-handlers-bound')) {
                        return;
                }

                $(document).data('hr-self-drive-quantity-handlers-bound', true);
                $(document).on('click.hrSelfDriveQuantity', '.hr-self-drive-qty-plus, .hr-self-drive-qty-minus, .hr-self-drive-guest-plus, .hr-self-drive-guest-minus', function (event) {
                        event.preventDefault();
                        event.stopPropagation();
                        handleSelfDriveQuantityButton($(this));
                });
        }

        function updateSelfDriveSelection($form, selfDriveData) {
                var $field = $form.find('.hr-self-drive-booking-field');
                var $total = $field.find('.hr-self-drive-booking-total');
                var payload = { vehicles: {} };
                var rentalDate = getSelfDriveRentalDateValue($form);
                var returnDate = getSelfDriveReturnDateValue($form);
                var days = calculateSelfDriveDays($form);
                var guestCount = getSelfDriveGuestCount($form);
                var hotelName = getSelfDriveHotelName($form);
                var startTime = $field.find('.hr-self-drive-start-time-select').val();
                var returnTime = $field.find('.hr-self-drive-return-time-select').val();
                var handover = $field.find('.hr-self-drive-handover-option:checked').val() || '';
                var deliveryAddress = $.trim($field.find('.hr-self-drive-delivery-address').val() || '');
                var deliveryFee = Number(selfDriveData && selfDriveData.deliveryFee ? selfDriveData.deliveryFee : 0);
                var selectedCount = 0;
                var total = 0;
                var invalid = false;

                if (!selfDriveData || !$field.length) {
                        return false;
                }

                updateSelfDriveDeliveryAddressState($field);
                syncSelfDriveGuestFields($form);

                $field.find('.hr-self-drive-vehicle-row').each(function () {
                        var $row = $(this);
                        var index = String($row.data('vehicle-index'));
                        var rate = Number($row.data('rate') || 0);
                        var stock = parseInt($row.data('stock'), 10) || 0;
                        var qty = parseInt($row.find('.hr-self-drive-vehicle-qty').val(), 10) || 0;

                        qty = Math.max(0, Math.min(qty, stock));
                        $row.find('.hr-self-drive-vehicle-qty').val(String(qty));

                        if (!qty) {
                                return;
                        }

                        selectedCount += qty;
                        payload.vehicles[index] = qty;

                        if (!days || !Number.isFinite(rate)) {
                                invalid = true;
                                return;
                        }

                        total += rate * qty * days;
                });

                if (!selectedCount) {
                        $field.find('input[name="hr_self_drive_selection_payload"]').val('');
                        $field.find('input[name="hr_self_drive_total"]').val('');
                        setSelfDriveValidationMessage($form, $total, window.hrBooking ? window.hrBooking.selfDriveChooseVehicle : '');
                        $form.data('hr-self-drive-total', 0).data('hr-self-drive-total-valid', false);
                        syncSelfDriveBaseBookingState($form, 0, selectedCount, false);
                        syncSelfDriveAjaxTotal($form);
                        return false;
                }

                if (!rentalDate) {
                        $field.find('input[name="hr_self_drive_selection_payload"]').val('');
                        $field.find('input[name="hr_self_drive_total"]').val('');
                        setSelfDriveValidationMessage($form, $total, window.hrBooking ? window.hrBooking.selfDriveChooseRentalDate : '');
                        $form.data('hr-self-drive-total', 0).data('hr-self-drive-total-valid', false);
                        syncSelfDriveBaseBookingState($form, 0, selectedCount, false);
                        syncSelfDriveAjaxTotal($form);
                        return false;
                }

                if (!returnDate || !days) {
                        $field.find('input[name="hr_self_drive_selection_payload"]').val('');
                        $field.find('input[name="hr_self_drive_total"]').val('');
                        setSelfDriveValidationMessage($form, $total, window.hrBooking ? window.hrBooking.selfDriveChooseReturnDate : '');
                        $form.data('hr-self-drive-total', 0).data('hr-self-drive-total-valid', false);
                        syncSelfDriveBaseBookingState($form, 0, selectedCount, false);
                        syncSelfDriveAjaxTotal($form);
                        return false;
                }

                if (!guestCount) {
                        $field.find('input[name="hr_self_drive_selection_payload"]').val('');
                        $field.find('input[name="hr_self_drive_total"]').val('');
                        setSelfDriveValidationMessage($form, $total, window.hrBooking ? window.hrBooking.selfDriveChooseGuests : '');
                        $form.data('hr-self-drive-total', 0).data('hr-self-drive-total-valid', false);
                        syncSelfDriveBaseBookingState($form, 0, selectedCount, false);
                        syncSelfDriveAjaxTotal($form);
                        return false;
                }

                if (!hotelName) {
                        $field.find('input[name="hr_self_drive_selection_payload"]').val('');
                        $field.find('input[name="hr_self_drive_total"]').val('');
                        setSelfDriveValidationMessage($form, $total, window.hrBooking ? window.hrBooking.selfDriveChooseHotelName : '');
                        $form.data('hr-self-drive-total', 0).data('hr-self-drive-total-valid', false);
                        syncSelfDriveBaseBookingState($form, 0, selectedCount, false);
                        syncSelfDriveAjaxTotal($form);
                        return false;
                }

                if (!startTime) {
                        $field.find('input[name="hr_self_drive_selection_payload"]').val('');
                        $field.find('input[name="hr_self_drive_total"]').val('');
                        setSelfDriveValidationMessage($form, $total, window.hrBooking ? window.hrBooking.selfDriveChooseStartTime : '');
                        $form.data('hr-self-drive-total', 0).data('hr-self-drive-total-valid', false);
                        syncSelfDriveBaseBookingState($form, 0, selectedCount, false);
                        syncSelfDriveAjaxTotal($form);
                        return false;
                }

                if (!returnTime) {
                        $field.find('input[name="hr_self_drive_selection_payload"]').val('');
                        $field.find('input[name="hr_self_drive_total"]').val('');
                        setSelfDriveValidationMessage($form, $total, window.hrBooking ? window.hrBooking.selfDriveChooseReturnTime : '');
                        $form.data('hr-self-drive-total', 0).data('hr-self-drive-total-valid', false);
                        syncSelfDriveBaseBookingState($form, 0, selectedCount, false);
                        syncSelfDriveAjaxTotal($form);
                        return false;
                }

                if (!handover || (handover === 'delivery' && !deliveryAddress) || invalid) {
                        $field.find('input[name="hr_self_drive_selection_payload"]').val('');
                        $field.find('input[name="hr_self_drive_total"]').val('');
                        setSelfDriveValidationMessage($form, $total, window.hrBooking ? window.hrBooking.selfDriveInvalidLabel : '');
                        $form.data('hr-self-drive-total', 0).data('hr-self-drive-total-valid', false);
                        syncSelfDriveBaseBookingState($form, 0, selectedCount, false);
                        syncSelfDriveAjaxTotal($form);
                        return false;
                }

                if (handover === 'delivery' && Number.isFinite(deliveryFee)) {
                        total += deliveryFee;
                }

                $field.find('input[name="hr_self_drive_selection_payload"]').val(JSON.stringify(payload));
                $field.find('input[name="hr_self_drive_rental_date"]').val(rentalDate);
                $field.find('input[name="hr_self_drive_return_date"]').val(returnDate);
                $field.find('input[name="hr_self_drive_guests"]').val(String(guestCount));
                $field.find('input[name="hr_self_drive_hotel_name"]').val(hotelName);
                $field.find('input[name="hr_self_drive_days"]').val(String(days));
                $field.find('input[name="hr_self_drive_start_time"]').val(startTime);
                $field.find('input[name="hr_self_drive_return_time"]').val(returnTime);
                $field.find('input[name="hr_self_drive_handover_option"]').val(handover);
                $field.find('input[name="hr_self_drive_delivery_address"]').val(handover === 'delivery' ? deliveryAddress : '');
                $field.find('input[name="hr_self_drive_total"]').val(Number.isFinite(total) ? String(total) : '');
                $total.text(Number.isFinite(total) ? (window.hrBooking ? window.hrBooking.selfDriveTotalLabel : 'Self-drive rental total: %s').replace('%s', formatPrice(total)) : '');
                $form.data('hr-self-drive-total', total).data('hr-self-drive-total-valid', Number.isFinite(total));
                syncSelfDriveBaseBookingState($form, total, selectedCount, Number.isFinite(total) && total > 0);
                syncSelfDriveAjaxTotal($form);
                return true;
        }

        function prepareSelfDriveValidationState($form, selfDriveData) {
                var valid;

                markSelfDriveValidationAttempted($form);
                ensureSelfDriveReturnDateField($form);
                hideLegacySelfDriveFields($form);
                valid = updateSelfDriveSelection($form, selfDriveData);
                syncSelfDriveAjaxTotal($form);
                scheduleSelfDriveBaseValidationClear($form);
                dumpSelfDriveDebugState($form, 'after prepare validation state');

                return valid;
        }

        function stopSelfDriveInvalidSubmit(event) {
                if (!event) {
                        return;
                }

                if (event.preventDefault) {
                        event.preventDefault();
                }

                if (event.stopImmediatePropagation) {
                        event.stopImmediatePropagation();
                } else if (event.stopPropagation) {
                        event.stopPropagation();
                }
        }

        function handleSelfDriveValidationAttempt($form, selfDriveData, event) {
                var valid = prepareSelfDriveValidationState($form, selfDriveData);

                if (!valid) {
                        stopSelfDriveInvalidSubmit(event);
                }

                return valid;
        }

        function installSelfDriveDebugObserver($form) {
                var scheduled = false;

                if (!selfDriveDebugEnabled || !$form.length || !$form[0] || $form.data('hr-self-drive-debug-observer-bound') || !window.MutationObserver) {
                        return;
                }

                $form.data('hr-self-drive-debug-observer-bound', true);

                new window.MutationObserver(function (mutations) {
                        var relevant = false;

                        mutations.forEach(function (mutation) {
                                if (relevant) {
                                        return;
                                }

                                if ($(mutation.target).closest('.hr-self-drive-booking-field, .hr-self-drive-trip-details').length) {
                                        relevant = true;
                                        return;
                                }

                                $(mutation.addedNodes || []).add(mutation.removedNodes || []).each(function () {
                                        if ($(this).is('.hr-self-drive-trip-details, .hr-self-drive-return-date-input, .hr-self-drive-guest-count, .hr-self-drive-hotel-name-input') || $(this).find('.hr-self-drive-trip-details, .hr-self-drive-return-date-input, .hr-self-drive-guest-count, .hr-self-drive-hotel-name-input').length) {
                                                relevant = true;
                                                return false;
                                        }
                                });
                        });

                        if (!relevant || scheduled) {
                                return;
                        }

                        scheduled = true;
                        window.setTimeout(function () {
                                scheduled = false;
                                dumpSelfDriveDebugState($form, 'debug observer noticed self-drive DOM mutation');
                        }, 50);
                }).observe($form[0], {
                        childList: true,
                        subtree: true,
                        attributes: true,
                        attributeFilter: ['class', 'style', 'hidden', 'aria-hidden']
                });
        }

        function installSelfDriveValidationObserver($form) {
                var scheduled = false;

                if (!$form.length || !$form[0] || $form.data('hr-self-drive-validation-observer-bound') || !window.MutationObserver) {
                        return;
                }

                $form.data('hr-self-drive-validation-observer-bound', true);

                new window.MutationObserver(function () {
                        if (scheduled) {
                                return;
                        }

                        scheduled = true;
                        window.setTimeout(function () {
                                var total = Number($form.find('input[name="hr_self_drive_total"]').val() || $form.data('hr-self-drive-total') || 0);
                                var selectedCount = $form.find('.hr-self-drive-vehicle-qty').map(function () {
                                        return parseInt($(this).val(), 10) || 0;
                                }).get().reduce(function (sum, qty) {
                                        return sum + qty;
                                }, 0);

                                scheduled = false;

                                clearSelfDriveBaseValidation($form);

                                if (!Number.isFinite(total) || total <= 0) {
                                        return;
                                }

                                syncSelfDriveBaseBookingState($form, total, selectedCount, true);
                                syncSelfDriveAjaxTotal($form);
                        }, 50);
                }).observe($form[0], {
                        childList: true,
                        subtree: true,
                        characterData: true,
                        attributes: true,
                        attributeFilter: ['class', 'disabled', 'style']
                });
        }

        function scheduleLegacyAirportTransferFieldHide($form) {
                [0, 100, 350, 800, 1500].forEach(function (delay) {
                        window.setTimeout(function () {
                                hideLegacyAirportTransferFields($form);
                        }, delay);
                });
        }

        function getLegacyAirportTransferFieldContainer($element) {
                var $container = $element.closest('.rental_item, .ovabrw-form-field, .ovabrw_custom_ckf, .ova-form-field, .form-group, p');

                if ($container.length && !$container.is('form')) {
                        return $container;
                }

                return $element;
        }

        function hideLegacyAirportTransferField($element) {
                var $container = getLegacyAirportTransferFieldContainer($element);
                var $controls = $container.find('input, select, textarea');

                if ($element.is('input, select, textarea')) {
                        $controls = $controls.add($element);
                }

                $controls.each(function () {
                        $(this)
                                .prop('required', false)
                                .removeAttr('required')
                                .removeClass('ovabrw-input-required');
                });

                $container
                        .addClass('hr-legacy-airport-transfer-field-hidden')
                        .attr('aria-hidden', 'true')
                        .hide();
        }

        function getDirectText($element) {
                return $.trim(
                        $element
                                .clone()
                                .children()
                                .remove()
                                .end()
                        .text()
                ).replace(/\s+/g, ' ');
        }

        function labelArrivalTimeField($form) {
                $form.find('input, textarea').each(function () {
                        var $input = $(this);
                        var type = String($input.attr('type') || '').toLowerCase();
                        var $container;
                        var $labelTarget;
                        var $nativeLabel;
                        var $displayLabel;
                        var fieldText;
                        var fieldId;
                        var labelText = window.hrBooking && window.hrBooking.arrivalTimeLabel ? window.hrBooking.arrivalTimeLabel : 'Arrival Time:';
                        var isCandidate;

                        if (type === 'hidden') {
                                return;
                        }

                        $container = getLegacyAirportTransferFieldContainer($input);
                        if ($container.is('input, textarea')) {
                                $container = $input.parent();
                        }

                        fieldText = [
                                $input.attr('name') || '',
                                $input.attr('id') || '',
                                $input.attr('placeholder') || '',
                                $input.attr('aria-label') || '',
                                $container.text()
                        ].join(' ');
                        isCandidate = /arrival\s*time/i.test(fieldText) || $input.hasClass('hr-arrival-time-input') || $container.hasClass('hr-arrival-time-labeled');

                        if (!isCandidate) {
                                return;
                        }

                        fieldId = $input.attr('id');
                        if (!fieldId) {
                                fieldId = 'hr-arrival-time-' + Math.floor(Math.random() * 1000000);
                                $input.attr('id', fieldId);
                        }

                        $input.addClass('hr-arrival-time-input');
                        $input.removeClass('hide-label-select');
                        $input.attr('aria-label', labelText.replace(/:$/, ''));
                        if (/arrival\s*time/i.test(String($input.attr('placeholder') || ''))) {
                                $input.attr('placeholder', '');
                        }

                        $container.addClass('hr-arrival-time-labeled');
                        $nativeLabel = $container.find('label').filter(function () {
                                return $(this).attr('for') === fieldId;
                        }).first();
                        if (!$nativeLabel.length) {
                                $nativeLabel = $container.children('label').first();
                        }

                        if ($nativeLabel.length) {
                                $container.find('.hr-arrival-time-label').not($nativeLabel).remove();
                                $nativeLabel
                                        .text(labelText)
                                        .addClass('hr-arrival-time-label hr-arrival-time-native-label')
                                        .attr('for', fieldId)
                                        .css({
                                                display: 'block',
                                                visibility: 'visible',
                                                opacity: 1,
                                                height: 'auto',
                                                width: 'auto',
                                                margin: '0 0 8px'
                                        });
                                $nativeLabel.each(function () {
                                        this.style.setProperty('display', 'block', 'important');
                                        this.style.setProperty('visibility', 'visible', 'important');
                                        this.style.setProperty('opacity', '1', 'important');
                                        this.style.setProperty('height', 'auto', 'important');
                                        this.style.setProperty('width', 'auto', 'important');
                                        this.style.setProperty('margin', '0 0 8px', 'important');
                                });
                                return;
                        }

                        $input.prev('.hr-arrival-time-label').remove();

                        $labelTarget = $input.parent('label').length ? $input.parent('label') : $input;
                        $displayLabel = $('<label>', {
                                class: 'hr-arrival-time-label',
                                for: fieldId,
                                text: labelText
                        });

                        $displayLabel.insertBefore($labelTarget).css({
                                display: 'block',
                                visibility: 'visible',
                                opacity: 1,
                                height: 'auto',
                                width: 'auto',
                                margin: '0 0 8px'
                        });
                        $displayLabel.each(function () {
                                this.style.setProperty('display', 'block', 'important');
                                this.style.setProperty('visibility', 'visible', 'important');
                                this.style.setProperty('opacity', '1', 'important');
                                this.style.setProperty('height', 'auto', 'important');
                                this.style.setProperty('width', 'auto', 'important');
                                this.style.setProperty('margin', '0 0 8px', 'important');
                        });
                });
        }

        function syncArrivalTimeCustomFieldConfig($form) {
                var labelText = window.hrBooking && window.hrBooking.arrivalTimeLabel ? window.hrBooking.arrivalTimeLabel : 'Arrival Time:';
                var labelValue = labelText.replace(/:$/, '');

                $form.find('input[name="data_custom_ckf"]').each(function () {
                        var $input = $(this);
                        var raw = $input.val();
                        var config;
                        var changed = false;

                        if (!raw) {
                                return;
                        }

                        try {
                                config = JSON.parse(raw);
                        } catch (error) {
                                return;
                        }

                        if (!config || typeof config !== 'object') {
                                return;
                        }

                        Object.keys(config).forEach(function (key) {
                                var field = config[key];
                                var fieldText;

                                if (!field || typeof field !== 'object') {
                                        return;
                                }

                                fieldText = [
                                        key,
                                        field.name || '',
                                        field.label || '',
                                        field.placeholder || ''
                                ].join(' ');

                                if (!/arrival\s*time/i.test(fieldText)) {
                                        return;
                                }

                                field.label = labelValue;
                                if (/arrival\s*time/i.test(String(field.placeholder || ''))) {
                                        field.placeholder = '';
                                }
                                if (field.class) {
                                        field.class = String(field.class)
                                                .split(/\s+/)
                                                .filter(function (className) {
                                                        return className && className !== 'hide-label-select';
                                                })
                                                .join(' ');
                                }
                                changed = true;
                        });

                        if (changed) {
                                raw = JSON.stringify(config);
                                $input.val(raw).attr('value', raw);
                        }
                });
        }

        function scheduleArrivalTimeLabelSync($form) {
                [0, 100, 350, 800, 1500].forEach(function (delay) {
                        window.setTimeout(function () {
                                labelArrivalTimeField($form);
                                syncArrivalTimeCustomFieldConfig($form);
                        }, delay);
                });
        }

        function hideLegacyAirportTransferFields($form) {
                var $airportField = $form.find('.hr-airport-transfer-booking-field');

                if (!$airportField.length) {
                        return;
                }

                labelArrivalTimeField($form);
                syncArrivalTimeCustomFieldConfig($form);

                $form.find('select').not('.hr-airport-transfer-route-select').not($airportField.find('select')).each(function () {
                        var $select = $(this);
                        var $container = getLegacyAirportTransferFieldContainer($select);
                        var optionText = $select.find('option').map(function () {
                                return $(this).text();
                        }).get().join(' ');
                        var fieldText = [
                                $select.attr('name') || '',
                                $select.attr('id') || '',
                                $select.attr('aria-label') || '',
                                optionText,
                                $container.text()
                        ].join(' ');

                        if (/to\s*\/\s*from\s+.*airport/i.test(fieldText)) {
                                hideLegacyAirportTransferField($select);
                        }
                });

                $form.find('input, textarea').not($airportField.find('input, textarea')).each(function () {
                        var $input = $(this);
                        var fieldText = [
                                $input.attr('name') || '',
                                $input.attr('id') || '',
                                $input.attr('placeholder') || '',
                                $input.attr('aria-label') || '',
                                getLegacyAirportTransferFieldContainer($input).text()
                        ].join(' ');

                        if (/drop[\s-]*off/i.test(fieldText) || /name\s+of\s+hotel\s*\/\s*resort/i.test(fieldText)) {
                                hideLegacyAirportTransferField($input);
                        }
                });

                $form.find('label, h1, h2, h3, h4, h5, h6, p').not($airportField.find('label, h1, h2, h3, h4, h5, h6, p')).each(function () {
                        var $element = $(this);

                        if (/^drop[\s-]*off\s+information:?$/i.test(getDirectText($element))) {
                                hideLegacyAirportTransferField($element);
                        }
                });
        }

        function renderAirportTransferSelectors($form, transferData) {
                var $field = $form.find('.hr-airport-transfer-booking-field');
                var $wrap = $field.find('.hr-airport-transfer-route-selectors');
                var routes = transferData && $.isArray(transferData.transferRoutes) ? transferData.transferRoutes : [];
                var placeholder = transferData && transferData.tourName ? 'To/From ' + transferData.tourName + ':' : 'Select a From/To place';
                var directionPlaceholder = window.hrBooking ? window.hrBooking.airportTransferDirectionPlaceholder : 'Select transfer direction:';
                var airportToPlaceLabel = window.hrBooking ? window.hrBooking.airportTransferDirectionAirportToPlace : 'Airport to From/To Place';
                var placeToAirportLabel = window.hrBooking ? window.hrBooking.airportTransferDirectionPlaceToAirport : 'From/To Place to Airport';
                var html = '';

                if (!$wrap.length) {
                        return;
                }

                html += '<div class="hr-airport-transfer-direction-group">';
                html += '<select class="hr-airport-transfer-direction-select ovabrw-input-required" required aria-required="true">';
                html += '<option value="">' + escapeHtml(directionPlaceholder) + '</option>';
                html += '<option value="airport_to_place">' + escapeHtml(airportToPlaceLabel) + '</option>';
                html += '<option value="place_to_airport">' + escapeHtml(placeToAirportLabel) + '</option>';
                html += '</select>';
                html += '</div>';

                html += '<div class="hr-airport-transfer-route-group">';
                html += '<select class="hr-airport-transfer-route-select ovabrw-input-required" required aria-required="true">';
                html += '<option value="">' + escapeHtml(placeholder) + '</option>';

                routes.forEach(function (route, index) {
                        html += '<option value="' + index + '">' + escapeHtml(route.label || 'From/To place') + '</option>';
                });

                html += '</select>';
                html += '</div>';

                $wrap.html(html);
        }

        function getAirportTransferBookingRate(route, guestCount) {
                var prices;
                var price;
                var pax;

                if (!route) {
                        return null;
                }

                if (route.rate !== null && typeof route.rate !== 'undefined' && route.rate !== '') {
                        return Number(route.rate);
                }

                if (route.price !== null && typeof route.price !== 'undefined' && route.price !== '') {
                        return Number(route.price);
                }

                if (route.unitPrice !== null && typeof route.unitPrice !== 'undefined' && route.unitPrice !== '') {
                        return Number(route.unitPrice);
                }

                if (route.unit_price !== null && typeof route.unit_price !== 'undefined' && route.unit_price !== '') {
                        return Number(route.unit_price);
                }

                prices = route.prices || {};

                if (guestCount && prices[String(guestCount)] !== null && typeof prices[String(guestCount)] !== 'undefined' && prices[String(guestCount)] !== '') {
                        return Number(prices[String(guestCount)]) * guestCount;
                }

                for (pax in prices) {
                        if (!Object.prototype.hasOwnProperty.call(prices, pax)) {
                                continue;
                        }

                        price = prices[pax];
                        if (price === null || typeof price === 'undefined' || price === '') {
                                continue;
                        }

                        return Number(price) * Math.max(parseInt(pax, 10) || 1, 1);
                }

                return null;
        }

        function updateAirportTransferSelection($form, transferData) {
                var $field = $form.find('.hr-airport-transfer-booking-field');
                var $total = $field.find('.hr-airport-transfer-booking-total');
                var guestCount = getGuestCount($form);
                var routeIndex = $field.find('.hr-airport-transfer-route-select').val();
                var direction = $field.find('.hr-airport-transfer-direction-select').val();
                var routes = transferData && $.isArray(transferData.transferRoutes) ? transferData.transferRoutes : [];
                var route = routeIndex !== '' ? routes[Number(routeIndex)] : null;
                var bookingRate = getAirportTransferBookingRate(route, guestCount);
                var total = 0;

                if (!transferData || !$field.length) {
                        return false;
                }

                $field.find('.hr-airport-transfer-route-select, .hr-airport-transfer-direction-select').prop('disabled', false);

                if (!direction) {
                        $field.find('input[name="hr_airport_transfer_route_index"]').val('');
                        $field.find('input[name="hr_airport_transfer_direction"]').val('');
                        $field.find('input[name="hr_airport_transfer_pax"]').val('');
                        $field.find('input[name="hr_airport_transfer_unit_price"]').val('');
                        $field.find('input[name="hr_airport_transfer_total"]').val('');
                        $total.text(window.hrBooking ? window.hrBooking.airportTransferChooseDirection : '');
                        $form.data('hr-airport-transfer-total', 0);
                        $form.data('hr-airport-transfer-total-valid', false);
                        syncAirportTransferAjaxTotal($form);
                        return false;
                }

                if (!route) {
                        $field.find('input[name="hr_airport_transfer_route_index"]').val('');
                        $field.find('input[name="hr_airport_transfer_direction"]').val('');
                        $field.find('input[name="hr_airport_transfer_pax"]').val('');
                        $field.find('input[name="hr_airport_transfer_unit_price"]').val('');
                        $field.find('input[name="hr_airport_transfer_total"]').val('');
                        $total.text(window.hrBooking ? window.hrBooking.airportTransferChooseRoute : '');
                        $form.data('hr-airport-transfer-total', 0);
                        $form.data('hr-airport-transfer-total-valid', false);
                        syncAirportTransferAjaxTotal($form);
                        return false;
                }

                if (bookingRate === null || Number.isNaN(Number(bookingRate))) {
                        $field.find('input[name="hr_airport_transfer_route_index"]').val('');
                        $field.find('input[name="hr_airport_transfer_direction"]').val('');
                        $field.find('input[name="hr_airport_transfer_pax"]').val('');
                        $field.find('input[name="hr_airport_transfer_unit_price"]').val('');
                        $field.find('input[name="hr_airport_transfer_total"]').val('');
                        $total.text(window.hrBooking ? window.hrBooking.airportTransferInvalidLabel : '');
                        $form.data('hr-airport-transfer-total', 0);
                        $form.data('hr-airport-transfer-total-valid', false);
                        syncAirportTransferAjaxTotal($form);
                        return false;
                }

                bookingRate = Number(bookingRate);
                total = bookingRate;

                $field.find('input[name="hr_airport_transfer_route_index"]').val(String(routeIndex));
                $field.find('input[name="hr_airport_transfer_direction"]').val(direction);
                $field.find('input[name="hr_airport_transfer_pax"]').val(guestCount ? String(guestCount) : '');
                $field.find('input[name="hr_airport_transfer_unit_price"]').val(String(bookingRate));
                $field.find('input[name="hr_airport_transfer_total"]').val(Number.isFinite(total) ? String(total) : '');
                $total.text(Number.isFinite(total) ? (window.hrBooking ? window.hrBooking.airportTransferTotalLabel : 'Airport transfer total: %s').replace('%s', formatPrice(total)) : '');
                $form.data('hr-airport-transfer-total', total);
                $form.data('hr-airport-transfer-total-valid', Number.isFinite(total));
                syncAirportTransferAjaxTotal($form);
                return true;
        }

        function renderTourAddonSelectors($form, tourData) {
                var $field = $form.find('.hr-tour-booking-field');
                var $wrap = $field.find('.hr-tour-addon-selectors');
                var groups = tourData && $.isArray(tourData.optionalAddons) ? tourData.optionalAddons : [];
                var html = '';

                if (!$wrap.length) {
                        return;
                }

                groups = groups.filter(function (group) {
                        return group && $.isArray(group.options) && group.options.length;
                });

                if (!groups.length) {
                        $wrap.empty().prop('hidden', true);
                        return;
                }

                html += '<div class="hr-tour-addon-title">' + escapeHtml(window.hrBooking ? window.hrBooking.tourAddonsLabel : 'Optional add-ons:') + '</div>';

                groups.forEach(function (group) {
                        html += '<div class="hr-tour-addon-group">';
                        html += '<select class="hr-tour-addon-select" data-group-key="' + escapeHtml(group.key) + '">';
                        html += '<option value="">' + escapeHtml(group.label) + '</option>';

                        group.options.forEach(function (option, index) {
                                var rate = Number(option && option.rate);
                                var label = option && option.label ? option.label : '';
                                var text = label;

                                if (Number.isFinite(rate)) {
                                        text += ' = ' + formatPrice(rate);
                                }

                                html += '<option value="' + index + '" data-rate="' + escapeHtml(rate) + '">' + escapeHtml(text) + '</option>';
                        });

                        html += '</select>';
                        html += '</div>';
                });

                $wrap.html(html).prop('hidden', false);
        }

        function getSelectedTourAddons($field) {
                var payload = {};
                var total = 0;

                $field.find('.hr-tour-addon-select').each(function () {
                        var $select = $(this);
                        var groupKey = String($select.data('group-key') || '');
                        var value = $select.val();
                        var $option;
                        var rate;

                        if (!groupKey || value === '') {
                                return;
                        }

                        $option = $select.find('option:selected');
                        rate = Number($option.data('rate'));

                        if (Number.isFinite(rate)) {
                                total += rate;
                        }

                        payload[groupKey] = Number(value);
                });

                return {
                        payload: payload,
                        total: total
                };
        }

        function getTourAdultGuestInput($form) {
                var $inputs = $form.find('input[name="ovabrw_adults"]').not('[data-hr-tour-zero-guest="1"]');
                var $rowInput = $inputs.filter(function () {
                        var $input = $(this);

                        return $input.closest('.guests-buttons').length && !$input.closest('.hr-tour-child-guest-row, .hr-tour-infant-guest-row').length;
                }).first();

                return $rowInput.length ? $rowInput : $inputs.first();
        }

        function getTourPaidGuestCount($form) {
                var value = parseInt(getTourAdultGuestInput($form).val(), 10);

                return Number.isNaN(value) ? 0 : Math.max(0, value);
        }

        function isDayTourData(tourData) {
                return !!tourData && tourData.type === 'Day Tour';
        }

        function isJoinerTourData(tourData) {
                return !!tourData && tourData.type === 'Joiner';
        }

        function isChildRateTourData(tourData) {
                return isDayTourData(tourData) || isJoinerTourData(tourData);
        }

        function getTourChildrenLabel($form, tourData) {
                var tourDataForForm = getTourDataForForm($form, tourData);
                var childrenRates = getTourChildrenRates(tourDataForForm);

                if (isChildRateTourData(tourDataForForm) && childrenRates.label) {
                        return childrenRates.label;
                }

                return window.hrBooking && window.hrBooking.tourChildrenLabel ? window.hrBooking.tourChildrenLabel : 'Children';
        }

        function getTourInfantLabel() {
                return window.hrBooking && window.hrBooking.tourInfantLabel ? window.hrBooking.tourInfantLabel : '0-2 Infant';
        }

        function getTourDataForForm($form, tourData) {
                return tourData || $form.data('hr-tour-data') || null;
        }

        function getTourChildrenRates(tourData) {
                tourData = tourData || {};

                if (!tourData.childrenRates || typeof tourData.childrenRates !== 'object') {
                        return {
                                enabled: false,
                                prices: {}
                        };
                }

                return {
                        enabled: !!tourData.childrenRates.enabled,
                        label: tourData.childrenRates.label ? String(tourData.childrenRates.label) : '',
                        discountPercent: typeof tourData.childrenRates.discount_percent !== 'undefined' ? Number(tourData.childrenRates.discount_percent) : (typeof tourData.childrenRates.discountPercent !== 'undefined' ? Number(tourData.childrenRates.discountPercent) : 0),
                        prices: tourData.childrenRates.prices && typeof tourData.childrenRates.prices === 'object' ? tourData.childrenRates.prices : {}
                };
        }

        function isTourChildrenEnabled($form, tourData) {
                tourData = getTourDataForForm($form, tourData);

                return isChildRateTourData(tourData) && getTourChildrenRates(tourData).enabled;
        }

        function syncTourGuestTotalDisplay($form, tourData) {
                var paidGuests = getTourPaidGuestCount($form);
                var children = isTourChildrenEnabled($form, tourData) ? getTourChildCount($form) : 0;
                var infants = getTourInfantCount($form);
                var total = paidGuests + children + infants;

                if (total > 0) {
                        $form.find('.ovabrw-wrapper-guestspicker .gueststotal').first().text(total);
                }
        }

        function getTourFreeGuestDisplayCount($form, displaySelector, hiddenName, pricedName) {
                var $displayInput = $form.find(displaySelector).first();
                var $pricedInput = $form.find('input[name="' + pricedName + '"]').not('[data-hr-tour-zero-guest="1"]').first();
                var $hiddenInput = $form.find('.hr-tour-booking-field input[name="' + hiddenName + '"]').first();
                var value = NaN;

                if ($displayInput.length) {
                        value = parseInt($displayInput.val(), 10);
                } else if ($hiddenInput.length) {
                        value = parseInt($hiddenInput.val(), 10);

                        if (Number.isNaN(value) && $pricedInput.length) {
                                value = parseInt($pricedInput.val(), 10);
                        }
                } else if ($pricedInput.length) {
                        value = parseInt($pricedInput.val(), 10);
                }

                return Number.isNaN(value) ? 0 : Math.max(0, value);
        }

        function getTourChildCount($form) {
                return getTourFreeGuestDisplayCount($form, '.hr-tour-child-count', 'hr_tour_children', 'ovabrw_childrens');
        }

        function getTourChildrenRateGuestCount($form) {
                return getTourPaidGuestCount($form) + getTourChildCount($form);
        }

        function getTourInfantCount($form) {
                return getTourFreeGuestDisplayCount($form, '.hr-tour-infant-count', 'hr_tour_infants', 'ovabrw_babies');
        }

        function ensureTourPricedGuestInputIsZero($form, name) {
                var $field = $form.find('.hr-tour-booking-field').first();
                var $inputs = $form.find('input[name="' + name + '"]');

                $inputs.each(function () {
                        $(this)
                                .attr('data-hr-tour-zero-guest', '1')
                                .val('0')
                                .attr('value', '0');
                });

                if (!$inputs.length && $field.length) {
                        $field.append('<input type="hidden" name="' + escapeHtml(name) + '" value="0" data-hr-tour-zero-guest="1">');
                }
        }

        function convertTourFreeGuestInput($input, displayName, displayClass, value) {
                var count = Number.isNaN(value) ? 0 : Math.max(0, value);

                if (!$input.length) {
                        return;
                }

                $input
                        .removeClass('ovabrw_childrens ovabrw_babies')
                        .addClass(displayClass)
                        .attr('name', displayName)
                        .attr('data-name', displayName)
                        .attr('data-hr-tour-free-display-input', '1')
                        .attr('min', '0')
                        .prop('readonly', true)
                        .val(String(count))
                        .attr('value', String(count));
        }

        function syncTourChildrenHidden($form) {
                var children = isTourChildrenEnabled($form) ? getTourChildCount($form) : 0;

                $form.find('.hr-tour-booking-field input[name="hr_tour_children"]').val(children > 0 ? String(children) : '');
                ensureTourPricedGuestInputIsZero($form, 'ovabrw_childrens');
                syncTourGuestTotalDisplay($form);

                return children;
        }

        function syncTourInfantHidden($form) {
                var infants = getTourInfantCount($form);

                $form.find('.hr-tour-booking-field input[name="hr_tour_infants"]').val(String(infants));
                ensureTourPricedGuestInputIsZero($form, 'ovabrw_babies');
                syncTourGuestTotalDisplay($form);

                return infants;
        }

        function getDiscountedTourChildrenUnitPrice($form, tourData) {
                var childCount = getTourChildCount($form);
                var totalPaidGuests = getTourChildrenRateGuestCount($form);
                var childrenRates = getTourChildrenRates(getTourDataForForm($form, tourData));
                var prices;
                var basePrice;
                var discountPercent;

                if (!childrenRates.enabled || childCount < 1) {
                        return 0;
                }

                prices = tourData && tourData.prices ? tourData.prices : {};
                basePrice = prices[String(totalPaidGuests)];

                if (totalPaidGuests < 1 || basePrice === null || typeof basePrice === 'undefined' || basePrice === '' || Number.isNaN(Number(basePrice))) {
                        return null;
                }

                discountPercent = Number(childrenRates.discountPercent);
                discountPercent = Number.isFinite(discountPercent) ? Math.min(100, Math.max(0, discountPercent)) : 0;

                return Math.max(0, Number(basePrice) * (1 - (discountPercent / 100)));
        }

        function getTourChildrenUnitPrice($form, tourData) {
                var childCount = getTourChildCount($form);
                var tourDataForForm = getTourDataForForm($form, tourData);
                var childrenRates = getTourChildrenRates(tourDataForForm);
                var price;

                if (isDayTourData(tourDataForForm) || isJoinerTourData(tourDataForForm)) {
                        return getDiscountedTourChildrenUnitPrice($form, tourDataForForm);
                }

                if (!childrenRates.enabled || childCount < 1) {
                        return 0;
                }

                price = childrenRates.prices[String(childCount)];

                if (price === null || typeof price === 'undefined' || price === '' || Number.isNaN(Number(price))) {
                        return null;
                }

                return Number(price);
        }

        function setTourGuestRowPrice($row, priceClass, priceText) {
                var $description = $row.find('.description').first();
                var $price = $description.find('.guests-price').first();

                if (!$price.length) {
                        $description.append('<span class="guests-price ' + priceClass + '"></span>');
                        $price = $description.find('.guests-price').first();
                }

                $description.find('.guests-price').not($price).remove();
                $price.attr('class', 'guests-price ' + priceClass).empty().text(priceText);
                $price.removeAttr('data-hr-free-price');
        }

        function applyTourFreeGuestPrice($row, priceClass) {
                setTourGuestRowPrice($row, priceClass, formatPrice(0));
        }

        function activateTourFreeGuestRowControls($form, $row, tourData) {
                tourData = getTourDataForForm($form, tourData);

                if (!isChildRateTourData(tourData) || !$row.length) {
                        return;
                }

                $row.find('.guests-icon, .guests-minus, .guests-plus')
                        .prop('disabled', false)
                        .removeAttr('disabled aria-disabled')
                        .removeClass('disabled is-disabled')
                        .css('pointer-events', 'auto');
        }

        function applyTourChildGuestPrice($form, $row, tourData) {
                var childCount = getTourChildCount($form);
                var unitPrice = getTourChildrenUnitPrice($form, tourData);
                var priceText = childCount > 0 && unitPrice !== null ? formatPrice(unitPrice) : formatPrice(0);

                setTourGuestRowPrice($row, 'childrens-price', priceText);
        }

        function hideTourChildRow($form) {
                var $displayInput = $form.find('.hr-tour-child-count').first();
                var $nativeInput = $displayInput.length ? $displayInput : $form.find('input[name="ovabrw_childrens"]').not('[data-hr-tour-zero-guest="1"]').first();
                var $row = $nativeInput.length ? $nativeInput.closest('.guests-buttons') : $form.find('.hr-tour-child-guest-row').first();

                if ($row.length) {
                        $row.hide();
                }

                if ($displayInput.length) {
                        $displayInput.val('0').attr('value', '0');
                }

                $form.find('.hr-tour-booking-field input[name="hr_tour_children"], .hr-tour-booking-field input[name="hr_tour_children_unit_price"], .hr-tour-booking-field input[name="hr_tour_children_total"]').val('');
                ensureTourPricedGuestInputIsZero($form, 'ovabrw_childrens');
                syncTourGuestTotalDisplay($form);
        }

        function ensureTourFreeChildRow($form, tourData) {
                var label = getTourChildrenLabel($form, tourData);
                var childCount = getTourChildCount($form);
                var $displayInput = $form.find('.hr-tour-child-count').first();
                var $nativeChildInput = $displayInput.length ? $displayInput : $form.find('input[name="ovabrw_childrens"]').not('[data-hr-tour-zero-guest="1"]').first();
                var $nativeRow;
                var $content;
                var html;

                if (!isTourChildrenEnabled($form, tourData)) {
                        hideTourChildRow($form);
                        return;
                }

                if ($nativeChildInput.length) {
                        $nativeRow = $nativeChildInput.closest('.guests-buttons');
                        $nativeRow.addClass('hr-tour-child-guest-row').show();
                        $nativeRow.find('.description .ovabrw-label').first().text(label);
                        $nativeRow.find('.description .guests-labels').remove();
                        convertTourFreeGuestInput($nativeChildInput, 'hr_tour_children_display', 'hr-tour-child-count', childCount);
                        ensureTourPricedGuestInputIsZero($form, 'ovabrw_childrens');
                        activateTourFreeGuestRowControls($form, $nativeRow, tourData);
                        applyTourChildGuestPrice($form, $nativeRow, tourData);
                        syncTourChildrenHidden($form);
                        return;
                }

                if ($form.find('.hr-tour-child-custom-row').length) {
                        ensureTourPricedGuestInputIsZero($form, 'ovabrw_childrens');
                        $form.find('.hr-tour-child-custom-row').first().show();
                        activateTourFreeGuestRowControls($form, $form.find('.hr-tour-child-custom-row').first(), tourData);
                        applyTourChildGuestPrice($form, $form.find('.hr-tour-child-custom-row').first(), tourData);
                        syncTourChildrenHidden($form);
                        return;
                }

                $content = $form.find('.ovabrw-wrapper-guestspicker .ovabrw-guestspicker-content').first();

                if (!$content.length) {
                        $content = $form.find('.ovabrw-wrapper-guestspicker').first();
                }

                if (!$content.length) {
                        return;
                }

                ensureTourPricedGuestInputIsZero($form, 'ovabrw_childrens');

                html = '';
                html += '<div class="guests-buttons hr-tour-child-guest-row hr-tour-child-custom-row">';
                html += '<div class="description">';
                html += '<h3 class="ovabrw-label">' + escapeHtml(label) + '</h3>';
                html += '<span class="guests-price childrens-price">' + escapeHtml(formatPrice(0)) + '</span>';
                html += '</div>';
                html += '<div class="guests-button">';
                html += '<div class="guests-icon minus"><i aria-hidden="true" class="icomoon icomoon-minus"></i></div>';
                html += '<input type="text" class="guests-input hr-tour-child-count" name="hr_tour_children_display" value="' + escapeHtml(childCount) + '" readonly min="0" data-name="hr_tour_children_display">';
                html += '<div class="guests-icon plus"><i aria-hidden="true" class="icomoon icomoon-plus"></i></div>';
                html += '</div>';
                html += '</div>';

                $content.append(html);
                activateTourFreeGuestRowControls($form, $content.find('.hr-tour-child-custom-row').last(), tourData);
                applyTourChildGuestPrice($form, $content.find('.hr-tour-child-custom-row').last(), tourData);
                syncTourChildrenHidden($form);
        }

        function ensureTourInfantGuestRow($form, tourData) {
                var label = getTourInfantLabel();
                var infantCount = getTourInfantCount($form);
                var $displayInput = $form.find('.hr-tour-infant-count').first();
                var $nativeBabyInput = $displayInput.length ? $displayInput : $form.find('input[name="ovabrw_babies"]').not('[data-hr-tour-zero-guest="1"]').first();
                var $nativeRow;
                var $content;
                var $anchorRow;
                var html;

                if ($nativeBabyInput.length) {
                        $nativeRow = $nativeBabyInput.closest('.guests-buttons');
                        $nativeRow.addClass('hr-tour-infant-guest-row').show();
                        $nativeRow.find('.description .ovabrw-label').first().text(label);
                        $nativeRow.find('.description .guests-labels').remove();
                        convertTourFreeGuestInput($nativeBabyInput, 'hr_tour_infants_display', 'hr-tour-infant-count', infantCount);
                        ensureTourPricedGuestInputIsZero($form, 'ovabrw_babies');
                        activateTourFreeGuestRowControls($form, $nativeRow, tourData);
                        applyTourFreeGuestPrice($nativeRow, 'babies-price');
                        syncTourInfantHidden($form);
                        return;
                }

                if ($form.find('.hr-tour-infant-custom-row').length) {
                        ensureTourPricedGuestInputIsZero($form, 'ovabrw_babies');
                        $form.find('.hr-tour-infant-custom-row').first().show();
                        activateTourFreeGuestRowControls($form, $form.find('.hr-tour-infant-custom-row').first(), tourData);
                        applyTourFreeGuestPrice($form.find('.hr-tour-infant-custom-row').first(), 'babies-price');
                        syncTourInfantHidden($form);
                        return;
                }

                $content = $form.find('.ovabrw-wrapper-guestspicker .ovabrw-guestspicker-content').first();

                if (!$content.length) {
                        $content = $form.find('.ovabrw-wrapper-guestspicker').first();
                }

                if (!$content.length) {
                        $anchorRow = $form.find('.hr-tour-child-guest-row:visible, .hr-tour-child-guest-row, .guests-buttons').last();

                        if ($anchorRow.length) {
                                $content = $anchorRow.parent();
                        }
                }

                if (!$content.length) {
                        return;
                }

                ensureTourPricedGuestInputIsZero($form, 'ovabrw_babies');

                html = '';
                html += '<div class="guests-buttons hr-tour-infant-guest-row hr-tour-infant-custom-row">';
                html += '<div class="description">';
                html += '<h3 class="ovabrw-label">' + escapeHtml(label) + '</h3>';
                html += '<span class="guests-price babies-price">' + escapeHtml(formatPrice(0)) + '</span>';
                html += '</div>';
                html += '<div class="guests-button">';
                html += '<div class="guests-icon minus"><i aria-hidden="true" class="icomoon icomoon-minus"></i></div>';
                html += '<input type="text" class="guests-input hr-tour-infant-count" name="hr_tour_infants_display" value="' + escapeHtml(infantCount) + '" readonly min="0" data-name="hr_tour_infants_display">';
                html += '<div class="guests-icon plus"><i aria-hidden="true" class="icomoon icomoon-plus"></i></div>';
                html += '</div>';
                html += '</div>';

                $content.append(html);
                activateTourFreeGuestRowControls($form, $content.find('.hr-tour-infant-custom-row').last(), tourData);
                syncTourChildrenHidden($form);
                syncTourInfantHidden($form);
        }

        function syncTourFreeGuestRows($form, tourData) {
                tourData = getTourDataForForm($form, tourData);
                ensureTourFreeChildRow($form, tourData);
                ensureTourInfantGuestRow($form, tourData);
                syncTourGuestRateDisplays($form, tourData);
        }

        function syncTourBookingTotalDisplay($form) {
                var total = Number($form.data('hr-tour-total') || 0);
                var valid = $form.data('hr-tour-total-valid') === true;
                var $total = $form.find('.hr-tour-booking-field .hr-tour-booking-total').first();

                if (!$total.length) {
                        return;
                }

                $total.text(valid && total > 0 && Number.isFinite(total) ? (window.hrBooking ? window.hrBooking.tourTotalLabel : 'Tour total: %s').replace('%s', formatPrice(total)) : '');
        }

        function getTourAdultUnitPrice($form, tourData) {
                var guestCount = getTourPaidGuestCount($form);
                var rateGuestCount = getTourChildrenRateGuestCount($form);
                var prices;
                var unitPrice;

                tourData = getTourDataForForm($form, tourData);
                prices = tourData && tourData.prices ? tourData.prices : {};
                unitPrice = prices[String(rateGuestCount)];

                if (guestCount < 1 || rateGuestCount < 1 || unitPrice === null || typeof unitPrice === 'undefined' || unitPrice === '' || Number.isNaN(Number(unitPrice))) {
                        return null;
                }

                return Number(unitPrice);
        }

        function syncTourAdultGuestRateDisplay($form, tourData) {
                var valid = $form.data('hr-tour-unit-price-valid') === true;
                var unitPrice = getTourAdultUnitPrice($form, tourData);
                var $adultInput;
                var $adultRow;

                if (unitPrice === null && valid) {
                        unitPrice = Number($form.data('hr-tour-unit-price'));
                }

                if (unitPrice === null || !Number.isFinite(unitPrice)) {
                        return;
                }

                $adultInput = getTourAdultGuestInput($form);

                if (!$adultInput.length) {
                        return;
                }

                $adultRow = $adultInput.closest('.guests-buttons');
                setTourGuestRowPrice($adultRow, 'adults-price', formatPrice(unitPrice));
        }

        function syncTourGuestRateDisplays($form, tourData) {
                var $childInput;
                var $infantInput;

                tourData = getTourDataForForm($form, tourData);

                syncTourAdultGuestRateDisplay($form, tourData);

                if (!isChildRateTourData(tourData)) {
                        return;
                }

                if (isTourChildrenEnabled($form, tourData)) {
                        $childInput = $form.find('.hr-tour-child-count').first();

                        if ($childInput.length) {
                                applyTourChildGuestPrice($form, $childInput.closest('.guests-buttons'), tourData);
                        }
                }

                $infantInput = $form.find('.hr-tour-infant-count').first();

                if ($infantInput.length) {
                        applyTourFreeGuestPrice($infantInput.closest('.guests-buttons'), 'babies-price');
                }
        }

        function bindTourFreeGuestButtonCapture($form) {
                if ($form.data('hr-tour-free-guest-button-capture-bound') || !$form.length || !$form[0].addEventListener) {
                        return;
                }

                $form.data('hr-tour-free-guest-button-capture-bound', true);

                $form[0].addEventListener('click', function (event) {
                        var $icon = $(event.target).closest('.guests-icon');
                        var $childRow;
                        var $infantRow;
                        var $input;
                        var value;

                        if (!$icon.length || !$.contains($form[0], $icon[0])) {
                                return;
                        }

                        $childRow = $icon.closest('.hr-tour-child-guest-row');
                        $infantRow = $childRow.length ? $() : $icon.closest('.hr-tour-infant-guest-row');

                        if (!$childRow.length && !$infantRow.length) {
                                return;
                        }

                        event.preventDefault();
                        event.stopPropagation();

                        if (event.stopImmediatePropagation) {
                                event.stopImmediatePropagation();
                        }

                        $input = $childRow.length ? $childRow.find('.hr-tour-child-count').first() : $infantRow.find('.hr-tour-infant-count').first();
                        value = parseInt($input.val(), 10);
                        value = Number.isNaN(value) ? 0 : value;

                        if ($icon.hasClass('minus') || $icon.hasClass('guests-minus')) {
                                value = Math.max(0, value - 1);
                        } else if ($icon.hasClass('plus') || $icon.hasClass('guests-plus')) {
                                value += 1;
                        }

                        $input.val(String(value)).attr('value', String(value)).trigger('change');
                }, true);
        }

        function scheduleTourFreeGuestRowsSync($form) {
                [0, 50, 150, 350, 800, 1500, 2500, 4000].forEach(function (delay) {
                        window.setTimeout(function () {
                                syncTourFreeGuestRows($form);
                                syncTourBookingTotalDisplay($form);
                                syncTourGuestRateDisplays($form);
                        }, delay);
                });
        }

        function bindDayTourAjaxGuestRowsSync($form) {
                if ($form.data('hr-day-tour-ajax-guest-sync-bound')) {
                        return;
                }

                $form.data('hr-day-tour-ajax-guest-sync-bound', true);

                $(document).on('ajaxComplete.hrDayTourGuestRows', function () {
                        var tourData = getTourDataForForm($form);

                        if (!isDayTourData(tourData) || !$form.closest('html').length) {
                                return;
                        }

                        scheduleTourFreeGuestRowsSync($form);
                        scheduleTourAjaxTotalSync($form);
                });
        }

        function bindJoinerTourAjaxGuestRowsSync($form) {
                if ($form.data('hr-joiner-tour-ajax-guest-sync-bound')) {
                        return;
                }

                $form.data('hr-joiner-tour-ajax-guest-sync-bound', true);

                $(document).on('ajaxComplete.hrJoinerTourGuestRows', function () {
                        var tourData = getTourDataForForm($form);

                        if (!isJoinerTourData(tourData) || !$form.closest('html').length) {
                                return;
                        }

                        scheduleTourFreeGuestRowsSync($form);
                        scheduleTourAjaxTotalSync($form);
                });
        }

        function bindTourFreeGuestRowsObserver($form) {
                var target;
                var observer;
                var syncTimer;

                if ($form.data('hr-tour-free-guest-observer-bound') || !window.MutationObserver) {
                        return;
                }

                target = $form.find('.ovabrw-wrapper-guestspicker').first()[0] || $form.find('.guests-buttons').first().parent()[0] || $form[0];

                if (!target) {
                        return;
                }

                $form.data('hr-tour-free-guest-observer-bound', true);

                observer = new MutationObserver(function () {
                        window.clearTimeout(syncTimer);
                        syncTimer = window.setTimeout(function () {
                                syncTourFreeGuestRows($form);
                                syncTourBookingTotalDisplay($form);
                                syncTourGuestRateDisplays($form);
                        }, 0);
                });

                observer.observe(target, {
                        childList: true,
                        subtree: true,
                        characterData: true
                });

                $form.data('hr-tour-free-guest-observer', observer);
        }

        function updateDayTourSelection($form, tourData) {
                var $field = $form.find('.hr-day-tour-booking-field').first();
                var $total;
                var guestCount = getTourPaidGuestCount($form);
                var childCount = isTourChildrenEnabled($form, tourData) ? getTourChildCount($form) : 0;
                var rateGuestCount = guestCount + childCount;
                var prices = tourData && tourData.prices ? tourData.prices : {};
                var unitPrice = prices[String(rateGuestCount)];
                var childUnitPrice = getTourChildrenUnitPrice($form, tourData);
                var childTotal = 0;
                var addons;
                var total = 0;

                if (!$field.length) {
                        $field = $form.find('.hr-tour-booking-field').first();
                }

                if (!isDayTourData(tourData) || !$field.length) {
                        return false;
                }

                $total = $field.find('.hr-tour-booking-total');

                syncTourFreeGuestRows($form, tourData);
                syncTourChildrenHidden($form);
                syncTourInfantHidden($form);

                if (!guestCount) {
                        $field.find('input[name="hr_tour_pax"]').val('');
                        $field.find('input[name="hr_tour_unit_price"]').val('');
                        $field.find('input[name="hr_tour_children_unit_price"]').val('');
                        $field.find('input[name="hr_tour_children_total"]').val('');
                        $field.find('input[name="hr_tour_addons_payload"]').val('');
                        $field.find('input[name="hr_tour_total"]').val('');
                        $field.find('.hr-tour-addon-select').prop('disabled', true);
                        $total.text(window.hrBooking ? window.hrBooking.tourChooseGuests : '');
                        $form.data('hr-tour-total', 0);
                        $form.data('hr-tour-total-valid', false);
                        $form.data('hr-tour-unit-price', 0);
                        $form.data('hr-tour-unit-price-valid', false);
                        syncTourAjaxTotal($form);
                        return false;
                }

                if (rateGuestCount < 1 || unitPrice === null || typeof unitPrice === 'undefined' || Number.isNaN(Number(unitPrice)) || (childCount > 0 && childUnitPrice === null)) {
                        $field.find('input[name="hr_tour_pax"]').val('');
                        $field.find('input[name="hr_tour_unit_price"]').val('');
                        $field.find('input[name="hr_tour_children_unit_price"]').val('');
                        $field.find('input[name="hr_tour_children_total"]').val('');
                        $field.find('input[name="hr_tour_addons_payload"]').val('');
                        $field.find('input[name="hr_tour_total"]').val('');
                        $field.find('.hr-tour-addon-select').prop('disabled', true);
                        $total.text(window.hrBooking ? window.hrBooking.tourInvalidLabel : '');
                        $form.data('hr-tour-total', 0);
                        $form.data('hr-tour-total-valid', false);
                        $form.data('hr-tour-unit-price', 0);
                        $form.data('hr-tour-unit-price-valid', false);
                        syncTourAjaxTotal($form);
                        return false;
                }

                unitPrice = Number(unitPrice);
                childUnitPrice = childCount > 0 ? Number(childUnitPrice) : 0;
                childTotal = childUnitPrice * childCount;
                $field.find('.hr-tour-addon-select').prop('disabled', false);
                addons = getSelectedTourAddons($field);
                total = (unitPrice * guestCount) + childTotal + addons.total;

                $field.find('input[name="hr_tour_pax"]').val(String(guestCount));
                $field.find('input[name="hr_tour_unit_price"]').val(String(unitPrice));
                $field.find('input[name="hr_tour_children_unit_price"]').val(childCount > 0 ? String(childUnitPrice) : '');
                $field.find('input[name="hr_tour_children_total"]').val(childTotal > 0 ? String(childTotal) : '');
                $field.find('input[name="hr_tour_addons_payload"]').val(Object.keys(addons.payload).length ? JSON.stringify(addons.payload) : '');
                $field.find('input[name="hr_tour_total"]').val(total ? String(total) : '');
                $form.data('hr-tour-total', total);
                $form.data('hr-tour-total-valid', total > 0);
                $form.data('hr-tour-unit-price', unitPrice);
                $form.data('hr-tour-unit-price-valid', true);
                syncTourBookingTotalDisplay($form);
                syncTourGuestRateDisplays($form, tourData);
                syncTourAjaxTotal($form);
                return true;
        }

        function updateTourSelection($form, tourData) {
                var $field = $form.find('.hr-tour-booking-field');
                var $total = $field.find('.hr-tour-booking-total');
                var guestCount = getTourPaidGuestCount($form);
                var childCount = isTourChildrenEnabled($form, tourData) ? getTourChildCount($form) : 0;
                var rateGuestCount = guestCount + childCount;
                var prices = tourData && tourData.prices ? tourData.prices : {};
                var unitPrice = prices[String(rateGuestCount)];
                var childUnitPrice = getTourChildrenUnitPrice($form, tourData);
                var childTotal = 0;
                var addons;
                var total = 0;

                if (isDayTourData(tourData)) {
                        return updateDayTourSelection($form, tourData);
                }

                if (!tourData || !$field.length) {
                        return false;
                }

                syncTourFreeGuestRows($form);
                syncTourChildrenHidden($form);
                syncTourInfantHidden($form);

                if (!guestCount) {
                        $field.find('input[name="hr_tour_pax"]').val('');
                        $field.find('input[name="hr_tour_unit_price"]').val('');
                        $field.find('input[name="hr_tour_children_unit_price"]').val('');
                        $field.find('input[name="hr_tour_children_total"]').val('');
                        $field.find('input[name="hr_tour_addons_payload"]').val('');
                        $field.find('input[name="hr_tour_total"]').val('');
                        $field.find('.hr-tour-addon-select').prop('disabled', true);
                        $total.text(window.hrBooking ? window.hrBooking.tourChooseGuests : '');
                        $form.data('hr-tour-total', 0);
                        $form.data('hr-tour-total-valid', false);
                        $form.data('hr-tour-unit-price', 0);
                        $form.data('hr-tour-unit-price-valid', false);
                        syncTourAjaxTotal($form);
                        return false;
                }

                if (rateGuestCount < 1 || unitPrice === null || typeof unitPrice === 'undefined' || Number.isNaN(Number(unitPrice)) || (childCount > 0 && childUnitPrice === null)) {
                        $field.find('input[name="hr_tour_pax"]').val('');
                        $field.find('input[name="hr_tour_unit_price"]').val('');
                        $field.find('input[name="hr_tour_children_unit_price"]').val('');
                        $field.find('input[name="hr_tour_children_total"]').val('');
                        $field.find('input[name="hr_tour_addons_payload"]').val('');
                        $field.find('input[name="hr_tour_total"]').val('');
                        $field.find('.hr-tour-addon-select').prop('disabled', true);
                        $total.text(window.hrBooking ? window.hrBooking.tourInvalidLabel : '');
                        $form.data('hr-tour-total', 0);
                        $form.data('hr-tour-total-valid', false);
                        $form.data('hr-tour-unit-price', 0);
                        $form.data('hr-tour-unit-price-valid', false);
                        syncTourAjaxTotal($form);
                        return false;
                }

                unitPrice = Number(unitPrice);
                childUnitPrice = childCount > 0 ? Number(childUnitPrice) : 0;
                childTotal = childUnitPrice * childCount;
                $field.find('.hr-tour-addon-select').prop('disabled', false);
                addons = getSelectedTourAddons($field);
                total = (unitPrice * guestCount) + childTotal + addons.total;

                $field.find('input[name="hr_tour_pax"]').val(String(guestCount));
                $field.find('input[name="hr_tour_unit_price"]').val(String(unitPrice));
                $field.find('input[name="hr_tour_children_unit_price"]').val(childCount > 0 ? String(childUnitPrice) : '');
                $field.find('input[name="hr_tour_children_total"]').val(childTotal > 0 ? String(childTotal) : '');
                $field.find('input[name="hr_tour_addons_payload"]').val(Object.keys(addons.payload).length ? JSON.stringify(addons.payload) : '');
                $field.find('input[name="hr_tour_total"]').val(total ? String(total) : '');
                $form.data('hr-tour-total', total);
                $form.data('hr-tour-total-valid', total > 0);
                $form.data('hr-tour-unit-price', unitPrice);
                $form.data('hr-tour-unit-price-valid', true);
                syncTourBookingTotalDisplay($form);
                syncTourGuestRateDisplays($form, tourData);
                syncTourAjaxTotal($form);
                return true;
        }

        function initDayTourBookingForm($form, tourData) {
                var dayTourRefreshTimer = null;
                var refreshDayTour;
                var scheduleDayTourRefresh;

                $form
                        .addClass('hr-day-tour-booking-form')
                        .data('hr-tour-data', tourData)
                        .data('hr-day-tour-data', tourData);

                hideTourAjaxTotal($form);
                renderTourAddonSelectors($form, tourData);
                syncTourFreeGuestRows($form, tourData);
                bindTourFreeGuestButtonCapture($form);
                bindTourFreeGuestRowsObserver($form);
                bindDayTourAjaxGuestRowsSync($form);
                updateDayTourSelection($form, tourData);
                scheduleTourFreeGuestRowsSync($form);
                scheduleTourAjaxTotalSync($form);

                refreshDayTour = function (triggerRecalc) {
                        syncTourFreeGuestRows($form, tourData);
                        updateDayTourSelection($form, tourData);

                        if (triggerRecalc) {
                                triggerBookingRecalculation($form);
                                scheduleTourFreeGuestRowsSync($form);
                                scheduleTourAjaxTotalSync($form);
                        }
                };

                scheduleDayTourRefresh = function (triggerRecalc) {
                        window.clearTimeout(dayTourRefreshTimer);
                        dayTourRefreshTimer = window.setTimeout(function () {
                                refreshDayTour(triggerRecalc);
                        }, 0);
                };

                $form.on('change input', '.hr-tour-addon-select, ' + GUEST_INPUT_SELECTOR, function () {
                        scheduleDayTourRefresh(true);
                        scheduleTourFreeGuestRowsSync($form);
                });

                $form.on('click', '.hr-tour-child-custom-row .guests-icon', function (event) {
                        var $input = $(this).closest('.hr-tour-child-custom-row').find('.hr-tour-child-count').first();
                        var value = parseInt($input.val(), 10);

                        event.preventDefault();
                        event.stopPropagation();

                        value = Number.isNaN(value) ? 0 : value;

                        if ($(this).hasClass('minus')) {
                                value = Math.max(0, value - 1);
                        } else if ($(this).hasClass('plus')) {
                                value += 1;
                        }

                        $input.val(String(value)).trigger('change');
                        scheduleDayTourRefresh(true);
                });

                $form.on('change input', '.hr-tour-child-count', function () {
                        syncTourChildrenHidden($form);
                        scheduleDayTourRefresh(true);
                        scheduleTourFreeGuestRowsSync($form);
                });

                $form.on('click', '.hr-tour-infant-custom-row .guests-icon', function (event) {
                        var $input = $(this).closest('.hr-tour-infant-custom-row').find('.hr-tour-infant-count').first();
                        var value = parseInt($input.val(), 10);

                        event.preventDefault();
                        event.stopPropagation();

                        value = Number.isNaN(value) ? 0 : value;

                        if ($(this).hasClass('minus')) {
                                value = Math.max(0, value - 1);
                        } else if ($(this).hasClass('plus')) {
                                value += 1;
                        }

                        $input.val(String(value)).trigger('change');
                        scheduleDayTourRefresh(true);
                });

                $form.on('change input', '.hr-tour-infant-count', function () {
                        syncTourInfantHidden($form);
                        scheduleDayTourRefresh(true);
                        scheduleTourFreeGuestRowsSync($form);
                });

                $form.on('change input blur', PACKAGE_START_DATE_INPUT_SELECTOR, function () {
                        scheduleDayTourRefresh(true);
                        scheduleTourFreeGuestRowsSync($form);
                });

                $form.on('click', '.ovabrw-wrapper-guestspicker .guests-icon', function () {
                        scheduleDayTourRefresh(true);
                        scheduleTourFreeGuestRowsSync($form);

                        window.setTimeout(function () {
                                refreshDayTour(true);
                        }, 50);
                });
        }

        function initJoinerTourBookingForm($form, tourData) {
                var joinerTourRefreshTimer = null;
                var refreshJoinerTour;
                var scheduleJoinerTourRefresh;

                $form
                        .addClass('hr-joiner-booking-form')
                        .data('hr-tour-data', tourData)
                        .data('hr-joiner-tour-data', tourData);

                hideTourAjaxTotal($form);
                renderTourAddonSelectors($form, tourData);
                syncTourFreeGuestRows($form, tourData);
                bindTourFreeGuestButtonCapture($form);
                bindTourFreeGuestRowsObserver($form);
                bindJoinerTourAjaxGuestRowsSync($form);
                updateTourSelection($form, tourData);
                scheduleTourFreeGuestRowsSync($form);
                scheduleTourAjaxTotalSync($form);

                refreshJoinerTour = function (triggerRecalc) {
                        syncTourFreeGuestRows($form, tourData);
                        updateTourSelection($form, tourData);

                        if (triggerRecalc) {
                                triggerBookingRecalculation($form);
                                scheduleTourFreeGuestRowsSync($form);
                                scheduleTourAjaxTotalSync($form);
                        }
                };

                scheduleJoinerTourRefresh = function (triggerRecalc) {
                        window.clearTimeout(joinerTourRefreshTimer);
                        joinerTourRefreshTimer = window.setTimeout(function () {
                                refreshJoinerTour(triggerRecalc);
                        }, 0);
                };

                $form.on('change input', '.hr-tour-addon-select, ' + GUEST_INPUT_SELECTOR, function () {
                        scheduleJoinerTourRefresh(true);
                        scheduleTourFreeGuestRowsSync($form);
                });

                $form.on('click', '.hr-tour-child-custom-row .guests-icon', function (event) {
                        var $input = $(this).closest('.hr-tour-child-custom-row').find('.hr-tour-child-count').first();
                        var value = parseInt($input.val(), 10);

                        event.preventDefault();
                        event.stopPropagation();

                        value = Number.isNaN(value) ? 0 : value;

                        if ($(this).hasClass('minus')) {
                                value = Math.max(0, value - 1);
                        } else if ($(this).hasClass('plus')) {
                                value += 1;
                        }

                        $input.val(String(value)).trigger('change');
                        scheduleJoinerTourRefresh(true);
                });

                $form.on('change input', '.hr-tour-child-count', function () {
                        syncTourChildrenHidden($form);
                        scheduleJoinerTourRefresh(true);
                        scheduleTourFreeGuestRowsSync($form);
                });

                $form.on('click', '.hr-tour-infant-custom-row .guests-icon', function (event) {
                        var $input = $(this).closest('.hr-tour-infant-custom-row').find('.hr-tour-infant-count').first();
                        var value = parseInt($input.val(), 10);

                        event.preventDefault();
                        event.stopPropagation();

                        value = Number.isNaN(value) ? 0 : value;

                        if ($(this).hasClass('minus')) {
                                value = Math.max(0, value - 1);
                        } else if ($(this).hasClass('plus')) {
                                value += 1;
                        }

                        $input.val(String(value)).trigger('change');
                        scheduleJoinerTourRefresh(true);
                });

                $form.on('change input', '.hr-tour-infant-count', function () {
                        syncTourInfantHidden($form);
                        scheduleJoinerTourRefresh(true);
                        scheduleTourFreeGuestRowsSync($form);
                });

                $form.on('change input blur', PACKAGE_START_DATE_INPUT_SELECTOR, function () {
                        scheduleJoinerTourRefresh(true);
                        scheduleTourFreeGuestRowsSync($form);
                });

                $form.on('click', '.ovabrw-wrapper-guestspicker .guests-icon', function () {
                        scheduleJoinerTourRefresh(true);
                        scheduleTourFreeGuestRowsSync($form);

                        window.setTimeout(function () {
                                refreshJoinerTour(true);
                        }, 50);
                });
        }

        function getPackageDataForForm($form, packageData) {
                return packageData || $form.data('hr-package-data') || null;
        }

        function getPackageData($form) {
                var $field = $form.find('.hr-package-booking-field');
                var raw = $field.attr('data-package');

                if (!$field.length || !raw) {
                        return null;
                }

                try {
                        var packageData = JSON.parse(raw);

                        $form.data('hr-package-data', packageData);

                        return packageData;
                } catch (error) {
                        return null;
                }
        }

        function hidePackageGuestRateDisplay($form) {
                $form.find('.ovabrw-wrapper-guestspicker .guests-price').each(function () {
                        this.style.setProperty('display', 'none', 'important');
                });
        }

        function schedulePackageGuestRateDisplayHide($form) {
                [0, 50, 150, 350, 800, 1500].forEach(function (delay) {
                        window.setTimeout(function () {
                                hidePackageGuestRateDisplay($form);
                        }, delay);
                });
        }

        function resetPackageBreakdown($field) {
                $field.find('.hr-package-booking-breakdown').empty().prop('hidden', true);
        }

        function getPackageGuestCounts($form) {
                var counts = {
                        adults: 0,
                        children: 0,
                        infants: 0
                };

                counts.adults = parseInt($form.find('[name="ovabrw_adults"]').val(), 10);
                counts.children = parseInt($form.find('[name="ovabrw_childrens"]').val(), 10);
                counts.infants = parseInt($form.find('[name="ovabrw_babies"]').val(), 10);
                counts.adults = Number.isNaN(counts.adults) ? 0 : Math.max(0, counts.adults);
                counts.children = Number.isNaN(counts.children) ? 0 : Math.max(0, counts.children);
                counts.infants = Number.isNaN(counts.infants) ? 0 : Math.max(0, counts.infants);
                counts.total = counts.adults + counts.children + counts.infants;
                counts.paid = counts.adults + counts.children;

                return counts;
        }

        function isPackageChildRateActivityType(type) {
                return type === 'Day Tour' || type === 'Joiner';
        }

        function getPackageActivityChildrenRates($option) {
                var rawRates = {};

                try {
                        rawRates = JSON.parse($option.attr('data-children-rates') || '{}');
                } catch (error) {
                        rawRates = {};
                }

                return getTourChildrenRates({ childrenRates: rawRates });
        }

        function getPackageActivityPricing($option, prices, guestCounts) {
                var perHead = $option.attr('data-per-head') === '1';
                var type = String($option.attr('data-tour-type') || '');
                var guestCount = guestCounts.total;
                var tierCount = guestCount;
                var unitPrice;
                var childrenRates;
                var childUnitPrice;
                var discountPercent;
                var amount;
                var descriptionParts = [];

                if (perHead && isPackageChildRateActivityType(type)) {
                        tierCount = guestCounts.paid;

                        if (tierCount < 1) {
                                return null;
                        }
                }

                if (tierCount < 1 || prices[String(tierCount)] === null || typeof prices[String(tierCount)] === 'undefined' || prices[String(tierCount)] === '' || Number.isNaN(Number(prices[String(tierCount)]))) {
                        return null;
                }

                unitPrice = Number(prices[String(tierCount)]);

                if (!perHead) {
                        return {
                                amount: unitPrice,
                                descriptionSuffix: ''
                        };
                }

                if (!isPackageChildRateActivityType(type)) {
                        return {
                                amount: unitPrice * guestCount,
                                descriptionSuffix: ' (' + formatPrice(unitPrice) + ' x ' + guestCount + ')'
                        };
                }

                childrenRates = getPackageActivityChildrenRates($option);
                discountPercent = childrenRates.enabled ? Number(childrenRates.discountPercent) : 0;
                discountPercent = Number.isFinite(discountPercent) ? Math.min(100, Math.max(0, discountPercent)) : 0;
                childUnitPrice = childrenRates.enabled ? Math.max(0, unitPrice * (1 - (discountPercent / 100))) : unitPrice;
                amount = (unitPrice * guestCounts.adults) + (childUnitPrice * guestCounts.children);

                if (guestCounts.adults > 0) {
                        descriptionParts.push('Adults: ' + formatPrice(unitPrice) + ' x ' + guestCounts.adults);
                }

                if (guestCounts.children > 0) {
                        descriptionParts.push((childrenRates.label || 'Children') + ': ' + formatPrice(childUnitPrice) + ' x ' + guestCounts.children);
                }

                if (guestCounts.infants > 0) {
                        descriptionParts.push('0-2 Infant: ' + formatPrice(0) + ' x ' + guestCounts.infants);
                }

                return {
                        amount: amount,
                        descriptionSuffix: descriptionParts.length ? ' (' + descriptionParts.join('; ') + ')' : ''
                };
        }

        function renderPackageBreakdown($field, rows, total) {
                var $breakdown = $field.find('.hr-package-booking-breakdown');
                var html = '';

                if (!$breakdown.length) {
                        $field.find('.hr-package-booking-selectors').after('<div class="hr-package-booking-breakdown" hidden></div>');
                        $breakdown = $field.find('.hr-package-booking-breakdown').first();
                }

                if (!rows.length || !Number.isFinite(total)) {
                        resetPackageBreakdown($field);
                        return;
                }

                html += '<div class="hr-package-breakdown-title">Package total breakdown</div>';

                rows.forEach(function (row) {
                        html += '<div class="hr-package-breakdown-row">';
                        html += '<div class="hr-package-breakdown-label">';
                        html += '<span>' + escapeHtml(row.label) + '</span>';

                        if (row.description) {
                                html += '<small>' + escapeHtml(row.description) + '</small>';
                        }

                        html += '</div>';
                        html += '<div class="hr-package-breakdown-amount">' + escapeHtml(formatPrice(row.amount)) + '</div>';
                        html += '</div>';
                });

                html += '<div class="hr-package-breakdown-row hr-package-breakdown-row-total">';
                html += '<div class="hr-package-breakdown-label"><span>Total</span></div>';
                html += '<div class="hr-package-breakdown-amount">' + escapeHtml(formatPrice(total)) + '</div>';
                html += '</div>';

                $breakdown.html(html).prop('hidden', false);
        }

        function getPackageFreeActivityOptionsForGroup(group) {
                var allValues = PACKAGE_FREE_ACTIVITY_OPTIONS.map(function (option) {
                        return option.value;
                });
                var enabledValues = Array.isArray(group && group.freeOptions)
                        ? group.freeOptions.map(String)
                        : allValues;

                return PACKAGE_FREE_ACTIVITY_OPTIONS.filter(function (option) {
                        return enabledValues.indexOf(option.value) !== -1;
                });
        }

        function getPackageActivityDisplayLabel(option) {
                var optionLabel = option && option.label ? String(option.label) : 'Activity option';
                var label = option && option.placeName ? String(option.placeName) + ' - ' + optionLabel : optionLabel;

                return label.replace(/\s+\((?:Day Tour|Joiner)\)\s*$/i, '');
        }

        function getPackageTransitDayPricing($option, guestCounts) {
                var fees = {};
                var paidGuestCount = Math.max(0, Number(guestCounts && guestCounts.paid ? guestCounts.paid : 0));
                var unitPrice;
                var descriptionParts = [];

                if (paidGuestCount < 1) {
                        return null;
                }

                try {
                        fees = JSON.parse($option.attr('data-transit-day-fees') || '{}');
                } catch (error) {
                        fees = {};
                }

                if (fees[String(paidGuestCount)] === null || typeof fees[String(paidGuestCount)] === 'undefined' || fees[String(paidGuestCount)] === '' || Number.isNaN(Number(fees[String(paidGuestCount)]))) {
                        return null;
                }

                unitPrice = Math.max(0, Number(fees[String(paidGuestCount)]));
                descriptionParts.push(formatPrice(unitPrice) + ' x ' + paidGuestCount + ' paid ' + (paidGuestCount === 1 ? 'guest' : 'guests'));

                if (guestCounts && guestCounts.infants > 0) {
                        descriptionParts.push('0-2 Infant: ' + formatPrice(0) + ' x ' + guestCounts.infants);
                }

                return {
                        amount: unitPrice * paidGuestCount,
                        descriptionSuffix: descriptionParts.length ? ' (' + descriptionParts.join('; ') + ')' : ''
                };
        }

        function getPackageHotelPricing(prices, guestCounts) {
                var paidGuestCount = Math.max(0, Number(guestCounts && guestCounts.paid ? guestCounts.paid : 0));
                var unitPrice;
                var descriptionParts = [];

                if (paidGuestCount < 1) {
                        return null;
                }

                if (prices[String(paidGuestCount)] === null || typeof prices[String(paidGuestCount)] === 'undefined' || prices[String(paidGuestCount)] === '' || Number.isNaN(Number(prices[String(paidGuestCount)]))) {
                        return null;
                }

                unitPrice = Math.max(0, Number(prices[String(paidGuestCount)]));
                descriptionParts.push(formatPrice(unitPrice) + ' x ' + paidGuestCount + ' paid ' + (paidGuestCount === 1 ? 'guest' : 'guests'));

                if (guestCounts && guestCounts.infants > 0) {
                        descriptionParts.push('0-2 Infant: ' + formatPrice(0) + ' x ' + guestCounts.infants);
                }

                return {
                        amount: unitPrice * paidGuestCount,
                        descriptionSuffix: descriptionParts.length ? ' (' + descriptionParts.join('; ') + ')' : ''
                };
        }

        function renderPackageSelectors($form, packageData) {
                var $field = $form.find('.hr-package-booking-field');
                var $wrap = $field.find('.hr-package-booking-selectors');
                var html = '';

                if (!packageData) {
                        return;
                }

                packageData.hotels.forEach(function (group) {
                        var groupLabel = group.placeName + ' Hotel';

                        if (group.nights) {
                                groupLabel += ' (' + group.nights + ' ' + (Number(group.nights) === 1 ? 'night' : 'nights') + ')';
                        }

                        html += '<div class="hr-package-group">';
                        html += '<label for="hr-package-hotel-' + group.placeId + '">' + groupLabel + '</label>';
                        html += '<select id="hr-package-hotel-' + group.placeId + '" class="hr-package-select hr-package-hotel-select" data-place-id="' + group.placeId + '" data-default-id="' + (group.defaultRateId || '') + '" required>';
                        html += '<option value="">' + (window.hrBooking ? window.hrBooking.selectPlaceholder : 'Select a hotel package') + '</option>';
                        group.options.forEach(function (option) {
                                var label = option.label || 'Hotel option';
                                html += '<option value="' + option.rateId + '" data-prices=\'' + JSON.stringify(option.prices) + '\' data-label="' + label.replace(/"/g, '&quot;') + '" data-hotel-id="' + option.hotelId + '">' + label + '</option>';
                        });
                        html += '</select>';
                        html += '</div>';
                });

                packageData.activities.forEach(function (group) {
                        html += '<div class="hr-package-group">';
                        html += '<label for="hr-package-activity-' + group.day + '">Day ' + group.day + '</label>';
                        html += '<select id="hr-package-activity-' + group.day + '" class="hr-package-select hr-package-activity-select" data-day="' + group.day + '" data-default-id="' + (group.defaultTourRateId || '') + '" required>';

                        if (group.transitDay) {
                                html += '<option value="hr_transit_day" data-label="Transit Day" data-transit-day="1" data-transit-day-fees=\'' + escapeAttribute(JSON.stringify(group.transitDayFees || {})) + '\'>Transit Day</option>';
                        } else {
                                getPackageFreeActivityOptionsForGroup(group).forEach(function (option) {
                                        html += '<option value="' + option.value + '" data-label="' + option.label.replace(/"/g, '&quot;') + '" data-free-option="1">' + option.label + '</option>';
                                });
                                group.options.forEach(function (option) {
                                        var label = getPackageActivityDisplayLabel(option);
                                        html += '<option value="' + option.tourRateId + '" data-prices=\'' + escapeAttribute(JSON.stringify(option.prices || {})) + '\' data-label="' + escapeAttribute(label) + '" data-per-head="' + (option.perHead ? '1' : '0') + '" data-tour-type="' + escapeAttribute(option.type || '') + '" data-children-rates=\'' + escapeAttribute(JSON.stringify(option.childrenRates || {})) + '\'>' + escapeHtml(label) + '</option>';
                                });
                        }

                        html += '</select>';
                        html += '</div>';
                });

                $wrap.html(html);

                $wrap.find('.hr-package-select').each(function () {
                        var $select = $(this);
                        var defaultId = String($select.data('default-id') || '');
                        var $realOptions = $select.find('option').filter(function () {
                                return $(this).val() !== '';
                        });

                        if (defaultId && $select.find('option[value="' + defaultId + '"]').length) {
                                $select.val(defaultId);
                        } else if ($realOptions.length === 1) {
                                $select.val($realOptions.first().val());
                        }
                });
        }

        function updatePackageSelection($form, packageData) {
                var $field = $form.find('.hr-package-booking-field');
                var $total = $field.find('.hr-package-booking-total');
                var guestCount = getGuestCount($form);
                var guestCounts = getPackageGuestCounts($form);
                var payload = { hotels: {}, activities: {} };
                var total = 0;
                var airportTransferFee = Number(packageData.airportTransferFee || 0);
                var seaportTransferFee = Number(packageData.seaportTransferFee || 0);
                var breakdownRows = [];
                var invalid = false;

                if (!packageData || !$field.length) {
                        return false;
                }

                if (!guestCount) {
                        $field.find('.hr-package-select').prop('disabled', true);
                        $field.find('input[name="hr_package_selection_payload"]').val('');
                        $field.find('input[name="hr_package_total"]').val('');
                        $total.text(window.hrBooking ? window.hrBooking.packageChooseGuests : '');
                        resetPackageBreakdown($field);
                        $form.data('hr-package-total', 0);
                        $form.data('hr-package-total-valid', false);
                        syncPackageAjaxTotal($form);
                        return false;
                }

                $field.find('.hr-package-select').prop('disabled', false);

                $field.find('.hr-package-hotel-select').each(function () {
                        var $select = $(this);
                        var value = $select.val();
                        var placeId = String($select.data('place-id'));

                        if (!value) {
                                invalid = true;
                                return;
                        }

                        var $option = $select.find('option:selected');
                        var prices = {};

                        try {
                                prices = JSON.parse($option.attr('data-prices') || '{}');
                        } catch (error) {
                                prices = {};
                        }

                        var hotelPricing = getPackageHotelPricing(prices, guestCounts);

                        if (!hotelPricing) {
                                invalid = true;
                                return;
                        }

                        total += hotelPricing.amount;
                        breakdownRows.push({
                                label: $.trim($select.closest('.hr-package-group').find('label').first().text()) || 'Hotel',
                                description: ($option.attr('data-label') || $.trim($option.text())) + hotelPricing.descriptionSuffix,
                                amount: hotelPricing.amount
                        });
                        payload.hotels[placeId] = Number(value);
                });

                $field.find('.hr-package-activity-select').each(function () {
                        var $select = $(this);
                        var value = $select.val();
                        var day = String($select.data('day'));

                        if (!value) {
                                invalid = true;
                                return;
                        }

                        var $option = $select.find('option:selected');
                        var prices = {};

                        if ($option.attr('data-transit-day') === '1') {
                                var transitPricing = getPackageTransitDayPricing($option, guestCounts);

                                if (!transitPricing) {
                                        invalid = true;
                                        return;
                                }

                                total += transitPricing.amount;
                                breakdownRows.push({
                                        label: $.trim($select.closest('.hr-package-group').find('label').first().text()) || ('Day ' + day),
                                        description: ($option.attr('data-label') || $.trim($option.text())) + transitPricing.descriptionSuffix,
                                        amount: transitPricing.amount
                                });
                                payload.activities[day] = value;
                                return;
                        }

                        if ($option.attr('data-free-option') === '1') {
                                breakdownRows.push({
                                        label: $.trim($select.closest('.hr-package-group').find('label').first().text()) || ('Day ' + day),
                                        description: $option.attr('data-label') || $.trim($option.text()),
                                        amount: 0
                                });
                                payload.activities[day] = value;
                                return;
                        }

                        try {
                                prices = JSON.parse($option.attr('data-prices') || '{}');
                        } catch (error) {
                                prices = {};
                        }

                        var pricing = getPackageActivityPricing($option, prices, guestCounts);

                        if (!pricing) {
                                invalid = true;
                                return;
                        }

                        total += pricing.amount;
                        breakdownRows.push({
                                label: $.trim($select.closest('.hr-package-group').find('label').first().text()) || ('Day ' + day),
                                description: ($option.attr('data-label') || $.trim($option.text())) + pricing.descriptionSuffix,
                                amount: pricing.amount
                        });
                        payload.activities[day] = Number(value);
                });

                if (airportTransferFee > 0 && Number.isFinite(airportTransferFee)) {
                        total += airportTransferFee;
                        breakdownRows.push({
                                label: 'Airport Transfer Fee',
                                description: '',
                                amount: airportTransferFee
                        });
                }

                if (seaportTransferFee > 0 && Number.isFinite(seaportTransferFee)) {
                        total += seaportTransferFee;
                        breakdownRows.push({
                                label: 'Seaport Transfer Fee',
                                description: '',
                                amount: seaportTransferFee
                        });
                }

                if (invalid) {
                        $field.find('input[name="hr_package_selection_payload"]').val('');
                        $field.find('input[name="hr_package_total"]').val('');
                        $total.text(window.hrBooking ? window.hrBooking.packageInvalidLabel : '');
                        resetPackageBreakdown($field);
                        $form.data('hr-package-total', 0);
                        $form.data('hr-package-total-valid', false);
                        syncPackageAjaxTotal($form);
                        return false;
                }

                $field.find('input[name="hr_package_selection_payload"]').val(JSON.stringify(payload));
                $field.find('input[name="hr_package_total"]').val(total ? String(total) : '');
                $total.text(total ? (window.hrBooking ? window.hrBooking.packageTotalLabel : 'Package total: %s').replace('%s', formatPrice(total)) : '');
                renderPackageBreakdown($field, breakdownRows, total);
                $form.data('hr-package-total', total);
                $form.data('hr-package-total-valid', total > 0);
                syncPackageAjaxTotal($form);
                return true;
        }

        function initPackageDealBookingForm($form, packageData) {
                var packageRefreshTimer = null;
                var refreshPackage;
                var schedulePackageRefresh;

                $form
                        .addClass('hr-package-booking-form')
                        .data('hr-package-data', packageData);

                bindPackageCalendarRangeHooks($form);
                hidePackageAjaxTotal($form);
                hidePackageSpecialRequestField($form);
                hidePackageGuestRateDisplay($form);
                renderPackageSelectors($form, packageData);
                syncPackageDateRange($form, packageData);
                updatePackageSelection($form, packageData);
                schedulePackageAjaxTotalSync($form);
                schedulePackageSpecialRequestHide($form);
                schedulePackageGuestRateDisplayHide($form);

                refreshPackage = function (triggerRecalc) {
                        hidePackageSpecialRequestField($form);
                        hidePackageGuestRateDisplay($form);
                        syncPackageDateRange($form, packageData);
                        updatePackageSelection($form, packageData);

                        if (triggerRecalc) {
                                triggerBookingRecalculation($form);
                                schedulePackageAjaxTotalSync($form);
                                schedulePackageSpecialRequestHide($form);
                                schedulePackageGuestRateDisplayHide($form);
                        }
                };

                schedulePackageRefresh = function (triggerRecalc) {
                        window.clearTimeout(packageRefreshTimer);
                        packageRefreshTimer = window.setTimeout(function () {
                                refreshPackage(triggerRecalc);
                        }, 0);
                };

                $form.on('change input', '.hr-package-select, ' + GUEST_INPUT_SELECTOR, function () {
                        schedulePackageRefresh(true);
                        schedulePackageGuestRateDisplayHide($form);
                });

                $form.on('change input blur', PACKAGE_START_DATE_INPUT_SELECTOR, function () {
                        setActivePackageCalendarRangeForm($form);
                        schedulePackageDateRangeSync($form, packageData);
                });

                $form.on('submit', function () {
                        restorePackageStartDateInput($form);
                });

                $form.on('click', '.ovabrw-wrapper-guestspicker .guests-icon', function () {
                        schedulePackageRefresh(true);
                        schedulePackageGuestRateDisplayHide($form);

                        window.setTimeout(function () {
                                refreshPackage(true);
                        }, 50);
                });
        }

        function resetSelection($field) {
                $field.find('input[name="hr_selected_hotel_id"]').val('');
                $field.find('input[name="hr_selected_hotel_name"]').val('');
                $field.find('input[name="hr_selected_hotel_label"]').val('');
                $field.find('input[name="hr_selected_pax"]').val('');
                $field.find('input[name="hr_selected_price"]').val('');
        }

        function syncSelection($form) {
                var $field = $form.find('.hr-booking-rate-field');
                var $select = $field.find('.hr-booking-rate-select');
                var $selected = $select.find('option:selected');
                var price = parseFloat($selected.attr('data-price'));
                var pax = parseInt($selected.attr('data-pax'), 10);

                resetSelection($field);

                if (!$selected.val()) {
                        return;
                }

                $field.find('input[name="hr_selected_hotel_id"]').val($selected.attr('data-hotel-id') || '');
                $field.find('input[name="hr_selected_hotel_name"]').val($selected.attr('data-hotel-name') || '');
                $field.find('input[name="hr_selected_hotel_label"]').val($selected.text() || '');
                $field.find('input[name="hr_selected_pax"]').val(Number.isNaN(pax) ? '' : pax);
                $field.find('input[name="hr_selected_price"]').val(Number.isNaN(price) ? '' : price);
        }

        function renderOptions($form, sourceRows) {
                var $field = $form.find('.hr-booking-rate-field');
                var $select = $field.find('.hr-booking-rate-select');
                var $help = $field.find('.hr-booking-rate-help');
                var currentRateId = $select.val();
                var guestCount = getGuestCount($form);
                var availableCount = 0;
                var selectionStillValid = false;

                $select.empty().append(
                        $('<option>', {
                                value: '',
                                text: window.hrBooking ? window.hrBooking.selectPlaceholder : 'Select a hotel package'
                        })
                );

                if (!guestCount) {
                        $field.prop('hidden', false);
                        $select.prop('disabled', true).removeClass('ovabrw-input-required');
                        $help.text(window.hrBooking ? window.hrBooking.chooseGuests : '');
                        resetSelection($field);
                        return false;
                }

                sourceRows.forEach(function (row) {
                        var price = row.prices ? row.prices[String(guestCount)] : null;

                        if (price === null || price === '' || Number.isNaN(Number(price))) {
                                return;
                        }

                        availableCount += 1;

                        $select.append(
                                $('<option>', {
                                        value: row.rateId,
                                        text: row.bookingLabel + ' - ' + formatPrice(price),
                                        selected: currentRateId === row.rateId
                                })
                                .attr('data-hotel-id', row.hotelId)
                                .attr('data-hotel-name', row.hotelName)
                                .attr('data-price', price)
                                .attr('data-pax', guestCount)
                        );

                        if (currentRateId === row.rateId) {
                                selectionStillValid = true;
                        }
                });

                $field.prop('hidden', false);

                if (!availableCount) {
                        $select.prop('disabled', true).removeClass('ovabrw-input-required');
                        $help.text(window.hrBooking ? window.hrBooking.noRates : '');
                        resetSelection($field);
                        return false;
                }

                if (!selectionStillValid) {
                        $select.val('');
                }

                $select.prop('disabled', false).addClass('ovabrw-input-required');

                if ($select.val()) {
                        var $selected = $select.find('option:selected');
                        $help.text(
                                (window.hrBooking ? window.hrBooking.priceLabel : 'Adds %s to this booking.')
                                        .replace('%s', formatPrice($selected.attr('data-price')))
                        );
                } else {
                        $help.text('');
                }

                syncSelection($form);

                return true;
        }

        function initBookingField($form, sourceRows) {
                var previousState = '';
                var $field = $form.find('.hr-booking-rate-field');

                if (!$field.length || !sourceRows.length) {
                        return;
                }

                function refresh(triggerRecalc) {
                        var available = renderOptions($form, sourceRows);
                        var state = [
                                $field.find('select').val(),
                                $field.find('input[name="hr_selected_price"]').val(),
                                $field.find('input[name="hr_selected_pax"]').val()
                        ].join('|');

                        if (!available) {
                                state = 'none';
                        }

                        if (triggerRecalc && previousState !== '' && previousState !== state) {
                                triggerBookingRecalculation($form);
                        }

                        previousState = state;
                }

                refresh(false);

                $form.on('change', '.hr-booking-rate-select', function () {
                        syncSelection($form);

                        var $selected = $(this).find('option:selected');
                        $field.find('.hr-booking-rate-help').text(
                                $selected.val()
                                        ? (window.hrBooking ? window.hrBooking.priceLabel : 'Adds %s to this booking.').replace('%s', formatPrice($selected.attr('data-price')))
                                        : ''
                        );

                        previousState = [
                                $(this).val(),
                                $field.find('input[name="hr_selected_price"]').val(),
                                $field.find('input[name="hr_selected_pax"]').val()
                        ].join('|');

                        triggerBookingRecalculation($form);
                });

                $form.on('change', GUEST_INPUT_SELECTOR, function () {
                        refresh(true);
                });

                $form.on('click', '.ovabrw-wrapper-guestspicker .guests-icon', function () {
                        window.setTimeout(function () {
                                refresh(true);
                        }, 0);
                });
        }

        $(function () {
                        installSelfDriveQuantityHandlers();

                        var sourceRows = getSourceRows();
                        var $forms = getBookingForms();

                        $forms.each(function () {
                        var $form = $(this);
                        var packageData = getPackageData($form);
                                var airportTransferData = getAirportTransferData($form);
                                var airportTransferRefreshTimer = null;
                                var motorbikeData = getMotorbikeData($form);
                                var motorbikeRefreshTimer = null;
                                var boatData = getBoatData($form);
                                var boatRefreshTimer = null;
                                var selfDriveData = getSelfDriveData($form);
                                var selfDriveRefreshTimer = null;
                                var destinationTourData = getDestinationTourData($form);
                                var destinationTourRefreshTimer = null;
                                var pickupDropoffData = getPickupDropoffData($form);
                                var pickupDropoffRefreshTimer = null;
                                var portTransferData = getPortTransferData($form);
                                var portTransferRefreshTimer = null;
                                var tourData = getTourData($form);
                                var tourRefreshTimer = null;
                                var refreshAirportTransfer;
                                var scheduleAirportTransferRefresh;
                                var refreshMotorbike;
                                var scheduleMotorbikeRefresh;
                                var refreshBoat;
                                var scheduleBoatRefresh;
                                var refreshSelfDrive;
                                var scheduleSelfDriveRefresh;
                                var refreshDestinationTour;
                                var scheduleDestinationTourRefresh;
                                var refreshPickupDropoff;
                                var schedulePickupDropoffRefresh;
                                var refreshPortTransfer;
                                var schedulePortTransferRefresh;
                        var refreshTour;
                        var scheduleTourRefresh;

                        scheduleArrivalTimeLabelSync($form);
                        $form.on('submit', function () {
                                labelArrivalTimeField($form);
                                syncArrivalTimeCustomFieldConfig($form);
                        });

                        if (packageData) {
                                        initPackageDealBookingForm($form, packageData);
                                        return;
                                }

                                if (airportTransferData) {
                                        hideAirportTransferAjaxTotal($form);
                                        renderAirportTransferSelectors($form, airportTransferData);
                                        hideLegacyAirportTransferFields($form);
                                        scheduleLegacyAirportTransferFieldHide($form);
                                        updateAirportTransferSelection($form, airportTransferData);
                                        scheduleAirportTransferAjaxTotalSync($form);

                                        refreshAirportTransfer = function (triggerRecalc) {
                                                hideLegacyAirportTransferFields($form);
                                                updateAirportTransferSelection($form, airportTransferData);
                                                if (triggerRecalc) {
                                                        triggerBookingRecalculation($form);
                                                        scheduleAirportTransferAjaxTotalSync($form);
                                                }
                                        };

                                        scheduleAirportTransferRefresh = function (triggerRecalc) {
                                                window.clearTimeout(airportTransferRefreshTimer);
                                                airportTransferRefreshTimer = window.setTimeout(function () {
                                                        refreshAirportTransfer(triggerRecalc);
                                                }, 0);
                                        };

                                        $form.on('change input', '.hr-airport-transfer-direction-select, .hr-airport-transfer-route-select, ' + GUEST_INPUT_SELECTOR, function () {
                                                scheduleAirportTransferRefresh(true);
                                        });

                                        $form.on('click', '.ovabrw-wrapper-guestspicker .guests-icon', function () {
                                                scheduleAirportTransferRefresh(true);

                                                window.setTimeout(function () {
                                                        refreshAirportTransfer(true);
                                                }, 50);
                                        });

                                        return;
                                }

                                if (boatData) {
                                        hideBoatAjaxTotal($form);
                                        renderBoatSelectors($form, boatData);
                                        scheduleBoatFormSync($form);
                                        updateBoatSelection($form, boatData);
                                        scheduleBoatAjaxTotalSync($form);

                                        if ($form[0] && !$form.data('hr-boat-validation-capture-bound')) {
                                                $form.data('hr-boat-validation-capture-bound', true);

                                                $form[0].addEventListener('click', function (event) {
                                                        if ($(event.target).closest('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit').length) {
                                                                handleBoatValidationAttempt($form, boatData, event);
                                                        }
                                                }, true);

                                                $form[0].addEventListener('submit', function (event) {
                                                        handleBoatValidationAttempt($form, boatData, event);
                                                }, true);
                                        }

                                        refreshBoat = function (triggerRecalc) {
                                                hideLegacyBoatFields($form);
                                                updateBoatSelection($form, boatData);
                                                if (triggerRecalc) {
                                                        scheduleBoatAjaxTotalSync($form);
                                                        scheduleBoatBaseValidationClear($form);
                                                }
                                        };

                                        scheduleBoatRefresh = function (triggerRecalc) {
                                                window.clearTimeout(boatRefreshTimer);
                                                boatRefreshTimer = window.setTimeout(function () {
                                                        refreshBoat(triggerRecalc);
                                                }, 0);
                                        };

                                        $form.on('click', '.hr-boat-passenger-plus, .hr-boat-passenger-minus', function (event) {
                                                event.preventDefault();
                                                event.stopPropagation();
                                                handleBoatPassengerButton($(this), boatData);
                                        });

                                        $form.on('change input', '.hr-boat-passenger-count, .hr-boat-rate-select, .hr-boat-boarding-time-select, .hr-boat-upgrade-fee-option', function () {
                                                scheduleBoatRefresh(true);
                                        });

                                        $form.on('change input blur', PACKAGE_START_DATE_INPUT_SELECTOR, function () {
                                                scheduleBoatRefresh(true);
                                        });

                                        $form.on('click', 'button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit', function (event) {
                                                return handleBoatValidationAttempt($form, boatData, event);
                                        });

                                        $form.on('submit', function (event) {
                                                return handleBoatValidationAttempt($form, boatData, event);
                                        });

                                        return;
                                }

                                if (motorbikeData) {
                                        hideMotorbikeAjaxTotal($form);
                                        renderMotorbikeSelectors($form, motorbikeData);
                                        scheduleMotorbikeFormSync($form);
                                        updateMotorbikeSelection($form, motorbikeData);
                                        scheduleMotorbikeAjaxTotalSync($form);
                                        installMotorbikeDebugObserver($form);
                                        dumpMotorbikeDebugState($form, 'motorbike init after render');

                                        if ($form[0] && !$form.data('hr-motorbike-validation-capture-bound')) {
                                                $form.data('hr-motorbike-validation-capture-bound', true);

                                                $form[0].addEventListener('click', function (event) {
                                                        if ($(event.target).closest('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit').length) {
                                                                prepareMotorbikeValidationState($form, motorbikeData);
                                                        }
                                                }, true);

                                                $form[0].addEventListener('submit', function () {
                                                        prepareMotorbikeValidationState($form, motorbikeData);
                                                }, true);
                                        }

                                        refreshMotorbike = function (triggerRecalc) {
                                                labelMotorbikeDateField($form);
                                                hideLegacyMotorbikeFields($form);
                                                updateMotorbikeSelection($form, motorbikeData);
                                                if (triggerRecalc) {
                                                        scheduleMotorbikeAjaxTotalSync($form);
                                                }
                                        };

                                        scheduleMotorbikeRefresh = function (triggerRecalc) {
                                                window.clearTimeout(motorbikeRefreshTimer);
                                                motorbikeRefreshTimer = window.setTimeout(function () {
                                                        refreshMotorbike(triggerRecalc);
                                                }, 0);
                                        };

                                        $form.on('click', '.hr-motorbike-qty-plus, .hr-motorbike-qty-minus', function () {
                                                var $button = $(this);
                                                var $row = $button.closest('.hr-motorbike-model-row');
                                                var $qty = $row.find('.hr-motorbike-model-qty');
                                                var current = parseInt($qty.val(), 10) || 0;
                                                var stock = parseInt($row.data('stock'), 10) || 0;

                                                if ($button.hasClass('hr-motorbike-qty-plus')) {
                                                        current += 1;
                                                } else {
                                                        current -= 1;
                                                }

                                                current = Math.max(0, Math.min(current, stock));
                                                $qty.val(String(current)).trigger('change');
                                        });

                                        $form.on('change input', '.hr-motorbike-model-qty, .hr-motorbike-start-time-select, .hr-motorbike-days-select, .hr-motorbike-handover-option, .hr-motorbike-delivery-address', function () {
                                                scheduleMotorbikeRefresh(true);
                                        });

                                        $form.on('click', 'button[type="submit"], input[type="submit"], .single_add_to_cart_button', function () {
                                                prepareMotorbikeValidationState($form, motorbikeData);
                                        });

                                        $form.on('submit', function () {
                                                prepareMotorbikeValidationState($form, motorbikeData);
                                        });

                                        return;
                                }

                                if (selfDriveData) {
                                        hideSelfDriveAjaxTotal($form);
                                        dumpSelfDriveDebugState($form, 'self-drive init before render');
                                        renderSelfDriveSelectors($form, selfDriveData);
                                        ensureSelfDriveReturnDateField($form);
                                        rememberSelfDriveRentalDateValue($form);
                                        installSelfDriveRentalDateWatcher($form);
                                        scheduleSelfDriveFormSync($form);
                                        updateSelfDriveSelection($form, selfDriveData);
                                        scheduleSelfDriveAjaxTotalSync($form);
                                        installSelfDriveDebugObserver($form);
                                        installSelfDriveValidationObserver($form);
                                        dumpSelfDriveDebugState($form, 'self-drive init complete');

                                        if ($form[0] && !$form.data('hr-self-drive-validation-capture-bound')) {
                                                $form.data('hr-self-drive-validation-capture-bound', true);

                                                $form[0].addEventListener('click', function (event) {
                                                        if ($(event.target).closest('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit').length) {
                                                                handleSelfDriveValidationAttempt($form, selfDriveData, event);
                                                        }
                                                }, true);

                                                $form[0].addEventListener('submit', function (event) {
                                                        handleSelfDriveValidationAttempt($form, selfDriveData, event);
                                                }, true);
                                        }

                                        refreshSelfDrive = function (triggerRecalc) {
                                                dumpSelfDriveDebugState($form, 'before refresh self-drive');
                                                labelSelfDriveDateField($form);
                                                ensureSelfDriveReturnDateField($form);
                                                clearSelfDriveReturnDateIfRentalDateChanged($form);
                                                hideLegacySelfDriveFields($form);
                                                updateSelfDriveSelection($form, selfDriveData);
                                                if (triggerRecalc) {
                                                        scheduleSelfDriveAjaxTotalSync($form);
                                                }
                                                dumpSelfDriveDebugState($form, 'after refresh self-drive');
                                        };

                                        scheduleSelfDriveRefresh = function (triggerRecalc) {
                                                window.clearTimeout(selfDriveRefreshTimer);
                                                selfDriveRefreshTimer = window.setTimeout(function () {
                                                        refreshSelfDrive(triggerRecalc);
                                                }, 0);
                                        };

                                        $form.on('click', '.hr-self-drive-qty-plus, .hr-self-drive-qty-minus, .hr-self-drive-guest-plus, .hr-self-drive-guest-minus', function (event) {
                                                event.preventDefault();
                                                event.stopPropagation();
                                                handleSelfDriveQuantityButton($(this), selfDriveData);
                                        });

                                        $form.on('change input', '.hr-self-drive-vehicle-qty, .hr-self-drive-guest-count, .hr-self-drive-hotel-name-input, .hr-self-drive-start-time-select, .hr-self-drive-return-time-select, .hr-self-drive-return-date-input, .hr-self-drive-handover-option, .hr-self-drive-delivery-address', function () {
                                                scheduleSelfDriveRefresh(true);
                                        });

                                        $form.on('change input blur', PACKAGE_START_DATE_INPUT_SELECTOR, function () {
                                                clearSelfDriveReturnDateIfRentalDateChanged($form);
                                                scheduleSelfDriveRefresh(true);
                                        });

                                        $form.on('click', 'button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit', function (event) {
                                                return handleSelfDriveValidationAttempt($form, selfDriveData, event);
                                        });

                                        $form.on('submit', function (event) {
                                                return handleSelfDriveValidationAttempt($form, selfDriveData, event);
                                        });

                                        return;
                                }

                                if (destinationTourData) {
                                        hideDestinationTourAjaxTotal($form);
                                        renderDestinationTourSelectors($form, destinationTourData);
                                        scheduleDestinationTourFormSync($form);
                                        updateDestinationTourSelection($form, destinationTourData);
                                        scheduleDestinationTourAjaxTotalSync($form);

                                        if ($form[0] && !$form.data('hr-destination-tour-validation-capture-bound')) {
                                                $form.data('hr-destination-tour-validation-capture-bound', true);

                                                $form[0].addEventListener('click', function (event) {
                                                        if ($(event.target).closest('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit').length) {
                                                                handleDestinationTourValidationAttempt($form, destinationTourData, event);
                                                        }
                                                }, true);

                                                $form[0].addEventListener('submit', function (event) {
                                                        handleDestinationTourValidationAttempt($form, destinationTourData, event);
                                                }, true);
                                        }

                                        refreshDestinationTour = function (triggerRecalc) {
                                                hideLegacyDestinationTourFields($form);
                                                updateDestinationTourSelection($form, destinationTourData);
                                                if (triggerRecalc) {
                                                        scheduleDestinationTourAjaxTotalSync($form);
                                                        scheduleDestinationTourBaseValidationClear($form);
                                                }
                                        };

                                        scheduleDestinationTourRefresh = function (triggerRecalc) {
                                                window.clearTimeout(destinationTourRefreshTimer);
                                                destinationTourRefreshTimer = window.setTimeout(function () {
                                                        refreshDestinationTour(triggerRecalc);
                                                }, 0);
                                        };

                                        $form.on('click', '.hr-destination-tour-guest-plus, .hr-destination-tour-guest-minus', function (event) {
                                                event.preventDefault();
                                                event.stopPropagation();
                                                handleDestinationTourGuestButton($(this), destinationTourData);
                                        });

                                        $form.on('change input', '.hr-destination-tour-date-input, .hr-destination-tour-guest-count, .hr-destination-tour-hotel-input, .hr-destination-tour-pickup-time-select, .hr-destination-tour-destination-select, .hr-destination-tour-pickup-addon-select', function () {
                                                scheduleDestinationTourRefresh(true);
                                        });

                                        $form.on('change input blur', PACKAGE_START_DATE_INPUT_SELECTOR, function () {
                                                scheduleDestinationTourRefresh(true);
                                        });

                                        $form.on('click', 'button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit', function (event) {
                                                return handleDestinationTourValidationAttempt($form, destinationTourData, event);
                                        });

                                        $form.on('submit', function (event) {
                                                return handleDestinationTourValidationAttempt($form, destinationTourData, event);
                                        });

                                        return;
                                }

                                if (pickupDropoffData) {
                                        hidePickupDropoffAjaxTotal($form);
                                        initPickupDropoffDateInputs($form);
                                        schedulePickupDropoffFormSync($form);
                                        updatePickupDropoffSelection($form, pickupDropoffData);
                                        schedulePickupDropoffAjaxTotalSync($form);

                                        if ($form[0] && !$form.data('hr-pickup-dropoff-validation-capture-bound')) {
                                                $form.data('hr-pickup-dropoff-validation-capture-bound', true);

                                                $form[0].addEventListener('click', function (event) {
                                                        if ($(event.target).closest('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit').length) {
                                                                handlePickupDropoffValidationAttempt($form, pickupDropoffData, event);
                                                        }
                                                }, true);

                                                $form[0].addEventListener('submit', function (event) {
                                                        handlePickupDropoffValidationAttempt($form, pickupDropoffData, event);
                                                }, true);
                                        }

                                        refreshPickupDropoff = function (triggerRecalc) {
                                                initPickupDropoffDateInputs($form);
                                                hideLegacyPickupDropoffFields($form);
                                                updatePickupDropoffSelection($form, pickupDropoffData);
                                                if (triggerRecalc) {
                                                        schedulePickupDropoffAjaxTotalSync($form);
                                                        schedulePickupDropoffBaseValidationClear($form);
                                                }
                                        };

                                        schedulePickupDropoffRefresh = function (triggerRecalc) {
                                                window.clearTimeout(pickupDropoffRefreshTimer);
                                                pickupDropoffRefreshTimer = window.setTimeout(function () {
                                                        refreshPickupDropoff(triggerRecalc);
                                                }, 0);
                                        };

                                        $form.on('click', '.hr-pickup-dropoff-passenger-plus, .hr-pickup-dropoff-passenger-minus', function (event) {
                                                event.preventDefault();
                                                event.stopPropagation();
                                                handlePickupDropoffPassengerButton($(this), pickupDropoffData);
                                        });

                                        $form.on('click', '.hr-pickup-dropoff-swap-button', function (event) {
                                                event.preventDefault();
                                                event.stopPropagation();
                                                handlePickupDropoffSwapButton($(this), pickupDropoffData);
                                        });

                                        $form.on('change input', '.hr-pickup-dropoff-date-input, .hr-pickup-dropoff-passenger-count, .hr-pickup-dropoff-from-select, .hr-pickup-dropoff-to-select, .hr-pickup-dropoff-pickup-time-select, .hr-pickup-dropoff-hotel-input, .hr-pickup-dropoff-addon-option', function () {
                                                schedulePickupDropoffRefresh(true);
                                        });

                                        $form.on('click', 'button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit', function (event) {
                                                return handlePickupDropoffValidationAttempt($form, pickupDropoffData, event);
                                        });

                                        $form.on('submit', function (event) {
                                                return handlePickupDropoffValidationAttempt($form, pickupDropoffData, event);
                                        });

                                        return;
                                }

                                if (portTransferData) {
                                        hidePortTransferAjaxTotal($form);
                                        renderPortTransferSelectors($form, portTransferData);
                                        schedulePortTransferFormSync($form);
                                        updatePortTransferSelection($form, portTransferData);
                                        schedulePortTransferAjaxTotalSync($form);

                                        if ($form[0] && !$form.data('hr-port-transfer-validation-capture-bound')) {
                                                $form.data('hr-port-transfer-validation-capture-bound', true);

                                                $form[0].addEventListener('click', function (event) {
                                                        if ($(event.target).closest('button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit').length) {
                                                                handlePortTransferValidationAttempt($form, portTransferData, event);
                                                        }
                                                }, true);

                                                $form[0].addEventListener('submit', function (event) {
                                                        handlePortTransferValidationAttempt($form, portTransferData, event);
                                                }, true);
                                        }

                                        refreshPortTransfer = function (triggerRecalc) {
                                                hideLegacyPortTransferFields($form);
                                                updatePortTransferSelection($form, portTransferData);
                                                if (triggerRecalc) {
                                                        schedulePortTransferAjaxTotalSync($form);
                                                        schedulePortTransferBaseValidationClear($form);
                                                }
                                        };

                                        schedulePortTransferRefresh = function (triggerRecalc) {
                                                window.clearTimeout(portTransferRefreshTimer);
                                                portTransferRefreshTimer = window.setTimeout(function () {
                                                        refreshPortTransfer(triggerRecalc);
                                                }, 0);
                                        };

                                        $form.on('click', '.hr-port-transfer-passenger-plus, .hr-port-transfer-passenger-minus', function (event) {
                                                event.preventDefault();
                                                event.stopPropagation();
                                                handlePortTransferPassengerButton($(this), portTransferData);
                                        });

                                        $form.on('click', '.hr-port-transfer-swap-button', function (event) {
                                                event.preventDefault();
                                                event.stopPropagation();
                                                handlePortTransferSwapButton($(this), portTransferData);
                                        });

                                        $form.on('change', '.hr-port-transfer-from-select', function () {
                                                syncPortTransferReturnDropoffFromFrom($form.find('.hr-port-transfer-booking-field'), true);
                                        });

                                        $form.on('change input', '.hr-port-transfer-passenger-count, .hr-port-transfer-type-select, .hr-port-transfer-from-select, .hr-port-transfer-to-select, .hr-port-transfer-date-input, .hr-port-transfer-time-input, .hr-port-transfer-flight-input, .hr-port-transfer-hotel-input, .hr-port-transfer-return-date-input, .hr-port-transfer-return-time-select, .hr-port-transfer-return-dropoff-select, .hr-port-transfer-addon-option', function () {
                                                schedulePortTransferRefresh(true);
                                        });

                                        $form.on('change input blur', PACKAGE_START_DATE_INPUT_SELECTOR, function () {
                                                schedulePortTransferRefresh(true);
                                        });

                                        $form.on('click', 'button[type="submit"], input[type="submit"], .single_add_to_cart_button, .booking-form-submit', function (event) {
                                                return handlePortTransferValidationAttempt($form, portTransferData, event);
                                        });

                                        $form.on('submit', function (event) {
                                                return handlePortTransferValidationAttempt($form, portTransferData, event);
                                        });

                                        return;
                                }

                                if (!tourData) {
                                        return;
                                }

                                if (isDayTourData(tourData)) {
                                        initDayTourBookingForm($form, tourData);
                                        return;
                                }

                                if (isJoinerTourData(tourData)) {
                                        initJoinerTourBookingForm($form, tourData);
                                        return;
                                }

                                $form.data('hr-tour-data', tourData);
                                hideTourAjaxTotal($form);
                                renderTourAddonSelectors($form, tourData);
                                syncTourFreeGuestRows($form);
                                bindTourFreeGuestButtonCapture($form);
                                bindTourFreeGuestRowsObserver($form);
                                updateTourSelection($form, tourData);
                                scheduleTourFreeGuestRowsSync($form);
                                scheduleTourAjaxTotalSync($form);

                                refreshTour = function (triggerRecalc) {
                                        syncTourFreeGuestRows($form);
                                        updateTourSelection($form, tourData);
                                        if (triggerRecalc) {
                                                triggerBookingRecalculation($form);
                                                scheduleTourFreeGuestRowsSync($form);
                                                scheduleTourAjaxTotalSync($form);
                                        }
                                };

                                scheduleTourRefresh = function (triggerRecalc) {
                                        window.clearTimeout(tourRefreshTimer);
                                        tourRefreshTimer = window.setTimeout(function () {
                                                refreshTour(triggerRecalc);
                                        }, 0);
                                };

                                $form.on('change input', '.hr-tour-addon-select, ' + GUEST_INPUT_SELECTOR, function () {
                                        scheduleTourRefresh(true);
                                        scheduleTourFreeGuestRowsSync($form);
                                });

                                $form.on('click', '.hr-tour-child-custom-row .guests-icon', function (event) {
                                        var $input = $(this).closest('.hr-tour-child-custom-row').find('.hr-tour-child-count').first();
                                        var value = parseInt($input.val(), 10);

                                        event.preventDefault();
                                        event.stopPropagation();

                                        value = Number.isNaN(value) ? 0 : value;

                                        if ($(this).hasClass('minus')) {
                                                value = Math.max(0, value - 1);
                                        } else if ($(this).hasClass('plus')) {
                                                value += 1;
                                        }

                                        $input.val(String(value)).trigger('change');
                                        scheduleTourRefresh(true);
                                });

                                $form.on('change input', '.hr-tour-child-count', function () {
                                        syncTourChildrenHidden($form);
                                        scheduleTourRefresh(true);
                                        scheduleTourFreeGuestRowsSync($form);
                                });

                                $form.on('click', '.hr-tour-infant-custom-row .guests-icon', function (event) {
                                        var $input = $(this).closest('.hr-tour-infant-custom-row').find('.hr-tour-infant-count').first();
                                        var value = parseInt($input.val(), 10);

                                        event.preventDefault();
                                        event.stopPropagation();

                                        value = Number.isNaN(value) ? 0 : value;

                                        if ($(this).hasClass('minus')) {
                                                value = Math.max(0, value - 1);
                                        } else if ($(this).hasClass('plus')) {
                                                value += 1;
                                        }

                                        $input.val(String(value)).trigger('change');
                                        scheduleTourRefresh(true);
                                });

                                $form.on('change input', '.hr-tour-infant-count', function () {
                                        syncTourInfantHidden($form);
                                        scheduleTourRefresh(true);
                                        scheduleTourFreeGuestRowsSync($form);
                                });

                                $form.on('change input blur', PACKAGE_START_DATE_INPUT_SELECTOR, function () {
                                        scheduleTourRefresh(true);
                                        scheduleTourFreeGuestRowsSync($form);
                                });

                                $form.on('click', '.ovabrw-wrapper-guestspicker .guests-icon', function () {
                                        scheduleTourRefresh(true);
                                        scheduleTourFreeGuestRowsSync($form);

                                        window.setTimeout(function () {
                                                refreshTour(true);
                                        }, 50);
                                });
                        });

                        if (!sourceRows.length) {
                                return;
                        }

                        $forms.each(function () {
                                initBookingField($(this), sourceRows);
                        });
        });
})(jQuery);
