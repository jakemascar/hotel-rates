<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
$selected_place_names = array();
$selected_place_ids   = $selected_package_deal && ! empty( $selected_package_deal->place_ids ) ? json_decode( $selected_package_deal->place_ids, true ) : array();
$selected_place_ids   = is_array( $selected_place_ids ) ? array_map( 'intval', $selected_place_ids ) : array();

foreach ( $selected_place_ids as $place_id ) {
        if ( isset( $places_lookup[ $place_id ] ) ) {
                $selected_place_names[] = $places_lookup[ $place_id ];
        }
}
?>
<?php wp_nonce_field( 'hr_save_product_package_deal', 'hr_product_package_deal_nonce' ); ?>

<p>
        <label for="hr-package-deal-id" class="screen-reader-text"><?php esc_html_e( 'Linked Package Deal', 'hotel-rates' ); ?></label>
        <select
                name="hr_package_deal_id"
                id="hr-package-deal-id"
                class="widefat hr-product-link-search"
                data-placeholder="<?php esc_attr_e( 'Search package deals...', 'hotel-rates' ); ?>"
        >
                <option value=""><?php esc_html_e( 'No linked package deal', 'hotel-rates' ); ?></option>
                <?php foreach ( $package_deals as $package_deal ) : ?>
                        <option value="<?php echo esc_attr( $package_deal->id ); ?>" <?php selected( $selected_package_deal_id, (int) $package_deal->id ); ?>>
                                <?php echo esc_html( $package_deal->package_name ); ?>
                        </option>
                <?php endforeach; ?>
        </select>
</p>

<?php if ( $selected_package_deal ) : ?>
        <div class="hr-product-package-summary">
                <p><strong><?php esc_html_e( 'Days:', 'hotel-rates' ); ?></strong> <?php echo esc_html( (int) $selected_package_deal->number_of_days ); ?></p>
                <p><strong><?php esc_html_e( 'Places:', 'hotel-rates' ); ?></strong> <?php echo esc_html( ! empty( $selected_place_names ) ? implode( ', ', $selected_place_names ) : __( 'No places assigned', 'hotel-rates' ) ); ?></p>
                <p>
                        <a href="<?php echo esc_url( add_query_arg( array(
                                'page'              => 'hotel-rates-register',
                                'tab'               => 'package-deals',
                                'edit_package_deal' => (int) $selected_package_deal->id,
                        ), admin_url( 'admin.php' ) ) ); ?>">
                                <?php esc_html_e( 'Edit this package deal', 'hotel-rates' ); ?>
                        </a>
                </p>
        </div>
<?php else : ?>
        <p class="description"><?php esc_html_e( 'Select one saved Package Deal to link this WooCommerce product to your Travel Rates package setup.', 'hotel-rates' ); ?></p>
<?php endif; ?>
