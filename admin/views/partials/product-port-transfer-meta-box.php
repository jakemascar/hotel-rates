<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php wp_nonce_field( 'hr_save_product_port_transfer', 'hr_product_port_transfer_nonce' ); ?>

<p>
        <label for="hr-port-transfer-id" class="screen-reader-text"><?php esc_html_e( 'Linked Port Transfer', 'hotel-rates' ); ?></label>
        <select
                name="hr_port_transfer_id"
                id="hr-port-transfer-id"
                class="widefat hr-product-link-search"
                data-placeholder="<?php esc_attr_e( 'Search port transfers...', 'hotel-rates' ); ?>"
        >
                <option value=""><?php esc_html_e( 'No linked port transfer', 'hotel-rates' ); ?></option>
                <?php foreach ( $port_transfers as $port_transfer ) : ?>
                        <option value="<?php echo esc_attr( $port_transfer->id ); ?>" <?php selected( $selected_port_transfer_id, (int) $port_transfer->id ); ?>>
                                <?php echo esc_html( $port_transfer->transfer_name ); ?>
                        </option>
                <?php endforeach; ?>
        </select>
</p>

<?php if ( $selected_port_transfer ) : ?>
        <?php
        $config = Hotel_Rates_DB::normalize_port_transfer_config( $selected_port_transfer->routes ?? '[]' );
        ?>
        <div class="hr-product-package-summary">
                <p><strong><?php esc_html_e( 'Rate matrix:', 'hotel-rates' ); ?></strong> <?php echo esc_html( count( $config['rates'] ?? array() ) ); ?></p>
                <p class="description">
                        <?php
                        echo esc_html( sprintf(
                                /* translators: 1: from option count, 2: to option count */
                                __( '%1$d port options, %2$d destination options', 'hotel-rates' ),
                                count( $config['fromOptions'] ?? array() ),
                                count( $config['toOptions'] ?? array() )
                        ) );
                        ?>
                </p>
                <p>
                        <a href="<?php echo esc_url( add_query_arg( array(
                                'page' => 'hotel-rates-port-transfers',
                                'edit_port_transfer' => (int) $selected_port_transfer->id,
                        ), admin_url( 'admin.php' ) ) ); ?>">
                                <?php esc_html_e( 'Edit this port transfer', 'hotel-rates' ); ?>
                        </a>
                </p>
        </div>
<?php else : ?>
        <p class="description"><?php esc_html_e( 'Select one saved Port Transfer to sell this WooCommerce product.', 'hotel-rates' ); ?></p>
<?php endif; ?>

<p class="description"><?php esc_html_e( 'If this product also has another custom booking module linked, those booking forms are shown first.', 'hotel-rates' ); ?></p>
