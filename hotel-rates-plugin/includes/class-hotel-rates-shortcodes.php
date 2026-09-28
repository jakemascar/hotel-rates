<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Shortcodes for embedding hotel and tour rate tables on any page/post.
 *
 * Usage:
 *   [tour_rate_table city="Dumaguete" nights="2 Nights"]
 *   [tour_rate_table city="Siquijor"  nights="1 Night"]
 *
 *   [tour_day_rate_table city="Dumaguete" type="Day Tour"]
 *   [tour_day_rate_table city="Dumaguete" type="Joiner"]
 *
 *   [package_deal_hotel_rates]
 *   [package_deal_hotel_rates package_id="6" max_pax="12"]
 *
 *   [specific_tour_rates]
 *   [specific_tour_rates tour_rate_id="123" max_pax="12"]
 *
 * Optional attribute for both: max_pax="8"  (default 8, max 12)
 */
class Hotel_Rates_Shortcodes {

        public function __construct() {
                add_shortcode( 'tour_rate_table',     array( $this, 'hotel_rate_table' ) );
                add_shortcode( 'tour_day_rate_table', array( $this, 'tour_rate_table' ) );
                add_shortcode( 'package_deal_hotel_rates', array( $this, 'package_deal_hotel_rates_table' ) );
                add_shortcode( 'specific_tour_rates', array( $this, 'specific_tour_rates_table' ) );
                add_shortcode( 'specific_port_transfer_rates', array( $this, 'specific_port_transfer_rates_table' ) );
                add_shortcode( 'specific_destination_tour_rates', array( $this, 'specific_destination_tour_rates_table' ) );
                add_shortcode( 'specific_self_drive_rates', array( $this, 'specific_self_drive_rates_table' ) );
                add_shortcode( 'specific_motorbike_rates', array( $this, 'specific_motorbike_rates_table' ) );
                add_shortcode( 'specific_boat_rates', array( $this, 'specific_boat_rates_table' ) );
                add_filter( 'tripgo_get_meta_data', array( $this, 'render_specific_tour_rates_in_tripgo_plan' ), 10, 4 );
                add_action( 'wp_enqueue_scripts',     array( $this, 'enqueue_styles' ) );
        }

        /**
         * Expand supported Hotel Rates shortcodes only in Tripgo Tour Plan descriptions.
         *
         * @param mixed  $value   Resolved Tripgo metadata value.
         * @param string $key     Metadata key requested by the template.
         * @param array  $args    Source metadata array.
         * @param mixed  $default Default metadata value.
         * @return mixed
         */
        public function render_specific_tour_rates_in_tripgo_plan( $value, $key, $args, $default ) {
                if ( 'ovabrw_tour_plan_desc' !== $key || ! is_string( $value ) ) {
                        return $value;
                }

                return $this->render_specific_tour_rates_in_content( $value );
        }

        /**
         * Expand only supported Hotel Rates shortcodes in a supplied string.
         *
         * @param string $content Tour Plan description.
         * @return string
         */
        public function render_specific_tour_rates_in_content( $content ) {
                if (
                        false === strpos( $content, '[specific_tour_rates' ) &&
                        false === strpos( $content, '[package_deal_hotel_rates' ) &&
                        false === strpos( $content, '[specific_port_transfer_rates' ) &&
                        false === strpos( $content, '[specific_destination_tour_rates' ) &&
                        false === strpos( $content, '[specific_self_drive_rates' ) &&
                        false === strpos( $content, '[specific_motorbike_rates' ) &&
                        false === strpos( $content, '[specific_boat_rates' )
                ) {
                        return $content;
                }

                $pattern = get_shortcode_regex( array( 'specific_tour_rates', 'package_deal_hotel_rates', 'specific_port_transfer_rates', 'specific_destination_tour_rates', 'specific_self_drive_rates', 'specific_motorbike_rates', 'specific_boat_rates' ) );

                return preg_replace_callback(
                        '/' . $pattern . '/s',
                        static function ( $matches ) {
                                return do_shortcode_tag( $matches );
                        },
                        $content
                );
        }

        public function enqueue_styles() {
                wp_enqueue_style(
                        'hr-shortcodes',
                        HR_PLUGIN_URL . 'public/css/shortcodes.css',
                        array(),
                        HR_VERSION
                );

                wp_add_inline_style(
                        'hr-shortcodes',
                        '.hr-package-rates .hr-package-rates-place{font-size:.875em!important;line-height:1.2!important}' .
                        '.hr-package-rates .hr-package-rates-overflow table.hr-package-rates-table>thead>tr>th{height:auto!important;padding-top:10px!important;padding-bottom:10px!important;line-height:24px!important;vertical-align:middle!important}' .
                        '.hr-package-rates .hr-package-rates-table .hr-package-rates-heading{display:flex!important;align-items:center!important;justify-content:center!important;height:auto!important;min-height:0!important;padding-top:20px!important;line-height:24px!important}' .
                        '.hr-package-rates .hr-package-rates-table>thead>tr>th:first-child .hr-package-rates-heading,.hr-package-rates .hr-package-rates-table>thead>tr>th:nth-child(2) .hr-package-rates-heading{justify-content:flex-start!important}' .
			'.hr-package-rates .hr-package-rates-table>thead>tr>th:first-child .hr-package-rates-heading{position:relative!important;top:6px!important}'
                );
        }

        /* ── Hotel Rate Table ─────────────────────────────────────────────── */

