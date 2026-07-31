<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php wp_nonce_field( 'hr_save_product_boat_rental', 'hr_product_boat_rental_nonce' ); ?>

<p>
        <label for="hr-boat-rental-id" class="screen-reader-text"><?php esc_html_e( 'Linked Boat Rental', 'hotel-rates' ); ?></label>
        <select
                name="hr_boat_rental_id"
                id="hr-boat-rental-id"
                class="widefat hr-product-link-search"
                data-placeholder="<?php esc_attr_e( 'Search boat rentals...', 'hotel-rates' ); ?>"
        >
                <option value=""><?php esc_html_e( 'No linked boat rental', 'hotel-rates' ); ?></option>
                <?php foreach ( $boat_rentals as $boat_rental ) : ?>
                        <option value="<?php echo esc_attr( $boat_rental->id ); ?>" <?php selected( $selected_boat_rental_id, (int) $boat_rental->id ); ?>>
                                <?php echo esc_html( $boat_rental->rental_name ); ?>
                        </option>
                <?php endforeach; ?>
        </select>
</p>

<?php if ( $selected_boat_rental ) : ?>
        <?php
        $rates = json_decode( $selected_boat_rental->rates ?? '[]', true );
        $rates = is_array( $rates ) ? $rates : array();
        ?>
        <div class="hr-product-package-summary">
                <p><strong><?php esc_html_e( 'Rate brackets:', 'hotel-rates' ); ?></strong> <?php echo esc_html( count( $rates ) ); ?></p>
                <p>
                        <a href="<?php echo esc_url( add_query_arg( array(
                                'page' => 'hotel-rates-boats',
                                'edit_boat_rental' => (int) $selected_boat_rental->id,
                        ), admin_url( 'admin.php' ) ) ); ?>">
                                <?php esc_html_e( 'Edit this boat rental', 'hotel-rates' ); ?>
                        </a>
                </p>
        </div>
<?php else : ?>
        <p class="description"><?php esc_html_e( 'Select one saved Boat Rental to sell this WooCommerce product.', 'hotel-rates' ); ?></p>
<?php endif; ?>

<p class="description"><?php esc_html_e( 'If this product also has a Package Deal, Tour, Airport Transfer, Motorbike Rental, or Self-Drive Vehicle linked, those booking forms are shown first.', 'hotel-rates' ); ?></p>
