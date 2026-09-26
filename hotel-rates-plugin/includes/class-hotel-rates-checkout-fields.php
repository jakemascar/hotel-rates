<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Optional checkout details that are stored with each WooCommerce order.
 */
class Hotel_Rates_Checkout_Fields {

	const NATIONALITY_META = '_hr_nationality';
	const PHONE_META       = '_hr_phone_whatsapp';
	const REQUEST_META     = '_hr_special_request';

	public function __construct() {
		add_filter( 'woocommerce_checkout_fields', array( $this, 'add_checkout_fields' ) );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout_fields' ), 20, 2 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_checkout_fields' ), 20, 2 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'show_admin_order_fields' ) );
		add_filter( 'woocommerce_email_order_meta_fields', array( $this, 'add_email_order_fields' ), 20, 3 );
		add_action( 'woocommerce_order_details_after_customer_details', array( $this, 'show_customer_order_fields' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_assets' ), 30 );
	}

	public function add_checkout_fields( $fields ) {
		if ( empty( $fields['billing'] ) || ! is_array( $fields['billing'] ) ) {
			$fields['billing'] = array();
		}

		$this->set_field_layout( $fields['billing'], 'billing_first_name', 10, array( 'form-row-first' ) );
		$this->set_field_layout( $fields['billing'], 'billing_last_name', 20, array( 'form-row-last' ) );

		$fields['billing']['billing_hr_nationality'] = array(
			'type'         => 'select',
			'label'        => __( 'Nationality', 'hotel-rates' ),
			'placeholder'  => __( 'Select nationality', 'hotel-rates' ),
			'required'     => true,
			'class'        => array( 'form-row-first', 'hr-nationality-field' ),
			'input_class'  => array( 'hr-nationality-select' ),
			'priority'     => 30,
			'options'      => array( '' => __( 'Select nationality', 'hotel-rates' ) ) + $this->get_countries(),
			'autocomplete' => 'country-name',
		);

		$fields['billing']['billing_hr_phone_whatsapp'] = array(
			'type'         => 'tel',
			'label'        => __( 'Phone / WhatsApp', 'hotel-rates' ),
			'placeholder'  => __( 'Phone / WhatsApp', 'hotel-rates' ),
			'required'     => true,
			'class'        => array( 'form-row-last', 'hr-phone-whatsapp-field' ),
			'input_class'  => array( 'input-text' ),
			'priority'     => 40,
			'autocomplete' => 'tel',
		);

		$fields['billing']['billing_hr_special_request'] = array(
			'type'        => 'textarea',
			'label'       => __( 'Message/Special Request', 'hotel-rates' ),
			'placeholder' => __( 'Message/Special Request', 'hotel-rates' ),
			'required'    => false,
			'class'       => array( 'form-row-wide', 'hr-special-request-field' ),
			'priority'    => 100,
		);

		$this->set_field_layout( $fields['billing'], 'billing_country', 50, array( 'form-row-wide' ) );
		$this->set_field_layout( $fields['billing'], 'billing_address_1', 60, array( 'form-row-wide' ) );
		$this->set_field_layout( $fields['billing'], 'billing_address_2', 61, array( 'form-row-wide' ) );
		$this->set_field_layout( $fields['billing'], 'billing_city', 70 );
		$this->set_field_layout( $fields['billing'], 'billing_state', 80 );
		$this->set_field_layout( $fields['billing'], 'billing_postcode', 90 );

		return $fields;
	}

	private function set_field_layout( &$fields, $key, $priority, $classes = null ) {
		if ( empty( $fields[ $key ] ) || ! is_array( $fields[ $key ] ) ) {
			return;
		}

		$fields[ $key ]['priority'] = $priority;
		if ( null !== $classes ) {
			$fields[ $key ]['class'] = $classes;
		}
	}

	public function validate_checkout_fields( $data, $errors ) {
		$nationality = isset( $data['billing_hr_nationality'] ) ? strtoupper( sanitize_text_field( $data['billing_hr_nationality'] ) ) : '';
		$phone = isset( $data['billing_hr_phone_whatsapp'] ) ? sanitize_text_field( $data['billing_hr_phone_whatsapp'] ) : '';

		if ( '' !== $nationality && ! array_key_exists( $nationality, $this->get_countries() ) ) {
			$errors->add( 'billing_hr_nationality_validation', __( 'Please select a valid nationality.', 'hotel-rates' ) );
		}
		if ( '' !== $phone && strlen( preg_replace( '/\D+/', '', $phone ) ) < 6 ) {
			$errors->add( 'billing_hr_phone_whatsapp_validation', __( 'Please enter a complete Phone / WhatsApp number.', 'hotel-rates' ) );
		}
	}

	public function save_checkout_fields( $order, $data ) {
		$nationality = isset( $data['billing_hr_nationality'] ) ? strtoupper( sanitize_text_field( $data['billing_hr_nationality'] ) ) : '';
		$phone = isset( $data['billing_hr_phone_whatsapp'] ) ? sanitize_text_field( $data['billing_hr_phone_whatsapp'] ) : '';
		$request = isset( $data['billing_hr_special_request'] ) ? sanitize_textarea_field( $data['billing_hr_special_request'] ) : '';

		if ( '' !== $nationality && array_key_exists( $nationality, $this->get_countries() ) ) {
			$order->update_meta_data( self::NATIONALITY_META, $nationality );
		}
		if ( '' !== $phone ) {
			$order->update_meta_data( self::PHONE_META, $phone );
		}
		if ( '' !== $request ) {
			$order->update_meta_data( self::REQUEST_META, $request );
		}
	}

	public function show_admin_order_fields( $order ) {
		foreach ( $this->get_order_values( $order ) as $value ) {
			if ( '' === $value['value'] ) {
				continue;
			}
			printf(
				'<p><strong>%1$s:</strong><br>%2$s</p>',
				esc_html( $value['label'] ),
				nl2br( esc_html( $value['value'] ) )
			);
		}
	}

	public function add_email_order_fields( $fields, $sent_to_admin, $order ) {
		foreach ( $this->get_order_values( $order ) as $key => $value ) {
			if ( '' !== $value['value'] ) {
				$fields[ 'hr_' . $key ] = $value;
			}
		}
		return $fields;
	}

	public function show_customer_order_fields( $order ) {
		$values = $this->get_order_values( $order );
		$values = array_filter( $values, function ( $value ) {
			return '' !== $value['value'];
		} );
		if ( empty( $values ) ) {
			return;
		}
		$this->render_customer_order_fields( $values );
	}

	public function enqueue_checkout_assets() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		$dependencies = array( 'jquery' );
		if ( wp_script_is( 'selectWoo', 'registered' ) ) {
			wp_enqueue_script( 'selectWoo' );
			$dependencies[] = 'selectWoo';
		} elseif ( wp_script_is( 'select2', 'registered' ) ) {
			wp_enqueue_script( 'select2' );
			$dependencies[] = 'select2';
		}
		if ( wp_style_is( 'select2', 'registered' ) ) {
			wp_enqueue_style( 'select2' );
		}

		wp_enqueue_style( 'hr-checkout-fields', HR_PLUGIN_URL . 'public/css/checkout-fields.css', array(), HR_VERSION );
		wp_enqueue_script( 'hr-checkout-fields', HR_PLUGIN_URL . 'public/js/checkout-fields.js', $dependencies, HR_VERSION, true );
		wp_localize_script( 'hr-checkout-fields', 'hrCheckoutFields', array(
			'flagsUrl' => HR_PLUGIN_URL . 'public/vendor/flag-icons/flags/24x18/',
		) );
	}

	private function render_customer_order_fields( $values ) {
		echo '<section><h2>' . esc_html__( 'Additional information', 'hotel-rates' ) . '</h2><dl>';
		foreach ( $values as $value ) {
			echo '<dt>' . esc_html( $value['label'] ) . '</dt><dd>';
			echo nl2br( esc_html( $value['value'] ) ) . '</dd>';
		}
		echo '</dl></section>';
	}

	private function get_order_values( $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
			return array();
		}
		$code = strtoupper( (string) $order->get_meta( self::NATIONALITY_META ) );
		$countries = $this->get_countries();

		return array(
			'nationality' => array(
				'label' => __( 'Nationality', 'hotel-rates' ),
				'value' => $countries[ $code ] ?? '',
			),
			'phone_whatsapp' => array(
				'label' => __( 'Phone / WhatsApp', 'hotel-rates' ),
				'value' => (string) $order->get_meta( self::PHONE_META ),
			),
			'special_request' => array(
				'label' => __( 'Message/Special Request', 'hotel-rates' ),
				'value' => (string) $order->get_meta( self::REQUEST_META ),
			),
		);
	}

	private function get_countries() {
		if ( function_exists( 'WC' ) && WC()->countries ) {
			return WC()->countries->get_countries();
		}
		if ( class_exists( 'WC_Countries' ) ) {
			$countries = new WC_Countries();
			return $countries->get_countries();
		}
		return array();
	}

}