        /**
         * [tour_rate_table city="..." nights="..." max_pax="8" title="Package Rates for Hotel & Tour"]
         */
        public function hotel_rate_table( $atts ) {
                global $wpdb;

                $atts = shortcode_atts( array(
                        'city'    => '',
                        'nights'  => '1',
                        'max_pax' => 8,
                        'title'   => 'Package Rates for Hotel &amp; Tour',
                ), $atts, 'tour_rate_table' );

                $city    = sanitize_text_field( $atts['city'] );
                $nights  = $this->parse_nights( $atts['nights'] );
                $max_pax = max( 1, min( 12, (int) $atts['max_pax'] ) );

                if ( ! $city || ! $nights ) {
                        return '<p class="hr-sc-error">tour_rate_table: please provide <code>city</code> and <code>nights</code> attributes.</p>';
                }

                // Find the place by name (fuzzy match).
                $place = $this->find_place( $city );
                if ( ! $place ) {
                        return '<p class="hr-sc-error">tour_rate_table: place "' . esc_html( $city ) . '" not found. Check that the place is registered in Hotel Rates → Register.</p>';
                }

                // Fetch all hotel rates for this place & night count.
                $rows = $wpdb->get_results( $wpdb->prepare(
                        "SELECT r.*, h.id AS hotel_id, h.name AS hotel_name
                         FROM {$wpdb->prefix}hr_rates r
                         INNER JOIN {$wpdb->prefix}hr_hotels h ON h.id = r.hotel_id
                         WHERE h.place_id = %d AND r.nights = %d
                         ORDER BY h.name, r.id",
                        $place->id, $nights
                ) );

                if ( empty( $rows ) ) {
                        return '<p class="hr-sc-empty">No hotel rates found for ' . esc_html( ucfirst( strtolower( $city ) ) ) . ' — ' . esc_html( $nights ) . ' night' . ( $nights !== 1 ? 's' : '' ) . '.</p>';
                }

                $city_label = esc_html( $this->truncate_label( ucwords( strtolower( $city ) ) . ' Hotel', 14 ) );

                ob_start();
                ?>
                <div class="hr-sc-wrap" data-hr-hotel-table="1" data-hr-place-id="<?php echo esc_attr( $place->id ); ?>" data-hr-city="<?php echo esc_attr( $city ); ?>" data-hr-nights="<?php echo esc_attr( $nights ); ?>">
                        <?php if ( $atts['title'] ) : ?>
                        <h3 class="hr-sc-title"><?php echo wp_kses_post( $atts['title'] ); ?></h3>
                        <?php endif; ?>
                        <div class="hr-sc-table-overflow">
                        <table class="hr-sc-table">
                                <thead>
                                        <tr>
                                                <th class="hr-sc-th-city"><?php echo $city_label; ?></th>
                                                <th class="hr-sc-th-bp">Breakfast</th>
                                                <?php for ( $p = 1; $p <= $max_pax; $p++ ) : ?>
                                                <th><?php echo $p; ?> pax</th>
                                                <?php endfor; ?>
                                        </tr>
                                </thead>
                                <tbody>
                                        <?php foreach ( $rows as $row ) :
                                                $has_breakfast = ( stripos( $row->meal_plan, 'breakfast' ) !== false );
                                                $prices        = $this->get_rate_prices( $row );
                                                $booking_label = $this->get_booking_label( $row, $has_breakfast );
                                        ?>
                                        <tr
                                                data-rate-id="<?php echo esc_attr( $row->id ); ?>"
                                                data-hotel-id="<?php echo esc_attr( $row->hotel_id ); ?>"
                                                data-hotel-name="<?php echo esc_attr( $row->hotel_name ); ?>"
                                                data-meal-plan="<?php echo esc_attr( $row->meal_plan ); ?>"
                                                data-breakfast="<?php echo $has_breakfast ? '1' : '0'; ?>"
                                                data-booking-label="<?php echo esc_attr( $booking_label ); ?>"
                                                data-prices="<?php echo esc_attr( wp_json_encode( $prices ) ); ?>"
                                        >
                                                <td class="hr-sc-hotel-name"><?php echo esc_html( $row->hotel_name ); ?></td>
                                                <td class="hr-sc-bp"><?php echo $has_breakfast ? '<span class="hr-sc-check">&#10003;</span>' : '<span class="hr-sc-cross">&#10005;</span>'; ?></td>
                                                <?php for ( $p = 1; $p <= $max_pax; $p++ ) :
                                                        $col = 'pax_' . $p;
                                                        $val = $row->$col;
                                                ?>
                                                <td class="hr-sc-price"><?php echo null !== $val ? wp_kses_post( Hotel_Rates_Currency::format_display_price( $val ) ) : '&mdash;'; ?></td>
                                                <?php endfor; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                </tbody>
                        </table>
                        </div>
                </div>
                <?php
                return ob_get_clean();
        }

        /* Package Deal Hotel Rates Table. */

