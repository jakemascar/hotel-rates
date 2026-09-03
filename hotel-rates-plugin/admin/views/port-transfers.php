<?php if ( ! defined( 'ABSPATH' ) ) exit;

$is_edit = ! empty( $edit_port_transfer );
$config = $is_edit ? Hotel_Rates_DB::normalize_port_transfer_config( $edit_port_transfer->routes ?? '[]' ) : array(
        'fromOptions' => array( 'Dumaguete Airport', 'Dumaguete Seaport', 'Sibulan Seaport' ),
        'toOptions'   => array( 'Dumaguete City Hotels', 'Bacong Resorts', 'Dauin Resorts', 'Zamboanguita Resorts', 'Valencia Resorts' ),
        'rates'       => array(),
        'addons'      => array( array( 'label' => 'Midnight Surcharge', 'rate' => 500 ) ),
        'convenienceFeePercent' => 0,
);

$from_options = ! empty( $config['fromOptions'] ) ? $config['fromOptions'] : array( 'Dumaguete Airport', 'Dumaguete Seaport', 'Sibulan Seaport' );
$to_options = ! empty( $config['toOptions'] ) ? $config['toOptions'] : array( 'Dumaguete City Hotels', 'Bacong Resorts', 'Dauin Resorts', 'Zamboanguita Resorts', 'Valencia Resorts' );
$rate_lookup = array();
$addons = ! empty( $config['addons'] ) ? $config['addons'] : array( array( 'label' => 'Midnight Surcharge', 'rate' => 500 ) );
$convenience_fee_percent = isset( $config['convenienceFeePercent'] ) ? (float) $config['convenienceFeePercent'] : 0.0;
$transfer_times = $is_edit ? json_decode( $edit_port_transfer->transfer_times ?? '[]', true ) : array();
$transfer_times = is_array( $transfer_times ) && ! empty( $transfer_times ) ? $transfer_times : array( '06:00 AM', '07:00 AM', '08:00 AM', '09:00 AM', '10:00 AM', '11:00 AM' );

