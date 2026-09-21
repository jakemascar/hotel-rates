<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Hotel_Rates_Booking {

        private $motorbike_default_booking_fields_removed = false;
        private $boat_default_booking_fields_removed = false;
        private $self_drive_default_booking_fields_removed = false;
        private $port_transfer_default_booking_fields_removed = false;
        private $destination_tour_default_booking_fields_removed = false;
        private $pickup_dropoff_default_booking_fields_removed = false;
        private $day_tour_default_booking_fields_removed = false;
        private $joiner_default_booking_fields_removed = false;

        public function __construct() {
                add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
                add_action( 'tripgo_booking_form', array( $this, 'maybe_remove_motorbike_default_booking_fields' ), 4 );
                add_action( 'tripgo_booking_form', array( $this, 'render_booking_field' ), 24 );
                add_action( 'tripgo_booking_form', array( $this, 'restore_motorbike_default_booking_fields' ), 26 );

                add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'prepare_motorbike_guestless_add_to_cart_request' ), 1, 3 );
                add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_motorbike_add_to_cart' ), 10, 3 );
                add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'prepare_boat_guestless_add_to_cart_request' ), 1, 3 );
                add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_boat_add_to_cart' ), 10, 3 );
                add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'prepare_self_drive_guestless_add_to_cart_request' ), 1, 3 );
                add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_self_drive_add_to_cart' ), 10, 3 );
                add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'prepare_port_transfer_guestless_add_to_cart_request' ), 1, 3 );
                add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_port_transfer_add_to_cart' ), 10, 3 );
                add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'prepare_destination_tour_guestless_add_to_cart_request' ), 1, 3 );
                add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_destination_tour_add_to_cart' ), 10, 3 );
                add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'prepare_pickup_dropoff_guestless_add_to_cart_request' ), 1, 3 );
                add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_pickup_dropoff_add_to_cart' ), 10, 3 );
                add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'prepare_day_tour_add_to_cart_request' ), 1, 3 );
                add_filter( 'ovabrw_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );
                add_filter( 'ovabrw_get_price_by_guests', array( $this, 'add_hotel_rate_to_total' ), 10, 5 );
                add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_port_transfer_convenience_fees' ), 20, 1 );
                add_filter( 'ovabrw_get_item_data', array( $this, 'add_item_data' ), 10, 2 );
                add_filter( 'woocommerce_get_item_data', array( $this, 'add_item_data' ), 10, 2 );
                add_filter( 'woocommerce_cart_item_name', array( $this, 'filter_destination_tour_cart_item_name' ), 20, 3 );
                add_filter( 'ovabrw_get_item_data', array( $this, 'normalize_arrival_time_item_data' ), 999, 2 );
                add_filter( 'woocommerce_get_item_data', array( $this, 'normalize_arrival_time_item_data' ), 999, 2 );
                add_filter( 'woocommerce_order_item_display_meta_key', array( $this, 'normalize_arrival_time_order_meta_key' ), 10, 3 );

                add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_meta' ), 20, 4 );
                add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'cleanup_order_item_meta' ), 999, 4 );
        }

        public function filter_destination_tour_cart_item_name( $product_name, $cart_item, $cart_item_key ) {
                if ( is_array( $cart_item ) && ! empty( $cart_item['hr_pickup_dropoff_service_id'] ) ) {
                        $pickup_dropoff_name = $this->get_pickup_dropoff_item_title( $cart_item );

                        if ( '' === $pickup_dropoff_name ) {
                                return $product_name;
                        }

                        if ( $this->is_destination_tour_checkout_remove_link_visible() ) {
                                $remove_url = function_exists( 'wc_get_cart_remove_url' ) ? wc_get_cart_remove_url( $cart_item_key ) : '';

                                if ( '' !== $remove_url ) {
                                        $product = isset( $cart_item['data'] ) && is_object( $cart_item['data'] ) ? $cart_item['data'] : null;

                                        return sprintf(
                                                '<span class="hr-pickup-dropoff-cart-item-name"><span class="hr-pickup-dropoff-cart-item-title">%1$s</span><a href="%2$s" class="remove hr-pickup-dropoff-remove" aria-label="%3$s" data-product_id="%4$s" data-cart_item_key="%5$s" data-product_sku="%6$s">&times;</a></span>',
                                                esc_html( $pickup_dropoff_name ),
                                                esc_url( $remove_url ),
                                                esc_attr( sprintf( __( 'Remove %s from cart', 'hotel-rates' ), $pickup_dropoff_name ) ),
                                                esc_attr( $cart_item['product_id'] ?? '' ),
                                                esc_attr( $cart_item_key ),
                                                esc_attr( $product && method_exists( $product, 'get_sku' ) ? $product->get_sku() : '' )
                                        );
                                }
                        }

                        return esc_html( $pickup_dropoff_name );
                }

                if ( ! is_array( $cart_item ) || empty( $cart_item['hr_destination_tour_id'] ) ) {
                        return $product_name;
                }

                $destination_tour_name = $this->get_destination_tour_item_title( $cart_item );

                if ( '' === $destination_tour_name ) {
                        return $product_name;
                }

                if ( $this->is_destination_tour_checkout_remove_link_visible() ) {
                        $remove_url = function_exists( 'wc_get_cart_remove_url' ) ? wc_get_cart_remove_url( $cart_item_key ) : '';

                        if ( '' !== $remove_url ) {
                                $product = isset( $cart_item['data'] ) && is_object( $cart_item['data'] ) ? $cart_item['data'] : null;

                                return sprintf(
                                        '<span class="hr-destination-tour-cart-item-name"><span class="hr-destination-tour-cart-item-title">%1$s</span><a href="%2$s" class="remove hr-destination-tour-remove" aria-label="%3$s" data-product_id="%4$s" data-cart_item_key="%5$s" data-product_sku="%6$s">&times;</a></span>',
                                        esc_html( $destination_tour_name ),
                                        esc_url( $remove_url ),
                                        esc_attr( sprintf( __( 'Remove %s from cart', 'hotel-rates' ), $destination_tour_name ) ),
                                        esc_attr( $cart_item['product_id'] ?? '' ),
                                        esc_attr( $cart_item_key ),
                                        esc_attr( $product && method_exists( $product, 'get_sku' ) ? $product->get_sku() : '' )
                                );
                        }
                }

                return esc_html( $destination_tour_name );
        }

        private function is_destination_tour_checkout_remove_link_visible() {
                if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
                        return false;
                }

                return ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' );
        }

        public function maybe_remove_motorbike_default_booking_fields( $args ) {
                $product_id = $this->get_booking_form_product_id( $args );
                $has_motorbike_rental = $product_id && $this->get_linked_product_motorbike_rental_id( $product_id );
                $has_boat_rental = $product_id && $this->get_linked_product_boat_rental_id( $product_id );
                $has_self_drive_rental = $product_id && $this->get_linked_product_self_drive_rental_id( $product_id );
                $has_port_transfer = $product_id && $this->get_linked_product_port_transfer_id( $product_id );
                $has_destination_tour = $product_id && $this->get_linked_product_destination_tour_id( $product_id );
                $has_pickup_dropoff = $product_id && $this->get_linked_product_pickup_dropoff_service_id( $product_id );
                $has_day_tour = $product_id && $this->get_linked_product_day_tour_rate_id( $product_id );
                $has_joiner = $product_id && $this->get_linked_product_joiner_tour_rate_id( $product_id );
                $has_full_custom_booking_form = $has_motorbike_rental || $has_boat_rental || $has_self_drive_rental || $has_port_transfer || $has_destination_tour || $has_pickup_dropoff;
                $has_compact_tour_booking_form = $has_day_tour || $has_joiner;

                if ( ! $has_full_custom_booking_form && ! $has_compact_tour_booking_form ) {
                        return;
                }

                if ( $has_destination_tour || $has_pickup_dropoff ) {
                        remove_action( 'tripgo_booking_form', 'tripgo_booking_form_dates', 5 );
                }

                if ( $has_full_custom_booking_form ) {
                        remove_action( 'tripgo_booking_form', 'tripgo_booking_form_guests', 5 );
                        remove_action( 'tripgo_booking_form', 'tripgo_booking_form_extra_fields', 10 );
                        remove_action( 'tripgo_booking_form', 'tripgo_booking_form_quantity', 10 );
                        remove_action( 'tripgo_booking_form', 'tripgo_booking_form_resources', 15 );
                        remove_action( 'tripgo_booking_form', 'tripgo_booking_form_services', 15 );
                        remove_action( 'tripgo_booking_form', 'tripgo_booking_form_deposit', 20 );
                }

                if ( $has_compact_tour_booking_form ) {
                        remove_action( 'tripgo_booking_form', 'tripgo_booking_form_extra_fields', 10 );
                        remove_action( 'tripgo_booking_form', 'tripgo_booking_form_resources', 15 );
                        remove_action( 'tripgo_booking_form', 'tripgo_booking_form_services', 15 );
                        remove_action( 'tripgo_booking_form', 'tripgo_booking_form_deposit', 20 );
                }

                $this->motorbike_default_booking_fields_removed = (bool) $has_motorbike_rental;
                $this->boat_default_booking_fields_removed = (bool) $has_boat_rental;
                $this->self_drive_default_booking_fields_removed = (bool) $has_self_drive_rental;
                $this->port_transfer_default_booking_fields_removed = (bool) $has_port_transfer;
                $this->destination_tour_default_booking_fields_removed = (bool) $has_destination_tour;
                $this->pickup_dropoff_default_booking_fields_removed = (bool) $has_pickup_dropoff;
                $this->day_tour_default_booking_fields_removed = (bool) $has_day_tour;
                $this->joiner_default_booking_fields_removed = (bool) $has_joiner;
        }

        public function restore_motorbike_default_booking_fields() {
                $full_custom_booking_fields_removed = $this->motorbike_default_booking_fields_removed || $this->boat_default_booking_fields_removed || $this->self_drive_default_booking_fields_removed || $this->port_transfer_default_booking_fields_removed || $this->destination_tour_default_booking_fields_removed || $this->pickup_dropoff_default_booking_fields_removed;
                $compact_tour_booking_fields_removed = $this->day_tour_default_booking_fields_removed || $this->joiner_default_booking_fields_removed;

                if ( ! $full_custom_booking_fields_removed && ! $compact_tour_booking_fields_removed ) {
                        return;
                }

                if ( $full_custom_booking_fields_removed ) {
                        add_action( 'tripgo_booking_form', 'tripgo_booking_form_guests', 5 );
                        add_action( 'tripgo_booking_form', 'tripgo_booking_form_extra_fields', 10 );
                        add_action( 'tripgo_booking_form', 'tripgo_booking_form_quantity', 10 );
                        add_action( 'tripgo_booking_form', 'tripgo_booking_form_resources', 15 );
                        add_action( 'tripgo_booking_form', 'tripgo_booking_form_services', 15 );
                        add_action( 'tripgo_booking_form', 'tripgo_booking_form_deposit', 20 );
                }

                if ( $compact_tour_booking_fields_removed && ! $full_custom_booking_fields_removed ) {
                        add_action( 'tripgo_booking_form', 'tripgo_booking_form_extra_fields', 10 );
                        add_action( 'tripgo_booking_form', 'tripgo_booking_form_resources', 15 );
                        add_action( 'tripgo_booking_form', 'tripgo_booking_form_services', 15 );
                        add_action( 'tripgo_booking_form', 'tripgo_booking_form_deposit', 20 );
                }

                if ( $this->destination_tour_default_booking_fields_removed || $this->pickup_dropoff_default_booking_fields_removed ) {
                        add_action( 'tripgo_booking_form', 'tripgo_booking_form_dates', 5 );
                }

                $this->motorbike_default_booking_fields_removed = false;
                $this->boat_default_booking_fields_removed = false;
                $this->self_drive_default_booking_fields_removed = false;
                $this->port_transfer_default_booking_fields_removed = false;
                $this->destination_tour_default_booking_fields_removed = false;
                $this->pickup_dropoff_default_booking_fields_removed = false;
                $this->day_tour_default_booking_fields_removed = false;
                $this->joiner_default_booking_fields_removed = false;
        }

        private function get_booking_form_product_id( $args ) {
                if ( function_exists( 'tripgo_get_meta_data' ) ) {
                        return (int) tripgo_get_meta_data( 'id', $args, get_the_ID() );
                }

                if ( is_array( $args ) && ! empty( $args['id'] ) ) {
                        return (int) $args['id'];
                }

                return (int) get_the_ID();
        }

        private function render_tour_deposit_field( $args ) {
                if ( function_exists( 'tripgo_booking_form_deposit' ) ) {
                        tripgo_booking_form_deposit( $args );
                }
        }

        public function enqueue_assets() {
                $is_product_page = function_exists( 'is_product' ) && is_product();
                $is_cart_page = function_exists( 'is_cart' ) && is_cart();
                $is_checkout_page = function_exists( 'is_checkout' ) && is_checkout();
                $is_order_page = function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' );

                if ( is_admin() || ( ! $is_product_page && ! $is_cart_page && ! $is_checkout_page && ! $is_order_page ) ) {
                        return;
                }

                wp_enqueue_style(
                        'hr-booking',
                        HR_PLUGIN_URL . 'public/css/booking-form.css',
                        array(),
                        HR_VERSION
                );

                if ( ! $is_product_page ) {
                        return;
                }

                wp_enqueue_script(
                        'hr-booking',
                        HR_PLUGIN_URL . 'public/js/booking-form.js',
                        array( 'jquery' ),
                        HR_VERSION,
                        true
                );

                $display_currency = Hotel_Rates_Currency::get_frontend_display_data();

                wp_localize_script( 'hr-booking', 'hrBooking', array(
                        'currencySymbol'             => $display_currency['symbol'],
                        'displayConversionAvailable' => $display_currency['conversionAvailable'],
                        'displayCurrency'            => $display_currency['currency'],
                        'displayCurrencySymbol'      => $display_currency['symbol'],
                        'displayRate'                => $display_currency['rate'],
                        'displayFee'                 => $display_currency['fee'],
                        'displayFormatting'          => $display_currency['formatting'],
                        'locale'            => str_replace( '_', '-', get_locale() ),
                        'version'           => HR_VERSION,
                        'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
                        'selectPlaceholder' => __( 'Select a hotel package', 'hotel-rates' ),
                        'fieldLabel'        => __( 'Hotel Package', 'hotel-rates' ),
                        'priceLabel'        => __( 'Adds %s to this booking.', 'hotel-rates' ),
                        'chooseGuests'      => __( 'Choose your guest count first to see available hotel packages.', 'hotel-rates' ),
                        'noRates'           => __( 'No hotel package is available for the selected guest count.', 'hotel-rates' ),
                        'breakfastLabel'    => __( 'Breakfast included', 'hotel-rates' ),
                        'packageChooseGuests' => __( 'Choose your guest count first to configure this package.', 'hotel-rates' ),
                        'packageTotalLabel'   => __( 'Package total: %s', 'hotel-rates' ),
                        'packageDepositAmountLabel' => __( 'Pay only %s deposit', 'hotel-rates' ),
                        'packageInvalidLabel' => __( 'One or more selected package items has no rate for the chosen guest count.', 'hotel-rates' ),
                        'packageDateRangeLabel' => __( 'Package dates: %1$s to %2$s', 'hotel-rates' ),
                        'packageDateRangePlaceholder' => __( 'Select a start date to see the package date range.', 'hotel-rates' ),
                        'tourChooseGuests'    => __( 'Choose your guest count first to calculate this tour.', 'hotel-rates' ),
                        'tourTotalLabel'      => __( 'Tour total: %s', 'hotel-rates' ),
                        'tourDepositAmountLabel' => __( 'Pay only %s deposit', 'hotel-rates' ),
                        'tourInvalidLabel'    => __( 'This tour has no rate for the chosen guest count.', 'hotel-rates' ),
                        'tourAddonsLabel'     => __( 'Optional add-ons:', 'hotel-rates' ),
                        'tourChildrenLabel'   => __( 'Children', 'hotel-rates' ),
                        'tourInfantLabel'     => __( '0-2 Infant', 'hotel-rates' ),
                        'airportTransferChooseGuests' => __( 'Select a From/To place to calculate this airport transfer.', 'hotel-rates' ),
                        'airportTransferChooseRoute'  => __( 'Select a From/To place to calculate this airport transfer.', 'hotel-rates' ),
                        'airportTransferChooseDirection' => __( 'Select a transfer direction.', 'hotel-rates' ),
                        'airportTransferDirectionPlaceholder' => __( 'Select transfer direction:', 'hotel-rates' ),
                        'airportTransferDirectionAirportToPlace' => __( 'Airport to From/To Place', 'hotel-rates' ),
                        'airportTransferDirectionPlaceToAirport' => __( 'From/To Place to Airport', 'hotel-rates' ),
                        'airportTransferTotalLabel'   => __( 'Airport transfer total: %s', 'hotel-rates' ),
                        'airportTransferInvalidLabel' => __( 'This airport transfer route has no rate.', 'hotel-rates' ),
                        'arrivalTimeLabel'            => __( 'Arrival Time:', 'hotel-rates' ),
                        'motorbikeRentalDateLabel'    => __( 'Rental Date', 'hotel-rates' ),
                        'motorbikeModelsLabel'        => __( 'Select Motorbike Model & Quantity', 'hotel-rates' ),
                        'motorbikeStartTimePlaceholder' => __( 'Pick-up/Delivery Time', 'hotel-rates' ),
                        'motorbikeDaysPlaceholder'    => __( 'No. of Days', 'hotel-rates' ),
                        'motorbikeHandoverLabel'      => __( 'Motorbike Handover Options:', 'hotel-rates' ),
                        'motorbikePickupLabel'        => __( 'Pick-up', 'hotel-rates' ),
                        'motorbikeDeliveryLabel'      => __( 'Deliver to Address (%s additional charge)', 'hotel-rates' ),
                        'motorbikeAddressPlaceholder' => __( 'Delivery Address', 'hotel-rates' ),
                        'motorbikeChooseModel'        => __( 'Choose at least one motorbike quantity.', 'hotel-rates' ),
                        'motorbikeChooseStartTime'    => __( 'Select a pick-up/delivery time.', 'hotel-rates' ),
                        'motorbikeChooseDays'         => __( 'Select the number of rental days.', 'hotel-rates' ),
                        'motorbikeTotalLabel'         => __( 'Motorbike rental total: %s', 'hotel-rates' ),
                        'motorbikeInvalidLabel'       => __( 'This motorbike rental selection is unavailable.', 'hotel-rates' ),
                        'boatRentalDateLabel'         => __( 'Rental Date', 'hotel-rates' ),
                        'boatPassengersLabel'         => __( 'No. of Passengers', 'hotel-rates' ),
                        'boatRatesLabel'              => __( 'Boat Rate and Capacity', 'hotel-rates' ),
                        'boatRateCapacityPlaceholder' => __( 'Boat Rate and Capacity', 'hotel-rates' ),
                        'boatBoardingTimePlaceholder' => __( 'Choose Boarding Time', 'hotel-rates' ),
                        'boatChooseRentalDate'        => __( 'Select a rental date.', 'hotel-rates' ),
                        'boatChoosePassengers'        => __( 'Enter the number of passengers.', 'hotel-rates' ),
                        'boatChooseRate'              => __( 'Select a boat rate and capacity.', 'hotel-rates' ),
                        'boatChooseBoardingTime'      => __( 'Select a boarding time.', 'hotel-rates' ),
                        'boatInvalidLabel'            => __( 'This boat rental selection is unavailable.', 'hotel-rates' ),
                        'boatTotalLabel'              => __( 'Boat rental total: %s', 'hotel-rates' ),
                        'selfDriveRentalDateLabel'    => __( 'Rental Date', 'hotel-rates' ),
                        'selfDriveReturnDateLabel'    => __( 'Return Date', 'hotel-rates' ),
                        'selfDriveReturnDatePlaceholder' => __( 'DD-MM-YYYY', 'hotel-rates' ),
                        'selfDriveGuestLabel'         => __( 'No. of Guest', 'hotel-rates' ),
                        'selfDriveHotelNamePlaceholder' => __( 'Hotel Name', 'hotel-rates' ),
                        'selfDriveVehiclesLabel'      => __( 'Select Vehicle Type & Quantity', 'hotel-rates' ),
                        'selfDriveVehiclePlaceholder' => __( 'Choose Vehicle Type:', 'hotel-rates' ),
                        'selfDriveStartTimePlaceholder' => __( 'Pick-up/Delivery Time', 'hotel-rates' ),
                        'selfDriveReturnTimePlaceholder' => __( 'Return Time', 'hotel-rates' ),
                        'selfDriveHandoverLabel'      => __( 'Vehicle Handover Options:', 'hotel-rates' ),
                        'selfDrivePickupLabel'        => __( 'Office/Garage Pick-up', 'hotel-rates' ),
                        'selfDriveDeliveryLabel'      => __( 'Deliver to Address (%s additional charge)', 'hotel-rates' ),
                        'selfDriveAddressPlaceholder' => __( 'Delivery Address', 'hotel-rates' ),
                        'selfDriveChooseVehicle'      => __( 'Choose at least one vehicle quantity.', 'hotel-rates' ),
                        'selfDriveChooseRentalDate'   => __( 'Select a rental date.', 'hotel-rates' ),
                        'selfDriveChooseReturnDate'   => __( 'Select a valid return date.', 'hotel-rates' ),
                        'selfDriveChooseGuests'       => __( 'Enter the number of guests.', 'hotel-rates' ),
                        'selfDriveChooseHotelName'    => __( 'Enter the hotel name.', 'hotel-rates' ),
                        'selfDriveChooseStartTime'    => __( 'Select a pick-up/delivery time.', 'hotel-rates' ),
                        'selfDriveChooseReturnTime'   => __( 'Select a return time.', 'hotel-rates' ),
                        'selfDriveTotalLabel'         => __( 'Self-drive rental total: %s', 'hotel-rates' ),
                        'selfDriveInvalidLabel'       => __( 'This self-drive rental selection is unavailable.', 'hotel-rates' ),
                        'destinationTourRentalDateLabel' => __( 'Rental Date', 'hotel-rates' ),
                        'destinationTourGuestsLabel'   => __( 'No. of Guest', 'hotel-rates' ),
                        'destinationTourPickupInfoLabel' => __( 'Pick-up Information', 'hotel-rates' ),
                        'destinationTourHotelPlaceholder' => __( 'Hotel/Resort Name', 'hotel-rates' ),
                        'destinationTourPickupTimePlaceholder' => __( 'Pick-up Time', 'hotel-rates' ),
                        'destinationTourDestinationPlaceholder' => __( 'Tourist Destinations:', 'hotel-rates' ),
                        'destinationTourAddonsLabel'  => __( 'Add-ons:', 'hotel-rates' ),
                        'destinationTourAddonPlaceholder' => __( 'For Pick-up Outside Dumaguete City', 'hotel-rates' ),
                        'destinationTourChooseRentalDate' => __( 'Select a rental date.', 'hotel-rates' ),
                        'destinationTourChooseGuests' => __( 'Enter the number of guests.', 'hotel-rates' ),
                        'destinationTourChooseHotelName' => __( 'Enter the hotel or resort name.', 'hotel-rates' ),
                        'destinationTourChoosePickupTime' => __( 'Select a pick-up time.', 'hotel-rates' ),
                        'destinationTourChooseDestination' => __( 'Select a tourist destination.', 'hotel-rates' ),
                        'destinationTourTotalLabel'   => __( 'Destination tour total: %s', 'hotel-rates' ),
                        'destinationTourInvalidLabel' => __( 'This destination tour selection is unavailable.', 'hotel-rates' ),
                        'pickupDropoffRentalDateLabel' => __( 'Rental Date', 'hotel-rates' ),
                        'pickupDropoffFromLabel'      => __( 'From:', 'hotel-rates' ),
                        'pickupDropoffToLabel'        => __( 'Drop-off To:', 'hotel-rates' ),
                        'pickupDropoffFromPlaceholder' => __( 'From', 'hotel-rates' ),
                        'pickupDropoffToPlaceholder'  => __( 'Drop-off To', 'hotel-rates' ),
                        'pickupDropoffTimePlaceholder' => __( 'Choose Pick-up Time', 'hotel-rates' ),
                        'pickupDropoffHotelPlaceholder' => __( 'Hotel/Resort Name', 'hotel-rates' ),
                        'pickupDropoffPassengersLabel' => __( 'No. of Passengers', 'hotel-rates' ),
                        'pickupDropoffAddonsLabel'    => __( 'ADD-ONS', 'hotel-rates' ),
                        'pickupDropoffChooseRentalDate' => __( 'Select a rental date.', 'hotel-rates' ),
                        'pickupDropoffChoosePassengers' => __( 'Enter the number of passengers.', 'hotel-rates' ),
                        'pickupDropoffChooseRoute'    => __( 'Select From and Drop-off To locations.', 'hotel-rates' ),
                        'pickupDropoffChoosePickupTime' => __( 'Select a pick-up time.', 'hotel-rates' ),
                        'pickupDropoffChooseHotelName' => __( 'Enter the hotel or resort name.', 'hotel-rates' ),
                        'pickupDropoffTotalLabel'     => __( 'Pick-up and drop-off total: %s', 'hotel-rates' ),
                        'pickupDropoffInvalidLabel'   => __( 'This pick-up and drop-off selection is unavailable.', 'hotel-rates' ),
                        'portTransferDateLabel'       => __( 'Arrival Date', 'hotel-rates' ),
                        'portTransferPassengersLabel' => __( 'No. of Passengers', 'hotel-rates' ),
                        'portTransferTypePlaceholder' => __( 'Transfer Type', 'hotel-rates' ),
                        'portTransferOneWayLabel'     => __( 'One-way Transfer', 'hotel-rates' ),
                        'portTransferRoundtripLabel'  => __( 'Round-trip Transfer', 'hotel-rates' ),
                        'portTransferFromLabel'       => __( 'From:', 'hotel-rates' ),
                        'portTransferToLabel'         => __( 'To:', 'hotel-rates' ),
                        'portTransferFromPlaceholder' => __( 'From', 'hotel-rates' ),
                        'portTransferToPlaceholder'   => __( 'To', 'hotel-rates' ),
                        'portTransferRoutePlaceholder' => __( 'From and To', 'hotel-rates' ),
                        'portTransferTimePlaceholder' => __( 'Time', 'hotel-rates' ),
                        'portTransferFlightPlaceholder' => __( 'Flight #', 'hotel-rates' ),
                        'portTransferHotelPlaceholder' => __( 'Hotel Name', 'hotel-rates' ),
                        'portTransferAddonsLabel'     => __( 'ADD-ONS', 'hotel-rates' ),
                        'portTransferDropoffPlaceholder' => __( 'Drop-off To', 'hotel-rates' ),
                        'portTransferReturnDateLabel' => __( 'Return Date', 'hotel-rates' ),
                        'portTransferReturnTimePlaceholder' => __( 'Pick-up Time', 'hotel-rates' ),
                        'portTransferReturnDropoffLabel' => __( 'Drop-off To:', 'hotel-rates' ),
                        'portTransferReturnDropoffPlaceholder' => __( 'Drop-off To', 'hotel-rates' ),
                        'portTransferChooseDate'      => __( 'Select an arrival date.', 'hotel-rates' ),
                        'portTransferChoosePassengers' => __( 'Enter the number of passengers.', 'hotel-rates' ),
                        'portTransferChooseType'      => __( 'Select one way or roundtrip.', 'hotel-rates' ),
                        'portTransferChooseRoute'     => __( 'Select From and To locations.', 'hotel-rates' ),
                        'portTransferChooseTime'      => __( 'Enter the transfer time.', 'hotel-rates' ),
                        'portTransferChooseHotelName' => __( 'Enter the hotel name.', 'hotel-rates' ),
                        'portTransferChooseDropoff'   => __( 'Select a drop-off location.', 'hotel-rates' ),
                        'portTransferChooseReturnDate' => __( 'Select a return date.', 'hotel-rates' ),
                        'portTransferChooseReturnTime' => __( 'Select a return time.', 'hotel-rates' ),
                        'portTransferChooseReturnDropoff' => __( 'Select a return drop-off location.', 'hotel-rates' ),
                        'portTransferTotalLabel'      => __( 'Port transfer total: %s', 'hotel-rates' ),
                        'portTransferInvalidLabel'    => __( 'This port transfer selection is unavailable.', 'hotel-rates' ),
                ) );
        }

        public function render_booking_field( $args ) {
                $product_id = isset( $args['id'] ) ? (int) $args['id'] : get_the_ID();

                if ( ! $product_id ) {
                        return;
                }

                $package_deal_id = (int) get_post_meta( $product_id, '_hr_package_deal_id', true );
                $package_data    = $package_deal_id ? $this->get_package_booking_data( $package_deal_id ) : null;

                if ( ! empty( $package_data ) ) {
                        $package_json = wp_json_encode( $package_data );
                        ?>
                        <div class="rental_item hr-package-booking-field" data-booking-module="package-deal" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-package='<?php echo esc_attr( $package_json ); ?>'>
                                <p class="hr-booking-rate-help">
                                        <?php
                                        printf(
                                                esc_html__( '%1$d days / %2$d nights package. Choose your hotels and daily activities below.', 'hotel-rates' ),
                                                (int) $package_data['days'],
                                                (int) $package_data['nights']
                                        );
                                        ?>
                                </p>
                                <p class="hr-package-booking-date-range" aria-live="polite"></p>
                                <div class="hr-package-booking-selectors"></div>
                                <div class="hr-package-booking-breakdown" hidden></div>
                                <p class="hr-package-booking-total"></p>

                                <input type="hidden" name="hr_package_deal_id" value="<?php echo esc_attr( $package_data['packageId'] ); ?>">
                                <input type="hidden" name="hr_package_selection_payload" value="">
                                <input type="hidden" name="hr_package_total" value="">
                        </div>
                        <?php
                        return;
                }

                $tour_rate_id = $this->get_linked_product_tour_rate_id( $product_id );
                $tour_data    = $tour_rate_id ? $this->get_tour_booking_data( $tour_rate_id ) : null;

                if ( ! empty( $tour_data ) ) {
                        $tour_json = wp_json_encode( $tour_data );

                        if ( 'Airport Transfer' === $tour_data['type'] ) {
                                ?>
                                <div class="rental_item hr-airport-transfer-booking-field" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-airport-transfer='<?php echo esc_attr( $tour_json ); ?>'>
                                        <p class="hr-booking-rate-help">
                                                <?php
                                                printf(
                                                        esc_html__( 'Airport transfer in %s.', 'hotel-rates' ),
                                                        esc_html( $tour_data['placeName'] )
                                                );
                                                ?>
                                        </p>
                                        <div class="hr-airport-transfer-route-selectors"></div>
                                        <p class="hr-airport-transfer-booking-total"></p>

                                        <input type="hidden" name="hr_airport_transfer_rate_id" value="<?php echo esc_attr( $tour_data['tourRateId'] ); ?>">
                                        <input type="hidden" name="hr_airport_transfer_route_index" value="">
                                        <input type="hidden" name="hr_airport_transfer_direction" value="">
                                        <input type="hidden" name="hr_airport_transfer_pax" value="">
                                        <input type="hidden" name="hr_airport_transfer_unit_price" value="">
                                        <input type="hidden" name="hr_airport_transfer_total" value="">
                                </div>
                                <?php
                                return;
                        }
                        $tour_booking_fields = ! empty( $tour_data['bookingFields'] ) && is_array( $tour_data['bookingFields'] ) ? $tour_data['bookingFields'] : Hotel_Rates_DB::normalize_tour_booking_fields( array() );
                        $tour_hotel_flight_field = $tour_booking_fields['hotel_flight_no'] ?? array();
                        $tour_pickup_time_field = $tour_booking_fields['pickup_time'] ?? array();
                        $show_tour_hotel_flight_field = ! empty( $tour_hotel_flight_field['enabled'] );
                        $show_tour_pickup_time_field = ! empty( $tour_pickup_time_field['enabled'] );
                        $tour_hotel_flight_label = ! empty( $tour_hotel_flight_field['label'] ) ? (string) $tour_hotel_flight_field['label'] : __( 'Hotel / Flight No.', 'hotel-rates' );
                        $tour_pickup_time_label = ! empty( $tour_pickup_time_field['label'] ) ? (string) $tour_pickup_time_field['label'] : __( 'Pick-up Time', 'hotel-rates' );

                        if ( 'Day Tour' === (string) $tour_data['type'] ) {
                                $tour_hotel_flight_label = $this->normalize_day_tour_display_label( $tour_hotel_flight_label );
                                $tour_pickup_time_label = $this->normalize_day_tour_display_label( $tour_pickup_time_label );
                        }

                        $tour_module = 'Day Tour' === (string) $tour_data['type'] ? 'day-tour' : sanitize_title( (string) $tour_data['type'] );
                        $tour_module_class = 'day-tour' === $tour_module ? ' hr-day-tour-booking-field' : ' hr-' . sanitize_html_class( $tour_module ) . '-booking-field';
                        ?>
                        <div class="rental_item hr-tour-booking-field<?php echo esc_attr( $tour_module_class ); ?>" data-booking-module="<?php echo esc_attr( $tour_module ); ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-tour='<?php echo esc_attr( $tour_json ); ?>'>
                                <?php if ( $show_tour_hotel_flight_field || $show_tour_pickup_time_field ) : ?>
                                <div class="hr-tour-pickup-fields">
                                        <?php if ( $show_tour_hotel_flight_field ) : ?>
                                        <div class="hr-tour-field-group">
                                                <input
                                                        type="text"
                                                        class="hr-tour-hotel-flight-input"
                                                        name="hr_tour_hotel_flight_no"
                                                        placeholder="<?php echo esc_attr( $tour_hotel_flight_label ); ?>"
                                                        aria-label="<?php echo esc_attr( $tour_hotel_flight_label ); ?>"
                                                        autocomplete="off"
                                                >
                                        </div>
                                        <?php endif; ?>
                                        <?php if ( $show_tour_pickup_time_field ) : ?>
                                        <div class="hr-tour-field-group">
                                                <input
                                                        type="text"
                                                        class="hr-tour-pickup-time-input"
                                                        name="hr_tour_pickup_time"
                                                        placeholder="<?php echo esc_attr( $tour_pickup_time_label ); ?>"
                                                        aria-label="<?php echo esc_attr( $tour_pickup_time_label ); ?>"
                                                        autocomplete="off"
                                                >
                                        </div>
                                        <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <div class="hr-tour-addon-selectors" hidden></div>
                                <?php $this->render_tour_deposit_field( $args ); ?>
                                <p class="hr-tour-booking-total"></p>

                                <input type="hidden" name="hr_tour_rate_id" value="<?php echo esc_attr( $tour_data['tourRateId'] ); ?>">
                                <input type="hidden" name="hr_tour_pax" value="">
                                <input type="hidden" name="hr_tour_children" value="">
                                <input type="hidden" name="hr_tour_children_unit_price" value="">
                                <input type="hidden" name="hr_tour_children_total" value="">
                                <input type="hidden" name="hr_tour_infants" value="">
                                <input type="hidden" name="hr_tour_unit_price" value="">
                                <input type="hidden" name="hr_tour_addons_payload" value="">
                                <input type="hidden" name="hr_tour_total" value="">
                        </div>
                        <?php
                        return;
                }

                $motorbike_rental_id = $this->get_linked_product_motorbike_rental_id( $product_id );
                $motorbike_data      = $motorbike_rental_id ? $this->get_motorbike_booking_data( $motorbike_rental_id ) : null;

                if ( ! empty( $motorbike_data ) ) {
                        $motorbike_json = wp_json_encode( $motorbike_data );
                        ?>
                        <div class="rental_item hr-motorbike-booking-field" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-motorbike='<?php echo esc_attr( $motorbike_json ); ?>'>
                                <div class="hr-motorbike-model-selectors"></div>
                                <p class="hr-motorbike-booking-total"></p>

                                <input type="hidden" name="hr_motorbike_rental_id" value="<?php echo esc_attr( $motorbike_data['rentalId'] ); ?>">
                                <input type="hidden" name="hr_motorbike_selection_payload" value="">
                                <input type="hidden" name="hr_motorbike_days" value="">
                                <input type="hidden" name="hr_motorbike_start_time" value="">
                                <input type="hidden" name="hr_motorbike_handover_option" value="">
                                <input type="hidden" name="hr_motorbike_delivery_address" value="">
                                <input type="hidden" name="hr_motorbike_total" value="">
                        </div>
                        <?php
                        return;
                }

                $boat_rental_id = $this->get_linked_product_boat_rental_id( $product_id );
                $boat_data      = $boat_rental_id ? $this->get_boat_booking_data( $boat_rental_id ) : null;

                if ( ! empty( $boat_data ) ) {
                        $boat_json = wp_json_encode( $boat_data );
                        ?>
                        <div class="rental_item hr-boat-booking-field" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-boat='<?php echo esc_attr( $boat_json ); ?>'>
                                <div class="hr-boat-selectors"></div>
                                <p class="hr-boat-booking-total"></p>

                                <input type="hidden" name="hr_boat_rental_id" value="<?php echo esc_attr( $boat_data['rentalId'] ); ?>">
                                <input type="hidden" name="hr_boat_selection_payload" value="">
                                <input type="hidden" name="hr_boat_rental_date" value="">
                                <input type="hidden" name="hr_boat_passengers" value="">
                                <input type="hidden" name="hr_boat_rate_index" value="">
                                <input type="hidden" name="hr_boat_rate_label" value="">
                                <input type="hidden" name="hr_boat_boarding_time" value="">
                                <input type="hidden" name="hr_boat_base_total" value="">
                                <input type="hidden" name="hr_boat_upgrade_fee_selected" value="">
                                <input type="hidden" name="hr_boat_upgrade_fee_label" value="">
                                <input type="hidden" name="hr_boat_upgrade_fee_amount" value="">
                                <input type="hidden" name="hr_boat_total" value="">
                        </div>
                        <?php
                        return;
                }

                $self_drive_rental_id = $this->get_linked_product_self_drive_rental_id( $product_id );
                $self_drive_data      = $self_drive_rental_id ? $this->get_self_drive_booking_data( $self_drive_rental_id ) : null;

                if ( ! empty( $self_drive_data ) ) {
                        $self_drive_json = wp_json_encode( $self_drive_data );
                        $self_drive_return_date_id = 'hr-self-drive-return-date-' . $product_id;
                        $self_drive_guests_id      = 'hr-self-drive-guests-' . $product_id;
                        ?>
                        <div class="rental_item hr-self-drive-booking-field" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-self-drive='<?php echo esc_attr( $self_drive_json ); ?>'>
                                <div class="hr-self-drive-trip-details" data-hr-self-drive-trip-details="1">
                                        <div class="rental_item hr-self-drive-return-date-field">
                                                <label for="<?php echo esc_attr( $self_drive_return_date_id ); ?>">
                                                        <?php esc_html_e( 'Return Date', 'hotel-rates' ); ?>
                                                </label>
                                                <input
                                                        type="text"
                                                        id="<?php echo esc_attr( $self_drive_return_date_id ); ?>"
                                                        class="hr-self-drive-return-date-input checkout-date hr-uniform-booking-datepicker ovabrw-input-required"
                                                        name="hr_self_drive_return_date_display"
                                                        placeholder="<?php esc_attr_e( 'DD-MM-YYYY', 'hotel-rates' ); ?>"
                                                        required
                                                        aria-required="true"
                                                        autocomplete="off"
                                                >
                                        </div>
                                        <div class="hr-self-drive-guest-row">
                                                <label class="hr-self-drive-guest-label" for="<?php echo esc_attr( $self_drive_guests_id ); ?>">
                                                        <?php esc_html_e( 'No. of Guest', 'hotel-rates' ); ?>
                                                </label>
                                                <button type="button" class="hr-self-drive-qty-btn hr-self-drive-guest-minus" aria-label="<?php esc_attr_e( 'Decrease guests', 'hotel-rates' ); ?>">-</button>
                                                <input
                                                        type="number"
                                                        id="<?php echo esc_attr( $self_drive_guests_id ); ?>"
                                                        class="hr-self-drive-guest-count ovabrw-input-required"
                                                        name="hr_self_drive_guests_display"
                                                        value="1"
                                                        min="1"
                                                        step="1"
                                                        inputmode="numeric"
                                                        required
                                                        aria-required="true"
                                                >
                                                <button type="button" class="hr-self-drive-qty-btn hr-self-drive-guest-plus" aria-label="<?php esc_attr_e( 'Increase guests', 'hotel-rates' ); ?>">+</button>
                                        </div>
                                        <div class="hr-self-drive-field-group hr-self-drive-hotel-name-field">
                                                <input
                                                        type="text"
                                                        class="hr-self-drive-hotel-name-input ovabrw-input-required"
                                                        name="hr_self_drive_hotel_name_display"
                                                        placeholder="<?php esc_attr_e( 'Hotel Name', 'hotel-rates' ); ?>"
                                                        required
                                                        aria-required="true"
                                                        autocomplete="off"
                                                >
                                        </div>
                                </div>
                                <div class="hr-self-drive-vehicle-selectors"></div>
                                <p class="hr-self-drive-booking-total"></p>

                                <input type="hidden" name="hr_self_drive_rental_id" value="<?php echo esc_attr( $self_drive_data['rentalId'] ); ?>">
                                <input type="hidden" name="hr_self_drive_selection_payload" value="">
                                <input type="hidden" name="hr_self_drive_rental_date" value="">
                                <input type="hidden" name="hr_self_drive_return_date" value="">
                                <input type="hidden" name="hr_self_drive_guests" value="">
                                <input type="hidden" name="hr_self_drive_hotel_name" value="">
                                <input type="hidden" name="hr_self_drive_days" value="">
                                <input type="hidden" name="hr_self_drive_start_time" value="">
                                <input type="hidden" name="hr_self_drive_return_time" value="">
                                <input type="hidden" name="hr_self_drive_handover_option" value="">
                                <input type="hidden" name="hr_self_drive_delivery_address" value="">
                                <input type="hidden" name="hr_self_drive_total" value="">
                        </div>
                        <?php
                        return;
                }

                $destination_tour_id = $this->get_linked_product_destination_tour_id( $product_id );
                $destination_tour_data = $destination_tour_id ? $this->get_destination_tour_booking_data( $destination_tour_id ) : null;

                if ( ! empty( $destination_tour_data ) ) {
                        $destination_tour_json = wp_json_encode( $destination_tour_data );
                        $destination_tour_date_id = 'hr-destination-tour-date-' . $product_id;
                        $destination_tour_guests_id = 'hr-destination-tour-guests-' . $product_id;
                        ?>
                        <div class="rental_item hr-destination-tour-booking-field" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-destination-tour='<?php echo esc_attr( $destination_tour_json ); ?>'>
                                <div class="hr-destination-tour-selectors">
                                        <div class="hr-destination-tour-field-group hr-destination-tour-date-field">
                                                <label for="<?php echo esc_attr( $destination_tour_date_id ); ?>">
                                                        <?php esc_html_e( 'Rental Date', 'hotel-rates' ); ?> <span class="required">*</span>
                                                </label>
                                                <input
                                                        type="text"
                                                        id="<?php echo esc_attr( $destination_tour_date_id ); ?>"
                                                        class="hr-destination-tour-date-input checkout-date hr-uniform-booking-datepicker ovabrw-input-required"
                                                        name="hr_destination_tour_rental_date_display"
                                                        placeholder="<?php esc_attr_e( 'DD-MM-YYYY', 'hotel-rates' ); ?>"
                                                        required
                                                        aria-required="true"
                                                        autocomplete="off"
                                                >
                                        </div>

                                        <div class="hr-destination-tour-guest-row">
                                                <label class="hr-destination-tour-guest-label" for="<?php echo esc_attr( $destination_tour_guests_id ); ?>">
                                                        <?php esc_html_e( 'No. of Guest', 'hotel-rates' ); ?>
                                                </label>
                                                <button type="button" class="hr-destination-tour-qty-btn hr-destination-tour-guest-minus" aria-label="<?php esc_attr_e( 'Decrease guests', 'hotel-rates' ); ?>">-</button>
                                                <input
                                                        type="number"
                                                        id="<?php echo esc_attr( $destination_tour_guests_id ); ?>"
                                                        class="hr-destination-tour-guest-count ovabrw-input-required"
                                                        name="hr_destination_tour_guests_display"
                                                        value="1"
                                                        min="1"
                                                        step="1"
                                                        inputmode="numeric"
                                                        required
                                                        aria-required="true"
                                                >
                                                <button type="button" class="hr-destination-tour-qty-btn hr-destination-tour-guest-plus" aria-label="<?php esc_attr_e( 'Increase guests', 'hotel-rates' ); ?>">+</button>
                                        </div>

                                        <div class="hr-destination-tour-pickup-title">
                                                <?php esc_html_e( 'Pick-up Information', 'hotel-rates' ); ?>
                                        </div>

                                        <div class="hr-destination-tour-field-group">
                                                <input
                                                        type="text"
                                                        class="hr-destination-tour-hotel-input ovabrw-input-required"
                                                        name="hr_destination_tour_hotel_name_display"
                                                        placeholder="<?php esc_attr_e( 'Hotel/Resort Name', 'hotel-rates' ); ?>"
                                                        required
                                                        aria-required="true"
                                                        autocomplete="off"
                                                >
                                        </div>

                                        <div class="hr-destination-tour-field-group">
                                                <select class="hr-destination-tour-pickup-time-select ovabrw-input-required" name="hr_destination_tour_pickup_time_display" required aria-required="true">
                                                        <option value=""><?php esc_html_e( 'Pick-up Time', 'hotel-rates' ); ?></option>
                                                        <?php foreach ( $destination_tour_data['pickupTimes'] as $pickup_time ) : ?>
                                                                <option value="<?php echo esc_attr( $pickup_time ); ?>"><?php echo esc_html( $pickup_time ); ?></option>
                                                        <?php endforeach; ?>
                                                </select>
                                        </div>

                                        <div class="hr-destination-tour-field-group">
                                                <select class="hr-destination-tour-destination-select ovabrw-input-required" name="hr_destination_tour_destination_display" required aria-required="true">
                                                        <option value=""><?php esc_html_e( 'Tourist Destinations:', 'hotel-rates' ); ?></option>
                                                        <?php foreach ( $destination_tour_data['destinations'] as $destination_index => $destination ) : ?>
                                                                <?php
                                                                $destination_label = (string) ( $destination['label'] ?? '' );
                                                                $destination_duration = (string) ( $destination['duration'] ?? '' );
                                                                $destination_rate = isset( $destination['rate'] ) ? (float) $destination['rate'] : 0.0;
                                                                $destination_option_text = $destination_label;

                                                                if ( '' !== $destination_duration ) {
                                                                        $destination_option_text .= ' - ' . $destination_duration;
                                                                }

                                                                if ( $destination_rate > 0 ) {
                                                                        $destination_option_text .= ' - ' . $this->format_port_transfer_price_text( $destination_rate );
                                                                }
                                                                ?>
                                                                <option
                                                                        value="<?php echo esc_attr( $destination_index ); ?>"
                                                                        data-label="<?php echo esc_attr( $destination_label ); ?>"
                                                                        data-duration="<?php echo esc_attr( $destination_duration ); ?>"
                                                                        data-rate="<?php echo esc_attr( $destination_rate ); ?>"
                                                                >
                                                                        <?php echo esc_html( $destination_option_text ); ?>
                                                                </option>
                                                        <?php endforeach; ?>
                                                </select>
                                        </div>

                                        <?php if ( ! empty( $destination_tour_data['pickupAddons'] ) ) : ?>
                                                <div class="hr-destination-tour-addons">
                                                        <div class="hr-destination-tour-addons-title">
                                                                <?php esc_html_e( 'Add-ons:', 'hotel-rates' ); ?>
                                                        </div>
                                                        <select class="hr-destination-tour-pickup-addon-select" name="hr_destination_tour_pickup_addon_display">
                                                                <option value=""><?php esc_html_e( 'For Pick-up Outside Dumaguete City', 'hotel-rates' ); ?></option>
                                                                <?php foreach ( $destination_tour_data['pickupAddons'] as $addon_index => $addon ) : ?>
                                                                        <?php
                                                                        $addon_label = (string) ( $addon['label'] ?? '' );
                                                                        $addon_rate = isset( $addon['rate'] ) ? (float) $addon['rate'] : 0.0;
                                                                        $addon_option_text = $addon_label;

                                                                        if ( $addon_rate > 0 ) {
                                                                                $addon_option_text .= ' - ' . $this->format_port_transfer_price_text( $addon_rate );
                                                                        }
                                                                        ?>
                                                                        <option
                                                                                value="<?php echo esc_attr( $addon_index ); ?>"
                                                                                data-label="<?php echo esc_attr( $addon_label ); ?>"
                                                                                data-rate="<?php echo esc_attr( $addon_rate ); ?>"
                                                                        >
                                                                                <?php echo esc_html( $addon_option_text ); ?>
                                                                        </option>
                                                                <?php endforeach; ?>
                                                        </select>
                                                </div>
                                        <?php endif; ?>
                                </div>
                                <p class="hr-destination-tour-booking-total"></p>

                                <input type="hidden" name="hr_destination_tour_id" value="<?php echo esc_attr( $destination_tour_data['tourId'] ); ?>">
                                <input type="hidden" name="hr_destination_tour_selection_payload" value="">
                                <input type="hidden" name="hr_destination_tour_rental_date" value="">
                                <input type="hidden" name="hr_destination_tour_guests" value="">
                                <input type="hidden" name="hr_destination_tour_hotel_name" value="">
                                <input type="hidden" name="hr_destination_tour_pickup_time" value="">
                                <input type="hidden" name="hr_destination_tour_destination_index" value="">
                                <input type="hidden" name="hr_destination_tour_destination_label" value="">
                                <input type="hidden" name="hr_destination_tour_destination_duration" value="">
                                <input type="hidden" name="hr_destination_tour_rental_rate" value="">
                                <input type="hidden" name="hr_destination_tour_pickup_addon_index" value="">
                                <input type="hidden" name="hr_destination_tour_pickup_addon_label" value="">
                                <input type="hidden" name="hr_destination_tour_pickup_addon_rate" value="">
                                <input type="hidden" name="hr_destination_tour_total" value="">
                        </div>
                        <?php
                        return;
                }

                $pickup_dropoff_service_id = $this->get_linked_product_pickup_dropoff_service_id( $product_id );
                $pickup_dropoff_data = $pickup_dropoff_service_id ? $this->get_pickup_dropoff_booking_data( $pickup_dropoff_service_id ) : null;

                if ( ! empty( $pickup_dropoff_data ) ) {
                        $pickup_dropoff_json = wp_json_encode( $pickup_dropoff_data );
                        $pickup_dropoff_date_id = 'hr-pickup-dropoff-date-' . $product_id;
                        $pickup_dropoff_passengers_id = 'hr-pickup-dropoff-passengers-' . $product_id;
                        ?>
                        <div class="rental_item hr-pickup-dropoff-booking-field" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-pickup-dropoff='<?php echo esc_attr( $pickup_dropoff_json ); ?>'>
                                <div class="hr-pickup-dropoff-selectors">
                                        <div class="hr-pickup-dropoff-route-grid">
                                                <div class="hr-pickup-dropoff-field-group hr-pickup-dropoff-route-field">
                                                        <label><?php esc_html_e( 'From:', 'hotel-rates' ); ?></label>
                                                        <select class="hr-pickup-dropoff-from-select ovabrw-input-required" name="hr_pickup_dropoff_from_display" required aria-required="true">
                                                                <option value=""><?php esc_html_e( 'From', 'hotel-rates' ); ?></option>
                                                                <?php foreach ( $pickup_dropoff_data['locations'] as $location ) : ?>
                                                                        <option value="<?php echo esc_attr( $location ); ?>"><?php echo esc_html( $location ); ?></option>
                                                                <?php endforeach; ?>
                                                        </select>
                                                </div>
                                                <button type="button" class="hr-pickup-dropoff-swap-button" aria-label="<?php esc_attr_e( 'Switch From and Drop-off To', 'hotel-rates' ); ?>">&#8597;</button>
                                                <div class="hr-pickup-dropoff-field-group hr-pickup-dropoff-route-field">
                                                        <label><?php esc_html_e( 'Drop-off To:', 'hotel-rates' ); ?></label>
                                                        <select class="hr-pickup-dropoff-to-select ovabrw-input-required" name="hr_pickup_dropoff_to_display" required aria-required="true">
                                                                <option value=""><?php esc_html_e( 'Drop-off To', 'hotel-rates' ); ?></option>
                                                                <?php foreach ( $pickup_dropoff_data['locations'] as $location ) : ?>
                                                                        <option value="<?php echo esc_attr( $location ); ?>"><?php echo esc_html( $location ); ?></option>
                                                                <?php endforeach; ?>
                                                        </select>
                                                </div>
                                        </div>

                                        <div class="hr-pickup-dropoff-field-group hr-pickup-dropoff-date-field">
                                                <label for="<?php echo esc_attr( $pickup_dropoff_date_id ); ?>">
                                                        <?php esc_html_e( 'Rental Date', 'hotel-rates' ); ?> <span class="required">*</span>
                                                </label>
                                                <input
                                                        type="text"
                                                        id="<?php echo esc_attr( $pickup_dropoff_date_id ); ?>"
                                                        class="hr-pickup-dropoff-date-input checkout-date hr-uniform-booking-datepicker ovabrw-input-required"
                                                        name="hr_pickup_dropoff_rental_date_display"
                                                        placeholder="<?php esc_attr_e( 'DD-MM-YYYY', 'hotel-rates' ); ?>"
                                                        required
                                                        aria-required="true"
                                                        autocomplete="off"
                                                >
                                        </div>

                                        <div class="hr-pickup-dropoff-field-group">
                                                <select class="hr-pickup-dropoff-pickup-time-select ovabrw-input-required" name="hr_pickup_dropoff_pickup_time_display" required aria-required="true">
                                                        <option value=""><?php esc_html_e( 'Choose Pick-up Time', 'hotel-rates' ); ?></option>
                                                        <?php foreach ( $pickup_dropoff_data['pickupTimes'] as $pickup_time ) : ?>
                                                                <option value="<?php echo esc_attr( $pickup_time ); ?>"><?php echo esc_html( $pickup_time ); ?></option>
                                                        <?php endforeach; ?>
                                                </select>
                                        </div>

                                        <div class="hr-pickup-dropoff-field-group">
                                                <input
                                                        type="text"
                                                        class="hr-pickup-dropoff-hotel-input ovabrw-input-required"
                                                        name="hr_pickup_dropoff_hotel_name_display"
                                                        placeholder="<?php esc_attr_e( 'Hotel/Resort Name', 'hotel-rates' ); ?>"
                                                        required
                                                        aria-required="true"
                                                        autocomplete="off"
                                                >
                                        </div>

                                        <div class="hr-pickup-dropoff-passenger-row">
                                                <label class="hr-pickup-dropoff-passenger-label" for="<?php echo esc_attr( $pickup_dropoff_passengers_id ); ?>">
                                                        <?php esc_html_e( 'No. of Passengers', 'hotel-rates' ); ?>
                                                </label>
                                                <button type="button" class="hr-pickup-dropoff-qty-btn hr-pickup-dropoff-passenger-minus" aria-label="<?php esc_attr_e( 'Decrease passengers', 'hotel-rates' ); ?>">-</button>
                                                <input
                                                        type="number"
                                                        id="<?php echo esc_attr( $pickup_dropoff_passengers_id ); ?>"
                                                        class="hr-pickup-dropoff-passenger-count ovabrw-input-required"
                                                        name="hr_pickup_dropoff_passengers_display"
                                                        value="1"
                                                        min="1"
                                                        step="1"
                                                        inputmode="numeric"
                                                        required
                                                        aria-required="true"
                                                >
                                                <button type="button" class="hr-pickup-dropoff-qty-btn hr-pickup-dropoff-passenger-plus" aria-label="<?php esc_attr_e( 'Increase passengers', 'hotel-rates' ); ?>">+</button>
                                        </div>

                                        <?php if ( ! empty( $pickup_dropoff_data['addons'] ) ) : ?>
                                                <div class="hr-pickup-dropoff-addons">
                                                        <div class="hr-pickup-dropoff-addons-title">
                                                                <?php esc_html_e( 'ADD-ONS', 'hotel-rates' ); ?>
                                                        </div>
                                                        <?php foreach ( $pickup_dropoff_data['addons'] as $addon_index => $addon ) : ?>
                                                                <?php
                                                                $addon_label = (string) ( $addon['label'] ?? '' );
                                                                $addon_rate = isset( $addon['rate'] ) ? (float) $addon['rate'] : 0.0;
                                                                $addon_option_text = $addon_label;

                                                                if ( $addon_rate > 0 ) {
                                                                        $addon_option_text .= ' (+' . $this->format_port_transfer_price_text( $addon_rate ) . ')';
                                                                }
                                                                ?>
                                                                <label class="hr-pickup-dropoff-addon-label">
                                                                        <input
                                                                                type="checkbox"
                                                                                class="hr-pickup-dropoff-addon-option"
                                                                                value="<?php echo esc_attr( $addon_index ); ?>"
                                                                                data-label="<?php echo esc_attr( $addon_label ); ?>"
                                                                                data-rate="<?php echo esc_attr( $addon_rate ); ?>"
                                                                        >
                                                                        <?php echo esc_html( $addon_option_text ); ?>
                                                                </label>
                                                        <?php endforeach; ?>
                                                </div>
                                        <?php endif; ?>
                                </div>
                                <p class="hr-pickup-dropoff-booking-total"></p>

                                <input type="hidden" name="hr_pickup_dropoff_service_id" value="<?php echo esc_attr( $pickup_dropoff_data['serviceId'] ); ?>">
                                <input type="hidden" name="hr_pickup_dropoff_selection_payload" value="">
                                <input type="hidden" name="hr_pickup_dropoff_rental_date" value="">
                                <input type="hidden" name="hr_pickup_dropoff_passengers" value="">
                                <input type="hidden" name="hr_pickup_dropoff_from" value="">
                                <input type="hidden" name="hr_pickup_dropoff_to" value="">
                                <input type="hidden" name="hr_pickup_dropoff_route_index" value="">
                                <input type="hidden" name="hr_pickup_dropoff_route_label" value="">
                                <input type="hidden" name="hr_pickup_dropoff_pickup_time" value="">
                                <input type="hidden" name="hr_pickup_dropoff_hotel_name" value="">
                                <input type="hidden" name="hr_pickup_dropoff_unit_price" value="">
                                <input type="hidden" name="hr_pickup_dropoff_addons" value="">
                                <input type="hidden" name="hr_pickup_dropoff_addons_total" value="">
                                <input type="hidden" name="hr_pickup_dropoff_total" value="">
                        </div>
                        <?php
                        return;
                }

                $port_transfer_id = $this->get_linked_product_port_transfer_id( $product_id );
                $port_transfer_data = $port_transfer_id ? $this->get_port_transfer_booking_data( $port_transfer_id ) : null;

                if ( ! empty( $port_transfer_data ) ) {
                        $port_transfer_json = wp_json_encode( $port_transfer_data );
                        ?>
                        <div class="rental_item hr-port-transfer-booking-field" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-port-transfer='<?php echo esc_attr( $port_transfer_json ); ?>'>
                                <div class="hr-port-transfer-selectors"></div>
                                <p class="hr-port-transfer-booking-total"></p>

                                <input type="hidden" name="hr_port_transfer_id" value="<?php echo esc_attr( $port_transfer_data['transferId'] ); ?>">
                                <input type="hidden" name="hr_port_transfer_selection_payload" value="">
                                <input type="hidden" name="hr_port_transfer_date" value="">
                                <input type="hidden" name="hr_port_transfer_passengers" value="">
                                <input type="hidden" name="hr_port_transfer_route_index" value="">
                                <input type="hidden" name="hr_port_transfer_route_label" value="">
                                <input type="hidden" name="hr_port_transfer_from" value="">
                                <input type="hidden" name="hr_port_transfer_to" value="">
                                <input type="hidden" name="hr_port_transfer_direction" value="">
                                <input type="hidden" name="hr_port_transfer_type" value="">
                                <input type="hidden" name="hr_port_transfer_time" value="">
                                <input type="hidden" name="hr_port_transfer_flight_number" value="">
                                <input type="hidden" name="hr_port_transfer_hotel_name" value="">
                                <input type="hidden" name="hr_port_transfer_dropoff" value="">
                                <input type="hidden" name="hr_port_transfer_return_date" value="">
                                <input type="hidden" name="hr_port_transfer_return_time" value="">
                                <input type="hidden" name="hr_port_transfer_return_dropoff" value="">
                                <input type="hidden" name="hr_port_transfer_addons" value="">
                                <input type="hidden" name="hr_port_transfer_addons_total" value="">
                                <input type="hidden" name="hr_port_transfer_unit_price" value="">
                                <input type="hidden" name="hr_port_transfer_total" value="">
                        </div>
                        <?php
                        return;
                }
                ?>
                <div class="rental_item hr-booking-rate-field" data-product-id="<?php echo esc_attr( $product_id ); ?>" hidden>
                        <label for="hr-selected-rate-<?php echo esc_attr( $product_id ); ?>">
                                <?php esc_html_e( 'Hotel Package', 'hotel-rates' ); ?>
                        </label>
                        <select id="hr-selected-rate-<?php echo esc_attr( $product_id ); ?>" name="hr_selected_rate_id" class="hr-booking-rate-select">
                                <option value=""><?php esc_html_e( 'Select a hotel package', 'hotel-rates' ); ?></option>
                        </select>
                        <p class="hr-booking-rate-help"></p>

                        <input type="hidden" name="hr_selected_hotel_id" value="">
                        <input type="hidden" name="hr_selected_hotel_name" value="">
                        <input type="hidden" name="hr_selected_hotel_label" value="">
                        <input type="hidden" name="hr_selected_pax" value="">
                        <input type="hidden" name="hr_selected_price" value="">
                </div>
                <?php
	}

	public function prepare_motorbike_guestless_add_to_cart_request( $passed, $product_id, $quantity ) {
		if ( ! $this->get_linked_product_motorbike_rental_id( $product_id ) ) {
			return $passed;
		}

		$guest_fallbacks = array(
			'ovabrw_adults'    => '1',
			'ovabrw_childrens' => '0',
			'ovabrw_babies'    => '0',
			'ovabrw_quantity'  => '1',
			'quantity'         => '1',
		);

		foreach ( $guest_fallbacks as $key => $value ) {
			$_POST[ $key ]    = $value;
			$_REQUEST[ $key ] = $value;
		}

		$motorbike_total = isset( $_POST['hr_motorbike_total'] ) ? (float) wp_unslash( $_POST['hr_motorbike_total'] ) : 0.0;
		if ( $motorbike_total > 0 ) {
			foreach ( array( 'ovabrw_total', 'ovabrw_total_price', 'ovabrw_amount', 'total_price' ) as $key ) {
				$_POST[ $key ]    = (string) $motorbike_total;
				$_REQUEST[ $key ] = (string) $motorbike_total;
			}
		}

		$this->populate_motorbike_legacy_custom_checkout_fields( $product_id );

		return $passed;
	}

	private function populate_motorbike_legacy_custom_checkout_fields( $product_id ) {
		if ( ! function_exists( 'ovabrw_get_list_field_checkout' ) ) {
			return;
		}

		$fields = ovabrw_get_list_field_checkout( $product_id );
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return;
		}

		$posted_custom_fields = $fields;

		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) || ( isset( $field['enabled'] ) && 'on' !== $field['enabled'] ) ) {
				continue;
			}

			$type  = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';
			$value = $this->get_motorbike_legacy_custom_checkout_field_value( $key, $field, $type, $product_id );

			if ( isset( $posted_custom_fields[ $key ]['required'] ) ) {
				$posted_custom_fields[ $key ]['required'] = '';
			}

			if ( 'file' === $type ) {
				continue;
			}

			if ( 'checkbox' === $type ) {
				$value = $value ? $value : $this->get_first_motorbike_custom_field_option( $field, 'checkbox' );
				if ( $value && empty( $_POST[ $key ] ) ) {
					$_POST[ $key ]    = array( $value => $value );
					$_REQUEST[ $key ] = $_POST[ $key ];
				}
				continue;
			}

			if ( '' === (string) $value ) {
				$value = __( 'Motorbike rental', 'hotel-rates' );
			}

			if ( empty( $_POST[ $key ] ) ) {
				$_POST[ $key ]    = $value;
				$_REQUEST[ $key ] = $value;
			}
		}

		$_POST['data_custom_ckf']    = wp_json_encode( $posted_custom_fields );
		$_REQUEST['data_custom_ckf'] = $_POST['data_custom_ckf'];
	}

	private function get_motorbike_legacy_custom_checkout_field_value( $key, $field, $type, $product_id ) {
		$field_text       = $this->get_motorbike_custom_field_search_text( $key, $field );
		$motorbike_data   = $this->get_motorbike_booking_data( $this->get_linked_product_motorbike_rental_id( $product_id ) );
		$selected_models  = $this->get_motorbike_selected_model_names_from_request( $motorbike_data );
		$start_time       = isset( $_POST['hr_motorbike_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['hr_motorbike_start_time'] ) ) : '';
		$days             = isset( $_POST['hr_motorbike_days'] ) ? (int) wp_unslash( $_POST['hr_motorbike_days'] ) : 0;
		$handover_option  = isset( $_POST['hr_motorbike_handover_option'] ) ? sanitize_key( wp_unslash( $_POST['hr_motorbike_handover_option'] ) ) : '';
		$delivery_address = isset( $_POST['hr_motorbike_delivery_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hr_motorbike_delivery_address'] ) ) : '';

		if ( preg_match( '/motorbike|model|unit/i', $field_text ) && ! empty( $selected_models ) ) {
			return implode( ', ', $selected_models );
		}

		if ( preg_match( '/time|pick[\s_-]*up|pickup|delivery/i', $field_text ) && $start_time ) {
			return $start_time;
		}

		if ( preg_match( '/day|duration|rental\s+length/i', $field_text ) && $days ) {
			return (string) $days;
		}

		if ( preg_match( '/address|location/i', $field_text ) ) {
			return $delivery_address ? $delivery_address : __( 'Pick-up', 'hotel-rates' );
		}

		if ( preg_match( '/handover|pick[\s_-]*up|pickup|delivery|drop[\s_-]*off|dropoff/i', $field_text ) ) {
			return $this->get_motorbike_matching_custom_field_option( $field, $type, 'delivery' === $handover_option ? 'delivery' : 'pickup' );
		}

		return $this->get_first_motorbike_custom_field_option( $field, $type );
	}

	private function get_motorbike_custom_field_search_text( $key, $field ) {
		$parts = array(
			$key,
			$field['name'] ?? '',
			$field['label'] ?? '',
			$field['placeholder'] ?? '',
			$field['class'] ?? '',
		);

		return strtolower( implode( ' ', array_map( 'wp_strip_all_tags', array_map( 'strval', $parts ) ) ) );
	}

	private function get_motorbike_selected_model_names_from_request( $motorbike_data ) {
		$payload = isset( $_POST['hr_motorbike_selection_payload'] ) ? json_decode( wp_unslash( $_POST['hr_motorbike_selection_payload'] ), true ) : array();
		$payload = is_array( $payload ) ? $payload : array();
		$models  = ! empty( $motorbike_data['models'] ) && is_array( $motorbike_data['models'] ) ? $motorbike_data['models'] : array();
		$names   = array();

		if ( empty( $payload['models'] ) || ! is_array( $payload['models'] ) ) {
			return $names;
		}

		foreach ( $payload['models'] as $index => $quantity ) {
			$quantity = (int) $quantity;
			if ( $quantity < 1 || ! isset( $models[ (int) $index ] ) ) {
				continue;
			}

			$name    = $models[ (int) $index ]['name'] ?? __( 'Motorbike', 'hotel-rates' );
			$names[] = sprintf( '%1$s x %2$d', $name, $quantity );
		}

		return $names;
	}

	private function get_motorbike_matching_custom_field_option( $field, $type, $needle ) {
		$options = $this->get_motorbike_custom_field_options( $field, $type );
		$needle  = strtolower( (string) $needle );

		foreach ( $options as $value => $label ) {
			$haystack = strtolower( $value . ' ' . $label );
			if ( false !== strpos( $haystack, $needle ) ) {
				return $value;
			}
		}

		return $this->get_first_motorbike_custom_field_option( $field, $type );
	}

	private function get_first_motorbike_custom_field_option( $field, $type ) {
		$options = $this->get_motorbike_custom_field_options( $field, $type );

		if ( empty( $options ) ) {
			return isset( $field['default'] ) ? (string) $field['default'] : '';
		}

		$default = isset( $field['default'] ) ? (string) $field['default'] : '';
		if ( '' !== $default && isset( $options[ $default ] ) ) {
			return $default;
		}

		$keys = array_keys( $options );

		return (string) reset( $keys );
	}

	private function get_motorbike_custom_field_options( $field, $type ) {
		if ( 'radio' === $type ) {
			$values = ! empty( $field['ova_radio_values'] ) && is_array( $field['ova_radio_values'] ) ? $field['ova_radio_values'] : array();
			return array_combine( $values, $values ) ?: array();
		}

		if ( 'checkbox' === $type ) {
			$keys   = ! empty( $field['ova_checkbox_key'] ) && is_array( $field['ova_checkbox_key'] ) ? $field['ova_checkbox_key'] : array();
			$labels = ! empty( $field['ova_checkbox_text'] ) && is_array( $field['ova_checkbox_text'] ) ? $field['ova_checkbox_text'] : $keys;
			return $keys ? array_combine( $keys, array_slice( array_pad( $labels, count( $keys ), '' ), 0, count( $keys ) ) ) : array();
		}

		if ( 'select' === $type ) {
			$keys   = ! empty( $field['ova_options_key'] ) && is_array( $field['ova_options_key'] ) ? $field['ova_options_key'] : array();
			$labels = ! empty( $field['ova_options_text'] ) && is_array( $field['ova_options_text'] ) ? $field['ova_options_text'] : $keys;
			return $keys ? array_combine( $keys, array_slice( array_pad( $labels, count( $keys ), '' ), 0, count( $keys ) ) ) : array();
		}

		return array();
	}

	public function validate_motorbike_add_to_cart( $passed, $product_id, $quantity ) {
		if ( ! $this->get_linked_product_motorbike_rental_id( $product_id ) ) {
			return $passed;
                }

                $selection = $this->get_motorbike_selection_from_request( $product_id );

                if ( ! $selection ) {
                        return false;
                }

                return $passed;
        }

	public function prepare_boat_guestless_add_to_cart_request( $passed, $product_id, $quantity ) {
		if ( ! $this->get_linked_product_boat_rental_id( $product_id ) ) {
			return $passed;
		}

		$passenger_count = isset( $_POST['hr_boat_passengers'] ) ? (int) wp_unslash( $_POST['hr_boat_passengers'] ) : 0;
		if ( $passenger_count < 1 && isset( $_POST['hr_boat_passengers_display'] ) ) {
			$passenger_count = (int) wp_unslash( $_POST['hr_boat_passengers_display'] );
		}
		if ( $passenger_count > 0 ) {
			$_POST['hr_boat_passengers']    = (string) $passenger_count;
			$_REQUEST['hr_boat_passengers'] = (string) $passenger_count;
		}

		$guest_fallbacks = array(
			'ovabrw_adults'    => (string) max( 1, $passenger_count ),
			'ovabrw_childrens' => '0',
			'ovabrw_babies'    => '0',
			'ovabrw_quantity'  => '1',
			'quantity'         => '1',
		);

		foreach ( $guest_fallbacks as $key => $value ) {
			$_POST[ $key ]    = $value;
			$_REQUEST[ $key ] = $value;
		}

		$boat_total = isset( $_POST['hr_boat_total'] ) ? (float) wp_unslash( $_POST['hr_boat_total'] ) : 0.0;
		if ( $boat_total > 0 ) {
			foreach ( array( 'ovabrw_total', 'ovabrw_total_price', 'ovabrw_amount', 'total_price' ) as $key ) {
				$_POST[ $key ]    = (string) $boat_total;
				$_REQUEST[ $key ] = (string) $boat_total;
			}
		}

		$rental_date = $this->get_self_drive_request_date_value( 'hr_boat_rental_date', array( 'ovabrw_pickup_date', 'pickup_date', 'checkin_date', 'tour_date', 'rental_date', 'booking_date' ) );
		if ( '' !== $rental_date ) {
			$_POST['hr_boat_rental_date']    = $rental_date;
			$_REQUEST['hr_boat_rental_date'] = $rental_date;
			$_POST['ovabrw_pickup_date']     = $rental_date;
			$_REQUEST['ovabrw_pickup_date']  = $rental_date;
		}

		$this->populate_boat_legacy_custom_checkout_fields( $product_id );

		return $passed;
	}

	private function populate_boat_legacy_custom_checkout_fields( $product_id ) {
		if ( ! function_exists( 'ovabrw_get_list_field_checkout' ) ) {
			return;
		}

		$fields = ovabrw_get_list_field_checkout( $product_id );
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return;
		}

		$posted_custom_fields = $fields;

		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) || ( isset( $field['enabled'] ) && 'on' !== $field['enabled'] ) ) {
				continue;
			}

			$type  = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';
			$value = $this->get_boat_legacy_custom_checkout_field_value( $key, $field, $type );

			if ( isset( $posted_custom_fields[ $key ]['required'] ) ) {
				$posted_custom_fields[ $key ]['required'] = '';
			}

			if ( 'file' === $type ) {
				continue;
			}

			if ( 'checkbox' === $type ) {
				$value = $value ? $value : $this->get_first_self_drive_custom_field_option( $field, 'checkbox' );
				if ( $value && empty( $_POST[ $key ] ) ) {
					$_POST[ $key ]    = array( $value => $value );
					$_REQUEST[ $key ] = $_POST[ $key ];
				}
				continue;
			}

			if ( '' === (string) $value ) {
				$value = __( 'Boat rental', 'hotel-rates' );
			}

			if ( empty( $_POST[ $key ] ) ) {
				$_POST[ $key ]    = $value;
				$_REQUEST[ $key ] = $value;
			}
		}

		$_POST['data_custom_ckf']    = wp_json_encode( $posted_custom_fields );
		$_REQUEST['data_custom_ckf'] = $_POST['data_custom_ckf'];
	}

	private function get_boat_legacy_custom_checkout_field_value( $key, $field, $type ) {
		$field_text   = $this->get_self_drive_custom_field_search_text( $key, $field );
		$rental_date  = $this->get_self_drive_request_date_value( 'hr_boat_rental_date', array( 'ovabrw_pickup_date', 'pickup_date', 'checkin_date', 'tour_date', 'rental_date', 'booking_date' ) );
		$passengers   = isset( $_POST['hr_boat_passengers'] ) ? (int) wp_unslash( $_POST['hr_boat_passengers'] ) : 0;
		$rate_label   = $this->get_posted_text_value( 'hr_boat_rate_label' );
		$boarding_time = $this->get_posted_text_value( 'hr_boat_boarding_time' );

		if ( $passengers < 1 && isset( $_POST['hr_boat_passengers_display'] ) ) {
			$passengers = (int) wp_unslash( $_POST['hr_boat_passengers_display'] );
		}

		if ( preg_match( '/passenger|pax|adult|guest|number.*passenger|no.*passenger/i', $field_text ) && $passengers > 0 ) {
			return (string) $passengers;
		}

		if ( preg_match( '/boat|capacity|rate|selection/i', $field_text ) && $rate_label ) {
			return $rate_label;
		}

		if ( preg_match( '/boarding.*time|board.*time|time/i', $field_text ) && $boarding_time ) {
			return $boarding_time;
		}

		if ( preg_match( '/rental.*date|pick[\s_-]*up.*date|pickup.*date|start.*date|booking.*date/i', $field_text ) && $rental_date ) {
			return $rental_date;
		}

		return $this->get_first_self_drive_custom_field_option( $field, $type );
	}

	public function validate_boat_add_to_cart( $passed, $product_id, $quantity ) {
		if ( ! $this->get_linked_product_boat_rental_id( $product_id ) ) {
			return $passed;
		}

		$selection = $this->get_boat_selection_from_request( $product_id );

		if ( ! $selection ) {
			return false;
		}

		return $passed;
	}

	public function prepare_self_drive_guestless_add_to_cart_request( $passed, $product_id, $quantity ) {
		if ( ! $this->get_linked_product_self_drive_rental_id( $product_id ) ) {
			return $passed;
		}

		$guest_count = isset( $_POST['hr_self_drive_guests'] ) ? (int) wp_unslash( $_POST['hr_self_drive_guests'] ) : 0;
		if ( $guest_count < 1 && isset( $_POST['hr_self_drive_guests_display'] ) ) {
			$guest_count = (int) wp_unslash( $_POST['hr_self_drive_guests_display'] );
		}
		$guest_count = max( 1, $guest_count );
		$_POST['hr_self_drive_guests']    = (string) $guest_count;
		$_REQUEST['hr_self_drive_guests'] = (string) $guest_count;

		$hotel_name = $this->get_posted_text_value( 'hr_self_drive_hotel_name' );
		if ( '' === $hotel_name ) {
			$hotel_name = $this->get_posted_text_value( 'hr_self_drive_hotel_name_display' );
		}
		if ( '' !== $hotel_name ) {
			$_POST['hr_self_drive_hotel_name']    = $hotel_name;
			$_REQUEST['hr_self_drive_hotel_name'] = $hotel_name;
		}

		$guest_fallbacks = array(
			'ovabrw_adults'    => (string) $guest_count,
			'ovabrw_childrens' => '0',
			'ovabrw_babies'    => '0',
			'ovabrw_quantity'  => '1',
			'quantity'         => '1',
		);

		foreach ( $guest_fallbacks as $key => $value ) {
			$_POST[ $key ]    = $value;
			$_REQUEST[ $key ] = $value;
		}

		$self_drive_total = isset( $_POST['hr_self_drive_total'] ) ? (float) wp_unslash( $_POST['hr_self_drive_total'] ) : 0.0;
		if ( $self_drive_total > 0 ) {
			foreach ( array( 'ovabrw_total', 'ovabrw_total_price', 'ovabrw_amount', 'total_price' ) as $key ) {
				$_POST[ $key ]    = (string) $self_drive_total;
				$_REQUEST[ $key ] = (string) $self_drive_total;
			}
		}

		$rental_date = $this->get_posted_text_value( 'hr_self_drive_rental_date' );
		$return_date = $this->get_posted_text_value( 'hr_self_drive_return_date' );

		if ( '' !== $rental_date ) {
			$_POST['ovabrw_pickup_date']    = $rental_date;
			$_REQUEST['ovabrw_pickup_date'] = $rental_date;
		}

		if ( '' !== $return_date ) {
			$_POST['ovabrw_pickoff_date']    = $return_date;
			$_REQUEST['ovabrw_pickoff_date'] = $return_date;
			$_POST['ovabrw_dropoff_date']    = $return_date;
			$_REQUEST['ovabrw_dropoff_date'] = $return_date;
		}

		$this->populate_self_drive_legacy_custom_checkout_fields( $product_id );

		return $passed;
	}

	private function populate_self_drive_legacy_custom_checkout_fields( $product_id ) {
		if ( ! function_exists( 'ovabrw_get_list_field_checkout' ) ) {
			return;
		}

		$fields = ovabrw_get_list_field_checkout( $product_id );
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return;
		}

		$posted_custom_fields = $fields;

		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) || ( isset( $field['enabled'] ) && 'on' !== $field['enabled'] ) ) {
				continue;
			}

			$type  = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';
			$value = $this->get_self_drive_legacy_custom_checkout_field_value( $key, $field, $type, $product_id );

			if ( isset( $posted_custom_fields[ $key ]['required'] ) ) {
				$posted_custom_fields[ $key ]['required'] = '';
			}

			if ( 'file' === $type ) {
				continue;
			}

			if ( 'checkbox' === $type ) {
				$value = $value ? $value : $this->get_first_self_drive_custom_field_option( $field, 'checkbox' );
				if ( $value && empty( $_POST[ $key ] ) ) {
					$_POST[ $key ]    = array( $value => $value );
					$_REQUEST[ $key ] = $_POST[ $key ];
				}
				continue;
			}

			if ( '' === (string) $value ) {
				$value = __( 'Self-drive rental', 'hotel-rates' );
			}

			if ( empty( $_POST[ $key ] ) ) {
				$_POST[ $key ]    = $value;
				$_REQUEST[ $key ] = $value;
			}
		}

		$_POST['data_custom_ckf']    = wp_json_encode( $posted_custom_fields );
		$_REQUEST['data_custom_ckf'] = $_POST['data_custom_ckf'];
	}

	private function get_self_drive_legacy_custom_checkout_field_value( $key, $field, $type, $product_id ) {
		$field_text       = $this->get_self_drive_custom_field_search_text( $key, $field );
		$self_drive_data  = $this->get_self_drive_booking_data( $this->get_linked_product_self_drive_rental_id( $product_id ) );
		$selected_vehicles = $this->get_self_drive_selected_vehicle_names_from_request( $self_drive_data );
		$rental_date      = $this->get_posted_text_value( 'hr_self_drive_rental_date' );
		$return_date      = $this->get_posted_text_value( 'hr_self_drive_return_date' );
		$start_time       = isset( $_POST['hr_self_drive_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['hr_self_drive_start_time'] ) ) : '';
		$return_time      = isset( $_POST['hr_self_drive_return_time'] ) ? sanitize_text_field( wp_unslash( $_POST['hr_self_drive_return_time'] ) ) : '';
		$guest_count      = isset( $_POST['hr_self_drive_guests'] ) ? (int) wp_unslash( $_POST['hr_self_drive_guests'] ) : 0;
		$hotel_name       = $this->get_posted_text_value( 'hr_self_drive_hotel_name' );
		if ( $guest_count < 1 && isset( $_POST['hr_self_drive_guests_display'] ) ) {
			$guest_count = (int) wp_unslash( $_POST['hr_self_drive_guests_display'] );
		}
		if ( '' === $hotel_name ) {
			$hotel_name = $this->get_posted_text_value( 'hr_self_drive_hotel_name_display' );
		}
		$days             = isset( $_POST['hr_self_drive_days'] ) ? (int) wp_unslash( $_POST['hr_self_drive_days'] ) : 0;
		$handover_option  = isset( $_POST['hr_self_drive_handover_option'] ) ? sanitize_key( wp_unslash( $_POST['hr_self_drive_handover_option'] ) ) : '';
		$delivery_address = isset( $_POST['hr_self_drive_delivery_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hr_self_drive_delivery_address'] ) ) : '';

		if ( preg_match( '/self[\s_-]*drive|vehicle|car|unit|model/i', $field_text ) && ! empty( $selected_vehicles ) ) {
			return implode( ', ', $selected_vehicles );
		}

		if ( preg_match( '/guest|pax|adult|number.*guest|no.*guest/i', $field_text ) && $guest_count > 0 ) {
			return (string) $guest_count;
		}

		if ( preg_match( '/hotel|resort|accommodation|lodging/i', $field_text ) && $hotel_name ) {
			return $hotel_name;
		}

		if ( preg_match( '/return.*date|drop[\s_-]*off.*date|dropoff.*date|end.*date/i', $field_text ) && $return_date ) {
			return $return_date;
		}

		if ( preg_match( '/rental.*date|pick[\s_-]*up.*date|pickup.*date|start.*date/i', $field_text ) && $rental_date ) {
			return $rental_date;
		}

		if ( preg_match( '/return.*time|drop[\s_-]*off.*time|dropoff.*time|end.*time/i', $field_text ) && $return_time ) {
			return $return_time;
		}

		if ( preg_match( '/time|pick[\s_-]*up|pickup|delivery/i', $field_text ) && $start_time ) {
			return $start_time;
		}

		if ( preg_match( '/day|duration|rental\s+length/i', $field_text ) && $days ) {
			return (string) $days;
		}

		if ( preg_match( '/address|location/i', $field_text ) ) {
			return $delivery_address ? $delivery_address : __( 'Office/Garage Pick-up', 'hotel-rates' );
		}

		if ( preg_match( '/handover|pick[\s_-]*up|pickup|delivery|drop[\s_-]*off|dropoff/i', $field_text ) ) {
			return $this->get_self_drive_matching_custom_field_option( $field, $type, 'delivery' === $handover_option ? 'delivery' : 'pickup' );
		}

		return $this->get_first_self_drive_custom_field_option( $field, $type );
	}

	private function get_self_drive_custom_field_search_text( $key, $field ) {
		$parts = array(
			$key,
			$field['name'] ?? '',
			$field['label'] ?? '',
			$field['placeholder'] ?? '',
			$field['class'] ?? '',
		);

		return strtolower( implode( ' ', array_map( 'wp_strip_all_tags', array_map( 'strval', $parts ) ) ) );
	}

	private function get_self_drive_selected_vehicle_names_from_request( $self_drive_data ) {
		$payload = isset( $_POST['hr_self_drive_selection_payload'] ) ? json_decode( wp_unslash( $_POST['hr_self_drive_selection_payload'] ), true ) : array();
		$payload = is_array( $payload ) ? $payload : array();
		$vehicles = ! empty( $self_drive_data['vehicles'] ) && is_array( $self_drive_data['vehicles'] ) ? $self_drive_data['vehicles'] : array();
		$names = array();

		if ( empty( $payload['vehicles'] ) || ! is_array( $payload['vehicles'] ) ) {
			return $names;
		}

		foreach ( $payload['vehicles'] as $index => $quantity ) {
			$quantity = (int) $quantity;
			if ( $quantity < 1 || ! isset( $vehicles[ (int) $index ] ) ) {
				continue;
			}

			$name = $vehicles[ (int) $index ]['name'] ?? __( 'Self-drive vehicle', 'hotel-rates' );
			$names[] = sprintf( '%1$s x %2$d', $name, $quantity );
		}

		return $names;
	}

	private function get_self_drive_matching_custom_field_option( $field, $type, $needle ) {
		$options = $this->get_motorbike_custom_field_options( $field, $type );
		$needle  = strtolower( (string) $needle );

		foreach ( $options as $value => $label ) {
			$haystack = strtolower( $value . ' ' . $label );
			if ( false !== strpos( $haystack, $needle ) ) {
				return $value;
			}
		}

		return $this->get_first_self_drive_custom_field_option( $field, $type );
	}

	private function get_first_self_drive_custom_field_option( $field, $type ) {
		$options = $this->get_motorbike_custom_field_options( $field, $type );

		if ( empty( $options ) ) {
			return isset( $field['default'] ) ? (string) $field['default'] : '';
		}

		$default = isset( $field['default'] ) ? (string) $field['default'] : '';
		if ( '' !== $default && isset( $options[ $default ] ) ) {
			return $default;
		}

		$keys = array_keys( $options );

		return (string) reset( $keys );
	}

	public function validate_self_drive_add_to_cart( $passed, $product_id, $quantity ) {
		if ( ! $this->get_linked_product_self_drive_rental_id( $product_id ) ) {
			return $passed;
		}

		$selection = $this->get_self_drive_selection_from_request( $product_id, false );

		if ( ! $selection ) {
			return false;
		}

		return $passed;
	}

	public function prepare_port_transfer_guestless_add_to_cart_request( $passed, $product_id, $quantity ) {
		if ( ! $this->get_linked_product_port_transfer_id( $product_id ) ) {
			return $passed;
		}

		$passenger_count = isset( $_POST['hr_port_transfer_passengers'] ) ? (int) wp_unslash( $_POST['hr_port_transfer_passengers'] ) : 0;
		if ( $passenger_count < 1 && isset( $_POST['hr_port_transfer_passengers_display'] ) ) {
			$passenger_count = (int) wp_unslash( $_POST['hr_port_transfer_passengers_display'] );
		}
		$passenger_count = max( 1, $passenger_count );
		$_POST['hr_port_transfer_passengers'] = (string) $passenger_count;
		$_REQUEST['hr_port_transfer_passengers'] = (string) $passenger_count;

		$guest_fallbacks = array(
			'ovabrw_adults'    => (string) $passenger_count,
			'ovabrw_childrens' => '0',
			'ovabrw_babies'    => '0',
			'ovabrw_quantity'  => '1',
			'quantity'         => '1',
		);

		foreach ( $guest_fallbacks as $key => $value ) {
			$_POST[ $key ] = $value;
			$_REQUEST[ $key ] = $value;
		}

		$port_transfer_total = isset( $_POST['hr_port_transfer_total'] ) ? (float) wp_unslash( $_POST['hr_port_transfer_total'] ) : 0.0;
		if ( $port_transfer_total > 0 ) {
			foreach ( array( 'ovabrw_total', 'ovabrw_total_price', 'ovabrw_amount', 'total_price' ) as $key ) {
				$_POST[ $key ] = (string) $port_transfer_total;
				$_REQUEST[ $key ] = (string) $port_transfer_total;
			}
		}

		$transfer_date = $this->get_self_drive_request_date_value( 'hr_port_transfer_date', array( 'hr_port_transfer_date_display', 'ovabrw_pickup_date', 'pickup_date', 'checkin_date', 'tour_date', 'rental_date', 'booking_date' ) );
		$return_date = $this->get_self_drive_request_date_value( 'hr_port_transfer_return_date', array( 'hr_port_transfer_return_date_display', 'ovabrw_pickoff_date', 'ovabrw_dropoff_date', 'dropoff_date', 'checkout_date' ) );

		if ( '' !== $transfer_date ) {
			$_POST['hr_port_transfer_date'] = $transfer_date;
			$_REQUEST['hr_port_transfer_date'] = $transfer_date;
			$_POST['ovabrw_pickup_date'] = $transfer_date;
			$_REQUEST['ovabrw_pickup_date'] = $transfer_date;
		}

		if ( '' !== $return_date ) {
			$_POST['hr_port_transfer_return_date'] = $return_date;
			$_REQUEST['hr_port_transfer_return_date'] = $return_date;
			$_POST['ovabrw_pickoff_date'] = $return_date;
			$_REQUEST['ovabrw_pickoff_date'] = $return_date;
			$_POST['ovabrw_dropoff_date'] = $return_date;
			$_REQUEST['ovabrw_dropoff_date'] = $return_date;
		}

		$this->populate_port_transfer_legacy_custom_checkout_fields( $product_id );

		return $passed;
	}

	private function populate_port_transfer_legacy_custom_checkout_fields( $product_id ) {
		if ( ! function_exists( 'ovabrw_get_list_field_checkout' ) ) {
			return;
		}

		$fields = ovabrw_get_list_field_checkout( $product_id );
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return;
		}

		$posted_custom_fields = $fields;

		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) || ( isset( $field['enabled'] ) && 'on' !== $field['enabled'] ) ) {
				continue;
			}

			$type = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';
			$value = $this->get_port_transfer_legacy_custom_checkout_field_value( $key, $field, $type );

			if ( isset( $posted_custom_fields[ $key ]['required'] ) ) {
				$posted_custom_fields[ $key ]['required'] = '';
			}

			if ( 'file' === $type ) {
				continue;
			}

			if ( 'checkbox' === $type ) {
				$value = $value ? $value : $this->get_first_self_drive_custom_field_option( $field, 'checkbox' );
				if ( $value && empty( $_POST[ $key ] ) ) {
					$_POST[ $key ] = array( $value => $value );
					$_REQUEST[ $key ] = $_POST[ $key ];
				}
				continue;
			}

			if ( '' === (string) $value ) {
				$value = __( 'Port transfer', 'hotel-rates' );
			}

			if ( empty( $_POST[ $key ] ) ) {
				$_POST[ $key ] = $value;
				$_REQUEST[ $key ] = $value;
			}
		}

		$_POST['data_custom_ckf'] = wp_json_encode( $posted_custom_fields );
		$_REQUEST['data_custom_ckf'] = $_POST['data_custom_ckf'];
	}

	private function get_port_transfer_legacy_custom_checkout_field_value( $key, $field, $type ) {
		$field_text = $this->get_self_drive_custom_field_search_text( $key, $field );
		$transfer_date = $this->get_self_drive_request_date_value( 'hr_port_transfer_date', array( 'hr_port_transfer_date_display', 'ovabrw_pickup_date', 'pickup_date', 'checkin_date', 'tour_date', 'rental_date', 'booking_date' ) );
		$return_date = $this->get_self_drive_request_date_value( 'hr_port_transfer_return_date', array( 'hr_port_transfer_return_date_display', 'ovabrw_pickoff_date', 'ovabrw_dropoff_date', 'dropoff_date', 'checkout_date' ) );
		$passengers = isset( $_POST['hr_port_transfer_passengers'] ) ? (int) wp_unslash( $_POST['hr_port_transfer_passengers'] ) : 0;
		$route_label = $this->get_posted_text_value( 'hr_port_transfer_route_label' );
		$from = $this->get_posted_text_value( 'hr_port_transfer_from' );
		$to = $this->get_posted_text_value( 'hr_port_transfer_to' );
		$transfer_type = isset( $_POST['hr_port_transfer_type'] ) ? sanitize_key( wp_unslash( $_POST['hr_port_transfer_type'] ) ) : '';
		$transfer_time = $this->get_posted_text_value( 'hr_port_transfer_time' );
		$flight_number = $this->get_posted_text_value( 'hr_port_transfer_flight_number' );
		$hotel_name = $this->get_posted_text_value( 'hr_port_transfer_hotel_name' );
		$return_time = $this->get_posted_text_value( 'hr_port_transfer_return_time' );
		$dropoff = $this->get_posted_text_value( 'hr_port_transfer_dropoff' );
		$return_dropoff = $this->get_posted_text_value( 'hr_port_transfer_return_dropoff' );

		if ( $passengers < 1 && isset( $_POST['hr_port_transfer_passengers_display'] ) ) {
			$passengers = (int) wp_unslash( $_POST['hr_port_transfer_passengers_display'] );
		}

		if ( preg_match( '/passenger|pax|adult|guest|number.*passenger|no.*passenger/i', $field_text ) && $passengers > 0 ) {
			return (string) $passengers;
		}

		if ( preg_match( '/^from$|\bfrom\b|pick[\s_-]*up\s+from|pickup\s+from/i', $field_text ) && $from ) {
			return $from;
		}

		if ( ! preg_match( '/return/i', $field_text ) && preg_match( '/^to$|\bto\b|drop[\s_-]*off\s+to|dropoff\s+to/i', $field_text ) && $to ) {
			return $to;
		}

		if ( preg_match( '/flight/i', $field_text ) && $flight_number ) {
			return $flight_number;
		}

		if ( preg_match( '/hotel\s+name|name\s+of\s+hotel|hotel|resort/i', $field_text ) && $hotel_name ) {
			return $hotel_name;
		}

		if ( preg_match( '/route|destination|capacity|port.*transfer|selection/i', $field_text ) && $route_label ) {
			return $route_label;
		}

		if ( preg_match( '/transfer.*type|trip.*type|roundtrip|one.*way/i', $field_text ) && $transfer_type ) {
			if ( ! in_array( $type, array( 'select', 'radio', 'checkbox' ), true ) ) {
				return 'roundtrip' === $transfer_type ? __( 'Round-trip Transfer', 'hotel-rates' ) : __( 'One-way Transfer', 'hotel-rates' );
			}

			return $this->get_self_drive_matching_custom_field_option( $field, $type, 'roundtrip' === $transfer_type ? 'round' : 'one' );
		}

		if ( preg_match( '/return.*date|drop[\s_-]*off.*date|dropoff.*date|end.*date/i', $field_text ) && $return_date ) {
			return $return_date;
		}

		if ( preg_match( '/transfer.*date|arrival.*date|pick[\s_-]*up.*date|pickup.*date|start.*date|booking.*date/i', $field_text ) && $transfer_date ) {
			return $transfer_date;
		}

		if ( preg_match( '/return.*time|drop[\s_-]*off.*time|dropoff.*time|end.*time/i', $field_text ) && $return_time ) {
			return $return_time;
		}

		if ( preg_match( '/arrival.*time|transfer.*time|pick[\s_-]*up.*time|pickup.*time|time/i', $field_text ) && $transfer_time ) {
			return $transfer_time;
		}

		if ( preg_match( '/return.*drop|return.*location|return.*address/i', $field_text ) && $return_dropoff ) {
			return $return_dropoff;
		}

		if ( preg_match( '/drop[\s_-]*off|dropoff|hotel|resort|address|location/i', $field_text ) && $dropoff ) {
			return $dropoff;
		}

		return $this->get_first_self_drive_custom_field_option( $field, $type );
	}

	public function validate_port_transfer_add_to_cart( $passed, $product_id, $quantity ) {
		if ( ! $this->get_linked_product_port_transfer_id( $product_id ) ) {
			return $passed;
		}

		$selection = $this->get_port_transfer_selection_from_request( $product_id );

		if ( ! $selection ) {
			return false;
		}

		return $passed;
	}

	public function prepare_destination_tour_guestless_add_to_cart_request( $passed, $product_id, $quantity ) {
		if ( ! $this->get_linked_product_destination_tour_id( $product_id ) ) {
			return $passed;
		}

		$guest_count = isset( $_POST['hr_destination_tour_guests'] ) ? (int) wp_unslash( $_POST['hr_destination_tour_guests'] ) : 0;
		if ( $guest_count < 1 && isset( $_POST['hr_destination_tour_guests_display'] ) ) {
			$guest_count = (int) wp_unslash( $_POST['hr_destination_tour_guests_display'] );
		}
		$guest_count = max( 1, $guest_count );
		$_POST['hr_destination_tour_guests'] = (string) $guest_count;
		$_REQUEST['hr_destination_tour_guests'] = (string) $guest_count;

		$hotel_name = $this->get_posted_text_value( 'hr_destination_tour_hotel_name' );
		if ( '' === $hotel_name ) {
			$hotel_name = $this->get_posted_text_value( 'hr_destination_tour_hotel_name_display' );
		}
		if ( '' !== $hotel_name ) {
			$_POST['hr_destination_tour_hotel_name'] = $hotel_name;
			$_REQUEST['hr_destination_tour_hotel_name'] = $hotel_name;
		}

		$pickup_time = $this->get_posted_text_value( 'hr_destination_tour_pickup_time' );
		if ( '' === $pickup_time ) {
			$pickup_time = $this->get_posted_text_value( 'hr_destination_tour_pickup_time_display' );
		}
		if ( '' !== $pickup_time ) {
			$_POST['hr_destination_tour_pickup_time'] = $pickup_time;
			$_REQUEST['hr_destination_tour_pickup_time'] = $pickup_time;
		}

		$guest_fallbacks = array(
			'ovabrw_adults'    => (string) $guest_count,
			'ovabrw_childrens' => '0',
			'ovabrw_babies'    => '0',
			'ovabrw_quantity'  => '1',
			'quantity'         => '1',
		);

		foreach ( $guest_fallbacks as $key => $value ) {
			$_POST[ $key ] = $value;
			$_REQUEST[ $key ] = $value;
		}

		$destination_tour_total = isset( $_POST['hr_destination_tour_total'] ) ? (float) wp_unslash( $_POST['hr_destination_tour_total'] ) : 0.0;
		if ( $destination_tour_total > 0 ) {
			foreach ( array( 'ovabrw_total', 'ovabrw_total_price', 'ovabrw_amount', 'total_price' ) as $key ) {
				$_POST[ $key ] = (string) $destination_tour_total;
				$_REQUEST[ $key ] = (string) $destination_tour_total;
			}
		}

		$rental_date = $this->get_self_drive_request_date_value( 'hr_destination_tour_rental_date', array( 'hr_destination_tour_rental_date_display', 'ovabrw_pickup_date', 'pickup_date', 'checkin_date', 'tour_date', 'rental_date', 'booking_date' ) );

		if ( '' !== $rental_date ) {
			$_POST['hr_destination_tour_rental_date'] = $rental_date;
			$_REQUEST['hr_destination_tour_rental_date'] = $rental_date;
			$_POST['ovabrw_pickup_date'] = $rental_date;
			$_REQUEST['ovabrw_pickup_date'] = $rental_date;
		}

		$this->populate_destination_tour_legacy_custom_checkout_fields( $product_id );

		return $passed;
	}

	private function populate_destination_tour_legacy_custom_checkout_fields( $product_id ) {
		if ( ! function_exists( 'ovabrw_get_list_field_checkout' ) ) {
			return;
		}

		$fields = ovabrw_get_list_field_checkout( $product_id );
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return;
		}

		$posted_custom_fields = $fields;

		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) || ( isset( $field['enabled'] ) && 'on' !== $field['enabled'] ) ) {
				continue;
			}

			$type = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';
			$value = $this->get_destination_tour_legacy_custom_checkout_field_value( $key, $field, $type );

			if ( isset( $posted_custom_fields[ $key ]['required'] ) ) {
				$posted_custom_fields[ $key ]['required'] = '';
			}

			if ( 'file' === $type ) {
				continue;
			}

			if ( 'checkbox' === $type ) {
				$value = $value ? $value : $this->get_first_self_drive_custom_field_option( $field, 'checkbox' );
				if ( $value && empty( $_POST[ $key ] ) ) {
					$_POST[ $key ] = array( $value => $value );
					$_REQUEST[ $key ] = $_POST[ $key ];
				}
				continue;
			}

			if ( '' === (string) $value ) {
				$value = __( 'Destination tour', 'hotel-rates' );
			}

			if ( empty( $_POST[ $key ] ) ) {
				$_POST[ $key ] = $value;
				$_REQUEST[ $key ] = $value;
			}
		}

		$_POST['data_custom_ckf'] = wp_json_encode( $posted_custom_fields );
		$_REQUEST['data_custom_ckf'] = $_POST['data_custom_ckf'];
	}

	private function get_destination_tour_legacy_custom_checkout_field_value( $key, $field, $type ) {
		$field_text = $this->get_self_drive_custom_field_search_text( $key, $field );
		$rental_date = $this->get_self_drive_request_date_value( 'hr_destination_tour_rental_date', array( 'hr_destination_tour_rental_date_display', 'ovabrw_pickup_date', 'pickup_date', 'checkin_date', 'tour_date', 'rental_date', 'booking_date' ) );
		$guests = isset( $_POST['hr_destination_tour_guests'] ) ? (int) wp_unslash( $_POST['hr_destination_tour_guests'] ) : 0;
		$hotel_name = $this->get_posted_text_value( 'hr_destination_tour_hotel_name' );
		$pickup_time = $this->get_posted_text_value( 'hr_destination_tour_pickup_time' );
		$destination_label = $this->get_posted_text_value( 'hr_destination_tour_destination_label' );
		$destination_duration = $this->get_posted_text_value( 'hr_destination_tour_destination_duration' );
		$rental_rate = isset( $_POST['hr_destination_tour_rental_rate'] ) ? (float) wp_unslash( $_POST['hr_destination_tour_rental_rate'] ) : 0.0;
		$pickup_addon_label = $this->get_posted_text_value( 'hr_destination_tour_pickup_addon_label' );
		$pickup_addon_rate = isset( $_POST['hr_destination_tour_pickup_addon_rate'] ) ? (float) wp_unslash( $_POST['hr_destination_tour_pickup_addon_rate'] ) : 0.0;

		if ( $guests < 1 && isset( $_POST['hr_destination_tour_guests_display'] ) ) {
			$guests = (int) wp_unslash( $_POST['hr_destination_tour_guests_display'] );
		}
		if ( '' === $hotel_name ) {
			$hotel_name = $this->get_posted_text_value( 'hr_destination_tour_hotel_name_display' );
		}
		if ( '' === $pickup_time ) {
			$pickup_time = $this->get_posted_text_value( 'hr_destination_tour_pickup_time_display' );
		}

		if ( preg_match( '/guest|pax|adult|number.*guest|no.*guest/i', $field_text ) && $guests > 0 ) {
			return (string) $guests;
		}

		if ( preg_match( '/hotel|resort|accommodation|lodging/i', $field_text ) && $hotel_name ) {
			return $hotel_name;
		}

		if ( preg_match( '/rental.*date|pick[\s_-]*up.*date|pickup.*date|start.*date|tour.*date|booking.*date/i', $field_text ) && $rental_date ) {
			return $rental_date;
		}

		if ( preg_match( '/time|pick[\s_-]*up|pickup/i', $field_text ) && $pickup_time ) {
			return $pickup_time;
		}

		if ( preg_match( '/duration|hour/i', $field_text ) && $destination_duration ) {
			return $destination_duration;
		}

		if ( preg_match( '/destination|tourist|tour/i', $field_text ) && $destination_label ) {
			return $destination_label;
		}

		if ( preg_match( '/outside|dumaguete|add[\s_-]*on|addon|pick[\s_-]*up.*outside/i', $field_text ) && $pickup_addon_label ) {
			if ( ! in_array( $type, array( 'select', 'radio', 'checkbox' ), true ) ) {
				return sprintf(
					'%1$s - %2$s',
					$pickup_addon_label,
					$this->format_port_transfer_price_text( $pickup_addon_rate )
				);
			}

			return $this->get_self_drive_matching_custom_field_option( $field, $type, $pickup_addon_label );
		}

		if ( preg_match( '/rate|price|amount|total/i', $field_text ) && $rental_rate > 0 ) {
			return $this->format_port_transfer_price_text( $rental_rate );
		}

		return $this->get_first_self_drive_custom_field_option( $field, $type );
	}

	public function validate_destination_tour_add_to_cart( $passed, $product_id, $quantity ) {
		if ( ! $this->get_linked_product_destination_tour_id( $product_id ) ) {
			return $passed;
		}

		$selection = $this->get_destination_tour_selection_from_request( $product_id );

		if ( ! $selection ) {
			return false;
		}

		return $passed;
	}

	public function prepare_pickup_dropoff_guestless_add_to_cart_request( $passed, $product_id, $quantity ) {
		if ( ! $this->get_linked_product_pickup_dropoff_service_id( $product_id ) ) {
			return $passed;
		}

		$passenger_count = isset( $_POST['hr_pickup_dropoff_passengers'] ) ? (int) wp_unslash( $_POST['hr_pickup_dropoff_passengers'] ) : 0;
		if ( $passenger_count < 1 && isset( $_POST['hr_pickup_dropoff_passengers_display'] ) ) {
			$passenger_count = (int) wp_unslash( $_POST['hr_pickup_dropoff_passengers_display'] );
		}
		$passenger_count = max( 1, $passenger_count );
		$_POST['hr_pickup_dropoff_passengers'] = (string) $passenger_count;
		$_REQUEST['hr_pickup_dropoff_passengers'] = (string) $passenger_count;

		foreach ( array(
			'ovabrw_adults'    => (string) $passenger_count,
			'ovabrw_childrens' => '0',
			'ovabrw_babies'    => '0',
			'ovabrw_quantity'  => '1',
			'quantity'         => '1',
		) as $key => $value ) {
			$_POST[ $key ] = $value;
			$_REQUEST[ $key ] = $value;
		}

		foreach ( array(
			'hr_pickup_dropoff_from'        => 'hr_pickup_dropoff_from_display',
			'hr_pickup_dropoff_to'          => 'hr_pickup_dropoff_to_display',
			'hr_pickup_dropoff_pickup_time' => 'hr_pickup_dropoff_pickup_time_display',
			'hr_pickup_dropoff_hotel_name'  => 'hr_pickup_dropoff_hotel_name_display',
		) as $target_key => $display_key ) {
			$value = $this->get_posted_text_value( $target_key );
			if ( '' === $value ) {
				$value = $this->get_posted_text_value( $display_key );
			}
			if ( '' !== $value ) {
				$_POST[ $target_key ] = $value;
				$_REQUEST[ $target_key ] = $value;
			}
		}

		$pickup_dropoff_total = isset( $_POST['hr_pickup_dropoff_total'] ) ? (float) wp_unslash( $_POST['hr_pickup_dropoff_total'] ) : 0.0;
		if ( $pickup_dropoff_total > 0 ) {
			foreach ( array( 'ovabrw_total', 'ovabrw_total_price', 'ovabrw_amount', 'total_price' ) as $key ) {
				$_POST[ $key ] = (string) $pickup_dropoff_total;
				$_REQUEST[ $key ] = (string) $pickup_dropoff_total;
			}
		}

		$rental_date = $this->get_self_drive_request_date_value( 'hr_pickup_dropoff_rental_date', array( 'hr_pickup_dropoff_rental_date_display', 'ovabrw_pickup_date', 'pickup_date', 'checkin_date', 'tour_date', 'rental_date', 'booking_date' ) );
		if ( '' !== $rental_date ) {
			$_POST['hr_pickup_dropoff_rental_date'] = $rental_date;
			$_REQUEST['hr_pickup_dropoff_rental_date'] = $rental_date;
			$_POST['ovabrw_pickup_date'] = $rental_date;
			$_REQUEST['ovabrw_pickup_date'] = $rental_date;
		}

		$this->populate_pickup_dropoff_legacy_custom_checkout_fields( $product_id );

		return $passed;
	}

	private function populate_pickup_dropoff_legacy_custom_checkout_fields( $product_id ) {
		if ( ! function_exists( 'ovabrw_get_list_field_checkout' ) ) {
			return;
		}

		$fields = ovabrw_get_list_field_checkout( $product_id );
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return;
		}

		$posted_custom_fields = $fields;

		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) || ( isset( $field['enabled'] ) && 'on' !== $field['enabled'] ) ) {
				continue;
			}

			$type = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';
			$value = $this->get_pickup_dropoff_legacy_custom_checkout_field_value( $key, $field, $type );

			if ( isset( $posted_custom_fields[ $key ]['required'] ) ) {
				$posted_custom_fields[ $key ]['required'] = '';
			}

			if ( 'file' === $type ) {
				continue;
			}

			if ( 'checkbox' === $type ) {
				$value = $value ? $value : $this->get_first_self_drive_custom_field_option( $field, 'checkbox' );
				if ( $value && empty( $_POST[ $key ] ) ) {
					$_POST[ $key ] = array( $value => $value );
					$_REQUEST[ $key ] = $_POST[ $key ];
				}
				continue;
			}

			if ( '' === (string) $value ) {
				$value = __( 'Pick-up and drop-off service', 'hotel-rates' );
			}

			if ( empty( $_POST[ $key ] ) ) {
				$_POST[ $key ] = $value;
				$_REQUEST[ $key ] = $value;
			}
		}

		$_POST['data_custom_ckf'] = wp_json_encode( $posted_custom_fields );
		$_REQUEST['data_custom_ckf'] = $_POST['data_custom_ckf'];
	}

	private function get_pickup_dropoff_legacy_custom_checkout_field_value( $key, $field, $type ) {
		$field_text = $this->get_self_drive_custom_field_search_text( $key, $field );
		$rental_date = $this->get_self_drive_request_date_value( 'hr_pickup_dropoff_rental_date', array( 'hr_pickup_dropoff_rental_date_display', 'ovabrw_pickup_date', 'pickup_date', 'checkin_date', 'tour_date', 'rental_date', 'booking_date' ) );
		$passengers = isset( $_POST['hr_pickup_dropoff_passengers'] ) ? (int) wp_unslash( $_POST['hr_pickup_dropoff_passengers'] ) : 0;
		$from = $this->get_posted_text_value( 'hr_pickup_dropoff_from' );
		$to = $this->get_posted_text_value( 'hr_pickup_dropoff_to' );
		$route_label = $this->get_posted_text_value( 'hr_pickup_dropoff_route_label' );
		$pickup_time = $this->get_posted_text_value( 'hr_pickup_dropoff_pickup_time' );
		$hotel_name = $this->get_posted_text_value( 'hr_pickup_dropoff_hotel_name' );
		$unit_price = isset( $_POST['hr_pickup_dropoff_unit_price'] ) ? (float) wp_unslash( $_POST['hr_pickup_dropoff_unit_price'] ) : 0.0;
		$addons = isset( $_POST['hr_pickup_dropoff_addons'] ) ? json_decode( wp_unslash( $_POST['hr_pickup_dropoff_addons'] ), true ) : array();
		$addons = is_array( $addons ) ? $addons : array();

		if ( $passengers < 1 && isset( $_POST['hr_pickup_dropoff_passengers_display'] ) ) {
			$passengers = (int) wp_unslash( $_POST['hr_pickup_dropoff_passengers_display'] );
		}

		if ( preg_match( '/passenger|pax|adult|guest|number.*passenger|no.*passenger/i', $field_text ) && $passengers > 0 ) {
			return (string) $passengers;
		}

		if ( preg_match( '/hotel|resort|accommodation|lodging/i', $field_text ) && $hotel_name ) {
			return $hotel_name;
		}

		if ( preg_match( '/drop[\s_-]*off|dropoff|destination|\bto\b/i', $field_text ) && $to ) {
			return $to;
		}

		if ( preg_match( '/from|pick[\s_-]*up.*location|pickup.*location|origin/i', $field_text ) && $from ) {
			return $from;
		}

		if ( preg_match( '/route|location|place/i', $field_text ) && $route_label ) {
			return $route_label;
		}

		if ( preg_match( '/pick[\s_-]*up.*time|pickup.*time|time/i', $field_text ) && $pickup_time ) {
			return $pickup_time;
		}

		if ( preg_match( '/rental.*date|pick[\s_-]*up.*date|pickup.*date|start.*date|booking.*date/i', $field_text ) && $rental_date ) {
			return $rental_date;
		}

		if ( preg_match( '/add[\s_-]*on|addon|surcharge/i', $field_text ) && ! empty( $addons ) ) {
			return implode( ', ', array_filter( array_map( static function ( $addon ) {
				return is_array( $addon ) ? trim( (string) ( $addon['label'] ?? '' ) ) : '';
			}, $addons ) ) );
		}

		if ( preg_match( '/rate|price|amount|total/i', $field_text ) && $unit_price > 0 ) {
			return $this->format_port_transfer_price_text( $unit_price );
		}

		return $this->get_first_self_drive_custom_field_option( $field, $type );
	}

	public function validate_pickup_dropoff_add_to_cart( $passed, $product_id, $quantity ) {
		if ( ! $this->get_linked_product_pickup_dropoff_service_id( $product_id ) ) {
			return $passed;
		}

		$selection = $this->get_pickup_dropoff_selection_from_request( $product_id );

		if ( ! $selection ) {
			return false;
		}

		return $passed;
	}

	/**
	 * Populate Ova's hidden legacy checkout fields for the compact Day Tour form.
	 *
	 * The Tripgo fields are intentionally removed for compact tour forms, but
	 * Ova still validates their submitted metadata. Keep this bridge strictly
	 * scoped to a matching Day Tour rate so Joiners and other modules are not
	 * changed.
	 */
	public function prepare_day_tour_add_to_cart_request( $passed, $product_id, $quantity ) {
		$day_tour_rate_id    = $this->get_linked_product_day_tour_rate_id( $product_id );
		$posted_tour_rate_id = isset( $_POST['hr_tour_rate_id'] ) ? absint( wp_unslash( $_POST['hr_tour_rate_id'] ) ) : 0;

		if ( ! $day_tour_rate_id || $posted_tour_rate_id !== $day_tour_rate_id ) {
			return $passed;
		}

		$day_tour_rate = Hotel_Rates_DB::get_tour_rate_by_id( $day_tour_rate_id );

		if ( ! $day_tour_rate || 'Day Tour' !== (string) $day_tour_rate->type ) {
			return $passed;
		}

		$this->populate_day_tour_legacy_custom_checkout_fields( $product_id );

		return $passed;
	}

	private function populate_day_tour_legacy_custom_checkout_fields( $product_id ) {
		if ( ! function_exists( 'ovabrw_get_list_field_checkout' ) ) {
			return;
		}

		$fields = ovabrw_get_list_field_checkout( $product_id );

		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return;
		}

		$posted_custom_fields = $fields;

		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) || ( isset( $field['enabled'] ) && 'on' !== $field['enabled'] ) ) {
				continue;
			}

			$type  = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';
			$value = $this->get_day_tour_legacy_custom_checkout_field_value( $key, $field, $type );

			if ( isset( $posted_custom_fields[ $key ]['required'] ) ) {
				$posted_custom_fields[ $key ]['required'] = '';
			}

			if ( isset( $posted_custom_fields[ $key ]['enabled'] ) ) {
				$posted_custom_fields[ $key ]['enabled'] = '';
			}

			if ( isset( $posted_custom_fields[ $key ]['default'] ) ) {
				$posted_custom_fields[ $key ]['default'] = '';
			}

			if ( 'file' === $type || '' === (string) $value ) {
				continue;
			}

			if ( 'checkbox' === $type ) {
				if ( $value && empty( $_POST[ $key ] ) ) {
					$_POST[ $key ]    = array( $value => $value );
					$_REQUEST[ $key ] = $_POST[ $key ];
				}

				continue;
			}

			if ( empty( $_POST[ $key ] ) ) {
				$_POST[ $key ]    = $value;
				$_REQUEST[ $key ] = $value;
			}
		}

		$_POST['data_custom_ckf']    = wp_json_encode( $posted_custom_fields );
		$_REQUEST['data_custom_ckf'] = $_POST['data_custom_ckf'];
	}

	private function get_day_tour_legacy_custom_checkout_field_value( $key, $field, $type ) {
		$field_text = $this->get_day_tour_legacy_custom_field_search_text( $key, $field );
		$guest_count     = $this->get_tour_paid_guest_count_from_request();
		$tour_date       = $this->get_self_drive_request_date_value( 'ovabrw_pickup_date', array( 'pickup_date', 'checkin_date', 'tour_date', 'booking_date' ) );

		if ( preg_match( '/hotel|flight|resort|accommodation|lodging|pick[\s_-]*up.*information|pickup.*information/i', $field_text ) ) {
			return $this->get_day_tour_optional_legacy_field_value();
		}

		if ( preg_match( '/pick[\s_-]*up.*time|pickup.*time/i', $field_text ) ) {
			return $this->get_day_tour_optional_legacy_field_value();
		}

		if ( preg_match( '/guest|pax|adult|number.*guest|no.*guest/i', $field_text ) && $guest_count > 0 ) {
			return $this->get_day_tour_legacy_compatible_field_value( $field, $type, (string) $guest_count );
		}

		if ( preg_match( '/pick[\s_-]*up.*date|pickup.*date|start.*date|tour.*date|booking.*date/i', $field_text ) && $tour_date ) {
			return $this->get_day_tour_legacy_compatible_field_value( $field, $type, $tour_date );
		}

		return '';
	}

	/**
	 * Keep the two configurable Day Tour fields optional in Ova's validator.
	 *
	 * This marker never matches an Ova select/radio/checkbox option, so it has
	 * no custom-field price. It is removed from Day Tour cart data immediately
	 * after Ova finishes validation.
	 */
	private function get_day_tour_optional_legacy_field_value() {
		return '__hr_day_tour_optional_field__';
	}

	private function get_day_tour_legacy_custom_field_search_text( $key, $field ) {
		$parts = array(
			$key,
			$field['name'] ?? '',
			$field['label'] ?? '',
			$field['placeholder'] ?? '',
		);

		return strtolower( implode( ' ', array_map( 'wp_strip_all_tags', array_map( 'strval', $parts ) ) ) );
	}

	private function get_day_tour_legacy_compatible_field_value( $field, $type, $value ) {
		if ( ! in_array( $type, array( 'select', 'radio', 'checkbox' ), true ) ) {
			return $value;
		}

		$needle = strtolower( trim( wp_strip_all_tags( (string) $value ) ) );

		if ( '' === $needle ) {
			return '';
		}

		foreach ( $this->get_motorbike_custom_field_options( $field, $type ) as $option_value => $option_label ) {
			$option_value_text = strtolower( trim( wp_strip_all_tags( (string) $option_value ) ) );
			$option_label_text = strtolower( trim( wp_strip_all_tags( (string) $option_label ) ) );

			if ( $needle === $option_value_text || $needle === $option_label_text ) {
				return (string) $option_value;
			}
		}

		return '';
	}

        public function add_cart_item_data( $cart_item_data, $product_id, $quantity ) {
                $package_selection = $this->get_package_selection_from_request( $product_id );
                if ( $package_selection ) {
                        $cart_item_data['hr_package_deal_id']        = $package_selection['package_id'];
                        $cart_item_data['hr_package_name']           = $package_selection['package_name'];
                        $cart_item_data['hr_package_days']           = $package_selection['days'];
                        $cart_item_data['hr_package_nights']         = $package_selection['nights'];
                        $cart_item_data['hr_package_airport_transfer_fee'] = $package_selection['airport_transfer_fee'];
                        $cart_item_data['hr_package_seaport_transfer_fee'] = $package_selection['seaport_transfer_fee'];
                        $cart_item_data['hr_package_total']          = $package_selection['total'];
                        $cart_item_data['hr_package_hotels']         = $package_selection['hotels'];
                        $cart_item_data['hr_package_activities']     = $package_selection['activities'];
                        $cart_item_data['hr_package_selection_payload'] = $package_selection['payload'];
                        return $cart_item_data;
                }

                $motorbike_selection = $this->get_motorbike_selection_from_request( $product_id );
                if ( $motorbike_selection ) {
                        $cart_item_data['hr_motorbike_rental_id']      = $motorbike_selection['rental_id'];
                        $cart_item_data['hr_motorbike_rental_name']    = $motorbike_selection['rental_name'];
                        $cart_item_data['hr_motorbike_items']          = $motorbike_selection['items'];
                        $cart_item_data['hr_motorbike_days']           = $motorbike_selection['days'];
                        $cart_item_data['hr_motorbike_start_time']     = $motorbike_selection['start_time'];
                        $cart_item_data['hr_motorbike_handover_option'] = $motorbike_selection['handover_option'];
                        $cart_item_data['hr_motorbike_handover_label'] = $motorbike_selection['handover_label'];
                        $cart_item_data['hr_motorbike_delivery_address'] = $motorbike_selection['delivery_address'];
                        $cart_item_data['hr_motorbike_delivery_fee']   = $motorbike_selection['delivery_fee'];
                        $cart_item_data['hr_motorbike_total']          = $motorbike_selection['total'];
                        $cart_item_data['hr_motorbike_selection_payload'] = $motorbike_selection['payload'];
                        return $cart_item_data;
                }

                $boat_selection = $this->get_boat_selection_from_request( $product_id );
                if ( $boat_selection ) {
                        $cart_item_data['hr_boat_rental_id']        = $boat_selection['rental_id'];
                        $cart_item_data['hr_boat_rental_name']      = $boat_selection['rental_name'];
                        $cart_item_data['hr_boat_rental_date']      = $boat_selection['rental_date'];
                        $cart_item_data['hr_boat_passengers']       = $boat_selection['passengers'];
                        $cart_item_data['hr_boat_rate_index']       = $boat_selection['rate_index'];
                        $cart_item_data['hr_boat_rate_label']       = $boat_selection['rate_label'];
                        $cart_item_data['hr_boat_min_pax']          = $boat_selection['min_pax'];
                        $cart_item_data['hr_boat_max_pax']          = $boat_selection['max_pax'];
                        $cart_item_data['hr_boat_boarding_time']    = $boat_selection['boarding_time'];
                        $cart_item_data['hr_boat_base_total']       = $boat_selection['base_total'];
                        $cart_item_data['hr_boat_upgrade_fee_selected'] = $boat_selection['upgrade_fee_selected'];
                        $cart_item_data['hr_boat_upgrade_fee_label'] = $boat_selection['upgrade_fee_label'];
                        $cart_item_data['hr_boat_upgrade_fee_amount'] = $boat_selection['upgrade_fee_amount'];
                        $cart_item_data['hr_boat_total']            = $boat_selection['total'];
                        $cart_item_data['hr_boat_credit_card_fee_percent'] = $boat_selection['credit_card_fee_percent'];
                        $cart_item_data['hr_boat_selection_payload'] = $boat_selection['payload'];
                        return $cart_item_data;
                }

                $self_drive_selection = $this->get_self_drive_selection_from_request( $product_id, true );
                if ( $self_drive_selection ) {
                        $cart_item_data['hr_self_drive_rental_id']      = $self_drive_selection['rental_id'];
                        $cart_item_data['hr_self_drive_rental_name']    = $self_drive_selection['rental_name'];
                        $cart_item_data['hr_self_drive_items']          = $self_drive_selection['items'];
                        $cart_item_data['hr_self_drive_rental_date']    = $self_drive_selection['rental_date'];
                        $cart_item_data['hr_self_drive_return_date']    = $self_drive_selection['return_date'];
                        $cart_item_data['hr_self_drive_guests']         = $self_drive_selection['guests'];
                        $cart_item_data['hr_self_drive_hotel_name']     = $self_drive_selection['hotel_name'];
                        $cart_item_data['hr_self_drive_days']           = $self_drive_selection['days'];
                        $cart_item_data['hr_self_drive_start_time']     = $self_drive_selection['start_time'];
                        $cart_item_data['hr_self_drive_return_time']    = $self_drive_selection['return_time'];
                        $cart_item_data['hr_self_drive_handover_option'] = $self_drive_selection['handover_option'];
                        $cart_item_data['hr_self_drive_handover_label'] = $self_drive_selection['handover_label'];
                        $cart_item_data['hr_self_drive_delivery_address'] = $self_drive_selection['delivery_address'];
                        $cart_item_data['hr_self_drive_delivery_fee']   = $self_drive_selection['delivery_fee'];
                        $cart_item_data['hr_self_drive_total']          = $self_drive_selection['total'];
                        $cart_item_data['hr_self_drive_credit_card_fee_percent'] = $self_drive_selection['credit_card_fee_percent'];
                        $cart_item_data['hr_self_drive_selection_payload'] = $self_drive_selection['payload'];
                        return $cart_item_data;
                }

                $destination_tour_selection = $this->get_destination_tour_selection_from_request( $product_id );
                if ( $destination_tour_selection ) {
                        $cart_item_data['hr_destination_tour_id']          = $destination_tour_selection['tour_id'];
                        $cart_item_data['hr_destination_tour_name']        = $destination_tour_selection['tour_name'];
                        $cart_item_data['hr_destination_tour_rental_date'] = $destination_tour_selection['rental_date'];
                        $cart_item_data['hr_destination_tour_guests']      = $destination_tour_selection['guests'];
                        $cart_item_data['hr_destination_tour_hotel_name']  = $destination_tour_selection['hotel_name'];
                        $cart_item_data['hr_destination_tour_pickup_time'] = $destination_tour_selection['pickup_time'];
                        $cart_item_data['hr_destination_tour_destination_index'] = $destination_tour_selection['destination_index'];
                        $cart_item_data['hr_destination_tour_destination_label'] = $destination_tour_selection['destination_label'];
                        $cart_item_data['hr_destination_tour_destination_duration'] = $destination_tour_selection['destination_duration'];
                        $cart_item_data['hr_destination_tour_rental_rate'] = $destination_tour_selection['rental_rate'];
                        $cart_item_data['hr_destination_tour_pickup_addon_index'] = $destination_tour_selection['pickup_addon_index'];
                        $cart_item_data['hr_destination_tour_pickup_addon_label'] = $destination_tour_selection['pickup_addon_label'];
                        $cart_item_data['hr_destination_tour_pickup_addon_rate'] = $destination_tour_selection['pickup_addon_rate'];
                        $cart_item_data['hr_destination_tour_total']       = $destination_tour_selection['total'];
                        $cart_item_data['hr_destination_tour_credit_card_fee_percent'] = $destination_tour_selection['credit_card_fee_percent'];
                        $cart_item_data['hr_destination_tour_selection_payload'] = $destination_tour_selection['payload'];
                        return $cart_item_data;
                }

                $pickup_dropoff_selection = $this->get_pickup_dropoff_selection_from_request( $product_id );
                if ( $pickup_dropoff_selection ) {
                        $cart_item_data['hr_pickup_dropoff_service_id'] = $pickup_dropoff_selection['service_id'];
                        $cart_item_data['hr_pickup_dropoff_service_name'] = $pickup_dropoff_selection['service_name'];
                        $cart_item_data['hr_pickup_dropoff_rental_date'] = $pickup_dropoff_selection['rental_date'];
                        $cart_item_data['hr_pickup_dropoff_passengers'] = $pickup_dropoff_selection['passengers'];
                        $cart_item_data['hr_pickup_dropoff_from'] = $pickup_dropoff_selection['from'];
                        $cart_item_data['hr_pickup_dropoff_to'] = $pickup_dropoff_selection['to'];
                        $cart_item_data['hr_pickup_dropoff_route_index'] = $pickup_dropoff_selection['route_index'];
                        $cart_item_data['hr_pickup_dropoff_route_label'] = $pickup_dropoff_selection['route_label'];
                        $cart_item_data['hr_pickup_dropoff_pickup_time'] = $pickup_dropoff_selection['pickup_time'];
                        $cart_item_data['hr_pickup_dropoff_hotel_name'] = $pickup_dropoff_selection['hotel_name'];
                        $cart_item_data['hr_pickup_dropoff_unit_price'] = $pickup_dropoff_selection['unit_price'];
                        $cart_item_data['hr_pickup_dropoff_addons'] = $pickup_dropoff_selection['addons'];
                        $cart_item_data['hr_pickup_dropoff_addons_total'] = $pickup_dropoff_selection['addons_total'];
                        $cart_item_data['hr_pickup_dropoff_total'] = $pickup_dropoff_selection['total'];
                        $cart_item_data['hr_pickup_dropoff_selection_payload'] = $pickup_dropoff_selection['payload'];
                        return $cart_item_data;
                }

                $port_transfer_selection = $this->get_port_transfer_selection_from_request( $product_id );
                if ( $port_transfer_selection ) {
                        $cart_item_data['hr_port_transfer_id']          = $port_transfer_selection['transfer_id'];
                        $cart_item_data['hr_port_transfer_name']        = $port_transfer_selection['transfer_name'];
                        $cart_item_data['hr_port_transfer_date']        = $port_transfer_selection['transfer_date'];
                        $cart_item_data['hr_port_transfer_passengers']  = $port_transfer_selection['passengers'];
                        $cart_item_data['hr_port_transfer_route_index'] = $port_transfer_selection['route_index'];
                        $cart_item_data['hr_port_transfer_route_label'] = $port_transfer_selection['route_label'];
                        $cart_item_data['hr_port_transfer_from']        = $port_transfer_selection['from'];
                        $cart_item_data['hr_port_transfer_to']          = $port_transfer_selection['to'];
                        $cart_item_data['hr_port_transfer_direction']   = $port_transfer_selection['direction'];
                        $cart_item_data['hr_port_transfer_type']        = $port_transfer_selection['transfer_type'];
                        $cart_item_data['hr_port_transfer_type_label']  = $port_transfer_selection['transfer_type_label'];
                        $cart_item_data['hr_port_transfer_time']        = $port_transfer_selection['transfer_time'];
                        $cart_item_data['hr_port_transfer_flight_number'] = $port_transfer_selection['flight_number'];
                        $cart_item_data['hr_port_transfer_hotel_name']  = $port_transfer_selection['hotel_name'];
                        $cart_item_data['hr_port_transfer_dropoff']     = $port_transfer_selection['dropoff'];
                        $cart_item_data['hr_port_transfer_return_date'] = $port_transfer_selection['return_date'];
                        $cart_item_data['hr_port_transfer_return_time'] = $port_transfer_selection['return_time'];
                        $cart_item_data['hr_port_transfer_return_dropoff'] = $port_transfer_selection['return_dropoff'];
                        $cart_item_data['hr_port_transfer_addons']      = $port_transfer_selection['addons'];
                        $cart_item_data['hr_port_transfer_addons_total'] = $port_transfer_selection['addons_total'];
                        $cart_item_data['hr_port_transfer_unit_price']  = $port_transfer_selection['unit_price'];
                        $cart_item_data['hr_port_transfer_total']       = $port_transfer_selection['total'];
                        $cart_item_data['hr_port_transfer_convenience_fee_percent'] = $port_transfer_selection['convenience_fee_percent'];
                        $cart_item_data['hr_port_transfer_selection_payload'] = $port_transfer_selection['payload'];
                        return $cart_item_data;
                }

                $airport_transfer_selection = $this->get_airport_transfer_selection_from_request( $product_id );
                if ( $airport_transfer_selection ) {
                        $cart_item_data['hr_airport_transfer_rate_id']    = $airport_transfer_selection['rate_id'];
                        $cart_item_data['hr_airport_transfer_name']       = $airport_transfer_selection['name'];
                        $cart_item_data['hr_airport_transfer_place_name'] = $airport_transfer_selection['place_name'];
                        $cart_item_data['hr_airport_transfer_route_label'] = $airport_transfer_selection['route_label'];
                        $cart_item_data['hr_airport_transfer_direction']  = $airport_transfer_selection['direction'];
                        $cart_item_data['hr_airport_transfer_direction_label'] = $airport_transfer_selection['direction_label'];
                        $cart_item_data['hr_airport_transfer_pax']        = $airport_transfer_selection['pax'];
                        $cart_item_data['hr_airport_transfer_unit_price'] = $airport_transfer_selection['unit_price'];
                        $cart_item_data['hr_airport_transfer_total']      = $airport_transfer_selection['total'];
                        return $cart_item_data;
                }

                $day_tour_selection = $this->get_day_tour_selection_from_request( $product_id );
                if ( $day_tour_selection ) {
                        return $this->add_tour_selection_cart_item_data( $cart_item_data, $day_tour_selection );
                }

                $tour_selection = $this->get_tour_selection_from_request( $product_id );
                if ( $tour_selection ) {
                        return $this->add_tour_selection_cart_item_data( $cart_item_data, $tour_selection );
                }

                $selection = $this->get_rate_selection_from_request();
                if ( ! $selection ) {
                        return $cart_item_data;
                }

                $cart_item_data['hr_selected_rate_id']   = $selection['rate_id'];
                $cart_item_data['hr_selected_hotel_id']  = $selection['hotel_id'];
                $cart_item_data['hr_selected_hotel_name'] = $selection['hotel_name'];
                $cart_item_data['hr_selected_hotel_label'] = $selection['label'];
                $cart_item_data['hr_selected_pax']       = $selection['pax'];
                $cart_item_data['hr_selected_price']     = $selection['price'];

                return $cart_item_data;
        }

        private function add_tour_selection_cart_item_data( $cart_item_data, $tour_selection ) {
                if ( 'Day Tour' === (string) $tour_selection['type'] ) {
                        $cart_item_data = $this->cleanup_day_tour_legacy_cart_item_data( $cart_item_data, $tour_selection );
                }

                $cart_item_data['hr_tour_rate_id']    = $tour_selection['tour_rate_id'];
                $cart_item_data['hr_tour_name']       = $tour_selection['tour_name'];
                $cart_item_data['hr_tour_type']       = $tour_selection['type'];
                $cart_item_data['hr_tour_place_name'] = $tour_selection['place_name'];
                $cart_item_data['hr_tour_pax']        = $tour_selection['pax'];

                if ( ! empty( $tour_selection['children_enabled'] ) && ! empty( $tour_selection['children'] ) ) {
                        $cart_item_data['hr_tour_children'] = $tour_selection['children'];
                        $cart_item_data['hr_tour_children_label'] = $tour_selection['children_label'];
                        $cart_item_data['hr_tour_children_unit_price'] = $tour_selection['children_unit_price'];
                        $cart_item_data['hr_tour_children_total'] = $tour_selection['children_total'];
                }

                $cart_item_data['hr_tour_infants']    = $tour_selection['infants'];
                $cart_item_data['hr_tour_hotel_flight_no'] = $tour_selection['hotel_flight_no'];
                $cart_item_data['hr_tour_hotel_flight_no_label'] = $tour_selection['hotel_flight_no_label'];
                $cart_item_data['hr_tour_pickup_time'] = $tour_selection['pickup_time'];
                $cart_item_data['hr_tour_pickup_time_label'] = $tour_selection['pickup_time_label'];

                if ( 'Day Tour' === (string) $tour_selection['type'] ) {
                        $cart_item_data['hr_tour_date'] = $tour_selection['tour_date'] ?? '';
                        $cart_item_data['hr_tour_hotel_flight_no_enabled'] = ! empty( $tour_selection['hotel_flight_no_enabled'] ) ? 1 : 0;
                        $cart_item_data['hr_tour_pickup_time_enabled'] = ! empty( $tour_selection['pickup_time_enabled'] ) ? 1 : 0;
                        $cart_item_data['hr_tour_legacy_display_keys'] = ! empty( $tour_selection['legacy_display_keys'] ) && is_array( $tour_selection['legacy_display_keys'] )
                                ? array_values( $tour_selection['legacy_display_keys'] )
                                : array();
                }

                $cart_item_data['hr_tour_unit_price'] = $tour_selection['unit_price'];
                $cart_item_data['hr_tour_addons']     = $tour_selection['addons'];
                $cart_item_data['hr_tour_addons_total'] = $tour_selection['addons_total'];
                $cart_item_data['hr_tour_total']      = $tour_selection['total'];

                return $cart_item_data;
        }

        /**
         * Remove Ova custom-field bridge values before Day Tour pricing/storage.
         */
        private function cleanup_day_tour_legacy_cart_item_data( $cart_item_data, $tour_selection ) {
                $legacy_keys = ! empty( $tour_selection['legacy_display_keys'] ) && is_array( $tour_selection['legacy_display_keys'] )
                        ? $tour_selection['legacy_display_keys']
                        : array();

                foreach ( $legacy_keys as $legacy_key ) {
                        if ( is_string( $legacy_key ) && '' !== $legacy_key ) {
                                unset( $cart_item_data[ $legacy_key ] );
                        }
                }

                unset( $cart_item_data['custom_ckf'], $cart_item_data['cckf_qty'] );

                return $cart_item_data;
        }

        public function add_hotel_rate_to_total( $line_total, $product_id, $checkin_date, $checkout_date, $cart_item ) {
                if ( ! empty( $cart_item['hr_package_deal_id'] ) && isset( $cart_item['hr_package_total'] ) ) {
                        return (float) $cart_item['hr_package_total'];
                }

                if ( ! empty( $cart_item['hr_motorbike_rental_id'] ) && isset( $cart_item['hr_motorbike_total'] ) ) {
                        return (float) $cart_item['hr_motorbike_total'];
                }

                if ( ! empty( $cart_item['hr_boat_rental_id'] ) && isset( $cart_item['hr_boat_total'] ) ) {
                        return (float) $cart_item['hr_boat_total'];
                }

                if ( ! empty( $cart_item['hr_self_drive_rental_id'] ) && isset( $cart_item['hr_self_drive_total'] ) ) {
                        return (float) $cart_item['hr_self_drive_total'];
                }

                if ( ! empty( $cart_item['hr_port_transfer_id'] ) && isset( $cart_item['hr_port_transfer_total'] ) ) {
                        return (float) $cart_item['hr_port_transfer_total'];
                }

                if ( ! empty( $cart_item['hr_destination_tour_id'] ) && isset( $cart_item['hr_destination_tour_total'] ) ) {
                        return (float) $cart_item['hr_destination_tour_total'];
                }

                if ( ! empty( $cart_item['hr_pickup_dropoff_service_id'] ) && isset( $cart_item['hr_pickup_dropoff_total'] ) ) {
                        return (float) $cart_item['hr_pickup_dropoff_total'];
                }

                if ( ! empty( $cart_item['hr_tour_rate_id'] ) && isset( $cart_item['hr_tour_total'] ) ) {
                        return (float) $cart_item['hr_tour_total'];
                }

                if ( ! empty( $cart_item['hr_airport_transfer_rate_id'] ) && isset( $cart_item['hr_airport_transfer_total'] ) ) {
                        return (float) $cart_item['hr_airport_transfer_total'];
                }

                $selection = $this->get_rate_selection( $cart_item );
                if ( ! $selection ) {
                        return $line_total;
                }

                return (float) $line_total + (float) $selection['price'];
        }

        public function add_port_transfer_convenience_fees( $cart ) {
                if ( ! $cart || ! method_exists( $cart, 'get_cart' ) || ! method_exists( $cart, 'add_fee' ) ) {
                        return;
                }

                if ( is_admin() && ( ! function_exists( 'wp_doing_ajax' ) || ! wp_doing_ajax() ) ) {
                        return;
                }

                $port_transfer_fees = array();
                $credit_card_fees = array();

                foreach ( $cart->get_cart() as $cart_item ) {
                        if ( ! empty( $cart_item['hr_port_transfer_id'] ) ) {
                                $percent = isset( $cart_item['hr_port_transfer_convenience_fee_percent'] ) ? (float) $cart_item['hr_port_transfer_convenience_fee_percent'] : 0.0;
                                $subtotal = isset( $cart_item['hr_port_transfer_total'] ) ? (float) $cart_item['hr_port_transfer_total'] : 0.0;

                                if ( $percent <= 0 || $subtotal <= 0 ) {
                                        continue;
                                }

                                $percent_label = $this->format_port_transfer_percent_text( $percent );
                                $amount = ( $subtotal * $percent ) / 100;

                                if ( $amount <= 0 ) {
                                        continue;
                                }

                                if ( ! isset( $port_transfer_fees[ $percent_label ] ) ) {
                                        $port_transfer_fees[ $percent_label ] = 0.0;
                                }

                                $port_transfer_fees[ $percent_label ] += $amount;
                        }

                        if ( ! empty( $cart_item['hr_destination_tour_id'] ) ) {
                                $percent = isset( $cart_item['hr_destination_tour_credit_card_fee_percent'] ) ? (float) $cart_item['hr_destination_tour_credit_card_fee_percent'] : 0.0;
                                $subtotal = isset( $cart_item['hr_destination_tour_total'] ) ? (float) $cart_item['hr_destination_tour_total'] : 0.0;

                                if ( $percent <= 0 || $subtotal <= 0 ) {
                                        continue;
                                }

                                $percent_label = $this->format_port_transfer_percent_text( $percent );
                                $amount = ( $subtotal * $percent ) / 100;

                                if ( $amount <= 0 ) {
                                        continue;
                                }

                                if ( ! isset( $credit_card_fees[ $percent_label ] ) ) {
                                        $credit_card_fees[ $percent_label ] = 0.0;
                                }

                                $credit_card_fees[ $percent_label ] += $amount;
                        }

                        if ( ! empty( $cart_item['hr_boat_rental_id'] ) ) {
                                $percent = isset( $cart_item['hr_boat_credit_card_fee_percent'] ) ? (float) $cart_item['hr_boat_credit_card_fee_percent'] : 0.0;
                                $subtotal = isset( $cart_item['hr_boat_total'] ) ? (float) $cart_item['hr_boat_total'] : 0.0;

                                if ( $percent <= 0 || $subtotal <= 0 ) {
                                        continue;
                                }

                                $percent_label = $this->format_port_transfer_percent_text( $percent );
                                $amount = ( $subtotal * $percent ) / 100;

                                if ( $amount <= 0 ) {
                                        continue;
                                }

                                if ( ! isset( $credit_card_fees[ $percent_label ] ) ) {
                                        $credit_card_fees[ $percent_label ] = 0.0;
                                }

                                $credit_card_fees[ $percent_label ] += $amount;
                        }

                        if ( ! empty( $cart_item['hr_self_drive_rental_id'] ) ) {
                                $percent = isset( $cart_item['hr_self_drive_credit_card_fee_percent'] ) ? (float) $cart_item['hr_self_drive_credit_card_fee_percent'] : 0.0;
                                $subtotal = isset( $cart_item['hr_self_drive_total'] ) ? (float) $cart_item['hr_self_drive_total'] : 0.0;

                                if ( $percent <= 0 || $subtotal <= 0 ) {
                                        continue;
                                }

                                $percent_label = $this->format_port_transfer_percent_text( $percent );
                                $amount = ( $subtotal * $percent ) / 100;

                                if ( $amount <= 0 ) {
                                        continue;
                                }

                                if ( ! isset( $credit_card_fees[ $percent_label ] ) ) {
                                        $credit_card_fees[ $percent_label ] = 0.0;
                                }

                                $credit_card_fees[ $percent_label ] += $amount;
                        }
                }

                foreach ( $port_transfer_fees as $percent_label => $amount ) {
                        $cart->add_fee(
                                sprintf(
                                        /* translators: %s: convenience fee percentage. */
                                        __( 'Convenience Fee +%s%%', 'hotel-rates' ),
                                        $percent_label
                                ),
                                round( $amount, function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2 ),
                                false
                        );
                }

                foreach ( $credit_card_fees as $percent_label => $amount ) {
                        $cart->add_fee(
                                sprintf(
                                        /* translators: %s: credit card fee percentage. */
                                        __( '+%s%% Credit Card Fee', 'hotel-rates' ),
                                        $percent_label
                                ),
                                round( $amount, function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2 ),
                                false
                        );
                }
        }

        public function add_item_data( $item_data, $cart_item ) {
                $item_data = $this->normalize_arrival_time_item_data( $item_data, $cart_item );

                if ( ! empty( $cart_item['hr_package_deal_id'] ) ) {
                        $item_data = $this->cleanup_package_item_data( $item_data, $cart_item );

                        $item_data[] = array(
                                'key'     => __( 'Package Deal', 'hotel-rates' ),
                                'value'   => wc_clean( $cart_item['hr_package_name'] ?? '' ),
                                'display' => '',
                        );

                        if ( ! empty( $cart_item['hr_package_airport_transfer_fee'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'Airport Transfer Fee', 'hotel-rates' ),
                                        'value'   => wp_strip_all_tags( wc_price( (float) $cart_item['hr_package_airport_transfer_fee'] ) ),
                                        'display' => '',
                                );
                        }

                        if ( ! empty( $cart_item['hr_package_seaport_transfer_fee'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'Seaport Transfer Fee', 'hotel-rates' ),
                                        'value'   => wp_strip_all_tags( wc_price( (float) $cart_item['hr_package_seaport_transfer_fee'] ) ),
                                        'display' => '',
                                );
                        }

                        if ( ! empty( $cart_item['hr_package_hotels'] ) && is_array( $cart_item['hr_package_hotels'] ) ) {
                                foreach ( $cart_item['hr_package_hotels'] as $hotel_selection ) {
                                        $item_data[] = array(
                                                'key'     => sprintf( __( '%s Hotel', 'hotel-rates' ), $hotel_selection['place_name'] ?? __( 'Package', 'hotel-rates' ) ),
                                                'value'   => wc_clean( $this->format_package_hotel_display_value( $hotel_selection ) ),
                                                'display' => '',
                                        );
                                }
                        }

                        if ( ! empty( $cart_item['hr_package_activities'] ) && is_array( $cart_item['hr_package_activities'] ) ) {
                                foreach ( $cart_item['hr_package_activities'] as $activity_selection ) {
                                        $item_data[] = array(
                                                'key'     => sprintf( __( 'Day %d Activity', 'hotel-rates' ), (int) ( $activity_selection['day'] ?? 0 ) ),
                                                'value'   => wc_clean( $this->format_package_activity_display_value( $activity_selection ) ),
                                                'display' => '',
                                        );
                                }
                        }

                        if ( isset( $cart_item['hr_package_total'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'Package Total', 'hotel-rates' ),
                                        'value'   => wp_strip_all_tags( wc_price( (float) $cart_item['hr_package_total'] ) ),
                                        'display' => '',
                                );
                        }

                        return $item_data;
                }

                if ( ! empty( $cart_item['hr_motorbike_rental_id'] ) ) {
                        $item_data[] = array(
                                'key'     => __( 'Motorbike Rental', 'hotel-rates' ),
                                'value'   => wc_clean( $cart_item['hr_motorbike_rental_name'] ?? '' ),
                                'display' => '',
                        );

                        if ( ! empty( $cart_item['hr_motorbike_items'] ) && is_array( $cart_item['hr_motorbike_items'] ) ) {
                                foreach ( $cart_item['hr_motorbike_items'] as $motorbike_item ) {
                                        $item_data[] = array(
                                                'key'     => __( 'Motorbike', 'hotel-rates' ),
                                                'value'   => wc_clean( sprintf(
                                                        '%1$s x %2$d - %3$s',
                                                        $motorbike_item['name'] ?? '',
                                                        (int) ( $motorbike_item['quantity'] ?? 0 ),
                                                        wp_strip_all_tags( wc_price( (float) ( $motorbike_item['line_total'] ?? 0 ) ) )
                                                ) ),
                                                'display' => '',
                                        );
                                }
                        }

                        if ( ! empty( $cart_item['hr_motorbike_start_time'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'Pick-up/Delivery Time', 'hotel-rates' ),
                                        'value'   => wc_clean( $cart_item['hr_motorbike_start_time'] ),
                                        'display' => '',
                                );
                        }

                        if ( ! empty( $cart_item['hr_motorbike_days'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'No. of Days', 'hotel-rates' ),
                                        'value'   => wc_clean( (string) (int) $cart_item['hr_motorbike_days'] ),
                                        'display' => '',
                                );
                        }

                        if ( ! empty( $cart_item['hr_motorbike_handover_label'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'Handover Option', 'hotel-rates' ),
                                        'value'   => wc_clean( $cart_item['hr_motorbike_handover_label'] ),
                                        'display' => '',
                                );
                        }

                        if ( ! empty( $cart_item['hr_motorbike_delivery_address'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'Delivery Address', 'hotel-rates' ),
                                        'value'   => wc_clean( $cart_item['hr_motorbike_delivery_address'] ),
                                        'display' => '',
                                );
                        }

                        if ( isset( $cart_item['hr_motorbike_total'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'Motorbike Rental Total', 'hotel-rates' ),
                                        'value'   => wp_strip_all_tags( wc_price( (float) $cart_item['hr_motorbike_total'] ) ),
                                        'display' => '',
                                );
                        }

                        return $this->cleanup_motorbike_item_data( $item_data );
                }

                if ( ! empty( $cart_item['hr_boat_rental_id'] ) ) {
                        $item_data = $this->cleanup_boat_item_data( $item_data, $cart_item );

                        foreach ( $this->get_boat_display_rows( $cart_item ) as $row ) {
                                $item_data[] = array(
                                        'key'     => $row['key'],
                                        'value'   => wc_clean( $row['value'] ),
                                        'display' => '',
                                );
                        }

                        return $item_data;
                }

                if ( ! empty( $cart_item['hr_self_drive_rental_id'] ) ) {
                        $item_data = $this->cleanup_self_drive_item_data( $item_data );

                        foreach ( $this->get_self_drive_display_rows( $cart_item ) as $row ) {
                                $item_data[] = array(
                                        'key'     => $row['key'],
                                        'value'   => wc_clean( $row['value'] ),
                                        'display' => '',
                                );
                        }

                        return $item_data;
                }

                if ( ! empty( $cart_item['hr_destination_tour_id'] ) ) {
                        $item_data = $this->cleanup_destination_tour_item_data( $item_data );

                        foreach ( $this->get_destination_tour_display_rows( $cart_item ) as $row ) {
                                $item_data[] = array(
                                        'key'     => $row['key'],
                                        'value'   => wc_clean( $row['value'] ),
                                        'display' => '',
                                );
                        }

                        return $item_data;
                }

                if ( ! empty( $cart_item['hr_pickup_dropoff_service_id'] ) ) {
                        $item_data = $this->cleanup_pickup_dropoff_item_data( $item_data );

                        foreach ( $this->get_pickup_dropoff_display_rows( $cart_item ) as $row ) {
                                $item_data[] = array(
                                        'key'     => $row['key'],
                                        'value'   => wc_clean( $row['value'] ),
                                        'display' => '',
                                );
                        }

                        return $item_data;
                }

                if ( ! empty( $cart_item['hr_port_transfer_id'] ) ) {
                        $item_data = $this->cleanup_port_transfer_item_data( $item_data );

                        foreach ( $this->get_port_transfer_display_rows( $cart_item ) as $row ) {
                                $item_data[] = array(
                                        'key'     => $row['key'],
                                        'value'   => wc_clean( $row['value'] ),
                                        'display' => '',
                                );
                        }

                        return $item_data;
                }

                if ( ! empty( $cart_item['hr_airport_transfer_rate_id'] ) ) {
                        $item_data[] = array(
                                'key'     => __( 'Airport Transfer', 'hotel-rates' ),
                                'value'   => wc_clean( $cart_item['hr_airport_transfer_name'] ?? '' ),
                                'display' => '',
                        );

                        if ( ! empty( $cart_item['hr_airport_transfer_route_label'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'From/To Place', 'hotel-rates' ),
                                        'value'   => wc_clean( $cart_item['hr_airport_transfer_route_label'] ),
                                        'display' => '',
                                );
                        }

                        if ( ! empty( $cart_item['hr_airport_transfer_direction_label'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'Transfer Direction', 'hotel-rates' ),
                                        'value'   => wc_clean( $cart_item['hr_airport_transfer_direction_label'] ),
                                        'display' => '',
                                );
                        }

                        if ( isset( $cart_item['hr_airport_transfer_total'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'Airport Transfer Total', 'hotel-rates' ),
                                        'value'   => wp_strip_all_tags( wc_price( (float) $cart_item['hr_airport_transfer_total'] ) ),
                                        'display' => '',
                                );
                        }

                        return $this->cleanup_airport_transfer_item_data( $item_data );
                }

                        if ( ! empty( $cart_item['hr_tour_rate_id'] ) ) {
                        $is_day_tour = 'Day Tour' === (string) ( $cart_item['hr_tour_type'] ?? '' );
                        $item_data = $is_day_tour
                                ? $this->cleanup_day_tour_item_data( $item_data, $cart_item )
                                : $this->cleanup_tour_item_data( $item_data, $cart_item );
                        $hotel_flight_label = $this->get_tour_display_label_from_values( $cart_item, 'hr_tour_hotel_flight_no_label', __( 'Hotel / Flight No.', 'hotel-rates' ) );
                        $pickup_time_label = $this->get_tour_display_label_from_values( $cart_item, 'hr_tour_pickup_time_label', __( 'Pick-up Time', 'hotel-rates' ) );
                        $show_hotel_flight = ! $is_day_tour || ! array_key_exists( 'hr_tour_hotel_flight_no_enabled', $cart_item ) || ! empty( $cart_item['hr_tour_hotel_flight_no_enabled'] );
                        $show_pickup_time = ! $is_day_tour || ! array_key_exists( 'hr_tour_pickup_time_enabled', $cart_item ) || ! empty( $cart_item['hr_tour_pickup_time_enabled'] );

                        if ( $is_day_tour ) {
                                $hotel_flight_label = $this->normalize_day_tour_display_label( $hotel_flight_label );
                                $pickup_time_label = $this->normalize_day_tour_display_label( $pickup_time_label );
                        }
                        $children_label = $this->get_tour_display_label_from_values( $cart_item, 'hr_tour_children_label', __( 'Children', 'hotel-rates' ) );
                        $children_rate_label = sprintf(
                                /* translators: %s: child guest label. */
                                __( '%s Rate Per Guest', 'hotel-rates' ),
                                $children_label
                        );

                        if ( $is_day_tour && ! empty( $cart_item['hr_tour_date'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'Tour Date', 'hotel-rates' ),
                                        'value'   => wc_clean( $cart_item['hr_tour_date'] ),
                                        'display' => '',
                                );
                        }

                        $item_data[] = array(
                                'key'     => __( 'Tour Type', 'hotel-rates' ),
                                'value'   => wc_clean( $cart_item['hr_tour_type'] ?? '' ),
                                'display' => '',
                        );

                        $item_data[] = array(
                                'key'     => __( 'Tour', 'hotel-rates' ),
                                'value'   => wc_clean( $cart_item['hr_tour_name'] ?? '' ),
                                'display' => '',
                        );

                        if ( ! empty( $cart_item['hr_tour_place_name'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'Place', 'hotel-rates' ),
                                        'value'   => wc_clean( $cart_item['hr_tour_place_name'] ),
                                        'display' => '',
                                );
                        }

                        if ( ! empty( $cart_item['hr_tour_pax'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'Adults', 'hotel-rates' ),
                                        'value'   => wc_clean( (string) (int) $cart_item['hr_tour_pax'] ),
                                        'display' => '',
                                );
                        }

                        if ( ! empty( $cart_item['hr_tour_children'] ) ) {
                                $item_data[] = array(
                                        'key'     => $children_label,
                                        'value'   => wc_clean( (string) (int) $cart_item['hr_tour_children'] ),
                                        'display' => '',
                                );
                        }

                        if ( ! empty( $cart_item['hr_tour_infants'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( '0-2 Infant', 'hotel-rates' ),
                                        'value'   => wc_clean( (string) (int) $cart_item['hr_tour_infants'] ),
                                        'display' => '',
                                );
                        }

                        if ( isset( $cart_item['hr_tour_children_unit_price'] ) && ! empty( $cart_item['hr_tour_children'] ) ) {
                                $item_data[] = array(
                                        'key'     => $children_rate_label,
                                        'value'   => wp_strip_all_tags( wc_price( (float) $cart_item['hr_tour_children_unit_price'] ) ),
                                        'display' => '',
                                );
                        }

                        if ( $show_hotel_flight && ! empty( $cart_item['hr_tour_hotel_flight_no'] ) ) {
                                $item_data[] = array(
                                        'key'     => $hotel_flight_label,
                                        'value'   => wc_clean( $cart_item['hr_tour_hotel_flight_no'] ),
                                        'display' => '',
                                );
                        }

                        if ( $show_pickup_time && ! empty( $cart_item['hr_tour_pickup_time'] ) ) {
                                $item_data[] = array(
                                        'key'     => $pickup_time_label,
                                        'value'   => wc_clean( $cart_item['hr_tour_pickup_time'] ),
                                        'display' => '',
                                );
                        }

                        if ( isset( $cart_item['hr_tour_unit_price'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'Rate Per Adult Guest', 'hotel-rates' ),
                                        'value'   => wp_strip_all_tags( wc_price( (float) $cart_item['hr_tour_unit_price'] ) ),
                                        'display' => '',
                                );
                        }

                        if ( ! empty( $cart_item['hr_tour_addons'] ) && is_array( $cart_item['hr_tour_addons'] ) ) {
                                foreach ( $cart_item['hr_tour_addons'] as $addon ) {
                                        $item_data[] = array(
                                                'key'     => $addon['group_label'] ?? __( 'Optional Add-on', 'hotel-rates' ),
                                                'value'   => wc_clean( sprintf(
                                                        '%1$s - %2$s',
                                                        $addon['label'] ?? '',
                                                        wp_strip_all_tags( wc_price( (float) ( $addon['rate'] ?? 0 ) ) )
                                                ) ),
                                                'display' => '',
                                        );
                                }
                        }

                        if ( isset( $cart_item['hr_tour_total'] ) ) {
                                $item_data[] = array(
                                        'key'     => __( 'Tour Total', 'hotel-rates' ),
                                        'value'   => wp_strip_all_tags( wc_price( (float) $cart_item['hr_tour_total'] ) ),
                                        'display' => '',
                                );
                        }

                        return $item_data;
                }

                if ( empty( $cart_item['hr_selected_rate_id'] ) ) {
                        return $item_data;
                }

                $label = ! empty( $cart_item['hr_selected_hotel_label'] ) ? $cart_item['hr_selected_hotel_label'] : $cart_item['hr_selected_hotel_name'];
                $price = isset( $cart_item['hr_selected_price'] ) ? (float) $cart_item['hr_selected_price'] : 0;

                if ( $label ) {
                        $item_data[] = array(
                                'key'     => __( 'Hotel Package', 'hotel-rates' ),
                                'value'   => wc_clean( $label ),
                                'display' => '',
                        );
                }

                if ( $price > 0 ) {
                        $item_data[] = array(
                                'key'     => __( 'Hotel Package Rate', 'hotel-rates' ),
                                'value'   => wp_strip_all_tags( wc_price( $price ) ),
                                'display' => '',
                        );
                }

                if ( ! empty( $cart_item['hr_airport_transfer_rate_id'] ) ) {
                        $item_data = $this->cleanup_airport_transfer_item_data( $item_data );
                }

                return $item_data;
        }

        public function normalize_arrival_time_item_data( $item_data, $cart_item = array() ) {
                if ( ! is_array( $item_data ) ) {
                        return $item_data;
                }

                foreach ( $item_data as $index => $data ) {
                        if ( ! is_array( $data ) ) {
                                continue;
                        }

                        $key   = isset( $data['key'] ) ? (string) $data['key'] : ( isset( $data['name'] ) ? (string) $data['name'] : '' );
                        $value = $data['display'] ?? ( $data['value'] ?? '' );

                        if ( ! $this->is_arrival_time_item_data( $key, $value ) ) {
                                continue;
                        }

                        $item_data[ $index ]['key'] = __( 'Arrival Time', 'hotel-rates' );
                        if ( isset( $item_data[ $index ]['name'] ) ) {
                                $item_data[ $index ]['name'] = __( 'Arrival Time', 'hotel-rates' );
                        }
                }

                return $item_data;
        }

        public function normalize_arrival_time_order_meta_key( $display_key, $meta, $item ) {
                $value = is_object( $meta ) && isset( $meta->value ) ? $meta->value : '';

                if ( ! $this->is_arrival_time_item_data( $display_key, $value ) ) {
                        return $display_key;
                }

                return __( 'Arrival Time', 'hotel-rates' );
        }

        public function cleanup_order_item_meta( $item, $cart_item_key, $values, $order ) {
                $is_airport_transfer = ! empty( $values['hr_airport_transfer_rate_id'] );
                $is_motorbike_rental = ! empty( $values['hr_motorbike_rental_id'] );
                $is_boat_rental = ! empty( $values['hr_boat_rental_id'] );
                $is_self_drive_rental = ! empty( $values['hr_self_drive_rental_id'] );
                $is_port_transfer = ! empty( $values['hr_port_transfer_id'] );
                $is_destination_tour = ! empty( $values['hr_destination_tour_id'] );
                $is_pickup_dropoff = ! empty( $values['hr_pickup_dropoff_service_id'] );
                $is_tour = ! empty( $values['hr_tour_rate_id'] );
                $is_day_tour = $is_tour && 'Day Tour' === (string) ( $values['hr_tour_type'] ?? '' );
                $rebuild_pickup_dropoff_meta = false;

                if ( ( ! $is_airport_transfer && ! $is_motorbike_rental && ! $is_boat_rental && ! $is_self_drive_rental && ! $is_port_transfer && ! $is_destination_tour && ! $is_pickup_dropoff && ! $is_tour ) || ! is_object( $item ) || ! method_exists( $item, 'get_meta_data' ) ) {
                        return;
                }

                $arrival_time_value = '';

                foreach ( $item->get_meta_data() as $meta ) {
                        if ( ! is_object( $meta ) ) {
                                continue;
                        }

                        $key   = isset( $meta->key ) ? (string) $meta->key : '';
                        $value = $meta->value ?? '';

                        if ( $is_airport_transfer && $this->should_remove_airport_transfer_item_data( $key ) ) {
                                $item->delete_meta_data( $key );
                                continue;
                        }

                        if ( $is_motorbike_rental && $this->should_remove_motorbike_item_data( $key ) ) {
                                $item->delete_meta_data( $key );
                                continue;
                        }

                        if ( $is_boat_rental && $this->is_boat_clean_order_item_meta_key( $key, $values ) ) {
                                continue;
                        }

                        if ( $is_boat_rental && $this->should_remove_boat_item_data( $key, true ) ) {
                                $item->delete_meta_data( $key );
                                continue;
                        }

                        if ( $is_self_drive_rental ) {
                                if ( $this->is_self_drive_clean_order_item_meta_key( $key, $values ) ) {
                                        continue;
                                }

                                if ( $this->should_remove_self_drive_item_data( $key, true ) ) {
                                        $item->delete_meta_data( $key );
                                        continue;
                                }
                        }

                        if ( $is_destination_tour ) {
                                if ( $this->is_destination_tour_clean_order_item_meta_key( $key ) ) {
                                        continue;
                                }

                                if ( $this->is_blank_item_data_key( $key ) ) {
                                        $item->delete_meta_data( $key );
                                        continue;
                                }

                                if ( $this->should_remove_destination_tour_item_data( $key, true ) ) {
                                        $item->delete_meta_data( $key );
                                        continue;
                                }
                        }

                        if ( $is_port_transfer ) {
                                if ( $this->is_port_transfer_clean_order_item_meta_key( $key ) ) {
                                        continue;
                                }

                                if ( $this->is_blank_item_data_key( $key ) ) {
                                        if ( $this->is_arrival_time_value( $value ) ) {
                                                $arrival_time_value = $value;
                                        }

                                        $item->delete_meta_data( $key );
                                        continue;
                                }

                                if ( $this->should_remove_port_transfer_item_data( $key, true ) ) {
                                        $item->delete_meta_data( $key );
                                        continue;
                                }
                        }

                        if ( $is_pickup_dropoff ) {
                                if ( $this->is_pickup_dropoff_clean_order_item_meta_key( $key ) ) {
                                        $item->delete_meta_data( $key );
                                        $rebuild_pickup_dropoff_meta = true;
                                        continue;
                                }

                                if ( $this->is_blank_item_data_key( $key ) ) {
                                        $item->delete_meta_data( $key );
                                        continue;
                                }

                                if ( $this->should_remove_pickup_dropoff_item_data( $key, true ) ) {
                                        $item->delete_meta_data( $key );
                                        continue;
                                }
                        }

                        if ( $is_tour ) {
                                $is_clean_tour_key = $this->is_tour_clean_order_item_meta_key( $key, $values );

                                if ( $is_day_tour && $is_clean_tour_key && $this->normalize_day_tour_display_label( $key ) !== $key ) {
                                        $item->delete_meta_data( $key );
                                        continue;
                                }

                                if ( $is_clean_tour_key ) {
                                        continue;
                                }

                                if ( $is_day_tour && $this->is_day_tour_legacy_display_key( $key, $values ) ) {
                                        $item->delete_meta_data( $key );
                                        continue;
                                }

                                if ( $this->is_blank_item_data_key( $key ) ) {
                                        $item->delete_meta_data( $key );
                                        continue;
                                }

                                if ( $this->should_remove_tour_item_data( $key, true, $values ) ) {
                                        $item->delete_meta_data( $key );
                                        continue;
                                }

                                if ( $is_day_tour ) {
                                        $normalized_key = $this->normalize_day_tour_display_label( $key );

                                        if ( '' !== $normalized_key && $normalized_key !== $key ) {
                                                $item->delete_meta_data( $key );
                                                $item->add_meta_data( $normalized_key, $value, true );
                                                continue;
                                        }
                                }
                        }

                        if ( $this->is_arrival_time_item_data( $key, $value ) && $this->is_blank_item_data_key( $key ) ) {
                                $arrival_time_value = $value;
                                $item->delete_meta_data( $key );
                        }
                }

                if ( $is_pickup_dropoff && $rebuild_pickup_dropoff_meta ) {
                        foreach ( $this->get_pickup_dropoff_display_rows( $values ) as $row ) {
                                $item->add_meta_data( $row['key'], $row['value'], empty( $row['allow_multiple'] ) );
                        }
                }

                if ( '' !== (string) $arrival_time_value && ! $this->order_item_has_meta_key( $item, __( 'Arrival Time', 'hotel-rates' ) ) ) {
                        $item->add_meta_data( __( 'Arrival Time', 'hotel-rates' ), $arrival_time_value, true );
                }
        }

        public function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
                if ( ! empty( $values['hr_package_deal_id'] ) ) {
                        $item->add_meta_data( __( 'Package Deal', 'hotel-rates' ), $values['hr_package_name'] ?? '', true );

                        if ( ! empty( $values['hr_package_airport_transfer_fee'] ) ) {
                                $item->add_meta_data( __( 'Airport Transfer Fee', 'hotel-rates' ), wp_strip_all_tags( wc_price( (float) $values['hr_package_airport_transfer_fee'] ) ), true );
                        }

                        if ( ! empty( $values['hr_package_seaport_transfer_fee'] ) ) {
                                $item->add_meta_data( __( 'Seaport Transfer Fee', 'hotel-rates' ), wp_strip_all_tags( wc_price( (float) $values['hr_package_seaport_transfer_fee'] ) ), true );
                        }

                        if ( ! empty( $values['hr_package_hotels'] ) && is_array( $values['hr_package_hotels'] ) ) {
                                foreach ( $values['hr_package_hotels'] as $hotel_selection ) {
                                        $item->add_meta_data(
                                                sprintf( __( '%s Hotel', 'hotel-rates' ), $hotel_selection['place_name'] ?? __( 'Package', 'hotel-rates' ) ),
                                                $this->format_package_hotel_display_value( $hotel_selection ),
                                                true
                                        );
                                }
                        }

                        if ( ! empty( $values['hr_package_activities'] ) && is_array( $values['hr_package_activities'] ) ) {
                                foreach ( $values['hr_package_activities'] as $activity_selection ) {
                                        $item->add_meta_data(
                                                sprintf( __( 'Day %d Activity', 'hotel-rates' ), (int) ( $activity_selection['day'] ?? 0 ) ),
                                                $this->format_package_activity_display_value( $activity_selection ),
                                                true
                                        );
                                }
                        }

                        $item->add_meta_data( __( 'Package Total', 'hotel-rates' ), wp_strip_all_tags( wc_price( (float) ( $values['hr_package_total'] ?? 0 ) ) ), true );

                        $item->add_meta_data( 'hr_package_deal_id', (int) $values['hr_package_deal_id'], true );
                        return;
                }

                if ( ! empty( $values['hr_motorbike_rental_id'] ) ) {
                        $item->add_meta_data( __( 'Motorbike Rental', 'hotel-rates' ), $values['hr_motorbike_rental_name'] ?? '', true );

                        if ( ! empty( $values['hr_motorbike_items'] ) && is_array( $values['hr_motorbike_items'] ) ) {
                                foreach ( $values['hr_motorbike_items'] as $motorbike_item ) {
                                        $item->add_meta_data(
                                                __( 'Motorbike', 'hotel-rates' ),
                                                sprintf(
                                                        '%1$s x %2$d - %3$s',
                                                        $motorbike_item['name'] ?? '',
                                                        (int) ( $motorbike_item['quantity'] ?? 0 ),
                                                        wp_strip_all_tags( wc_price( (float) ( $motorbike_item['line_total'] ?? 0 ) ) )
                                                ),
                                                false
                                        );
                                }
                        }

                        if ( ! empty( $values['hr_motorbike_start_time'] ) ) {
                                $item->add_meta_data( __( 'Pick-up/Delivery Time', 'hotel-rates' ), $values['hr_motorbike_start_time'], true );
                        }

                        if ( ! empty( $values['hr_motorbike_days'] ) ) {
                                $item->add_meta_data( __( 'No. of Days', 'hotel-rates' ), (int) $values['hr_motorbike_days'], true );
                        }

                        if ( ! empty( $values['hr_motorbike_handover_label'] ) ) {
                                $item->add_meta_data( __( 'Handover Option', 'hotel-rates' ), $values['hr_motorbike_handover_label'], true );
                        }

                        if ( ! empty( $values['hr_motorbike_delivery_address'] ) ) {
                                $item->add_meta_data( __( 'Delivery Address', 'hotel-rates' ), $values['hr_motorbike_delivery_address'], true );
                        }

                        $item->add_meta_data( __( 'Motorbike Rental Total', 'hotel-rates' ), wp_strip_all_tags( wc_price( (float) ( $values['hr_motorbike_total'] ?? 0 ) ) ), true );
                        $item->add_meta_data( 'hr_motorbike_rental_id', (int) $values['hr_motorbike_rental_id'], true );
                        return;
                }

                if ( ! empty( $values['hr_boat_rental_id'] ) ) {
                        $deleted_boat_meta_keys = array();

                        foreach ( $this->get_boat_display_rows( $values ) as $row ) {
                                if ( empty( $deleted_boat_meta_keys[ $row['key'] ] ) ) {
                                        $item->delete_meta_data( $row['key'] );
                                        $deleted_boat_meta_keys[ $row['key'] ] = true;
                                }

                                $item->add_meta_data( $row['key'], $row['value'], true );
                        }

                        $item->add_meta_data( 'hr_boat_rental_id', (int) $values['hr_boat_rental_id'], true );
                        return;
                }

                if ( ! empty( $values['hr_self_drive_rental_id'] ) ) {
                        $deleted_self_drive_meta_keys = array();

                        foreach ( $this->get_self_drive_display_rows( $values ) as $row ) {
                                if ( empty( $deleted_self_drive_meta_keys[ $row['key'] ] ) ) {
                                        $item->delete_meta_data( $row['key'] );
                                        $deleted_self_drive_meta_keys[ $row['key'] ] = true;
                                }

                                $item->add_meta_data( $row['key'], $row['value'], empty( $row['allow_multiple'] ) );
                        }

                        $item->add_meta_data( 'hr_self_drive_rental_id', (int) $values['hr_self_drive_rental_id'], true );
                        return;
                }

                if ( ! empty( $values['hr_destination_tour_id'] ) ) {
                        $deleted_destination_tour_meta_keys = array();
                        $destination_tour_name = $this->get_destination_tour_item_title( $values );

                        if ( '' !== $destination_tour_name && method_exists( $item, 'set_name' ) ) {
                                $item->set_name( $destination_tour_name );
                        }

                        foreach ( $this->get_destination_tour_display_rows( $values ) as $row ) {
                                if ( empty( $deleted_destination_tour_meta_keys[ $row['key'] ] ) ) {
                                        $item->delete_meta_data( $row['key'] );
                                        $deleted_destination_tour_meta_keys[ $row['key'] ] = true;
                                }

                                $item->add_meta_data( $row['key'], $row['value'], empty( $row['allow_multiple'] ) );
                        }

                        $item->add_meta_data( 'hr_destination_tour_id', (int) $values['hr_destination_tour_id'], true );
                        return;
                }

                if ( ! empty( $values['hr_pickup_dropoff_service_id'] ) ) {
                        $deleted_pickup_dropoff_meta_keys = array();
                        $pickup_dropoff_name = $this->get_pickup_dropoff_item_title( $values );

                        if ( '' !== $pickup_dropoff_name && method_exists( $item, 'set_name' ) ) {
                                $item->set_name( $pickup_dropoff_name );
                        }

                        foreach ( $this->get_pickup_dropoff_display_rows( $values ) as $row ) {
                                if ( empty( $deleted_pickup_dropoff_meta_keys[ $row['key'] ] ) ) {
                                        $item->delete_meta_data( $row['key'] );
                                        $deleted_pickup_dropoff_meta_keys[ $row['key'] ] = true;
                                }

                                $item->add_meta_data( $row['key'], $row['value'], empty( $row['allow_multiple'] ) );
                        }

                        $item->add_meta_data( 'hr_pickup_dropoff_service_id', (int) $values['hr_pickup_dropoff_service_id'], true );
                        return;
                }

                if ( ! empty( $values['hr_port_transfer_id'] ) ) {
                        $deleted_port_transfer_meta_keys = array();

                        foreach ( $this->get_port_transfer_display_rows( $values ) as $row ) {
                                if ( empty( $deleted_port_transfer_meta_keys[ $row['key'] ] ) ) {
                                        $item->delete_meta_data( $row['key'] );
                                        $deleted_port_transfer_meta_keys[ $row['key'] ] = true;
                                }

                                $item->add_meta_data( $row['key'], $row['value'], empty( $row['allow_multiple'] ) );
                        }

                        $item->add_meta_data( 'hr_port_transfer_id', (int) $values['hr_port_transfer_id'], true );
                        return;
                }

                if ( ! empty( $values['hr_airport_transfer_rate_id'] ) ) {
                        $item->add_meta_data( __( 'Airport Transfer', 'hotel-rates' ), $values['hr_airport_transfer_name'] ?? '', true );

                        if ( ! empty( $values['hr_airport_transfer_route_label'] ) ) {
                                $item->add_meta_data( __( 'From/To Place', 'hotel-rates' ), $values['hr_airport_transfer_route_label'], true );
                        }

                        if ( ! empty( $values['hr_airport_transfer_direction_label'] ) ) {
                                $item->add_meta_data( __( 'Transfer Direction', 'hotel-rates' ), $values['hr_airport_transfer_direction_label'], true );
                        }

                        $item->add_meta_data( __( 'Airport Transfer Total', 'hotel-rates' ), wp_strip_all_tags( wc_price( (float) ( $values['hr_airport_transfer_total'] ?? 0 ) ) ), true );
                        $item->add_meta_data( 'hr_airport_transfer_rate_id', (int) $values['hr_airport_transfer_rate_id'], true );
                        return;
                }

                if ( ! empty( $values['hr_tour_rate_id'] ) ) {
                        $deleted_tour_meta_keys = array();
                        $is_day_tour = 'Day Tour' === (string) ( $values['hr_tour_type'] ?? '' );
                        $hotel_flight_label = $this->get_tour_display_label_from_values( $values, 'hr_tour_hotel_flight_no_label', __( 'Hotel / Flight No.', 'hotel-rates' ) );
                        $pickup_time_label = $this->get_tour_display_label_from_values( $values, 'hr_tour_pickup_time_label', __( 'Pick-up Time', 'hotel-rates' ) );
                        $show_hotel_flight = ! $is_day_tour || ! array_key_exists( 'hr_tour_hotel_flight_no_enabled', $values ) || ! empty( $values['hr_tour_hotel_flight_no_enabled'] );
                        $show_pickup_time = ! $is_day_tour || ! array_key_exists( 'hr_tour_pickup_time_enabled', $values ) || ! empty( $values['hr_tour_pickup_time_enabled'] );

                        if ( $is_day_tour ) {
                                $hotel_flight_label = $this->normalize_day_tour_display_label( $hotel_flight_label );
                                $pickup_time_label = $this->normalize_day_tour_display_label( $pickup_time_label );
                        }
                        $children_label = $this->get_tour_display_label_from_values( $values, 'hr_tour_children_label', __( 'Children', 'hotel-rates' ) );
                        $children_rate_label = sprintf(
                                /* translators: %s: child guest label. */
                                __( '%s Rate Per Guest', 'hotel-rates' ),
                                $children_label
                        );
                        $delete_tour_meta = function ( $key ) use ( $item, &$deleted_tour_meta_keys ) {
                                if ( empty( $deleted_tour_meta_keys[ $key ] ) ) {
                                        $item->delete_meta_data( $key );
                                        $deleted_tour_meta_keys[ $key ] = true;
                                }
                        };

                        $delete_tour_meta( __( 'Tour Type', 'hotel-rates' ) );
                        $item->add_meta_data( __( 'Tour Type', 'hotel-rates' ), $values['hr_tour_type'] ?? '', true );
                        $delete_tour_meta( __( 'Tour', 'hotel-rates' ) );
                        $item->add_meta_data( __( 'Tour', 'hotel-rates' ), $values['hr_tour_name'] ?? '', true );

                        if ( $is_day_tour && ! empty( $values['hr_tour_date'] ) ) {
                                $delete_tour_meta( __( 'Tour Date', 'hotel-rates' ) );
                                $item->add_meta_data( __( 'Tour Date', 'hotel-rates' ), $values['hr_tour_date'], true );
                        }

                        if ( ! empty( $values['hr_tour_place_name'] ) ) {
                                $delete_tour_meta( __( 'Place', 'hotel-rates' ) );
                                $item->add_meta_data( __( 'Place', 'hotel-rates' ), $values['hr_tour_place_name'], true );
                        }

                        if ( ! empty( $values['hr_tour_pax'] ) ) {
                                $delete_tour_meta( __( 'Guests', 'hotel-rates' ) );
                                $delete_tour_meta( __( 'Adults', 'hotel-rates' ) );
                                $item->add_meta_data( __( 'Adults', 'hotel-rates' ), (int) $values['hr_tour_pax'], true );
                        }

                        $delete_tour_meta( __( 'Children', 'hotel-rates' ) );
                        $delete_tour_meta( $children_label );
                        if ( ! empty( $values['hr_tour_children'] ) ) {
                                $item->add_meta_data( $children_label, (int) $values['hr_tour_children'], true );
                        }

                        $delete_tour_meta( __( 'Babies', 'hotel-rates' ) );
                        $delete_tour_meta( __( '0-2 Infant', 'hotel-rates' ) );
                        if ( ! empty( $values['hr_tour_infants'] ) ) {
                                $item->add_meta_data( __( '0-2 Infant', 'hotel-rates' ), (int) $values['hr_tour_infants'], true );
                        }

                        $delete_tour_meta( __( 'Children Rate Per Guest', 'hotel-rates' ) );
                        $delete_tour_meta( $children_rate_label );
                        if ( isset( $values['hr_tour_children_unit_price'] ) && ! empty( $values['hr_tour_children'] ) ) {
                                $item->add_meta_data( $children_rate_label, wp_strip_all_tags( wc_price( (float) $values['hr_tour_children_unit_price'] ) ), true );
                        }

                        if ( $is_day_tour ) {
                                $delete_tour_meta( __( 'Hotel / Flight No.', 'hotel-rates' ) );
                                $delete_tour_meta( $hotel_flight_label );
                                $delete_tour_meta( __( 'Pick-up Time', 'hotel-rates' ) );
                                $delete_tour_meta( $pickup_time_label );
                        }

                        if ( $show_hotel_flight && ! empty( $values['hr_tour_hotel_flight_no'] ) ) {
                                $delete_tour_meta( __( 'Hotel / Flight No.', 'hotel-rates' ) );
                                $delete_tour_meta( $hotel_flight_label );
                                $item->add_meta_data( $hotel_flight_label, $values['hr_tour_hotel_flight_no'], true );
                        }

                        if ( $show_pickup_time && ! empty( $values['hr_tour_pickup_time'] ) ) {
                                $delete_tour_meta( __( 'Pick-up Time', 'hotel-rates' ) );
                                $delete_tour_meta( $pickup_time_label );
                                $item->add_meta_data( $pickup_time_label, $values['hr_tour_pickup_time'], true );
                        }

                        if ( isset( $values['hr_tour_unit_price'] ) ) {
                                $delete_tour_meta( __( 'Rate Per Guest', 'hotel-rates' ) );
                                $delete_tour_meta( __( 'Rate Per Adult Guest', 'hotel-rates' ) );
                                $item->add_meta_data( __( 'Rate Per Adult Guest', 'hotel-rates' ), wp_strip_all_tags( wc_price( (float) $values['hr_tour_unit_price'] ) ), true );
                        }

                        if ( ! empty( $values['hr_tour_addons'] ) && is_array( $values['hr_tour_addons'] ) ) {
                                foreach ( $values['hr_tour_addons'] as $addon ) {
                                        $addon_key = $addon['group_label'] ?? __( 'Optional Add-on', 'hotel-rates' );
                                        $delete_tour_meta( $addon_key );
                                        $item->add_meta_data(
                                                $addon_key,
                                                sprintf(
                                                        '%1$s - %2$s',
                                                        $addon['label'] ?? '',
                                                        wp_strip_all_tags( wc_price( (float) ( $addon['rate'] ?? 0 ) ) )
                                                ),
                                                true
                                        );
                                }
                        }

                        $delete_tour_meta( __( 'Tour Total', 'hotel-rates' ) );
                        $item->add_meta_data( __( 'Tour Total', 'hotel-rates' ), wp_strip_all_tags( wc_price( (float) ( $values['hr_tour_total'] ?? 0 ) ) ), true );
                        $delete_tour_meta( 'hr_tour_rate_id' );
                        $item->add_meta_data( 'hr_tour_rate_id', (int) $values['hr_tour_rate_id'], true );
                        return;
                }

                if ( empty( $values['hr_selected_rate_id'] ) ) {
                        return;
                }

                $label = ! empty( $values['hr_selected_hotel_label'] ) ? $values['hr_selected_hotel_label'] : ( $values['hr_selected_hotel_name'] ?? '' );
                $price = isset( $values['hr_selected_price'] ) ? (float) $values['hr_selected_price'] : 0;

                if ( $label ) {
                        $item->add_meta_data( __( 'Hotel Package', 'hotel-rates' ), $label, true );
                }

                if ( $price > 0 ) {
                        $item->add_meta_data( __( 'Hotel Package Rate', 'hotel-rates' ), wp_strip_all_tags( wc_price( $price ) ), true );
                }

                $item->add_meta_data( 'hr_selected_rate_id', (int) $values['hr_selected_rate_id'], true );
        }

        private function get_rate_selection_from_request() {
                return $this->get_rate_selection( array() );
        }

        private function get_boat_display_rows( $values ) {
                if ( ! is_array( $values ) ) {
                        return array();
                }

                $rows = array();
                $upgrade_fee_amount = ! empty( $values['hr_boat_upgrade_fee_selected'] ) && isset( $values['hr_boat_upgrade_fee_amount'] ) ? (float) $values['hr_boat_upgrade_fee_amount'] : 0.0;
                $base_total = isset( $values['hr_boat_base_total'] ) ? (float) $values['hr_boat_base_total'] : ( (float) ( $values['hr_boat_total'] ?? 0 ) - $upgrade_fee_amount );
                $base_total = max( 0.0, $base_total );

                $this->add_boat_display_row( $rows, __( 'Boat Rental', 'hotel-rates' ), $values['hr_boat_rental_name'] ?? '' );
                $this->add_boat_display_row( $rows, __( 'Rental Date', 'hotel-rates' ), $values['hr_boat_rental_date'] ?? '' );

                if ( ! empty( $values['hr_boat_passengers'] ) ) {
                        $this->add_boat_display_row( $rows, __( 'Passengers', 'hotel-rates' ), (string) (int) $values['hr_boat_passengers'] );
                }

                if ( ! empty( $values['hr_boat_rate_label'] ) ) {
                        $this->add_boat_display_row(
                                $rows,
                                __( 'Boat Rate and Capacity', 'hotel-rates' ),
                                sprintf(
                                        '%1$s - %2$s',
                                        $values['hr_boat_rate_label'],
                                        wp_strip_all_tags( wc_price( $base_total ) )
                                )
                        );
                }

                $this->add_boat_display_row( $rows, __( 'Boarding Time', 'hotel-rates' ), $values['hr_boat_boarding_time'] ?? '' );

                if ( ! empty( $values['hr_boat_upgrade_fee_selected'] ) && $upgrade_fee_amount > 0 ) {
                        $this->add_boat_display_row(
                                $rows,
                                ! empty( $values['hr_boat_upgrade_fee_label'] ) ? $values['hr_boat_upgrade_fee_label'] : __( 'Upgrade to Bigger Boat Fee', 'hotel-rates' ),
                                wp_strip_all_tags( wc_price( $upgrade_fee_amount ) )
                        );
                }

                if ( isset( $values['hr_boat_total'] ) ) {
                        $this->add_boat_display_row( $rows, __( 'Boat Rental Total', 'hotel-rates' ), wp_strip_all_tags( wc_price( (float) $values['hr_boat_total'] ) ) );
                }

                return $rows;
        }

        private function add_boat_display_row( &$rows, $key, $value ) {
                if ( '' === trim( (string) $value ) ) {
                        return;
                }

                $rows[] = array(
                        'key'   => $key,
                        'value' => $value,
                );
        }

        private function get_self_drive_display_rows( $values ) {
                if ( ! is_array( $values ) ) {
                        return array();
                }

                $rows = array();

                $this->add_self_drive_display_row( $rows, __( 'Rental Date', 'hotel-rates' ), $this->format_self_drive_summary_date_text( $this->get_self_drive_display_value( $values, 'hr_self_drive_rental_date' ) ) );
                $this->add_self_drive_display_row( $rows, __( 'Pick-up/Delivery Time', 'hotel-rates' ), $this->get_self_drive_display_value( $values, 'hr_self_drive_start_time' ) );
                $this->add_self_drive_display_row( $rows, __( 'Return Date', 'hotel-rates' ), $this->format_self_drive_summary_date_text( $this->get_self_drive_display_value( $values, 'hr_self_drive_return_date' ) ) );
                $this->add_self_drive_display_row( $rows, __( 'Return Time', 'hotel-rates' ), $this->get_self_drive_display_value( $values, 'hr_self_drive_return_time' ) );

                if ( ! empty( $values['hr_self_drive_days'] ) ) {
                        $this->add_self_drive_display_row( $rows, __( 'No. of Day (s)', 'hotel-rates' ), (string) (int) $values['hr_self_drive_days'] );
                }

                if ( ! empty( $values['hr_self_drive_guests'] ) ) {
                        $this->add_self_drive_display_row( $rows, __( 'No. of Guest (s)', 'hotel-rates' ), (string) (int) $values['hr_self_drive_guests'] );
                }

                $this->add_self_drive_display_row( $rows, __( 'Hotel/Resort Name', 'hotel-rates' ), $this->get_self_drive_display_value( $values, 'hr_self_drive_hotel_name' ) );

                if ( ! empty( $values['hr_self_drive_items'] ) && is_array( $values['hr_self_drive_items'] ) ) {
                        $vehicle_number = 1;

                        foreach ( $values['hr_self_drive_items'] as $self_drive_item ) {
                                if ( ! is_array( $self_drive_item ) ) {
                                        continue;
                                }

                                $quantity = (int) ( $self_drive_item['quantity'] ?? 0 );
                                $name = trim( (string) ( $self_drive_item['name'] ?? '' ) );
                                $line_total = (float) ( $self_drive_item['line_total'] ?? 0 );

                                if ( $quantity < 1 || '' === $name ) {
                                        continue;
                                }

                                $this->add_self_drive_display_row(
                                        $rows,
                                        sprintf(
                                                /* translators: %d: vehicle row number. */
                                                __( 'Vehicle %d', 'hotel-rates' ),
                                                $vehicle_number
                                        ),
                                        sprintf(
                                                '%1$s x %2$d = %3$s',
                                                $name,
                                                $quantity,
                                                wp_strip_all_tags( wc_price( $line_total ) )
                                        )
                                );

                                $vehicle_number++;
                        }
                }

                $this->add_self_drive_display_row( $rows, __( 'Handover Options', 'hotel-rates' ), $this->get_self_drive_display_value( $values, 'hr_self_drive_handover_label' ) );
                $this->add_self_drive_display_row( $rows, __( 'Delivery Address', 'hotel-rates' ), $this->get_self_drive_display_value( $values, 'hr_self_drive_delivery_address' ) );

                return $rows;
        }

        private function get_self_drive_display_value( $values, $key ) {
                if ( ! isset( $values[ $key ] ) || is_array( $values[ $key ] ) || is_object( $values[ $key ] ) ) {
                        return '';
                }

                return trim( (string) $values[ $key ] );
        }

        private function add_self_drive_display_row( &$rows, $key, $value, $allow_multiple = false ) {
                if ( '' === trim( (string) $value ) ) {
                        return;
                }

                $rows[] = array(
                        'key'            => $key,
                        'value'          => $value,
                        'allow_multiple' => (bool) $allow_multiple,
                );
        }

        private function format_self_drive_summary_date_text( $value ) {
                $value = trim( (string) $value );
                $date = $this->parse_self_drive_request_date( $value );

                return $date ? $date->format( 'd-m-y' ) : $value;
        }

        private function get_destination_tour_display_rows( $values ) {
                if ( ! is_array( $values ) ) {
                        return array();
                }

                $rows = array();
                $rental_rate = isset( $values['hr_destination_tour_rental_rate'] ) ? (float) $values['hr_destination_tour_rental_rate'] : 0.0;
                $pickup_addon_rate = isset( $values['hr_destination_tour_pickup_addon_rate'] ) ? (float) $values['hr_destination_tour_pickup_addon_rate'] : 0.0;
                $pickup_addon_label = $this->get_destination_tour_display_value( $values, 'hr_destination_tour_pickup_addon_label' );

                $this->add_destination_tour_display_row( $rows, __( 'Rental Date', 'hotel-rates' ), $this->format_port_transfer_date_text( $this->get_destination_tour_display_value( $values, 'hr_destination_tour_rental_date' ) ) );

                if ( ! empty( $values['hr_destination_tour_guests'] ) ) {
                        $this->add_destination_tour_display_row( $rows, __( 'No. of Guest', 'hotel-rates' ), (string) (int) $values['hr_destination_tour_guests'] );
                }

                $this->add_destination_tour_display_row( $rows, __( 'Hotel/Resort Name', 'hotel-rates' ), $this->get_destination_tour_display_value( $values, 'hr_destination_tour_hotel_name' ) );
                $this->add_destination_tour_display_row( $rows, __( 'Pick-up Time', 'hotel-rates' ), $this->get_destination_tour_display_value( $values, 'hr_destination_tour_pickup_time' ) );
                $this->add_destination_tour_display_row( $rows, __( 'Tourist Destination', 'hotel-rates' ), $this->get_destination_tour_display_value( $values, 'hr_destination_tour_destination_label' ) );

                if ( $rental_rate > 0 ) {
                        $this->add_destination_tour_display_row( $rows, __( 'Rental Rate', 'hotel-rates' ), $this->format_port_transfer_price_text( $rental_rate ) );
                }

                if ( '' !== $pickup_addon_label && $pickup_addon_rate > 0 ) {
                        $this->add_destination_tour_display_row(
                                $rows,
                                __( 'For Pick-up Outside Dumaguete City', 'hotel-rates' ),
                                sprintf(
                                        '%1$s - %2$s',
                                        $pickup_addon_label,
                                        $this->format_port_transfer_price_text( $pickup_addon_rate )
                                )
                        );
                }

                return $rows;
        }

        private function get_destination_tour_item_title( $values ) {
                if ( ! is_array( $values ) || empty( $values['hr_destination_tour_name'] ) ) {
                        return '';
                }

                $title = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $values['hr_destination_tour_name'] ) ) );

                return $title;
        }

        private function get_destination_tour_display_value( $values, $key ) {
                if ( ! isset( $values[ $key ] ) || is_array( $values[ $key ] ) || is_object( $values[ $key ] ) ) {
                        return '';
                }

                return trim( (string) $values[ $key ] );
        }

        private function add_destination_tour_display_row( &$rows, $key, $value, $allow_multiple = false ) {
                if ( '' === trim( (string) $value ) ) {
                        return;
                }

                $rows[] = array(
                        'key'            => $key,
                        'value'          => $value,
                        'allow_multiple' => (bool) $allow_multiple,
                );
        }

        private function get_pickup_dropoff_display_rows( $values ) {
                if ( ! is_array( $values ) ) {
                        return array();
                }

                $rows = array();
                $unit_price = isset( $values['hr_pickup_dropoff_unit_price'] ) ? (float) $values['hr_pickup_dropoff_unit_price'] : 0.0;
                $addons = ! empty( $values['hr_pickup_dropoff_addons'] ) && is_array( $values['hr_pickup_dropoff_addons'] ) ? $values['hr_pickup_dropoff_addons'] : array();
                $addons_total = isset( $values['hr_pickup_dropoff_addons_total'] ) ? (float) $values['hr_pickup_dropoff_addons_total'] : 0.0;

                if ( $addons_total <= 0 && ! empty( $addons ) ) {
                        foreach ( $addons as $addon ) {
                                $addons_total += is_array( $addon ) ? (float) ( $addon['rate'] ?? 0 ) : 0.0;
                        }
                }

                $this->add_pickup_dropoff_display_row( $rows, __( 'From', 'hotel-rates' ), $this->get_pickup_dropoff_display_value( $values, 'hr_pickup_dropoff_from' ) );
                $this->add_pickup_dropoff_display_row( $rows, __( 'Drop-off To', 'hotel-rates' ), $this->get_pickup_dropoff_display_value( $values, 'hr_pickup_dropoff_to' ) );
                $this->add_pickup_dropoff_display_row( $rows, __( 'Rental Date', 'hotel-rates' ), $this->format_port_transfer_date_text( $this->get_pickup_dropoff_display_value( $values, 'hr_pickup_dropoff_rental_date' ) ) );
                $this->add_pickup_dropoff_display_row( $rows, __( 'Pick-up Time', 'hotel-rates' ), $this->get_pickup_dropoff_display_value( $values, 'hr_pickup_dropoff_pickup_time' ) );
                $this->add_pickup_dropoff_display_row( $rows, __( 'Hotel/Resort Name', 'hotel-rates' ), $this->get_pickup_dropoff_display_value( $values, 'hr_pickup_dropoff_hotel_name' ) );

                if ( ! empty( $values['hr_pickup_dropoff_passengers'] ) ) {
                        $this->add_pickup_dropoff_display_row( $rows, __( 'No. of Passengers', 'hotel-rates' ), (string) (int) $values['hr_pickup_dropoff_passengers'] );
                }

                if ( $unit_price > 0 ) {
                        $this->add_pickup_dropoff_display_row( $rows, __( 'Route Rate', 'hotel-rates' ), $this->format_port_transfer_price_text( $unit_price ) );
                }

                foreach ( $addons as $addon ) {
                        if ( ! is_array( $addon ) ) {
                                continue;
                        }

                        $addon_label = isset( $addon['label'] ) ? trim( (string) $addon['label'] ) : '';
                        $addon_rate = (float) ( $addon['rate'] ?? 0 );

                        if ( '' === $addon_label && $addon_rate <= 0 ) {
                                continue;
                        }

                        $this->add_pickup_dropoff_display_row(
                                $rows,
                                __( 'Add-on', 'hotel-rates' ),
                                sprintf(
                                        '%1$s - %2$s',
                                        '' !== $addon_label ? $addon_label : __( 'Add-on', 'hotel-rates' ),
                                        $this->format_port_transfer_price_text( $addon_rate )
                                ),
                                true
                        );
                }

                if ( $addons_total > 0 ) {
                        $this->add_pickup_dropoff_display_row( $rows, __( 'Add-ons Total', 'hotel-rates' ), $this->format_port_transfer_price_text( $addons_total ) );
                }

                return $rows;
        }

        private function get_pickup_dropoff_item_title( $values ) {
                if ( ! is_array( $values ) || empty( $values['hr_pickup_dropoff_service_name'] ) ) {
                        return '';
                }

                return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $values['hr_pickup_dropoff_service_name'] ) ) );
        }

        private function get_pickup_dropoff_display_value( $values, $key ) {
                if ( ! isset( $values[ $key ] ) || is_array( $values[ $key ] ) || is_object( $values[ $key ] ) ) {
                        return '';
                }

                return trim( (string) $values[ $key ] );
        }

        private function add_pickup_dropoff_display_row( &$rows, $key, $value, $allow_multiple = false ) {
                if ( '' === trim( (string) $value ) ) {
                        return;
                }

                $rows[] = array(
                        'key'            => $key,
                        'value'          => $value,
                        'allow_multiple' => (bool) $allow_multiple,
                );
        }

        private function get_port_transfer_display_rows( $values ) {
                if ( ! is_array( $values ) ) {
                        return array();
                }

                $rows = array();
                $transfer_type = $this->get_port_transfer_display_value( $values, 'hr_port_transfer_type' );
                $transfer_type_label = $this->get_port_transfer_display_value( $values, 'hr_port_transfer_type_label' );
                $from = $this->get_port_transfer_display_value( $values, 'hr_port_transfer_from' );
                $to = $this->get_port_transfer_display_value( $values, 'hr_port_transfer_to' );
                $unit_price = isset( $values['hr_port_transfer_unit_price'] ) ? (float) $values['hr_port_transfer_unit_price'] : 0.0;
                $addons = ! empty( $values['hr_port_transfer_addons'] ) && is_array( $values['hr_port_transfer_addons'] ) ? $values['hr_port_transfer_addons'] : array();
                $addons_total = isset( $values['hr_port_transfer_addons_total'] ) ? (float) $values['hr_port_transfer_addons_total'] : 0.0;
                $return_dropoff = $this->get_port_transfer_display_value( $values, 'hr_port_transfer_return_dropoff' );

                if ( in_array( $transfer_type, array( 'one_way', 'roundtrip' ), true ) ) {
                        $transfer_type_label = 'roundtrip' === $transfer_type ? __( 'Round-trip Transfer', 'hotel-rates' ) : __( 'One-way Transfer', 'hotel-rates' );
                }

                if ( $addons_total <= 0 && ! empty( $addons ) ) {
                        foreach ( $addons as $addon ) {
                                $addons_total += is_array( $addon ) ? (float) ( $addon['rate'] ?? 0 ) : 0.0;
                        }
                }

                $this->add_port_transfer_display_row( $rows, __( 'Transfer Type', 'hotel-rates' ), $transfer_type_label );
                $this->add_port_transfer_display_row( $rows, __( 'From', 'hotel-rates' ), $from );
                $this->add_port_transfer_display_row( $rows, __( 'To', 'hotel-rates' ), $to );
                $this->add_port_transfer_display_row( $rows, __( 'Arrival Date', 'hotel-rates' ), $this->format_port_transfer_date_text( $this->get_port_transfer_display_value( $values, 'hr_port_transfer_date' ) ) );
                $this->add_port_transfer_display_row( $rows, __( 'Arrival Time', 'hotel-rates' ), $this->get_port_transfer_display_value( $values, 'hr_port_transfer_time' ) );
                $this->add_port_transfer_display_row( $rows, __( 'Flight #', 'hotel-rates' ), $this->get_port_transfer_display_value( $values, 'hr_port_transfer_flight_number' ) );
                $this->add_port_transfer_display_row( $rows, __( 'Hotel/Resort Name', 'hotel-rates' ), $this->get_port_transfer_display_value( $values, 'hr_port_transfer_hotel_name' ) );

                if ( 'roundtrip' === $transfer_type ) {
                        $this->add_port_transfer_display_row( $rows, __( 'Return Date', 'hotel-rates' ), $this->format_port_transfer_date_text( $this->get_port_transfer_display_value( $values, 'hr_port_transfer_return_date' ) ) );
                        $this->add_port_transfer_display_row( $rows, __( 'Return Time', 'hotel-rates' ), $this->get_port_transfer_display_value( $values, 'hr_port_transfer_return_time' ) );
                        $this->add_port_transfer_display_row( $rows, __( 'Drop-off To', 'hotel-rates' ), $return_dropoff );
                }

                if ( ! empty( $values['hr_port_transfer_passengers'] ) ) {
                        $this->add_port_transfer_display_row( $rows, __( '# of Passengers', 'hotel-rates' ), (string) (int) $values['hr_port_transfer_passengers'] );
                }

                if ( $unit_price > 0 ) {
                        if ( 'roundtrip' === $transfer_type ) {
                                $this->add_port_transfer_display_row(
                                        $rows,
                                        __( 'Round-trip Rate', 'hotel-rates' ),
                                        sprintf(
                                                '%1$s x 2 = %2$s',
                                                $this->format_port_transfer_price_text( $unit_price ),
                                                $this->format_port_transfer_price_text( $unit_price * 2 )
                                        )
                                );
                        } else {
                                $this->add_port_transfer_display_row( $rows, __( 'One-way Rate', 'hotel-rates' ), $this->format_port_transfer_price_text( $unit_price ) );
                        }
                }

                foreach ( $addons as $addon ) {
                        if ( ! is_array( $addon ) ) {
                                continue;
                        }

                        $addon_label = isset( $addon['label'] ) ? trim( (string) $addon['label'] ) : '';
                        $addon_rate = (float) ( $addon['rate'] ?? 0 );

                        if ( '' === $addon_label && $addon_rate <= 0 ) {
                                continue;
                        }

                        $this->add_port_transfer_display_row(
                                $rows,
                                __( 'Add-on', 'hotel-rates' ),
                                sprintf(
                                        '%1$s - %2$s',
                                        '' !== $addon_label ? $addon_label : __( 'Add-on', 'hotel-rates' ),
                                        $this->format_port_transfer_price_text( $addon_rate )
                                ),
                                true
                        );
                }

                if ( $addons_total > 0 ) {
                        $this->add_port_transfer_display_row( $rows, __( 'Add-ons Total', 'hotel-rates' ), $this->format_port_transfer_price_text( $addons_total ) );
                }

                return $rows;
        }

        private function get_port_transfer_display_value( $values, $key ) {
                if ( ! isset( $values[ $key ] ) || is_array( $values[ $key ] ) || is_object( $values[ $key ] ) ) {
                        return '';
                }

                return trim( (string) $values[ $key ] );
        }

        private function add_port_transfer_display_row( &$rows, $key, $value, $allow_multiple = false ) {
                if ( '' === trim( (string) $value ) ) {
                        return;
                }

                $rows[] = array(
                        'key'            => $key,
                        'value'          => $value,
                        'allow_multiple' => (bool) $allow_multiple,
                );
        }

        private function format_port_transfer_price_text( $amount ) {
                return wp_strip_all_tags( wc_price( (float) $amount ) );
        }

        private function format_port_transfer_percent_text( $percent ) {
                $text = rtrim( rtrim( number_format( (float) $percent, 2, '.', '' ), '0' ), '.' );

                return '' === $text ? '0' : $text;
        }

        private function format_port_transfer_date_text( $value ) {
                $value = trim( (string) $value );
                $date = $this->parse_self_drive_request_date( $value );

                return $date ? $date->format( 'd-m-Y' ) : $value;
        }

        private function get_port_transfer_rate_calculation_text( $unit_price, $multiplier, $addons_total, $total ) {
                $calculation = sprintf(
                        /* translators: %s: formatted one-way rate. */
                        __( 'One-way rate %s', 'hotel-rates' ),
                        $this->format_port_transfer_price_text( $unit_price )
                );

                if ( $multiplier > 1 ) {
                        $calculation .= sprintf( ' x %d', (int) $multiplier );
                }

                if ( $addons_total > 0 ) {
                        $calculation .= sprintf(
                                /* translators: %s: formatted add-ons total. */
                                __( ' + add-ons %s', 'hotel-rates' ),
                                $this->format_port_transfer_price_text( $addons_total )
                        );
                }

                if ( $multiplier > 1 || $addons_total > 0 ) {
                        $calculation .= ' = ' . $this->format_port_transfer_price_text( $total );
                }

                return $calculation;
        }

        private function is_blank_item_data_key( $key ) {
                $key = html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) );
                $key = str_replace( "\xc2\xa0", ' ', $key );
                $key = preg_replace( '/\s+/u', '', $key );

                return '' === $key || ':' === $key;
        }

        private function cleanup_package_item_data( $item_data, $cart_item = array() ) {
                $cleaned = array();

                foreach ( $item_data as $data ) {
                        if ( ! is_array( $data ) ) {
                                $cleaned[] = $data;
                                continue;
                        }

                        $key = isset( $data['key'] ) ? (string) $data['key'] : ( isset( $data['name'] ) ? (string) $data['name'] : '' );

                        if ( $this->should_remove_package_item_data( $key, $cart_item ) ) {
                                continue;
                        }

                        $cleaned[] = $data;
                }

                return $cleaned;
        }

        private function should_remove_package_item_data( $key, $cart_item = array() ) {
                $key_slug = $this->get_item_data_key_slug( $key );

                if ( '' === $key_slug ) {
                        return false;
                }

                if ( 0 === strpos( $key_slug, 'hr_package_' ) ) {
                        return true;
                }

                if ( in_array( $key_slug, array(
                        'package_deal',
                        'package_name',
                        'package_total',
                        'airport_transfer_fee',
                        'seaport_transfer_fee',
                ), true ) ) {
                        return true;
                }

                if ( preg_match( '/^day_[0-9]+_activity$/', $key_slug ) ) {
                        return true;
                }

                foreach ( $this->get_package_display_keys( $cart_item ) as $display_key ) {
                        if ( $key_slug === $this->get_item_data_key_slug( $display_key ) ) {
                                return true;
                        }
                }

                return false;
        }

        private function get_package_display_keys( $cart_item ) {
                if ( ! is_array( $cart_item ) ) {
                        return array();
                }

                $keys = array(
                        __( 'Package Deal', 'hotel-rates' ),
                        __( 'Airport Transfer Fee', 'hotel-rates' ),
                        __( 'Seaport Transfer Fee', 'hotel-rates' ),
                        __( 'Package Total', 'hotel-rates' ),
                );

                if ( ! empty( $cart_item['hr_package_hotels'] ) && is_array( $cart_item['hr_package_hotels'] ) ) {
                        foreach ( $cart_item['hr_package_hotels'] as $hotel_selection ) {
                                $keys[] = sprintf( __( '%s Hotel', 'hotel-rates' ), $hotel_selection['place_name'] ?? __( 'Package', 'hotel-rates' ) );
                        }
                }

                if ( ! empty( $cart_item['hr_package_activities'] ) && is_array( $cart_item['hr_package_activities'] ) ) {
                        foreach ( $cart_item['hr_package_activities'] as $activity_selection ) {
                                $keys[] = sprintf( __( 'Day %d Activity', 'hotel-rates' ), (int) ( $activity_selection['day'] ?? 0 ) );
                        }
                }

                return array_filter( $keys );
        }

        private function get_item_data_key_slug( $key ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );

                return trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );
        }

        private function cleanup_airport_transfer_item_data( $item_data ) {
                $cleaned = array();
                $has_arrival_time = false;

                foreach ( $item_data as $data ) {
                        if ( ! is_array( $data ) ) {
                                $cleaned[] = $data;
                                continue;
                        }

                        $key   = isset( $data['key'] ) ? (string) $data['key'] : ( isset( $data['name'] ) ? (string) $data['name'] : '' );
                        $value = $data['display'] ?? ( $data['value'] ?? '' );

                        if ( $this->should_remove_airport_transfer_item_data( $key ) ) {
                                continue;
                        }

                        if ( $this->is_arrival_time_item_data( $key, $value ) ) {
                                $data['key'] = __( 'Arrival Time', 'hotel-rates' );
                                if ( isset( $data['name'] ) ) {
                                        $data['name'] = __( 'Arrival Time', 'hotel-rates' );
                                }

                                if ( ! $has_arrival_time ) {
                                        $cleaned[] = $data;
                                        $has_arrival_time  = true;
                                }
                                continue;
                        }

                        $cleaned[] = $data;
                }

                return $cleaned;
        }

        private function cleanup_motorbike_item_data( $item_data ) {
                $cleaned = array();

                foreach ( $item_data as $data ) {
                        if ( ! is_array( $data ) ) {
                                $cleaned[] = $data;
                                continue;
                        }

                        $key = isset( $data['key'] ) ? (string) $data['key'] : ( isset( $data['name'] ) ? (string) $data['name'] : '' );

                        if ( $this->should_remove_motorbike_item_data( $key ) ) {
                                continue;
                        }

                        $cleaned[] = $data;
                }

                return $cleaned;
        }

        private function cleanup_self_drive_item_data( $item_data ) {
                $cleaned = array();

                foreach ( $item_data as $data ) {
                        if ( ! is_array( $data ) ) {
                                $cleaned[] = $data;
                                continue;
                        }

                        $key = isset( $data['key'] ) ? (string) $data['key'] : ( isset( $data['name'] ) ? (string) $data['name'] : '' );

                        if ( $this->should_remove_self_drive_item_data( $key, true ) ) {
                                continue;
                        }

                        $cleaned[] = $data;
                }

                return $cleaned;
        }

        private function cleanup_boat_item_data( $item_data, $cart_item = array() ) {
                $cleaned = array();

                foreach ( $item_data as $data ) {
                        if ( ! is_array( $data ) ) {
                                $cleaned[] = $data;
                                continue;
                        }

                        $key = isset( $data['key'] ) ? (string) $data['key'] : ( isset( $data['name'] ) ? (string) $data['name'] : '' );

                        if ( $this->is_boat_clean_order_item_meta_key( $key, $cart_item ) ) {
                                continue;
                        }

                        if ( $this->should_remove_boat_item_data( $key, true ) ) {
                                continue;
                        }

                        $cleaned[] = $data;
                }

                return $cleaned;
        }

        private function cleanup_port_transfer_item_data( $item_data ) {
                $cleaned = array();

                foreach ( $item_data as $data ) {
                        if ( ! is_array( $data ) ) {
                                $cleaned[] = $data;
                                continue;
                        }

                        $key = isset( $data['key'] ) ? (string) $data['key'] : ( isset( $data['name'] ) ? (string) $data['name'] : '' );

                        if ( $this->is_blank_item_data_key( $key ) ) {
                                continue;
                        }

                        if ( $this->should_remove_port_transfer_item_data( $key, true ) ) {
                                continue;
                        }

                        $cleaned[] = $data;
                }

                return $cleaned;
        }

        private function cleanup_destination_tour_item_data( $item_data ) {
                $cleaned = array();

                foreach ( $item_data as $data ) {
                        if ( ! is_array( $data ) ) {
                                $cleaned[] = $data;
                                continue;
                        }

                        $key = isset( $data['key'] ) ? (string) $data['key'] : ( isset( $data['name'] ) ? (string) $data['name'] : '' );

                        if ( $this->is_blank_item_data_key( $key ) ) {
                                continue;
                        }

                        if ( $this->should_remove_destination_tour_item_data( $key, true ) ) {
                                continue;
                        }

                        $cleaned[] = $data;
                }

                return $cleaned;
        }

        private function cleanup_pickup_dropoff_item_data( $item_data ) {
                $cleaned = array();

                foreach ( $item_data as $data ) {
                        if ( ! is_array( $data ) ) {
                                $cleaned[] = $data;
                                continue;
                        }

                        $key = isset( $data['key'] ) ? (string) $data['key'] : ( isset( $data['name'] ) ? (string) $data['name'] : '' );

                        if ( $this->is_blank_item_data_key( $key ) ) {
                                continue;
                        }

                        if ( $this->is_pickup_dropoff_clean_order_item_meta_key( $key ) ) {
                                continue;
                        }

                        if ( $this->should_remove_pickup_dropoff_item_data( $key, true ) ) {
                                continue;
                        }

                        $cleaned[] = $data;
                }

                return $cleaned;
        }

        private function cleanup_day_tour_item_data( $item_data, $cart_item = array() ) {
                $cleaned = array();
                $seen_keys = array();

                foreach ( $item_data as $data ) {
                        if ( ! is_array( $data ) ) {
                                $cleaned[] = $data;
                                continue;
                        }

                        $key_property = isset( $data['key'] ) ? 'key' : ( isset( $data['name'] ) ? 'name' : '' );
                        $key = '' !== $key_property ? (string) $data[ $key_property ] : '';

                        if (
                                $this->is_blank_item_data_key( $key )
                                || $this->should_remove_tour_item_data( $key, true, $cart_item )
                                || $this->is_day_tour_legacy_display_key( $key, $cart_item )
                        ) {
                                continue;
                        }

                        $normalized_key = $this->normalize_day_tour_display_label( $key );
                        $key_slug = $this->get_item_data_key_slug( $normalized_key );

                        if ( '' === $normalized_key || '' === $key_slug || isset( $seen_keys[ $key_slug ] ) ) {
                                continue;
                        }

                        if ( '' !== $key_property ) {
                                $data[ $key_property ] = $normalized_key;
                        }

                        $seen_keys[ $key_slug ] = true;
                        $cleaned[] = $data;
                }

                return $cleaned;
        }

        private function is_day_tour_legacy_display_key( $key, $values = array() ) {
                $key_slug = $this->get_item_data_key_slug( $this->normalize_day_tour_display_label( $key ) );
                $legacy_slugs = array(
                        'tour_date',
                        'choose_pick_up_time',
                        'pick_up_information',
                        'optional_add_ons',
                        'for_pick_up_outside_dumaguete_city',
                );

                if ( in_array( $key_slug, $legacy_slugs, true ) ) {
                        return true;
                }

                if ( empty( $values['hr_tour_legacy_display_keys'] ) || ! is_array( $values['hr_tour_legacy_display_keys'] ) ) {
                        return false;
                }

                foreach ( $values['hr_tour_legacy_display_keys'] as $legacy_key ) {
                        if ( $key_slug === $this->get_item_data_key_slug( $legacy_key ) ) {
                                return true;
                        }
                }

                return false;
        }

        private function cleanup_tour_item_data( $item_data, $cart_item = array() ) {
                $cleaned = array();

                foreach ( $item_data as $data ) {
                        if ( ! is_array( $data ) ) {
                                $cleaned[] = $data;
                                continue;
                        }

                        $key = isset( $data['key'] ) ? (string) $data['key'] : ( isset( $data['name'] ) ? (string) $data['name'] : '' );

                        if ( $this->should_remove_tour_item_data( $key, true, $cart_item ) ) {
                                continue;
                        }

                        $cleaned[] = $data;
                }

                return $cleaned;
        }

        private function get_tour_display_label_from_values( $values, $label_key, $default_label ) {
                if ( ! is_array( $values ) || empty( $values[ $label_key ] ) ) {
                        return $default_label;
                }

                $label = trim( wp_strip_all_tags( (string) $values[ $label_key ] ) );

                return '' !== $label ? $label : $default_label;
        }

        private function get_tour_children_label_from_rates( $children_rates ) {
                if ( ! is_array( $children_rates ) || empty( $children_rates['label'] ) ) {
                        return __( 'Children', 'hotel-rates' );
                }

                $label = trim( wp_strip_all_tags( (string) $children_rates['label'] ) );

                return '' !== $label ? $label : __( 'Children', 'hotel-rates' );
        }

        private function is_tour_clean_order_item_meta_key( $key, $values = array() ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                if ( in_array( $key_slug, array(
                        'tour_type',
                        'tour',
                        'tour_date',
                        'place',
                        'guests',
                        'adult',
                        'adults',
                        'children',
                        'children_rate_per_guest',
                        'childrens',
                        '0_2_infant',
                        'hotel_flight_no',
                        'hotel_flight_number',
                        'pick_up_time',
                        'pickup_time',
                        'rate_per_guest',
                        'rate_per_adult_guest',
                        'tour_total',
                ), true ) ) {
                        return true;
                }

                if ( ! empty( $values['hr_tour_children_label'] ) ) {
                        $children_label = $this->get_tour_display_label_from_values( $values, 'hr_tour_children_label', __( 'Children', 'hotel-rates' ) );
                        $children_rate_label = sprintf(
                                /* translators: %s: child guest label. */
                                __( '%s Rate Per Guest', 'hotel-rates' ),
                                $children_label
                        );

                        foreach ( array( $children_label, $children_rate_label ) as $display_label ) {
                                $display_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', strtolower( html_entity_decode( wp_strip_all_tags( (string) $display_label ), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) ), '_' );

                                if ( '' !== $display_slug && $display_slug === $key_slug ) {
                                        return true;
                                }
                        }
                }

                return $this->is_tour_booking_field_display_key( $key, $values ) || $this->is_tour_addon_display_key( $key, $values );
        }

        private function should_remove_tour_item_data( $key, $remove_display_like_keys = false, $values = array() ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                if ( 0 === strpos( $key_slug, 'hr_tour_' ) ) {
                        return true;
                }

                if ( in_array( $key_slug, array(
                        'tour_rate_id',
                        'tour_name',
                        'tour_type',
                        'tour_place_name',
                        'tour_pax',
                        'tour_children',
                        'tour_children_label',
                        'tour_children_unit_price',
                        'tour_children_total',
                        'tour_infants',
                        'tour_hotel_flight_no',
                        'tour_hotel_flight_no_label',
                        'tour_hotel_flight_number',
                        'tour_pickup_time',
                        'tour_pickup_time_label',
                        'tour_unit_price',
                        'tour_addons',
                        'tour_addons_total',
                        'tour_addons_payload',
                        'tour_total',
                        'adult',
                        'adults',
                        'ovabrw_adult',
                        'ovabrw_adults',
                        'child',
                        'children',
                        'children_rate_per_guest',
                        'childrens',
                        'ovabrw_child',
                        'ovabrw_children',
                        'ovabrw_childrens',
                        'baby',
                        'babies',
                        'ovabrw_baby',
                        'ovabrw_babies',
                ), true ) ) {
                        return true;
                }

                if ( $remove_display_like_keys && $this->is_tour_clean_order_item_meta_key( $key, $values ) ) {
                        return true;
                }

                return false;
        }

        private function is_tour_booking_field_display_key( $key, $values = array() ) {
                if ( ! is_array( $values ) ) {
                        return false;
                }

                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                foreach ( array( 'hr_tour_hotel_flight_no_label', 'hr_tour_pickup_time_label' ) as $label_key ) {
                        if ( empty( $values[ $label_key ] ) ) {
                                continue;
                        }

                        $label_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', strtolower( html_entity_decode( wp_strip_all_tags( (string) $values[ $label_key ] ), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) ), '_' );

                        if ( '' !== $label_slug && $label_slug === $key_slug ) {
                                return true;
                        }
                }

                return false;
        }

        private function is_tour_addon_display_key( $key, $values = array() ) {
                if ( empty( $values['hr_tour_addons'] ) || ! is_array( $values['hr_tour_addons'] ) ) {
                        return false;
                }

                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                foreach ( $values['hr_tour_addons'] as $addon ) {
                        if ( ! is_array( $addon ) ) {
                                continue;
                        }

                        $group_label = trim( (string) ( $addon['group_label'] ?? '' ) );

                        if ( '' === $group_label ) {
                                $group_label = __( 'Optional Add-on', 'hotel-rates' );
                        }

                        $group_slug = strtolower( html_entity_decode( wp_strip_all_tags( $group_label ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                        $group_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $group_slug ), '_' );

                        if ( $key_slug === $group_slug ) {
                                return true;
                        }
                }

                return false;
        }

        private function should_remove_airport_transfer_item_data( $key ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                if ( 0 === strpos( $key_slug, 'hr_airport_transfer_' ) ) {
                        return true;
                }

                if ( in_array( $key_slug, array( 'airport_transfer_direction', 'airport_transfer_direction_label' ), true ) ) {
                        return true;
                }

                if ( in_array( $key_slug, array( 'place', 'airport_transfer_rate' ), true ) ) {
                        return true;
                }

                if ( false !== strpos( $key_slug, 'airport_transfer_rate' ) ) {
                        return true;
                }

                if ( false !== strpos( $key_slug, 'airport_seaport' ) && false !== strpos( $key_slug, 'to_from' ) ) {
                        return true;
                }

                if ( false !== strpos( $key_slug, 'airport' ) && false !== strpos( $key_slug, 'seaport' ) && false !== strpos( $key_slug, 'from' ) ) {
                        return true;
                }

                return false;
        }

	private function should_remove_motorbike_item_data( $key ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                if ( 0 === strpos( $key_slug, 'hr_motorbike_' ) ) {
                        return true;
                }

                if ( in_array( $key_slug, array(
                        'motorbike_selection_payload',
                        'motorbike_total',
				'motorbike_days',
				'motorbike_start_time',
				'motorbike_handover_option',
				'motorbike_delivery_address',
				'adult',
				'adults',
				'children',
				'childrens',
				'baby',
				'babies',
				'guest',
				'guests',
				'number_of_guest',
				'number_of_guests',
				'no_of_guest',
				'no_of_guests',
				'total_guest',
				'total_guests',
				'ovabrw_adults',
				'ovabrw_children',
				'ovabrw_childrens',
				'ovabrw_baby',
				'ovabrw_babies',
				'ovabrw_guest',
				'ovabrw_guests',
				'quantity',
				'ovabrw_quantity',
				'ovabrw_total',
				'ovabrw_total_price',
				'ovabrw_amount',
				'total_price',
			), true ) ) {
                        return true;
                }

		if ( false !== strpos( $key_slug, 'motorbike' ) && ( false !== strpos( $key_slug, 'model' ) || false !== strpos( $key_slug, 'selection' ) ) ) {
			return true;
		}

		if ( false !== strpos( $key_slug, 'driver' ) && false !== strpos( $key_slug, 'license' ) ) {
			return true;
		}

		if ( false !== strpos( $key_slug, 'proof' ) && false !== strpos( $key_slug, 'billing' ) ) {
			return true;
		}

		if ( false !== strpos( $key_slug, 'handover' ) ) {
			return true;
		}

		if ( false !== strpos( $key_slug, 'pickup_delivery' ) || false !== strpos( $key_slug, 'pick_up_delivery' ) || false !== strpos( $key_slug, 'pickup_time' ) || false !== strpos( $key_slug, 'pick_up_time' ) || false !== strpos( $key_slug, 'delivery_time' ) ) {
			return true;
		}

		if ( false !== strpos( $key_slug, 'delivery_address' ) ) {
			return true;
		}

		return false;
	}

        private function should_remove_boat_item_data( $key, $remove_display_like_keys = false ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                if ( 0 === strpos( $key_slug, 'hr_boat_' ) ) {
                        return true;
                }

                if ( in_array( $key_slug, array(
                        'boat_selection_payload',
                        'boat_total',
                        'boat_rental',
                        'boat_rental_total',
                        'boat_rental_date',
                        'boat_passengers',
                        'boat_rate_index',
                        'boat_rate_label',
                        'boat_boarding_time',
                        'booking_date',
                        'tour_date',
                        'rental_date',
                        'pickup_date',
                        'pick_up_date',
                        'adult',
                        'adults',
                        'children',
                        'childrens',
                        'baby',
                        'babies',
                        'guest',
                        'guests',
                        'passenger',
                        'passengers',
                        'number_of_passenger',
                        'number_of_passengers',
                        'no_of_passenger',
                        'no_of_passengers',
                        'ovabrw_adults',
                        'ovabrw_children',
                        'ovabrw_childrens',
                        'ovabrw_baby',
                        'ovabrw_babies',
                        'quantity',
                        'ovabrw_quantity',
                        'ovabrw_total',
                        'ovabrw_total_price',
                        'ovabrw_amount',
                        'total_price',
                ), true ) ) {
                        return true;
                }

                if ( false !== strpos( $key_slug, 'boat' ) && ( false !== strpos( $key_slug, 'rate' ) || false !== strpos( $key_slug, 'capacity' ) || false !== strpos( $key_slug, 'selection' ) ) ) {
                        return true;
                }

                if ( false !== strpos( $key_slug, 'boarding_time' ) || false !== strpos( $key_slug, 'board_time' ) ) {
                        return true;
                }

                if ( $remove_display_like_keys && in_array( $key_slug, array(
                        'boat',
                        'boat_rental',
                        'boat_rate',
                        'boat_rental_total',
                        'capacity',
                        'boat_rate_and_capacity',
                        'tour_date',
                        'booking_date',
                        'rental_date',
                        'boarding_time',
                        'passengers',
                ), true ) ) {
                        return true;
                }

                return false;
        }

        private function is_boat_clean_order_item_meta_key( $key, $values = array() ) {
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) ), '_' );

                foreach ( $this->get_boat_display_rows( $values ) as $row ) {
                        $label_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', strtolower( html_entity_decode( wp_strip_all_tags( (string) $row['key'] ), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) ), '_' );

                        if ( '' !== $label_slug && $key_slug === $label_slug ) {
                                return true;
                        }
                }

                return false;
        }

        private function is_self_drive_clean_order_item_meta_key( $key, $values = array() ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                if ( preg_match( '/^vehicle_\d+$/', $key_slug ) ) {
                        return true;
                }

                if ( 'delivery_address' === $key_slug && '' === $this->get_self_drive_display_value( $values, 'hr_self_drive_delivery_address' ) ) {
                        return false;
                }

                return in_array( $key_slug, array(
                        'rental_date',
                        'pick_up_delivery_time',
                        'pickup_delivery_time',
                        'return_date',
                        'return_time',
                        'no_of_day_s',
                        'no_of_days',
                        'no_of_guest_s',
                        'no_of_guests',
                        'hotel_resort_name',
                        'handover_options',
                        'delivery_address',
                ), true );
        }

        private function is_destination_tour_clean_order_item_meta_key( $key ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                return in_array( $key_slug, array(
                        'rental_date',
                        'no_of_guest',
                        'no_of_guests',
                        'hotel_resort_name',
                        'pick_up_time',
                        'pickup_time',
                        'tourist_destination',
                        'rental_rate',
                        'for_pick_up_outside_dumaguete_city',
                        'for_pickup_outside_dumaguete_city',
                ), true );
        }

        private function is_pickup_dropoff_clean_order_item_meta_key( $key ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                return in_array( $key_slug, array(
                        'from',
                        'drop_off_to',
                        'dropoff_to',
                        'rental_date',
                        'pick_up_time',
                        'pickup_time',
                        'hotel_resort_name',
                        'no_of_passengers',
                        'passengers',
                        'route_rate',
                        'add_on',
                        'add_ons_total',
                ), true );
        }

        private function is_port_transfer_clean_order_item_meta_key( $key ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                return in_array( $key_slug, array(
                        'transfer_type',
                        'from',
                        'to',
                        'arrival_date',
                        'arrival_time',
                        'flight',
                        'flight_number',
                        'hotel_name',
                        'hotel_resort_name',
                        'passengers',
                        'of_passengers',
                        'drop_off_to',
                        'dropoff_to',
                        'return_date',
                        'return_time',
                        'one_way_rate',
                        'round_trip_rate',
                        'add_on',
                        'add_ons_total',
                ), true );
        }

        private function should_remove_destination_tour_item_data( $key, $remove_display_like_keys = false ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                if ( 0 === strpos( $key_slug, 'hr_destination_tour_' ) ) {
                        return true;
                }

                if ( in_array( $key_slug, array(
                        'destination_tour_selection_payload',
                        'destination_tour_total',
                        'destination_tour_rental_date',
                        'destination_tour_guests',
                        'destination_tour_hotel_name',
                        'destination_tour_pickup_time',
                        'destination_tour_destination_index',
                        'destination_tour_destination_label',
                        'destination_tour_destination_duration',
                        'destination_tour_rental_rate',
                        'destination_tour_pickup_addon_index',
                        'destination_tour_pickup_addon_label',
                        'destination_tour_pickup_addon_rate',
                        'adult',
                        'adults',
                        'children',
                        'childrens',
                        'baby',
                        'babies',
                        'guest',
                        'guests',
                        'number_of_guest',
                        'number_of_guests',
                        'no_of_guest',
                        'no_of_guests',
                        'ovabrw_adults',
                        'ovabrw_children',
                        'ovabrw_childrens',
                        'ovabrw_baby',
                        'ovabrw_babies',
                        'quantity',
                        'ovabrw_quantity',
                        'ovabrw_total',
                        'ovabrw_total_price',
                        'ovabrw_amount',
                        'total_price',
                ), true ) ) {
                        return true;
                }

                if ( false !== strpos( $key_slug, 'destination_tour' ) && ( false !== strpos( $key_slug, 'selection' ) || false !== strpos( $key_slug, 'destination' ) || false !== strpos( $key_slug, 'addon' ) ) ) {
                        return true;
                }

                if ( $remove_display_like_keys && in_array( $key_slug, array(
                        'destination_tour',
                        'tour',
                        'tour_type',
                        'rental_date',
                        'pickup_date',
                        'pick_up_date',
                        'tour_date',
                        'booking_date',
                        'guests',
                        'hotel',
                        'hotel_name',
                        'hotel_resort_name',
                        'pick_up_information',
                        'pickup_information',
                        'pick_up_time',
                        'pickup_time',
                        'tourist_destination',
                        'destination',
                        'duration',
                        'rental_rate',
                        'rate',
                        'route',
                        'route_and_capacity',
                        'from',
                        'to',
                        'transfer_type',
                        'drop_off_information',
                        'dropoff_information',
                        'drop_off_location',
                        'dropoff_location',
                        'drop_off_to',
                        'dropoff_to',
                        'add_on',
                        'add_ons',
                        'addon',
                        'addons',
                        'for_pick_up_outside_dumaguete_city',
                        'for_pickup_outside_dumaguete_city',
                ), true ) ) {
                        return true;
                }

                if ( $remove_display_like_keys && (
                        false !== strpos( $key_slug, 'pickup_information' ) ||
                        false !== strpos( $key_slug, 'pick_up_information' ) ||
                        false !== strpos( $key_slug, 'dropoff' ) ||
                        false !== strpos( $key_slug, 'drop_off' ) ||
                        false !== strpos( $key_slug, 'outside_dumaguete' )
                ) ) {
                        return true;
                }

                return false;
        }

        private function should_remove_pickup_dropoff_item_data( $key, $remove_display_like_keys = false ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                if ( 0 === strpos( $key_slug, 'hr_pickup_dropoff_' ) ) {
                        return true;
                }

                if ( in_array( $key_slug, array(
                        'pickup_dropoff_selection_payload',
                        'pickup_dropoff_total',
                        'pickup_dropoff_rental_date',
                        'pickup_dropoff_passengers',
                        'pickup_dropoff_from',
                        'pickup_dropoff_to',
                        'pickup_dropoff_route_index',
                        'pickup_dropoff_route_label',
                        'pickup_dropoff_pickup_time',
                        'pickup_dropoff_hotel_name',
                        'pickup_dropoff_unit_price',
                        'pickup_dropoff_addons',
                        'pickup_dropoff_addons_total',
                        'adult',
                        'adults',
                        'children',
                        'childrens',
                        'baby',
                        'babies',
                        'guest',
                        'guests',
                        'passenger',
                        'passengers',
                        'number_of_passenger',
                        'number_of_passengers',
                        'no_of_passenger',
                        'no_of_passengers',
                        'ovabrw_adults',
                        'ovabrw_children',
                        'ovabrw_childrens',
                        'ovabrw_baby',
                        'ovabrw_babies',
                        'quantity',
                        'ovabrw_quantity',
                        'ovabrw_total',
                        'ovabrw_total_price',
                        'ovabrw_amount',
                        'total_price',
                ), true ) ) {
                        return true;
                }

                if ( false !== strpos( $key_slug, 'pickup_dropoff' ) && ( false !== strpos( $key_slug, 'route' ) || false !== strpos( $key_slug, 'selection' ) || false !== strpos( $key_slug, 'addon' ) ) ) {
                        return true;
                }

                if ( $remove_display_like_keys && in_array( $key_slug, array(
                        'pick_up_drop_off',
                        'pickup_dropoff',
                        'pickup_and_dropoff',
                        'pick_up_and_drop_off',
                        'from',
                        'to',
                        'drop_off_to',
                        'dropoff_to',
                        'route',
                        'route_and_capacity',
                        'route_rate',
                        'from_to',
                        'to_from',
                        'rental_date',
                        'pickup_date',
                        'pick_up_date',
                        'tour_date',
                        'booking_date',
                        'pick_up_time',
                        'pickup_time',
                        'time',
                        'hotel',
                        'hotel_name',
                        'hotel_resort_name',
                        'passengers',
                        'passenger',
                        'no_of_passengers',
                        'no_of_passenger',
                        'number_of_passengers',
                        'number_of_passenger',
                        'guests',
                        'guest',
                        'add_on',
                        'add_ons',
                        'addon',
                        'addons',
                        'add_ons_total',
                        'total',
                        'rate',
                ), true ) ) {
                        return true;
                }

                if ( $remove_display_like_keys && (
                        false !== strpos( $key_slug, 'dropoff' ) ||
                        false !== strpos( $key_slug, 'drop_off' ) ||
                        false !== strpos( $key_slug, 'pick_up_time' ) ||
                        false !== strpos( $key_slug, 'pickup_time' ) ||
                        false !== strpos( $key_slug, 'hotel_resort' ) ||
                        false !== strpos( $key_slug, 'route_rate' ) ||
                        false !== strpos( $key_slug, 'passenger' )
                ) ) {
                        return true;
                }

                return false;
        }

        private function should_remove_port_transfer_item_data( $key, $remove_display_like_keys = false ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                if ( 0 === strpos( $key_slug, 'hr_port_transfer_' ) ) {
                        return true;
                }

                if ( in_array( $key_slug, array(
                        'port_transfer_selection_payload',
                        'port_transfer_total',
                        'port_transfer_date',
                        'port_transfer_passengers',
                        'port_transfer_route_index',
                        'port_transfer_route_label',
                        'port_transfer_from',
                        'port_transfer_to',
                        'port_transfer_direction',
                        'port_transfer_type',
                        'port_transfer_time',
                        'port_transfer_flight_number',
                        'port_transfer_hotel_name',
                        'port_transfer_dropoff',
                        'port_transfer_return_date',
                        'port_transfer_return_time',
                        'port_transfer_return_dropoff',
                        'port_transfer_addons',
                        'port_transfer_addons_total',
                        'adult',
                        'adults',
                        'children',
                        'childrens',
                        'baby',
                        'babies',
                        'guest',
                        'guests',
                        'passenger',
                        'passengers',
                        'number_of_passenger',
                        'number_of_passengers',
                        'no_of_passenger',
                        'no_of_passengers',
                        'ovabrw_adults',
                        'ovabrw_children',
                        'ovabrw_childrens',
                        'ovabrw_baby',
                        'ovabrw_babies',
                        'quantity',
                        'ovabrw_quantity',
                        'ovabrw_total',
                        'ovabrw_total_price',
                        'ovabrw_amount',
                        'total_price',
                ), true ) ) {
                        return true;
                }

                if ( false !== strpos( $key_slug, 'port_transfer' ) && ( false !== strpos( $key_slug, 'route' ) || false !== strpos( $key_slug, 'selection' ) || false !== strpos( $key_slug, 'type' ) ) ) {
                        return true;
                }

                if ( false !== strpos( $key_slug, 'return_drop' ) || false !== strpos( $key_slug, 'return_location' ) ) {
                        return true;
                }

                if ( $remove_display_like_keys && in_array( $key_slug, array(
                        'port_transfer',
                        'transfer_type',
                        'route',
                        'route_and_capacity',
                        'from',
                        'to',
                        'check_in_date',
                        'checkin_date',
                        'arrival_date',
                        'tour_date',
                        'transfer_date',
                        'booking_date',
                        'pickup_date',
                        'pick_up_date',
                        'time',
                        'arrival_time',
                        'transfer_time',
                        'pickup_time',
                        'pick_up_time',
                        'flight',
                        'flight_number',
                        'pick_up_information',
                        'pickup_information',
                        'hotel_name',
                        'hotel_resort_name',
                        'drop_off_information',
                        'dropoff_information',
                        'drop_off_location',
                        'dropoff_location',
                        'drop_off_to',
                        'dropoff_to',
                        'return_date',
                        'return_time',
                        'pick_up_time',
                        'return_drop_off_location',
                        'return_dropoff_location',
                        'return_drop_off_to',
                        'return_dropoff_to',
                        'addons',
                        'add_ons',
                        'add_on',
                        'add_ons_total',
                        'one_way_rate',
                        'roundtrip_rate',
                        'round_trip_rate',
                        'rate_calculation',
                        'passengers',
                        'of_passengers',
                ), true ) ) {
                        return true;
                }

                if ( $remove_display_like_keys && (
                        false !== strpos( $key_slug, 'check_in_date' ) ||
                        false !== strpos( $key_slug, 'checkin_date' ) ||
                        false !== strpos( $key_slug, 'pickup_information' ) ||
                        false !== strpos( $key_slug, 'pick_up_information' ) ||
                        false !== strpos( $key_slug, 'dropoff_information' ) ||
                        false !== strpos( $key_slug, 'drop_off_information' ) ||
                        false !== strpos( $key_slug, 'pickup_info' ) ||
                        false !== strpos( $key_slug, 'pick_up_info' ) ||
                        false !== strpos( $key_slug, 'dropoff_info' ) ||
                        false !== strpos( $key_slug, 'drop_off_info' )
                ) ) {
                        return true;
                }

                if ( $remove_display_like_keys && (
                        0 === strpos( $key_slug, 'to_from' ) ||
                        0 === strpos( $key_slug, 'from_to' ) ||
                        false !== strpos( $key_slug, 'to_from_' ) ||
                        false !== strpos( $key_slug, 'from_to_' )
                ) ) {
                        return true;
                }

                return false;
        }

        private function should_remove_self_drive_item_data( $key, $remove_display_like_keys = false ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = trim( preg_replace( '/[^a-z0-9]+/', '_', $key_text ), '_' );

                if ( 0 === strpos( $key_slug, 'hr_self_drive_' ) ) {
                        return true;
                }

                if ( in_array( $key_slug, array(
                        'self_drive_selection_payload',
                        'self_drive_vehicle_index',
                        'self_drive_vehicle_rate',
                        'self_drive_total',
                        'self_drive_rental_date',
                        'self_drive_return_date',
                        'self_drive_days',
                        'self_drive_start_time',
                        'self_drive_return_time',
                        'self_drive_guests',
                        'self_drive_hotel_name',
                        'self_drive_handover_option',
                        'self_drive_delivery_address',
                        'adult',
                        'adults',
                        'children',
                        'childrens',
                        'baby',
                        'babies',
                        'guest',
                        'guests',
                        'number_of_guest',
                        'number_of_guests',
                        'no_of_guest',
                        'no_of_guests',
                        'total_guest',
                        'total_guests',
                        'hotel',
                        'hotel_name',
                        'name_of_hotel',
                        'hotel_resort',
                        'ovabrw_adults',
                        'ovabrw_children',
                        'ovabrw_childrens',
                        'ovabrw_baby',
                        'ovabrw_babies',
                        'ovabrw_guest',
                        'ovabrw_guests',
                        'quantity',
                        'ovabrw_quantity',
                        'ovabrw_total',
                        'ovabrw_total_price',
                        'ovabrw_amount',
                        'total_price',
                ), true ) ) {
                        return true;
                }

                if ( false !== strpos( $key_slug, 'self_drive' ) && ( false !== strpos( $key_slug, 'vehicle' ) || false !== strpos( $key_slug, 'selection' ) ) ) {
                        return true;
                }

                if ( false !== strpos( $key_slug, 'vehicle_type' ) || false !== strpos( $key_slug, 'choose_vehicle' ) ) {
                        return true;
                }

                if ( false !== strpos( $key_slug, 'vehicle_handover' ) || false !== strpos( $key_slug, 'pickup_delivery' ) || false !== strpos( $key_slug, 'pick_up_delivery' ) || false !== strpos( $key_slug, 'pickup_time' ) || false !== strpos( $key_slug, 'pick_up_time' ) || false !== strpos( $key_slug, 'delivery_time' ) ) {
                        return true;
                }

                if ( $remove_display_like_keys && preg_match( '/^vehicle_\d+$/', $key_slug ) ) {
                        return true;
                }

                if ( $remove_display_like_keys && in_array( $key_slug, array(
                        'self_drive_rental',
                        'self_drive_rental_total',
                        'vehicle',
                        'vehicle_type',
                        'start_time',
                        'pick_up_delivery_time',
                        'pickup_delivery_time',
                        'return_time',
                        'rental_date',
                        'return_date',
                        'guests',
                        'no_of_guest_s',
                        'hotel',
                        'hotel_name',
                        'hotel_resort_name',
                        'no_of_day_s',
                        'no_of_days',
                        'number_of_days',
                        'handover_option',
                        'handover_options',
                        'delivery_address',
                        'driver_s_license_proof_billing',
                        'drivers_license_proof_billing',
                ), true ) ) {
                        return true;
                }

                if ( $remove_display_like_keys && false !== strpos( $key_slug, 'driver' ) && false !== strpos( $key_slug, 'license' ) ) {
                        return true;
                }

                if ( $remove_display_like_keys && false !== strpos( $key_slug, 'proof' ) && false !== strpos( $key_slug, 'billing' ) ) {
                        return true;
                }

                return false;
        }

        private function order_item_has_meta_key( $item, $key ) {
                foreach ( $item->get_meta_data() as $meta ) {
                        if ( is_object( $meta ) && isset( $meta->key ) && (string) $meta->key === (string) $key ) {
                                return true;
                        }
                }

                return false;
        }

        private function is_arrival_time_item_data( $key, $value ) {
                $key_text = strtolower( html_entity_decode( wp_strip_all_tags( (string) $key ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
                $key_slug = preg_replace( '/[^a-z0-9]+/', '_', $key_text );

                if ( false !== strpos( $key_slug, 'arrival_time' ) ) {
                        return true;
                }

                return $this->is_blank_item_data_key( $key ) && $this->is_arrival_time_value( $value );
        }

        private function is_arrival_time_value( $value ) {
                if ( is_array( $value ) || is_object( $value ) ) {
                        return false;
                }

                $value = html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES, get_bloginfo( 'charset' ) );
                $value = strtolower( trim( $value ) );

                if ( '' === $value ) {
                        return false;
                }

                return (bool) preg_match( '/^\d{1,2}(?::\d{2})?\s*(?:am|pm)$/i', $value )
                        || (bool) preg_match( '/^\d{1,2}:\d{2}$/', $value )
                        || (bool) preg_match( '/^(?:[01]?\d|2[0-3])$/', $value );
        }

        private function get_motorbike_selection_from_request( $product_id ) {
                $rental_id        = isset( $_POST['hr_motorbike_rental_id'] ) ? (int) wp_unslash( $_POST['hr_motorbike_rental_id'] ) : 0;
                $linked_rental_id = $this->get_linked_product_motorbike_rental_id( $product_id );

                if ( ! $rental_id || $rental_id !== $linked_rental_id ) {
                        return false;
                }

                $motorbike_data = $this->get_motorbike_booking_data( $rental_id );

                if ( empty( $motorbike_data ) ) {
                        $this->add_motorbike_notice( __( 'This motorbike rental is unavailable.', 'hotel-rates' ) );
                        return false;
                }

                $payload = isset( $_POST['hr_motorbike_selection_payload'] ) ? json_decode( wp_unslash( $_POST['hr_motorbike_selection_payload'] ), true ) : array();
                $payload = is_array( $payload ) ? $payload : array();
                $selected_quantities = isset( $payload['models'] ) && is_array( $payload['models'] ) ? $payload['models'] : array();
                $days = isset( $_POST['hr_motorbike_days'] ) ? (int) wp_unslash( $_POST['hr_motorbike_days'] ) : 0;
                $start_time = isset( $_POST['hr_motorbike_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['hr_motorbike_start_time'] ) ) : '';
                $handover_option = isset( $_POST['hr_motorbike_handover_option'] ) ? sanitize_key( wp_unslash( $_POST['hr_motorbike_handover_option'] ) ) : '';
                $delivery_address = isset( $_POST['hr_motorbike_delivery_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hr_motorbike_delivery_address'] ) ) : '';
                $day_options = ! empty( $motorbike_data['dayOptions'] ) ? array_map( 'intval', $motorbike_data['dayOptions'] ) : array();
                $items = array();
                $subtotal = 0.0;

                if ( $days < 1 || ( ! empty( $day_options ) && ! in_array( $days, $day_options, true ) ) ) {
                        $this->add_motorbike_notice( __( 'Please select the number of rental days.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' === $start_time || ( ! empty( $motorbike_data['startTimes'] ) && ! in_array( $start_time, $motorbike_data['startTimes'], true ) ) ) {
                        $this->add_motorbike_notice( __( 'Please select a pick-up/delivery time.', 'hotel-rates' ) );
                        return false;
                }

                if ( ! in_array( $handover_option, array( 'pickup', 'delivery' ), true ) ) {
                        $this->add_motorbike_notice( __( 'Please select a motorbike handover option.', 'hotel-rates' ) );
                        return false;
                }

                if ( 'delivery' === $handover_option && '' === $delivery_address ) {
                        $this->add_motorbike_notice( __( 'Please enter the delivery address.', 'hotel-rates' ) );
                        return false;
                }

                foreach ( $motorbike_data['models'] as $index => $model ) {
                        $quantity = isset( $selected_quantities[ (string) $index ] ) ? (int) $selected_quantities[ (string) $index ] : 0;
                        $stock    = max( 0, (int) ( $model['stock'] ?? 0 ) );
                        $rate     = (float) ( $model['rate'] ?? 0 );

                        if ( $quantity < 1 ) {
                                continue;
                        }

                        if ( $quantity > $stock ) {
                                $this->add_motorbike_notice( __( 'One or more motorbike quantities is unavailable.', 'hotel-rates' ) );
                                return false;
                        }

                        $line_total = $rate * $quantity * $days;
                        $subtotal += $line_total;
                        $items[] = array(
                                'index'      => (int) $index,
                                'name'       => $model['name'] ?? '',
                                'rate'       => $rate,
                                'quantity'   => $quantity,
                                'days'       => $days,
                                'line_total' => $line_total,
                        );
                }

                if ( empty( $items ) ) {
                        $this->add_motorbike_notice( __( 'Please choose at least one motorbike quantity.', 'hotel-rates' ) );
                        return false;
                }

                $delivery_fee = 'delivery' === $handover_option ? (float) ( $motorbike_data['deliveryFee'] ?? 0 ) : 0.0;
                return array(
                        'rental_id'        => (int) $motorbike_data['rentalId'],
                        'rental_name'      => $motorbike_data['rentalName'],
                        'items'            => $items,
                        'days'             => $days,
                        'start_time'       => $start_time,
                        'handover_option'  => $handover_option,
                        'handover_label'   => 'delivery' === $handover_option ? __( 'Deliver to Address', 'hotel-rates' ) : __( 'Pick-up', 'hotel-rates' ),
                        'delivery_address' => 'delivery' === $handover_option ? $delivery_address : '',
                        'delivery_fee'     => $delivery_fee,
                        'total'            => $subtotal + $delivery_fee,
                        'payload'          => wp_json_encode( $payload ),
                );
        }

        private function get_boat_selection_from_request( $product_id ) {
                $rental_id        = isset( $_POST['hr_boat_rental_id'] ) ? (int) wp_unslash( $_POST['hr_boat_rental_id'] ) : 0;
                $linked_rental_id = $this->get_linked_product_boat_rental_id( $product_id );

                if ( ! $rental_id || $rental_id !== $linked_rental_id ) {
                        return false;
                }

                $boat_data = $this->get_boat_booking_data( $rental_id );

                if ( empty( $boat_data ) ) {
                        $this->add_motorbike_notice( __( 'This boat rental is unavailable.', 'hotel-rates' ) );
                        return false;
                }

                $payload = isset( $_POST['hr_boat_selection_payload'] ) ? json_decode( wp_unslash( $_POST['hr_boat_selection_payload'] ), true ) : array();
                $payload = is_array( $payload ) ? $payload : array();
                $rental_date = $this->get_self_drive_request_date_value( 'hr_boat_rental_date', array( 'ovabrw_pickup_date', 'pickup_date', 'checkin_date', 'tour_date', 'rental_date', 'booking_date' ) );
                $passengers = isset( $_POST['hr_boat_passengers'] ) ? (int) wp_unslash( $_POST['hr_boat_passengers'] ) : 0;
                $rate_index_raw = isset( $_POST['hr_boat_rate_index'] ) ? (string) wp_unslash( $_POST['hr_boat_rate_index'] ) : '';
                $boarding_time = $this->get_posted_text_value( 'hr_boat_boarding_time' );
                $upgrade_fee_selected = isset( $_POST['hr_boat_upgrade_fee_selected'] ) && '1' === sanitize_key( wp_unslash( $_POST['hr_boat_upgrade_fee_selected'] ) );
                $upgrade_fee_label = '';
                $upgrade_fee_amount = 0.0;
                $rates = ! empty( $boat_data['rates'] ) && is_array( $boat_data['rates'] ) ? $boat_data['rates'] : array();
                $rate_index;
                $rate;
                $min_pax;
                $max_pax;
                $base_total;
                $total;

                if ( $passengers < 1 && isset( $_POST['hr_boat_passengers_display'] ) ) {
                        $passengers = (int) wp_unslash( $_POST['hr_boat_passengers_display'] );
                }

                if ( '' === $rate_index_raw && isset( $payload['rateIndex'] ) ) {
                        $rate_index_raw = (string) $payload['rateIndex'];
                }

                if ( ! $upgrade_fee_selected && ! empty( $payload['upgradeFeeSelected'] ) ) {
                        $upgrade_fee_selected = true;
                }

                if ( '' === $rental_date ) {
                        $this->add_motorbike_notice( __( 'Please select a rental date.', 'hotel-rates' ) );
                        return false;
                }

                $rental_date_object = $this->parse_self_drive_request_date( $rental_date );
                $today = new DateTimeImmutable( 'today', function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' ) );

                if ( ! $rental_date_object || $rental_date_object < $today ) {
                        $this->add_motorbike_notice( __( 'Please select today or a future rental date.', 'hotel-rates' ) );
                        return false;
                }

                if ( $passengers < 1 ) {
                        $this->add_motorbike_notice( __( 'Please enter the number of passengers.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' === $rate_index_raw ) {
                        $this->add_motorbike_notice( __( 'Please select a boat rate and capacity.', 'hotel-rates' ) );
                        return false;
                }

                $rate_index = (int) $rate_index_raw;
                if ( ! isset( $rates[ $rate_index ] ) ) {
                        $this->add_motorbike_notice( __( 'Please select a valid boat rate and capacity.', 'hotel-rates' ) );
                        return false;
                }

                $rate = $rates[ $rate_index ];
                $min_pax = max( 1, (int) ( $rate['minPax'] ?? 1 ) );
                $max_pax = max( $min_pax, (int) ( $rate['maxPax'] ?? $min_pax ) );
                $base_total = (float) ( $rate['rate'] ?? 0 );

                if ( $passengers < $min_pax || $passengers > $max_pax || $base_total <= 0 ) {
                        $this->add_motorbike_notice( __( 'Please select a boat rate that matches the number of passengers.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' === $boarding_time || ( ! empty( $boat_data['boardingTimes'] ) && ! in_array( $boarding_time, $boat_data['boardingTimes'], true ) ) ) {
                        $this->add_motorbike_notice( __( 'Please select a boarding time.', 'hotel-rates' ) );
                        return false;
                }

                if ( $upgrade_fee_selected ) {
                        $configured_upgrade_fee_amount = isset( $boat_data['upgradeFeeAmount'] ) ? max( 0.0, (float) $boat_data['upgradeFeeAmount'] ) : 0.0;

                        if ( $configured_upgrade_fee_amount > 0 ) {
                                $upgrade_fee_label = ! empty( $boat_data['upgradeFeeLabel'] ) ? (string) $boat_data['upgradeFeeLabel'] : __( 'Upgrade to Bigger Boat Fee', 'hotel-rates' );
                                $upgrade_fee_amount = $configured_upgrade_fee_amount;
                        } else {
                                $upgrade_fee_selected = false;
                        }
                }

                $total = $base_total + $upgrade_fee_amount;

                return array(
                        'rental_id'     => (int) $boat_data['rentalId'],
                        'rental_name'   => $boat_data['rentalName'],
                        'rental_date'   => $rental_date,
                        'passengers'    => $passengers,
                        'rate_index'    => $rate_index,
                        'rate_label'    => $rate['label'] ?? '',
                        'min_pax'       => $min_pax,
                        'max_pax'       => $max_pax,
                        'boarding_time' => $boarding_time,
                        'base_total'    => $base_total,
                        'upgrade_fee_selected' => $upgrade_fee_selected,
                        'upgrade_fee_label' => $upgrade_fee_label,
                        'upgrade_fee_amount' => $upgrade_fee_amount,
                        'total'         => $total,
                        'credit_card_fee_percent' => max( 0.0, (float) ( $boat_data['creditCardFeePercent'] ?? 0 ) ),
                        'payload'       => wp_json_encode( array(
                                'rateIndex' => $rate_index,
                                'passengers' => $passengers,
                                'upgradeFeeSelected' => $upgrade_fee_selected,
                        ) ),
                );
        }

        private function get_destination_tour_selection_from_request( $product_id ) {
                $tour_id = isset( $_POST['hr_destination_tour_id'] ) ? (int) wp_unslash( $_POST['hr_destination_tour_id'] ) : 0;
                $linked_tour_id = $this->get_linked_product_destination_tour_id( $product_id );

                if ( ! $tour_id || $tour_id !== $linked_tour_id ) {
                        return false;
                }

                $tour_data = $this->get_destination_tour_booking_data( $tour_id, true );

                if ( empty( $tour_data ) ) {
                        $this->add_motorbike_notice( __( 'This destination tour is unavailable.', 'hotel-rates' ) );
                        return false;
                }

                $payload = isset( $_POST['hr_destination_tour_selection_payload'] ) ? json_decode( wp_unslash( $_POST['hr_destination_tour_selection_payload'] ), true ) : array();
                $payload = is_array( $payload ) ? $payload : array();
                $rental_date = $this->get_self_drive_request_date_value( 'hr_destination_tour_rental_date', array( 'hr_destination_tour_rental_date_display', 'ovabrw_pickup_date', 'pickup_date', 'checkin_date', 'tour_date', 'rental_date', 'booking_date' ) );
                $guests = isset( $_POST['hr_destination_tour_guests'] ) ? (int) wp_unslash( $_POST['hr_destination_tour_guests'] ) : 0;
                $hotel_name = $this->get_posted_text_value( 'hr_destination_tour_hotel_name' );
                $pickup_time = $this->get_posted_text_value( 'hr_destination_tour_pickup_time' );
                $destination_index_raw = isset( $_POST['hr_destination_tour_destination_index'] ) ? (string) wp_unslash( $_POST['hr_destination_tour_destination_index'] ) : '';
                $pickup_addon_index_raw = isset( $_POST['hr_destination_tour_pickup_addon_index'] ) ? (string) wp_unslash( $_POST['hr_destination_tour_pickup_addon_index'] ) : '';
                $destinations = ! empty( $tour_data['destinations'] ) && is_array( $tour_data['destinations'] ) ? $tour_data['destinations'] : array();
                $pickup_addons = ! empty( $tour_data['pickupAddons'] ) && is_array( $tour_data['pickupAddons'] ) ? $tour_data['pickupAddons'] : array();
                $pickup_times = ! empty( $tour_data['pickupTimes'] ) && is_array( $tour_data['pickupTimes'] ) ? $tour_data['pickupTimes'] : array();
                $destination;
                $destination_index;
                $pickup_addon = array();
                $pickup_addon_index = null;
                $rental_rate;
                $pickup_addon_rate = 0.0;
                $rental_date_object;
                $today;

                if ( $guests < 1 && isset( $_POST['hr_destination_tour_guests_display'] ) ) {
                        $guests = (int) wp_unslash( $_POST['hr_destination_tour_guests_display'] );
                }
                if ( $guests < 1 && isset( $_POST['ovabrw_adults'] ) ) {
                        $guests = (int) wp_unslash( $_POST['ovabrw_adults'] );
                }
                if ( '' === $hotel_name ) {
                        $hotel_name = $this->get_posted_text_value( 'hr_destination_tour_hotel_name_display' );
                }
                if ( '' === $pickup_time ) {
                        $pickup_time = $this->get_posted_text_value( 'hr_destination_tour_pickup_time_display' );
                }
                if ( '' === $destination_index_raw && isset( $payload['destinationIndex'] ) ) {
                        $destination_index_raw = (string) $payload['destinationIndex'];
                }
                if ( '' === $destination_index_raw && isset( $_POST['hr_destination_tour_destination_display'] ) ) {
                        $destination_index_raw = (string) wp_unslash( $_POST['hr_destination_tour_destination_display'] );
                }
                if ( '' === $pickup_addon_index_raw && isset( $payload['pickupAddonIndex'] ) && '' !== (string) $payload['pickupAddonIndex'] ) {
                        $pickup_addon_index_raw = (string) $payload['pickupAddonIndex'];
                }
                if ( '' === $pickup_addon_index_raw && isset( $_POST['hr_destination_tour_pickup_addon_display'] ) ) {
                        $pickup_addon_index_raw = (string) wp_unslash( $_POST['hr_destination_tour_pickup_addon_display'] );
                }

                if ( '' === $rental_date ) {
                        $this->add_motorbike_notice( __( 'Please select a rental date.', 'hotel-rates' ) );
                        return false;
                }

                $rental_date_object = $this->parse_self_drive_request_date( $rental_date );
                $today = new DateTimeImmutable( 'today', function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' ) );

                if ( ! $rental_date_object || $rental_date_object < $today ) {
                        $this->add_motorbike_notice( __( 'Please select today or a future rental date.', 'hotel-rates' ) );
                        return false;
                }

                if ( $guests < 1 ) {
                        $this->add_motorbike_notice( __( 'Please enter the number of guests.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' === $hotel_name ) {
                        $this->add_motorbike_notice( __( 'Please enter the hotel or resort name.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' === $pickup_time || ( ! empty( $pickup_times ) && ! in_array( $pickup_time, $pickup_times, true ) ) ) {
                        $this->add_motorbike_notice( __( 'Please select a pick-up time.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' === $destination_index_raw ) {
                        $this->add_motorbike_notice( __( 'Please select a tourist destination.', 'hotel-rates' ) );
                        return false;
                }

                $destination_index = (int) $destination_index_raw;
                if ( ! isset( $destinations[ $destination_index ] ) ) {
                        $this->add_motorbike_notice( __( 'Please select a valid tourist destination.', 'hotel-rates' ) );
                        return false;
                }

                $destination = $destinations[ $destination_index ];
                $rental_rate = (float) ( $destination['rate'] ?? 0 );

                if ( $rental_rate <= 0 ) {
                        $this->add_motorbike_notice( __( 'This destination tour selection is unavailable.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' !== $pickup_addon_index_raw ) {
                        $pickup_addon_index = (int) $pickup_addon_index_raw;

                        if ( ! isset( $pickup_addons[ $pickup_addon_index ] ) ) {
                                $this->add_motorbike_notice( __( 'Please select a valid pick-up add-on.', 'hotel-rates' ) );
                                return false;
                        }

                        $pickup_addon = $pickup_addons[ $pickup_addon_index ];
                        $pickup_addon_rate = max( 0.0, (float) ( $pickup_addon['rate'] ?? 0 ) );
                }

                return array(
                        'tour_id'                 => (int) $tour_data['tourId'],
                        'tour_name'               => $tour_data['tourName'],
                        'rental_date'             => $rental_date,
                        'guests'                  => $guests,
                        'hotel_name'              => $hotel_name,
                        'pickup_time'             => $pickup_time,
                        'destination_index'       => $destination_index,
                        'destination_label'       => $destination['label'] ?? '',
                        'destination_duration'    => $destination['duration'] ?? '',
                        'rental_rate'             => $rental_rate,
                        'pickup_addon_index'      => null === $pickup_addon_index ? '' : $pickup_addon_index,
                        'pickup_addon_label'      => $pickup_addon['label'] ?? '',
                        'pickup_addon_rate'       => $pickup_addon_rate,
                        'total'                   => $rental_rate + $pickup_addon_rate,
                        'credit_card_fee_percent' => max( 0.0, (float) ( $tour_data['creditCardFeePercent'] ?? 0 ) ),
                        'payload'                 => wp_json_encode( array(
                                'destinationIndex' => $destination_index,
                                'pickupAddonIndex' => null === $pickup_addon_index ? '' : $pickup_addon_index,
                        ) ),
                );
        }

        private function get_pickup_dropoff_selection_from_request( $product_id ) {
                $service_id = isset( $_POST['hr_pickup_dropoff_service_id'] ) ? (int) wp_unslash( $_POST['hr_pickup_dropoff_service_id'] ) : 0;
                $linked_service_id = $this->get_linked_product_pickup_dropoff_service_id( $product_id );

                if ( ! $service_id || $service_id !== $linked_service_id ) {
                        return false;
                }

                $service_data = $this->get_pickup_dropoff_booking_data( $service_id, true );

                if ( empty( $service_data ) ) {
                        $this->add_motorbike_notice( __( 'This pick-up and drop-off service is unavailable.', 'hotel-rates' ) );
                        return false;
                }

                $payload = isset( $_POST['hr_pickup_dropoff_selection_payload'] ) ? json_decode( wp_unslash( $_POST['hr_pickup_dropoff_selection_payload'] ), true ) : array();
                $payload = is_array( $payload ) ? $payload : array();
                $rental_date = $this->get_self_drive_request_date_value( 'hr_pickup_dropoff_rental_date', array( 'hr_pickup_dropoff_rental_date_display', 'ovabrw_pickup_date', 'pickup_date', 'checkin_date', 'tour_date', 'rental_date', 'booking_date' ) );
                $passengers = isset( $_POST['hr_pickup_dropoff_passengers'] ) ? (int) wp_unslash( $_POST['hr_pickup_dropoff_passengers'] ) : 0;
                $from = $this->get_posted_text_value( 'hr_pickup_dropoff_from' );
                $to = $this->get_posted_text_value( 'hr_pickup_dropoff_to' );
                $pickup_time = $this->get_posted_text_value( 'hr_pickup_dropoff_pickup_time' );
                $hotel_name = $this->get_posted_text_value( 'hr_pickup_dropoff_hotel_name' );
                $routes = ! empty( $service_data['routes'] ) && is_array( $service_data['routes'] ) ? $service_data['routes'] : array();
                $locations = ! empty( $service_data['locations'] ) && is_array( $service_data['locations'] ) ? $service_data['locations'] : array();
                $pickup_times = ! empty( $service_data['pickupTimes'] ) && is_array( $service_data['pickupTimes'] ) ? $service_data['pickupTimes'] : array();
                $available_addons = ! empty( $service_data['addons'] ) && is_array( $service_data['addons'] ) ? $service_data['addons'] : array();
                $selected_pair_key;
                $selected_route = null;
                $selected_route_index = null;
                $unit_price = 0.0;
                $selected_addons = array();
                $selected_addon_indices = array();
                $addons_total = 0.0;
                $rental_date_object;
                $today;

                if ( $passengers < 1 && isset( $_POST['hr_pickup_dropoff_passengers_display'] ) ) {
                        $passengers = (int) wp_unslash( $_POST['hr_pickup_dropoff_passengers_display'] );
                }
                if ( $passengers < 1 && isset( $_POST['ovabrw_adults'] ) ) {
                        $passengers = (int) wp_unslash( $_POST['ovabrw_adults'] );
                }
                if ( '' === $from ) {
                        $from = $this->get_posted_text_value( 'hr_pickup_dropoff_from_display' );
                }
                if ( '' === $from && isset( $payload['from'] ) ) {
                        $from = sanitize_text_field( (string) $payload['from'] );
                }
                if ( '' === $to ) {
                        $to = $this->get_posted_text_value( 'hr_pickup_dropoff_to_display' );
                }
                if ( '' === $to && isset( $payload['to'] ) ) {
                        $to = sanitize_text_field( (string) $payload['to'] );
                }
                if ( '' === $pickup_time ) {
                        $pickup_time = $this->get_posted_text_value( 'hr_pickup_dropoff_pickup_time_display' );
                }
                if ( '' === $hotel_name ) {
                        $hotel_name = $this->get_posted_text_value( 'hr_pickup_dropoff_hotel_name_display' );
                }

                if ( '' === $rental_date ) {
                        $this->add_motorbike_notice( __( 'Please select a rental date.', 'hotel-rates' ) );
                        return false;
                }

                $rental_date_object = $this->parse_self_drive_request_date( $rental_date );
                $today = new DateTimeImmutable( 'today', function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' ) );

                if ( ! $rental_date_object || $rental_date_object < $today ) {
                        $this->add_motorbike_notice( __( 'Please select today or a future rental date.', 'hotel-rates' ) );
                        return false;
                }

                if ( $passengers < 1 ) {
                        $this->add_motorbike_notice( __( 'Please enter the number of passengers.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' === $from || '' === $to || $from === $to || ! in_array( $from, $locations, true ) || ! in_array( $to, $locations, true ) ) {
                        $this->add_motorbike_notice( __( 'Please select valid From and Drop-off To locations.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' === $pickup_time || ( ! empty( $pickup_times ) && ! in_array( $pickup_time, $pickup_times, true ) ) ) {
                        $this->add_motorbike_notice( __( 'Please select a pick-up time.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' === $hotel_name ) {
                        $this->add_motorbike_notice( __( 'Please enter the hotel or resort name.', 'hotel-rates' ) );
                        return false;
                }

                $selected_pair_key = $this->get_pickup_dropoff_route_pair_key( $from, $to );
                foreach ( $routes as $route_index => $route ) {
                        if ( ! is_array( $route ) ) {
                                continue;
                        }

                        if ( $selected_pair_key !== $this->get_pickup_dropoff_route_pair_key( $route['from'] ?? '', $route['to'] ?? '' ) ) {
                                continue;
                        }

                        $selected_route = $route;
                        $selected_route_index = (int) $route_index;
                        break;
                }

                if ( ! $selected_route ) {
                        $this->add_motorbike_notice( __( 'This pick-up and drop-off route is unavailable.', 'hotel-rates' ) );
                        return false;
                }

                $unit_price = (float) ( $selected_route['rate'] ?? 0 );
                if ( $unit_price <= 0 ) {
                        $this->add_motorbike_notice( __( 'This pick-up and drop-off route has no rate.', 'hotel-rates' ) );
                        return false;
                }

                if ( isset( $_POST['hr_pickup_dropoff_addons'] ) ) {
                        $posted_addons = json_decode( wp_unslash( $_POST['hr_pickup_dropoff_addons'] ), true );

                        if ( is_array( $posted_addons ) ) {
                                foreach ( $posted_addons as $posted_addon ) {
                                        if ( is_array( $posted_addon ) && isset( $posted_addon['index'] ) ) {
                                                $selected_addon_indices[] = (int) $posted_addon['index'];
                                        }
                                }
                        }
                }

                if ( empty( $selected_addon_indices ) && ! empty( $payload['addons'] ) && is_array( $payload['addons'] ) ) {
                        foreach ( $payload['addons'] as $addon_index ) {
                                $selected_addon_indices[] = (int) $addon_index;
                        }
                }

                $selected_addon_indices = array_values( array_unique( array_filter( $selected_addon_indices, static function ( $index ) {
                        return $index >= 0;
                } ) ) );

                foreach ( $selected_addon_indices as $addon_index ) {
                        if ( ! isset( $available_addons[ $addon_index ] ) ) {
                                $this->add_motorbike_notice( __( 'Please select a valid add-on.', 'hotel-rates' ) );
                                return false;
                        }

                        $addon = $available_addons[ $addon_index ];
                        $addon_rate = max( 0.0, (float) ( $addon['rate'] ?? 0 ) );

                        $selected_addons[] = array(
                                'index' => $addon_index,
                                'label' => $addon['label'] ?? '',
                                'rate'  => $addon_rate,
                        );
                        $addons_total += $addon_rate;
                }

                return array(
                        'service_id'    => (int) $service_data['serviceId'],
                        'service_name'  => $service_data['serviceName'],
                        'rental_date'   => $rental_date,
                        'passengers'    => $passengers,
                        'from'          => $from,
                        'to'            => $to,
                        'route_index'   => null === $selected_route_index ? '' : $selected_route_index,
                        'route_label'   => sprintf( '%1$s to %2$s', $from, $to ),
                        'pickup_time'   => $pickup_time,
                        'hotel_name'    => $hotel_name,
                        'unit_price'    => $unit_price,
                        'addons'        => $selected_addons,
                        'addons_total'  => $addons_total,
                        'total'         => $unit_price + $addons_total,
                        'payload'       => wp_json_encode( array(
                                'from'   => $from,
                                'to'     => $to,
                                'routeIndex' => null === $selected_route_index ? '' : $selected_route_index,
                                'addons' => $selected_addon_indices,
                        ) ),
                );
        }

        private function get_port_transfer_selection_from_request( $product_id ) {
                $transfer_id = isset( $_POST['hr_port_transfer_id'] ) ? (int) wp_unslash( $_POST['hr_port_transfer_id'] ) : 0;
                $linked_transfer_id = $this->get_linked_product_port_transfer_id( $product_id );

                if ( ! $transfer_id || $transfer_id !== $linked_transfer_id ) {
                        return false;
                }

                $transfer_data = $this->get_port_transfer_booking_data( $transfer_id, true );

                if ( empty( $transfer_data ) ) {
                        $this->add_motorbike_notice( __( 'This port transfer is unavailable.', 'hotel-rates' ) );
                        return false;
                }

                $payload = isset( $_POST['hr_port_transfer_selection_payload'] ) ? json_decode( wp_unslash( $_POST['hr_port_transfer_selection_payload'] ), true ) : array();
                $payload = is_array( $payload ) ? $payload : array();
                $transfer_date = $this->get_self_drive_request_date_value( 'hr_port_transfer_date', array( 'hr_port_transfer_date_display', 'ovabrw_pickup_date', 'pickup_date', 'checkin_date', 'tour_date', 'rental_date', 'booking_date' ) );
                $return_date = $this->get_self_drive_request_date_value( 'hr_port_transfer_return_date', array( 'hr_port_transfer_return_date_display', 'ovabrw_pickoff_date', 'ovabrw_dropoff_date', 'dropoff_date', 'checkout_date' ) );
                $passengers = isset( $_POST['hr_port_transfer_passengers'] ) ? (int) wp_unslash( $_POST['hr_port_transfer_passengers'] ) : 0;
                $route_index_raw = isset( $_POST['hr_port_transfer_route_index'] ) ? (string) wp_unslash( $_POST['hr_port_transfer_route_index'] ) : '';
                $from = $this->get_posted_text_value( 'hr_port_transfer_from' );
                $to = $this->get_posted_text_value( 'hr_port_transfer_to' );
                $direction = isset( $_POST['hr_port_transfer_direction'] ) ? sanitize_key( wp_unslash( $_POST['hr_port_transfer_direction'] ) ) : '';
                $transfer_type = isset( $_POST['hr_port_transfer_type'] ) ? sanitize_key( wp_unslash( $_POST['hr_port_transfer_type'] ) ) : '';
                $transfer_time = $this->get_posted_text_value( 'hr_port_transfer_time' );
                $flight_number = $this->get_posted_text_value( 'hr_port_transfer_flight_number' );
                $hotel_name = $this->get_posted_text_value( 'hr_port_transfer_hotel_name' );
                $dropoff = $this->get_posted_text_value( 'hr_port_transfer_dropoff' );
                $return_time = $this->get_posted_text_value( 'hr_port_transfer_return_time' );
                $return_dropoff = $this->get_posted_text_value( 'hr_port_transfer_return_dropoff' );
                $routes = ! empty( $transfer_data['routes'] ) && is_array( $transfer_data['routes'] ) ? $transfer_data['routes'] : array();
                $from_options = ! empty( $transfer_data['fromOptions'] ) && is_array( $transfer_data['fromOptions'] ) ? $transfer_data['fromOptions'] : array();
                $to_options = ! empty( $transfer_data['toOptions'] ) && is_array( $transfer_data['toOptions'] ) ? $transfer_data['toOptions'] : array();
                $available_addons = ! empty( $transfer_data['addons'] ) && is_array( $transfer_data['addons'] ) ? $transfer_data['addons'] : array();
                $route_index = null;
                $route = null;
                $unit_price;
                $matrix_from;
                $matrix_to;
                $multiplier;
                $selected_addons = array();
                $selected_addon_indices = array();
                $addons_total = 0.0;
                $transfer_date_object;
                $return_date_object;

                if ( $passengers < 1 && isset( $_POST['hr_port_transfer_passengers_display'] ) ) {
                        $passengers = (int) wp_unslash( $_POST['hr_port_transfer_passengers_display'] );
                }

                if ( '' === $route_index_raw && isset( $payload['routeIndex'] ) ) {
                        $route_index_raw = (string) $payload['routeIndex'];
                }

                if ( '' === $from && isset( $payload['from'] ) ) {
                        $from = sanitize_text_field( (string) $payload['from'] );
                }

                if ( '' === $to && isset( $payload['to'] ) ) {
                        $to = sanitize_text_field( (string) $payload['to'] );
                }

                if ( '' === $direction && isset( $payload['direction'] ) ) {
                        $direction = sanitize_key( (string) $payload['direction'] );
                }

                if ( '' === $transfer_type && isset( $payload['transferType'] ) ) {
                        $transfer_type = sanitize_key( (string) $payload['transferType'] );
                }

                if ( '' === $direction ) {
                        $direction = 'from_to';
                }

                if ( '' === $transfer_date ) {
                        $this->add_motorbike_notice( __( 'Please select an arrival date.', 'hotel-rates' ) );
                        return false;
                }

                if ( $passengers < 1 ) {
                        $this->add_motorbike_notice( __( 'Please enter the number of passengers.', 'hotel-rates' ) );
                        return false;
                }

                if ( ! in_array( $direction, array( 'from_to', 'to_from' ), true ) || '' === $from || '' === $to || $from === $to ) {
                        $this->add_motorbike_notice( __( 'Please select valid From and To locations.', 'hotel-rates' ) );
                        return false;
                }

                if ( ! in_array( $transfer_type, array( 'one_way', 'roundtrip' ), true ) ) {
                        $this->add_motorbike_notice( __( 'Please select one way or roundtrip.', 'hotel-rates' ) );
                        return false;
                }

                if ( 'from_to' === $direction ) {
                        $matrix_from = $from;
                        $matrix_to = $to;
                        if ( ! in_array( $from, $from_options, true ) || ! in_array( $to, $to_options, true ) ) {
                                $this->add_motorbike_notice( __( 'Please select valid From and To locations.', 'hotel-rates' ) );
                                return false;
                        }
                } else {
                        $matrix_from = $to;
                        $matrix_to = $from;
                        if ( ! in_array( $from, $to_options, true ) || ! in_array( $to, $from_options, true ) ) {
                                $this->add_motorbike_notice( __( 'Please select valid From and To locations.', 'hotel-rates' ) );
                                return false;
                        }
                }

                if ( '' === $transfer_time ) {
                        $this->add_motorbike_notice( __( 'Please enter a transfer time.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' === $hotel_name ) {
                        $this->add_motorbike_notice( __( 'Please enter the hotel name.', 'hotel-rates' ) );
                        return false;
                }

                $dropoff = $to;

                if ( 'roundtrip' === $transfer_type ) {
                        if ( '' === $return_date ) {
                                $this->add_motorbike_notice( __( 'Please select a return date.', 'hotel-rates' ) );
                                return false;
                        }

                        $transfer_date_object = $this->parse_self_drive_request_date( $transfer_date );
                        $return_date_object = $this->parse_self_drive_request_date( $return_date );

                        if ( ! $transfer_date_object || ! $return_date_object || (int) $transfer_date_object->diff( $return_date_object )->format( '%r%a' ) < 0 ) {
                                $this->add_motorbike_notice( __( 'Please select a valid return date.', 'hotel-rates' ) );
                                return false;
                        }

                        if ( '' === $return_time || ( ! empty( $transfer_data['transferTimes'] ) && ! in_array( $return_time, $transfer_data['transferTimes'], true ) ) ) {
                                $this->add_motorbike_notice( __( 'Please select a return time.', 'hotel-rates' ) );
                                return false;
                        }

                        if ( '' === $return_dropoff ) {
                                $this->add_motorbike_notice( __( 'Please select a return drop-off location.', 'hotel-rates' ) );
                                return false;
                        }

                } else {
                        $return_date = '';
                        $return_time = '';
                        $return_dropoff = '';
                }

                if ( '' !== $route_index_raw && isset( $routes[ (int) $route_index_raw ] ) ) {
                        $candidate_route = $routes[ (int) $route_index_raw ];

                        if ( ( $candidate_route['from'] ?? '' ) === $matrix_from && ( $candidate_route['to'] ?? '' ) === $matrix_to ) {
                                $route_index = (int) $route_index_raw;
                                $route = $candidate_route;
                        }
                }

                if ( null === $route ) {
                        foreach ( $routes as $index => $candidate_route ) {
                                if ( ( $candidate_route['from'] ?? '' ) === $matrix_from && ( $candidate_route['to'] ?? '' ) === $matrix_to ) {
                                        $route_index = (int) $index;
                                        $route = $candidate_route;
                                        break;
                                }
                        }
                }

                if ( null === $route ) {
                        $this->add_motorbike_notice( __( 'Please select a valid port transfer route.', 'hotel-rates' ) );
                        return false;
                }

                $unit_price = (float) ( $route['rate'] ?? 0 );

                if ( $unit_price <= 0 ) {
                        $this->add_motorbike_notice( __( 'This port transfer selection is unavailable.', 'hotel-rates' ) );
                        return false;
                }

                if ( isset( $_POST['hr_port_transfer_addons'] ) ) {
                        $posted_addons = json_decode( wp_unslash( $_POST['hr_port_transfer_addons'] ), true );
                        if ( is_array( $posted_addons ) ) {
                                foreach ( $posted_addons as $posted_addon ) {
                                        if ( is_array( $posted_addon ) && isset( $posted_addon['index'] ) ) {
                                                $selected_addon_indices[] = (int) $posted_addon['index'];
                                        } elseif ( is_numeric( $posted_addon ) ) {
                                                $selected_addon_indices[] = (int) $posted_addon;
                                        }
                                }
                        }
                }

                if ( empty( $selected_addon_indices ) && isset( $payload['addons'] ) && is_array( $payload['addons'] ) ) {
                        $selected_addon_indices = array_map( 'intval', $payload['addons'] );
                }

                foreach ( array_values( array_unique( $selected_addon_indices ) ) as $addon_index ) {
                        if ( ! isset( $available_addons[ $addon_index ] ) ) {
                                continue;
                        }

                        $addon = $available_addons[ $addon_index ];
                        $addon_rate = (float) ( $addon['rate'] ?? 0 );
                        $selected_addons[] = array(
                                'index' => $addon_index,
                                'label' => $addon['label'] ?? '',
                                'rate'  => $addon_rate,
                        );
                        $addons_total += $addon_rate;
                }

                $multiplier = 'roundtrip' === $transfer_type ? 2 : 1;
                $convenience_fee_percent = max( 0.0, (float) ( $transfer_data['convenienceFeePercent'] ?? 0 ) );

                return array(
                        'transfer_id'        => (int) $transfer_data['transferId'],
                        'transfer_name'      => $transfer_data['transferName'],
                        'transfer_date'      => $transfer_date,
                        'passengers'         => $passengers,
                        'route_index'        => $route_index,
                        'route_label'        => sprintf( '%1$s to %2$s', $from, $to ),
                        'from'               => $from,
                        'to'                 => $to,
                        'direction'          => $direction,
                        'transfer_type'      => $transfer_type,
                        'transfer_type_label' => 'roundtrip' === $transfer_type ? __( 'Round-trip Transfer', 'hotel-rates' ) : __( 'One-way Transfer', 'hotel-rates' ),
                        'transfer_time'      => $transfer_time,
                        'flight_number'      => $flight_number,
                        'hotel_name'         => $hotel_name,
                        'dropoff'            => $dropoff,
                        'return_date'        => $return_date,
                        'return_time'        => $return_time,
                        'return_dropoff'     => $return_dropoff,
                        'addons'             => $selected_addons,
                        'addons_total'       => $addons_total,
                        'unit_price'         => $unit_price,
                        'total'              => ( $unit_price * $multiplier ) + $addons_total,
                        'convenience_fee_percent' => $convenience_fee_percent,
                        'payload'            => wp_json_encode( array(
                                'routeIndex'   => $route_index,
                                'passengers'   => $passengers,
                                'transferType' => $transfer_type,
                                'from'         => $from,
                                'to'           => $to,
                                'direction'    => $direction,
                                'addons'       => wp_list_pluck( $selected_addons, 'index' ),
                        ) ),
                );
        }

        private function get_self_drive_selection_from_request( $product_id, $process_upload = true ) {
                $rental_id        = isset( $_POST['hr_self_drive_rental_id'] ) ? (int) wp_unslash( $_POST['hr_self_drive_rental_id'] ) : 0;
                $linked_rental_id = $this->get_linked_product_self_drive_rental_id( $product_id );

                if ( ! $rental_id || $rental_id !== $linked_rental_id ) {
                        return false;
                }

                $self_drive_data = $this->get_self_drive_booking_data( $rental_id );

                if ( empty( $self_drive_data ) ) {
                        $this->add_motorbike_notice( __( 'This self-drive rental is unavailable.', 'hotel-rates' ) );
                        return false;
                }

                $payload = isset( $_POST['hr_self_drive_selection_payload'] ) ? json_decode( wp_unslash( $_POST['hr_self_drive_selection_payload'] ), true ) : array();
                $payload = is_array( $payload ) ? $payload : array();
                $selected_quantities = isset( $payload['vehicles'] ) && is_array( $payload['vehicles'] ) ? $payload['vehicles'] : array();
                $rental_date = $this->get_self_drive_request_date_value( 'hr_self_drive_rental_date', array( 'ovabrw_pickup_date', 'pickup_date', 'checkin_date', 'tour_date', 'rental_date', 'booking_date' ) );
                $return_date = $this->get_self_drive_request_date_value( 'hr_self_drive_return_date', array( 'hr_self_drive_return_date_display', 'ovabrw_pickoff_date', 'ovabrw_dropoff_date', 'dropoff_date', 'checkout_date' ) );
                $days = $this->calculate_self_drive_days( $rental_date, $return_date );
                $guest_count = isset( $_POST['hr_self_drive_guests'] ) ? (int) wp_unslash( $_POST['hr_self_drive_guests'] ) : 0;
                if ( $guest_count < 1 && isset( $_POST['hr_self_drive_guests_display'] ) ) {
                        $guest_count = (int) wp_unslash( $_POST['hr_self_drive_guests_display'] );
                }
                if ( $guest_count < 1 && isset( $_POST['ovabrw_adults'] ) ) {
                        $guest_count = (int) wp_unslash( $_POST['ovabrw_adults'] );
                }
                $hotel_name = $this->get_posted_text_value( 'hr_self_drive_hotel_name' );
                if ( '' === $hotel_name ) {
                        $hotel_name = $this->get_posted_text_value( 'hr_self_drive_hotel_name_display' );
                }
                $start_time = isset( $_POST['hr_self_drive_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['hr_self_drive_start_time'] ) ) : '';
                $return_time = isset( $_POST['hr_self_drive_return_time'] ) ? sanitize_text_field( wp_unslash( $_POST['hr_self_drive_return_time'] ) ) : '';
                $handover_option = isset( $_POST['hr_self_drive_handover_option'] ) ? sanitize_key( wp_unslash( $_POST['hr_self_drive_handover_option'] ) ) : '';
                $delivery_address = isset( $_POST['hr_self_drive_delivery_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hr_self_drive_delivery_address'] ) ) : '';
                $vehicles = ! empty( $self_drive_data['vehicles'] ) && is_array( $self_drive_data['vehicles'] ) ? $self_drive_data['vehicles'] : array();
                $items = array();
                $subtotal = 0.0;

                if ( '' === $rental_date ) {
                        $this->add_motorbike_notice( __( 'Please select a rental date.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' === $return_date || $days < 1 ) {
                        $this->add_motorbike_notice( __( 'Please select a valid return date.', 'hotel-rates' ) );
                        return false;
                }

                if ( $guest_count < 1 ) {
                        $this->add_motorbike_notice( __( 'Please enter the number of guests.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' === $hotel_name ) {
                        $this->add_motorbike_notice( __( 'Please enter the hotel name.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' === $start_time || ( ! empty( $self_drive_data['startTimes'] ) && ! in_array( $start_time, $self_drive_data['startTimes'], true ) ) ) {
                        $this->add_motorbike_notice( __( 'Please select a pick-up/delivery time.', 'hotel-rates' ) );
                        return false;
                }

                if ( '' === $return_time || ( ! empty( $self_drive_data['startTimes'] ) && ! in_array( $return_time, $self_drive_data['startTimes'], true ) ) ) {
                        $this->add_motorbike_notice( __( 'Please select a return time.', 'hotel-rates' ) );
                        return false;
                }

                if ( ! in_array( $handover_option, array( 'pickup', 'delivery' ), true ) ) {
                        $this->add_motorbike_notice( __( 'Please select a vehicle handover option.', 'hotel-rates' ) );
                        return false;
                }

                if ( 'delivery' === $handover_option && '' === $delivery_address ) {
                        $this->add_motorbike_notice( __( 'Please enter the delivery address.', 'hotel-rates' ) );
                        return false;
                }

                foreach ( $vehicles as $index => $vehicle ) {
                        $quantity = isset( $selected_quantities[ (string) $index ] ) ? (int) $selected_quantities[ (string) $index ] : 0;
                        $stock    = max( 0, (int) ( $vehicle['stock'] ?? 0 ) );
                        $rate     = (float) ( $vehicle['rate'] ?? 0 );

                        if ( $quantity < 1 ) {
                                continue;
                        }

                        if ( $rate < 0 || $stock < 1 || $quantity > $stock ) {
                                $this->add_motorbike_notice( __( 'One or more self-drive vehicle quantities is unavailable.', 'hotel-rates' ) );
                                return false;
                        }

                        $line_total = $rate * $quantity * $days;
                        $subtotal += $line_total;
                        $items[] = array(
                                'index'      => (int) $index,
                                'name'       => $vehicle['name'] ?? '',
                                'rate'       => $rate,
                                'quantity'   => $quantity,
                                'days'       => $days,
                                'line_total' => $line_total,
                        );
                }

                if ( empty( $items ) ) {
                        $this->add_motorbike_notice( __( 'Please choose at least one vehicle quantity.', 'hotel-rates' ) );
                        return false;
                }

                $delivery_fee = 'delivery' === $handover_option ? (float) ( $self_drive_data['deliveryFee'] ?? 0 ) : 0.0;

                return array(
                        'rental_id'        => (int) $self_drive_data['rentalId'],
                        'rental_name'      => $self_drive_data['rentalName'],
                        'items'            => $items,
                        'rental_date'      => $rental_date,
                        'return_date'      => $return_date,
                        'guests'           => $guest_count,
                        'hotel_name'       => $hotel_name,
                        'days'             => $days,
                        'start_time'       => $start_time,
                        'return_time'      => $return_time,
                        'handover_option'  => $handover_option,
                        'handover_label'   => 'delivery' === $handover_option ? __( 'Deliver to Address', 'hotel-rates' ) : __( 'Office/Garage Pick-up', 'hotel-rates' ),
                        'delivery_address' => 'delivery' === $handover_option ? $delivery_address : '',
                        'delivery_fee'     => $delivery_fee,
                        'total'            => $subtotal + $delivery_fee,
                        'credit_card_fee_percent' => max( 0.0, (float) ( $self_drive_data['creditCardFeePercent'] ?? 0 ) ),
                        'payload'          => wp_json_encode( $payload ),
                );
        }

        private function get_posted_text_value( $key ) {
                if ( ! isset( $_POST[ $key ] ) ) {
                        return '';
                }

                $value = wp_unslash( $_POST[ $key ] );

                if ( is_array( $value ) ) {
                        $value = reset( $value );
                }

                if ( is_array( $value ) ) {
                        return '';
                }

                return sanitize_text_field( (string) $value );
        }

        private function get_self_drive_request_date_value( $primary_key, $fallback_keys = array() ) {
                $value = $this->get_posted_text_value( $primary_key );

                if ( '' !== $value ) {
                        return $value;
                }

                foreach ( $fallback_keys as $fallback_key ) {
                        $value = $this->get_posted_text_value( $fallback_key );

                        if ( '' !== $value ) {
                                return $value;
                        }
                }

                return '';
        }

        private function calculate_self_drive_days( $rental_date, $return_date ) {
                $rental_date = $this->parse_self_drive_request_date( $rental_date );
                $return_date = $this->parse_self_drive_request_date( $return_date );

                if ( ! $rental_date || ! $return_date ) {
                        return 0;
                }

                $diff = (int) $rental_date->diff( $return_date )->format( '%r%a' );

                if ( $diff < 0 ) {
                        return 0;
                }

                return max( 1, $diff );
        }

        private function parse_self_drive_request_date( $value ) {
                $value = trim( wp_strip_all_tags( (string) $value ) );

                if ( '' === $value ) {
                        return null;
                }

                if ( preg_match( '/^\d{4}[\/.-]\d{1,2}[\/.-]\d{1,2}/', $value, $match ) ) {
                        return $this->create_self_drive_date_from_format( 'Y-m-d', str_replace( array( '/', '.' ), '-', $match[0] ) );
                }

                if ( preg_match( '/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})/', $value, $match ) ) {
                        $first = (int) $match[1];
                        $second = (int) $match[2];
                        $normalized = sprintf( '%02d-%02d-%04d', $first, $second, (int) $match[3] );

                        if ( $first > 12 || $second <= 12 ) {
                                return $this->create_self_drive_date_from_format( 'd-m-Y', $normalized );
                        }

                        return $this->create_self_drive_date_from_format( 'm-d-Y', $normalized );
                }

                try {
                        $timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
                        return new DateTimeImmutable( $value, $timezone );
                } catch ( Exception $error ) {
                        return null;
                }
        }

        private function create_self_drive_date_from_format( $format, $value ) {
                $timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
                $date = DateTimeImmutable::createFromFormat( '!' . $format, $value, $timezone );
                $errors = DateTimeImmutable::getLastErrors();

                if ( ! $date || ( is_array( $errors ) && ( ! empty( $errors['warning_count'] ) || ! empty( $errors['error_count'] ) ) ) ) {
                        return null;
                }

                return $date;
        }

        private function add_motorbike_notice( $message ) {
                if ( function_exists( 'wc_add_notice' ) ) {
                        wc_add_notice( $message, 'error' );
                }
        }

        private function get_airport_transfer_selection_from_request( $product_id ) {
                $rate_id        = isset( $_POST['hr_airport_transfer_rate_id'] ) ? (int) wp_unslash( $_POST['hr_airport_transfer_rate_id'] ) : 0;
                $linked_rate_id = $this->get_linked_product_airport_transfer_rate_id( $product_id );

                if ( ! $rate_id || $rate_id !== $linked_rate_id ) {
                        return false;
                }

                $transfer_data = $this->get_tour_booking_data( $rate_id );
                if ( empty( $transfer_data ) || 'Airport Transfer' !== $transfer_data['type'] ) {
                        return false;
                }

                $route_index_raw = isset( $_POST['hr_airport_transfer_route_index'] ) ? (string) wp_unslash( $_POST['hr_airport_transfer_route_index'] ) : '';
                $direction       = isset( $_POST['hr_airport_transfer_direction'] ) ? sanitize_key( wp_unslash( $_POST['hr_airport_transfer_direction'] ) ) : '';
                $routes          = $transfer_data['transferRoutes'] ?? array();

                if ( '' === $route_index_raw ) {
                        return false;
                }

                if ( ! in_array( $direction, array( 'airport_to_place', 'place_to_airport' ), true ) ) {
                        return false;
                }

                $route_index = (int) $route_index_raw;

                if ( ! isset( $routes[ $route_index ] ) ) {
                        return false;
                }

                $route      = $routes[ $route_index ];
                $pax        = max( 0, $this->get_guest_count( array() ) );
                $unit_price = array_key_exists( 'rate', $route ) ? $route['rate'] : null;

                if ( ( null === $unit_price || '' === $unit_price ) && ! empty( $route['prices'] ) && is_array( $route['prices'] ) ) {
                        if ( $pax > 0 && isset( $route['prices'][ (string) $pax ] ) && null !== $route['prices'][ (string) $pax ] ) {
                                $unit_price = (float) $route['prices'][ (string) $pax ] * $pax;
                        } else {
                                foreach ( $route['prices'] as $price_pax => $price ) {
                                        if ( null === $price || '' === $price ) {
                                                continue;
                                        }

                                        $price_pax  = max( 1, (int) $price_pax );
                                        $unit_price = (float) $price * $price_pax;
                                        break;
                                }
                        }
                }

                if ( null === $unit_price || '' === $unit_price ) {
                        return false;
                }

                $unit_price = (float) $unit_price;

                return array(
                        'rate_id'     => (int) $transfer_data['tourRateId'],
                        'name'        => $transfer_data['tourName'],
                        'place_name'  => $transfer_data['placeName'],
                        'route_label' => $route['label'] ?? '',
                        'direction'   => $direction,
                        'direction_label' => $this->get_airport_transfer_direction_label( $transfer_data['tourName'], $route['label'] ?? '', $direction ),
                        'pax'         => $pax,
                        'unit_price'  => $unit_price,
                        'total'       => $unit_price,
                );
        }

        private function get_airport_transfer_direction_label( $airport_name, $route_label, $direction ) {
                $airport_name = '' !== (string) $airport_name ? (string) $airport_name : __( 'Airport', 'hotel-rates' );
                $route_label  = '' !== (string) $route_label ? (string) $route_label : __( 'From/To Place', 'hotel-rates' );

                if ( 'airport_to_place' === $direction ) {
                        return sprintf( __( '%1$s to %2$s', 'hotel-rates' ), $airport_name, $route_label );
                }

                if ( 'place_to_airport' === $direction ) {
                        return sprintf( __( '%1$s to %2$s', 'hotel-rates' ), $route_label, $airport_name );
                }

                return '';
        }

        private function get_day_tour_selection_from_request( $product_id ) {
                $tour_rate_id        = isset( $_POST['hr_tour_rate_id'] ) ? (int) wp_unslash( $_POST['hr_tour_rate_id'] ) : 0;
                $linked_tour_rate_id = $this->get_linked_product_tour_rate_id( $product_id );

                if ( ! $tour_rate_id || $tour_rate_id !== $linked_tour_rate_id ) {
                        return false;
                }

                $tour_data = $this->get_tour_booking_data( $tour_rate_id );
                if ( empty( $tour_data ) || 'Day Tour' !== (string) ( $tour_data['type'] ?? '' ) ) {
                        return false;
                }

                $pax = $this->get_tour_paid_guest_count_from_request();
                if ( $pax < 1 ) {
                        return false;
                }

                $booking_fields = ! empty( $tour_data['bookingFields'] ) && is_array( $tour_data['bookingFields'] ) ? $tour_data['bookingFields'] : Hotel_Rates_DB::normalize_tour_booking_fields( array() );
                $hotel_flight_no_enabled = $this->is_tour_booking_field_enabled( $booking_fields, 'hotel_flight_no' );
                $pickup_time_enabled = $this->is_tour_booking_field_enabled( $booking_fields, 'pickup_time' );
                $hotel_flight_no = $hotel_flight_no_enabled ? $this->get_posted_text_value( 'hr_tour_hotel_flight_no' ) : '';
                $pickup_time = $pickup_time_enabled ? $this->get_posted_text_value( 'hr_tour_pickup_time' ) : '';
                $tour_date = $this->get_self_drive_request_date_value( 'ovabrw_pickup_date', array( 'pickup_date', 'checkin_date', 'tour_date', 'booking_date' ) );
                $children_rates = ! empty( $tour_data['childrenRates'] ) && is_array( $tour_data['childrenRates'] ) ? $tour_data['childrenRates'] : Hotel_Rates_DB::normalize_tour_children_rates( array() );
                $children_enabled = ! empty( $children_rates['enabled'] );
                $children_label = $this->get_tour_children_label_from_rates( $children_rates );
                $children = $children_enabled && isset( $_POST['hr_tour_children'] ) ? max( 0, (int) wp_unslash( $_POST['hr_tour_children'] ) ) : 0;
                $rate_guest_count = $this->get_tour_children_rate_guest_count( $pax, $children );
                $unit_price = $tour_data['prices'][ (string) $rate_guest_count ] ?? null;

                if ( null === $unit_price || '' === $unit_price ) {
                        return false;
                }

                $unit_price = (float) $unit_price;
                $children_unit_price = 0.0;
                $children_total = 0.0;
                $infants = isset( $_POST['hr_tour_infants'] ) ? max( 0, (int) wp_unslash( $_POST['hr_tour_infants'] ) ) : 0;
                $addons_payload = isset( $_POST['hr_tour_addons_payload'] ) ? json_decode( wp_unslash( $_POST['hr_tour_addons_payload'] ), true ) : array();
                $addons = $this->get_selected_tour_addons( $tour_data, $addons_payload );
                $addons_total = 0.0;

                if ( $children > 0 ) {
                        $children_discount_percent = isset( $children_rates['discount_percent'] ) ? min( 100, max( 0, (float) $children_rates['discount_percent'] ) ) : 0.0;
                        $children_unit_price = max( 0.0, $unit_price * ( 1 - ( $children_discount_percent / 100 ) ) );
                        $children_total = $children_unit_price * $children;
                }

                foreach ( $addons as $addon ) {
                        $addons_total += (float) ( $addon['rate'] ?? 0 );
                }

                return array(
                        'tour_rate_id' => (int) $tour_data['tourRateId'],
                        'tour_name'    => $tour_data['tourName'],
                        'type'         => $tour_data['type'],
                        'place_name'   => $tour_data['placeName'],
                        'tour_date'    => $tour_date,
                        'pax'          => $pax,
                        'children'     => $children,
                        'children_enabled' => $children_enabled,
                        'children_label' => $children_label,
                        'children_unit_price' => $children_unit_price,
                        'children_total' => $children_total,
                        'infants'      => $infants,
                        'hotel_flight_no' => $hotel_flight_no,
                        'hotel_flight_no_enabled' => $hotel_flight_no_enabled,
                        'hotel_flight_no_label' => $this->get_day_tour_booking_field_label( $booking_fields, 'hotel_flight_no', __( 'Hotel / Flight No.', 'hotel-rates' ) ),
                        'pickup_time'  => $pickup_time,
                        'pickup_time_enabled' => $pickup_time_enabled,
                        'pickup_time_label' => $this->get_day_tour_booking_field_label( $booking_fields, 'pickup_time', __( 'Pick-up Time', 'hotel-rates' ) ),
                        'legacy_display_keys' => $this->get_day_tour_legacy_display_keys( $product_id ),
                        'unit_price'   => $unit_price,
                        'addons'       => $addons,
                        'addons_total' => $addons_total,
                        'total'        => ( $unit_price * $pax ) + $children_total + $addons_total,
                );
        }

        private function get_tour_selection_from_request( $product_id ) {
                $tour_rate_id        = isset( $_POST['hr_tour_rate_id'] ) ? (int) wp_unslash( $_POST['hr_tour_rate_id'] ) : 0;
                $linked_tour_rate_id = $this->get_linked_product_tour_rate_id( $product_id );

                if ( ! $tour_rate_id || $tour_rate_id !== $linked_tour_rate_id ) {
                        return false;
                }

                $tour_data = $this->get_tour_booking_data( $tour_rate_id );
                if ( empty( $tour_data ) ) {
                        return false;
                }

                if ( 'Day Tour' === (string) ( $tour_data['type'] ?? '' ) ) {
                        return false;
                }

                $pax = $this->get_tour_paid_guest_count_from_request();
                if ( $pax < 1 ) {
                        return false;
                }

                $hotel_flight_no = $this->get_posted_text_value( 'hr_tour_hotel_flight_no' );
                $pickup_time = $this->get_posted_text_value( 'hr_tour_pickup_time' );
                $children_rates = ! empty( $tour_data['childrenRates'] ) && is_array( $tour_data['childrenRates'] ) ? $tour_data['childrenRates'] : Hotel_Rates_DB::normalize_tour_children_rates( array() );
                $children_enabled = 'Joiner' === (string) ( $tour_data['type'] ?? '' ) && ! empty( $children_rates['enabled'] );
                $children_label = $this->get_tour_children_label_from_rates( $children_rates );
                $children = $children_enabled && isset( $_POST['hr_tour_children'] ) ? max( 0, (int) wp_unslash( $_POST['hr_tour_children'] ) ) : 0;
                $rate_guest_count = $this->get_tour_children_rate_guest_count( $pax, $children );
                $unit_price = $tour_data['prices'][ (string) $rate_guest_count ] ?? null;

                if ( null === $unit_price || '' === $unit_price ) {
                        return false;
                }

                $unit_price = (float) $unit_price;
                $children_unit_price = 0.0;
                $children_total = 0.0;
                $infants = isset( $_POST['hr_tour_infants'] ) ? max( 0, (int) wp_unslash( $_POST['hr_tour_infants'] ) ) : 0;
                $booking_fields = ! empty( $tour_data['bookingFields'] ) && is_array( $tour_data['bookingFields'] ) ? $tour_data['bookingFields'] : Hotel_Rates_DB::normalize_tour_booking_fields( array() );
                $addons_payload = isset( $_POST['hr_tour_addons_payload'] ) ? json_decode( wp_unslash( $_POST['hr_tour_addons_payload'] ), true ) : array();
                $addons = $this->get_selected_tour_addons( $tour_data, $addons_payload );
                $addons_total = 0.0;

                if ( $children > 0 ) {
                        $children_discount_percent = isset( $children_rates['discount_percent'] ) ? min( 100, max( 0, (float) $children_rates['discount_percent'] ) ) : 0.0;
                        $children_unit_price = max( 0.0, $unit_price * ( 1 - ( $children_discount_percent / 100 ) ) );
                        $children_total = $children_unit_price * $children;
                }

                foreach ( $addons as $addon ) {
                        $addons_total += (float) ( $addon['rate'] ?? 0 );
                }

                return array(
                        'tour_rate_id' => (int) $tour_data['tourRateId'],
                        'tour_name'    => $tour_data['tourName'],
                        'type'         => $tour_data['type'],
                        'place_name'   => $tour_data['placeName'],
                        'pax'          => $pax,
                        'children'     => $children,
                        'children_enabled' => $children_enabled,
                        'children_label' => $children_label,
                        'children_unit_price' => $children_unit_price,
                        'children_total' => $children_total,
                        'infants'      => $infants,
                        'hotel_flight_no' => $hotel_flight_no,
                        'hotel_flight_no_label' => $this->get_tour_booking_field_label( $booking_fields, 'hotel_flight_no', __( 'Hotel / Flight No.', 'hotel-rates' ) ),
                        'pickup_time'  => $pickup_time,
                        'pickup_time_label' => $this->get_tour_booking_field_label( $booking_fields, 'pickup_time', __( 'Pick-up Time', 'hotel-rates' ) ),
                        'unit_price'   => $unit_price,
                        'addons'       => $addons,
                        'addons_total' => $addons_total,
                        'total'        => ( $unit_price * $pax ) + $children_total + $addons_total,
                );
        }

        private function get_tour_paid_guest_count_from_request() {
                $tour_pax = isset( $_POST['hr_tour_pax'] ) ? (int) wp_unslash( $_POST['hr_tour_pax'] ) : 0;

                if ( $tour_pax > 0 ) {
                        return $tour_pax;
                }

                $adults = isset( $_POST['ovabrw_adults'] ) ? (int) wp_unslash( $_POST['ovabrw_adults'] ) : 0;

                return max( 0, $adults );
        }

        private function get_tour_children_rate_guest_count( $adult_count, $children_count ) {
                return max( 0, (int) $adult_count ) + max( 0, (int) $children_count );
        }

        private function get_tour_booking_field_label( $booking_fields, $field_key, $default_label ) {
                if ( ! is_array( $booking_fields ) || empty( $booking_fields[ $field_key ]['label'] ) ) {
                        return $default_label;
                }

                $label = trim( wp_strip_all_tags( (string) $booking_fields[ $field_key ]['label'] ) );

                return '' !== $label ? $label : $default_label;
        }

        private function is_tour_booking_field_enabled( $booking_fields, $field_key ) {
                return is_array( $booking_fields )
                        && isset( $booking_fields[ $field_key ] )
                        && is_array( $booking_fields[ $field_key ] )
                        && ! empty( $booking_fields[ $field_key ]['enabled'] );
        }

        private function get_day_tour_booking_field_label( $booking_fields, $field_key, $default_label ) {
                $label = $this->get_tour_booking_field_label( $booking_fields, $field_key, $default_label );
                $label = $this->normalize_day_tour_display_label( $label );

                return '' !== $label ? $label : $default_label;
        }

        private function normalize_day_tour_display_label( $label ) {
                $label = trim( wp_strip_all_tags( (string) $label ) );

                return rtrim( $label, ' :' );
        }

        private function get_day_tour_legacy_display_keys( $product_id ) {
                $keys = array(
                        __( 'Tour Date', 'hotel-rates' ),
                        __( 'Choose pick-up time', 'hotel-rates' ),
                        __( 'Pick-up Information', 'hotel-rates' ),
                        __( 'Optional add-ons', 'hotel-rates' ),
                        __( 'For pick-up outside Dumaguete City', 'hotel-rates' ),
                );

                if ( function_exists( 'ovabrw_get_list_field_checkout' ) ) {
                        $fields = ovabrw_get_list_field_checkout( $product_id );

                        if ( is_array( $fields ) ) {
                                foreach ( $fields as $key => $field ) {
                                        if ( ! is_array( $field ) ) {
                                                continue;
                                        }

                                        $keys[] = $key;
                                        $keys[] = $field['name'] ?? '';
                                        $keys[] = $field['label'] ?? '';
                                }
                        }
                }

                $normalized_keys = array();

                foreach ( $keys as $key ) {
                        $key = $this->normalize_day_tour_display_label( $key );

                        if ( '' !== $key ) {
                                $normalized_keys[ $this->get_item_data_key_slug( $key ) ] = $key;
                        }
                }

                return array_values( $normalized_keys );
        }

        private function get_selected_tour_addons( $tour_data, $payload ) {
                $selected = array();
                $groups   = array();

                if ( ! is_array( $payload ) ) {
                        $payload = array();
                }

                foreach ( $tour_data['optionalAddons'] ?? array() as $group ) {
                        if ( empty( $group['key'] ) ) {
                                continue;
                        }

                        $groups[ (string) $group['key'] ] = $group;
                }

                foreach ( $payload as $group_key => $option_index ) {
                        $group_key = (string) $group_key;

                        if ( ! isset( $groups[ $group_key ] ) || '' === $option_index || null === $option_index ) {
                                continue;
                        }

                        $option_index = (int) $option_index;
                        $options      = $groups[ $group_key ]['options'] ?? array();

                        if ( ! isset( $options[ $option_index ] ) ) {
                                continue;
                        }

                        $option = $options[ $option_index ];
                        $selected[] = array(
                                'group_key'   => $group_key,
                                'group_label' => $groups[ $group_key ]['label'] ?? __( 'Optional Add-on', 'hotel-rates' ),
                                'label'       => $option['label'] ?? '',
                                'rate'        => (float) ( $option['rate'] ?? 0 ),
                        );
                }

                return $selected;
        }

        private function get_linked_product_tour_rate_id( $product_id ) {
                $day_tour_rate_id    = (int) get_post_meta( $product_id, '_hr_day_tour_rate_id', true );
                $joiner_tour_rate_id = (int) get_post_meta( $product_id, '_hr_joiner_tour_rate_id', true );
                $airport_transfer_rate_id = $this->get_linked_product_airport_transfer_rate_id( $product_id );

                if ( $day_tour_rate_id ) {
                        return $day_tour_rate_id;
                }

                if ( $joiner_tour_rate_id ) {
                        return $joiner_tour_rate_id;
                }

                if ( $airport_transfer_rate_id ) {
                        return $airport_transfer_rate_id;
                }

                return (int) get_post_meta( $product_id, '_hr_tour_rate_id', true );
        }

        private function get_linked_product_day_tour_rate_id( $product_id ) {
                $day_tour_rate_id = (int) get_post_meta( $product_id, '_hr_day_tour_rate_id', true );

                if ( $day_tour_rate_id ) {
                        return $day_tour_rate_id;
                }

                $legacy_tour_rate_id = (int) get_post_meta( $product_id, '_hr_tour_rate_id', true );

                if ( ! $legacy_tour_rate_id ) {
                        return 0;
                }

                $tour_rate = Hotel_Rates_DB::get_tour_rate_by_id( $legacy_tour_rate_id );

                return $tour_rate && 'Day Tour' === (string) $tour_rate->type ? $legacy_tour_rate_id : 0;
        }

        private function get_linked_product_joiner_tour_rate_id( $product_id ) {
                $joiner_tour_rate_id = (int) get_post_meta( $product_id, '_hr_joiner_tour_rate_id', true );

                if ( $joiner_tour_rate_id ) {
                        return $joiner_tour_rate_id;
                }

                $legacy_tour_rate_id = (int) get_post_meta( $product_id, '_hr_tour_rate_id', true );

                if ( ! $legacy_tour_rate_id ) {
                        return 0;
                }

                $tour_rate = Hotel_Rates_DB::get_tour_rate_by_id( $legacy_tour_rate_id );

                return $tour_rate && 'Joiner' === (string) $tour_rate->type ? $legacy_tour_rate_id : 0;
        }

        private function get_linked_product_airport_transfer_rate_id( $product_id ) {
                return (int) get_post_meta( $product_id, '_hr_airport_transfer_rate_id', true );
        }

        private function get_linked_product_motorbike_rental_id( $product_id ) {
                return (int) get_post_meta( $product_id, '_hr_motorbike_rental_id', true );
        }

        private function get_linked_product_boat_rental_id( $product_id ) {
                return (int) get_post_meta( $product_id, '_hr_boat_rental_id', true );
        }

        private function get_linked_product_self_drive_rental_id( $product_id ) {
                return (int) get_post_meta( $product_id, '_hr_self_drive_rental_id', true );
        }

        private function get_linked_product_port_transfer_id( $product_id ) {
                return (int) get_post_meta( $product_id, '_hr_port_transfer_id', true );
        }

        private function get_linked_product_destination_tour_id( $product_id ) {
                return (int) get_post_meta( $product_id, '_hr_destination_tour_id', true );
        }

        private function get_linked_product_pickup_dropoff_service_id( $product_id ) {
                return (int) get_post_meta( $product_id, '_hr_pickup_dropoff_service_id', true );
        }

        private function get_package_selection_from_request( $product_id ) {
                $package_deal_id = isset( $_POST['hr_package_deal_id'] ) ? (int) wp_unslash( $_POST['hr_package_deal_id'] ) : 0;
                $linked_package_deal_id = (int) get_post_meta( $product_id, '_hr_package_deal_id', true );

                if ( ! $package_deal_id || $package_deal_id !== $linked_package_deal_id ) {
                        return false;
                }

                $package_data = $this->get_package_booking_data( $package_deal_id );
                if ( empty( $package_data ) ) {
                        return false;
                }

                $payload = isset( $_POST['hr_package_selection_payload'] ) ? json_decode( wp_unslash( $_POST['hr_package_selection_payload'] ), true ) : array();
                $payload = is_array( $payload ) ? $payload : array();
                $pax = $this->get_guest_count( array() );
                $package_guest_counts = $this->get_package_guest_counts_from_request();

                if ( $pax < 1 ) {
                        return false;
                }

                $hotels     = array();
                $activities = array();
                $total      = 0.0;
                $airport_transfer_fee = isset( $package_data['airportTransferFee'] ) ? (float) $package_data['airportTransferFee'] : 0.0;
                $seaport_transfer_fee = isset( $package_data['seaportTransferFee'] ) ? (float) $package_data['seaportTransferFee'] : 0.0;

                $selected_hotels = isset( $payload['hotels'] ) && is_array( $payload['hotels'] ) ? $payload['hotels'] : array();
                foreach ( $package_data['hotels'] as $hotel_group ) {
                        $place_id         = (string) $hotel_group['placeId'];
                        $selected_rate_id = isset( $selected_hotels[ $place_id ] ) ? (int) $selected_hotels[ $place_id ] : 0;

                        if ( ! $selected_rate_id ) {
                                continue;
                        }

                        foreach ( $hotel_group['options'] as $option ) {
                                if ( (int) $option['rateId'] !== $selected_rate_id ) {
                                        continue;
                                }

                                $hotel_pricing = $this->get_package_hotel_pricing( $option, $package_guest_counts );

                                if ( ! $hotel_pricing ) {
                                        return false;
                                }

                                $unit_price = (float) $hotel_pricing['unit_price'];
                                $price      = (float) $hotel_pricing['total'];
                                $hotel_pax  = (int) $hotel_pricing['pax'];
                                $total += $price;
                                $hotels[] = array(
                                        'place_id'   => (int) $hotel_group['placeId'],
                                        'place_name' => $hotel_group['placeName'],
                                        'nights'     => (int) ( $hotel_group['nights'] ?? 0 ),
                                        'rate_id'    => (int) $option['rateId'],
                                        'hotel_id'   => (int) $option['hotelId'],
                                        'label'      => $option['label'],
                                        'unit_price' => $unit_price,
                                        'pax'        => $hotel_pax,
                                        'price'      => $price,
                                );
                                break;
                        }
                }

                $selected_activities = isset( $payload['activities'] ) && is_array( $payload['activities'] ) ? $payload['activities'] : array();
                foreach ( $package_data['activities'] as $activity_group ) {
                        $day                     = (string) $activity_group['day'];
                        $selected_activity_value = isset( $selected_activities[ $day ] ) ? (string) $selected_activities[ $day ] : '';
                        $special_activity_label  = $this->get_package_free_activity_label( $selected_activity_value );

                        if ( ! empty( $activity_group['transitDay'] ) ) {
                                if ( 'hr_transit_day' !== $selected_activity_value ) {
                                        return false;
                                }

                                $transit_day_pricing = $this->get_package_transit_day_pricing( $activity_group, $package_guest_counts );

                                if ( ! $transit_day_pricing ) {
                                        return false;
                                }

                                $total += (float) $transit_day_pricing['total'];
                                $activities[] = array(
                                        'day'            => (int) $activity_group['day'],
                                        'tour_rate_id'   => 0,
                                        'type'           => 'Transit Day',
                                        'label'          => $this->get_package_transit_day_label(),
                                        'unit_price'     => (float) $transit_day_pricing['unit_price'],
                                        'pax'            => (int) $transit_day_pricing['pax'],
                                        'price'          => (float) $transit_day_pricing['total'],
                                        'is_transit_day' => true,
                                );
                                continue;
                        }

                        if ( 'hr_transit_day' === $selected_activity_value ) {
                                return false;
                        }

                        if ( $special_activity_label ) {
                                if ( ! $this->is_package_free_activity_option_enabled_for_group( $activity_group, $selected_activity_value ) ) {
                                        return false;
                                }

                                $activities[] = array(
                                        'day'          => (int) $activity_group['day'],
                                        'tour_rate_id' => 0,
                                        'type'         => '',
                                        'label'        => $special_activity_label,
                                        'unit_price'   => 0,
                                        'pax'          => $pax,
                                        'price'        => 0,
                                );
                                continue;
                        }

                        $selected_activity_id = (int) $selected_activity_value;

                        if ( ! $selected_activity_id ) {
                                continue;
                        }

                        foreach ( $activity_group['options'] as $option ) {
                                if ( (int) $option['tourRateId'] !== $selected_activity_id ) {
                                        continue;
                                }

                                if ( $this->is_per_head_activity_option( $option ) && $this->is_child_rate_package_activity_option( $option ) ) {
                                        $activity_pricing = $this->get_package_child_rate_activity_pricing( $option, $package_guest_counts );

                                        if ( ! $activity_pricing ) {
                                                return false;
                                        }

                                        $unit_price = (float) $activity_pricing['unit_price'];
                                        $price      = (float) $activity_pricing['total'];
                                        $activity_pax = (int) $activity_pricing['pax'];
                                } else {
                                        $price = isset( $option['prices'][ (string) $pax ] ) ? $option['prices'][ (string) $pax ] : null;
                                        if ( $price === null ) {
                                                return false;
                                        }

                                        $unit_price = (float) $price;
                                        $price      = $this->is_per_head_activity_option( $option ) ? $unit_price * $pax : $unit_price;
                                        $activity_pax = $pax;
                                }

                                $total += $price;
                                $activities[] = array(
                                        'day'          => (int) $activity_group['day'],
                                        'tour_rate_id' => (int) $option['tourRateId'],
                                        'type'         => $option['type'] ?? '',
                                        'label'        => $option['label'],
                                        'unit_price'   => $unit_price,
                                        'pax'          => $activity_pax,
                                        'price'        => $price,
                                );
                                break;
                        }
                }

                if ( $airport_transfer_fee > 0 ) {
                        $total += $airport_transfer_fee;
                }

                if ( $seaport_transfer_fee > 0 ) {
                        $total += $seaport_transfer_fee;
                }

                return array(
                        'package_id'   => (int) $package_data['packageId'],
                        'package_name' => $package_data['packageName'],
                        'days'         => (int) $package_data['days'],
                        'nights'       => (int) $package_data['nights'],
                        'airport_transfer_fee' => $airport_transfer_fee,
                        'seaport_transfer_fee' => $seaport_transfer_fee,
                        'hotels'       => $hotels,
                        'activities'   => $activities,
                        'total'        => $total,
                        'payload'      => wp_json_encode( $payload ),
                );
        }

        private function format_package_hotel_display_value( $hotel_selection ) {
                if ( ! is_array( $hotel_selection ) ) {
                        return '';
                }

                $hotel_label = trim( (string) ( $hotel_selection['label'] ?? '' ) );

                if ( ! empty( $hotel_selection['nights'] ) ) {
                        $hotel_label .= sprintf(
                                ' (%s)',
                                sprintf(
                                        _n( '%d night', '%d nights', (int) $hotel_selection['nights'], 'hotel-rates' ),
                                        (int) $hotel_selection['nights']
                                )
                        );
                }

                $price = (float) ( $hotel_selection['price'] ?? 0 );
                $pax = max( 0, (int) ( $hotel_selection['pax'] ?? 0 ) );

                if ( array_key_exists( 'unit_price', $hotel_selection ) && $pax > 0 ) {
                        return sprintf(
                                '%1$s - %2$s x %3$d = %4$s',
                                $hotel_label,
                                wp_strip_all_tags( wc_price( (float) $hotel_selection['unit_price'] ) ),
                                $pax,
                                wp_strip_all_tags( wc_price( $price ) )
                        );
                }

                return sprintf(
                        '%1$s - %2$s',
                        $hotel_label,
                        wp_strip_all_tags( wc_price( $price ) )
                );
        }

        private function format_package_activity_display_value( $activity_selection ) {
                if ( ! is_array( $activity_selection ) ) {
                        return '';
                }

                $label = trim( (string) ( $activity_selection['label'] ?? '' ) );
                $price = (float) ( $activity_selection['price'] ?? 0 );

                if ( ! empty( $activity_selection['is_transit_day'] ) ) {
                        $unit_price = (float) ( $activity_selection['unit_price'] ?? 0 );
                        $pax = max( 0, (int) ( $activity_selection['pax'] ?? 0 ) );

                        return sprintf(
                                '%1$s - %2$s x %3$d = %4$s',
                                $label,
                                wp_strip_all_tags( wc_price( $unit_price ) ),
                                $pax,
                                wp_strip_all_tags( wc_price( $price ) )
                        );
                }

                return sprintf(
                        '%1$s - %2$s',
                        $label,
                        wp_strip_all_tags( wc_price( $price ) )
                );
        }

        private function get_package_guest_counts_from_request() {
                $adults = isset( $_POST['ovabrw_adults'] ) ? (int) wp_unslash( $_POST['ovabrw_adults'] ) : 0;
                $children = isset( $_POST['ovabrw_childrens'] ) ? (int) wp_unslash( $_POST['ovabrw_childrens'] ) : 0;
                $infants = isset( $_POST['ovabrw_babies'] ) ? (int) wp_unslash( $_POST['ovabrw_babies'] ) : 0;
                $adults = max( 0, $adults );
                $children = max( 0, $children );
                $infants = max( 0, $infants );

                return array(
                        'adults'   => $adults,
                        'children' => $children,
                        'infants'  => $infants,
                        'total'    => $adults + $children + $infants,
                        'paid'     => $adults + $children,
                );
        }

        private function is_child_rate_package_activity_option( $option ) {
                $type = is_array( $option ) ? ( $option['type'] ?? '' ) : ( is_object( $option ) ? ( $option->type ?? '' ) : '' );

                return in_array( (string) $type, array( 'Day Tour', 'Joiner' ), true );
        }

        private function get_package_hotel_pricing( $option, $guest_counts ) {
                if ( ! is_array( $option ) || empty( $option['prices'] ) || ! is_array( $option['prices'] ) || ! is_array( $guest_counts ) ) {
                        return false;
                }

                $paid_guest_count = max( 0, (int) ( $guest_counts['paid'] ?? 0 ) );

                if ( $paid_guest_count < 1 ) {
                        return false;
                }

                $unit_price = $option['prices'][ (string) $paid_guest_count ] ?? null;

                if ( null === $unit_price || '' === $unit_price ) {
                        return false;
                }

                $unit_price = max( 0.0, (float) $unit_price );

                return array(
                        'unit_price' => $unit_price,
                        'pax'        => $paid_guest_count,
                        'total'      => $unit_price * $paid_guest_count,
                );
        }

        private function get_package_child_rate_activity_pricing( $option, $guest_counts ) {
                if ( ! is_array( $option ) || empty( $option['prices'] ) || ! is_array( $option['prices'] ) || ! is_array( $guest_counts ) ) {
                        return false;
                }

                $paid_guest_count = max( 0, (int) ( $guest_counts['paid'] ?? 0 ) );

                if ( $paid_guest_count < 1 ) {
                        return false;
                }

                $unit_price = $option['prices'][ (string) $paid_guest_count ] ?? null;

                if ( null === $unit_price || '' === $unit_price ) {
                        return false;
                }

                $unit_price = (float) $unit_price;
                $children = max( 0, (int) ( $guest_counts['children'] ?? 0 ) );
                $adults = max( 0, (int) ( $guest_counts['adults'] ?? 0 ) );
                $children_rates = ! empty( $option['childrenRates'] ) && is_array( $option['childrenRates'] ) ? $option['childrenRates'] : Hotel_Rates_DB::normalize_tour_children_rates( array() );
                $children_discount_percent = ! empty( $children_rates['enabled'] ) && isset( $children_rates['discount_percent'] ) ? min( 100, max( 0, (float) $children_rates['discount_percent'] ) ) : 0.0;
                $children_unit_price = max( 0.0, $unit_price * ( 1 - ( $children_discount_percent / 100 ) ) );

                return array(
                        'unit_price'          => $unit_price,
                        'children_unit_price' => $children_unit_price,
                        'pax'                 => $paid_guest_count,
                        'total'               => ( $unit_price * $adults ) + ( $children_unit_price * $children ),
                );
        }

        private function get_rate_selection( $cart_item ) {
                $rate_id = 0;

                if ( ! empty( $cart_item['hr_selected_rate_id'] ) ) {
                        $rate_id = (int) $cart_item['hr_selected_rate_id'];
                } elseif ( isset( $_POST['hr_selected_rate_id'] ) ) {
                        $rate_id = (int) wp_unslash( $_POST['hr_selected_rate_id'] );
                }

                if ( ! $rate_id ) {
                        return false;
                }

                $rate = Hotel_Rates_DB::get_rate_by_id( $rate_id );
                if ( ! $rate ) {
                        return false;
                }

                $pax = $this->get_guest_count( $cart_item );
                if ( $pax < 1 ) {
                        return false;
                }

                $price = Hotel_Rates_DB::get_rate_amount_for_pax( $rate, $pax );
                if ( $price === null ) {
                        return false;
                }

                return array(
                        'rate_id'    => (int) $rate->id,
                        'hotel_id'   => (int) $rate->hotel_id,
                        'hotel_name' => $rate->hotel_name,
                        'label'      => $this->build_rate_label( $rate ),
                        'pax'        => $pax,
                        'price'      => (float) $price,
                );
        }

        private function get_guest_count( $cart_item ) {
                $adults   = 0;
                $children = 0;
                $babies   = 0;
                $selected_pax = 0;
                $tour_pax = 0;
                $airport_transfer_pax = 0;
                $port_transfer_pax = 0;
                $destination_tour_pax = 0;
                $pickup_dropoff_pax = 0;

                if ( ! empty( $cart_item ) ) {
                        $adults   = isset( $cart_item['ovabrw_adults'] ) ? (int) $cart_item['ovabrw_adults'] : 0;
                        $children = isset( $cart_item['ovabrw_childrens'] ) ? (int) $cart_item['ovabrw_childrens'] : 0;
                        $babies   = isset( $cart_item['ovabrw_babies'] ) ? (int) $cart_item['ovabrw_babies'] : 0;
                        $selected_pax = isset( $cart_item['hr_selected_pax'] ) ? (int) $cart_item['hr_selected_pax'] : 0;
                        $tour_pax = isset( $cart_item['hr_tour_pax'] ) ? (int) $cart_item['hr_tour_pax'] : 0;
                        $airport_transfer_pax = isset( $cart_item['hr_airport_transfer_pax'] ) ? (int) $cart_item['hr_airport_transfer_pax'] : 0;
                        $port_transfer_pax = isset( $cart_item['hr_port_transfer_passengers'] ) ? (int) $cart_item['hr_port_transfer_passengers'] : 0;
                        $destination_tour_pax = isset( $cart_item['hr_destination_tour_guests'] ) ? (int) $cart_item['hr_destination_tour_guests'] : 0;
                        $pickup_dropoff_pax = isset( $cart_item['hr_pickup_dropoff_passengers'] ) ? (int) $cart_item['hr_pickup_dropoff_passengers'] : 0;
                } else {
                        $adults   = isset( $_POST['ovabrw_adults'] ) ? (int) wp_unslash( $_POST['ovabrw_adults'] ) : 0;
                        $children = isset( $_POST['ovabrw_childrens'] ) ? (int) wp_unslash( $_POST['ovabrw_childrens'] ) : 0;
                        $babies   = isset( $_POST['ovabrw_babies'] ) ? (int) wp_unslash( $_POST['ovabrw_babies'] ) : 0;
                        $selected_pax = isset( $_POST['hr_selected_pax'] ) ? (int) wp_unslash( $_POST['hr_selected_pax'] ) : 0;
                        $tour_pax = isset( $_POST['hr_tour_pax'] ) ? (int) wp_unslash( $_POST['hr_tour_pax'] ) : 0;
                        $airport_transfer_pax = isset( $_POST['hr_airport_transfer_pax'] ) ? (int) wp_unslash( $_POST['hr_airport_transfer_pax'] ) : 0;
                        $port_transfer_pax = isset( $_POST['hr_port_transfer_passengers'] ) ? (int) wp_unslash( $_POST['hr_port_transfer_passengers'] ) : 0;
                        $destination_tour_pax = isset( $_POST['hr_destination_tour_guests'] ) ? (int) wp_unslash( $_POST['hr_destination_tour_guests'] ) : 0;
                        $pickup_dropoff_pax = isset( $_POST['hr_pickup_dropoff_passengers'] ) ? (int) wp_unslash( $_POST['hr_pickup_dropoff_passengers'] ) : 0;
                }

                $total = max( 0, $adults + $children + $babies );

                if ( $total < 1 && $tour_pax > 0 ) {
                        return $tour_pax;
                }

                if ( $total < 1 && $airport_transfer_pax > 0 ) {
                        return $airport_transfer_pax;
                }

                if ( $total < 1 && $port_transfer_pax > 0 ) {
                        return $port_transfer_pax;
                }

                if ( $total < 1 && $destination_tour_pax > 0 ) {
                        return $destination_tour_pax;
                }

                if ( $total < 1 && $pickup_dropoff_pax > 0 ) {
                        return $pickup_dropoff_pax;
                }

                if ( $total < 1 && $selected_pax > 0 ) {
                        return $selected_pax;
                }

                return $total;
        }

        private function build_rate_label( $rate ) {
                $label = $rate->hotel_name;

                if ( ! empty( $rate->meal_plan ) && strtoupper( $rate->meal_plan ) !== 'X' ) {
                        if ( stripos( $rate->meal_plan, 'breakfast' ) !== false ) {
                                $label .= ' (' . __( 'Breakfast included', 'hotel-rates' ) . ')';
                        } else {
                                $label .= ' (' . $rate->meal_plan . ')';
                        }
                }

                return $label;
        }

        private function get_tour_booking_data( $tour_rate_id ) {
                $tour_rate = Hotel_Rates_DB::get_tour_rate_by_id( $tour_rate_id );

                if ( ! $tour_rate || ! in_array( $tour_rate->type, array( 'Day Tour', 'Joiner', 'Airport Transfer' ), true ) ) {
                        return null;
                }

                return array(
                        'tourRateId' => (int) $tour_rate->id,
                        'tourName'   => $tour_rate->tour_name,
                        'type'       => $tour_rate->type,
                        'placeId'    => (int) $tour_rate->place_id,
                        'placeName'  => $tour_rate->place_name,
                        'prices'     => $this->extract_rate_prices( $tour_rate ),
                        'childrenRates' => Hotel_Rates_DB::normalize_tour_children_rates( $tour_rate->children_rates ?? '' ),
                        'optionalAddons' => $this->get_tour_optional_addons_for_booking( $tour_rate ),
                        'transferRoutes' => $this->get_airport_transfer_routes_for_booking( $tour_rate->transfer_routes ?? '' ),
                        'bookingFields' => Hotel_Rates_DB::normalize_tour_booking_fields( $tour_rate->booking_fields ?? '' ),
                );
        }

        private function get_motorbike_booking_data( $motorbike_rental_id ) {
                $rental = Hotel_Rates_DB::get_motorbike_rental( $motorbike_rental_id );

                if ( ! $rental ) {
                        return null;
                }

                $models = json_decode( $rental->models ?? '[]', true );
                $models = Hotel_Rates_DB::normalize_motorbike_models( is_array( $models ) ? $models : array() );
                $start_times = json_decode( $rental->start_times ?? '[]', true );
                $start_times = Hotel_Rates_DB::normalize_motorbike_text_lines( is_array( $start_times ) ? $start_times : array() );
                $day_options = json_decode( $rental->day_options ?? '[]', true );
                $day_options = Hotel_Rates_DB::normalize_motorbike_day_options( is_array( $day_options ) ? $day_options : array() );

                if ( empty( $models ) ) {
                        return null;
                }

                if ( empty( $start_times ) ) {
                        $start_times = array( '8:00 AM', '9:00 AM', '10:00 AM' );
                }

                if ( empty( $day_options ) ) {
                        $day_options = array( 1, 2, 3, 4, 5, 6, 7 );
                }

                return array(
                        'rentalId'    => (int) $rental->id,
                        'rentalName'  => $rental->rental_name,
                        'models'      => $models,
                        'startTimes'  => $start_times,
                        'dayOptions'  => $day_options,
                        'deliveryFee' => isset( $rental->delivery_fee ) ? (float) $rental->delivery_fee : 0.0,
                );
        }

        private function get_boat_booking_data( $boat_rental_id ) {
                $rental = Hotel_Rates_DB::get_boat_rental( $boat_rental_id );

                if ( ! $rental ) {
                        return null;
                }

                $rates = json_decode( $rental->rates ?? '[]', true );
                $rates = Hotel_Rates_DB::normalize_boat_rates( is_array( $rates ) ? $rates : array() );
                $boarding_times = json_decode( $rental->boarding_times ?? '[]', true );
                $boarding_times = Hotel_Rates_DB::normalize_motorbike_text_lines( is_array( $boarding_times ) ? $boarding_times : array() );

                if ( empty( $rates ) ) {
                        return null;
                }

                if ( empty( $boarding_times ) ) {
                        $boarding_times = array( '06:30 AM', '07:00 AM', '07:30 AM', '08:00 AM', '08:30 AM', '09:00 AM', '09:30 AM', '10:00 AM' );
                }

                return array(
                        'rentalId'      => (int) $rental->id,
                        'rentalName'    => $rental->rental_name,
                        'rates'         => $rates,
                        'boardingTimes' => $boarding_times,
                        'creditCardFeePercent' => isset( $rental->credit_card_fee_percent ) ? max( 0.0, (float) $rental->credit_card_fee_percent ) : 5.0,
                        'upgradeFeeLabel' => ! empty( $rental->upgrade_boat_fee_label ) ? (string) $rental->upgrade_boat_fee_label : __( 'Upgrade to Bigger Boat Fee', 'hotel-rates' ),
                        'upgradeFeeAmount' => isset( $rental->upgrade_boat_fee_amount ) && null !== $rental->upgrade_boat_fee_amount ? max( 0.0, (float) $rental->upgrade_boat_fee_amount ) : 0.0,
                );
        }

        private function get_self_drive_booking_data( $self_drive_rental_id ) {
                $rental = Hotel_Rates_DB::get_self_drive_rental( $self_drive_rental_id );

                if ( ! $rental ) {
                        return null;
                }

                $vehicles = json_decode( $rental->vehicles ?? '[]', true );
                $vehicles = Hotel_Rates_DB::normalize_self_drive_vehicles( is_array( $vehicles ) ? $vehicles : array() );
                $start_times = json_decode( $rental->start_times ?? '[]', true );
                $start_times = Hotel_Rates_DB::normalize_motorbike_text_lines( is_array( $start_times ) ? $start_times : array() );

                if ( empty( $vehicles ) ) {
                        return null;
                }

                if ( empty( $start_times ) ) {
                        $start_times = array( '8:00 AM', '9:00 AM', '10:00 AM' );
                }

                if ( empty( $day_options ) ) {
                        $day_options = array( 1, 2, 3, 4, 5, 6, 7 );
                }

                return array(
                        'rentalId'    => (int) $rental->id,
                        'rentalName'  => $rental->rental_name,
                        'vehicles'    => $vehicles,
                        'startTimes'  => $start_times,
                        'deliveryFee' => isset( $rental->delivery_fee ) ? (float) $rental->delivery_fee : 500.00,
                        'creditCardFeePercent' => isset( $rental->credit_card_fee_percent ) ? max( 0.0, (float) $rental->credit_card_fee_percent ) : 5.0,
                );
        }

        private function get_port_transfer_booking_data( $port_transfer_id, $include_private = false ) {
                $transfer = Hotel_Rates_DB::get_port_transfer( $port_transfer_id );

                if ( ! $transfer ) {
                        return null;
                }

                $config = Hotel_Rates_DB::normalize_port_transfer_config( $transfer->routes ?? '[]' );
                $transfer_times = json_decode( $transfer->transfer_times ?? '[]', true );
                $transfer_times = Hotel_Rates_DB::normalize_motorbike_text_lines( is_array( $transfer_times ) ? $transfer_times : array() );

                if ( empty( $config['fromOptions'] ) || empty( $config['toOptions'] ) || empty( $config['rates'] ) ) {
                        return null;
                }

                if ( empty( $transfer_times ) ) {
                        $transfer_times = array( '06:00 AM', '07:00 AM', '08:00 AM', '09:00 AM', '10:00 AM', '11:00 AM' );
                }

                $data = array(
                        'transferId'    => (int) $transfer->id,
                        'transferName'  => $transfer->transfer_name,
                        'fromOptions'   => $config['fromOptions'],
                        'toOptions'     => $config['toOptions'],
                        'routes'        => $config['rates'],
                        'addons'        => $config['addons'],
                        'transferTimes' => $transfer_times,
                );

                if ( $include_private ) {
                        $data['convenienceFeePercent'] = isset( $config['convenienceFeePercent'] ) ? (float) $config['convenienceFeePercent'] : 0.0;
                }

                return $data;
        }

        private function get_destination_tour_booking_data( $destination_tour_id, $include_private = false ) {
                $tour = Hotel_Rates_DB::get_destination_tour( $destination_tour_id );

                if ( ! $tour ) {
                        return null;
                }

                $destinations = json_decode( $tour->destinations ?? '[]', true );
                $destinations = Hotel_Rates_DB::normalize_destination_tour_destinations( is_array( $destinations ) ? $destinations : array() );
                $pickup_addons = json_decode( $tour->pickup_addons ?? '[]', true );
                $pickup_addons = Hotel_Rates_DB::normalize_port_transfer_addons( is_array( $pickup_addons ) ? $pickup_addons : array() );
                $pickup_times = json_decode( $tour->pickup_times ?? '[]', true );
                $pickup_times = Hotel_Rates_DB::normalize_motorbike_text_lines( is_array( $pickup_times ) ? $pickup_times : array() );

                if ( empty( $destinations ) ) {
                        return null;
                }

                if ( empty( $pickup_times ) ) {
                        $pickup_times = array( '07:00AM', '08:00AM', '09:00AM' );
                }

                $data = array(
                        'tourId'       => (int) $tour->id,
                        'tourName'     => $tour->tour_name,
                        'destinations' => $destinations,
                        'pickupAddons' => $pickup_addons,
                        'pickupTimes'  => $pickup_times,
                );

                if ( $include_private ) {
                        $data['creditCardFeePercent'] = isset( $tour->credit_card_fee_percent ) ? max( 0.0, (float) $tour->credit_card_fee_percent ) : 5.0;
                }

                return $data;
        }

        private function get_pickup_dropoff_booking_data( $pickup_dropoff_service_id, $include_private = false ) {
                $service = Hotel_Rates_DB::get_pickup_dropoff_service( $pickup_dropoff_service_id );

                if ( ! $service ) {
                        return null;
                }

                $locations = json_decode( $service->locations ?? '[]', true );
                $locations = Hotel_Rates_DB::normalize_pickup_dropoff_locations( is_array( $locations ) ? $locations : array() );
                $routes = json_decode( $service->route_rates ?? '[]', true );
                $routes = Hotel_Rates_DB::normalize_pickup_dropoff_route_rates( is_array( $routes ) ? $routes : array(), $locations );
                $pickup_times = json_decode( $service->pickup_times ?? '[]', true );
                $pickup_times = Hotel_Rates_DB::normalize_motorbike_text_lines( is_array( $pickup_times ) ? $pickup_times : array() );
                $addons = json_decode( $service->addons ?? '[]', true );
                $addons = Hotel_Rates_DB::normalize_port_transfer_addons( is_array( $addons ) ? $addons : array() );

                if ( empty( $pickup_times ) ) {
                        for ( $hour = 0; $hour < 24; $hour++ ) {
                                foreach ( array( 0, 30 ) as $minute ) {
                                        $pickup_times[] = sprintf( '%02d:%02d', $hour, $minute );
                                }
                        }
                }

                if ( count( $locations ) < 2 || empty( $routes ) ) {
                        return null;
                }

                return array(
                        'serviceId'   => (int) $service->id,
                        'serviceName' => $service->service_name,
                        'locations'   => $locations,
                        'routes'      => $routes,
                        'pickupTimes' => $pickup_times,
                        'addons'      => $addons,
                );
        }

        private function get_pickup_dropoff_route_pair_key( $from, $to ) {
                $pair = array( sanitize_text_field( (string) $from ), sanitize_text_field( (string) $to ) );

                natcasesort( $pair );
                $pair = array_values( $pair );

                return strtolower( $pair[0] ) . "\0" . strtolower( $pair[1] );
        }

        private function get_tour_optional_addons_for_booking( $tour_rate ) {
                return Hotel_Rates_DB::normalize_tour_optional_addons( $tour_rate->optional_addons ?? '', $tour_rate->type );
        }

        private function get_airport_transfer_routes_for_booking( $raw_routes ) {
                $routes = Hotel_Rates_DB::normalize_airport_transfer_routes( $raw_routes );

                foreach ( $routes as &$route ) {
                        $rate = isset( $route['rate'] ) ? (float) $route['rate'] : 0.0;
                        $prices = array();

                        for ( $p = 1; $p <= Hotel_Rates_DB::MAX_PAX; $p++ ) {
                                $prices[ (string) $p ] = $p > 0 ? $rate / $p : $rate;
                        }

                        $route['rate']   = $rate;
                        $route['prices'] = $prices;
                }
                unset( $route );

                return $routes;
        }

        private function get_package_booking_data( $package_deal_id ) {
                $package = Hotel_Rates_DB::get_package_deal( $package_deal_id );

                if ( ! $package ) {
                        return null;
                }

                $place_ids       = json_decode( $package->place_ids ?? '[]', true );
                $hotel_ids_map    = json_decode( $package->hotel_ids ?? '{}', true );
                $default_hotels_map = json_decode( $package->default_hotel_ids ?? '{}', true );
                $hotel_nights_map = json_decode( $package->hotel_nights ?? '{}', true );
                $activities_map   = json_decode( $package->activities ?? '{}', true );
                $default_activities_map = json_decode( $package->default_activities ?? '{}', true );
                $free_activity_options_map = json_decode( $package->free_activity_options ?? '{}', true );
                $transit_day_fees = json_decode( $package->transit_day_fees ?? '{}', true );
                $transit_day_days_map = json_decode( $package->transit_day_days ?? '{}', true );
                $place_ids        = is_array( $place_ids ) ? array_map( 'intval', $place_ids ) : array();
                $hotel_ids_map    = is_array( $hotel_ids_map ) ? $hotel_ids_map : array();
                $default_hotels_map = is_array( $default_hotels_map ) ? $default_hotels_map : array();
                $hotel_nights_map = is_array( $hotel_nights_map ) ? $hotel_nights_map : array();
                $activities_map   = is_array( $activities_map ) ? $activities_map : array();
                $default_activities_map = is_array( $default_activities_map ) ? $default_activities_map : array();
                $free_activity_options_map = is_array( $free_activity_options_map ) ? $free_activity_options_map : array();
                $transit_day_fees = $this->normalize_package_transit_day_fees_for_booking( $transit_day_fees );
                $transit_day_days_map = is_array( $transit_day_days_map ) ? $transit_day_days_map : array();
                $places_lookup    = array();
                $default_nights   = max( 1, (int) $package->number_of_days - 1 );
                $hotel_groups     = array();
                $activity_groups  = array();

                foreach ( Hotel_Rates_DB::get_places() as $place ) {
                        $places_lookup[ (int) $place->id ] = $place;
                }

                foreach ( $place_ids as $place_id ) {
                        if ( empty( $hotel_ids_map[ (string) $place_id ] ) || empty( $places_lookup[ $place_id ] ) ) {
                                continue;
                        }

                        $options = array();
                        $nights  = isset( $hotel_nights_map[ (string) $place_id ] ) ? (int) $hotel_nights_map[ (string) $place_id ] : $default_nights;
                        $nights  = max( 1, min( Hotel_Rates_DB::MAX_NIGHTS, $nights ) );

                        foreach ( array_map( 'intval', (array) $hotel_ids_map[ (string) $place_id ] ) as $hotel_id ) {
                                $hotel = Hotel_Rates_DB::get_hotel( $hotel_id );
                                $rates = Hotel_Rates_DB::get_rates( $hotel_id, $nights );
                                $rate  = $this->get_preferred_package_hotel_rate( $rates );

                                if ( $rate ) {
                                        if ( $hotel ) {
                                                $rate->hotel_name = $hotel->name;
                                        }

                                        $options[] = array(
                                                'rateId'   => (int) $rate->id,
                                                'hotelId'  => (int) $rate->hotel_id,
                                                'label'    => $this->build_rate_label( $rate ),
                                                'prices'   => $this->extract_rate_prices( $rate ),
                                        );
                                }
                        }

                        if ( empty( $options ) ) {
                                continue;
                        }

                        $default_hotel_id = isset( $default_hotels_map[ (string) $place_id ] ) ? (int) $default_hotels_map[ (string) $place_id ] : 0;
                        $default_rate_id  = 0;

                        foreach ( $options as $option ) {
                                if ( $default_hotel_id && (int) $option['hotelId'] === $default_hotel_id ) {
                                        $default_rate_id = (int) $option['rateId'];
                                        break;
                                }
                        }

                        $hotel_groups[] = array(
                                'placeId'       => (int) $place_id,
                                'placeName'     => $places_lookup[ $place_id ]->name,
                                'nights'        => $nights,
                                'defaultRateId' => $default_rate_id,
                                'options'       => $options,
                        );
                }

                for ( $day = 1; $day <= (int) $package->number_of_days; $day++ ) {
                        $day_key = (string) $day;
                        $is_transit_day = ! empty( $transit_day_days_map[ $day_key ] );
                        $tour_rate_ids = isset( $activities_map[ $day_key ] ) ? $activities_map[ $day_key ] : array();
                        $options = array();

                        foreach ( array_map( 'intval', (array) $tour_rate_ids ) as $tour_rate_id ) {
                                $tour_rate = Hotel_Rates_DB::get_tour_rate_by_id( $tour_rate_id );
                                if ( ! $tour_rate ) {
                                        continue;
                                }

                                $options[] = array(
                                        'tourRateId' => (int) $tour_rate->id,
                                        'type'       => $tour_rate->type,
                                        'label'      => sprintf( '%s (%s)', $tour_rate->tour_name, $tour_rate->type ),
                                        'placeName'  => $tour_rate->place_name,
                                        'perHead'    => $this->is_per_head_activity_option( $tour_rate ),
                                        'prices'     => $this->extract_rate_prices( $tour_rate ),
                                        'childrenRates' => Hotel_Rates_DB::normalize_tour_children_rates( $tour_rate->children_rates ?? '' ),
                                );
                        }

                        if ( empty( $options ) && ! $is_transit_day ) {
                                continue;
                        }

                        $default_tour_rate_id = isset( $default_activities_map[ $day_key ] ) ? (int) $default_activities_map[ $day_key ] : 0;
                        $option_tour_rate_ids = array_map( 'intval', array_column( $options, 'tourRateId' ) );

                        $activity_groups[] = array(
                                'day'               => (int) $day,
                                'defaultTourRateId' => $is_transit_day ? 0 : ( in_array( $default_tour_rate_id, $option_tour_rate_ids, true ) ? $default_tour_rate_id : 0 ),
                                'freeOptions'       => $is_transit_day ? array() : $this->get_package_enabled_free_activity_options_for_day( $free_activity_options_map, $day ),
                                'transitDay'        => $is_transit_day,
                                'transitDayFees'    => $is_transit_day ? $transit_day_fees : array(),
                                'options'           => $is_transit_day ? array() : $options,
                        );
                }

                return array(
                        'packageId'   => (int) $package->id,
                        'packageName' => $package->package_name,
                        'days'        => (int) $package->number_of_days,
                        'nights'      => $default_nights,
                        'airportTransferFee' => isset( $package->airport_transfer_fee ) ? (float) $package->airport_transfer_fee : 0.0,
                        'seaportTransferFee' => isset( $package->seaport_transfer_fee ) ? (float) $package->seaport_transfer_fee : 0.0,
                        'hotels'      => $hotel_groups,
                        'activities'  => $activity_groups,
                );
        }

        private function extract_rate_prices( $row ) {
                $prices = array();

                for ( $p = 1; $p <= Hotel_Rates_DB::MAX_PAX; $p++ ) {
                        $col = 'pax_' . $p;
                        $prices[ (string) $p ] = isset( $row->$col ) && $row->$col !== null && $row->$col !== '' ? (float) $row->$col : null;
                }

                return $prices;
        }

        private function is_per_head_activity_option( $option ) {
                if ( is_array( $option ) && array_key_exists( 'perHead', $option ) ) {
                        return (bool) $option['perHead'];
                }

                $type = '';

                if ( is_array( $option ) ) {
                        $type = $option['type'] ?? '';
                } elseif ( is_object( $option ) ) {
                        $type = $option->type ?? '';
                }

                return in_array( (string) $type, array( 'Day Tour', 'Joiner' ), true );
        }

        private function get_package_free_activity_label( $value ) {
                $labels = $this->get_package_free_activity_options();

                return $labels[ (string) $value ] ?? '';
        }

        private function get_package_free_activity_options() {
                return array(
                        'hr_airport_seaport_pickup'  => __( 'Airport/Seaport Pick-up', 'hotel-rates' ),
                        'hr_airport_seaport_dropoff' => __( 'Airport/Seaport Drop-off', 'hotel-rates' ),
                        'hr_free_time'               => __( 'Free Time', 'hotel-rates' ),
                );
        }

        private function get_package_transit_day_label() {
                return __( 'Transit Day', 'hotel-rates' );
        }

        private function normalize_package_transit_day_fees_for_booking( $raw_fees ) {
                $raw_fees = is_array( $raw_fees ) ? $raw_fees : array();
                $fees = array();

                for ( $pax = 1; $pax <= Hotel_Rates_DB::MAX_PAX; $pax++ ) {
                        $value = $raw_fees[ (string) $pax ] ?? ( $raw_fees[ $pax ] ?? null );
                        $fees[ (string) $pax ] = null === $value || '' === $value ? null : (float) $value;
                }

                return $fees;
        }

        private function get_package_transit_day_pricing( $activity_group, $guest_counts ) {
                if ( ! is_array( $activity_group ) || ! is_array( $guest_counts ) ) {
                        return false;
                }

                $paid_guest_count = max( 0, (int) ( $guest_counts['paid'] ?? 0 ) );

                if ( $paid_guest_count < 1 ) {
                        return false;
                }

                $fees = isset( $activity_group['transitDayFees'] ) && is_array( $activity_group['transitDayFees'] ) ? $activity_group['transitDayFees'] : array();
                $unit_price = $fees[ (string) $paid_guest_count ] ?? null;

                if ( null === $unit_price || '' === $unit_price || ! is_numeric( $unit_price ) ) {
                        return false;
                }

                $unit_price = max( 0.0, (float) $unit_price );

                return array(
                        'unit_price' => $unit_price,
                        'pax'        => $paid_guest_count,
                        'total'      => $unit_price * $paid_guest_count,
                );
        }

        private function get_package_enabled_free_activity_options_for_day( $free_activity_options_map, $day ) {
                $allowed_options = array_keys( $this->get_package_free_activity_options() );
                $day_key = (string) max( 1, (int) $day );

                if ( ! is_array( $free_activity_options_map ) || ! array_key_exists( $day_key, $free_activity_options_map ) ) {
                        return $allowed_options;
                }

                $raw_options = is_array( $free_activity_options_map[ $day_key ] ) ? $free_activity_options_map[ $day_key ] : array();
                $enabled_options = array();

                foreach ( $raw_options as $option ) {
                        $option = sanitize_key( $option );

                        if ( in_array( $option, $allowed_options, true ) && ! in_array( $option, $enabled_options, true ) ) {
                                $enabled_options[] = $option;
                        }
                }

                return $enabled_options;
        }

        private function is_package_free_activity_option_enabled_for_group( $activity_group, $value ) {
                if ( ! is_array( $activity_group ) ) {
                        return false;
                }

                $enabled_options = isset( $activity_group['freeOptions'] ) && is_array( $activity_group['freeOptions'] )
                        ? array_map( 'strval', $activity_group['freeOptions'] )
                        : array_keys( $this->get_package_free_activity_options() );

                return in_array( (string) $value, $enabled_options, true );
        }

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
}