        /**
         * Display the hotel rates configured for one Package Deal.
         *
         * By default, the package is resolved from the current WooCommerce
         * product's _hr_package_deal_id meta value.
         *
         * Usage:
         *   [package_deal_hotel_rates]
         *   [package_deal_hotel_rates package_id="6"]
         *   [package_deal_hotel_rates product_id="123" max_pax="8"]
         */
        public function package_deal_hotel_rates_table( $atts ) {
                $atts = shortcode_atts( array(
                        'package_id' => 0,
                        'product_id' => 0,
                        'max_pax'    => Hotel_Rates_DB::MAX_PAX,
                ), $atts, 'package_deal_hotel_rates' );

                $package_id = absint( $atts['package_id'] );
                $product_id = absint( $atts['product_id'] );
                $max_pax    = max( 1, min( Hotel_Rates_DB::MAX_PAX, (int) $atts['max_pax'] ) );

                if ( ! $package_id ) {
                        if ( ! $product_id ) {
                                $product_id = (int) get_queried_object_id();
                        }

                        if ( ! $product_id ) {
                                $product_id = (int) get_the_ID();
                        }

                        if ( $product_id ) {
                                $package_id = (int) get_post_meta( $product_id, '_hr_package_deal_id', true );
                        }
                }

                if ( ! $package_id ) {
                        return '<p class="hr-package-rates-message">' . esc_html__( 'No Package Deal is linked to this page.', 'hotel-rates' ) . '</p>';
                }

                $package = Hotel_Rates_DB::get_package_deal( $package_id );

                if ( ! $package ) {
                        return '<p class="hr-package-rates-message">' . esc_html__( 'The linked Package Deal could not be found.', 'hotel-rates' ) . '</p>';
                }

                $place_ids        = json_decode( $package->place_ids ?? '[]', true );
                $hotel_ids_map    = json_decode( $package->hotel_ids ?? '{}', true );
                $hotel_nights_map = json_decode( $package->hotel_nights ?? '{}', true );
                $place_ids        = is_array( $place_ids ) ? array_values( array_unique( array_filter( array_map( 'intval', $place_ids ) ) ) ) : array();
                $hotel_ids_map    = is_array( $hotel_ids_map ) ? $hotel_ids_map : array();
                $hotel_nights_map = is_array( $hotel_nights_map ) ? $hotel_nights_map : array();
                $default_nights   = max( 1, min( Hotel_Rates_DB::MAX_NIGHTS, (int) $package->number_of_days - 1 ) );
                $groups           = array();

                foreach ( $place_ids as $place_id ) {
                        $place_key = (string) $place_id;
                        $hotel_ids = isset( $hotel_ids_map[ $place_key ] ) && is_array( $hotel_ids_map[ $place_key ] )
                                ? array_values( array_unique( array_filter( array_map( 'intval', $hotel_ids_map[ $place_key ] ) ) ) )
                                : array();

                        if ( empty( $hotel_ids ) ) {
                                continue;
                        }

                        $place = Hotel_Rates_DB::get_place( $place_id );

                        if ( ! $place ) {
                                continue;
                        }

                        $nights = isset( $hotel_nights_map[ $place_key ] ) ? (int) $hotel_nights_map[ $place_key ] : $default_nights;
                        $nights = max( 1, min( Hotel_Rates_DB::MAX_NIGHTS, $nights ) );
                        $rows   = array();

                        foreach ( $hotel_ids as $hotel_id ) {
                                $hotel = Hotel_Rates_DB::get_hotel( $hotel_id );

                                if ( ! $hotel || (int) $hotel->place_id !== $place_id ) {
                                        continue;
                                }

                                $rate = $this->get_preferred_package_hotel_rate( Hotel_Rates_DB::get_rates( $hotel_id, $nights ) );

                                if ( ! $rate ) {
                                        continue;
                                }

                                $rows[] = array(
                                        'hotel' => $hotel,
                                        'rate'  => $rate,
                                );
                        }

                        if ( ! empty( $rows ) ) {
                                $groups[] = array(
                                        'place'  => $place,
                                        'nights' => $nights,
                                        'rows'   => $rows,
                                );
                        }
                }

                if ( empty( $groups ) ) {
                        return '<p class="hr-package-rates-message">' . esc_html__( 'No hotel rates are available for this Package Deal.', 'hotel-rates' ) . '</p>';
                }

                ob_start();
                ?>
                <div class="hr-package-rates" data-package-deal-id="<?php echo esc_attr( $package_id ); ?>">

                        <?php foreach ( $groups as $group ) : ?>
                                <section class="hr-package-rates-group">
                                        <h4 class="hr-package-rates-place">
                                                <?php
                                                printf(
                                                        esc_html__( '%1$s Hotels — %2$d %3$s', 'hotel-rates' ),
                                                        esc_html( $group['place']->name ),
                                                        (int) $group['nights'],
                                                        1 === (int) $group['nights'] ? esc_html__( 'Night', 'hotel-rates' ) : esc_html__( 'Nights', 'hotel-rates' )
                                                );
                                                ?>
                                        </h4>
                                        <div class="hr-package-rates-overflow">
                                                <table class="hr-package-rates-table">
                                                        <thead>
                                                                <tr>
                                                                        <th scope="col"><span class="hr-package-rates-heading"><?php esc_html_e( 'Hotel', 'hotel-rates' ); ?></span></th>
                                                                        <th scope="col"><span class="hr-package-rates-heading"><?php esc_html_e( 'Meal Plan', 'hotel-rates' ); ?></span></th>
                                                                        <?php for ( $pax = 1; $pax <= $max_pax; $pax++ ) : ?>
                                                                                <th scope="col"><span class="hr-package-rates-heading"><?php echo esc_html( sprintf( __( '%d pax', 'hotel-rates' ), $pax ) ); ?></span></th>
                                                                        <?php endfor; ?>
                                                                </tr>
                                                        </thead>
                                                        <tbody>
                                                                <?php foreach ( $group['rows'] as $row ) : ?>
                                                                        <tr>
                                                                                <th scope="row" class="hr-package-rates-hotel"><?php echo esc_html( $row['hotel']->name ); ?></th>
                                                                                <td class="hr-package-rates-meal"><?php echo esc_html( $this->get_package_meal_plan_label( $row['rate']->meal_plan ?? '' ) ); ?></td>
                                                                                <?php for ( $pax = 1; $pax <= $max_pax; $pax++ ) : ?>
                                                                                        <?php
                                                                                        $column = 'pax_' . $pax;
                                                                                        $value  = isset( $row['rate']->$column ) && '' !== $row['rate']->$column ? $row['rate']->$column : null;
                                                                                        ?>
                                                                                        <td class="hr-package-rates-price">
                                                                                                <?php echo null === $value ? '&mdash;' : wp_kses_post( $this->format_package_rate_price( $value ) ); ?>
                                                                                        </td>
                                                                                <?php endfor; ?>
                                                                        </tr>
                                                                <?php endforeach; ?>
                                                        </tbody>
                                                </table>
                                        </div>
                                </section>
                        <?php endforeach; ?>
                </div>
                <?php

                return ob_get_clean();
        }

        /* ── Tour Rate Table (Day Tours / Joiners) ───────────────────────── */

        /**
         * [tour_day_rate_table city="..." type="Day Tour" max_pax="8" title="Tour Rates"]
         */
        public function tour_rate_table( $atts ) {
                global $wpdb;

                $atts = shortcode_atts( array(
                        'city'    => '',
                        'type'    => 'Day Tour',
                        'max_pax' => 8,
                        'title'   => '',
                ), $atts, 'tour_day_rate_table' );

                $city    = sanitize_text_field( $atts['city'] );
                $type    = sanitize_text_field( $atts['type'] );
                $max_pax = max( 1, min( 12, (int) $atts['max_pax'] ) );

                if ( ! $city ) {
                        return '<p class="hr-sc-error">tour_day_rate_table: please provide a <code>city</code> attribute.</p>';
                }

                $place = $this->find_place( $city );
                if ( ! $place ) {
                        return '<p class="hr-sc-error">tour_day_rate_table: place "' . esc_html( $city ) . '" not found. Check that the place is registered in Hotel Rates → Register.</p>';
                }

                $rows = Hotel_Rates_DB::get_tour_rates( $place->id, $type );
                if ( empty( $rows ) ) {
                        return '<p class="hr-sc-empty">No ' . esc_html( $type ) . ' rates found for ' . esc_html( ucwords( strtolower( $city ) ) ) . '.</p>';
                }

                $default_title = esc_html( ucwords( strtolower( $city ) ) ) . ' &mdash; ' . esc_html( $type ) . ' Rates';
                $title         = $atts['title'] ? wp_kses_post( $atts['title'] ) : $default_title;

                ob_start();
                ?>
                <div class="hr-sc-wrap">
                        <?php if ( $title ) : ?>
                        <h3 class="hr-sc-title"><?php echo $title; ?></h3>
                        <?php endif; ?>
                        <div class="hr-sc-table-overflow">
                        <table class="hr-sc-table">
                                <thead>
                                        <tr>
                                                <th class="hr-sc-th-city"><?php echo esc_html( $this->truncate_label( ucwords( strtolower( $city ) ) . ' Tours', 16 ) ); ?></th>
                                                <?php for ( $p = 1; $p <= $max_pax; $p++ ) : ?>
                                                <th><?php echo $p; ?> pax</th>
                                                <?php endfor; ?>
                                        </tr>
                                </thead>
                                <tbody>
                                        <?php foreach ( $rows as $row ) : ?>
                                        <tr>
                                                <td class="hr-sc-hotel-name"><?php echo esc_html( $row->tour_name ); ?></td>
                                                <?php for ( $p = 1; $p <= $max_pax; $p++ ) :
                                                        $col = 'pax_' . $p;
                                                        $val = $row->$col;
                                                ?>
                                                <td class="hr-sc-price"><?php echo null !== $val ? wp_kses_post( Hotel_Rates_Currency::format_display_price( $val ) ) : '&mdash;'; ?></td>
                                                <?php endfor; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                </tbody>
                        </table>
                        </div>
                </div>
                <?php
                return ob_get_clean();
        }

        /* ── Helpers ──────────────────────────────────────────────────────── */

