<?php if ( ! defined( 'ABSPATH' ) ) exit;

$is_edit = ! empty( $edit_self_drive_rental );
$vehicles = $is_edit ? json_decode( $edit_self_drive_rental->vehicles ?? '[]', true ) : array();
$vehicles = is_array( $vehicles ) ? $vehicles : array();
$start_times = $is_edit ? json_decode( $edit_self_drive_rental->start_times ?? '[]', true ) : array();
$start_times = is_array( $start_times ) ? $start_times : array( '8:00 AM', '9:00 AM', '10:00 AM' );
$delivery_fee = $is_edit && null !== $edit_self_drive_rental->delivery_fee ? $edit_self_drive_rental->delivery_fee : '500.00';
$credit_card_fee_percent = $is_edit && isset( $edit_self_drive_rental->credit_card_fee_percent ) && null !== $edit_self_drive_rental->credit_card_fee_percent ? (float) $edit_self_drive_rental->credit_card_fee_percent : 5.0;

if ( empty( $vehicles ) ) {
        $vehicles[] = array( 'name' => '', 'rate' => '', 'stock' => 1, 'seaters' => '5' );
}
?>
<div class="wrap hr-wrap">
        <h1><?php esc_html_e( 'Self-Drive Vehicles', 'hotel-rates' ); ?></h1>

        <?php if ( isset( $_GET['saved'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Self-drive rental saved successfully.', 'hotel-rates' ); ?></p></div>
        <?php endif; ?>
        <?php if ( isset( $_GET['deleted'] ) ) : ?>
                <div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Self-drive rental deleted.', 'hotel-rates' ); ?></p></div>
        <?php endif; ?>

        <div class="hr-rate-section">
                <h2><?php echo $is_edit ? esc_html__( 'Edit Self-Drive Rental', 'hotel-rates' ) : esc_html__( 'Add Self-Drive Rental', 'hotel-rates' ); ?></h2>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-reg-form hr-self-drive-admin-form">
                        <input type="hidden" name="action" value="hr_save_self_drive_rental">
                        <input type="hidden" name="id" value="<?php echo esc_attr( $is_edit ? (int) $edit_self_drive_rental->id : 0 ); ?>">
                        <?php wp_nonce_field( 'hr_save_self_drive_rental' ); ?>

                        <div class="hr-reg-form-col">
                                <div class="hr-reg-field">
                                        <label for="hr-self-drive-rental-name"><?php esc_html_e( 'Rental Name', 'hotel-rates' ); ?></label>
                                        <input type="text" id="hr-self-drive-rental-name" name="rental_name" class="hr-reg-input" value="<?php echo esc_attr( $is_edit ? $edit_self_drive_rental->rental_name : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., Dumaguete Self-Drive Vehicles', 'hotel-rates' ); ?>" required>
                                </div>

                                <div class="hr-reg-field hr-reg-field--sm">
                                        <label for="hr-self-drive-delivery-fee"><?php esc_html_e( 'Delivery Fee', 'hotel-rates' ); ?></label>
                                        <input type="number" id="hr-self-drive-delivery-fee" name="delivery_fee" class="hr-reg-input" value="<?php echo esc_attr( $delivery_fee ); ?>" step="0.01" min="0" placeholder="500.00">
                                </div>

                                <div class="hr-reg-field hr-reg-field--sm">
                                        <label for="hr-self-drive-credit-card-fee"><?php esc_html_e( 'Credit Card Fee (%)', 'hotel-rates' ); ?></label>
                                        <input type="number" id="hr-self-drive-credit-card-fee" name="credit_card_fee_percent" class="hr-reg-input" value="<?php echo esc_attr( $credit_card_fee_percent ); ?>" step="0.01" min="0" placeholder="5">
                                        <p class="description"><?php esc_html_e( 'Added to checkout/order totals as a percentage of the Self-drive Rental subtotal.', 'hotel-rates' ); ?></p>
                                </div>
                        </div>

                        <div class="hr-self-drive-admin-vehicles">
                                <h3><?php esc_html_e( 'Vehicles', 'hotel-rates' ); ?></h3>
                                <table class="widefat fixed striped hr-self-drive-vehicle-table">
                                        <thead>
                                                <tr>
                                                        <th><?php esc_html_e( 'Vehicle Type', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Daily Rate', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Available Qty', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Seaters', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php foreach ( $vehicles as $index => $vehicle ) : ?>
                                                <tr class="hr-self-drive-vehicle-row">
                                                        <td><input type="text" name="vehicles[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $vehicle['name'] ?? '' ); ?>" class="widefat hr-self-drive-vehicle-name" placeholder="<?php esc_attr_e( 'Toyota Vios', 'hotel-rates' ); ?>" required></td>
                                                        <td><input type="number" name="vehicles[<?php echo esc_attr( $index ); ?>][rate]" value="<?php echo esc_attr( $vehicle['rate'] ?? '' ); ?>" class="widefat hr-self-drive-vehicle-rate" step="0.01" min="0" placeholder="0.00" required></td>
                                                        <td><input type="number" name="vehicles[<?php echo esc_attr( $index ); ?>][stock]" value="<?php echo esc_attr( $vehicle['stock'] ?? 1 ); ?>" class="widefat hr-self-drive-vehicle-stock" step="1" min="1" required></td>
                                                        <td><input type='text' name='vehicles[<?php echo esc_attr( $index ); ?>][seaters]' value='<?php echo esc_attr( $vehicle['seaters'] ?? '5' ); ?>' class='widefat hr-self-drive-vehicle-seaters' pattern='[0-9]+(-[0-9]+)?' placeholder='<?php esc_attr_e( '5 or 7-8', 'hotel-rates' ); ?>' required></td>
                                                        <td><button type="button" class="button-link-delete hr-self-drive-vehicle-remove"><?php esc_html_e( 'Remove', 'hotel-rates' ); ?></button></td>
                                                </tr>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                                <p><button type="button" class="button hr-self-drive-vehicle-add"><?php esc_html_e( 'Add Vehicle', 'hotel-rates' ); ?></button></p>
                        </div>

                        <div class="hr-reg-form-row">
                                <div class="hr-reg-field">
                                        <label for="hr-self-drive-start-times"><?php esc_html_e( 'Pick-up/Delivery and Return Time Options', 'hotel-rates' ); ?></label>
                                        <textarea id="hr-self-drive-start-times" name="start_times" class="large-text" rows="5" placeholder="<?php esc_attr_e( 'One option per line', 'hotel-rates' ); ?>"><?php echo esc_textarea( implode( "\n", $start_times ) ); ?></textarea>
                                </div>
                        </div>

                        <p class="submit">
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Self-Drive Rental', 'hotel-rates' ); ?></button>
                                <?php if ( $is_edit ) : ?>
                                        <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=hotel-rates-self-drive' ) ); ?>"><?php esc_html_e( 'Cancel Edit', 'hotel-rates' ); ?></a>
                                <?php endif; ?>
                        </p>
                </form>
        </div>

        <div class="hr-rate-section">
                <h2><?php esc_html_e( 'Saved Self-Drive Rentals', 'hotel-rates' ); ?></h2>

                <?php if ( ! empty( $self_drive_rentals ) ) : ?>
                <div class="hr-table-wrap">
                        <table class="wp-list-table widefat fixed striped hr-rate-table">
                                <thead>
                                        <tr>
                                                <th><?php esc_html_e( 'Rental', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Vehicles', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Delivery Fee', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Credit Card Fee', 'hotel-rates' ); ?></th>
                                                <th class="hr-actions-col"><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
                                        </tr>
                                </thead>
                                <tbody>
                                        <?php foreach ( $self_drive_rentals as $rental ) : ?>
                                        <?php
                                        $rental_vehicles = json_decode( $rental->vehicles ?? '[]', true );
                                        $rental_vehicles = is_array( $rental_vehicles ) ? $rental_vehicles : array();
                                        $saved_credit_card_fee_percent = isset( $rental->credit_card_fee_percent ) && null !== $rental->credit_card_fee_percent ? (float) $rental->credit_card_fee_percent : 5.0;
                                        $vehicle_labels = array();

                                        foreach ( $rental_vehicles as $vehicle ) {
                                                $vehicle_labels[] = sprintf(
                                                        '%1$s - %2$s/day x %3$d',
                                                        $vehicle['name'] ?? '',
                                                        function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( (float) ( $vehicle['rate'] ?? 0 ) ) ) : number_format( (float) ( $vehicle['rate'] ?? 0 ), 2 ),
                                                        (int) ( $vehicle['stock'] ?? 0 )
                                                );
                                        }
                                        ?>
                                        <tr>
                                                <td><strong><?php echo esc_html( $rental->rental_name ); ?></strong></td>
                                                <td><?php echo esc_html( implode( ', ', $vehicle_labels ) ); ?></td>
                                                <td><?php echo esc_html( function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( (float) $rental->delivery_fee ) ) : number_format( (float) $rental->delivery_fee, 2 ) ); ?></td>
                                                <td><?php echo esc_html( sprintf( __( '+%s%% Credit Card Fee', 'hotel-rates' ), rtrim( rtrim( number_format( $saved_credit_card_fee_percent, 2, '.', '' ), '0' ), '.' ) ) ); ?></td>
                                                <td class="hr-actions-col">
                                                        <a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'hotel-rates-self-drive', 'edit_self_drive_rental' => (int) $rental->id ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'hotel-rates' ); ?></a>
                                                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                                                                <input type="hidden" name="action" value="hr_delete_self_drive_rental">
                                                                <input type="hidden" name="id" value="<?php echo esc_attr( $rental->id ); ?>">
                                                                <?php wp_nonce_field( 'hr_delete_self_drive_rental' ); ?>
                                                                <button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this self-drive rental?', 'hotel-rates' ); ?>')"><?php esc_html_e( 'Del', 'hotel-rates' ); ?></button>
                                                        </form>
                                                </td>
                                        </tr>
                                        <?php endforeach; ?>
                                </tbody>
                        </table>
                </div>
                <?php else : ?>
                        <p class="hr-no-rates"><?php esc_html_e( 'No self-drive rentals added yet.', 'hotel-rates' ); ?></p>
                <?php endif; ?>
        </div>
</div>
