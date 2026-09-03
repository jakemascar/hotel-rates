<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Hotel_Rates_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_hr_get_places',       array( $this, 'get_places' ) );
		add_action( 'wp_ajax_hr_get_hotels',       array( $this, 'get_hotels' ) );
		add_action( 'wp_ajax_hr_update_tour_name', array( $this, 'update_tour_name' ) );
	}

	private function verify() {
		check_ajax_referer( 'hr_ajax_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}
	}

	public function get_places() {
		$this->verify();
		$region_id = (int) ( $_POST['region_id'] ?? 0 );
		$places    = Hotel_Rates_DB::get_places( $region_id );
		wp_send_json_success( $places );
	}

	public function get_hotels() {
		$this->verify();
		$place_id = (int) ( $_POST['place_id'] ?? 0 );
		$hotels   = Hotel_Rates_DB::get_hotels( $place_id );
		wp_send_json_success( $hotels );
	}

	public function update_tour_name() {
		$this->verify();
		$id   = (int) ( $_POST['id'] ?? 0 );
		$name = sanitize_text_field( $_POST['tour_name'] ?? '' );
		if ( ! $id || $name === '' ) {
			wp_send_json_error( 'Invalid data.' );
		}
		Hotel_Rates_DB::update_tour_name( $id, $name );
		wp_send_json_success( array( 'tour_name' => $name ) );
	}
}
