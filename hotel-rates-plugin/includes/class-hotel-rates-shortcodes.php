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
                add_filter( 'the_content', array( $this, 'render_specific_tour_rates_in_content' ), 99 );
                add_action( 'wp_enqueue_scripts',     array( $this, 'enqueue_styles' ) );
        }

        /**
         * Expand only [specific_tour_rates] when content is added after the
         * normal shortcode pass, as happens in some product accordion renderers.
         *
         * @param string $content Filtered post content.
         * @return string
         */
        public function render_specific_tour_rates_in_content( $content ) {
                if ( false === strpos( $content, '[specific_tour_rates' ) ) {
                        return $content;
                }

                $pattern = get_shortcode_regex( array( 'specific_tour_rates' ) );

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
                                                <td class="hr-sc-price"><?php echo $val !== null ? '&#8369;' . number_format( (float) $val, 0 ) : '&mdash;'; ?></td>
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
         *   [package_deal_hotel_rates product_id="123" max_pax="8" title="Hotel options"]
         */
        public function package_deal_hotel_rates_table( $atts ) {
                $atts = shortcode_atts( array(
                        'package_id' => 0,
                        'product_id' => 0,
                        'max_pax'    => Hotel_Rates_DB::MAX_PAX,
                        'title'      => '',
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

                $title = '' !== trim( (string) $atts['title'] )
                        ? wp_kses_post( $atts['title'] )
                        : sprintf( esc_html__( '%s Hotel Rates', 'hotel-rates' ), esc_html( $package->package_name ) );

                ob_start();
                ?>
                <div class="hr-package-rates" data-package-deal-id="<?php echo esc_attr( $package_id ); ?>">
                        <?php if ( $title ) : ?>
                                <h3 class="hr-package-rates-title"><?php echo $title; ?></h3>
                        <?php endif; ?>

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
                                                                        <th scope="col"><?php esc_html_e( 'Hotel', 'hotel-rates' ); ?></th>
                                                                        <th scope="col"><?php esc_html_e( 'Meal Plan', 'hotel-rates' ); ?></th>
                                                                        <?php for ( $pax = 1; $pax <= $max_pax; $pax++ ) : ?>
                                                                                <th scope="col"><?php echo esc_html( sprintf( __( '%d pax', 'hotel-rates' ), $pax ) ); ?></th>
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
                                                <td class="hr-sc-price"><?php echo $val !== null ? '&#8369;' . number_format( (float) $val, 0 ) : '&mdash;'; ?></td>
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
         *   [specific_tour_rates product_id="456" max_pax="8" title="Tour Rates"]
         */
        public function specific_tour_rates_table( $atts ) {
                $atts = shortcode_atts( array(
                        'tour_rate_id' => 0,
                        'product_id'   => 0,
                        'max_pax'      => Hotel_Rates_DB::MAX_PAX,
                        'title'        => '',
                ), $atts, 'specific_tour_rates' );

                $tour_rate_id = absint( $atts['tour_rate_id'] );
                $product_id   = absint( $atts['product_id'] );
                $max_pax      = max( 1, min( Hotel_Rates_DB::MAX_PAX, (int) $atts['max_pax'] ) );

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

                $title = '' !== trim( (string) $atts['title'] )
                        ? wp_kses_post( $atts['title'] )
                        : esc_html( $tour_rate->tour_name );

                ob_start();
                ?>
                <div
                        class="hr-sc-wrap hr-specific-tour-rates"
                        data-tour-rate-id="<?php echo esc_attr( $tour_rate_id ); ?>"
                        data-tour-type="<?php echo esc_attr( $tour_rate->type ); ?>"
                >
                        <div class="hr-sc-table-overflow">
                                <table class="hr-sc-table hr-specific-tour-rate-table">
                                        <thead>
                                                <tr class="hr-specific-tour-name-row">
                                                        <th colspan="3" scope="colgroup"><?php echo $title; ?></th>
                                                </tr>
                                                <tr>
                                                        <th scope="col"><?php esc_html_e( 'No. of Pax', 'hotel-rates' ); ?></th>
                                                        <th scope="col"><?php esc_html_e( 'Rate per Person', 'hotel-rates' ); ?></th>
                                                        <th scope="col"><?php esc_html_e( 'Details of the Package', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php for ( $pax = 1; $pax <= $max_pax; $pax++ ) : ?>
                                                                <?php
                                                                $column = 'pax_' . $pax;
                                                                $value  = isset( $tour_rate->$column ) && '' !== $tour_rate->$column ? $tour_rate->$column : null;
                                                                ?>
                                                        <tr>
                                                                <td class="hr-specific-tour-rate-pax"><?php echo esc_html( $pax ); ?></td>
                                                                <td class="hr-specific-tour-rate-price"><?php echo null === $value ? '&mdash;' : '&#8369; ' . esc_html( number_format_i18n( (float) $value, 0 ) ); ?></td>
                                                                <?php if ( 1 === $pax ) : ?>
                                                                        <td class="hr-specific-tour-package-details" rowspan="<?php echo esc_attr( $max_pax ); ?>">
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
                                                <?php endfor; ?>
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
                $value = (float) $value;

                if ( function_exists( 'wc_price' ) ) {
                        return wc_price( $value );
                }

                return '&#8369;' . number_format_i18n( $value, 2 );
        }

        /**
         * Truncate a label and add "…" if too long. Used for table column headers.
         */
        private function truncate_label( $text, $max = 14 ) {
                return mb_strlen( $text ) > $max ? mb_substr( $text, 0, $max ) . '…' : $text;
        }
}
