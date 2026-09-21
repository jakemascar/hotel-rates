<?php
/**
 * Display-only currency compatibility for Hotel Rates Manager.
 *
 * All inputs to this class are raw store-currency (PHP) amounts. Converted
 * values returned here must never be used for booking, cart, order, or payment
 * calculations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hotel_Rates_Currency {

	/**
	 * Determine whether the public YayCurrency conversion API is available.
	 *
	 * @return bool
	 */
	public static function is_yaycurrency_available() {
		return class_exists( 'Yay_Currency\\Helpers\\YayCurrencyHelper' )
			&& method_exists( 'Yay_Currency\\Helpers\\YayCurrencyHelper', 'detect_current_currency' )
			&& false !== has_filter( 'yay_currency_convert_price' );
	}

	/**
	 * Get YayCurrency's currently selected currency metadata.
	 *
	 * @return array|false
	 */
	public static function get_current_currency() {
		if ( ! self::is_yaycurrency_available() ) {
			return false;
		}

		try {
			$current_currency = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
		} catch ( \Throwable $error ) {
			return false;
		}

		return is_array( $current_currency ) && ! empty( $current_currency['currency'] )
			? $current_currency
			: false;
	}

	/**
	 * Convert a copy of a raw PHP price for display only.
	 *
	 * @param int|float|string $raw_php_price Raw store-currency amount.
	 * @return float Raw PHP amount when conversion is unavailable; otherwise the converted display amount.
	 */
	public static function convert_display_amount( $raw_php_price ) {
		$raw_php_price   = is_numeric( $raw_php_price ) ? (float) $raw_php_price : 0.0;
		$current_currency = self::get_current_currency();

		if ( ! $current_currency ) {
			return $raw_php_price;
		}

		try {
			$display_amount = apply_filters( 'yay_currency_convert_price', $raw_php_price, $current_currency );
		} catch ( \Throwable $error ) {
			return $raw_php_price;
		}

		return is_numeric( $display_amount ) ? (float) $display_amount : $raw_php_price;
	}

	/**
	 * Format a raw PHP price for frontend display.
	 *
	 * @param int|float|string $raw_php_price Raw store-currency amount.
	 * @return string Safe WooCommerce-style price HTML.
	 */
	public static function format_display_price( $raw_php_price ) {
		$raw_php_price   = is_numeric( $raw_php_price ) ? (float) $raw_php_price : 0.0;
		$current_currency = self::get_current_currency();

		if ( ! $current_currency ) {
			return self::format_php_fallback( $raw_php_price );
		}

		$display_amount = self::convert_display_amount( $raw_php_price );

		if ( method_exists( 'Yay_Currency\\Helpers\\YayCurrencyHelper', 'format_price' ) ) {
			try {
				$display_html = \Yay_Currency\Helpers\YayCurrencyHelper::format_price( $display_amount, $current_currency );

				if ( is_string( $display_html ) && '' !== $display_html ) {
					return $display_html;
				}
			} catch ( \Throwable $error ) {
				// Continue to the explicit PHP fallback below.
			}
		}

		return self::format_php_fallback( $raw_php_price );
	}

	/**
	 * Provide display-only metadata for dynamic booking-form labels.
	 *
	 * @return array
	 */
	public static function get_frontend_display_data() {
		$fallback = array(
			'conversionAvailable' => false,
			'currency'            => 'PHP',
			'symbol'              => '₱',
			'rate'                => 1.0,
			'fee'                 => array(
				'type'  => 'fixed',
				'value' => 0,
			),
			'formatting'          => array(
				'currencyPosition'  => 'left',
				'thousandSeparator' => ',',
				'decimalSeparator'  => '.',
				'numberDecimal'     => 0,
				'roundingType'      => 'disabled',
				'roundingValue'     => 1,
				'subtractAmount'    => 0,
			),
		);

		$current_currency = self::get_current_currency();

		if ( ! $current_currency ) {
			return $fallback;
		}

		if ( false === has_filter( 'yay_currency_rate' ) ) {
			return $fallback;
		}

		$rate = apply_filters( 'yay_currency_rate', 1 );

		if ( ! is_numeric( $rate ) || (float) $rate <= 0 ) {
			return $fallback;
		}

		$symbol = isset( $current_currency['symbol'] )
			? html_entity_decode( wp_strip_all_tags( (string) $current_currency['symbol'] ), ENT_QUOTES, get_bloginfo( 'charset' ) )
			: '';

		if ( '' === $symbol ) {
			return $fallback;
		}

		return array(
			'conversionAvailable' => true,
			'currency'            => sanitize_text_field( (string) $current_currency['currency'] ),
			'symbol'              => $symbol,
			'rate'                => (float) $rate,
			'fee'                 => isset( $current_currency['fee'] ) && is_array( $current_currency['fee'] ) ? $current_currency['fee'] : $fallback['fee'],
			'formatting'          => array(
				'currencyPosition'  => self::get_metadata_value( $current_currency, 'currencyPosition', 'left' ),
				'thousandSeparator' => self::get_metadata_value( $current_currency, 'thousandSeparator', ',' ),
				'decimalSeparator'  => self::get_metadata_value( $current_currency, 'decimalSeparator', '.' ),
				'numberDecimal'     => max( 0, (int) self::get_metadata_value( $current_currency, 'numberDecimal', 0 ) ),
				'roundingType'      => self::get_metadata_value( $current_currency, 'roundingType', 'disabled' ),
				'roundingValue'     => (float) self::get_metadata_value( $current_currency, 'roundingValue', 1 ),
				'subtractAmount'    => (float) self::get_metadata_value( $current_currency, 'subtractAmount', 0 ),
			),
		);
	}

	/**
	 * Get one scalar value from YayCurrency metadata.
	 *
	 * @param array  $currency Currency metadata.
	 * @param string $key      Metadata key.
	 * @param mixed  $default  Fallback value.
	 * @return mixed
	 */
	private static function get_metadata_value( $currency, $key, $default ) {
		return isset( $currency[ $key ] ) && is_scalar( $currency[ $key ] ) ? $currency[ $key ] : $default;
	}

	/**
	 * Format raw store currency without consulting WooCommerce currency filters.
	 *
	 * @param float $raw_php_price Raw PHP amount.
	 * @return string
	 */
	private static function format_php_fallback( $raw_php_price ) {
		$is_negative = $raw_php_price < 0;
		$absolute    = abs( $raw_php_price );
		$decimals    = floor( $absolute ) === $absolute ? 0 : 2;
		$number      = number_format_i18n( $absolute, $decimals );

		return '<span class="woocommerce-Price-amount amount"><bdi>'
			. ( $is_negative ? '-' : '' )
			. '<span class="woocommerce-Price-currencySymbol">&#8369;</span>'
			. esc_html( $number )
			. '</bdi></span>';
	}
}
