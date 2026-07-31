/* Hotel Rates Plugin — Admin JS */
(function ($) {
        'use strict';

        /* ── Cascading Dropdowns ─────────────────────────────────────────── */

        var $region = $('#hr-region');
        var $place  = $('#hr-place');
        var $hotel  = $('#hr-hotel');

        if ( $region.length ) {
                $region.on('change', function () {
                        var regionId = $(this).val();
                        $place.html('<option>Loading…</option>');
                        if ( $hotel.length ) $hotel.html('');

                        $.post(hrAjax.ajaxurl, {
                                action   : 'hr_get_places',
                                nonce    : hrAjax.nonce,
                                region_id: regionId
                        }, function (res) {
                                $place.html('');
                                if ( res.success && res.data.length ) {
                                        $.each(res.data, function (i, p) {
                                                $place.append('<option value="' + p.id + '">' + p.name + '</option>');
                                        });
                                        $place.trigger('change');
                                } else {
                                        $place.html('<option value="">No places found</option>');
                                        if ( $hotel.length ) $hotel.html('<option value="">No hotels found</option>');
                                }
                        });
                });

                $place.on('change', function () {
                        var placeId = $(this).val();
                        if ( !$hotel.length ) return;
                        $hotel.html('<option>Loading…</option>');

                        $.post(hrAjax.ajaxurl, {
                                action  : 'hr_get_hotels',
                                nonce   : hrAjax.nonce,
                                place_id: placeId
                        }, function (res) {
                                $hotel.html('');
                                if ( res.success && res.data.length ) {
                                        $.each(res.data, function (i, h) {
                                                $hotel.append('<option value="' + h.id + '">' + h.name + '</option>');
                                        });
                                } else {
                                        $hotel.html('<option value="">No hotels found</option>');
                                }
                        });
                });
        }

        /* Product Editor - Searchable Package/Tour Linking */

        function initProductLinkSearch() {
                var $selects = $('.hr-product-link-search');

                if ( !$selects.length ) {
                        return;
                }

                $selects.each(function () {
                        var $select = $(this);
                        var options = {
                                allowClear: true,
                                minimumResultsForSearch: 0,
                                placeholder: $select.data('placeholder') || '',
                                width: '100%'
                        };

                        if ( $select.data('select2') || $select.data('selectWoo') ) {
                                return;
                        }

                        if ( $.fn.selectWoo ) {
                                $select.selectWoo(options);
                        } else if ( $.fn.select2 ) {
                                $select.select2(options);
                        }
                });
        }

        initProductLinkSearch();
        $(document.body).on('wc-enhanced-select-init', initProductLinkSearch);

        /* Motorbike Rental Admin */

        function reindexMotorbikeModelRows() {
                $('.hr-motorbike-model-table tbody .hr-motorbike-model-row').each(function (index) {
                        var $row = $(this);

                        $row.find('.hr-motorbike-model-name').attr('name', 'models[' + index + '][name]');
                        $row.find('.hr-motorbike-model-rate').attr('name', 'models[' + index + '][rate]');
                        $row.find('.hr-motorbike-model-stock').attr('name', 'models[' + index + '][stock]');
                });
        }

        $(document).on('click', '.hr-motorbike-model-add', function () {
                var $tbody = $('.hr-motorbike-model-table tbody').first();
                var index = $tbody.find('.hr-motorbike-model-row').length;
                var html = '';

                html += '<tr class="hr-motorbike-model-row">';
                html += '<td><input type="text" name="models[' + index + '][name]" value="" class="widefat hr-motorbike-model-name" placeholder="Yamaha Fazzio" required></td>';
                html += '<td><input type="number" name="models[' + index + '][rate]" value="" class="widefat hr-motorbike-model-rate" step="0.01" min="0" placeholder="0.00" required></td>';
                html += '<td><input type="number" name="models[' + index + '][stock]" value="1" class="widefat hr-motorbike-model-stock" step="1" min="1" required></td>';
                html += '<td><button type="button" class="button-link-delete hr-motorbike-model-remove">Remove</button></td>';
                html += '</tr>';

                $tbody.append(html);
                reindexMotorbikeModelRows();
                $tbody.find('.hr-motorbike-model-row').last().find('.hr-motorbike-model-name').focus();
        });

        $(document).on('click', '.hr-motorbike-model-remove', function () {
                var $rows = $('.hr-motorbike-model-table tbody .hr-motorbike-model-row');

                if ( $rows.length <= 1 ) {
                        $(this).closest('.hr-motorbike-model-row').find('input').val('');
                        $(this).closest('.hr-motorbike-model-row').find('.hr-motorbike-model-stock').val('1');
                } else {
                        $(this).closest('.hr-motorbike-model-row').remove();
                }

                reindexMotorbikeModelRows();
        });

        /* ── Hotel Rates — Load Rates button ─────────────────────────────── */

        /* Self-Drive Rental Admin */

        function reindexSelfDriveVehicleRows() {
                $('.hr-self-drive-vehicle-table tbody .hr-self-drive-vehicle-row').each(function (index) {
                        var $row = $(this);

                        $row.find('.hr-self-drive-vehicle-name').attr('name', 'vehicles[' + index + '][name]');
                        $row.find('.hr-self-drive-vehicle-rate').attr('name', 'vehicles[' + index + '][rate]');
                        $row.find('.hr-self-drive-vehicle-stock').attr('name', 'vehicles[' + index + '][stock]');
                });
        }

        $(document).on('click', '.hr-self-drive-vehicle-add', function () {
                var $tbody = $('.hr-self-drive-vehicle-table tbody').first();
                var index = $tbody.find('.hr-self-drive-vehicle-row').length;
                var html = '';

                html += '<tr class="hr-self-drive-vehicle-row">';
                html += '<td><input type="text" name="vehicles[' + index + '][name]" value="" class="widefat hr-self-drive-vehicle-name" placeholder="Toyota Vios" required></td>';
                html += '<td><input type="number" name="vehicles[' + index + '][rate]" value="" class="widefat hr-self-drive-vehicle-rate" step="0.01" min="0" placeholder="0.00" required></td>';
                html += '<td><input type="number" name="vehicles[' + index + '][stock]" value="1" class="widefat hr-self-drive-vehicle-stock" step="1" min="1" required></td>';
                html += '<td><button type="button" class="button-link-delete hr-self-drive-vehicle-remove">Remove</button></td>';
                html += '</tr>';

                $tbody.append(html);
                reindexSelfDriveVehicleRows();
                $tbody.find('.hr-self-drive-vehicle-row').last().find('.hr-self-drive-vehicle-name').focus();
        });

        $(document).on('click', '.hr-self-drive-vehicle-remove', function () {
                var $rows = $('.hr-self-drive-vehicle-table tbody .hr-self-drive-vehicle-row');

                if ( $rows.length <= 1 ) {
                        $(this).closest('.hr-self-drive-vehicle-row').find('input').val('');
                        $(this).closest('.hr-self-drive-vehicle-row').find('.hr-self-drive-vehicle-stock').val('1');
                } else {
                        $(this).closest('.hr-self-drive-vehicle-row').remove();
                }

                reindexSelfDriveVehicleRows();
        });

        /* Boat Rental Admin */

        function reindexBoatRateRows() {
                $('.hr-boat-rate-table tbody .hr-boat-rate-row').each(function (index) {
                        var $row = $(this);

                        $row.find('.hr-boat-rate-label').attr('name', 'rates[' + index + '][label]');
                        $row.find('.hr-boat-rate-min-pax').attr('name', 'rates[' + index + '][min_pax]');
                        $row.find('.hr-boat-rate-max-pax').attr('name', 'rates[' + index + '][max_pax]');
                        $row.find('.hr-boat-rate-amount').attr('name', 'rates[' + index + '][rate]');
                });
        }

        $(document).on('click', '.hr-boat-rate-add', function () {
                var $tbody = $('.hr-boat-rate-table tbody').first();
                var index = $tbody.find('.hr-boat-rate-row').length;
                var html = '';

                html += '<tr class="hr-boat-rate-row">';
                html += '<td><input type="text" name="rates[' + index + '][label]" value="" class="widefat hr-boat-rate-label" placeholder="1-4 pax"></td>';
                html += '<td><input type="number" name="rates[' + index + '][min_pax]" value="1" class="widefat hr-boat-rate-min-pax" step="1" min="1" required></td>';
                html += '<td><input type="number" name="rates[' + index + '][max_pax]" value="4" class="widefat hr-boat-rate-max-pax" step="1" min="1" required></td>';
                html += '<td><input type="number" name="rates[' + index + '][rate]" value="" class="widefat hr-boat-rate-amount" step="0.01" min="0" placeholder="0.00" required></td>';
                html += '<td><button type="button" class="button-link-delete hr-boat-rate-remove">Remove</button></td>';
                html += '</tr>';

                $tbody.append(html);
                reindexBoatRateRows();
                $tbody.find('.hr-boat-rate-row').last().find('.hr-boat-rate-label').focus();
        });

        $(document).on('click', '.hr-boat-rate-remove', function () {
                var $rows = $('.hr-boat-rate-table tbody .hr-boat-rate-row');

                if ( $rows.length <= 1 ) {
                        $(this).closest('.hr-boat-rate-row').find('input').val('');
                        $(this).closest('.hr-boat-rate-row').find('.hr-boat-rate-min-pax').val('1');
                        $(this).closest('.hr-boat-rate-row').find('.hr-boat-rate-max-pax').val('4');
                } else {
                        $(this).closest('.hr-boat-rate-row').remove();
                }

                reindexBoatRateRows();
        });

        /* Destination Tour Admin */

        function reindexDestinationTourDestinationRows() {
                $('.hr-destination-tour-destination-table tbody .hr-destination-tour-destination-row').each(function (index) {
                        var $row = $(this);

                        $row.find('.hr-destination-tour-destination-label').attr('name', 'destinations[' + index + '][label]');
                        $row.find('.hr-destination-tour-destination-duration').attr('name', 'destinations[' + index + '][duration]');
                        $row.find('.hr-destination-tour-destination-rate').attr('name', 'destinations[' + index + '][rate]');
                });
        }

        function reindexDestinationTourAddonRows() {
                $('.hr-destination-tour-addon-table tbody .hr-destination-tour-addon-row').each(function (index) {
                        var $row = $(this);

                        $row.find('.hr-destination-tour-addon-label').attr('name', 'pickup_addons[' + index + '][label]');
                        $row.find('.hr-destination-tour-addon-rate').attr('name', 'pickup_addons[' + index + '][rate]');
                });
        }

        $(document).on('click', '.hr-destination-tour-destination-add', function () {
                var $tbody = $('.hr-destination-tour-destination-table tbody').first();
                var index = $tbody.find('.hr-destination-tour-destination-row').length;
                var html = '';

                html += '<tr class="hr-destination-tour-destination-row">';
                html += '<td><input type="text" name="destinations[' + index + '][label]" value="" class="widefat hr-destination-tour-destination-label" required></td>';
                html += '<td><input type="text" name="destinations[' + index + '][duration]" value="" class="widefat hr-destination-tour-destination-duration" placeholder="8 Hours"></td>';
                html += '<td><input type="number" name="destinations[' + index + '][rate]" value="" class="widefat hr-destination-tour-destination-rate" step="0.01" min="0" required></td>';
                html += '<td><button type="button" class="button-link-delete hr-destination-tour-destination-remove">Remove</button></td>';
                html += '</tr>';

                $tbody.append(html);
                reindexDestinationTourDestinationRows();
                $tbody.find('.hr-destination-tour-destination-row').last().find('.hr-destination-tour-destination-label').focus();
        });

        $(document).on('click', '.hr-destination-tour-destination-remove', function () {
                var $rows = $('.hr-destination-tour-destination-table tbody .hr-destination-tour-destination-row');

                if ($rows.length <= 1) {
                        $(this).closest('.hr-destination-tour-destination-row').find('input').val('');
                } else {
                        $(this).closest('.hr-destination-tour-destination-row').remove();
                }

                reindexDestinationTourDestinationRows();
        });

        $(document).on('click', '.hr-destination-tour-addon-add', function () {
                var $tbody = $('.hr-destination-tour-addon-table tbody').first();
                var index = $tbody.find('.hr-destination-tour-addon-row').length;
                var html = '';

                html += '<tr class="hr-destination-tour-addon-row">';
                html += '<td><input type="text" name="pickup_addons[' + index + '][label]" value="" class="widefat hr-destination-tour-addon-label" placeholder="Bacong Resorts"></td>';
                html += '<td><input type="number" name="pickup_addons[' + index + '][rate]" value="" class="widefat hr-destination-tour-addon-rate" step="0.01" min="0"></td>';
                html += '<td><button type="button" class="button-link-delete hr-destination-tour-addon-remove">Remove</button></td>';
                html += '</tr>';

                $tbody.append(html);
                reindexDestinationTourAddonRows();
                $tbody.find('.hr-destination-tour-addon-row').last().find('.hr-destination-tour-addon-label').focus();
        });

        $(document).on('click', '.hr-destination-tour-addon-remove', function () {
                var $rows = $('.hr-destination-tour-addon-table tbody .hr-destination-tour-addon-row');

                if ($rows.length <= 1) {
                        $(this).closest('.hr-destination-tour-addon-row').find('input').val('');
                } else {
                        $(this).closest('.hr-destination-tour-addon-row').remove();
                }

                reindexDestinationTourAddonRows();
        });

        /* Port Transfer Admin */

        function escapePortTransferAdminAttr(value) {
                return String(value || '')
                        .replace(/&/g, '&amp;')
                        .replace(/"/g, '&quot;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
        }

        function getPortTransferLines($field) {
                var seen = {};
                var lines = [];

                String($field.val() || '').split(/\r\n|\r|\n/).forEach(function (line) {
                        line = $.trim(line);

                        if (!line || seen[line]) {
                                return;
                        }

                        seen[line] = true;
                        lines.push(line);
                });

                return lines;
        }

        function getPortTransferRateMap($table) {
                var map = {};

                $table.find('.hr-port-transfer-rate-row').each(function () {
                        var $row = $(this);
                        var from = String($row.find('.hr-port-transfer-rate-from').val() || $row.data('from') || '');
                        var to = String($row.find('.hr-port-transfer-rate-to').val() || $row.data('to') || '');

                        if (from && to) {
                                map[from + '\u0000' + to] = $row.find('.hr-port-transfer-rate-value').val();
                        }
                });

                return map;
        }

        function rebuildPortTransferRateMatrix() {
                var $table = $('.hr-port-transfer-rate-matrix-table').first();
                var $tbody = $table.find('tbody').first();
                var fromOptions = getPortTransferLines($('.hr-port-transfer-from-options').first());
                var toOptions = getPortTransferLines($('.hr-port-transfer-to-options').first());
                var existingRates = getPortTransferRateMap($table);
                var index = 0;
                var html = '';

                if (!$table.length || !$tbody.length) {
                        return;
                }

                fromOptions.forEach(function (from) {
                        toOptions.forEach(function (to) {
                                var key = from + '\u0000' + to;
                                var value = existingRates[key] || '';

                                html += '<tr class="hr-port-transfer-rate-row" data-from="' + escapePortTransferAdminAttr(from) + '" data-to="' + escapePortTransferAdminAttr(to) + '">';
                                html += '<td><strong>' + escapePortTransferAdminAttr(from) + '</strong><input type="hidden" name="rates[' + index + '][from]" value="' + escapePortTransferAdminAttr(from) + '" class="hr-port-transfer-rate-from"></td>';
                                html += '<td><strong>' + escapePortTransferAdminAttr(to) + '</strong><input type="hidden" name="rates[' + index + '][to]" value="' + escapePortTransferAdminAttr(to) + '" class="hr-port-transfer-rate-to"></td>';
                                html += '<td><input type="number" name="rates[' + index + '][rate]" value="' + escapePortTransferAdminAttr(value) + '" class="widefat hr-port-transfer-rate-value" step="0.01" min="0" placeholder="0.00" required></td>';
                                html += '</tr>';
                                index += 1;
                        });
                });

                $tbody.html(html);
        }

        function reindexPortTransferAddonRows() {
                $('.hr-port-transfer-addon-table tbody .hr-port-transfer-addon-row').each(function (index) {
                        var $row = $(this);

                        $row.find('.hr-port-transfer-addon-label').attr('name', 'addons[' + index + '][label]');
                        $row.find('.hr-port-transfer-addon-rate').attr('name', 'addons[' + index + '][rate]');
                });
        }

        $(document).on('click', '.hr-port-transfer-rate-matrix-refresh', function () {
                rebuildPortTransferRateMatrix();
        });

        $(document).on('blur', '.hr-port-transfer-from-options, .hr-port-transfer-to-options', function () {
                rebuildPortTransferRateMatrix();
        });

        $(document).on('click', '.hr-port-transfer-addon-add', function () {
                var $tbody = $('.hr-port-transfer-addon-table tbody').first();
                var index = $tbody.find('.hr-port-transfer-addon-row').length;
                var html = '';

                html += '<tr class="hr-port-transfer-addon-row">';
                html += '<td><input type="text" name="addons[' + index + '][label]" value="" class="widefat hr-port-transfer-addon-label" placeholder="Midnight Surcharge"></td>';
                html += '<td><input type="number" name="addons[' + index + '][rate]" value="" class="widefat hr-port-transfer-addon-rate" step="0.01" min="0" placeholder="0.00"></td>';
                html += '<td><button type="button" class="button-link-delete hr-port-transfer-addon-remove">Remove</button></td>';
                html += '</tr>';

                $tbody.append(html);
                reindexPortTransferAddonRows();
                $tbody.find('.hr-port-transfer-addon-row').last().find('.hr-port-transfer-addon-label').focus();
        });

        $(document).on('click', '.hr-port-transfer-addon-remove', function () {
                var $rows = $('.hr-port-transfer-addon-table tbody .hr-port-transfer-addon-row');

                if ($rows.length <= 1) {
                        $(this).closest('.hr-port-transfer-addon-row').find('input').val('');
                } else {
                        $(this).closest('.hr-port-transfer-addon-row').remove();
                }

                reindexPortTransferAddonRows();
        });

        function getPickupDropoffPairKey(from, to) {
                var pair = [String(from || ''), String(to || '')];

                pair.sort(function (a, b) {
                        return a.toLowerCase().localeCompare(b.toLowerCase());
                });

                return pair[0].toLowerCase() + '\u0000' + pair[1].toLowerCase();
        }

        function getPickupDropoffRateMap($table) {
                var map = {};

                $table.find('.hr-pickup-dropoff-rate-row').each(function () {
                        var $row = $(this);
                        var from = String($row.find('.hr-pickup-dropoff-rate-from').val() || $row.data('from') || '');
                        var to = String($row.find('.hr-pickup-dropoff-rate-to').val() || $row.data('to') || '');

                        if (from && to) {
                                map[getPickupDropoffPairKey(from, to)] = $row.find('.hr-pickup-dropoff-rate-value').val();
                        }
                });

                return map;
        }

        function rebuildPickupDropoffRateMatrix() {
                var $table = $('.hr-pickup-dropoff-rate-matrix-table').first();
                var $tbody = $table.find('tbody').first();
                var locations = getPortTransferLines($('.hr-pickup-dropoff-locations').first());
                var existingRates = getPickupDropoffRateMap($table);
                var index = 0;
                var html = '';

                if (!$table.length || !$tbody.length) {
                        return;
                }

                locations.forEach(function (from, fromIndex) {
                        locations.forEach(function (to, toIndex) {
                                var key;
                                var value;

                                if (toIndex <= fromIndex) {
                                        return;
                                }

                                key = getPickupDropoffPairKey(from, to);
                                value = existingRates[key] || '';

                                html += '<tr class="hr-pickup-dropoff-rate-row" data-from="' + escapePortTransferAdminAttr(from) + '" data-to="' + escapePortTransferAdminAttr(to) + '">';
                                html += '<td><strong>' + escapePortTransferAdminAttr(from) + '</strong><input type="hidden" name="route_rates[' + index + '][from]" value="' + escapePortTransferAdminAttr(from) + '" class="hr-pickup-dropoff-rate-from"></td>';
                                html += '<td><strong>' + escapePortTransferAdminAttr(to) + '</strong><input type="hidden" name="route_rates[' + index + '][to]" value="' + escapePortTransferAdminAttr(to) + '" class="hr-pickup-dropoff-rate-to"></td>';
                                html += '<td><input type="number" name="route_rates[' + index + '][rate]" value="' + escapePortTransferAdminAttr(value) + '" class="widefat hr-pickup-dropoff-rate-value" step="0.01" min="0" placeholder="0.00" required></td>';
                                html += '</tr>';
                                index += 1;
                        });
                });

                $tbody.html(html);
        }

        function reindexPickupDropoffAddonRows() {
                $('.hr-pickup-dropoff-addon-table tbody .hr-pickup-dropoff-addon-row').each(function (index) {
                        var $row = $(this);

                        $row.find('.hr-pickup-dropoff-addon-label').attr('name', 'addons[' + index + '][label]');
                        $row.find('.hr-pickup-dropoff-addon-rate').attr('name', 'addons[' + index + '][rate]');
                });
        }

        $(document).on('click', '.hr-pickup-dropoff-rate-matrix-refresh', function () {
                rebuildPickupDropoffRateMatrix();
        });

        $(document).on('blur', '.hr-pickup-dropoff-locations', function () {
                rebuildPickupDropoffRateMatrix();
        });

        $(document).on('click', '.hr-pickup-dropoff-addon-add', function () {
                var $tbody = $('.hr-pickup-dropoff-addon-table tbody').first();
                var index = $tbody.find('.hr-pickup-dropoff-addon-row').length;
                var html = '';

                if (!$tbody.length) {
                        return;
                }

                html += '<tr class="hr-pickup-dropoff-addon-row">';
                html += '<td><input type="text" name="addons[' + index + '][label]" value="" class="widefat hr-pickup-dropoff-addon-label" placeholder="Midnight Surcharge"></td>';
                html += '<td><input type="number" name="addons[' + index + '][rate]" value="" class="widefat hr-pickup-dropoff-addon-rate" step="0.01" min="0" placeholder="0.00"></td>';
                html += '<td><button type="button" class="button-link-delete hr-pickup-dropoff-addon-remove">Remove</button></td>';
                html += '</tr>';

                $tbody.append(html);
                reindexPickupDropoffAddonRows();
                $tbody.find('.hr-pickup-dropoff-addon-row').last().find('.hr-pickup-dropoff-addon-label').focus();
        });

        $(document).on('click', '.hr-pickup-dropoff-addon-remove', function () {
                var $rows = $('.hr-pickup-dropoff-addon-table tbody .hr-pickup-dropoff-addon-row');

                if ($rows.length <= 1) {
                        $(this).closest('.hr-pickup-dropoff-addon-row').find('input').val('');
                } else {
                        $(this).closest('.hr-pickup-dropoff-addon-row').remove();
                }

                reindexPickupDropoffAddonRows();
        });

        $('#hr-load-rates').on('click', function () {
                var regionId = $region.val();
                var placeId  = $place.val();
                var hotelId  = $hotel.val();
                if ( !hotelId ) { alert('Please select a hotel.'); return; }
                window.location.href = window.location.pathname +
                        '?page=hotel-rates&region_id=' + regionId +
                        '&place_id=' + placeId +
                        '&hotel_id=' + hotelId;
        });

        /* ── Night Toggle Checkboxes ─────────────────────────────────────── */

        $(document).on('change', '.hr-night-chk', function () {
                var night   = parseInt( $(this).val(), 10 );
                var $section = $('#hr-night-' + night);
                if ( $(this).is(':checked') ) {
                        $section.show();
                } else {
                        $section.hide();
                }
        });

        /* ── Night + / − Controls ────────────────────────────────────────── */

        $('#hr-night-add').on('click', function () {
                var $unchecked = $('.hr-night-chk:not(:checked)').first();
                if ( $unchecked.length ) {
                        $unchecked.prop('checked', true).trigger('change');
                }
        });

        $('#hr-night-rem').on('click', function () {
                var $checked = $('.hr-night-chk:checked').last();
                if ( $checked.length ) {
                        $checked.prop('checked', false).trigger('change');
                }
        });

        /* ── Per-Table Pax Column + / − ─────────────────────────────────── */

        $(document).on('click', '.hr-pax-add', function () {
                var $table      = $(this).closest('table');
                var $hidden     = $table.find('th.hr-pax-hidden').first();
                if ( $hidden.length ) {
                        var pax = $hidden.data('pax');
                        $table.find('[data-pax="' + pax + '"]').removeClass('hr-pax-hidden');
                }
        });

        $(document).on('click', '.hr-pax-rem', function () {
                var $table   = $(this).closest('table');
                var $visible = $table.find('th.hr-col-pax:not(.hr-pax-hidden)').last();
                if ( $visible.length ) {
                        var pax = $visible.data('pax');
                        $table.find('[data-pax="' + pax + '"]').addClass('hr-pax-hidden');
                }
        });

        /* ── Tour Rates — Load Rates button ─────────────────────────────── */

        $('#hr-load-tour-rates').on('click', function () {
                var regionId = $region.val();
                var placeId  = $place.val();
                var tourType = $('#hr-tour-type').val();
                if ( !placeId )  { alert('Please select a place.'); return; }
                if ( !tourType ) { alert('Please select a type.'); return; }
                window.location.href = window.location.pathname +
                        '?page=hotel-rates-tour&region_id=' + encodeURIComponent(regionId) +
                        '&place_id=' + encodeURIComponent(placeId) +
                        '&tour_type=' + encodeURIComponent(tourType);
        });

        /* ── Hotel Rates Modal ───────────────────────────────────────────── */

        var $modal   = $('#hr-rate-modal');
        var $overlay = $('#hr-overlay');

        function openModal()  { $modal.show(); $overlay.show(); $('body').css('overflow','hidden'); }
        function closeModal() { $modal.hide(); $overlay.hide(); $('body').css('overflow',''); }

        $(document).on('click', '.hr-modal-close, #hr-overlay', closeModal);

        $(document).on('click', '.hr-add-rate-btn', function () {
                var nights = $(this).data('nights');
                $('#hr-modal-title').text('Add ' + nights + 'N Rate');
                $('#hr-rate-id').val('');
                $('#hr-rate-nights').val(nights);
                $('#hr-meal-plan').val('X');
                for ( var p = 1; p <= 12; p++ ) { $('#hr-pax-' + p).val(''); }
                openModal();
        });

        $(document).on('click', '.hr-edit-rate-btn', function () {
                var $btn   = $(this);
                var nights = $btn.data('nights');
                $('#hr-modal-title').text('Edit ' + nights + 'N Rate');
                $('#hr-rate-id').val($btn.data('id'));
                $('#hr-rate-nights').val(nights);
                $('#hr-meal-plan').val($btn.data('meal'));
                for ( var p = 1; p <= 12; p++ ) {
                        $('#hr-pax-' + p).val($btn.data('pax-' + p) || '');
                }
                openModal();
        });

        /* ── Tour Rate Modal ────────────────────────────────────────────── */

        var $tourModal   = $('#hr-tour-rate-modal');
        var $tourOverlay = $('#hr-tour-overlay');

        function openTourModal()  { $tourModal.show(); $tourOverlay.show(); $('body').css('overflow','hidden'); }
        function closeTourModal() { $tourModal.hide(); $tourOverlay.hide(); $('body').css('overflow',''); }

        function isAirportTransferType(type) {
                return String(type || '') === 'Airport Transfer';
        }

        function escapeTourAddonAttr(value) {
                return (value === null || typeof value === 'undefined' ? '' : String(value))
                        .replace(/&/g, '&amp;')
                        .replace(/"/g, '&quot;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
        }

        function parseAirportTransferRoutes(raw) {
                if ( !raw ) {
                        return [];
                }

                if ( typeof raw === 'object' ) {
                        return $.isArray(raw) ? raw : [];
                }

                try {
                        raw = JSON.parse(raw);
                } catch (error) {
                        return [];
                }

                return $.isArray(raw) ? raw : [];
        }

        function setTourRateModalType(type) {
                var airportTransfer = isAirportTransferType(type);
                var tourType = String(type || '');
                var dayTour = tourType === 'Day Tour';
                var childRateTour = dayTour || tourType === 'Joiner';

                $('#hr-tour-name-label').text(airportTransfer ? 'Name' : 'Tour Name');
                $('#hr-tour-name-input').attr('placeholder', airportTransfer ? 'e.g., Panglao International Airport' : 'e.g., Dumaguete City Tour');
                $tourModal.find('.hr-modal-pax-grid').toggle(!airportTransfer);
                $tourModal.find('.hr-tour-children-rates').toggle(childRateTour);
                $tourModal.find('.hr-tour-child-label-field').toggle(childRateTour);
                $tourModal.find('.hr-tour-child-discount-field').toggle(childRateTour);
                $tourModal.find('.hr-tour-children-pax-grid').toggle(false);
                $tourModal.find('.hr-tour-child-label-field :input').prop('disabled', !childRateTour);
                $tourModal.find('.hr-tour-child-discount-field :input').prop('disabled', !childRateTour);
                $tourModal.find('.hr-tour-children-pax-grid :input').prop('disabled', true);
                $tourModal.find('.hr-tour-children-rate-help').text(dayTour ? 'Enable Children guests for this Day Tour and enter the child discount percentage.' : 'Enable Children guests for this Joiner and enter the child discount percentage.');
                $tourModal.find('.hr-tour-booking-fields').toggle(!airportTransfer);
                $tourModal.find('.hr-tour-addons').toggle(!airportTransfer);
                $tourModal.find('.hr-airport-transfer-routes').toggle(airportTransfer);
                refreshTourChildrenRateLabels();
        }

        function refreshTourChildrenRateLabels() {
                $tourModal.find('.hr-tour-children-pax-grid .hr-modal-pax-cell label').each(function (index) {
                        $(this).text((index + 1) + ' child (\u20B1)');
                });
        }

        function getAirportTransferRouteList() {
                return $tourModal.find('.hr-airport-transfer-route-list').first();
        }

        function reindexAirportTransferRoutes() {
                getAirportTransferRouteList().find('.hr-airport-transfer-route').each(function (index) {
                        var $route = $(this);

                        $route.attr('data-index', index).data('index', index);
                        $route.find('[data-field="route-label"]').attr('name', 'transfer_routes[' + index + '][label]');
                        $route.find('[data-field="route-rate"]').attr('name', 'transfer_routes[' + index + '][rate]');
                });
        }

        function addAirportTransferRoute(route, shouldFocus) {
                var $list = getAirportTransferRouteList();
                var index = $list.find('.hr-airport-transfer-route').length;
                var label = route && route.label ? route.label : '';
                var rate = route && route.rate !== null && typeof route.rate !== 'undefined' ? route.rate : '';
                var html = '';
                var $route;

                if ( rate === '' && route && route.prices ) {
                        $.each(route.prices, function (pax, value) {
                                if ( rate === '' && value !== null && typeof value !== 'undefined' && value !== '' ) {
                                        rate = value;
                                }
                        });
                }

                if ( rate === '' ) {
                        rate = '0.00';
                }

                html += '<div class="hr-airport-transfer-route" data-index="' + index + '">';
                html += '<div class="hr-airport-transfer-route-fields">';
                html += '<div class="hr-airport-transfer-route-field">';
                html += '<label>From/To Place</label>';
                html += '<input type="text" data-field="route-label" name="transfer_routes[' + index + '][label]" class="hr-airport-transfer-route-label" value="' + escapeTourAddonAttr(label) + '" placeholder="From/To place name" required>';
                html += '</div>';
                html += '<div class="hr-airport-transfer-route-field hr-airport-transfer-route-rate-field">';
                html += '<label>Rate per booking (&#8369;)</label>';
                html += '<input type="number" data-field="route-rate" name="transfer_routes[' + index + '][rate]" class="hr-airport-transfer-route-rate" value="' + escapeTourAddonAttr(rate) + '" step="0.01" min="0" placeholder="0.00" required>';
                html += '</div>';
                html += '<button type="button" class="button-link-delete hr-airport-transfer-route-remove">Remove Place</button>';
                html += '</div>';
                html += '</div>';

                $list.append(html);
                $route = $list.find('.hr-airport-transfer-route').last();
                $route.data('index', index);
                reindexAirportTransferRoutes();

                if ( shouldFocus ) {
                        $route.find('.hr-airport-transfer-route-label').focus();
                }
        }

        function getAirportTransferRoutesForSave() {
                var routes = [];

                getAirportTransferRouteList().find('.hr-airport-transfer-route').each(function () {
                        var $route = $(this);
                        var label = $.trim($route.find('[data-field="route-label"]').val() || '');
                        var rate = $.trim($route.find('[data-field="route-rate"]').val() || '');

                        if (label === '' && rate === '') {
                                return;
                        }

                        routes.push({
                                label: label,
                                rate: rate === '' ? '0' : rate
                        });
                });

                return routes;
        }

        function resetAirportTransferRoutes(routes) {
                getAirportTransferRouteList().empty();

                $.each(parseAirportTransferRoutes(routes), function (index, route) {
                        addAirportTransferRoute(route, false);
                });
        }

        function parseTourAddons(raw) {
                if ( !raw ) {
                        return [];
                }

                if ( typeof raw === 'object' ) {
                        return $.isArray(raw) ? raw : [];
                }

                try {
                        raw = JSON.parse(raw);
                } catch (error) {
                        return [];
                }

                return $.isArray(raw) ? raw : [];
        }

        function getDefaultTourBookingFields() {
                return {
                        hotel_flight_no: {
                                enabled: true,
                                label: 'Hotel / Flight No.'
                        },
                        pickup_time: {
                                enabled: true,
                                label: 'Pick-up Time'
                        }
                };
        }

        function parseTourBookingFields(raw) {
                var defaults = getDefaultTourBookingFields();
                var parsed = {};

                if ( raw && typeof raw === 'object' ) {
                        parsed = raw;
                } else if ( raw ) {
                        try {
                                parsed = JSON.parse(raw);
                        } catch (error) {
                                parsed = {};
                        }
                }

                $.each(defaults, function (key, fieldDefault) {
                        var field = parsed && parsed[key] && typeof parsed[key] === 'object' ? parsed[key] : {};

                        parsed[key] = {
                                enabled: typeof field.enabled === 'undefined' ? fieldDefault.enabled : !!Number(field.enabled),
                                label: field.label ? String(field.label) : fieldDefault.label
                        };
                });

                return parsed;
        }

        function resetTourBookingFields(raw) {
                var fields = parseTourBookingFields(raw);

                $.each(fields, function (key, field) {
                        var $setting = $tourModal.find('.hr-tour-booking-field-setting[data-field-key="' + key + '"]');

                        $setting.find('.hr-tour-booking-field-enabled').prop('checked', !!field.enabled);
                        $setting.find('.hr-tour-booking-field-label').val(field.label);
                });
        }

        function parseTourChildrenRates(raw) {
                var parsed = {};
                var prices = {};

                if ( raw && typeof raw === 'object' ) {
                        parsed = raw;
                } else if ( raw ) {
                        try {
                                parsed = JSON.parse(raw);
                        } catch (error) {
                                parsed = {};
                        }
                }

                if ( parsed && parsed.prices && typeof parsed.prices === 'object' ) {
                        prices = parsed.prices;
                }

                return {
                        enabled: parsed && typeof parsed.enabled !== 'undefined' ? !!Number(parsed.enabled) : false,
                        label: parsed && parsed.label ? String(parsed.label) : 'Children',
                        discountPercent: parsed && typeof parsed.discount_percent !== 'undefined' ? Number(parsed.discount_percent) : (parsed && typeof parsed.discountPercent !== 'undefined' ? Number(parsed.discountPercent) : 0),
                        prices: prices
                };
        }

        function resetTourChildrenRates(raw) {
                var childrenRates = parseTourChildrenRates(raw);

                $('#hr-tour-children-rates-enabled').prop('checked', !!childrenRates.enabled);
                $('#hr-tour-child-label').val(childrenRates.label || 'Children');
                $('#hr-tour-child-discount-percent').val(Number.isFinite(childrenRates.discountPercent) ? childrenRates.discountPercent : '');

                for ( var p = 1; p <= 12; p++ ) {
                        $('#hr-tour-children-pax-' + p).val(childrenRates.prices && childrenRates.prices[String(p)] !== null && typeof childrenRates.prices[String(p)] !== 'undefined' ? childrenRates.prices[String(p)] : '');
                }
        }

        function getTourAddonGroupsWrap() {
                return $tourModal.find('.hr-tour-addon-groups').first();
        }

        function reindexTourAddonRows($group) {
                var groupIndex = $group.data('index');

                $group.find('.hr-tour-addon-row').each(function (index) {
                        $(this).find('[data-field="label"]').attr('name', 'optional_addons[' + groupIndex + '][options][' + index + '][label]');
                        $(this).find('[data-field="rate"]').attr('name', 'optional_addons[' + groupIndex + '][options][' + index + '][rate]');
                });
        }

        function reindexTourAddonGroups() {
                getTourAddonGroupsWrap().find('.hr-tour-addon-group').each(function (index) {
                        var $group = $(this);

                        $group.attr('data-index', index).data('index', index);
                        $group.find('[data-field="group-key"]').attr('name', 'optional_addons[' + index + '][key]');
                        $group.find('[data-field="group-label"]').attr('name', 'optional_addons[' + index + '][label]');
                        reindexTourAddonRows($group);
                });
        }

        function addTourAddonRow($group, option) {
                var groupIndex = $group.data('index');
                var index = $group.find('.hr-tour-addon-row').length;
                var label = option && option.label ? option.label : '';
                var rate = option && option.rate !== null && typeof option.rate !== 'undefined' ? option.rate : '';
                var html = '';

                if ( !$group.length ) {
                        return;
                }

                html += '<div class="hr-tour-addon-row">';
                html += '<input type="text" data-field="label" name="optional_addons[' + groupIndex + '][options][' + index + '][label]" class="hr-tour-addon-label" value="' + escapeTourAddonAttr(label) + '" placeholder="Select label">';
                html += '<input type="number" data-field="rate" name="optional_addons[' + groupIndex + '][options][' + index + '][rate]" class="hr-tour-addon-rate" value="' + escapeTourAddonAttr(rate) + '" min="0" step="0.01" placeholder="Rate">';
                html += '<button type="button" class="button-link-delete hr-tour-addon-remove">Remove</button>';
                html += '</div>';

                $group.find('.hr-tour-addon-rows').append(html);
        }

        function addTourAddonGroup(group, shouldFocus) {
                var $groups = getTourAddonGroupsWrap();
                var index = $groups.find('.hr-tour-addon-group').length;
                var key = group && group.key ? group.key : '';
                var label = group && group.label ? group.label : '';
                var options = group && $.isArray(group.options) ? group.options : [];
                var html = '';
                var $group;

                html += '<div class="hr-tour-addon-group" data-index="' + index + '">';
                html += '<div class="hr-tour-addon-group-head">';
                html += '<input type="hidden" data-field="group-key" name="optional_addons[' + index + '][key]" value="' + escapeTourAddonAttr(key) + '">';
                html += '<input type="text" data-field="group-label" name="optional_addons[' + index + '][label]" class="hr-tour-addon-group-label" value="' + escapeTourAddonAttr(label) + '" placeholder="Add-on group label">';
                html += '<div class="hr-tour-addon-group-controls">';
                html += '<button type="button" class="button button-small hr-tour-addon-add">Add Selection</button>';
                html += '<button type="button" class="button-link-delete hr-tour-addon-group-remove">Remove Group</button>';
                html += '</div>';
                html += '</div>';
                html += '<div class="hr-tour-addon-rows"></div>';
                html += '</div>';

                $groups.append(html);
                $group = $groups.find('.hr-tour-addon-group').last();
                $group.data('index', index);

                if ( options.length ) {
                        $.each(options, function (optionIndex, option) {
                                addTourAddonRow($group, option);
                        });
                } else {
                        addTourAddonRow($group, {});
                }

                reindexTourAddonGroups();
                if ( shouldFocus ) {
                        $group.find('.hr-tour-addon-group-label').focus();
                }
        }

        function resetTourAddonFields(addons) {
                getTourAddonGroupsWrap().empty();

                $.each(parseTourAddons(addons), function (index, group) {
                        addTourAddonGroup(group, false);
                });
        }

        $(document).on('click', '#hr-tour-overlay', closeTourModal);

        // Intercept hr-modal-close inside #hr-tour-rate-modal
        $tourModal.on('click', '.hr-modal-close', closeTourModal);

        $tourModal.on('click', '.hr-tour-addon-group-add', function () {
                addTourAddonGroup({}, true);
        });

        $tourModal.on('click', '.hr-tour-addon-add', function () {
                addTourAddonRow($(this).closest('.hr-tour-addon-group'), {});
        });

        $tourModal.on('click', '.hr-tour-addon-remove', function () {
                var $group = $(this).closest('.hr-tour-addon-group');

                $(this).closest('.hr-tour-addon-row').remove();
                reindexTourAddonRows($group);
        });

        $tourModal.on('click', '.hr-tour-addon-group-remove', function () {
                $(this).closest('.hr-tour-addon-group').remove();
                reindexTourAddonGroups();
        });

        $tourModal.on('click', '.hr-airport-transfer-route-add', function () {
                addAirportTransferRoute({}, true);
        });

        $tourModal.on('click', '.hr-airport-transfer-route-remove', function () {
                $(this).closest('.hr-airport-transfer-route').remove();
                reindexAirportTransferRoutes();
        });

        $tourModal.on('submit', 'form', function () {
                if ( !isAirportTransferType($('#hr-tour-type-input').val()) ) {
                        return;
                }

                $('#hr-airport-transfer-routes-json').val(JSON.stringify(getAirportTransferRoutesForSave()));
        });

        $(document).on('click', '.hr-add-tour-rate-btn', function () {
                var $btn = $(this);
                var type = $btn.data('type');
                $('#hr-tour-modal-title').text('Add ' + $btn.data('type') + ' Rate');
                $('#hr-tour-rate-id').val('');
                $('#hr-tour-place-id').val($btn.data('place-id'));
                $('#hr-tour-region-id').val($btn.data('region-id'));
                $('#hr-tour-type-input').val(type);
                $('#hr-tour-name-input').val('');
                for ( var p = 1; p <= 12; p++ ) { $('#hr-tour-pax-' + p).val(''); }
                setTourRateModalType(type);
                resetTourBookingFields({});
                resetTourChildrenRates({});
                resetTourAddonFields([]);
                resetAirportTransferRoutes([]);
                if ( isAirportTransferType(type) ) {
                        addAirportTransferRoute({}, false);
                }
                openTourModal();
        });

        $(document).on('click', '.hr-edit-tour-rate-btn', function () {
                var $btn = $(this);
                var type = $btn.data('type');
                $('#hr-tour-modal-title').text('Edit ' + $btn.data('type') + ' Rate');
                $('#hr-tour-rate-id').val($btn.data('id'));
                $('#hr-tour-type-input').val(type);
                $('#hr-tour-name-input').val($btn.data('tour-name'));
                for ( var p = 1; p <= 12; p++ ) {
                        $('#hr-tour-pax-' + p).val($btn.data('pax-' + p) || '');
                }
                setTourRateModalType(type);
                resetTourBookingFields($btn.attr('data-booking-fields'));
                resetTourChildrenRates($btn.attr('data-children-rates'));
                resetTourAddonFields($btn.attr('data-addons'));
                resetAirportTransferRoutes($btn.attr('data-transfer-routes'));
                if ( isAirportTransferType(type) && !getAirportTransferRouteList().find('.hr-airport-transfer-route').length ) {
                        addAirportTransferRoute({}, false);
                }
                openTourModal();
        });

        /* ── Tour Rates — Inline Row + / − (legacy inline mode) ─────────── */

        function reindexTable($table) {
                $table.find('.hr-tour-row:not(.hr-tour-row--hidden)').each(function (i) {
                        $(this).attr('data-idx', i)
                                .find('input[name]').each(function () {
                                        var name = $(this).attr('name');
                                        $(this).attr('name', name.replace(/rows\[\d+\]/, 'rows[' + i + ']'));
                                });
                });
        }

        var DEFAULT_PAX_SHOWN = 8;
        var MAX_PAX = 12;

        function buildNewRow(idx, $refTable) {
                // Detect how many pax columns are currently visible in the reference table
                // so new rows match the current state.
                var visiblePax = DEFAULT_PAX_SHOWN;
                if ( $refTable && $refTable.length ) {
                        visiblePax = $refTable.find('th.hr-col-pax:not(.hr-pax-hidden)').length || DEFAULT_PAX_SHOWN;
                }

                var $row = $('<tr class="hr-tour-row" data-idx="' + idx + '"></tr>');
                var cells = '<td>' +
                        '<input type="hidden" name="rows[' + idx + '][id]" value="">' +
                        '<input type="hidden" name="rows[' + idx + '][_delete]" value="0" class="hr-delete-flag">' +
                        '<input type="text" name="rows[' + idx + '][tour_name]" value="" class="hr-input-tour" placeholder="Tour name">' +
                        '</td>';
                for ( var p = 1; p <= MAX_PAX; p++ ) {
                        var hiddenClass = p > visiblePax ? ' hr-pax-hidden' : '';
                        cells += '<td class="hr-col-pax' + hiddenClass + '" data-pax="' + p + '">' +
                                '<input type="number" name="rows[' + idx + '][pax_' + p + ']" value="" class="hr-input-pax" step="0.01" min="0" placeholder="0">' +
                                '</td>';
                }
                cells += '<td class="hr-col-pax-ctrl"></td>' +
                        '<td class="hr-col-actions">' +
                        '<button type="button" class="hr-row-add" title="Add row">+</button>' +
                        '<button type="button" class="hr-row-remove" title="Remove row">−</button>' +
                        '</td>';
                $row.html(cells);
                return $row;
        }

        $(document).on('click', '.hr-row-add', function () {
                var $currentRow = $(this).closest('.hr-tour-row');
                var $table      = $currentRow.closest('table');
                var $allRows    = $table.find('.hr-tour-row');
                var idx         = $allRows.length;
                var $newRow     = buildNewRow(idx, $table);
                $currentRow.after($newRow);
                $newRow.find('.hr-input-tour').focus();
                reindexTable($table);
        });

        $(document).on('click', '.hr-row-remove', function () {
                var $row    = $(this).closest('.hr-tour-row');
                var $table  = $row.closest('table');
                var hasId   = $row.find('input[name$="[id]"]').val();
                if ( hasId ) {
                        $row.find('.hr-delete-flag').val('1');
                        $row.addClass('hr-tour-row--hidden').hide();
                } else {
                        $row.remove();
                }
                if ( $table.find('.hr-tour-row:visible').length === 0 ) {
                        $table.find('tbody').append(buildNewRow(0, $table));
                }
                reindexTable($table);
        });

        /* ── Register Page — Hotel Place filter by region ────────────────── */

        $('#hotel-region-filter').on('change', function () {
                var regionId = $(this).val();
                $('#hotel-place option').each(function () {
                        var $opt = $(this);
                        if ( !regionId || $opt.data('region') == regionId || $opt.val() === '' ) {
                                $opt.show();
                        } else {
                                $opt.hide();
                        }
                });
                $('#hotel-place').val('');
        });

        function renderPackageHotels() {
                var $places = $('#hr-package-places');
                var $wrap = $('#hr-package-hotels-wrap');
                var $templates = $('#hr-package-hotel-templates');

                if ( !$places.length || !$wrap.length || !$templates.length ) {
                        return;
                }

                $wrap.empty();

                var $selectedPlaces = $places.find('option:selected');
                if ( !$selectedPlaces.length ) {
                        $wrap.append('<p class="description">Select one or more places above to assign optional hotels for each place.</p>');
                        return;
                }

                $selectedPlaces.each(function () {
                        var $place = $(this);
                        var placeId = String($place.val());
                        var $template = $templates.find('.hr-package-hotel-template[data-place-id="' + placeId + '"]').first();

                        if ( $template.length ) {
                                $wrap.append($template.html());
                        }
                });
        }

        $(document).on('click', '#hr-package-confirm-places', function (e) {
                e.preventDefault();
                renderPackageHotels();
        });

        /* ── Register Page — Accordion (region level) ────────────────────── */

        $(document).on('click', '.hr-accordion-header', function () {
                $(this).closest('.hr-accordion-item').toggleClass('hr-accordion-item--open');
        });

        /* ── Register Page — Tour Inline Edit ────────────────────────────── */

        // Enter edit mode
        $(document).on('click', '.hr-tour-edit-btn', function () {
                var $item = $(this).closest('.hr-tour-item');
                $item.find('.hr-tour-view').hide();
                $item.find('.hr-tour-edit-mode').show();
                $item.find('.hr-tour-inline-input').focus().select();
        });

        // Cancel edit
        $(document).on('click', '.hr-tour-cancel-inline', function () {
                var $item = $(this).closest('.hr-tour-item');
                var original = $item.find('.hr-tour-name-text').text();
                $item.find('.hr-tour-inline-input').val(original);
                $item.find('.hr-tour-edit-mode').hide();
                $item.find('.hr-tour-view').show();
        });

        // Save edit via AJAX
        $(document).on('click', '.hr-tour-save-inline', function () {
                var $item    = $(this).closest('.hr-tour-item');
                var tourId   = $item.data('tour-id');
                var newName  = $item.find('.hr-tour-inline-input').val().trim();
                if ( !newName ) { alert('Tour name cannot be empty.'); return; }

                var $btn = $(this);
                $btn.text('…').prop('disabled', true);

                $.post(hrAjax.ajaxurl, {
                        action    : 'hr_update_tour_name',
                        nonce     : hrAjax.nonce,
                        id        : tourId,
                        tour_name : newName
                }, function (res) {
                        $btn.text('✓').prop('disabled', false);
                        if ( res.success ) {
                                $item.find('.hr-tour-name-text').text(res.data.tour_name);
                                $item.find('.hr-tour-inline-input').val(res.data.tour_name);
                                $item.find('.hr-tour-edit-mode').hide();
                                $item.find('.hr-tour-view').show();
                        } else {
                                alert('Failed to save. Please try again.');
                        }
                }).fail(function () {
                        $btn.text('✓').prop('disabled', false);
                        alert('Network error. Please try again.');
                });
        });

        // Allow Enter key in inline input to trigger save
        $(document).on('keydown', '.hr-tour-inline-input', function (e) {
                if ( e.key === 'Enter' ) {
                        $(this).closest('.hr-tour-item').find('.hr-tour-save-inline').trigger('click');
                }
                if ( e.key === 'Escape' ) {
                        $(this).closest('.hr-tour-item').find('.hr-tour-cancel-inline').trigger('click');
                }
        });

})(jQuery);
