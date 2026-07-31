<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php wp_nonce_field( 'hr_save_product_motorbike_rental', 'hr_product_motorbike_rental_nonce' ); ?>

<p>
        <label for="hr-motorbike-rental-id" class="screen-reader-text"><?php esc_html_e( 'Linked Motorbike Rental', 'hotel-rates' ); ?></label>
        <select
                name="hr_motorbike_rental_id"
                id="hr-motorbike-rental-id"
                class="widefat hr-product-link-search"
                data-placeholder="<?php esc_attr_e( 'Search motorbike rentals...', 'hotel-rates' ); ?>"
        >
                <option value=""><?php esc_html_e( 'No linked motorbike rental', 'hotel-rates' ); ?></option>
                <?php foreach ( $motorbike_rentals as $motorbike_rental ) : ?>
                        <option value="<?php echo esc_attr( $motorbike_rental->id ); ?>" <?php selected( $selected_motorbike_rental_id, (int) $motorbike_rental->id ); ?>>
                                <?php echo esc_html( $motorbike_rental->rental_name ); ?>
                        </option>
                <?php endforeach; ?>
        </select>
</p>

<?php if ( $selected_motorbike_rental ) : ?>
        <?php
        $models = json_decode( $selected_motorbike_rental->models ?? '[]', true );
        $models = is_array( $models ) ? $models : array();
        ?>
        <div class="hr-product-package-summary">
                <p><strong><?php esc_html_e( 'Models:', 'hotel-rates' ); ?></strong> <?php echo esc_html( count( $models ) ); ?></p>
                <p>
                        <a href="<?php echo esc_url( add_query_arg( array(
                                'page' => 'hotel-rates-motorbikes',
                                'edit_motorbike_rental' => (int) $selected_motorbike_rental->id,
                        ), admin_url( 'admin.php' ) ) ); ?>">
                                <?php esc_html_e( 'Edit this motorbike rental', 'hotel-rates' ); ?>
                        </a>
                </p>
        </div>
<?php else : ?>
        <p class="description"><?php esc_html_e( 'Select one saved Motorbike Rental to sell this WooCommerce product.', 'hotel-rates' ); ?></p>
<?php endif; ?>

<p class="description"><?php esc_html_e( 'If this product also has a Package Deal, Tour, or Airport Transfer linked, those booking forms are shown first.', 'hotel-rates' ); ?></p>
