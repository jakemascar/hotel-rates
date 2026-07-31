<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php wp_nonce_field( 'hr_save_product_pickup_dropoff_service', 'hr_product_pickup_dropoff_service_nonce' ); ?>

<p>
        <label for="hr-pickup-dropoff-service-id" class="screen-reader-text"><?php esc_html_e( 'Linked Pick-up & Drop-off Service', 'hotel-rates' ); ?></label>
        <select
                name="hr_pickup_dropoff_service_id"
                id="hr-pickup-dropoff-service-id"
                class="widefat hr-product-link-search"
                data-placeholder="<?php esc_attr_e( 'Search pick-up and drop-off services...', 'hotel-rates' ); ?>"
        >
                <option value=""><?php esc_html_e( 'No linked Pick-up & Drop-off service', 'hotel-rates' ); ?></option>
                <?php foreach ( $pickup_dropoff_services as $pickup_dropoff_service ) : ?>
                        <option value="<?php echo esc_attr( $pickup_dropoff_service->id ); ?>" <?php selected( $selected_pickup_dropoff_service_id, (int) $pickup_dropoff_service->id ); ?>>
                                <?php echo esc_html( $pickup_dropoff_service->service_name ); ?>
                        </option>
                <?php endforeach; ?>
        </select>
</p>

<?php if ( $selected_pickup_dropoff_service ) : ?>
        <?php
        $locations = Hotel_Rates_DB::normalize_pickup_dropoff_locations( json_decode( $selected_pickup_dropoff_service->locations ?? '[]', true ) ?: array() );
        $routes = Hotel_Rates_DB::normalize_pickup_dropoff_route_rates( json_decode( $selected_pickup_dropoff_service->route_rates ?? '[]', true ) ?: array(), $locations );
        $addons = Hotel_Rates_DB::normalize_port_transfer_addons( json_decode( $selected_pickup_dropoff_service->addons ?? '[]', true ) ?: array() );
        ?>
        <div class="hr-product-package-summary">
                <p><strong><?php esc_html_e( 'Locations:', 'hotel-rates' ); ?></strong> <?php echo esc_html( count( $locations ) ); ?></p>
                <p><strong><?php esc_html_e( 'Route rates:', 'hotel-rates' ); ?></strong> <?php echo esc_html( count( $routes ) ); ?></p>
                <p><strong><?php esc_html_e( 'Add-ons:', 'hotel-rates' ); ?></strong> <?php echo esc_html( count( $addons ) ); ?></p>
                <p>
                        <a href="<?php echo esc_url( add_query_arg( array(
                                'page' => 'hotel-rates-pickup-dropoff',
                                'edit_pickup_dropoff_service' => (int) $selected_pickup_dropoff_service->id,
                        ), admin_url( 'admin.php' ) ) ); ?>">
                                <?php esc_html_e( 'Edit this Pick-up & Drop-off service', 'hotel-rates' ); ?>
                        </a>
                </p>
        </div>
<?php else : ?>
        <p class="description"><?php esc_html_e( 'Select one saved Pick-up & Drop-off service to sell this WooCommerce product.', 'hotel-rates' ); ?></p>
<?php endif; ?>

<p class="description"><?php esc_html_e( 'If this product also has another custom booking module linked, those booking forms are shown first.', 'hotel-rates' ); ?></p>
