<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php wp_nonce_field( 'hr_save_product_destination_tour', 'hr_product_destination_tour_nonce' ); ?>

<p>
        <label for="hr-destination-tour-id" class="screen-reader-text"><?php esc_html_e( 'Linked Destination Tour', 'hotel-rates' ); ?></label>
        <select
                name="hr_destination_tour_id"
                id="hr-destination-tour-id"
                class="widefat hr-product-link-search"
                data-placeholder="<?php esc_attr_e( 'Search destination tours...', 'hotel-rates' ); ?>"
        >
                <option value=""><?php esc_html_e( 'No linked destination tour', 'hotel-rates' ); ?></option>
                <?php foreach ( $destination_tours as $destination_tour ) : ?>
                        <option value="<?php echo esc_attr( $destination_tour->id ); ?>" <?php selected( $selected_destination_tour_id, (int) $destination_tour->id ); ?>>
                                <?php echo esc_html( $destination_tour->tour_name ); ?>
                        </option>
                <?php endforeach; ?>
        </select>
</p>

<?php if ( $selected_destination_tour ) : ?>
        <?php
        $destinations = Hotel_Rates_DB::normalize_destination_tour_destinations( json_decode( $selected_destination_tour->destinations ?? '[]', true ) ?: array() );
        $addons = Hotel_Rates_DB::normalize_port_transfer_addons( json_decode( $selected_destination_tour->pickup_addons ?? '[]', true ) ?: array() );
        ?>
        <div class="hr-product-package-summary">
                <p><strong><?php esc_html_e( 'Destinations:', 'hotel-rates' ); ?></strong> <?php echo esc_html( count( $destinations ) ); ?></p>
                <p><strong><?php esc_html_e( 'Pick-up add-ons:', 'hotel-rates' ); ?></strong> <?php echo esc_html( count( $addons ) ); ?></p>
                <p>
                        <a href="<?php echo esc_url( add_query_arg( array(
                                'page' => 'hotel-rates-destination-tours',
                                'edit_destination_tour' => (int) $selected_destination_tour->id,
                        ), admin_url( 'admin.php' ) ) ); ?>">
                                <?php esc_html_e( 'Edit this destination tour', 'hotel-rates' ); ?>
                        </a>
                </p>
        </div>
<?php else : ?>
        <p class="description"><?php esc_html_e( 'Select one saved Destination Tour to sell this WooCommerce product.', 'hotel-rates' ); ?></p>
<?php endif; ?>

<p class="description"><?php esc_html_e( 'If this product also has another custom booking module linked, those booking forms are shown first.', 'hotel-rates' ); ?></p>
