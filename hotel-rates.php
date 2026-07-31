<?php
/**
 * Plugin Name: Hotel Rates Manager
 * Plugin URI:  https://example.com/hotel-rates
 * Description: Manage hotel and tour rates by region, place, and hotel with per-night, per-pax pricing.
 * Version:     1.5.100
 * Author:      Island Diaries Travel and Tours
 * License:     GPL-2.0+
 * Text Domain: hotel-rates
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

define( 'HR_VERSION',     '1.5.100' );
define( 'HR_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'HR_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'HR_PLUGIN_FILE', __FILE__ );

require_once HR_PLUGIN_DIR . 'includes/class-hotel-rates-db.php';
require_once HR_PLUGIN_DIR . 'includes/class-hotel-rates-admin.php';
require_once HR_PLUGIN_DIR . 'includes/class-hotel-rates-ajax.php';
require_once HR_PLUGIN_DIR . 'includes/class-hotel-rates-shortcodes.php';
require_once HR_PLUGIN_DIR . 'includes/class-hotel-rates-booking.php';

register_activation_hook( __FILE__, array( 'Hotel_Rates_DB', 'create_tables' ) );
register_deactivation_hook( __FILE__, '__return_true' );

add_action( 'plugins_loaded', function () {
        // Run DB upgrades on each load so existing installs get the new columns.
        Hotel_Rates_DB::maybe_upgrade();

        new Hotel_Rates_Admin();
        new Hotel_Rates_Ajax();
        new Hotel_Rates_Shortcodes();
        new Hotel_Rates_Booking();
} );
