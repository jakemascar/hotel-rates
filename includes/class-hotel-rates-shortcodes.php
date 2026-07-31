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
 * Optional attribute for both: max_pax="8"  (default 8, max 12)
 */
class Hotel_Rates_Shortcodes {

        public function __construct() {
                add_shortcode( 'tour_rate_table',     array( $this, 'hotel_rate_table' ) );
                add_shortcode( 'tour_day_rate_table', array( $this, 'tour_rate_table' ) );
                add_action( 'wp_enqueue_scripts',     array( $this, 'enqueue_styles' ) );
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
         * Truncate a label and add "…" if too long. Used for table column headers.
         */
        private function truncate_label( $text, $max = 14 ) {
                return mb_strlen( $text ) > $max ? mb_substr( $text, 0, $max ) . '…' : $text;
        }
}
