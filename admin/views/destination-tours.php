<?php if ( ! defined( 'ABSPATH' ) ) exit;

$is_edit = ! empty( $edit_destination_tour );
$destinations = $is_edit ? json_decode( $edit_destination_tour->destinations ?? '[]', true ) : array();
$destinations = Hotel_Rates_DB::normalize_destination_tour_destinations( is_array( $destinations ) ? $destinations : array() );
$pickup_addons = $is_edit ? json_decode( $edit_destination_tour->pickup_addons ?? '[]', true ) : array();
$pickup_addons = Hotel_Rates_DB::normalize_port_transfer_addons( is_array( $pickup_addons ) ? $pickup_addons : array() );
$pickup_times = $is_edit ? json_decode( $edit_destination_tour->pickup_times ?? '[]', true ) : array();
$pickup_times = Hotel_Rates_DB::normalize_motorbike_text_lines( is_array( $pickup_times ) ? $pickup_times : array() );
$credit_card_fee_percent = $is_edit && null !== $edit_destination_tour->credit_card_fee_percent ? (float) $edit_destination_tour->credit_card_fee_percent : 5.0;

if ( empty( $destinations ) ) {
        $destinations = array(
                array( 'label' => 'Twin Lakes', 'duration' => '8 Hours', 'rate' => 4700 ),
        );
}

if ( empty( $pickup_addons ) ) {
        $pickup_addons = array(
                array( 'label' => 'Bacong Resorts', 'rate' => 1000 ),
                array( 'label' => 'Dauin Resorts', 'rate' => 1200 ),
                array( 'label' => 'Zamboanguita Resorts', 'rate' => 2000 ),
        );
}

