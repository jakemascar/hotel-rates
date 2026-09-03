<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Hotel_Rates_Admin {

        public function __construct() {
                add_action( 'admin_menu',            array( $this, 'register_menus' ) );
                add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
                add_action( 'add_meta_boxes',        array( $this, 'register_product_meta_boxes' ) );
                add_action( 'save_post_product',     array( $this, 'save_product_package_deal_link' ) );

                // Hotel Rates handlers.
                add_action( 'admin_post_hr_save_place',     array( $this, 'handle_save_place' ) );
                add_action( 'admin_post_hr_delete_place',   array( $this, 'handle_delete_place' ) );
                add_action( 'admin_post_hr_save_hotel',     array( $this, 'handle_save_hotel' ) );
                add_action( 'admin_post_hr_delete_hotel',   array( $this, 'handle_delete_hotel' ) );
                add_action( 'admin_post_hr_save_rate',      array( $this, 'handle_save_rate' ) );
                add_action( 'admin_post_hr_delete_rate',    array( $this, 'handle_delete_rate' ) );

                // Tour registration handlers (from Register page).
                add_action( 'admin_post_hr_save_tour_reg',   array( $this, 'handle_save_tour_reg' ) );
                add_action( 'admin_post_hr_delete_tour_reg', array( $this, 'handle_delete_tour_reg' ) );

                // Tour Rates — single save/delete (modal, from Tour Rates page).
                add_action( 'admin_post_hr_save_tour_rate',   array( $this, 'handle_save_tour_rate' ) );
                add_action( 'admin_post_hr_delete_tour_rate', array( $this, 'handle_delete_tour_rate' ) );

                // Tour Rates batch save (legacy, kept for compatibility).
                add_action( 'admin_post_hr_save_tour_rates', array( $this, 'handle_save_tour_rates' ) );

                // Package Deals handlers.
                add_action( 'admin_post_hr_save_package_deal', array( $this, 'handle_save_package_deal' ) );
                add_action( 'admin_post_hr_delete_package_deal', array( $this, 'handle_delete_package_deal' ) );

                // Motorbike Rental handlers.
                add_action( 'admin_post_hr_save_motorbike_rental', array( $this, 'handle_save_motorbike_rental' ) );
                add_action( 'admin_post_hr_delete_motorbike_rental', array( $this, 'handle_delete_motorbike_rental' ) );

                // Boat Rental handlers.
                add_action( 'admin_post_hr_save_boat_rental', array( $this, 'handle_save_boat_rental' ) );
                add_action( 'admin_post_hr_delete_boat_rental', array( $this, 'handle_delete_boat_rental' ) );

                // Self-Drive Rental handlers.
                add_action( 'admin_post_hr_save_self_drive_rental', array( $this, 'handle_save_self_drive_rental' ) );
                add_action( 'admin_post_hr_delete_self_drive_rental', array( $this, 'handle_delete_self_drive_rental' ) );

                // Port Transfer handlers.
                add_action( 'admin_post_hr_save_port_transfer', array( $this, 'handle_save_port_transfer' ) );
                add_action( 'admin_post_hr_delete_port_transfer', array( $this, 'handle_delete_port_transfer' ) );

                // Destination Tour handlers.
                add_action( 'admin_post_hr_save_destination_tour', array( $this, 'handle_save_destination_tour' ) );
                add_action( 'admin_post_hr_delete_destination_tour', array( $this, 'handle_delete_destination_tour' ) );

                // Pick-up & Drop-off Service handlers.
                add_action( 'admin_post_hr_save_pickup_dropoff_service', array( $this, 'handle_save_pickup_dropoff_service' ) );
                add_action( 'admin_post_hr_delete_pickup_dropoff_service', array( $this, 'handle_delete_pickup_dropoff_service' ) );
        }

        /* ── Menus ───────────────────────────────────────────────────────── */

        public function register_menus() {
                add_menu_page(
                        __( 'Hotel Rates', 'hotel-rates' ),
                        __( 'Hotel Rates', 'hotel-rates' ),
                        'manage_options',
                        'hotel-rates',
                        array( $this, 'page_hotel_rates' ),
                        'dashicons-building',
                        30
                );
                add_submenu_page( 'hotel-rates', __( 'Hotel Rates', 'hotel-rates' ),     __( 'Hotel Rates', 'hotel-rates' ),                      'manage_options', 'hotel-rates',              array( $this, 'page_hotel_rates' ) );
                add_submenu_page( 'hotel-rates', __( 'Tour Rates', 'hotel-rates' ),      __( 'Tour Rates', 'hotel-rates' ),                       'manage_options', 'hotel-rates-tour',          array( $this, 'page_tour_rates' ) );
                add_submenu_page( 'hotel-rates', __( 'Motorbike Rentals', 'hotel-rates' ), __( 'Motorbike Rentals', 'hotel-rates' ),               'manage_options', 'hotel-rates-motorbikes',    array( $this, 'page_motorbike_rentals' ) );
                add_submenu_page( 'hotel-rates', __( 'Boat Rentals', 'hotel-rates' ),    __( 'Boat Rentals', 'hotel-rates' ),                     'manage_options', 'hotel-rates-boats',         array( $this, 'page_boat_rentals' ) );
                add_submenu_page( 'hotel-rates', __( 'Self-Drive Vehicles', 'hotel-rates' ), __( 'Self-Drive Vehicles', 'hotel-rates' ),           'manage_options', 'hotel-rates-self-drive',    array( $this, 'page_self_drive_rentals' ) );
                add_submenu_page( 'hotel-rates', __( 'Port Transfers', 'hotel-rates' ),  __( 'Port Transfers', 'hotel-rates' ),                   'manage_options', 'hotel-rates-port-transfers', array( $this, 'page_port_transfers' ) );
                add_submenu_page( 'hotel-rates', __( 'Destination Tours', 'hotel-rates' ), __( 'Destination Tours', 'hotel-rates' ),              'manage_options', 'hotel-rates-destination-tours', array( $this, 'page_destination_tours' ) );
                add_submenu_page( 'hotel-rates', __( 'Pick-up & Drop-off', 'hotel-rates' ), __( 'Pick-up & Drop-off', 'hotel-rates' ),            'manage_options', 'hotel-rates-pickup-dropoff', array( $this, 'page_pickup_dropoff_services' ) );
                add_submenu_page( 'hotel-rates', __( 'Register Hotels, Places, & Tours', 'hotel-rates' ), __( 'Register Hotels, Places, & Tours', 'hotel-rates' ), 'manage_options', 'hotel-rates-register', array( $this, 'page_register' ) );
        }

        /* ── Assets ──────────────────────────────────────────────────────── */

        public function enqueue_assets( $hook ) {
                $pages = array(
                        'toplevel_page_hotel-rates',
                        'hotel-rates_page_hotel-rates-tour',
                        'hotel-rates_page_hotel-rates-motorbikes',
                        'hotel-rates_page_hotel-rates-boats',
                        'hotel-rates_page_hotel-rates-self-drive',
                        'hotel-rates_page_hotel-rates-port-transfers',
                        'hotel-rates_page_hotel-rates-destination-tours',
                        'hotel-rates_page_hotel-rates-pickup-dropoff',
                        'hotel-rates_page_hotel-rates-register',
                );
                $is_hotel_rates_page = in_array( $hook, $pages, true );
                $screen              = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
                $is_product_editor   = in_array( $hook, array( 'post.php', 'post-new.php' ), true )
                        && $screen
                        && isset( $screen->post_type )
                        && 'product' === $screen->post_type;

                if ( ! $is_hotel_rates_page && ! $is_product_editor ) return;

                $script_dependencies = array( 'jquery' );

                if ( $is_product_editor ) {
                        if ( wp_script_is( 'selectWoo', 'registered' ) ) {
                                wp_enqueue_script( 'selectWoo' );
                                $script_dependencies[] = 'selectWoo';
                        } elseif ( wp_script_is( 'select2', 'registered' ) ) {
                                wp_enqueue_script( 'select2' );
                                $script_dependencies[] = 'select2';
                        }

                        if ( wp_style_is( 'select2', 'registered' ) ) {
                                wp_enqueue_style( 'select2' );
                        }
                }

                wp_enqueue_style( 'hr-admin', HR_PLUGIN_URL . 'admin/css/admin.css', array(), HR_VERSION );
                wp_enqueue_script( 'hr-admin', HR_PLUGIN_URL . 'admin/js/admin.js', $script_dependencies, HR_VERSION, true );
                wp_localize_script( 'hr-admin', 'hrAjax', array(
                        'ajaxurl' => admin_url( 'admin-ajax.php' ),
                        'nonce'   => wp_create_nonce( 'hr_ajax_nonce' ),
                ) );
        }

        /* ── Page: Hotel Rates ───────────────────────────────────────────── */

        public function page_hotel_rates() {
                $regions       = Hotel_Rates_DB::get_regions();
                $region_id     = isset( $_GET['region_id'] ) ? (int) $_GET['region_id'] : ( ! empty( $regions ) ? $regions[0]->id : 0 );
                $places        = $region_id ? Hotel_Rates_DB::get_places( $region_id ) : array();
                $place_id      = isset( $_GET['place_id'] ) ? (int) $_GET['place_id'] : ( ! empty( $places ) ? $places[0]->id : 0 );
                $hotels        = $place_id ? Hotel_Rates_DB::get_hotels( $place_id ) : array();
                $hotel_id      = isset( $_GET['hotel_id'] ) ? (int) $_GET['hotel_id'] : ( ! empty( $hotels ) ? $hotels[0]->id : 0 );
                $hotel         = $hotel_id ? Hotel_Rates_DB::get_hotel( $hotel_id ) : null;
                $nights_filter = isset( $_GET['nights'] ) ? array_map( 'intval', (array) $_GET['nights'] ) : array( 1, 2, 3, 4, 5 );
                include HR_PLUGIN_DIR . 'admin/views/hotel-rates.php';
        }

        /* ── Page: Tour Rates ────────────────────────────────────────────── */

        public function page_tour_rates() {
                $regions   = Hotel_Rates_DB::get_regions();
                $region_id = isset( $_GET['region_id'] ) ? (int) $_GET['region_id'] : ( ! empty( $regions ) ? $regions[0]->id : 0 );
                $places    = $region_id ? Hotel_Rates_DB::get_places( $region_id ) : array();
                $place_id  = isset( $_GET['place_id'] ) ? (int) $_GET['place_id'] : ( ! empty( $places ) ? $places[0]->id : 0 );
                $tour_type = isset( $_GET['tour_type'] ) ? rawurldecode( sanitize_text_field( wp_unslash( $_GET['tour_type'] ) ) ) : 'Day Tour';
                if ( ! in_array( $tour_type, Hotel_Rates_DB::TOUR_TYPES, true ) ) {
                        $tour_type = 'Day Tour';
                }
                if ( 'Package Deal' === $tour_type ) {
                        $tour_rates = $place_id ? Hotel_Rates_DB::get_package_deals_by_place( $place_id ) : array();
                } else {
                        $tour_rates = ( $place_id && $tour_type ) ? Hotel_Rates_DB::get_tour_rates( $place_id, $tour_type ) : array();
                }
                include HR_PLUGIN_DIR . 'admin/views/tour-rates.php';
        }

        /* ── Page: Register ──────────────────────────────────────────────── */

        public function page_register() {
                $tab     = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'place';
                $regions = Hotel_Rates_DB::get_regions();
                $places  = Hotel_Rates_DB::get_places();
                $edit_package_deal = isset( $_GET['edit_package_deal'] ) ? Hotel_Rates_DB::get_package_deal( (int) $_GET['edit_package_deal'] ) : null;
                include HR_PLUGIN_DIR . 'admin/views/register.php';
        }

        public function page_motorbike_rentals() {
                $motorbike_rentals = Hotel_Rates_DB::get_motorbike_rentals();
                $edit_motorbike_rental = isset( $_GET['edit_motorbike_rental'] ) ? Hotel_Rates_DB::get_motorbike_rental( (int) $_GET['edit_motorbike_rental'] ) : null;
                include HR_PLUGIN_DIR . 'admin/views/motorbike-rentals.php';
        }

        public function page_boat_rentals() {
                $boat_rentals = Hotel_Rates_DB::get_boat_rentals();
                $edit_boat_rental = isset( $_GET['edit_boat_rental'] ) ? Hotel_Rates_DB::get_boat_rental( (int) $_GET['edit_boat_rental'] ) : null;
                include HR_PLUGIN_DIR . 'admin/views/boat-rentals.php';
        }

        public function page_self_drive_rentals() {
                $self_drive_rentals = Hotel_Rates_DB::get_self_drive_rentals();
                $edit_self_drive_rental = isset( $_GET['edit_self_drive_rental'] ) ? Hotel_Rates_DB::get_self_drive_rental( (int) $_GET['edit_self_drive_rental'] ) : null;
                include HR_PLUGIN_DIR . 'admin/views/self-drive-rentals.php';
        }

        public function page_port_transfers() {
                $port_transfers = Hotel_Rates_DB::get_port_transfers();
                $edit_port_transfer = isset( $_GET['edit_port_transfer'] ) ? Hotel_Rates_DB::get_port_transfer( (int) $_GET['edit_port_transfer'] ) : null;
                include HR_PLUGIN_DIR . 'admin/views/port-transfers.php';
        }

        public function page_destination_tours() {
                $destination_tours = Hotel_Rates_DB::get_destination_tours();
                $edit_destination_tour = isset( $_GET['edit_destination_tour'] ) ? Hotel_Rates_DB::get_destination_tour( (int) $_GET['edit_destination_tour'] ) : null;
                include HR_PLUGIN_DIR . 'admin/views/destination-tours.php';
        }

        public function page_pickup_dropoff_services() {
                $pickup_dropoff_services = Hotel_Rates_DB::get_pickup_dropoff_services();
                $edit_pickup_dropoff_service = isset( $_GET['edit_pickup_dropoff_service'] ) ? Hotel_Rates_DB::get_pickup_dropoff_service( (int) $_GET['edit_pickup_dropoff_service'] ) : null;
                include HR_PLUGIN_DIR . 'admin/views/pickup-dropoff-services.php';
        }

        public function register_product_meta_boxes() {
                add_meta_box(
                        'hr-package-deal-link',
                        __( 'Linked Package Deal', 'hotel-rates' ),
                        array( $this, 'render_product_package_deal_meta_box' ),
                        'product',
                        'side',
                        'default'
                );

                add_meta_box(
                        'hr-day-tour-rate-link',
                        __( 'Linked Day Tour', 'hotel-rates' ),
                        array( $this, 'render_product_day_tour_rate_meta_box' ),
                        'product',
                        'side',
                        'default'
                );

                add_meta_box(
                        'hr-joiner-tour-rate-link',
                        __( 'Linked Joiner Tour', 'hotel-rates' ),
                        array( $this, 'render_product_joiner_tour_rate_meta_box' ),
                        'product',
                        'side',
                        'default'
                );

                add_meta_box(
                        'hr-airport-transfer-rate-link',
                        __( 'Linked Airport Transfer', 'hotel-rates' ),
                        array( $this, 'render_product_airport_transfer_rate_meta_box' ),
                        'product',
                        'side',
                        'default'
                );

                add_meta_box(
                        'hr-motorbike-rental-link',
                        __( 'Linked Motorbike Rental', 'hotel-rates' ),
                        array( $this, 'render_product_motorbike_rental_meta_box' ),
                        'product',
                        'side',
                        'default'
                );

                add_meta_box(
                        'hr-boat-rental-link',
                        __( 'Linked Boat Rental', 'hotel-rates' ),
                        array( $this, 'render_product_boat_rental_meta_box' ),
                        'product',
                        'side',
                        'default'
                );

                add_meta_box(
                        'hr-self-drive-rental-link',
                        __( 'Linked Self-Drive Vehicle', 'hotel-rates' ),
                        array( $this, 'render_product_self_drive_rental_meta_box' ),
                        'product',
                        'side',
                        'default'
                );

                add_meta_box(
                        'hr-port-transfer-link',
                        __( 'Linked Port Transfer', 'hotel-rates' ),
                        array( $this, 'render_product_port_transfer_meta_box' ),
                        'product',
                        'side',
                        'default'
                );

                add_meta_box(
                        'hr-destination-tour-link',
                        __( 'Linked Destination Tour', 'hotel-rates' ),
                        array( $this, 'render_product_destination_tour_meta_box' ),
                        'product',
                        'side',
                        'default'
                );

                add_meta_box(
                        'hr-pickup-dropoff-service-link',
                        __( 'Linked Pick-up & Drop-off Service', 'hotel-rates' ),
                        array( $this, 'render_product_pickup_dropoff_service_meta_box' ),
                        'product',
                        'side',
                        'default'
                );
        }

        public function render_product_package_deal_meta_box( $post ) {
                $selected_package_deal_id = (int) get_post_meta( $post->ID, '_hr_package_deal_id', true );
                $package_deals            = Hotel_Rates_DB::get_package_deals();
                $selected_package_deal    = $selected_package_deal_id ? Hotel_Rates_DB::get_package_deal( $selected_package_deal_id ) : null;
                $places_lookup            = array();

                foreach ( Hotel_Rates_DB::get_places() as $place ) {
                        $places_lookup[ (int) $place->id ] = $place->name;
                }

                include HR_PLUGIN_DIR . 'admin/views/partials/product-package-deal-meta-box.php';
        }

        public function render_product_day_tour_rate_meta_box( $post ) {
                $this->render_product_tour_rate_meta_box( $post, 'Day Tour' );
        }

        public function render_product_joiner_tour_rate_meta_box( $post ) {
                $this->render_product_tour_rate_meta_box( $post, 'Joiner' );
        }

        public function render_product_airport_transfer_rate_meta_box( $post ) {
                $this->render_product_tour_rate_meta_box( $post, 'Airport Transfer' );
        }

        public function render_product_motorbike_rental_meta_box( $post ) {
                $selected_motorbike_rental_id = (int) get_post_meta( $post->ID, '_hr_motorbike_rental_id', true );
                $motorbike_rentals            = Hotel_Rates_DB::get_motorbike_rentals();
                $selected_motorbike_rental    = $selected_motorbike_rental_id ? Hotel_Rates_DB::get_motorbike_rental( $selected_motorbike_rental_id ) : null;

                include HR_PLUGIN_DIR . 'admin/views/partials/product-motorbike-rental-meta-box.php';
        }

        public function render_product_boat_rental_meta_box( $post ) {
                $selected_boat_rental_id = (int) get_post_meta( $post->ID, '_hr_boat_rental_id', true );
                $boat_rentals            = Hotel_Rates_DB::get_boat_rentals();
                $selected_boat_rental    = $selected_boat_rental_id ? Hotel_Rates_DB::get_boat_rental( $selected_boat_rental_id ) : null;

                include HR_PLUGIN_DIR . 'admin/views/partials/product-boat-rental-meta-box.php';
        }

        public function render_product_self_drive_rental_meta_box( $post ) {
                $selected_self_drive_rental_id = (int) get_post_meta( $post->ID, '_hr_self_drive_rental_id', true );
                $self_drive_rentals            = Hotel_Rates_DB::get_self_drive_rentals();
                $selected_self_drive_rental    = $selected_self_drive_rental_id ? Hotel_Rates_DB::get_self_drive_rental( $selected_self_drive_rental_id ) : null;

                include HR_PLUGIN_DIR . 'admin/views/partials/product-self-drive-rental-meta-box.php';
        }

        public function render_product_port_transfer_meta_box( $post ) {
                $selected_port_transfer_id = (int) get_post_meta( $post->ID, '_hr_port_transfer_id', true );
                $port_transfers            = Hotel_Rates_DB::get_port_transfers();
                $selected_port_transfer    = $selected_port_transfer_id ? Hotel_Rates_DB::get_port_transfer( $selected_port_transfer_id ) : null;

                include HR_PLUGIN_DIR . 'admin/views/partials/product-port-transfer-meta-box.php';
        }

        public function render_product_destination_tour_meta_box( $post ) {
                $selected_destination_tour_id = (int) get_post_meta( $post->ID, '_hr_destination_tour_id', true );
                $destination_tours            = Hotel_Rates_DB::get_destination_tours();
                $selected_destination_tour    = $selected_destination_tour_id ? Hotel_Rates_DB::get_destination_tour( $selected_destination_tour_id ) : null;

                include HR_PLUGIN_DIR . 'admin/views/partials/product-destination-tour-meta-box.php';
        }

        public function render_product_pickup_dropoff_service_meta_box( $post ) {
                $selected_pickup_dropoff_service_id = (int) get_post_meta( $post->ID, '_hr_pickup_dropoff_service_id', true );
                $pickup_dropoff_services            = Hotel_Rates_DB::get_pickup_dropoff_services();
                $selected_pickup_dropoff_service    = $selected_pickup_dropoff_service_id ? Hotel_Rates_DB::get_pickup_dropoff_service( $selected_pickup_dropoff_service_id ) : null;

                include HR_PLUGIN_DIR . 'admin/views/partials/product-pickup-dropoff-service-meta-box.php';
        }

        private function render_product_tour_rate_meta_box( $post, $tour_link_type ) {
                $is_joiner             = 'Joiner' === $tour_link_type;
                $is_airport_transfer   = 'Airport Transfer' === $tour_link_type;
                $meta_key              = $is_airport_transfer ? '_hr_airport_transfer_rate_id' : ( $is_joiner ? '_hr_joiner_tour_rate_id' : '_hr_day_tour_rate_id' );
                $legacy_tour_rate_id   = (int) get_post_meta( $post->ID, '_hr_tour_rate_id', true );
                $selected_tour_rate_id = (int) get_post_meta( $post->ID, $meta_key, true );
                $tour_rates            = Hotel_Rates_DB::get_bookable_tour_rates( $tour_link_type );
                $selected_tour_rate    = $selected_tour_rate_id ? Hotel_Rates_DB::get_tour_rate_by_id( $selected_tour_rate_id ) : null;

                if ( ! $is_airport_transfer && ! $selected_tour_rate && $legacy_tour_rate_id ) {
                        $legacy_tour_rate = Hotel_Rates_DB::get_tour_rate_by_id( $legacy_tour_rate_id );

                        if ( $legacy_tour_rate && $tour_link_type === $legacy_tour_rate->type ) {
                                $selected_tour_rate_id = $legacy_tour_rate_id;
                                $selected_tour_rate    = $legacy_tour_rate;
                        }
                }

                if ( $selected_tour_rate && $tour_link_type !== $selected_tour_rate->type ) {
                        $selected_tour_rate = null;
                }

                include HR_PLUGIN_DIR . 'admin/views/partials/product-tour-rate-meta-box.php';
        }

        public function save_product_package_deal_link( $post_id ) {
                if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                        return;
                }

                $can_save_package_link = isset( $_POST['hr_product_package_deal_nonce'] ) && wp_verify_nonce( $_POST['hr_product_package_deal_nonce'], 'hr_save_product_package_deal' );
                $can_save_day_tour_link = isset( $_POST['hr_product_day_tour_rate_nonce'] ) && wp_verify_nonce( $_POST['hr_product_day_tour_rate_nonce'], 'hr_save_product_day_tour_rate' );
                $can_save_joiner_tour_link = isset( $_POST['hr_product_joiner_tour_rate_nonce'] ) && wp_verify_nonce( $_POST['hr_product_joiner_tour_rate_nonce'], 'hr_save_product_joiner_tour_rate' );
                $can_save_airport_transfer_link = isset( $_POST['hr_product_airport_transfer_rate_nonce'] ) && wp_verify_nonce( $_POST['hr_product_airport_transfer_rate_nonce'], 'hr_save_product_airport_transfer_rate' );
                $can_save_motorbike_rental_link = isset( $_POST['hr_product_motorbike_rental_nonce'] ) && wp_verify_nonce( $_POST['hr_product_motorbike_rental_nonce'], 'hr_save_product_motorbike_rental' );
                $can_save_boat_rental_link = isset( $_POST['hr_product_boat_rental_nonce'] ) && wp_verify_nonce( $_POST['hr_product_boat_rental_nonce'], 'hr_save_product_boat_rental' );
                $can_save_self_drive_rental_link = isset( $_POST['hr_product_self_drive_rental_nonce'] ) && wp_verify_nonce( $_POST['hr_product_self_drive_rental_nonce'], 'hr_save_product_self_drive_rental' );
                $can_save_port_transfer_link = isset( $_POST['hr_product_port_transfer_nonce'] ) && wp_verify_nonce( $_POST['hr_product_port_transfer_nonce'], 'hr_save_product_port_transfer' );
                $can_save_destination_tour_link = isset( $_POST['hr_product_destination_tour_nonce'] ) && wp_verify_nonce( $_POST['hr_product_destination_tour_nonce'], 'hr_save_product_destination_tour' );
                $can_save_pickup_dropoff_service_link = isset( $_POST['hr_product_pickup_dropoff_service_nonce'] ) && wp_verify_nonce( $_POST['hr_product_pickup_dropoff_service_nonce'], 'hr_save_product_pickup_dropoff_service' );

                if ( ! $can_save_package_link && ! $can_save_day_tour_link && ! $can_save_joiner_tour_link && ! $can_save_airport_transfer_link && ! $can_save_motorbike_rental_link && ! $can_save_boat_rental_link && ! $can_save_self_drive_rental_link && ! $can_save_port_transfer_link && ! $can_save_destination_tour_link && ! $can_save_pickup_dropoff_service_link ) {
                        return;
                }

                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                        return;
                }

                if ( $can_save_package_link ) {
                        $package_deal_id = isset( $_POST['hr_package_deal_id'] ) ? (int) $_POST['hr_package_deal_id'] : 0;

                        if ( $package_deal_id > 0 && Hotel_Rates_DB::get_package_deal( $package_deal_id ) ) {
                                update_post_meta( $post_id, '_hr_package_deal_id', $package_deal_id );
                        } else {
                                delete_post_meta( $post_id, '_hr_package_deal_id' );
                        }
                }

                if ( $can_save_day_tour_link ) {
                        $this->save_product_tour_rate_link( $post_id, 'Day Tour', 'hr_day_tour_rate_id', '_hr_day_tour_rate_id' );
                }

                if ( $can_save_joiner_tour_link ) {
                        $this->save_product_tour_rate_link( $post_id, 'Joiner', 'hr_joiner_tour_rate_id', '_hr_joiner_tour_rate_id' );
                }

                if ( $can_save_airport_transfer_link ) {
                        $this->save_product_tour_rate_link( $post_id, 'Airport Transfer', 'hr_airport_transfer_rate_id', '_hr_airport_transfer_rate_id' );
                }

                if ( $can_save_motorbike_rental_link ) {
                        $motorbike_rental_id = isset( $_POST['hr_motorbike_rental_id'] ) ? (int) $_POST['hr_motorbike_rental_id'] : 0;

                        if ( $motorbike_rental_id > 0 && Hotel_Rates_DB::get_motorbike_rental( $motorbike_rental_id ) ) {
                                update_post_meta( $post_id, '_hr_motorbike_rental_id', $motorbike_rental_id );
                        } else {
                                delete_post_meta( $post_id, '_hr_motorbike_rental_id' );
                        }
                }

                if ( $can_save_boat_rental_link ) {
                        $boat_rental_id = isset( $_POST['hr_boat_rental_id'] ) ? (int) $_POST['hr_boat_rental_id'] : 0;

                        if ( $boat_rental_id > 0 && Hotel_Rates_DB::get_boat_rental( $boat_rental_id ) ) {
                                update_post_meta( $post_id, '_hr_boat_rental_id', $boat_rental_id );
                        } else {
                                delete_post_meta( $post_id, '_hr_boat_rental_id' );
                        }
                }

                if ( $can_save_self_drive_rental_link ) {
                        $self_drive_rental_id = isset( $_POST['hr_self_drive_rental_id'] ) ? (int) $_POST['hr_self_drive_rental_id'] : 0;

                        if ( $self_drive_rental_id > 0 && Hotel_Rates_DB::get_self_drive_rental( $self_drive_rental_id ) ) {
                                update_post_meta( $post_id, '_hr_self_drive_rental_id', $self_drive_rental_id );
                        } else {
                                delete_post_meta( $post_id, '_hr_self_drive_rental_id' );
                        }
                }

                if ( $can_save_port_transfer_link ) {
                        $port_transfer_id = isset( $_POST['hr_port_transfer_id'] ) ? (int) $_POST['hr_port_transfer_id'] : 0;

                        if ( $port_transfer_id > 0 && Hotel_Rates_DB::get_port_transfer( $port_transfer_id ) ) {
                                update_post_meta( $post_id, '_hr_port_transfer_id', $port_transfer_id );
                        } else {
                                delete_post_meta( $post_id, '_hr_port_transfer_id' );
                        }
                }

                if ( $can_save_destination_tour_link ) {
                        $destination_tour_id = isset( $_POST['hr_destination_tour_id'] ) ? (int) $_POST['hr_destination_tour_id'] : 0;

                        if ( $destination_tour_id > 0 && Hotel_Rates_DB::get_destination_tour( $destination_tour_id ) ) {
                                update_post_meta( $post_id, '_hr_destination_tour_id', $destination_tour_id );
                        } else {
                                delete_post_meta( $post_id, '_hr_destination_tour_id' );
                        }
                }

                if ( $can_save_pickup_dropoff_service_link ) {
                        $pickup_dropoff_service_id = isset( $_POST['hr_pickup_dropoff_service_id'] ) ? (int) $_POST['hr_pickup_dropoff_service_id'] : 0;

                        if ( $pickup_dropoff_service_id > 0 && Hotel_Rates_DB::get_pickup_dropoff_service( $pickup_dropoff_service_id ) ) {
                                update_post_meta( $post_id, '_hr_pickup_dropoff_service_id', $pickup_dropoff_service_id );
                        } else {
                                delete_post_meta( $post_id, '_hr_pickup_dropoff_service_id' );
                        }
                }

                if ( $can_save_day_tour_link || $can_save_joiner_tour_link ) {
                        $day_tour_rate_id    = (int) get_post_meta( $post_id, '_hr_day_tour_rate_id', true );
                        $joiner_tour_rate_id = (int) get_post_meta( $post_id, '_hr_joiner_tour_rate_id', true );
                        $legacy_tour_rate_id = $day_tour_rate_id ? $day_tour_rate_id : $joiner_tour_rate_id;

                        if ( $legacy_tour_rate_id ) {
                                update_post_meta( $post_id, '_hr_tour_rate_id', $legacy_tour_rate_id );
                        } else {
                                delete_post_meta( $post_id, '_hr_tour_rate_id' );
                        }
                }
        }

        private function save_product_tour_rate_link( $post_id, $tour_type, $field_name, $meta_key ) {
                $tour_rate_id = isset( $_POST[ $field_name ] ) ? (int) $_POST[ $field_name ] : 0;
                $tour_rate    = $tour_rate_id ? Hotel_Rates_DB::get_tour_rate_by_id( $tour_rate_id ) : null;

                if ( $tour_rate && $tour_type === $tour_rate->type ) {
                        update_post_meta( $post_id, $meta_key, $tour_rate_id );
                        return;
                }

                delete_post_meta( $post_id, $meta_key );
        }

        /* ── Nonce Helper ────────────────────────────────────────────────── */

        private function verify_nonce( $action ) {
                if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $action ) ) {
                        wp_die( 'Security check failed.' );
                }
                if ( ! current_user_can( 'manage_options' ) ) {
                        wp_die( 'Insufficient permissions.' );
                }
        }

        private function reg_redirect( $tab, $extra = array() ) {
                $args = array_merge( array( 'page' => 'hotel-rates-register', 'tab' => $tab ), $extra );
                wp_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
                exit;
        }

        private function get_tour_registration_tab( $type ) {
                if ( 'Joiner' === $type ) {
                        return 'joiners';
                }

                if ( 'Airport Transfer' === $type ) {
                        return 'airport-transfers';
                }

                return 'day-tours';
        }

        /* ── Place Handlers ──────────────────────────────────────────────── */

        public function handle_save_place() {
                $this->verify_nonce( 'hr_save_place' );
                Hotel_Rates_DB::save_place( $_POST );
                $this->reg_redirect( 'place', array( 'saved' => 1 ) );
        }

        public function handle_delete_place() {
                $this->verify_nonce( 'hr_delete_place' );
                Hotel_Rates_DB::delete_place( (int) $_POST['id'] );
                $this->reg_redirect( 'place', array( 'deleted' => 1 ) );
        }

        /* ── Hotel Handlers ──────────────────────────────────────────────── */

        public function handle_save_hotel() {
                $this->verify_nonce( 'hr_save_hotel' );
                Hotel_Rates_DB::save_hotel( $_POST );
                $this->reg_redirect( 'hotels', array( 'saved' => 1 ) );
        }

        public function handle_delete_hotel() {
                $this->verify_nonce( 'hr_delete_hotel' );
                Hotel_Rates_DB::delete_hotel( (int) $_POST['id'] );
                $this->reg_redirect( 'hotels', array( 'deleted' => 1 ) );
        }

        /* ── Hotel Rate Handlers ─────────────────────────────────────────── */

        public function handle_save_rate() {
                $this->verify_nonce( 'hr_save_rate' );
                Hotel_Rates_DB::save_rate( $_POST );
                wp_redirect( add_query_arg( array(
                        'page'      => 'hotel-rates',
                        'region_id' => (int) $_POST['region_id'],
                        'place_id'  => (int) $_POST['place_id'],
                        'hotel_id'  => (int) $_POST['hotel_id'],
                        'saved'     => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        public function handle_delete_rate() {
                $this->verify_nonce( 'hr_delete_rate' );
                Hotel_Rates_DB::delete_rate( (int) $_POST['id'] );
                wp_redirect( add_query_arg( array(
                        'page'      => 'hotel-rates',
                        'region_id' => (int) $_POST['region_id'],
                        'place_id'  => (int) $_POST['place_id'],
                        'hotel_id'  => (int) $_POST['hotel_id'],
                        'deleted'   => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        /* ── Tour Registration Handlers (Register page) ──────────────────── */

        public function handle_save_tour_reg() {
                $this->verify_nonce( 'hr_save_tour_reg' );
                $type = sanitize_text_field( $_POST['type'] ?? 'Day Tour' );
                Hotel_Rates_DB::register_tour( $_POST );
                $tab = $this->get_tour_registration_tab( $type );
                $this->reg_redirect( $tab, array( 'saved' => 1 ) );
        }

        public function handle_delete_tour_reg() {
                $this->verify_nonce( 'hr_delete_tour_reg' );
                $type = sanitize_text_field( $_POST['type'] ?? 'Day Tour' );
                Hotel_Rates_DB::delete_tour_registration( (int) $_POST['id'] );
                $tab = $this->get_tour_registration_tab( $type );
                $this->reg_redirect( $tab, array( 'deleted' => 1 ) );
        }

        /* ── Tour Rate Single Save / Delete (Tour Rates modal) ──────────── */

        public function handle_save_tour_rate() {
                $this->verify_nonce( 'hr_save_tour_rate' );
                $data = wp_unslash( $_POST );
                Hotel_Rates_DB::save_single_tour_rate( $data );
                wp_redirect( add_query_arg( array(
                        'page'      => 'hotel-rates-tour',
                        'region_id' => (int) ( $data['region_id'] ?? 0 ),
                        'place_id'  => (int) ( $data['place_id']  ?? 0 ),
                        'tour_type' => sanitize_text_field( $data['type'] ?? 'Day Tour' ),
                        'saved'     => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        public function handle_delete_tour_rate() {
                $this->verify_nonce( 'hr_delete_tour_rate' );
                Hotel_Rates_DB::delete_tour_registration( (int) $_POST['id'] );
                wp_redirect( add_query_arg( array(
                        'page'      => 'hotel-rates-tour',
                        'region_id' => (int) ( $_POST['region_id'] ?? 0 ),
                        'place_id'  => (int) ( $_POST['place_id']  ?? 0 ),
                        'tour_type' => sanitize_text_field( $_POST['type'] ?? 'Day Tour' ),
                        'deleted'   => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        /* ── Tour Rates Batch Save (Tour Rates page) ─────────────────────── */

        public function handle_save_tour_rates() {
                $this->verify_nonce( 'hr_save_tour_rates' );
                $region_id = (int) ( $_POST['region_id'] ?? 0 );
                $place_id  = (int) ( $_POST['place_id']  ?? 0 );
                $tour_type = sanitize_text_field( $_POST['tour_type'] ?? 'Day Tour' );
                $rows      = isset( $_POST['rows'] ) && is_array( $_POST['rows'] ) ? $_POST['rows'] : array();

                Hotel_Rates_DB::save_tour_rates_batch( $region_id, $place_id, $tour_type, $rows );

                wp_redirect( add_query_arg( array(
                        'page'      => 'hotel-rates-tour',
                        'region_id' => $region_id,
                        'place_id'  => $place_id,
                        'tour_type' => $tour_type,
                        'saved'     => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        public function handle_save_package_deal() {
                $this->verify_nonce( 'hr_save_package_deal' );

                Hotel_Rates_DB::save_package_deal( $_POST );

                $this->reg_redirect( 'package-deals', array( 'saved' => 1 ) );
        }

        public function handle_delete_package_deal() {
                $this->verify_nonce( 'hr_delete_package_deal' );

                Hotel_Rates_DB::delete_package_deal( (int) ( $_POST['id'] ?? 0 ) );

                if ( ! empty( $_POST['from_tour_rates'] ) ) {
                        wp_redirect( add_query_arg( array(
                                'page'      => 'hotel-rates-tour',
                                'region_id' => (int) ( $_POST['region_id'] ?? 0 ),
                                'place_id'  => (int) ( $_POST['place_id'] ?? 0 ),
                                'tour_type' => 'Package Deal',
                                'deleted'   => 1,
                        ), admin_url( 'admin.php' ) ) );
                        exit;
                }

                $this->reg_redirect( 'package-deals', array( 'deleted' => 1 ) );
        }

        public function handle_save_motorbike_rental() {
                $this->verify_nonce( 'hr_save_motorbike_rental' );

                Hotel_Rates_DB::save_motorbike_rental( wp_unslash( $_POST ) );

                wp_redirect( add_query_arg( array(
                        'page'  => 'hotel-rates-motorbikes',
                        'saved' => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        public function handle_delete_motorbike_rental() {
                $this->verify_nonce( 'hr_delete_motorbike_rental' );

                Hotel_Rates_DB::delete_motorbike_rental( (int) ( $_POST['id'] ?? 0 ) );

                wp_redirect( add_query_arg( array(
                        'page'    => 'hotel-rates-motorbikes',
                        'deleted' => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }
        public function handle_save_boat_rental() {
                $this->verify_nonce( 'hr_save_boat_rental' );

                Hotel_Rates_DB::save_boat_rental( wp_unslash( $_POST ) );

                wp_redirect( add_query_arg( array(
                        'page'  => 'hotel-rates-boats',
                        'saved' => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        public function handle_delete_boat_rental() {
                $this->verify_nonce( 'hr_delete_boat_rental' );

                Hotel_Rates_DB::delete_boat_rental( (int) ( $_POST['id'] ?? 0 ) );

                wp_redirect( add_query_arg( array(
                        'page'    => 'hotel-rates-boats',
                        'deleted' => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        public function handle_save_self_drive_rental() {
                $this->verify_nonce( 'hr_save_self_drive_rental' );

                Hotel_Rates_DB::save_self_drive_rental( wp_unslash( $_POST ) );

                wp_redirect( add_query_arg( array(
                        'page'  => 'hotel-rates-self-drive',
                        'saved' => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        public function handle_delete_self_drive_rental() {
                $this->verify_nonce( 'hr_delete_self_drive_rental' );

                Hotel_Rates_DB::delete_self_drive_rental( (int) ( $_POST['id'] ?? 0 ) );

                wp_redirect( add_query_arg( array(
                        'page'    => 'hotel-rates-self-drive',
                        'deleted' => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        public function handle_save_port_transfer() {
                $this->verify_nonce( 'hr_save_port_transfer' );

                Hotel_Rates_DB::save_port_transfer( wp_unslash( $_POST ) );

                wp_redirect( add_query_arg( array(
                        'page'  => 'hotel-rates-port-transfers',
                        'saved' => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        public function handle_delete_port_transfer() {
                $this->verify_nonce( 'hr_delete_port_transfer' );

                Hotel_Rates_DB::delete_port_transfer( (int) ( $_POST['id'] ?? 0 ) );

                wp_redirect( add_query_arg( array(
                        'page'    => 'hotel-rates-port-transfers',
                        'deleted' => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        public function handle_save_destination_tour() {
                $this->verify_nonce( 'hr_save_destination_tour' );

                Hotel_Rates_DB::save_destination_tour( wp_unslash( $_POST ) );

                wp_redirect( add_query_arg( array(
                        'page'  => 'hotel-rates-destination-tours',
                        'saved' => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        public function handle_delete_destination_tour() {
                $this->verify_nonce( 'hr_delete_destination_tour' );

                Hotel_Rates_DB::delete_destination_tour( (int) ( $_POST['id'] ?? 0 ) );

                wp_redirect( add_query_arg( array(
                        'page'    => 'hotel-rates-destination-tours',
                        'deleted' => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        public function handle_save_pickup_dropoff_service() {
                $this->verify_nonce( 'hr_save_pickup_dropoff_service' );

                Hotel_Rates_DB::save_pickup_dropoff_service( wp_unslash( $_POST ) );

                wp_redirect( add_query_arg( array(
                        'page'  => 'hotel-rates-pickup-dropoff',
                        'saved' => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }

        public function handle_delete_pickup_dropoff_service() {
                $this->verify_nonce( 'hr_delete_pickup_dropoff_service' );

                Hotel_Rates_DB::delete_pickup_dropoff_service( (int) ( $_POST['id'] ?? 0 ) );

                wp_redirect( add_query_arg( array(
                        'page'    => 'hotel-rates-pickup-dropoff',
                        'deleted' => 1,
                ), admin_url( 'admin.php' ) ) );
                exit;
        }
}