        /**
         * Display one linked Day Tour or Joiner rate row.
         *
         * Usage:
         *   [specific_tour_rates]
         *   [specific_tour_rates tour_rate_id="123"]
         *   [specific_tour_rates product_id="456" max_pax="8"]
         */
        public function specific_tour_rates_table( $atts ) {
                $atts = shortcode_atts( array(
                        'tour_rate_id' => 0,
                        'product_id'   => 0,
                        'max_pax'      => Hotel_Rates_DB::DAY_TOUR_MAX_PAX,
                ), $atts, 'specific_tour_rates' );

                $tour_rate_id = absint( $atts['tour_rate_id'] );
                $product_id   = absint( $atts['product_id'] );
                $max_pax      = max( 1, min( Hotel_Rates_DB::DAY_TOUR_MAX_PAX, (int) $atts['max_pax'] ) );

                if ( ! $tour_rate_id ) {
                        if ( ! $product_id ) {
                                $product_id = (int) get_queried_object_id();
                        }

                        if ( ! $product_id ) {
                                $product_id = (int) get_the_ID();
                        }

                        if ( $product_id ) {
                                $tour_rate_id = $this->get_linked_day_or_joiner_tour_rate_id( $product_id );
                        }
                }

                if ( ! $tour_rate_id ) {
                        return '<p class="hr-sc-empty">' . esc_html__( 'No Day Tour or Joiner Tour is linked to this page.', 'hotel-rates' ) . '</p>';
                }

                $tour_rate = Hotel_Rates_DB::get_tour_rate_by_id( $tour_rate_id );

                if ( ! $tour_rate || ! in_array( (string) $tour_rate->type, array( 'Day Tour', 'Joiner' ), true ) ) {
                        return '<p class="hr-sc-error">' . esc_html__( 'The selected tour rate must be a Day Tour or Joiner Tour.', 'hotel-rates' ) . '</p>';
                }

                $type_max_pax = 'Day Tour' === (string) $tour_rate->type
                        ? Hotel_Rates_DB::DAY_TOUR_MAX_PAX
                        : Hotel_Rates_DB::MAX_PAX;
                $max_pax = min( $max_pax, $type_max_pax );

                $package_details = Hotel_Rates_DB::normalize_tour_package_details( $tour_rate->package_details ?? '' );
                $children_rates  = Hotel_Rates_DB::normalize_tour_children_rates( $tour_rate->children_rates ?? '' );
                $child_discount  = isset( $children_rates['discount_percent'] ) ? (float) $children_rates['discount_percent'] : 0.0;
                $child_discount_label = floor( $child_discount ) === $child_discount
                        ? number_format_i18n( $child_discount, 0 )
                        : rtrim( rtrim( number_format_i18n( $child_discount, 2 ), '0' ), '.,' );
                $child_discount_text = sprintf(
                        __( 'Each child uses the adult rate for the total adults plus children count, then %s%% is applied.', 'hotel-rates' ),
                        $child_discount_label
                );
                $rate_rows = array();

                for ( $pax = 1; $pax <= $max_pax; $pax++ ) {
                        $column = 'pax_' . $pax;
                        $value  = isset( $tour_rate->$column ) && '' !== $tour_rate->$column ? $tour_rate->$column : null;

                        if ( in_array( (string) $tour_rate->type, array( 'Day Tour', 'Joiner' ), true ) && ( ! is_numeric( $value ) || (float) $value <= 0 ) ) {
                                continue;
                        }

                        $rate_rows[] = array(
                                'pax'   => $pax,
                                'value' => $value,
                        );
                }

                if ( 'Joiner' === (string) $tour_rate->type && ! empty( $rate_rows ) ) {
                        return $this->render_specific_joiner_rate(
                                $tour_rate_id,
                                $tour_rate,
                                $rate_rows,
                                $package_details,
                                $children_rates,
                                $child_discount_text
                        );
                }

                if ( in_array( (string) $tour_rate->type, array( 'Day Tour', 'Joiner' ), true ) && empty( $rate_rows ) ) {
                        return '<p class="hr-sc-empty">' . esc_html__( 'This tour does not currently have passenger rates configured.', 'hotel-rates' ) . '</p>';
                }


                ob_start();
                ?>
                <div
                        class="hr-sc-wrap hr-specific-tour-rates"
                        data-tour-rate-id="<?php echo esc_attr( $tour_rate_id ); ?>"
                        data-tour-type="<?php echo esc_attr( $tour_rate->type ); ?>"
                >
                        <div class="hr-sc-table-overflow">
                                <table class="hr-sc-table hr-specific-tour-rate-table">
                                        <colgroup>
                                                <col class="hr-specific-tour-rate-col-pax">
                                                <col class="hr-specific-tour-rate-col-price">
                                                <col class="hr-specific-tour-rate-col-details">
                                        </colgroup>
                                        <thead>
                                                <tr>
                                                        <th scope="col"><?php esc_html_e( 'No. of Pax', 'hotel-rates' ); ?></th>
                                                        <th scope="col"><?php esc_html_e( 'Rate per Person', 'hotel-rates' ); ?></th>
                                                        <th scope="col"><?php esc_html_e( 'Details of the Package', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php foreach ( $rate_rows as $rate_row_index => $rate_row ) : ?>
                                                        <tr>
                                                                <td class="hr-specific-tour-rate-pax"><?php echo esc_html( $rate_row['pax'] ); ?></td>
                                                                <td class="hr-specific-tour-rate-price"><?php echo null === $rate_row['value'] ? '&mdash;' : wp_kses_post( Hotel_Rates_Currency::format_display_price( $rate_row['value'] ) ); ?></td>
                                                                <?php if ( 0 === $rate_row_index ) : ?>
                                                                        <td class="hr-specific-tour-package-details" rowspan="<?php echo esc_attr( count( $rate_rows ) ); ?>">
                                                                                <?php if ( ! empty( $package_details ) ) : ?>
                                                                                        <?php foreach ( $package_details as $detail_group ) : ?>
                                                                                                <section class="hr-specific-tour-package-detail-group">
                                                                                                        <h4><?php echo esc_html( $detail_group['header'] ); ?></h4>
                                                                                                        <ul>
                                                                                                                <?php foreach ( $detail_group['items'] as $detail_item ) : ?>
                                                                                                                        <li><?php echo esc_html( $detail_item ); ?></li>
                                                                                                                <?php endforeach; ?>
                                                                                                        </ul>
                                                                                                </section>
                                                                                        <?php endforeach; ?>
                                                                                <?php else : ?>
                                                                                        &mdash;
                                                                                <?php endif; ?>
                                                                        </td>
                                                                <?php endif; ?>
                                                        </tr>
                                                <?php endforeach; ?>
						<?php if ( ! empty( $children_rates['enabled'] ) ) : ?>
							<tr class="hr-specific-tour-child-discount-row hr-specific-tour-desktop-child-discount">
								<td colspan="3">
									<?php echo esc_html( $child_discount_text ); ?>
								</td>
							</tr>
						<?php endif; ?>
						<?php if ( ! empty( $children_rates['enabled'] ) ) : ?>
							<tr class="hr-specific-tour-child-discount-row hr-specific-tour-mobile-child-discount">
								<td colspan="3">
									<strong><?php esc_html_e( 'Child Rate Information', 'hotel-rates' ); ?></strong>
									<p><?php echo esc_html( $child_discount_text ); ?></p>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
                <?php

                return ob_get_clean();
        }

        /**
         * Render the compact two-column rate table used only by Joiner tours.
         */
        private function render_specific_joiner_rate( $tour_rate_id, $tour_rate, $rate_rows, $package_details, $children_rates, $child_discount_text ) {
                $joiner_rate = reset( $rate_rows );
                $quote       = chr( 34 );
                $html        = '<div class=' . $quote . 'hr-sc-wrap hr-specific-tour-rates hr-specific-tour-rates-joiner' . $quote;
                $html       .= ' data-tour-rate-id=' . $quote . esc_attr( $tour_rate_id ) . $quote;
                $html       .= ' data-tour-type=' . $quote . esc_attr( $tour_rate->type ) . $quote . '>';
                $html       .= '<div class=' . $quote . 'hr-sc-table-overflow' . $quote . '>';
                $html       .= '<table class=' . $quote . 'hr-sc-table hr-specific-tour-rate-table hr-specific-tour-rate-table-joiner' . $quote . '>';
                $html       .= '<colgroup><col class=' . $quote . 'hr-specific-tour-rate-col-price' . $quote . '>';
                $html       .= '<col class=' . $quote . 'hr-specific-tour-rate-col-details' . $quote . '></colgroup>';
                $html       .= '<thead><tr><th scope=col>' . esc_html__( 'Rate per Person', 'hotel-rates' ) . '</th>';
                $html       .= '<th scope=col>' . esc_html__( 'Details of the Package', 'hotel-rates' ) . '</th></tr></thead><tbody><tr>';
                $html       .= '<td class=' . $quote . 'hr-specific-tour-rate-price' . $quote . '>';
                $html       .= wp_kses_post( Hotel_Rates_Currency::format_display_price( $joiner_rate['value'] ) ) . '</td>';
                $html       .= '<td class=' . $quote . 'hr-specific-tour-package-details' . $quote . '>';

                if ( ! empty( $package_details ) ) {
                        foreach ( $package_details as $detail_group ) {
                                $html .= '<section class=' . $quote . 'hr-specific-tour-package-detail-group' . $quote . '>';
                                $html .= '<h4>' . esc_html( $detail_group['header'] ) . '</h4><ul>';
                                foreach ( $detail_group['items'] as $detail_item ) {
                                        $html .= '<li>' . esc_html( $detail_item ) . '</li>';
                                }
                                $html .= '</ul></section>';
                        }
                } else {
                        $html .= '&mdash;';
                }

                $html .= '</td></tr>';

                if ( ! empty( $children_rates['enabled'] ) ) {
                        $html .= '<tr class=' . $quote . 'hr-specific-tour-child-discount-row' . $quote . '>';
                        $html .= '<td colspan=2>' . esc_html( $child_discount_text ) . '</td></tr>';
                }

                return $html . '</tbody></table></div></div>';
        }

        /**
         * Display the rate matrix linked to a Port Transfer product.
         *
         * Usage:
         *   [specific_port_transfer_rates]
         *   [specific_port_transfer_rates port_transfer_id=123]
         *   [specific_port_transfer_rates product_id=456]
         */
        public function specific_port_transfer_rates_table( $atts ) {
                $atts = shortcode_atts( array(
                        'port_transfer_id' => 0,
                        'product_id'       => 0,
                ), $atts, 'specific_port_transfer_rates' );

                $port_transfer_id = absint( $atts['port_transfer_id'] );
                $product_id       = absint( $atts['product_id'] );

                if ( ! $port_transfer_id ) {
                        if ( ! $product_id ) {
                                $product_id = (int) get_queried_object_id();
                        }

                        if ( ! $product_id ) {
                                $product_id = (int) get_the_ID();
                        }

                        if ( $product_id ) {
                                $port_transfer_id = (int) get_post_meta( $product_id, '_hr_port_transfer_id', true );
                        }
                }

                if ( ! $port_transfer_id ) {
                        return '<p class=hr-sc-empty>' . esc_html__( 'No Port Transfer is linked to this page.', 'hotel-rates' ) . '</p>';
                }

                $port_transfer = Hotel_Rates_DB::get_port_transfer( $port_transfer_id );

                if ( ! $port_transfer ) {
                        return '<p class=hr-sc-error>' . esc_html__( 'The selected Port Transfer could not be found.', 'hotel-rates' ) . '</p>';
                }

                $config      = Hotel_Rates_DB::normalize_port_transfer_config( $port_transfer->routes ?? '[]' );
                $rate_groups = $this->group_specific_port_transfer_rates( $config );

                if ( empty( $rate_groups ) ) {
                        return '<p class=hr-sc-empty>' . esc_html__( 'This Port Transfer does not currently have rates configured.', 'hotel-rates' ) . '</p>';
                }

                ob_start();
                ?>
                <div class='hr-sc-wrap hr-specific-port-transfer-rates' data-port-transfer-id='<?php echo esc_attr( $port_transfer_id ); ?>'>
                        <?php foreach ( $rate_groups as $rate_group ) : ?>
                                <?php $source_label = implode( ' & ', $rate_group['sources'] ); ?>
                                <div class='hr-port-transfer-rate-group'>
                                        <div class='hr-sc-table-overflow'>
                                                <table class='hr-sc-table hr-port-transfer-rate-table'>
                                                        <colgroup>
                                                                <col class='hr-port-transfer-route-col'>
                                                                <col class='hr-port-transfer-price-col'>
                                                        </colgroup>
                                                        <thead>
                                                                <tr>
                                                                        <th scope='col' class='hr-port-transfer-route-heading'><?php echo esc_html( sprintf( __( 'To/From %s:', 'hotel-rates' ), $source_label ) ); ?></th>
                                                                        <th scope='col' class='hr-port-transfer-rate-heading'><?php esc_html_e( 'Rates', 'hotel-rates' ); ?></th>
                                                                </tr>
                                                        </thead>
                                                        <tbody>
                                                                <?php foreach ( $rate_group['rows'] as $rate_row ) : ?>
                                                                        <tr>
                                                                                <td class='hr-port-transfer-route'><?php echo esc_html( $rate_row['destination'] ); ?></td>
                                                                                <td class='hr-port-transfer-price'><?php echo wp_kses_post( Hotel_Rates_Currency::format_display_price( $rate_row['rate'] ) ); ?></td>
                                                                        </tr>
                                                                <?php endforeach; ?>
                                                        </tbody>
                                                </table>
                                        </div>
                                </div>
                        <?php endforeach; ?>
                </div>
                <?php

                return ob_get_clean();
        }

        /**
         * Combine source locations when their destination rate matrices match.
         */
        private function group_specific_port_transfer_rates( $config ) {
                $from_options = isset( $config['fromOptions'] ) && is_array( $config['fromOptions'] ) ? $config['fromOptions'] : array();
                $to_options   = isset( $config['toOptions'] ) && is_array( $config['toOptions'] ) ? $config['toOptions'] : array();
                $rates        = isset( $config['rates'] ) && is_array( $config['rates'] ) ? $config['rates'] : array();
                $rate_lookup  = array();
                $groups       = array();
                $group_lookup = array();

                foreach ( $rates as $rate ) {
                        if ( ! is_array( $rate ) || ! isset( $rate['from'], $rate['to'], $rate['rate'] ) || ! is_numeric( $rate['rate'] ) ) {
                                continue;
                        }

                        $rate_lookup[ (string) $rate['from'] ][ (string) $rate['to'] ] = (float) $rate['rate'];
                }

                foreach ( $from_options as $source ) {
                        $rows = array();

                        foreach ( $to_options as $destination ) {
                                if ( ! isset( $rate_lookup[ $source ] ) || ! array_key_exists( $destination, $rate_lookup[ $source ] ) ) {
                                        continue;
                                }

                                $rows[] = array(
                                        'destination' => (string) $destination,
                                        'rate'        => (float) $rate_lookup[ $source ][ $destination ],
                                );
                        }

                        if ( empty( $rows ) ) {
                                continue;
                        }

                        $signature = wp_json_encode( $rows );

                        if ( isset( $group_lookup[ $signature ] ) ) {
                                $groups[ $group_lookup[ $signature ] ]['sources'][] = (string) $source;
                                continue;
                        }

                        $group_lookup[ $signature ] = count( $groups );
                        $groups[] = array(
                                'sources' => array( (string) $source ),
                                'rows'    => $rows,
                        );
                }

                return $groups;
        }

        /**
         * Display the destination rates linked to a Destination Tour product.
         *
         * Usage:
         *   [specific_destination_tour_rates]
         *   [specific_destination_tour_rates destination_tour_id=123]
         *   [specific_destination_tour_rates product_id=456]
         */
        public function specific_destination_tour_rates_table( $atts ) {
                $atts = shortcode_atts( array(
                        'destination_tour_id' => 0,
                        'product_id'          => 0,
                ), $atts, 'specific_destination_tour_rates' );

                $destination_tour_id = absint( $atts['destination_tour_id'] );
                $product_id          = absint( $atts['product_id'] );

                if ( ! $destination_tour_id ) {
                        if ( ! $product_id ) {
                                $product_id = (int) get_queried_object_id();
                        }

                        if ( ! $product_id ) {
                                $product_id = (int) get_the_ID();
                        }

                        if ( $product_id ) {
                                $destination_tour_id = (int) get_post_meta( $product_id, '_hr_destination_tour_id', true );
                        }
                }

                if ( ! $destination_tour_id ) {
                        return '<p class=hr-sc-empty>' . esc_html__( 'No Destination Tour is linked to this page.', 'hotel-rates' ) . '</p>';
                }

                $destination_tour = Hotel_Rates_DB::get_destination_tour( $destination_tour_id );

                if ( ! $destination_tour ) {
                        return '<p class=hr-sc-error>' . esc_html__( 'The selected Destination Tour could not be found.', 'hotel-rates' ) . '</p>';
                }

                $destinations = json_decode( $destination_tour->destinations ?? '[]', true );
                $destinations = Hotel_Rates_DB::normalize_destination_tour_destinations( is_array( $destinations ) ? $destinations : array() );

                if ( empty( $destinations ) ) {
                        return '<p class=hr-sc-empty>' . esc_html__( 'This Destination Tour does not currently have rates configured.', 'hotel-rates' ) . '</p>';
                }

                ob_start();
                ?>
                <div class='hr-sc-wrap hr-specific-destination-tour-rates' data-destination-tour-id='<?php echo esc_attr( $destination_tour_id ); ?>'>
                        <div class='hr-sc-table-overflow'>
                                <table class='hr-sc-table hr-destination-tour-rate-table'>
                                        <colgroup>
                                                <col class='hr-destination-tour-name-col'>
                                                <col class='hr-destination-tour-duration-col'>
                                                <col class='hr-destination-tour-price-col'>
                                        </colgroup>
                                        <thead>
                                                <tr>
                                                        <th scope='col' class='hr-destination-tour-name-heading'><?php esc_html_e( 'Tourist Destinations:', 'hotel-rates' ); ?></th>
                                                        <th scope='col' class='hr-destination-tour-duration-heading'><?php esc_html_e( 'Duration', 'hotel-rates' ); ?></th>
                                                        <th scope='col' class='hr-destination-tour-rate-heading'><?php esc_html_e( 'Rates', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php foreach ( $destinations as $destination ) : ?>
                                                        <tr>
                                                                <td class='hr-destination-tour-name'><?php echo esc_html( $destination['label'] ); ?></td>
                                                                <td class='hr-destination-tour-duration'><?php echo '' !== $destination['duration'] ? esc_html( $destination['duration'] ) : '&mdash;'; ?></td>
                                                                <td class='hr-destination-tour-price'><?php echo wp_kses_post( Hotel_Rates_Currency::format_display_price( $destination['rate'] ) ); ?></td>
                                                        </tr>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                        </div>
                </div>
                <?php

                return ob_get_clean();
        }

        /**
         * Display the daily rates linked to a Self-Drive Rental product.
         *
         * Usage:
         *   [specific_self_drive_rates]
         *   [specific_self_drive_rates self_drive_rental_id=123]
         *   [specific_self_drive_rates product_id=456]
         */
        public function specific_self_drive_rates_table( $atts ) {
                $atts = shortcode_atts( array(
                        'self_drive_rental_id' => 0,
                        'product_id'           => 0,
                ), $atts, 'specific_self_drive_rates' );

                $self_drive_rental_id = absint( $atts['self_drive_rental_id'] );
                $product_id           = absint( $atts['product_id'] );

                if ( ! $self_drive_rental_id ) {
                        if ( ! $product_id ) {
                                $product_id = (int) get_queried_object_id();
                        }

                        if ( ! $product_id ) {
                                $product_id = (int) get_the_ID();
                        }

                        if ( $product_id ) {
                                $self_drive_rental_id = (int) get_post_meta( $product_id, '_hr_self_drive_rental_id', true );
                        }
                }

                if ( ! $self_drive_rental_id ) {
                        return '<p class=hr-sc-empty>' . esc_html__( 'No Self-Drive Rental is linked to this page.', 'hotel-rates' ) . '</p>';
                }

                $self_drive_rental = Hotel_Rates_DB::get_self_drive_rental( $self_drive_rental_id );

                if ( ! $self_drive_rental ) {
                        return '<p class=hr-sc-error>' . esc_html__( 'The selected Self-Drive Rental could not be found.', 'hotel-rates' ) . '</p>';
                }

                $vehicles = json_decode( $self_drive_rental->vehicles ?? '[]', true );
                $vehicles = Hotel_Rates_DB::normalize_self_drive_vehicles( is_array( $vehicles ) ? $vehicles : array() );

                if ( empty( $vehicles ) ) {
                        return '<p class=hr-sc-empty>' . esc_html__( 'This Self-Drive Rental does not currently have vehicle rates configured.', 'hotel-rates' ) . '</p>';
                }

                ob_start();
                ?>
                <div class='hr-sc-wrap hr-specific-self-drive-rates' data-self-drive-rental-id='<?php echo esc_attr( $self_drive_rental_id ); ?>'>
                        <div class='hr-sc-table-overflow'>
                                <table class='hr-sc-table hr-self-drive-rate-table'>
                                        <colgroup>
                                                <col class='hr-self-drive-vehicle-col'>
                                                <col class='hr-self-drive-seaters-col'>
                                                <col class='hr-self-drive-price-col'>
                                        </colgroup>
                                        <thead>
                                                <tr>
                                                        <th scope='col' class='hr-self-drive-vehicle-heading'><?php esc_html_e( 'Vehicle Type', 'hotel-rates' ); ?></th>
                                                        <th scope='col' class='hr-self-drive-seaters-heading'><?php esc_html_e( 'Seaters', 'hotel-rates' ); ?></th>
                                                        <th scope='col' class='hr-self-drive-rate-heading'><?php esc_html_e( 'Rates', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php foreach ( $vehicles as $vehicle ) : ?>
                                                        <tr>
                                                                <td class='hr-self-drive-vehicle'><?php echo esc_html( $vehicle['name'] ); ?></td>
                                                                <td class='hr-self-drive-seaters'><?php echo esc_html( $vehicle['seaters'] ); ?></td>
                                                                <td class='hr-self-drive-price'><?php echo wp_kses_post( Hotel_Rates_Currency::format_display_price( $vehicle['rate'] ) ); ?></td>
                                                        </tr>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                        </div>
                </div>
                <?php

                return ob_get_clean();
        }

        /**
         * Display the daily rates linked to a Motorbike Rental product.
         *
         * Usage:
         *   [specific_motorbike_rates]
         *   [specific_motorbike_rates motorbike_rental_id=123]
         *   [specific_motorbike_rates product_id=456]
         */
        public function specific_motorbike_rates_table( $atts ) {
                $atts = shortcode_atts( array(
                        'motorbike_rental_id' => 0,
                        'product_id'          => 0,
                ), $atts, 'specific_motorbike_rates' );

                $motorbike_rental_id = absint( $atts['motorbike_rental_id'] );
                $product_id          = absint( $atts['product_id'] );

                if ( ! $motorbike_rental_id ) {
                        if ( ! $product_id ) {
                                $product_id = (int) get_queried_object_id();
                        }

                        if ( ! $product_id ) {
                                $product_id = (int) get_the_ID();
                        }

                        if ( $product_id ) {
                                $motorbike_rental_id = (int) get_post_meta( $product_id, '_hr_motorbike_rental_id', true );
                        }
                }

                if ( ! $motorbike_rental_id ) {
                        return '<p class=hr-sc-empty>' . esc_html__( 'No Motorbike Rental is linked to this page.', 'hotel-rates' ) . '</p>';
                }

                $motorbike_rental = Hotel_Rates_DB::get_motorbike_rental( $motorbike_rental_id );

                if ( ! $motorbike_rental ) {
                        return '<p class=hr-sc-error>' . esc_html__( 'The selected Motorbike Rental could not be found.', 'hotel-rates' ) . '</p>';
                }

                $models = json_decode( $motorbike_rental->models ?? '[]', true );
                $models = Hotel_Rates_DB::normalize_motorbike_models( is_array( $models ) ? $models : array() );

                if ( empty( $models ) ) {
                        return '<p class=hr-sc-empty>' . esc_html__( 'This Motorbike Rental does not currently have model rates configured.', 'hotel-rates' ) . '</p>';
                }

                ob_start();
                ?>
                <div class='hr-sc-wrap hr-specific-motorbike-rates' data-motorbike-rental-id='<?php echo esc_attr( $motorbike_rental_id ); ?>'>
                        <div class='hr-sc-table-overflow'>
                                <table class='hr-sc-table hr-motorbike-rate-table'>
                                        <colgroup>
                                                <col class='hr-motorbike-model-col'>
                                                <col class='hr-motorbike-engine-col'>
                                                <col class='hr-motorbike-price-col'>
                                        </colgroup>
                                        <thead>
                                                <tr>
                                                        <th scope='col' class='hr-motorbike-model-heading'><?php esc_html_e( 'Motorbike Model', 'hotel-rates' ); ?></th>
                                                        <th scope='col' class='hr-motorbike-engine-heading'><?php esc_html_e( 'Engine', 'hotel-rates' ); ?></th>
                                                        <th scope='col' class='hr-motorbike-rate-heading'><?php esc_html_e( 'Rates', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php foreach ( $models as $model ) : ?>
                                                        <tr>
                                                                <td class='hr-motorbike-model'><?php echo esc_html( $model['name'] ); ?></td>
                                                                <td class='hr-motorbike-engine'><?php echo '' !== $model['engine'] ? esc_html( $model['engine'] ) : '&mdash;'; ?></td>
                                                                <td class='hr-motorbike-price'><?php echo wp_kses_post( Hotel_Rates_Currency::format_display_price( $model['rate'] ) ); ?></td>
                                                        </tr>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                        </div>
                </div>
                <?php

                return ob_get_clean();
        }

        /**
         * Display the capacity rates linked to a Boat Rental product.
         *
         * Usage:
         *   [specific_boat_rates]
         *   [specific_boat_rates boat_rental_id=123]
         *   [specific_boat_rates product_id=456]
         */
        public function specific_boat_rates_table( $atts ) {
                $atts = shortcode_atts( array(
                        'boat_rental_id' => 0,
                        'product_id'     => 0,
                ), $atts, 'specific_boat_rates' );

                $boat_rental_id = absint( $atts['boat_rental_id'] );
                $product_id     = absint( $atts['product_id'] );

                if ( ! $boat_rental_id ) {
                        if ( ! $product_id ) {
                                $product_id = (int) get_queried_object_id();
                        }

                        if ( ! $product_id ) {
                                $product_id = (int) get_the_ID();
                        }

                        if ( $product_id ) {
                                $boat_rental_id = (int) get_post_meta( $product_id, '_hr_boat_rental_id', true );
                        }
                }

                if ( ! $boat_rental_id ) {
                        return '<p class=hr-sc-empty>' . esc_html__( 'No Boat Rental is linked to this page.', 'hotel-rates' ) . '</p>';
                }

                $boat_rental = Hotel_Rates_DB::get_boat_rental( $boat_rental_id );

                if ( ! $boat_rental ) {
                        return '<p class=hr-sc-error>' . esc_html__( 'The selected Boat Rental could not be found.', 'hotel-rates' ) . '</p>';
                }

                $rates = json_decode( $boat_rental->rates ?? '[]', true );
                $rates = Hotel_Rates_DB::normalize_boat_rates( is_array( $rates ) ? $rates : array() );

                if ( empty( $rates ) ) {
                        return '<p class=hr-sc-empty>' . esc_html__( 'This Boat Rental does not currently have capacity rates configured.', 'hotel-rates' ) . '</p>';
                }

                ob_start();
                ?>
                <div class='hr-sc-wrap hr-specific-boat-rates' data-boat-rental-id='<?php echo esc_attr( $boat_rental_id ); ?>'>
                        <div class='hr-sc-table-overflow'>
                                <table class='hr-sc-table hr-boat-rate-table'>
                                        <colgroup>
                                                <col class='hr-boat-capacity-col'>
                                                <col class='hr-boat-price-col'>
                                        </colgroup>
                                        <thead>
                                                <tr>
                                                        <th scope='col' class='hr-boat-capacity-heading'><?php esc_html_e( 'Boat Capacity', 'hotel-rates' ); ?></th>
                                                        <th scope='col' class='hr-boat-rate-heading'><?php esc_html_e( 'Rates', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php foreach ( $rates as $rate ) : ?>
                                                        <tr>
                                                                <td class='hr-boat-capacity'><?php echo esc_html( $rate['label'] ); ?></td>
                                                                <td class='hr-boat-price'><?php echo wp_kses_post( Hotel_Rates_Currency::format_display_price( $rate['rate'] ) ); ?></td>
                                                        </tr>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                        </div>
                </div>
                <?php

                return ob_get_clean();
        }

        /**
         * Resolve only Day Tour and Joiner product links. Day Tour follows the
         * same priority used by the booking form when both links are present.
         */
        private function get_linked_day_or_joiner_tour_rate_id( $product_id ) {
                $day_tour_rate_id = (int) get_post_meta( $product_id, '_hr_day_tour_rate_id', true );

                if ( $day_tour_rate_id ) {
                        return $day_tour_rate_id;
                }

                $joiner_tour_rate_id = (int) get_post_meta( $product_id, '_hr_joiner_tour_rate_id', true );

                if ( $joiner_tour_rate_id ) {
                        return $joiner_tour_rate_id;
                }

                $legacy_tour_rate_id = (int) get_post_meta( $product_id, '_hr_tour_rate_id', true );

                if ( ! $legacy_tour_rate_id ) {
                        return 0;
                }

                $legacy_tour_rate = Hotel_Rates_DB::get_tour_rate_by_id( $legacy_tour_rate_id );

                return $legacy_tour_rate && in_array( (string) $legacy_tour_rate->type, array( 'Day Tour', 'Joiner' ), true )
                        ? $legacy_tour_rate_id
                        : 0;
        }

        /**
         * Find a place row by name.
         * Tries (in order):
         *   1. Exact match (case-insensitive)
         *   2. DB name contains the given city string (LIKE %city%)
         *   3. Given city string contains the DB name (catch "Dumaguete" matching "Dumaguete City")
         */
        private function find_place( $city ) {
                global $wpdb;

                // 1. Exact (case-insensitive)
                $place = $wpdb->get_row( $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}hr_places WHERE LOWER(TRIM(name)) = LOWER(TRIM(%s)) LIMIT 1",
                        $city
                ) );
                if ( $place ) return $place;

                // 2. DB name contains input  (e.g. input "Dumaguete" matches "Dumaguete City")
                $like  = '%' . $wpdb->esc_like( $city ) . '%';
                $place = $wpdb->get_row( $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}hr_places WHERE LOWER(name) LIKE LOWER(%s) LIMIT 1",
                        $like
                ) );
                if ( $place ) return $place;

                // 3. Input contains DB name  (e.g. input "Dumaguete City" matches stored "Dumaguete")
                $all = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}hr_places ORDER BY LENGTH(name) DESC" );
                foreach ( $all as $p ) {
                        if ( stripos( $city, $p->name ) !== false ) {
                                return $p;
                        }
                }

                return null;
        }

