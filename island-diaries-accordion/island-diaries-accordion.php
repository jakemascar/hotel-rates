<?php
/**
 * Plugin Name: Island Diaries Accordion
 * Description: Create reusable, responsive accordion sets with rates, itinerary, and rich-content layouts.
 * Version:     1.0.1
 * Author:      Island Diaries Travel and Tours
 * License:     GPL-2.0-or-later
 * Text Domain: island-diaries-accordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IDA_VERSION', '1.0.1' );
define( 'IDA_PLUGIN_FILE', __FILE__ );
define( 'IDA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IDA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once IDA_PLUGIN_DIR . 'includes/class-ida-admin.php';
require_once IDA_PLUGIN_DIR . 'includes/class-ida-shortcode.php';

final class Island_Diaries_Accordion {
	/**
	 * Start the plugin.
	 */
	public function __construct() {
		new IDA_Admin();
		new IDA_Shortcode();
	}

	/**
	 * Create one editable example on first activation.
	 */
	public static function activate() {
		IDA_Admin::register_post_type();

		$sample_id = absint( get_option( 'ida_sample_accordion_id' ) );
		if ( $sample_id && 'ida_accordion' === get_post_type( $sample_id ) ) {
			return;
		}

		$sample_id = wp_insert_post(
			array(
				'post_type'   => 'ida_accordion',
				'post_status' => 'publish',
				'post_title'  => __( 'Sample Tour Details', 'island-diaries-accordion' ),
			)
		);

		if ( is_wp_error( $sample_id ) || ! $sample_id ) {
			return;
		}

		update_post_meta( $sample_id, '_ida_sections', self::sample_sections() );
		update_option( 'ida_sample_accordion_id', $sample_id );
	}

	/**
	 * Content used by the activation example.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function sample_sections() {
		return array(
			array(
				'title'          => 'Exclusive Rates & Inclusions',
				'layout'         => 'rates',
				'initially_open' => 1,
				'left_heading'   => 'No. of Pax',
				'right_heading'  => 'Rate per Person',
				'details_heading'=> 'Details of the Package',
				'rates'          => array(
					array( 'label' => '1', 'value' => '₱ 4,250' ),
					array( 'label' => '2', 'value' => '₱ 2,695' ),
					array( 'label' => '3', 'value' => '₱ 2,177' ),
					array( 'label' => '4', 'value' => '₱ 2,243' ),
					array( 'label' => '5', 'value' => '₱ 2,022' ),
					array( 'label' => '6', 'value' => '₱ 1,875' ),
					array( 'label' => '7', 'value' => '₱ 1,770' ),
					array( 'label' => '8', 'value' => '₱ 1,691' ),
					array( 'label' => '9', 'value' => '₱ 1,630' ),
					array( 'label' => '10', 'value' => '₱ 1,581' ),
				),
				'details'       => '<h4>Inclusions:</h4><ul><li>Hotel pick-up and drop-off</li><li>All entrance fees</li><li>Lunch</li><li>Parking fees</li><li>Driver as Guide</li></ul><h4>Transportation:</h4><ul><li>1–3 pax using Tricycle/Tuk-tuk</li><li>4 pax and up using Van</li></ul>',
			),
			array(
				'title'          => 'Sample Itinerary',
				'layout'         => 'itinerary',
				'initially_open' => 0,
				'left_heading'   => 'Time',
				'right_heading'  => 'Activity',
				'itinerary'      => array(
					array(
						'label'      => '08:30 AM',
						'summary'    => 'Port/Hotel/Resort pick-up',
						'subheading' => 'Destinations:',
						'bullets'    => "Welcome to Siquijor\nSaint Francis of Assisi Church\nI Love Siquijor Signage\nPaliton Beach\nPitogo Cliff\nOld Enchanted Balete Tree & Fish Spa\nLazi Church & Lazi Convent\nCambugahay Falls",
					),
					array( 'label' => '12:00 PM', 'summary' => 'Lunch', 'subheading' => '', 'bullets' => '' ),
					array( 'label' => '01:00 PM', 'summary' => 'Salagdoong Resort', 'subheading' => '', 'bullets' => '' ),
					array( 'label' => '03:00 PM', 'summary' => 'Triad Overlooking Restaurant', 'subheading' => '', 'bullets' => '' ),
					array( 'label' => '04:30 PM', 'summary' => 'Hotel drop-off', 'subheading' => '', 'bullets' => '' ),
				),
			),
			array(
				'title'          => 'Tour Tips',
				'layout'         => 'content',
				'initially_open' => 0,
				'content'        => '<ul><li>Wear comfortable clothes and walking shoes.</li><li>Bring sun protection, drinking water, and a waterproof bag.</li><li>Please be ready at the pick-up point 15 minutes before departure.</li></ul>',
			),
			array(
				'title'          => 'Activity Policy',
				'layout'         => 'content',
				'initially_open' => 0,
				'content'        => '<p>Schedules may change because of weather, local conditions, or safety requirements. Please contact us in advance for cancellations or changes to your booking.</p>',
			),
		);
	}
}

register_activation_hook( __FILE__, array( 'Island_Diaries_Accordion', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		new Island_Diaries_Accordion();
	}
);
