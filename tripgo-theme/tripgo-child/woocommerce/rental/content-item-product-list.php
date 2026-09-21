<?php
/**
 * Tripgo list-card currency compatibility.
 *
 * Keep the parent template authoritative while limiting display conversion to
 * the wc_price() calls made during this one card render.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hotel_rates_currency_scope = class_exists( 'Hotel_Rates_Currency' )
	&& method_exists( 'Hotel_Rates_Currency', 'begin_tripgo_archive_price_scope' )
	&& method_exists( 'Hotel_Rates_Currency', 'end_tripgo_archive_price_scope' );

if ( $hotel_rates_currency_scope ) {
	Hotel_Rates_Currency::begin_tripgo_archive_price_scope();
}

try {
	include get_template_directory() . '/woocommerce/rental/content-item-product-list.php';
} finally {
	if ( $hotel_rates_currency_scope ) {
		Hotel_Rates_Currency::end_tripgo_archive_price_scope();
	}
}