        /**
         * Extract the integer from strings like "2 Nights", "1 Night", "3N", "3".
         */
        private function parse_nights( $value ) {
                preg_match( '/(\d+)/', (string) $value, $m );
                return isset( $m[1] ) ? (int) $m[1] : 0;
        }

        private function get_rate_prices( $row ) {
                $prices = array();

                for ( $p = 1; $p <= Hotel_Rates_DB::MAX_PAX; $p++ ) {
                        $col = 'pax_' . $p;
                        $prices[ (string) $p ] = isset( $row->$col ) && $row->$col !== null ? (float) $row->$col : null;
                }

                return $prices;
        }

        private function get_booking_label( $row, $has_breakfast ) {
                $label = $row->hotel_name;

                if ( $has_breakfast ) {
                        $label .= ' (' . __( 'Breakfast included', 'hotel-rates' ) . ')';
                } elseif ( ! empty( $row->meal_plan ) && strtoupper( $row->meal_plan ) !== 'X' ) {
                        $label .= ' (' . $row->meal_plan . ')';
                }

                return $label;
        }

        /**
         * Match the rate-selection rule used by the Package Deal booking form:
         * prefer the no-meal/"X" row, otherwise use the first saved rate row.
         */
        private function get_preferred_package_hotel_rate( $rates ) {
                if ( empty( $rates ) || ! is_array( $rates ) ) {
                        return null;
                }

                $fallback = null;

                foreach ( $rates as $rate ) {
                        if ( ! is_object( $rate ) ) {
                                continue;
                        }

                        if ( null === $fallback ) {
                                $fallback = $rate;
                        }

                        $meal_plan = isset( $rate->meal_plan ) ? strtoupper( trim( (string) $rate->meal_plan ) ) : '';

                        if ( '' === $meal_plan || 'X' === $meal_plan ) {
                                return $rate;
                        }
                }

                return $fallback;
        }

        private function get_package_meal_plan_label( $meal_plan ) {
                $meal_plan = trim( (string) $meal_plan );

                if ( '' === $meal_plan || 'X' === strtoupper( $meal_plan ) ) {
                        return __( 'No breakfast', 'hotel-rates' );
                }

                return $meal_plan;
        }

        private function format_package_rate_price( $value ) {
                return Hotel_Rates_Currency::format_display_price( $value );
        }

        /**
         * Truncate a label and add "…" if too long. Used for table column headers.
         */
        private function truncate_label( $text, $max = 14 ) {
                return mb_strlen( $text ) > $max ? mb_substr( $text, 0, $max ) . '…' : $text;
        }
}
