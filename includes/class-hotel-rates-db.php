<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Hotel_Rates_DB {

        const TOUR_TYPES      = array( 'Day Tour', 'Joiner', 'Airport Transfer', 'Package Deal' );
        const DEFAULT_REGIONS = array( 'Luzon', 'Visayas', 'Mindanao' );
        const MAX_NIGHTS      = 8;
        const MAX_PAX         = 12;

        public static function get_legacy_tour_optional_addon_group_labels() {
                return array(
                        'tour_guide_services'       => __( 'Tour Guide Services', 'hotel-rates' ),
                        'gopro_rental'              => __( 'GoPro Rental', 'hotel-rates' ),
                        'scuba_diving'              => __( 'Scuba Diving', 'hotel-rates' ),
                        'roundtrip_land_transfers'  => __( 'Roundtrip Land Transfers', 'hotel-rates' ),
                );
        }

        /* ── Activation / Upgrade ────────────────────────────────────────── */

        public static function create_tables() {
                global $wpdb;
                $charset = $wpdb->get_charset_collate();

                $pax_cols = self::pax_col_defs();

                $regions = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_regions (
                        id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        name       VARCHAR(255)    NOT NULL,
                        created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";

                $places = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_places (
                        id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        region_id  BIGINT UNSIGNED NOT NULL,
                        name       VARCHAR(255)    NOT NULL,
                        created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY region_id (region_id)
                ) $charset;";

                $hotels = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_hotels (
                        id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        place_id   BIGINT UNSIGNED NOT NULL,
                        name       VARCHAR(255)    NOT NULL,
                        created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY place_id (place_id)
                ) $charset;";

                $rates = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_rates (
                        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        hotel_id    BIGINT UNSIGNED NOT NULL,
                        nights      TINYINT UNSIGNED NOT NULL,
                        meal_plan   VARCHAR(100)    NOT NULL DEFAULT 'X',
                        {$pax_cols}
                        created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY hotel_nights (hotel_id, nights)
                ) $charset;";

                $tour_rates = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_tour_rates (
                        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        region_id   BIGINT UNSIGNED NOT NULL,
                        place_id    BIGINT UNSIGNED NOT NULL,
                        type        VARCHAR(100)    NOT NULL DEFAULT 'Day Tour',
                        tour_name   VARCHAR(255)    NOT NULL,
                        sort_order  INT UNSIGNED    NOT NULL DEFAULT 0,
                        optional_addons LONGTEXT    NULL,
                        transfer_routes LONGTEXT    NULL,
                        booking_fields LONGTEXT     NULL,
                        children_rates LONGTEXT     NULL,
                        {$pax_cols}
                        created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY region_place_type (region_id, place_id, type)
                ) $charset;";

                $package_deals = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_package_deals (
                        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        package_name   VARCHAR(255)     NOT NULL,
                        airport_transfer_fee DECIMAL(12,2) NULL,
                        seaport_transfer_fee DECIMAL(12,2) NULL,
                        number_of_days TINYINT UNSIGNED NOT NULL DEFAULT 1,
                        place_ids      LONGTEXT         NULL,
                        hotel_ids      LONGTEXT         NULL,
                        default_hotel_ids LONGTEXT      NULL,
                        hotel_nights   LONGTEXT         NULL,
                        activities     LONGTEXT         NULL,
                        default_activities LONGTEXT     NULL,
                        free_activity_options LONGTEXT  NULL,
                        transit_day_fees LONGTEXT       NULL,
                        transit_day_days LONGTEXT       NULL,
                        children_rates LONGTEXT         NULL,
                        created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";

                $motorbike_rentals = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_motorbike_rentals (
                        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        rental_name    VARCHAR(255)     NOT NULL,
                        models         LONGTEXT         NULL,
                        start_times    LONGTEXT         NULL,
                        day_options    LONGTEXT         NULL,
                        delivery_fee   DECIMAL(12,2)    NULL,
                        created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";

                $boat_rentals = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_boat_rentals (
                        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        rental_name    VARCHAR(255)     NOT NULL,
                        rates          LONGTEXT         NULL,
                        boarding_times LONGTEXT         NULL,
                        credit_card_fee_percent DECIMAL(8,2) NULL,
                        upgrade_boat_fee_label VARCHAR(255) NULL,
                        upgrade_boat_fee_amount DECIMAL(12,2) NULL,
                        created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";

                $self_drive_rentals = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_self_drive_rentals (
                        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        rental_name    VARCHAR(255)     NOT NULL,
                        vehicles       LONGTEXT         NULL,
                        start_times    LONGTEXT         NULL,
                        day_options    LONGTEXT         NULL,
                        delivery_fee   DECIMAL(12,2)    NULL,
                        credit_card_fee_percent DECIMAL(8,2) NULL,
                        created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";

                $port_transfers = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_port_transfers (
                        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        transfer_name  VARCHAR(255)     NOT NULL,
                        routes         LONGTEXT         NULL,
                        transfer_times LONGTEXT         NULL,
                        created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";

                $destination_tours = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_destination_tours (
                        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        tour_name      VARCHAR(255)     NOT NULL,
                        destinations   LONGTEXT         NULL,
                        pickup_addons  LONGTEXT         NULL,
                        pickup_times   LONGTEXT         NULL,
                        credit_card_fee_percent DECIMAL(8,2) NULL,
                        created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";

                $pickup_dropoff_services = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_pickup_dropoff_services (
                        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        service_name   VARCHAR(255)     NOT NULL,
                        locations      LONGTEXT         NULL,
                        route_rates    LONGTEXT         NULL,
                        pickup_times   LONGTEXT         NULL,
                        addons         LONGTEXT         NULL,
                        created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";

                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                dbDelta( $regions );
                dbDelta( $places );
                dbDelta( $hotels );
                dbDelta( $rates );
                dbDelta( $tour_rates );
                dbDelta( $package_deals );
                dbDelta( $motorbike_rentals );
                dbDelta( $boat_rentals );
                dbDelta( $self_drive_rentals );
                dbDelta( $port_transfers );
                dbDelta( $destination_tours );
                dbDelta( $pickup_dropoff_services );

                self::maybe_upgrade();
                self::seed_default_regions();
        }

        private static function pax_col_defs() {
                $cols = '';
                for ( $p = 1; $p <= self::MAX_PAX; $p++ ) {
                        $cols .= "pax_{$p} DECIMAL(12,2) NULL,\n\t\t\t";
                }
                return $cols;
        }

        public static function maybe_upgrade() {
                global $wpdb;
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                $charset = $wpdb->get_charset_collate();

                $package_deals = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_package_deals (
                        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        package_name   VARCHAR(255)     NOT NULL,
                        airport_transfer_fee DECIMAL(12,2) NULL,
                        seaport_transfer_fee DECIMAL(12,2) NULL,
                        number_of_days TINYINT UNSIGNED NOT NULL DEFAULT 1,
                        place_ids      LONGTEXT         NULL,
                        hotel_ids      LONGTEXT         NULL,
                        default_hotel_ids LONGTEXT      NULL,
                        hotel_nights   LONGTEXT         NULL,
                        activities     LONGTEXT         NULL,
                        default_activities LONGTEXT     NULL,
                        free_activity_options LONGTEXT  NULL,
                        transit_day_fees LONGTEXT       NULL,
                        transit_day_days LONGTEXT       NULL,
                        children_rates LONGTEXT         NULL,
                        created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";
                dbDelta( $package_deals );
                $motorbike_rentals = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_motorbike_rentals (
                        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        rental_name    VARCHAR(255)     NOT NULL,
                        models         LONGTEXT         NULL,
                        start_times    LONGTEXT         NULL,
                        day_options    LONGTEXT         NULL,
                        delivery_fee   DECIMAL(12,2)    NULL,
                        created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";
                dbDelta( $motorbike_rentals );

                $boat_rentals = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_boat_rentals (
                        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        rental_name    VARCHAR(255)     NOT NULL,
                        rates          LONGTEXT         NULL,
                        boarding_times LONGTEXT         NULL,
                        credit_card_fee_percent DECIMAL(8,2) NULL,
                        upgrade_boat_fee_label VARCHAR(255) NULL,
                        upgrade_boat_fee_amount DECIMAL(12,2) NULL,
                        created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";
                dbDelta( $boat_rentals );

                $self_drive_rentals = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_self_drive_rentals (
                        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        rental_name    VARCHAR(255)     NOT NULL,
                        vehicles       LONGTEXT         NULL,
                        start_times    LONGTEXT         NULL,
                        day_options    LONGTEXT         NULL,
                        delivery_fee   DECIMAL(12,2)    NULL,
                        credit_card_fee_percent DECIMAL(8,2) NULL,
                        created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";
                dbDelta( $self_drive_rentals );

                $port_transfers = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_port_transfers (
                        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        transfer_name  VARCHAR(255)     NOT NULL,
                        routes         LONGTEXT         NULL,
                        transfer_times LONGTEXT         NULL,
                        created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";
                dbDelta( $port_transfers );

                $destination_tours = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_destination_tours (
                        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        tour_name      VARCHAR(255)     NOT NULL,
                        destinations   LONGTEXT         NULL,
                        pickup_addons  LONGTEXT         NULL,
                        pickup_times   LONGTEXT         NULL,
                        credit_card_fee_percent DECIMAL(8,2) NULL,
                        created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";
                dbDelta( $destination_tours );

                $pickup_dropoff_services = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hr_pickup_dropoff_services (
                        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        service_name   VARCHAR(255)     NOT NULL,
                        locations      LONGTEXT         NULL,
                        route_rates    LONGTEXT         NULL,
                        pickup_times   LONGTEXT         NULL,
                        addons         LONGTEXT         NULL,
                        created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                ) $charset;";
                dbDelta( $pickup_dropoff_services );

                $boat_rental_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}hr_boat_rentals" );
                if ( ! in_array( 'credit_card_fee_percent', $boat_rental_cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_boat_rentals ADD COLUMN credit_card_fee_percent DECIMAL(8,2) NULL AFTER boarding_times" );
                }
                if ( ! in_array( 'upgrade_boat_fee_label', $boat_rental_cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_boat_rentals ADD COLUMN upgrade_boat_fee_label VARCHAR(255) NULL AFTER credit_card_fee_percent" );
                }
                if ( ! in_array( 'upgrade_boat_fee_amount', $boat_rental_cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_boat_rentals ADD COLUMN upgrade_boat_fee_amount DECIMAL(12,2) NULL AFTER upgrade_boat_fee_label" );
                }

                $self_drive_rental_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}hr_self_drive_rentals" );
                if ( ! in_array( 'credit_card_fee_percent', $self_drive_rental_cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_self_drive_rentals ADD COLUMN credit_card_fee_percent DECIMAL(8,2) NULL AFTER delivery_fee" );
                }

                $destination_tour_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}hr_destination_tours" );
                if ( ! in_array( 'credit_card_fee_percent', $destination_tour_cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_destination_tours ADD COLUMN credit_card_fee_percent DECIMAL(8,2) NULL AFTER pickup_times" );
                }

                $package_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}hr_package_deals" );
                if ( ! in_array( 'airport_transfer_fee', $package_cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_package_deals ADD COLUMN airport_transfer_fee DECIMAL(12,2) NULL AFTER package_name" );
                }
                if ( ! in_array( 'seaport_transfer_fee', $package_cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_package_deals ADD COLUMN seaport_transfer_fee DECIMAL(12,2) NULL AFTER airport_transfer_fee" );
                }
                if ( ! in_array( 'default_hotel_ids', $package_cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_package_deals ADD COLUMN default_hotel_ids LONGTEXT NULL AFTER hotel_ids" );
                }
                if ( ! in_array( 'hotel_nights', $package_cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_package_deals ADD COLUMN hotel_nights LONGTEXT NULL AFTER default_hotel_ids" );
                }
                if ( ! in_array( 'default_activities', $package_cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_package_deals ADD COLUMN default_activities LONGTEXT NULL AFTER activities" );
                }
                if ( ! in_array( 'free_activity_options', $package_cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_package_deals ADD COLUMN free_activity_options LONGTEXT NULL AFTER default_activities" );
                }
                if ( ! in_array( 'transit_day_fees', $package_cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_package_deals ADD COLUMN transit_day_fees LONGTEXT NULL AFTER free_activity_options" );
                }
                if ( ! in_array( 'transit_day_days', $package_cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_package_deals ADD COLUMN transit_day_days LONGTEXT NULL AFTER transit_day_fees" );
                }
                if ( ! in_array( 'children_rates', $package_cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_package_deals ADD COLUMN children_rates LONGTEXT NULL AFTER transit_day_days" );
                }

                // hr_tour_rates: add type / sort_order if missing.
                $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}hr_tour_rates" );
                if ( ! in_array( 'type', $cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_tour_rates ADD COLUMN type VARCHAR(100) NOT NULL DEFAULT 'Day Tour' AFTER place_id" );
                }
                if ( ! in_array( 'sort_order', $cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_tour_rates ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER tour_name" );
                }
                if ( ! in_array( 'optional_addons', $cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_tour_rates ADD COLUMN optional_addons LONGTEXT NULL AFTER sort_order" );
                }
                if ( ! in_array( 'transfer_routes', $cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_tour_rates ADD COLUMN transfer_routes LONGTEXT NULL AFTER optional_addons" );
                }
                if ( ! in_array( 'booking_fields', $cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_tour_rates ADD COLUMN booking_fields LONGTEXT NULL AFTER transfer_routes" );
                }
                if ( ! in_array( 'children_rates', $cols, true ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_tour_rates ADD COLUMN children_rates LONGTEXT NULL AFTER booking_fields" );
                }
                // Add pax 9–12 to hr_rates.
                $rate_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}hr_rates" );
                for ( $p = 9; $p <= self::MAX_PAX; $p++ ) {
                        if ( ! in_array( "pax_{$p}", $rate_cols, true ) ) {
                                $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_rates ADD COLUMN pax_{$p} DECIMAL(12,2) NULL" );
                        }
                }
                // Add pax 9–12 to hr_tour_rates.
                $tour_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}hr_tour_rates" );
                for ( $p = 9; $p <= self::MAX_PAX; $p++ ) {
                        if ( ! in_array( "pax_{$p}", $tour_cols, true ) ) {
                                $wpdb->query( "ALTER TABLE {$wpdb->prefix}hr_tour_rates ADD COLUMN pax_{$p} DECIMAL(12,2) NULL" );
                        }
                }
        }

        public static function seed_default_regions() {
                global $wpdb;
                $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}hr_regions" );
                if ( $count > 0 ) return;
                foreach ( self::DEFAULT_REGIONS as $name ) {
                        $wpdb->insert( "{$wpdb->prefix}hr_regions", array( 'name' => $name ) );
                }
        }

        /* ── Regions ─────────────────────────────────────────────────────── */

        public static function get_regions() {
                global $wpdb;
                return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}hr_regions ORDER BY FIELD(name,'Luzon','Visayas','Mindanao'), name" );
        }

        public static function get_region( $id ) {
                global $wpdb;
                return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}hr_regions WHERE id = %d", $id ) );
        }

        public static function save_region( $data ) {
                global $wpdb;
                if ( ! empty( $data['id'] ) ) {
                        $wpdb->update( "{$wpdb->prefix}hr_regions", array( 'name' => sanitize_text_field( $data['name'] ) ), array( 'id' => (int) $data['id'] ) );
                        return (int) $data['id'];
                }
                $wpdb->insert( "{$wpdb->prefix}hr_regions", array( 'name' => sanitize_text_field( $data['name'] ) ) );
                return (int) $wpdb->insert_id;
        }

        public static function delete_region( $id ) {
                global $wpdb;
                $places = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}hr_places WHERE region_id = %d", $id ) );
                foreach ( $places as $pid ) { self::delete_place( $pid ); }
                $wpdb->delete( "{$wpdb->prefix}hr_regions", array( 'id' => (int) $id ) );
        }

        /* ── Places ──────────────────────────────────────────────────────── */

        public static function get_places( $region_id = null ) {
                global $wpdb;
                if ( $region_id ) {
                        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}hr_places WHERE region_id = %d ORDER BY name", $region_id ) );
                }
                return $wpdb->get_results( "SELECT p.*, r.name AS region_name, r.id AS region_id FROM {$wpdb->prefix}hr_places p LEFT JOIN {$wpdb->prefix}hr_regions r ON r.id = p.region_id ORDER BY FIELD(r.name,'Luzon','Visayas','Mindanao'), r.name, p.name" );
        }

        public static function get_places_grouped() {
                $all    = self::get_places();
                $groups = array();
                foreach ( $all as $p ) {
                        $groups[ $p->region_name ][] = $p;
                }
                return $groups;
        }

        public static function get_place( $id ) {
                global $wpdb;
                return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}hr_places WHERE id = %d", $id ) );
        }

        public static function save_place( $data ) {
                global $wpdb;
                $row = array( 'region_id' => (int) $data['region_id'], 'name' => sanitize_text_field( $data['name'] ) );
                if ( ! empty( $data['id'] ) ) {
                        $wpdb->update( "{$wpdb->prefix}hr_places", $row, array( 'id' => (int) $data['id'] ) );
                        return (int) $data['id'];
                }
                $wpdb->insert( "{$wpdb->prefix}hr_places", $row );
                return (int) $wpdb->insert_id;
        }

        public static function delete_place( $id ) {
                global $wpdb;
                $hotels = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}hr_hotels WHERE place_id = %d", $id ) );
                foreach ( $hotels as $hid ) { self::delete_hotel( $hid ); }
                $wpdb->delete( "{$wpdb->prefix}hr_tour_rates", array( 'place_id' => (int) $id ) );
                $wpdb->delete( "{$wpdb->prefix}hr_places", array( 'id' => (int) $id ) );
        }

        /* ── Hotels ──────────────────────────────────────────────────────── */

        public static function get_hotels( $place_id = null ) {
                global $wpdb;
                if ( $place_id ) {
                        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}hr_hotels WHERE place_id = %d ORDER BY name", $place_id ) );
                }
                return $wpdb->get_results( "SELECT h.*, p.name AS place_name, r.name AS region_name FROM {$wpdb->prefix}hr_hotels h LEFT JOIN {$wpdb->prefix}hr_places p ON p.id = h.place_id LEFT JOIN {$wpdb->prefix}hr_regions r ON r.id = p.region_id ORDER BY r.name, p.name, h.name" );
        }

        public static function get_hotels_grouped() {
                $all    = self::get_hotels();
                $groups = array();
                foreach ( $all as $h ) {
                        $key = $h->region_name . ' / ' . $h->place_name;
                        $groups[ $key ][] = $h;
                }
                return $groups;
        }

        public static function get_hotel( $id ) {
                global $wpdb;
                return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}hr_hotels WHERE id = %d", $id ) );
        }

        public static function save_hotel( $data ) {
                global $wpdb;
                $row = array( 'place_id' => (int) $data['place_id'], 'name' => sanitize_text_field( $data['name'] ) );
                if ( ! empty( $data['id'] ) ) {
                        $wpdb->update( "{$wpdb->prefix}hr_hotels", $row, array( 'id' => (int) $data['id'] ) );
                        return (int) $data['id'];
                }
                $wpdb->insert( "{$wpdb->prefix}hr_hotels", $row );
                return (int) $wpdb->insert_id;
        }

        public static function delete_hotel( $id ) {
                global $wpdb;
                $wpdb->delete( "{$wpdb->prefix}hr_rates", array( 'hotel_id' => (int) $id ) );
                $wpdb->delete( "{$wpdb->prefix}hr_hotels", array( 'id' => (int) $id ) );
        }

        /* ── Hotel Rates ─────────────────────────────────────────────────── */

        public static function get_rates( $hotel_id, $nights ) {
                global $wpdb;
                return $wpdb->get_results( $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}hr_rates WHERE hotel_id = %d AND nights = %d ORDER BY id",
                        $hotel_id, $nights
                ) );
        }

        public static function get_all_rates( $hotel_id ) {
                global $wpdb;
                $rows   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}hr_rates WHERE hotel_id = %d ORDER BY nights, id", $hotel_id ) );
                $result = array();
                for ( $n = 1; $n <= self::MAX_NIGHTS; $n++ ) {
                        $result[ $n ] = array();
                }
                foreach ( $rows as $row ) {
                        $result[ (int) $row->nights ][] = $row;
                }
                return $result;
        }

        public static function get_rate_by_id( $rate_id ) {
                global $wpdb;

                return $wpdb->get_row( $wpdb->prepare(
                        "SELECT r.*, h.id AS hotel_id, h.name AS hotel_name, h.place_id, p.name AS place_name, p.region_id, rg.name AS region_name
                         FROM {$wpdb->prefix}hr_rates r
                         INNER JOIN {$wpdb->prefix}hr_hotels h ON h.id = r.hotel_id
                         INNER JOIN {$wpdb->prefix}hr_places p ON p.id = h.place_id
                         LEFT JOIN {$wpdb->prefix}hr_regions rg ON rg.id = p.region_id
                         WHERE r.id = %d
                         LIMIT 1",
                        $rate_id
                ) );
        }

        public static function get_rate_amount_for_pax( $rate, $pax ) {
                if ( ! is_object( $rate ) ) {
                        return null;
                }

                $pax = max( 1, min( self::MAX_PAX, (int) $pax ) );
                $col = 'pax_' . $pax;

                if ( ! isset( $rate->$col ) || $rate->$col === null || $rate->$col === '' ) {
                        return null;
                }

                return (float) $rate->$col;
        }

        public static function save_rate( $data ) {
                global $wpdb;
                $row = array(
                        'hotel_id'  => (int) $data['hotel_id'],
                        'nights'    => (int) $data['nights'],
                        'meal_plan' => sanitize_text_field( $data['meal_plan'] ?? 'X' ),
                );
                for ( $p = 1; $p <= self::MAX_PAX; $p++ ) {
                        $row[ "pax_{$p}" ] = self::maybe_null( $data[ "pax_{$p}" ] ?? null );
                }
                if ( ! empty( $data['id'] ) ) {
                        $wpdb->update( "{$wpdb->prefix}hr_rates", $row, array( 'id' => (int) $data['id'] ) );
                        return (int) $data['id'];
                }
                $wpdb->insert( "{$wpdb->prefix}hr_rates", $row );
                return (int) $wpdb->insert_id;
        }

        public static function delete_rate( $id ) {
                global $wpdb;
                $wpdb->delete( "{$wpdb->prefix}hr_rates", array( 'id' => (int) $id ) );
        }

        /* ── Tour Rates ──────────────────────────────────────────────────── */

        public static function get_tour_rates( $place_id, $type ) {
                global $wpdb;
                return $wpdb->get_results( $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}hr_tour_rates WHERE place_id = %d AND type = %s ORDER BY sort_order, id",
                        $place_id, $type
                ) );
        }

        public static function get_tour_rate_by_id( $id ) {
                global $wpdb;
                return $wpdb->get_row( $wpdb->prepare(
                        "SELECT t.*, p.name AS place_name, r.name AS region_name
                         FROM {$wpdb->prefix}hr_tour_rates t
                         LEFT JOIN {$wpdb->prefix}hr_places p ON p.id = t.place_id
                         LEFT JOIN {$wpdb->prefix}hr_regions r ON r.id = t.region_id
                         WHERE t.id = %d
                         LIMIT 1",
                        $id
                ) );
        }

        public static function get_bookable_tour_rates( $type = null ) {
                global $wpdb;

                if ( in_array( $type, array( 'Day Tour', 'Joiner', 'Airport Transfer' ), true ) ) {
                        return $wpdb->get_results( $wpdb->prepare(
                                "SELECT t.*, p.name AS place_name, r.name AS region_name
                                 FROM {$wpdb->prefix}hr_tour_rates t
                                 LEFT JOIN {$wpdb->prefix}hr_places p ON p.id = t.place_id
                                 LEFT JOIN {$wpdb->prefix}hr_regions r ON r.id = t.region_id
                                 WHERE t.type = %s
                                 ORDER BY FIELD(r.name,'Luzon','Visayas','Mindanao'), r.name, p.name, t.tour_name",
                                $type
                        ) );
                }

                return $wpdb->get_results(
                        "SELECT t.*, p.name AS place_name, r.name AS region_name
                         FROM {$wpdb->prefix}hr_tour_rates t
                         LEFT JOIN {$wpdb->prefix}hr_places p ON p.id = t.place_id
                         LEFT JOIN {$wpdb->prefix}hr_regions r ON r.id = t.region_id
                         WHERE t.type IN ('Day Tour', 'Joiner', 'Airport Transfer')
                         ORDER BY FIELD(t.type,'Day Tour','Joiner','Airport Transfer'), FIELD(r.name,'Luzon','Visayas','Mindanao'), r.name, p.name, t.tour_name"
                );
        }

        /**
         * Returns nested structure: [ region_id => ['name'=>, 'places'=> [ place_id => ['name'=>, 'tours'=>[...]] ] ] ]
         */
        public static function get_tours_nested( $type ) {
                global $wpdb;
                $rows = $wpdb->get_results( $wpdb->prepare(
                        "SELECT t.id, t.tour_name, t.place_id, t.region_id, t.type,
                                r.name AS region_name, p.name AS place_name
                         FROM {$wpdb->prefix}hr_tour_rates t
                         LEFT JOIN {$wpdb->prefix}hr_regions r ON r.id = t.region_id
                         LEFT JOIN {$wpdb->prefix}hr_places  p ON p.id = t.place_id
                         WHERE t.type = %s
                         ORDER BY FIELD(r.name,'Luzon','Visayas','Mindanao'), r.name, p.name, t.tour_name",
                        $type
                ) );

                $nested = array();
                // Also ensure all regions appear (even if empty).
                $regions = self::get_regions();
                foreach ( $regions as $r ) {
                        $nested[ $r->id ] = array( 'name' => $r->name, 'places' => array() );
                }
                foreach ( $rows as $row ) {
                        $rid = $row->region_id;
                        $pid = $row->place_id;
                        if ( ! isset( $nested[ $rid ] ) ) {
                                $nested[ $rid ] = array( 'name' => $row->region_name, 'places' => array() );
                        }
                        if ( ! isset( $nested[ $rid ]['places'][ $pid ] ) ) {
                                $nested[ $rid ]['places'][ $pid ] = array( 'name' => $row->place_name, 'tours' => array() );
                        }
                        $nested[ $rid ]['places'][ $pid ]['tours'][] = $row;
                }
                return $nested;
        }

        public static function get_tours_by_type_grouped( $type ) {
                global $wpdb;
                $rows = $wpdb->get_results( $wpdb->prepare(
                        "SELECT t.id, t.tour_name, t.place_id, t.region_id,
                                r.name AS region_name, p.name AS place_name
                         FROM {$wpdb->prefix}hr_tour_rates t
                         LEFT JOIN {$wpdb->prefix}hr_regions r ON r.id = t.region_id
                         LEFT JOIN {$wpdb->prefix}hr_places  p ON p.id = t.place_id
                         WHERE t.type = %s
                         ORDER BY r.name, p.name, t.tour_name",
                        $type
                ) );
                $groups = array();
                foreach ( $rows as $row ) {
                        $groups[ $row->region_name ][] = $row;
                }
                return $groups;
        }

        public static function normalize_tour_optional_addons( $raw_addons, $type = '' ) {
                if ( is_string( $raw_addons ) ) {
                        $decoded = json_decode( $raw_addons, true );
                        $raw_addons = is_array( $decoded ) ? $decoded : array();
                }

                if ( ! is_array( $raw_addons ) ) {
                        $raw_addons = array();
                }

                if ( isset( $raw_addons['groups'] ) && is_array( $raw_addons['groups'] ) ) {
                        $raw_groups = $raw_addons['groups'];
                } elseif ( self::is_tour_addon_group_list( $raw_addons ) ) {
                        $raw_groups = $raw_addons;
                } else {
                        $raw_groups = self::convert_legacy_tour_optional_addons( $raw_addons );
                }

                $addons = array();
                $used_keys = array();

                foreach ( $raw_groups as $group_index => $group ) {
                        if ( ! is_array( $group ) ) {
                                continue;
                        }

                        $group_label = sanitize_text_field( $group['label'] ?? '' );
                        $options     = isset( $group['options'] ) && is_array( $group['options'] ) ? $group['options'] : array();

                        if ( '' === $group_label || empty( $options ) ) {
                                continue;
                        }

                        $group_key = sanitize_title( $group['key'] ?? $group_label );
                        if ( '' === $group_key ) {
                                $group_key = 'addon_group';
                        }

                        $base_key = $group_key;
                        $suffix   = 2;
                        while ( isset( $used_keys[ $group_key ] ) ) {
                                $group_key = $base_key . '_' . $suffix;
                                $suffix++;
                        }
                        $used_keys[ $group_key ] = true;

                        $addon_options = array();
                        foreach ( $options as $option ) {
                                if ( ! is_array( $option ) ) {
                                        continue;
                                }

                                $label = sanitize_text_field( $option['label'] ?? '' );
                                $rate  = self::maybe_null( $option['rate'] ?? null );

                                if ( '' === $label || null === $rate ) {
                                        continue;
                                }

                                $addon_options[] = array(
                                        'label' => $label,
                                        'rate'  => max( 0, (float) $rate ),
                                );
                        }

                        if ( empty( $addon_options ) ) {
                                continue;
                        }

                        $addons[] = array(
                                'key'     => $group_key,
                                'label'   => $group_label,
                                'options' => $addon_options,
                        );
                }

                return $addons;
        }

        public static function normalize_tour_booking_fields( $raw_fields ) {
                if ( is_string( $raw_fields ) ) {
                        $decoded = json_decode( $raw_fields, true );
                        $raw_fields = is_array( $decoded ) ? $decoded : array();
                }

                if ( ! is_array( $raw_fields ) ) {
                        $raw_fields = array();
                }

                $defaults = array(
                        'hotel_flight_no' => array(
                                'enabled' => true,
                                'label'   => __( 'Hotel / Flight No.', 'hotel-rates' ),
                        ),
                        'pickup_time'     => array(
                                'enabled' => true,
                                'label'   => __( 'Pick-up Time', 'hotel-rates' ),
                        ),
                );

                $fields = array();

                foreach ( $defaults as $field_key => $default ) {
                        $raw_field = isset( $raw_fields[ $field_key ] ) && is_array( $raw_fields[ $field_key ] ) ? $raw_fields[ $field_key ] : array();
                        $label     = sanitize_text_field( $raw_field['label'] ?? $default['label'] );

                        $fields[ $field_key ] = array(
                                'enabled' => array_key_exists( 'enabled', $raw_field ) ? (bool) (int) $raw_field['enabled'] : (bool) $default['enabled'],
                                'label'   => '' !== $label ? $label : $default['label'],
                        );
                }

                return $fields;
        }

        public static function normalize_tour_children_rates( $raw_rates ) {
                if ( is_string( $raw_rates ) ) {
                        $decoded = json_decode( $raw_rates, true );
                        $raw_rates = is_array( $decoded ) ? $decoded : array();
                }

                if ( ! is_array( $raw_rates ) ) {
                        $raw_rates = array();
                }

                $rates = array(
                        'enabled'          => ! empty( $raw_rates['enabled'] ),
                        'label'            => __( 'Children', 'hotel-rates' ),
                        'discount_percent' => 0.0,
                        'prices'           => array(),
                );

                $label = sanitize_text_field( $raw_rates['label'] ?? ( $raw_rates['children_label'] ?? ( $raw_rates['childrenLabel'] ?? '' ) ) );
                if ( '' !== $label ) {
                        $rates['label'] = $label;
                }

                $discount_percent = self::maybe_null( $raw_rates['discount_percent'] ?? ( $raw_rates['discountPercent'] ?? null ) );
                if ( null !== $discount_percent ) {
                        $rates['discount_percent'] = min( 100, max( 0, (float) $discount_percent ) );
                }

                $raw_prices = array();

                if ( ! empty( $raw_rates['prices'] ) && is_array( $raw_rates['prices'] ) ) {
                        $raw_prices = $raw_rates['prices'];
                }

                for ( $p = 1; $p <= self::MAX_PAX; $p++ ) {
                        $price = $raw_prices[ (string) $p ] ?? ( $raw_prices[ $p ] ?? ( $raw_rates[ "pax_{$p}" ] ?? null ) );
                        $price = self::maybe_null( $price );

                        $rates['prices'][ (string) $p ] = null === $price ? null : max( 0, (float) $price );
                }

                return $rates;
        }

        private static function is_tour_addon_group_list( $raw_addons ) {
                foreach ( $raw_addons as $group ) {
                        return is_array( $group ) && ( array_key_exists( 'label', $group ) || array_key_exists( 'options', $group ) );
                }

                return false;
        }

        private static function convert_legacy_tour_optional_addons( $raw_addons ) {
                $legacy_labels = self::get_legacy_tour_optional_addon_group_labels();
                $groups        = array();

                foreach ( $raw_addons as $group_key => $options ) {
                        if ( ! is_array( $options ) ) {
                                continue;
                        }

                        $groups[] = array(
                                'key'     => sanitize_key( $group_key ),
                                'label'   => $legacy_labels[ $group_key ] ?? ucwords( str_replace( array( '-', '_' ), ' ', (string) $group_key ) ),
                                'options' => $options,
                        );
                }

                return $groups;
        }

        public static function normalize_airport_transfer_routes( $raw_routes ) {
                if ( is_string( $raw_routes ) ) {
                        $decoded = json_decode( $raw_routes, true );
                        $raw_routes = is_array( $decoded ) ? $decoded : array();
                }

                if ( ! is_array( $raw_routes ) ) {
                        return array();
                }

                $routes = array();

                foreach ( $raw_routes as $route ) {
                        $rate = null;
                        $has_rate_key = false;

                        if ( ! is_array( $route ) ) {
                                continue;
                        }

                        $label = sanitize_text_field( $route['label'] ?? '' );
                        if ( '' === $label ) {
                                continue;
                        }

                        if ( array_key_exists( 'rate', $route ) ) {
                                $has_rate_key = true;
                                $rate = self::maybe_null( $route['rate'] );
                        }

                        if ( null === $rate ) {
                                for ( $p = 1; $p <= self::MAX_PAX; $p++ ) {
                                        $legacy_price = self::maybe_null( $route[ "pax_{$p}" ] ?? ( $route['prices'][ (string) $p ] ?? null ) );

                                        if ( null !== $legacy_price ) {
                                                $rate = $legacy_price;
                                                break;
                                        }
                                }
                        }

                        if ( null === $rate && $has_rate_key ) {
                                $rate = 0;
                        }

                        if ( null === $rate ) {
                                continue;
                        }

                        $routes[] = array(
                                'label' => $label,
                                'rate'  => max( 0, (float) $rate ),
                        );
                }

                return $routes;
        }

        /**
         * Save a single tour rate row (add or edit). Used by the Tour Rates modal.
         */
        public static function save_single_tour_rate( $data ) {
                global $wpdb;
                $place_id = (int) $data['place_id'];
                $place    = self::get_place( $place_id );
                $type     = sanitize_text_field( $data['type'] ?? 'Day Tour' );
                $transfer_routes = $data['transfer_routes'] ?? array();

                if ( ! empty( $data['transfer_routes_json'] ) ) {
                        $decoded_transfer_routes = json_decode( (string) $data['transfer_routes_json'], true );

                        if ( is_array( $decoded_transfer_routes ) ) {
                                $transfer_routes = $decoded_transfer_routes;
                        }
                }

                $row      = array(
                        'region_id' => $place ? (int) $place->region_id : (int) ( $data['region_id'] ?? 0 ),
                        'place_id'  => $place_id,
                        'type'      => $type,
                        'tour_name' => sanitize_text_field( $data['tour_name'] ?? '' ),
                        'sort_order' => (int) ( $data['sort_order'] ?? 0 ),
                        'optional_addons' => wp_json_encode( self::normalize_tour_optional_addons( $data['optional_addons'] ?? array(), $type ) ),
                        'transfer_routes' => wp_json_encode( self::normalize_airport_transfer_routes( $transfer_routes ) ),
                        'booking_fields' => wp_json_encode( self::normalize_tour_booking_fields( $data['tour_booking_fields'] ?? array() ) ),
                        'children_rates' => wp_json_encode( self::normalize_tour_children_rates( $data['tour_children_rates'] ?? array() ) ),
                );
                for ( $p = 1; $p <= self::MAX_PAX; $p++ ) {
                        $row[ "pax_{$p}" ] = self::maybe_null( $data[ "pax_{$p}" ] ?? null );
                }
                if ( ! empty( $data['id'] ) ) {
                        $wpdb->update( "{$wpdb->prefix}hr_tour_rates", $row, array( 'id' => (int) $data['id'] ) );
                        return (int) $data['id'];
                }
                $wpdb->insert( "{$wpdb->prefix}hr_tour_rates", $row );
                return (int) $wpdb->insert_id;
        }

        public static function register_tour( $data ) {
                global $wpdb;
                $place_id  = (int) $data['place_id'];
                $type      = sanitize_text_field( $data['type'] );
                $tour_name = sanitize_text_field( $data['tour_name'] );
                $place     = self::get_place( $place_id );
                if ( ! $place ) return false;
                $wpdb->insert( "{$wpdb->prefix}hr_tour_rates", array(
                        'region_id'  => (int) $place->region_id,
                        'place_id'   => $place_id,
                        'type'       => $type,
                        'tour_name'  => $tour_name,
                        'sort_order' => 0,
                ) );
                return (int) $wpdb->insert_id;
        }

        public static function update_tour_name( $id, $name ) {
                global $wpdb;
                $wpdb->update(
                        "{$wpdb->prefix}hr_tour_rates",
                        array( 'tour_name' => sanitize_text_field( $name ) ),
                        array( 'id' => (int) $id )
                );
        }

        public static function delete_tour_registration( $id ) {
                global $wpdb;
                $wpdb->delete( "{$wpdb->prefix}hr_tour_rates", array( 'id' => (int) $id ) );
        }

        public static function save_tour_rates_batch( $region_id, $place_id, $type, $rows ) {
                global $wpdb;
                $region_id = (int) $region_id;
                $place_id  = (int) $place_id;
                $type      = sanitize_text_field( $type );
                $kept_ids  = array();

                foreach ( $rows as $idx => $row ) {
                        if ( ! empty( $row['_delete'] ) ) {
                                if ( ! empty( $row['id'] ) ) {
                                        $wpdb->delete( "{$wpdb->prefix}hr_tour_rates", array( 'id' => (int) $row['id'] ) );
                                }
                                continue;
                        }
                        $tour_name = sanitize_text_field( $row['tour_name'] ?? '' );
                        if ( $tour_name === '' ) continue;

                        $db_row = array(
                                'region_id'  => $region_id,
                                'place_id'   => $place_id,
                                'type'       => $type,
                                'tour_name'  => $tour_name,
                                'sort_order' => (int) $idx,
                        );
                        for ( $p = 1; $p <= self::MAX_PAX; $p++ ) {
                                $db_row[ "pax_{$p}" ] = self::maybe_null( $row[ "pax_{$p}" ] ?? null );
                        }

                        if ( ! empty( $row['id'] ) ) {
                                $id = (int) $row['id'];
                                $wpdb->update( "{$wpdb->prefix}hr_tour_rates", $db_row, array( 'id' => $id ) );
                                $kept_ids[] = $id;
                        } else {
                                $wpdb->insert( "{$wpdb->prefix}hr_tour_rates", $db_row );
                                $kept_ids[] = (int) $wpdb->insert_id;
                        }
                }
                return $kept_ids;
        }

        /* ── Helpers ─────────────────────────────────────────────────────── */

        public static function get_package_deals() {
                global $wpdb;
                return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}hr_package_deals ORDER BY created_at DESC, id DESC" );
        }

        public static function get_package_deals_by_place( $place_id ) {
                $place_id = (int) $place_id;
                if ( ! $place_id ) {
                        return array();
                }

                $rows = self::get_package_deals();
                $kept = array();

                foreach ( $rows as $row ) {
                        $place_ids = json_decode( $row->place_ids ?? '[]', true );
                        $place_ids = is_array( $place_ids ) ? array_map( 'intval', $place_ids ) : array();

                        if ( in_array( $place_id, $place_ids, true ) ) {
                                $kept[] = $row;
                        }
                }

                return $kept;
        }

        public static function get_package_deal( $id ) {
                global $wpdb;
                return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}hr_package_deals WHERE id = %d", $id ) );
        }

        public static function save_package_deal( $data ) {
                global $wpdb;

                $number_of_days = max( 1, (int) ( $data['number_of_days'] ?? 1 ) );
                $place_ids = isset( $data['place_ids'] ) && is_array( $data['place_ids'] ) ? array_map( 'intval', $data['place_ids'] ) : array();
                $place_ids = array_values( array_filter( $place_ids ) );

                $hotel_ids = array();
                if ( isset( $data['package_hotels'] ) && is_array( $data['package_hotels'] ) ) {
                        foreach ( $data['package_hotels'] as $place_id => $ids ) {
                                if ( ! is_array( $ids ) ) {
                                        continue;
                                }

                                $hotel_ids[ (string) (int) $place_id ] = array_values( array_filter( array_map( 'intval', $ids ) ) );
                        }
                }

                $hotel_nights = array();
                if ( isset( $data['package_hotel_nights'] ) && is_array( $data['package_hotel_nights'] ) ) {
                        foreach ( $data['package_hotel_nights'] as $place_id => $nights ) {
                                $place_key = (string) (int) $place_id;

                                if ( $nights === null || $nights === '' ) {
                                        continue;
                                }

                                $nights    = max( 1, min( self::MAX_NIGHTS, (int) $nights ) );

                                if ( ! empty( $hotel_ids[ $place_key ] ) ) {
                                        $hotel_nights[ $place_key ] = $nights;
                                }
                        }
                }

                $default_hotel_ids = self::get_cheapest_package_default_hotel_ids( $hotel_ids, $hotel_nights, $number_of_days );

                $activities = array();
                $transit_day_days = array();
                if ( isset( $data['package_day_activities'] ) && is_array( $data['package_day_activities'] ) ) {
                        foreach ( $data['package_day_activities'] as $day => $ids ) {
                                if ( ! is_array( $ids ) ) {
                                        continue;
                                }

                                $day_key = (string) max( 1, (int) $day );
                                $has_transit_day = in_array( 'hr_transit_day', array_map( 'strval', $ids ), true );

                                if ( $has_transit_day ) {
                                        $transit_day_days[ $day_key ] = true;
                                        $activities[ $day_key ] = array();
                                        continue;
                                }

                                $activities[ $day_key ] = array_values( array_filter( array_map( 'intval', $ids ) ) );
                        }
                }

                $default_activities = array();
                if ( isset( $data['package_default_day_activities'] ) && is_array( $data['package_default_day_activities'] ) ) {
                        foreach ( $data['package_default_day_activities'] as $day => $tour_rate_id ) {
                                $day_key      = (string) max( 1, (int) $day );
                                $tour_rate_id = (int) $tour_rate_id;

                                if ( $tour_rate_id && ! empty( $activities[ $day_key ] ) && in_array( $tour_rate_id, $activities[ $day_key ], true ) ) {
                                        $default_activities[ $day_key ] = $tour_rate_id;
                                }
                        }
                }

                $allowed_free_activity_options = array(
                        'hr_airport_seaport_pickup',
                        'hr_airport_seaport_dropoff',
                        'hr_free_time',
                );
                $posted_free_activity_options = isset( $data['package_free_activity_options'] ) && is_array( $data['package_free_activity_options'] ) ? $data['package_free_activity_options'] : array();
                $free_activity_options = array();

                for ( $day = 1; $day <= $number_of_days; $day++ ) {
                        $day_key = (string) $day;
                        $selected_free_options = isset( $posted_free_activity_options[ $day_key ] ) && is_array( $posted_free_activity_options[ $day_key ] ) ? $posted_free_activity_options[ $day_key ] : array();
                        $normalized_free_options = array();

                        foreach ( $selected_free_options as $free_option ) {
                                $free_option = sanitize_key( $free_option );

                                if ( in_array( $free_option, $allowed_free_activity_options, true ) && ! in_array( $free_option, $normalized_free_options, true ) ) {
                                        $normalized_free_options[] = $free_option;
                                }
                        }

                        $free_activity_options[ $day_key ] = $normalized_free_options;
                }

                $transit_day_fees = self::normalize_package_transit_day_fees( $data['package_transit_day_fees'] ?? array() );

                $row = array(
                        'package_name'          => sanitize_text_field( $data['package_name'] ?? '' ),
                        'airport_transfer_fee'  => self::maybe_null( $data['airport_transfer_fee'] ?? null ),
                        'seaport_transfer_fee'  => self::maybe_null( $data['seaport_transfer_fee'] ?? null ),
                        'number_of_days'        => $number_of_days,
                        'place_ids'             => wp_json_encode( $place_ids ),
                        'hotel_ids'             => wp_json_encode( $hotel_ids ),
                        'default_hotel_ids'     => wp_json_encode( $default_hotel_ids ),
                        'hotel_nights'          => wp_json_encode( $hotel_nights ),
                        'activities'            => wp_json_encode( $activities ),
                        'default_activities'    => wp_json_encode( $default_activities ),
                        'free_activity_options' => wp_json_encode( $free_activity_options ),
                        'transit_day_fees'      => wp_json_encode( $transit_day_fees ),
                        'transit_day_days'      => wp_json_encode( $transit_day_days ),
                );

                if ( ! empty( $data['id'] ) ) {
                        $id = (int) $data['id'];
                        $wpdb->update( "{$wpdb->prefix}hr_package_deals", $row, array( 'id' => $id ) );
                        return $id;
                }

                $wpdb->insert( "{$wpdb->prefix}hr_package_deals", $row );
                return (int) $wpdb->insert_id;
        }

        private static function normalize_package_transit_day_fees( $raw_fees ) {
                $raw_fees = is_array( $raw_fees ) ? $raw_fees : array();
                $fees = array();

                for ( $pax = 1; $pax <= self::MAX_PAX; $pax++ ) {
                        $value = $raw_fees[ $pax ] ?? ( $raw_fees[ (string) $pax ] ?? '' );
                        $fees[ (string) $pax ] = self::maybe_null( $value );
                }

                return $fees;
        }

        private static function get_cheapest_package_default_hotel_ids( $hotel_ids, $hotel_nights, $number_of_days ) {
                $hotel_ids = is_array( $hotel_ids ) ? $hotel_ids : array();
                $hotel_nights = is_array( $hotel_nights ) ? $hotel_nights : array();
                $default_nights = max( 1, min( self::MAX_NIGHTS, (int) $number_of_days - 1 ) );
                $defaults = array();

                foreach ( $hotel_ids as $place_key => $selected_hotel_ids ) {
                        $place_key = (string) (int) $place_key;
                        $selected_hotel_ids = is_array( $selected_hotel_ids ) ? array_values( array_filter( array_map( 'intval', $selected_hotel_ids ) ) ) : array();

                        if ( empty( $selected_hotel_ids ) ) {
                                continue;
                        }

                        $nights = isset( $hotel_nights[ $place_key ] ) ? (int) $hotel_nights[ $place_key ] : $default_nights;
                        $nights = max( 1, min( self::MAX_NIGHTS, $nights ) );
                        $cheapest_hotel_id = 0;
                        $cheapest_rate = null;

                        foreach ( $selected_hotel_ids as $hotel_id ) {
                                $rate = self::get_preferred_package_hotel_rate_for_default( self::get_rates( $hotel_id, $nights ) );

                                if ( ! $rate || ! isset( $rate->pax_1 ) || null === $rate->pax_1 || '' === $rate->pax_1 ) {
                                        continue;
                                }

                                $pax_one_rate = (float) $rate->pax_1;

                                if ( null === $cheapest_rate || $pax_one_rate < $cheapest_rate ) {
                                        $cheapest_rate = $pax_one_rate;
                                        $cheapest_hotel_id = $hotel_id;
                                }
                        }

                        if ( $cheapest_hotel_id ) {
                                $defaults[ $place_key ] = $cheapest_hotel_id;
                        }
                }

                return $defaults;
        }

        private static function get_preferred_package_hotel_rate_for_default( $rates ) {
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

        public static function delete_package_deal( $id ) {
                global $wpdb;
                $wpdb->delete( "{$wpdb->prefix}hr_package_deals", array( 'id' => (int) $id ) );
        }

        public static function get_motorbike_rentals() {
                global $wpdb;
                return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}hr_motorbike_rentals ORDER BY created_at DESC, id DESC" );
        }

        public static function get_motorbike_rental( $id ) {
                global $wpdb;
                return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}hr_motorbike_rentals WHERE id = %d", $id ) );
        }

        public static function save_motorbike_rental( $data ) {
                global $wpdb;

                $models = self::normalize_motorbike_models( $data['models'] ?? array() );
                $start_times = self::normalize_motorbike_text_lines( $data['start_times'] ?? '' );
                $day_options = self::normalize_motorbike_day_options( $data['day_options'] ?? '' );

                $row = array(
                        'rental_name'  => sanitize_text_field( $data['rental_name'] ?? '' ),
                        'models'       => wp_json_encode( $models ),
                        'start_times'  => wp_json_encode( $start_times ),
                        'day_options'  => wp_json_encode( $day_options ),
                        'delivery_fee' => self::maybe_null( $data['delivery_fee'] ?? null ),
                );

                if ( '' === $row['rental_name'] || empty( $models ) ) {
                        return false;
                }

                if ( ! empty( $data['id'] ) ) {
                        $id = (int) $data['id'];
                        $wpdb->update( "{$wpdb->prefix}hr_motorbike_rentals", $row, array( 'id' => $id ) );
                        return $id;
                }

                $wpdb->insert( "{$wpdb->prefix}hr_motorbike_rentals", $row );
                return (int) $wpdb->insert_id;
        }

        public static function delete_motorbike_rental( $id ) {
                global $wpdb;
                $wpdb->delete( "{$wpdb->prefix}hr_motorbike_rentals", array( 'id' => (int) $id ) );
        }

        public static function get_boat_rentals() {
                global $wpdb;
                return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}hr_boat_rentals ORDER BY created_at DESC, id DESC" );
        }

        public static function get_boat_rental( $id ) {
                global $wpdb;
                return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}hr_boat_rentals WHERE id = %d", $id ) );
        }

        public static function save_boat_rental( $data ) {
                global $wpdb;

                $rates = self::normalize_boat_rates( $data['rates'] ?? array() );
                $boarding_times = self::normalize_motorbike_text_lines( $data['boarding_times'] ?? '' );
                $credit_card_fee_percent = self::maybe_null( $data['credit_card_fee_percent'] ?? '5' );
                $upgrade_boat_fee_label = sanitize_text_field( $data['upgrade_boat_fee_label'] ?? '' );
                $upgrade_boat_fee_amount = self::maybe_null( $data['upgrade_boat_fee_amount'] ?? null );

                $row = array(
                        'rental_name'              => sanitize_text_field( $data['rental_name'] ?? '' ),
                        'rates'                    => wp_json_encode( $rates ),
                        'boarding_times'           => wp_json_encode( $boarding_times ),
                        'credit_card_fee_percent'  => null === $credit_card_fee_percent ? 5.0 : max( 0.0, (float) $credit_card_fee_percent ),
                        'upgrade_boat_fee_label'   => '' !== $upgrade_boat_fee_label ? $upgrade_boat_fee_label : __( 'Upgrade to Bigger Boat Fee', 'hotel-rates' ),
                        'upgrade_boat_fee_amount'  => null === $upgrade_boat_fee_amount ? null : max( 0.0, (float) $upgrade_boat_fee_amount ),
                );

                if ( '' === $row['rental_name'] || empty( $rates ) ) {
                        return false;
                }

                if ( ! empty( $data['id'] ) ) {
                        $id = (int) $data['id'];
                        $wpdb->update( "{$wpdb->prefix}hr_boat_rentals", $row, array( 'id' => $id ) );
                        return $id;
                }

                $wpdb->insert( "{$wpdb->prefix}hr_boat_rentals", $row );
                return (int) $wpdb->insert_id;
        }

        public static function delete_boat_rental( $id ) {
                global $wpdb;
                $wpdb->delete( "{$wpdb->prefix}hr_boat_rentals", array( 'id' => (int) $id ) );
        }

        public static function get_self_drive_rentals() {
                global $wpdb;
                return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}hr_self_drive_rentals ORDER BY created_at DESC, id DESC" );
        }

        public static function get_self_drive_rental( $id ) {
                global $wpdb;
                return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}hr_self_drive_rentals WHERE id = %d", $id ) );
        }

        public static function save_self_drive_rental( $data ) {
                global $wpdb;

                $vehicles = self::normalize_self_drive_vehicles( $data['vehicles'] ?? array() );
                $start_times = self::normalize_motorbike_text_lines( $data['start_times'] ?? '' );
                $day_options = self::normalize_motorbike_day_options( $data['day_options'] ?? '' );
                $delivery_fee = self::maybe_null( $data['delivery_fee'] ?? '500.00' );
                $credit_card_fee_percent = self::maybe_null( $data['credit_card_fee_percent'] ?? '5' );

                $row = array(
                        'rental_name'              => sanitize_text_field( $data['rental_name'] ?? '' ),
                        'vehicles'                 => wp_json_encode( $vehicles ),
                        'start_times'              => wp_json_encode( $start_times ),
                        'day_options'              => wp_json_encode( $day_options ),
                        'delivery_fee'             => null === $delivery_fee ? 500.00 : $delivery_fee,
                        'credit_card_fee_percent'  => null === $credit_card_fee_percent ? 5.0 : max( 0.0, (float) $credit_card_fee_percent ),
                );

                if ( '' === $row['rental_name'] || empty( $vehicles ) ) {
                        return false;
                }

                if ( ! empty( $data['id'] ) ) {
                        $id = (int) $data['id'];
                        $wpdb->update( "{$wpdb->prefix}hr_self_drive_rentals", $row, array( 'id' => $id ) );
                        return $id;
                }

                $wpdb->insert( "{$wpdb->prefix}hr_self_drive_rentals", $row );
                return (int) $wpdb->insert_id;
        }

        public static function delete_self_drive_rental( $id ) {
                global $wpdb;
                $wpdb->delete( "{$wpdb->prefix}hr_self_drive_rentals", array( 'id' => (int) $id ) );
        }

        public static function get_port_transfers() {
                global $wpdb;
                return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}hr_port_transfers ORDER BY created_at DESC, id DESC" );
        }

        public static function get_port_transfer( $id ) {
                global $wpdb;
                return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}hr_port_transfers WHERE id = %d", $id ) );
        }

        public static function save_port_transfer( $data ) {
                global $wpdb;

                $config = self::normalize_port_transfer_config( $data );
                $transfer_times = self::normalize_motorbike_text_lines( $data['transfer_times'] ?? '' );

                $row = array(
                        'transfer_name'  => sanitize_text_field( $data['transfer_name'] ?? '' ),
                        'routes'         => wp_json_encode( $config ),
                        'transfer_times' => wp_json_encode( $transfer_times ),
                );

                if ( '' === $row['transfer_name'] || empty( $config['fromOptions'] ) || empty( $config['toOptions'] ) || empty( $config['rates'] ) ) {
                        return false;
                }

                if ( ! empty( $data['id'] ) ) {
                        $id = (int) $data['id'];
                        $wpdb->update( "{$wpdb->prefix}hr_port_transfers", $row, array( 'id' => $id ) );
                        return $id;
                }

                $wpdb->insert( "{$wpdb->prefix}hr_port_transfers", $row );
                return (int) $wpdb->insert_id;
        }

        public static function delete_port_transfer( $id ) {
                global $wpdb;
                $wpdb->delete( "{$wpdb->prefix}hr_port_transfers", array( 'id' => (int) $id ) );
        }

        public static function get_destination_tours() {
                global $wpdb;
                return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}hr_destination_tours ORDER BY created_at DESC, id DESC" );
        }

        public static function get_destination_tour( $id ) {
                global $wpdb;
                return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}hr_destination_tours WHERE id = %d", $id ) );
        }

        public static function save_destination_tour( $data ) {
                global $wpdb;

                $destinations = self::normalize_destination_tour_destinations( $data['destinations'] ?? array() );
                $pickup_addons = self::normalize_port_transfer_addons( $data['pickup_addons'] ?? array() );
                $pickup_times = self::normalize_motorbike_text_lines( $data['pickup_times'] ?? '' );
                $credit_card_fee_percent = self::maybe_null( $data['credit_card_fee_percent'] ?? '5' );

                $row = array(
                        'tour_name'               => sanitize_text_field( $data['tour_name'] ?? '' ),
                        'destinations'            => wp_json_encode( $destinations ),
                        'pickup_addons'           => wp_json_encode( $pickup_addons ),
                        'pickup_times'            => wp_json_encode( $pickup_times ),
                        'credit_card_fee_percent' => null === $credit_card_fee_percent ? 5.0 : max( 0.0, (float) $credit_card_fee_percent ),
                );

                if ( '' === $row['tour_name'] || empty( $destinations ) ) {
                        return false;
                }

                if ( ! empty( $data['id'] ) ) {
                        $id = (int) $data['id'];
                        $wpdb->update( "{$wpdb->prefix}hr_destination_tours", $row, array( 'id' => $id ) );
                        return $id;
                }

                $wpdb->insert( "{$wpdb->prefix}hr_destination_tours", $row );
                return (int) $wpdb->insert_id;
        }

        public static function delete_destination_tour( $id ) {
                global $wpdb;
                $wpdb->delete( "{$wpdb->prefix}hr_destination_tours", array( 'id' => (int) $id ) );
        }

        public static function get_pickup_dropoff_services() {
                global $wpdb;
                return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}hr_pickup_dropoff_services ORDER BY created_at DESC, id DESC" );
        }

        public static function get_pickup_dropoff_service( $id ) {
                global $wpdb;
                return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}hr_pickup_dropoff_services WHERE id = %d", $id ) );
        }

        public static function save_pickup_dropoff_service( $data ) {
                global $wpdb;

                $locations = self::normalize_pickup_dropoff_locations( $data['locations'] ?? array() );
                $route_rates = self::normalize_pickup_dropoff_route_rates( $data['route_rates'] ?? array(), $locations );
                $pickup_times = self::normalize_motorbike_text_lines( $data['pickup_times'] ?? '' );
                $addons = self::normalize_port_transfer_addons( $data['addons'] ?? array() );

                $row = array(
                        'service_name' => sanitize_text_field( $data['service_name'] ?? '' ),
                        'locations'    => wp_json_encode( $locations ),
                        'route_rates'  => wp_json_encode( $route_rates ),
                        'pickup_times' => wp_json_encode( $pickup_times ),
                        'addons'       => wp_json_encode( $addons ),
                );

                if ( '' === $row['service_name'] || count( $locations ) < 2 || empty( $route_rates ) ) {
                        return false;
                }

                if ( ! empty( $data['id'] ) ) {
                        $id = (int) $data['id'];
                        $wpdb->update( "{$wpdb->prefix}hr_pickup_dropoff_services", $row, array( 'id' => $id ) );
                        return $id;
                }

                $wpdb->insert( "{$wpdb->prefix}hr_pickup_dropoff_services", $row );
                return (int) $wpdb->insert_id;
        }

        public static function delete_pickup_dropoff_service( $id ) {
                global $wpdb;
                $wpdb->delete( "{$wpdb->prefix}hr_pickup_dropoff_services", array( 'id' => (int) $id ) );
        }

        public static function normalize_self_drive_vehicles( $raw_vehicles ) {
                $vehicles = array();

                if ( ! is_array( $raw_vehicles ) ) {
                        return $vehicles;
                }

                foreach ( $raw_vehicles as $vehicle ) {
                        if ( ! is_array( $vehicle ) ) {
                                continue;
                        }

                        $name = sanitize_text_field( $vehicle['name'] ?? '' );
                        $rate = self::maybe_null( $vehicle['rate'] ?? null );
                        $stock = max( 0, (int) ( $vehicle['stock'] ?? 0 ) );

                        if ( '' === $name || null === $rate || $stock < 1 ) {
                                continue;
                        }

                        $vehicles[] = array(
                                'name'  => $name,
                                'rate'  => max( 0, (float) $rate ),
                                'stock' => $stock,
                        );
                }

                return $vehicles;
        }

        public static function normalize_destination_tour_destinations( $raw_destinations ) {
                $destinations = array();

                if ( ! is_array( $raw_destinations ) ) {
                        return $destinations;
                }

                foreach ( $raw_destinations as $destination ) {
                        if ( ! is_array( $destination ) ) {
                                continue;
                        }

                        $label = sanitize_text_field( $destination['label'] ?? ( $destination['destination'] ?? '' ) );
                        $duration = sanitize_text_field( $destination['duration'] ?? '' );
                        $rate = self::maybe_null( $destination['rate'] ?? null );

                        if ( '' === $label || null === $rate ) {
                                continue;
                        }

                        $destinations[] = array(
                                'label'    => $label,
                                'duration' => $duration,
                                'rate'     => max( 0, (float) $rate ),
                        );
                }

                return $destinations;
        }

        public static function normalize_motorbike_models( $raw_models ) {
                $models = array();

                if ( ! is_array( $raw_models ) ) {
                        return $models;
                }

                foreach ( $raw_models as $model ) {
                        if ( ! is_array( $model ) ) {
                                continue;
                        }

                        $name = sanitize_text_field( $model['name'] ?? '' );
                        $rate = self::maybe_null( $model['rate'] ?? null );
                        $stock = max( 0, (int) ( $model['stock'] ?? 0 ) );

                        if ( '' === $name || null === $rate || $stock < 1 ) {
                                continue;
                        }

                        $models[] = array(
                                'name'  => $name,
                                'rate'  => max( 0, (float) $rate ),
                                'stock' => $stock,
                        );
                }

                return $models;
        }

        public static function normalize_boat_rates( $raw_rates ) {
                $rates = array();

                if ( ! is_array( $raw_rates ) ) {
                        return $rates;
                }

                foreach ( $raw_rates as $rate_row ) {
                        if ( ! is_array( $rate_row ) ) {
                                continue;
                        }

                        $min_pax = max( 1, (int) ( $rate_row['min_pax'] ?? ( $rate_row['minPax'] ?? 0 ) ) );
                        $max_pax = max( 1, (int) ( $rate_row['max_pax'] ?? ( $rate_row['maxPax'] ?? 0 ) ) );
                        $rate    = self::maybe_null( $rate_row['rate'] ?? null );
                        $label   = sanitize_text_field( $rate_row['label'] ?? '' );

                        if ( $max_pax < $min_pax ) {
                                $swap = $min_pax;
                                $min_pax = $max_pax;
                                $max_pax = $swap;
                        }

                        if ( null === $rate ) {
                                continue;
                        }

                        if ( '' === $label ) {
                                $label = $min_pax === $max_pax ? sprintf( '%d pax', $min_pax ) : sprintf( '%1$d-%2$d pax', $min_pax, $max_pax );
                        }

                        $rates[] = array(
                                'label'   => $label,
                                'minPax'  => $min_pax,
                                'maxPax'  => $max_pax,
                                'rate'    => max( 0, (float) $rate ),
                        );
                }

                usort( $rates, function ( $a, $b ) {
                        if ( (int) $a['minPax'] === (int) $b['minPax'] ) {
                                return (int) $a['maxPax'] <=> (int) $b['maxPax'];
                        }

                        return (int) $a['minPax'] <=> (int) $b['minPax'];
                } );

                return $rates;
        }

        public static function normalize_port_transfer_config( $raw_config ) {
                if ( is_string( $raw_config ) ) {
                        $decoded = json_decode( $raw_config, true );
                        $raw_config = is_array( $decoded ) ? $decoded : array();
                }

                if ( ! is_array( $raw_config ) ) {
                        $raw_config = array();
                }

                if (
                        array_key_exists( 'from_options', $raw_config ) ||
                        array_key_exists( 'fromOptions', $raw_config ) ||
                        array_key_exists( 'to_options', $raw_config ) ||
                        array_key_exists( 'toOptions', $raw_config ) ||
                        array_key_exists( 'rates', $raw_config ) ||
                        array_key_exists( 'addons', $raw_config ) ||
                        array_key_exists( 'convenience_fee_percent', $raw_config ) ||
                        array_key_exists( 'convenienceFeePercent', $raw_config )
                ) {
                        $from_options = self::normalize_motorbike_text_lines( $raw_config['from_options'] ?? ( $raw_config['fromOptions'] ?? array() ) );
                        $to_options   = self::normalize_motorbike_text_lines( $raw_config['to_options'] ?? ( $raw_config['toOptions'] ?? array() ) );

                        return array(
                                'fromOptions'             => $from_options,
                                'toOptions'               => $to_options,
                                'rates'                   => self::normalize_port_transfer_matrix_rates( $raw_config['rates'] ?? array(), $from_options, $to_options ),
                                'addons'                  => self::normalize_port_transfer_addons( $raw_config['addons'] ?? array() ),
                                'convenienceFeePercent'   => self::normalize_port_transfer_convenience_fee_percent( $raw_config ),
                        );
                }

                return self::normalize_legacy_port_transfer_config( $raw_config );
        }

        public static function normalize_port_transfer_routes( $raw_routes ) {
                return self::normalize_port_transfer_config( $raw_routes )['rates'];
        }

        public static function normalize_port_transfer_addons( $raw_addons ) {
                $addons = array();

                if ( ! is_array( $raw_addons ) ) {
                        return $addons;
                }

                foreach ( $raw_addons as $addon ) {
                        if ( ! is_array( $addon ) ) {
                                continue;
                        }

                        $label = sanitize_text_field( $addon['label'] ?? '' );
                        $rate  = self::maybe_null( $addon['rate'] ?? null );

                        if ( '' === $label || null === $rate ) {
                                continue;
                        }

                        $addons[] = array(
                                'label' => $label,
                                'rate'  => max( 0, (float) $rate ),
                        );
                }

                return $addons;
        }

        public static function normalize_pickup_dropoff_locations( $value ) {
                return self::normalize_motorbike_text_lines( $value );
        }

        public static function normalize_pickup_dropoff_route_rates( $raw_rates, $locations ) {
                $routes = array();
                $location_lookup = array_flip( self::normalize_pickup_dropoff_locations( $locations ) );
                $seen = array();

                if ( ! is_array( $raw_rates ) ) {
                        return $routes;
                }

                foreach ( $raw_rates as $route_row ) {
                        if ( ! is_array( $route_row ) ) {
                                continue;
                        }

                        $from = sanitize_text_field( $route_row['from'] ?? '' );
                        $to   = sanitize_text_field( $route_row['to'] ?? '' );
                        $rate = self::maybe_null( $route_row['rate'] ?? null );

                        if (
                                '' === $from ||
                                '' === $to ||
                                $from === $to ||
                                null === $rate ||
                                ( ! empty( $location_lookup ) && ( ! isset( $location_lookup[ $from ] ) || ! isset( $location_lookup[ $to ] ) ) )
                        ) {
                                continue;
                        }

                        $pair = self::get_pickup_dropoff_route_pair( $from, $to );
                        $seen[ $pair['key'] ] = array(
                                'from'  => $pair['from'],
                                'to'    => $pair['to'],
                                'label' => sprintf( '%1$s to %2$s', $pair['from'], $pair['to'] ),
                                'rate'  => max( 0, (float) $rate ),
                        );
                }

                $routes = array_values( $seen );
                usort( $routes, function ( $a, $b ) {
                        if ( (string) $a['from'] === (string) $b['from'] ) {
                                return strcasecmp( (string) $a['to'], (string) $b['to'] );
                        }

                        return strcasecmp( (string) $a['from'], (string) $b['from'] );
                } );

                return $routes;
        }

        private static function get_pickup_dropoff_route_pair( $from, $to ) {
                $from = sanitize_text_field( $from );
                $to = sanitize_text_field( $to );
                $pair = array( $from, $to );

                natcasesort( $pair );
                $pair = array_values( $pair );

                return array(
                        'from' => $pair[0],
                        'to'   => $pair[1],
                        'key'  => strtolower( $pair[0] ) . "\0" . strtolower( $pair[1] ),
                );
        }

        private static function normalize_port_transfer_convenience_fee_percent( $raw_config ) {
                if ( ! is_array( $raw_config ) ) {
                        return 0.0;
                }

                $raw_value = $raw_config['convenience_fee_percent'] ?? ( $raw_config['convenienceFeePercent'] ?? 0 );
                $percent = self::maybe_null( $raw_value );

                if ( null === $percent ) {
                        return 0.0;
                }

                return max( 0.0, (float) $percent );
        }

        private static function normalize_port_transfer_matrix_rates( $raw_rates, $from_options, $to_options ) {
                $routes = array();
                $from_lookup = array_flip( $from_options );
                $to_lookup = array_flip( $to_options );
                $seen = array();

                if ( ! is_array( $raw_rates ) ) {
                        return $routes;
                }

                foreach ( $raw_rates as $route_row ) {
                        if ( ! is_array( $route_row ) ) {
                                continue;
                        }

                        $from = sanitize_text_field( $route_row['from'] ?? ( $route_row['fromOption'] ?? '' ) );
                        $to   = sanitize_text_field( $route_row['to'] ?? ( $route_row['toOption'] ?? '' ) );
                        $rate = self::maybe_null( $route_row['rate'] ?? null );

                        if (
                                '' === $from ||
                                '' === $to ||
                                null === $rate ||
                                ( ! empty( $from_lookup ) && ! isset( $from_lookup[ $from ] ) ) ||
                                ( ! empty( $to_lookup ) && ! isset( $to_lookup[ $to ] ) )
                        ) {
                                continue;
                        }

                        $seen[ $from . "\0" . $to ] = array(
                                'from'  => $from,
                                'to'    => $to,
                                'label' => sprintf( '%1$s to %2$s', $from, $to ),
                                'rate'  => max( 0, (float) $rate ),
                        );
                }

                $routes = array_values( $seen );
                usort( $routes, function ( $a, $b ) {
                        if ( (string) $a['from'] === (string) $b['from'] ) {
                                return strcasecmp( (string) $a['to'], (string) $b['to'] );
                        }

                        return strcasecmp( (string) $a['from'], (string) $b['from'] );
                } );

                return $routes;
        }

        private static function normalize_legacy_port_transfer_config( $raw_routes ) {
                $from_options = array();
                $to_options = array();
                $rates = array();

                if ( ! is_array( $raw_routes ) ) {
                        $raw_routes = array();
                }

                foreach ( $raw_routes as $route_row ) {
                        if ( ! is_array( $route_row ) ) {
                                continue;
                        }

                        $label = sanitize_text_field( $route_row['label'] ?? '' );
                        $rate = self::maybe_null( $route_row['rate'] ?? null );

                        if ( '' === $label || null === $rate ) {
                                continue;
                        }

                        $from = sanitize_text_field( $route_row['from'] ?? 'Port / Seaport' );
                        $to = sanitize_text_field( $route_row['to'] ?? $label );

                        $from_options[] = $from;
                        $to_options[] = $to;
                        $rates[] = array(
                                'from'  => $from,
                                'to'    => $to,
                                'label' => sprintf( '%1$s to %2$s', $from, $to ),
                                'rate'  => max( 0, (float) $rate ),
                        );
                }

                return array(
                        'fromOptions'           => array_values( array_unique( $from_options ) ),
                        'toOptions'             => array_values( array_unique( $to_options ) ),
                        'rates'                 => $rates,
                        'addons'                => array(),
                        'convenienceFeePercent' => 0.0,
                );
        }

        public static function normalize_motorbike_text_lines( $value ) {
                $lines = array();

                if ( is_array( $value ) ) {
                        $raw_lines = $value;
                } else {
                        $raw_lines = preg_split( '/\r\n|\r|\n/', (string) $value );
                }

                foreach ( $raw_lines as $line ) {
                        $line = sanitize_text_field( $line );

                        if ( '' !== $line ) {
                                $lines[] = $line;
                        }
                }

                return array_values( array_unique( $lines ) );
        }

        public static function normalize_motorbike_day_options( $value ) {
                $days = array();

                if ( is_array( $value ) ) {
                        $raw_days = $value;
                } else {
                        $raw_days = preg_split( '/\r\n|\r|\n|,/', (string) $value );
                }

                foreach ( $raw_days as $day ) {
                        $day = (int) $day;

                        if ( $day > 0 ) {
                                $days[] = $day;
                        }
                }

                $days = array_values( array_unique( $days ) );
                sort( $days, SORT_NUMERIC );

                return $days;
        }

        private static function maybe_null( $val ) {
                if ( $val === null || $val === '' ) return null;
                return (float) $val;
        }
}
