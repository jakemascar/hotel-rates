<?php if ( ! defined( 'ABSPATH' ) ) exit;

$is_edit = ! empty( $edit_boat_rental );
$rates = $is_edit ? json_decode( $edit_boat_rental->rates ?? '[]', true ) : array();
$rates = is_array( $rates ) ? $rates : array();
$boarding_times = $is_edit ? json_decode( $edit_boat_rental->boarding_times ?? '[]', true ) : array();
$boarding_times = is_array( $boarding_times ) ? $boarding_times : array( '06:30 AM', '07:00 AM', '07:30 AM', '08:00 AM', '08:30 AM', '09:00 AM', '09:30 AM', '10:00 AM' );
$credit_card_fee_percent = $is_edit && isset( $edit_boat_rental->credit_card_fee_percent ) && null !== $edit_boat_rental->credit_card_fee_percent ? (float) $edit_boat_rental->credit_card_fee_percent : 5.0;
$upgrade_boat_fee_label = $is_edit && ! empty( $edit_boat_rental->upgrade_boat_fee_label ) ? (string) $edit_boat_rental->upgrade_boat_fee_label : __( 'Upgrade to Bigger Boat Fee', 'hotel-rates' );
$upgrade_boat_fee_amount = $is_edit && isset( $edit_boat_rental->upgrade_boat_fee_amount ) && null !== $edit_boat_rental->upgrade_boat_fee_amount ? (float) $edit_boat_rental->upgrade_boat_fee_amount : '';