foreach ( $config['rates'] ?? array() as $rate ) {
        $rate_lookup[ ( $rate['from'] ?? '' ) . "\0" . ( $rate['to'] ?? '' ) ] = $rate['rate'] ?? '';
}
?>
<div class="wrap hr-wrap">
        <h1><?php esc_html_e( 'Port Transfers', 'hotel-rates' ); ?></h1>

        <?php if ( isset( $_GET['saved'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Port transfer saved successfully.', 'hotel-rates' ); ?></p></div>
        <?php endif; ?>
        <?php if ( isset( $_GET['deleted'] ) ) : ?>
                <div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Port transfer deleted.', 'hotel-rates' ); ?></p></div>
        <?php endif; ?>

        <div class="hr-rate-section">
                <h2><?php echo $is_edit ? esc_html__( 'Edit Port Transfer', 'hotel-rates' ) : esc_html__( 'Add Port Transfer', 'hotel-rates' ); ?></h2>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-reg-form hr-port-transfer-admin-form">
                        <input type="hidden" name="action" value="hr_save_port_transfer">
                        <input type="hidden" name="id" value="<?php echo esc_attr( $is_edit ? (int) $edit_port_transfer->id : 0 ); ?>">
                        <?php wp_nonce_field( 'hr_save_port_transfer' ); ?>

                        <div class="hr-reg-form-col">
                                <div class="hr-reg-field">
                                        <label for="hr-port-transfer-name"><?php esc_html_e( 'Transfer Name', 'hotel-rates' ); ?></label>
                                        <input type="text" id="hr-port-transfer-name" name="transfer_name" class="hr-reg-input" value="<?php echo esc_attr( $is_edit ? $edit_port_transfer->transfer_name : 'Dumaguete Port Transfer' ); ?>" placeholder="<?php esc_attr_e( 'e.g., Dumaguete Port Transfer', 'hotel-rates' ); ?>" required>
                                </div>
                                <div class="hr-reg-field">
                                        <label for="hr-port-transfer-convenience-fee-percent"><?php esc_html_e( 'Convenience Fee (%)', 'hotel-rates' ); ?></label>
                                        <input type="number" id="hr-port-transfer-convenience-fee-percent" name="convenience_fee_percent" class="hr-reg-input" value="<?php echo esc_attr( $convenience_fee_percent ); ?>" step="0.01" min="0" placeholder="5">
                                        <p class="description"><?php esc_html_e( 'Added to checkout/order totals as a percentage of the Port Transfer subtotal. This is not shown in the booking form.', 'hotel-rates' ); ?></p>
                                </div>
                        </div>

                        <div class="hr-reg-form-row hr-port-transfer-location-lists">
                                <div class="hr-reg-field">
                                        <label for="hr-port-transfer-from-options"><?php esc_html_e( 'Port / Airport / Seaport Options', 'hotel-rates' ); ?></label>
                                        <textarea id="hr-port-transfer-from-options" name="from_options" class="large-text hr-port-transfer-from-options" rows="6" placeholder="<?php esc_attr_e( 'One option per line', 'hotel-rates' ); ?>" required><?php echo esc_textarea( implode( "\n", $from_options ) ); ?></textarea>
                                        <p class="description"><?php esc_html_e( 'These are the options shown in the From dropdown before the guest swaps direction.', 'hotel-rates' ); ?></p>
                                </div>
                                <div class="hr-reg-field">
                                        <label for="hr-port-transfer-to-options"><?php esc_html_e( 'Hotel / Resort / Other Location Options', 'hotel-rates' ); ?></label>
                                        <textarea id="hr-port-transfer-to-options" name="to_options" class="large-text hr-port-transfer-to-options" rows="6" placeholder="<?php esc_attr_e( 'One option per line', 'hotel-rates' ); ?>" required><?php echo esc_textarea( implode( "\n", $to_options ) ); ?></textarea>
                                        <p class="description"><?php esc_html_e( 'These are the options shown in the To dropdown before the guest swaps direction.', 'hotel-rates' ); ?></p>
                                </div>
                        </div>

                        <div class="hr-port-transfer-admin-routes">
                                <h3><?php esc_html_e( 'One-Way Rate Matrix', 'hotel-rates' ); ?></h3>
                                <p class="description"><?php esc_html_e( 'Set one one-way rate for every port/location combination. Roundtrip uses the matching one-way rate multiplied by 2.', 'hotel-rates' ); ?></p>
                                <table class="widefat fixed striped hr-port-transfer-rate-matrix-table">
                                        <thead>
                                                <tr>
                                                        <th><?php esc_html_e( 'Port / Airport / Seaport', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Hotel / Resort / Other Location', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'One-Way Rate', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php $rate_index = 0; ?>
                                                <?php foreach ( $from_options as $from_option ) : ?>
                                                        <?php foreach ( $to_options as $to_option ) : ?>
                                                                <?php $lookup_key = $from_option . "\0" . $to_option; ?>
                                                                <tr class="hr-port-transfer-rate-row" data-from="<?php echo esc_attr( $from_option ); ?>" data-to="<?php echo esc_attr( $to_option ); ?>">
                                                                        <td>
                                                                                <strong><?php echo esc_html( $from_option ); ?></strong>
                                                                                <input type="hidden" name="rates[<?php echo esc_attr( $rate_index ); ?>][from]" value="<?php echo esc_attr( $from_option ); ?>" class="hr-port-transfer-rate-from">
                                                                        </td>
                                                                        <td>
                                                                                <strong><?php echo esc_html( $to_option ); ?></strong>
                                                                                <input type="hidden" name="rates[<?php echo esc_attr( $rate_index ); ?>][to]" value="<?php echo esc_attr( $to_option ); ?>" class="hr-port-transfer-rate-to">
                                                                        </td>
                                                                        <td><input type="number" name="rates[<?php echo esc_attr( $rate_index ); ?>][rate]" value="<?php echo esc_attr( $rate_lookup[ $lookup_key ] ?? '' ); ?>" class="widefat hr-port-transfer-rate-value" step="0.01" min="0" placeholder="0.00" required></td>
                                                                </tr>
                                                                <?php $rate_index++; ?>
                                                        <?php endforeach; ?>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                                <p><button type="button" class="button hr-port-transfer-rate-matrix-refresh"><?php esc_html_e( 'Refresh Rate Matrix', 'hotel-rates' ); ?></button></p>
                        </div>

                        <div class="hr-reg-form-row">
                                <div class="hr-reg-field">
                                        <label for="hr-port-transfer-times"><?php esc_html_e( 'Transfer Time Options', 'hotel-rates' ); ?></label>
                                        <textarea id="hr-port-transfer-times" name="transfer_times" class="large-text" rows="5" placeholder="<?php esc_attr_e( 'One option per line', 'hotel-rates' ); ?>"><?php echo esc_textarea( implode( "\n", $transfer_times ) ); ?></textarea>
                                </div>
                        </div>

                        <div class="hr-port-transfer-admin-addons">
                                <h3><?php esc_html_e( 'Add-ons', 'hotel-rates' ); ?></h3>
                                <table class="widefat fixed striped hr-port-transfer-addon-table">
                                        <thead>
                                                <tr>
                                                        <th><?php esc_html_e( 'Add-on Label', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Rate', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php foreach ( $addons as $index => $addon ) : ?>
                                                        <tr class="hr-port-transfer-addon-row">
                                                                <td><input type="text" name="addons[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $addon['label'] ?? '' ); ?>" class="widefat hr-port-transfer-addon-label" placeholder="<?php esc_attr_e( 'Midnight Surcharge', 'hotel-rates' ); ?>"></td>
                                                                <td><input type="number" name="addons[<?php echo esc_attr( $index ); ?>][rate]" value="<?php echo esc_attr( $addon['rate'] ?? '' ); ?>" class="widefat hr-port-transfer-addon-rate" step="0.01" min="0" placeholder="0.00"></td>
                                                                <td><button type="button" class="button-link-delete hr-port-transfer-addon-remove"><?php esc_html_e( 'Remove', 'hotel-rates' ); ?></button></td>
                                                        </tr>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                                <p><button type="button" class="button hr-port-transfer-addon-add"><?php esc_html_e( 'Add Add-on', 'hotel-rates' ); ?></button></p>
                        </div>

                        <p class="submit">
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Port Transfer', 'hotel-rates' ); ?></button>
                                <?php if ( $is_edit ) : ?>
                                        <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=hotel-rates-port-transfers' ) ); ?>"><?php esc_html_e( 'Cancel Edit', 'hotel-rates' ); ?></a>
                                <?php endif; ?>
                        </p>
                </form>
        </div>

        <div class="hr-rate-section">
                <h2><?php esc_html_e( 'Saved Port Transfers', 'hotel-rates' ); ?></h2>

                <?php if ( ! empty( $port_transfers ) ) : ?>
                <div class="hr-table-wrap">
                        <table class="wp-list-table widefat fixed striped hr-rate-table">
                                <thead>
                                        <tr>
                                                <th><?php esc_html_e( 'Transfer', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Locations', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Rates / Fees / Add-ons', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Times', 'hotel-rates' ); ?></th>
                                                <th class="hr-actions-col"><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
                                        </tr>
                                </thead>
                                <tbody>
                                        <?php foreach ( $port_transfers as $transfer ) : ?>
                                        <?php
                                        $transfer_config = Hotel_Rates_DB::normalize_port_transfer_config( $transfer->routes ?? '[]' );
                                        $times = json_decode( $transfer->transfer_times ?? '[]', true );
                                        $times = is_array( $times ) ? $times : array();
                                        $rate_count = count( $transfer_config['rates'] ?? array() );
                                        $saved_convenience_fee_percent = isset( $transfer_config['convenienceFeePercent'] ) ? (float) $transfer_config['convenienceFeePercent'] : 0.0;
                                        $addon_labels = array_map( function ( $addon ) {
                                                return sprintf(
                                                        '%1$s (%2$s)',
                                                        $addon['label'] ?? '',
                                                        function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( (float) ( $addon['rate'] ?? 0 ) ) ) : number_format( (float) ( $addon['rate'] ?? 0 ), 2 )
                                                );
                                        }, $transfer_config['addons'] ?? array() );
                                        ?>
                                        <tr>
                                                <td><strong><?php echo esc_html( $transfer->transfer_name ); ?></strong></td>
                                                <td>
                                                        <?php
                                                        echo esc_html( sprintf(
                                                                /* translators: 1: from option count, 2: to option count */
                                                                __( '%1$d port options, %2$d destination options', 'hotel-rates' ),
                                                                count( $transfer_config['fromOptions'] ?? array() ),
                                                                count( $transfer_config['toOptions'] ?? array() )
                                                        ) );
                                                        ?>
                                                </td>
                                                <td>
                                                        <?php echo esc_html( sprintf( _n( '%d rate', '%d rates', $rate_count, 'hotel-rates' ), $rate_count ) ); ?>
                                                        <?php if ( $saved_convenience_fee_percent > 0 ) : ?>
                                                                <br><span class="description"><?php echo esc_html( sprintf( __( 'Convenience Fee +%s%%', 'hotel-rates' ), rtrim( rtrim( number_format( $saved_convenience_fee_percent, 2, '.', '' ), '0' ), '.' ) ) ); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ( ! empty( $addon_labels ) ) : ?>
                                                                <br><span class="description"><?php echo esc_html( implode( ', ', $addon_labels ) ); ?></span>
                                                        <?php endif; ?>
                                                </td>
                                                <td><?php echo esc_html( implode( ', ', $times ) ); ?></td>
                                                <td class="hr-actions-col">
                                                        <a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'hotel-rates-port-transfers', 'edit_port_transfer' => (int) $transfer->id ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'hotel-rates' ); ?></a>
                                                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                                                                <input type="hidden" name="action" value="hr_delete_port_transfer">
                                                                <input type="hidden" name="id" value="<?php echo esc_attr( $transfer->id ); ?>">
                                                                <?php wp_nonce_field( 'hr_delete_port_transfer' ); ?>
                                                                <button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this port transfer?', 'hotel-rates' ); ?>')"><?php esc_html_e( 'Del', 'hotel-rates' ); ?></button>
                                                        </form>
                                                </td>
                                        </tr>
                                        <?php endforeach; ?>
                                </tbody>
                        </table>
                </div>
                <?php else : ?>
                        <p class="hr-no-rates"><?php esc_html_e( 'No port transfers added yet.', 'hotel-rates' ); ?></p>
                <?php endif; ?>
        </div>
</div>