if ( empty( $pickup_times ) ) {
        $pickup_times = array( '07:00AM', '08:00AM', '09:00AM' );
}
?>
<div class="wrap hr-wrap">
        <h1><?php esc_html_e( 'Destination Tours', 'hotel-rates' ); ?></h1>

        <?php if ( isset( $_GET['saved'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Destination tour saved successfully.', 'hotel-rates' ); ?></p></div>
        <?php endif; ?>
        <?php if ( isset( $_GET['deleted'] ) ) : ?>
                <div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Destination tour deleted.', 'hotel-rates' ); ?></p></div>
        <?php endif; ?>

        <div class="hr-rate-section">
                <h2><?php echo $is_edit ? esc_html__( 'Edit Destination Tour', 'hotel-rates' ) : esc_html__( 'Add Destination Tour', 'hotel-rates' ); ?></h2>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-reg-form hr-destination-tour-admin-form">
                        <input type="hidden" name="action" value="hr_save_destination_tour">
                        <input type="hidden" name="id" value="<?php echo esc_attr( $is_edit ? (int) $edit_destination_tour->id : 0 ); ?>">
                        <?php wp_nonce_field( 'hr_save_destination_tour' ); ?>

                        <div class="hr-reg-form-col">
                                <div class="hr-reg-field">
                                        <label for="hr-destination-tour-name"><?php esc_html_e( 'Service Name', 'hotel-rates' ); ?></label>
                                        <input type="text" id="hr-destination-tour-name" name="tour_name" class="hr-reg-input" value="<?php echo esc_attr( $is_edit ? $edit_destination_tour->tour_name : 'Destination Day Tour: Service Rentals' ); ?>" required>
                                </div>
                                <div class="hr-reg-field hr-reg-field--sm">
                                        <label for="hr-destination-tour-credit-card-fee"><?php esc_html_e( 'Credit Card Fee (%)', 'hotel-rates' ); ?></label>
                                        <input type="number" id="hr-destination-tour-credit-card-fee" name="credit_card_fee_percent" class="hr-reg-input" value="<?php echo esc_attr( $credit_card_fee_percent ); ?>" step="0.01" min="0" placeholder="5">
                                        <p class="description"><?php esc_html_e( 'Added to checkout/order totals as a percentage of the Destination Tour subtotal.', 'hotel-rates' ); ?></p>
                                </div>
                        </div>

                        <div class="hr-destination-tour-admin-destinations">
                                <h3><?php esc_html_e( 'Tourist Destinations', 'hotel-rates' ); ?></h3>
                                <table class="widefat fixed striped hr-destination-tour-destination-table">
                                        <thead>
                                                <tr>
                                                        <th><?php esc_html_e( 'Tourist Destination', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Duration', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Rate', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php foreach ( $destinations as $index => $destination ) : ?>
                                                        <tr class="hr-destination-tour-destination-row">
                                                                <td><input type="text" name="destinations[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $destination['label'] ?? '' ); ?>" class="widefat hr-destination-tour-destination-label" required></td>
                                                                <td><input type="text" name="destinations[<?php echo esc_attr( $index ); ?>][duration]" value="<?php echo esc_attr( $destination['duration'] ?? '' ); ?>" class="widefat hr-destination-tour-destination-duration" placeholder="<?php esc_attr_e( '8 Hours', 'hotel-rates' ); ?>"></td>
                                                                <td><input type="number" name="destinations[<?php echo esc_attr( $index ); ?>][rate]" value="<?php echo esc_attr( $destination['rate'] ?? '' ); ?>" class="widefat hr-destination-tour-destination-rate" step="0.01" min="0" required></td>
                                                                <td><button type="button" class="button-link-delete hr-destination-tour-destination-remove"><?php esc_html_e( 'Remove', 'hotel-rates' ); ?></button></td>
                                                        </tr>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                                <p><button type="button" class="button hr-destination-tour-destination-add"><?php esc_html_e( 'Add Destination', 'hotel-rates' ); ?></button></p>
                        </div>

                        <div class="hr-reg-form-row">
                                <div class="hr-reg-field">
                                        <label for="hr-destination-tour-pickup-times"><?php esc_html_e( 'Pick-up Time Options', 'hotel-rates' ); ?></label>
                                        <textarea id="hr-destination-tour-pickup-times" name="pickup_times" class="large-text" rows="5" placeholder="<?php esc_attr_e( 'One option per line', 'hotel-rates' ); ?>"><?php echo esc_textarea( implode( "\n", $pickup_times ) ); ?></textarea>
                                </div>
                        </div>

                        <div class="hr-destination-tour-admin-addons">
                                <h3><?php esc_html_e( 'Pick-up Outside Dumaguete City Add-ons', 'hotel-rates' ); ?></h3>
                                <table class="widefat fixed striped hr-destination-tour-addon-table">
                                        <thead>
                                                <tr>
                                                        <th><?php esc_html_e( 'Location Label', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Rate', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php foreach ( $pickup_addons as $index => $addon ) : ?>
                                                        <tr class="hr-destination-tour-addon-row">
                                                                <td><input type="text" name="pickup_addons[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $addon['label'] ?? '' ); ?>" class="widefat hr-destination-tour-addon-label" placeholder="<?php esc_attr_e( 'Bacong Resorts', 'hotel-rates' ); ?>"></td>
                                                                <td><input type="number" name="pickup_addons[<?php echo esc_attr( $index ); ?>][rate]" value="<?php echo esc_attr( $addon['rate'] ?? '' ); ?>" class="widefat hr-destination-tour-addon-rate" step="0.01" min="0"></td>
                                                                <td><button type="button" class="button-link-delete hr-destination-tour-addon-remove"><?php esc_html_e( 'Remove', 'hotel-rates' ); ?></button></td>
                                                        </tr>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                                <p><button type="button" class="button hr-destination-tour-addon-add"><?php esc_html_e( 'Add Pick-up Add-on', 'hotel-rates' ); ?></button></p>
                        </div>

                        <p class="submit">
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Destination Tour', 'hotel-rates' ); ?></button>
                                <?php if ( $is_edit ) : ?>
                                        <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=hotel-rates-destination-tours' ) ); ?>"><?php esc_html_e( 'Cancel Edit', 'hotel-rates' ); ?></a>
                                <?php endif; ?>
                        </p>
                </form>
        </div>

        <div class="hr-rate-section">
                <h2><?php esc_html_e( 'Saved Destination Tours', 'hotel-rates' ); ?></h2>

                <?php if ( ! empty( $destination_tours ) ) : ?>
                <div class="hr-table-wrap">
                        <table class="wp-list-table widefat fixed striped hr-rate-table">
                                <thead>
                                        <tr>
                                                <th><?php esc_html_e( 'Tour', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Destinations', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Add-ons / Fee', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Times', 'hotel-rates' ); ?></th>
                                                <th class="hr-actions-col"><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
                                        </tr>
                                </thead>
                                <tbody>
                                        <?php foreach ( $destination_tours as $tour ) : ?>
                                        <?php
                                        $saved_destinations = Hotel_Rates_DB::normalize_destination_tour_destinations( json_decode( $tour->destinations ?? '[]', true ) ?: array() );
                                        $saved_addons = Hotel_Rates_DB::normalize_port_transfer_addons( json_decode( $tour->pickup_addons ?? '[]', true ) ?: array() );
                                        $saved_times = Hotel_Rates_DB::normalize_motorbike_text_lines( json_decode( $tour->pickup_times ?? '[]', true ) ?: array() );
                                        ?>
                                        <tr>
                                                <td><strong><?php echo esc_html( $tour->tour_name ); ?></strong></td>
                                                <td><?php echo esc_html( sprintf( _n( '%d destination', '%d destinations', count( $saved_destinations ), 'hotel-rates' ), count( $saved_destinations ) ) ); ?></td>
                                                <td>
                                                        <?php echo esc_html( sprintf( __( '+%s%% Credit Card Fee', 'hotel-rates' ), rtrim( rtrim( number_format( (float) $tour->credit_card_fee_percent, 2, '.', '' ), '0' ), '.' ) ) ); ?>
                                                        <?php if ( ! empty( $saved_addons ) ) : ?>
                                                                <br><span class="description"><?php echo esc_html( implode( ', ', wp_list_pluck( $saved_addons, 'label' ) ) ); ?></span>
                                                        <?php endif; ?>
                                                </td>
                                                <td><?php echo esc_html( implode( ', ', $saved_times ) ); ?></td>
                                                <td class="hr-actions-col">
                                                        <a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'hotel-rates-destination-tours', 'edit_destination_tour' => (int) $tour->id ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'hotel-rates' ); ?></a>
                                                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                                                                <input type="hidden" name="action" value="hr_delete_destination_tour">
                                                                <input type="hidden" name="id" value="<?php echo esc_attr( $tour->id ); ?>">
                                                                <?php wp_nonce_field( 'hr_delete_destination_tour' ); ?>
                                                                <button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this destination tour?', 'hotel-rates' ); ?>')"><?php esc_html_e( 'Del', 'hotel-rates' ); ?></button>
                                                        </form>
                                                </td>
                                        </tr>
                                        <?php endforeach; ?>
                                </tbody>
                        </table>
                </div>
                <?php else : ?>
                        <p class="hr-no-rates"><?php esc_html_e( 'No destination tours added yet.', 'hotel-rates' ); ?></p>
                <?php endif; ?>
        </div>
</div>