if ( empty( $rates ) ) {
        $rates = array(
                array( 'label' => '1-4 pax', 'minPax' => 1, 'maxPax' => 4, 'rate' => '4500' ),
                array( 'label' => '5-6 pax', 'minPax' => 5, 'maxPax' => 6, 'rate' => '5000' ),
                array( 'label' => '7-8 pax', 'minPax' => 7, 'maxPax' => 8, 'rate' => '6000' ),
                array( 'label' => '9-10 pax', 'minPax' => 9, 'maxPax' => 10, 'rate' => '6500' ),
                array( 'label' => '11-12 pax', 'minPax' => 11, 'maxPax' => 12, 'rate' => '7500' ),
                array( 'label' => '13-14 pax', 'minPax' => 13, 'maxPax' => 14, 'rate' => '8000' ),
                array( 'label' => '15-16 pax', 'minPax' => 15, 'maxPax' => 16, 'rate' => '8500' ),
                array( 'label' => '17-20 pax', 'minPax' => 17, 'maxPax' => 20, 'rate' => '9500' ),
                array( 'label' => '21-24 pax', 'minPax' => 21, 'maxPax' => 24, 'rate' => '10500' ),
                array( 'label' => '25-30 pax', 'minPax' => 25, 'maxPax' => 30, 'rate' => '11500' ),
        );
}
?>
<div class="wrap hr-wrap">
        <h1><?php esc_html_e( 'Boat Rentals', 'hotel-rates' ); ?></h1>

        <?php if ( isset( $_GET['saved'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Boat rental saved successfully.', 'hotel-rates' ); ?></p></div>
        <?php endif; ?>
        <?php if ( isset( $_GET['deleted'] ) ) : ?>
                <div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Boat rental deleted.', 'hotel-rates' ); ?></p></div>
        <?php endif; ?>

        <div class="hr-rate-section">
                <h2><?php echo $is_edit ? esc_html__( 'Edit Boat Rental', 'hotel-rates' ) : esc_html__( 'Add Boat Rental', 'hotel-rates' ); ?></h2>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-reg-form hr-boat-admin-form">
                        <input type="hidden" name="action" value="hr_save_boat_rental">
                        <input type="hidden" name="id" value="<?php echo esc_attr( $is_edit ? (int) $edit_boat_rental->id : 0 ); ?>">
                        <?php wp_nonce_field( 'hr_save_boat_rental' ); ?>

                        <div class="hr-reg-form-col">
                                <div class="hr-reg-field">
                                        <label for="hr-boat-rental-name"><?php esc_html_e( 'Rental Name', 'hotel-rates' ); ?></label>
                                        <input type="text" id="hr-boat-rental-name" name="rental_name" class="hr-reg-input" value="<?php echo esc_attr( $is_edit ? $edit_boat_rental->rental_name : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., Dumaguete Boat Rental', 'hotel-rates' ); ?>" required>
                                </div>

                                <div class="hr-reg-field hr-reg-field--sm">
                                        <label for="hr-boat-credit-card-fee"><?php esc_html_e( 'Credit Card Fee (%)', 'hotel-rates' ); ?></label>
                                        <input type="number" id="hr-boat-credit-card-fee" name="credit_card_fee_percent" class="hr-reg-input" value="<?php echo esc_attr( $credit_card_fee_percent ); ?>" step="0.01" min="0" placeholder="5">
                                        <p class="description"><?php esc_html_e( 'Added to checkout/order totals as a percentage of the Boat Rental subtotal.', 'hotel-rates' ); ?></p>
                                </div>

                                <div class="hr-reg-field">
                                        <label for="hr-boat-upgrade-fee-label"><?php esc_html_e( 'Upgrade to Bigger Boat Fee Label', 'hotel-rates' ); ?></label>
                                        <input type="text" id="hr-boat-upgrade-fee-label" name="upgrade_boat_fee_label" class="hr-reg-input" value="<?php echo esc_attr( $upgrade_boat_fee_label ); ?>" placeholder="<?php esc_attr_e( 'Upgrade to Bigger Boat Fee', 'hotel-rates' ); ?>">
                                        <p class="description"><?php esc_html_e( 'Custom label to show later in the Boat Rental booking form.', 'hotel-rates' ); ?></p>
                                </div>

                                <div class="hr-reg-field hr-reg-field--sm">
                                        <label for="hr-boat-upgrade-fee-amount"><?php esc_html_e( 'Upgrade to Bigger Boat Fee Amount', 'hotel-rates' ); ?></label>
                                        <input type="number" id="hr-boat-upgrade-fee-amount" name="upgrade_boat_fee_amount" class="hr-reg-input" value="<?php echo esc_attr( $upgrade_boat_fee_amount ); ?>" step="0.01" min="0" placeholder="0.00">
                                        <p class="description"><?php esc_html_e( 'Saved for the Boat Rental booking form update.', 'hotel-rates' ); ?></p>
                                </div>
                        </div>

                        <div class="hr-boat-admin-rates">
                                <h3><?php esc_html_e( 'Boat Rates and Capacity', 'hotel-rates' ); ?></h3>
                                <table class="widefat fixed striped hr-boat-rate-table">
                                        <thead>
                                                <tr>
                                                        <th><?php esc_html_e( 'Capacity Label', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Min Pax', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Max Pax', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Rate', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php foreach ( $rates as $index => $rate ) : ?>
                                                <?php
                                                $min_pax = $rate['min_pax'] ?? ( $rate['minPax'] ?? 1 );
                                                $max_pax = $rate['max_pax'] ?? ( $rate['maxPax'] ?? $min_pax );
                                                ?>
                                                <tr class="hr-boat-rate-row">
                                                        <td><input type="text" name="rates[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $rate['label'] ?? '' ); ?>" class="widefat hr-boat-rate-label" placeholder="<?php esc_attr_e( '1-4 pax', 'hotel-rates' ); ?>"></td>
                                                        <td><input type="number" name="rates[<?php echo esc_attr( $index ); ?>][min_pax]" value="<?php echo esc_attr( $min_pax ); ?>" class="widefat hr-boat-rate-min-pax" step="1" min="1" required></td>
                                                        <td><input type="number" name="rates[<?php echo esc_attr( $index ); ?>][max_pax]" value="<?php echo esc_attr( $max_pax ); ?>" class="widefat hr-boat-rate-max-pax" step="1" min="1" required></td>
                                                        <td><input type="number" name="rates[<?php echo esc_attr( $index ); ?>][rate]" value="<?php echo esc_attr( $rate['rate'] ?? '' ); ?>" class="widefat hr-boat-rate-amount" step="0.01" min="0" placeholder="0.00" required></td>
                                                        <td><button type="button" class="button-link-delete hr-boat-rate-remove"><?php esc_html_e( 'Remove', 'hotel-rates' ); ?></button></td>
                                                </tr>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                                <p><button type="button" class="button hr-boat-rate-add"><?php esc_html_e( 'Add Boat Rate', 'hotel-rates' ); ?></button></p>
                        </div>

                        <div class="hr-reg-form-row">
                                <div class="hr-reg-field">
                                        <label for="hr-boat-boarding-times"><?php esc_html_e( 'Boarding Time Options', 'hotel-rates' ); ?></label>
                                        <textarea id="hr-boat-boarding-times" name="boarding_times" class="large-text" rows="5" placeholder="<?php esc_attr_e( 'One option per line', 'hotel-rates' ); ?>"><?php echo esc_textarea( implode( "\n", $boarding_times ) ); ?></textarea>
                                </div>
                        </div>

                        <p class="submit">
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Boat Rental', 'hotel-rates' ); ?></button>
                                <?php if ( $is_edit ) : ?>
                                        <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=hotel-rates-boats' ) ); ?>"><?php esc_html_e( 'Cancel Edit', 'hotel-rates' ); ?></a>
                                <?php endif; ?>
                        </p>
                </form>
        </div>

        <div class="hr-rate-section">
                <h2><?php esc_html_e( 'Saved Boat Rentals', 'hotel-rates' ); ?></h2>

                <?php if ( ! empty( $boat_rentals ) ) : ?>
                <div class="hr-table-wrap">
                        <table class="wp-list-table widefat fixed striped hr-rate-table">
                                <thead>
                                        <tr>
                                                <th><?php esc_html_e( 'Rental', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Rates', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Credit Card Fee', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Upgrade Fee', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Boarding Times', 'hotel-rates' ); ?></th>
                                                <th class="hr-actions-col"><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
                                        </tr>
                                </thead>
                                <tbody>
                                        <?php foreach ( $boat_rentals as $rental ) : ?>
                                        <?php
                                        $rental_rates = json_decode( $rental->rates ?? '[]', true );
                                        $rental_rates = is_array( $rental_rates ) ? $rental_rates : array();
                                        $rental_times = json_decode( $rental->boarding_times ?? '[]', true );
                                        $rental_times = is_array( $rental_times ) ? $rental_times : array();
                                        $saved_credit_card_fee_percent = isset( $rental->credit_card_fee_percent ) && null !== $rental->credit_card_fee_percent ? (float) $rental->credit_card_fee_percent : 5.0;
                                        $saved_upgrade_fee_label = ! empty( $rental->upgrade_boat_fee_label ) ? (string) $rental->upgrade_boat_fee_label : __( 'Upgrade to Bigger Boat Fee', 'hotel-rates' );
                                        $saved_upgrade_fee_amount = isset( $rental->upgrade_boat_fee_amount ) && null !== $rental->upgrade_boat_fee_amount ? (float) $rental->upgrade_boat_fee_amount : null;
                                        $rate_labels = array();

                                        foreach ( $rental_rates as $rate ) {
                                                $rate_labels[] = sprintf(
                                                        '%1$s = %2$s',
                                                        $rate['label'] ?? '',
                                                        function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( (float) ( $rate['rate'] ?? 0 ) ) ) : number_format( (float) ( $rate['rate'] ?? 0 ), 2 )
                                                );
                                        }
                                        ?>
                                        <tr>
                                                <td><strong><?php echo esc_html( $rental->rental_name ); ?></strong></td>
                                                <td><?php echo esc_html( implode( ', ', $rate_labels ) ); ?></td>
                                                <td><?php echo esc_html( sprintf( __( '+%s%% Credit Card Fee', 'hotel-rates' ), rtrim( rtrim( number_format( $saved_credit_card_fee_percent, 2, '.', '' ), '0' ), '.' ) ) ); ?></td>
                                                <td>
                                                        <?php
                                                        if ( null === $saved_upgrade_fee_amount ) {
                                                                esc_html_e( 'Not set', 'hotel-rates' );
                                                        } else {
                                                                echo esc_html( sprintf(
                                                                        '%1$s: %2$s',
                                                                        $saved_upgrade_fee_label,
                                                                        function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $saved_upgrade_fee_amount ) ) : number_format( $saved_upgrade_fee_amount, 2 )
                                                                ) );
                                                        }
                                                        ?>
                                                </td>
                                                <td><?php echo esc_html( implode( ', ', $rental_times ) ); ?></td>
                                                <td class="hr-actions-col">
                                                        <a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'hotel-rates-boats', 'edit_boat_rental' => (int) $rental->id ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'hotel-rates' ); ?></a>
                                                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                                                                <input type="hidden" name="action" value="hr_delete_boat_rental">
                                                                <input type="hidden" name="id" value="<?php echo esc_attr( $rental->id ); ?>">
                                                                <?php wp_nonce_field( 'hr_delete_boat_rental' ); ?>
                                                                <button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this boat rental?', 'hotel-rates' ); ?>')"><?php esc_html_e( 'Del', 'hotel-rates' ); ?></button>
                                                        </form>
                                                </td>
                                        </tr>
                                        <?php endforeach; ?>
                                </tbody>
                        </table>
                </div>
                <?php else : ?>
                        <p class="hr-no-rates"><?php esc_html_e( 'No boat rentals added yet.', 'hotel-rates' ); ?></p>
                <?php endif; ?>
        </div>
</div>
