<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php wp_nonce_field( 'hr_save_product_self_drive_rental', 'hr_product_self_drive_rental_nonce' ); ?>

<p>
        <label for="hr-self-drive-rental-id" class="screen-reader-text"><?php esc_html_e( 'Linked Self-Drive Rental', 'hotel-rates' ); ?></label>
        <select
                name="hr_self_drive_rental_id"
                id="hr-self-drive-rental-id"
                class="widefat hr-product-link-search"
                data-placeholder="<?php esc_attr_e( 'Search self-drive rentals...', 'hotel-rates' ); ?>"
        >
                <option value=""><?php esc_html_e( 'No linked self-drive rental', 'hotel-rates' ); ?></option>
                <?php foreach ( $self_drive_rentals as $self_drive_rental ) : ?>
                        <option value="<?php echo esc_attr( $self_drive_rental->id ); ?>" <?php selected( $selected_self_drive_rental_id, (int) $self_drive_rental->id ); ?>>
                                <?php echo esc_html( $self_drive_rental->rental_name ); ?>
                        </option>
                <?php endforeach; ?>
        </select>
</p>

<?php if ( $selected_self_drive_rental ) : ?>
        <?php
        $vehicles = json_decode( $selected_self_drive_rental->vehicles ?? '[]', true );
        $vehicles = is_array( $vehicles ) ? $vehicles : array();
        ?>
        <div class="hr-product-package-summary">
                <p><strong><?php esc_html_e( 'Vehicles:', 'hotel-rates' ); ?></strong> <?php echo esc_html( count( $vehicles ) ); ?></p>
                <p>
                        <a href="<?php echo esc_url( add_query_arg( array(
                                'page' => 'hotel-rates-self-drive',
                                'edit_self_drive_rental' => (int) $selected_self_drive_rental->id,
                        ), admin_url( 'admin.php' ) ) ); ?>">
                                <?php esc_html_e( 'Edit this self-drive rental', 'hotel-rates' ); ?>
                        </a>
                </p>
        </div>
<?php else : ?>
        <p class="description"><?php esc_html_e( 'Select one saved Self-Drive Rental to sell this WooCommerce product.', 'hotel-rates' ); ?></p>
<?php endif; ?>

<p class="description"><?php esc_html_e( 'If this product also has a Package Deal, Tour, Airport Transfer, or Motorbike Rental linked, those booking forms are shown first.', 'hotel-rates' ); ?></p>
