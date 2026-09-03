<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
$is_joiner          = isset( $tour_link_type ) && 'Joiner' === $tour_link_type;
$is_airport_transfer = isset( $tour_link_type ) && 'Airport Transfer' === $tour_link_type;

if ( $is_airport_transfer ) {
        $field_name    = 'hr_airport_transfer_rate_id';
        $field_id      = 'hr-airport-transfer-rate-id';
        $nonce_action  = 'hr_save_product_airport_transfer_rate';
        $nonce_name    = 'hr_product_airport_transfer_rate_nonce';
        $label         = __( 'Linked Airport Transfer', 'hotel-rates' );
        $empty_label   = __( 'No linked airport transfer', 'hotel-rates' );
        $placeholder   = __( 'Search airport transfers...', 'hotel-rates' );
        $empty_help    = __( 'Select one saved Airport Transfer to sell this WooCommerce product.', 'hotel-rates' );
        $priority_help = __( 'If this product also has a Package Deal, Day Tour, or Joiner Tour linked, those booking forms are shown first.', 'hotel-rates' );
} elseif ( $is_joiner ) {
        $field_name    = 'hr_joiner_tour_rate_id';
        $field_id      = 'hr-joiner-tour-rate-id';
        $nonce_action  = 'hr_save_product_joiner_tour_rate';
        $nonce_name    = 'hr_product_joiner_tour_rate_nonce';
        $label         = __( 'Linked Joiner Tour', 'hotel-rates' );
        $empty_label   = __( 'No linked joiner tour', 'hotel-rates' );
        $placeholder   = __( 'Search joiner tours...', 'hotel-rates' );
        $empty_help    = __( 'Select one saved Joiner tour to sell this WooCommerce product without hotel selection.', 'hotel-rates' );
        $priority_help = __( 'If this product also has a Package Deal or Day Tour linked, those booking forms are shown first.', 'hotel-rates' );
} else {
        $field_name    = 'hr_day_tour_rate_id';
        $field_id      = 'hr-day-tour-rate-id';
        $nonce_action  = 'hr_save_product_day_tour_rate';
        $nonce_name    = 'hr_product_day_tour_rate_nonce';
        $label         = __( 'Linked Day Tour', 'hotel-rates' );
        $empty_label   = __( 'No linked day tour', 'hotel-rates' );
        $placeholder   = __( 'Search day tours...', 'hotel-rates' );
        $empty_help    = __( 'Select one saved Day Tour to sell this WooCommerce product without hotel selection.', 'hotel-rates' );
        $priority_help = __( 'If this product also has a Package Deal linked, the package booking form is shown first.', 'hotel-rates' );
}
?>
<?php wp_nonce_field( $nonce_action, $nonce_name ); ?>

<p>
        <label for="<?php echo esc_attr( $field_id ); ?>" class="screen-reader-text"><?php echo esc_html( $label ); ?></label>
        <select
                name="<?php echo esc_attr( $field_name ); ?>"
                id="<?php echo esc_attr( $field_id ); ?>"
                class="widefat hr-product-link-search"
                data-placeholder="<?php echo esc_attr( $placeholder ); ?>"
        >
                <option value=""><?php echo esc_html( $empty_label ); ?></option>
                <?php foreach ( $tour_rates as $tour_rate ) : ?>
                        <option value="<?php echo esc_attr( $tour_rate->id ); ?>" <?php selected( $selected_tour_rate_id, (int) $tour_rate->id ); ?>>
                                <?php
                                echo esc_html( sprintf(
                                        '%1$s (%2$s)',
                                        $tour_rate->tour_name,
                                        $tour_rate->place_name
                                ) );
                                ?>
                        </option>
                <?php endforeach; ?>
        </select>
</p>

<?php if ( $selected_tour_rate ) : ?>
        <div class="hr-product-package-summary">
                <p><strong><?php esc_html_e( 'Type:', 'hotel-rates' ); ?></strong> <?php echo esc_html( $selected_tour_rate->type ); ?></p>
                <p><strong><?php esc_html_e( 'Place:', 'hotel-rates' ); ?></strong> <?php echo esc_html( $selected_tour_rate->place_name ); ?></p>
                <p>
                        <a href="<?php echo esc_url( add_query_arg( array(
                                'page'      => 'hotel-rates-tour',
                                'region_id' => (int) $selected_tour_rate->region_id,
                                'place_id'  => (int) $selected_tour_rate->place_id,
                                'tour_type' => $selected_tour_rate->type,
                        ), admin_url( 'admin.php' ) ) ); ?>">
                                <?php
                                if ( $is_airport_transfer ) {
                                        esc_html_e( 'Edit this airport transfer', 'hotel-rates' );
                                } elseif ( $is_joiner ) {
                                        esc_html_e( 'Edit this joiner tour', 'hotel-rates' );
                                } else {
                                        esc_html_e( 'Edit this day tour', 'hotel-rates' );
                                }
                                ?>
                        </a>
                </p>
        </div>
<?php else : ?>
        <p class="description"><?php echo esc_html( $empty_help ); ?></p>
<?php endif; ?>

<p class="description"><?php echo esc_html( $priority_help ); ?></p>
