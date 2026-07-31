<?php if ( ! defined( 'ABSPATH' ) ) { exit; }

$is_edit = ! empty( $edit_pickup_dropoff_service );
$default_locations = array(
        'Dumaguete',
        'San Jose',
        'Amlan',
        'Tanjay City',
        'Bais City (Canibol Wharf)',
        'Mabinay',
        'Manjuyod',
        'Bindoy',
        'Ayungon',
        'Tayasan',
        'Jimalalud',
        'La Libertad',
        'Guihulngan',
        'Canlaon City',
        'Bacolod',
        'San Carlos',
        'Cebu',
        'Toledo',
        'Bogo',
        'Balamban',
        'Lapu-Lapu',
        'Mactan',
        'Minglanilla',
        'Moalboal',
        'Oslob',
);
$default_pickup_times = array();

for ( $hour = 0; $hour < 24; $hour++ ) {
        foreach ( array( 0, 30 ) as $minute ) {
                $default_pickup_times[] = sprintf( '%02d:%02d', $hour, $minute );
        }
}

$locations = $is_edit ? json_decode( $edit_pickup_dropoff_service->locations ?? '[]', true ) : $default_locations;
$locations = Hotel_Rates_DB::normalize_pickup_dropoff_locations( $locations );
$locations = ! empty( $locations ) ? $locations : $default_locations;

$route_rates = $is_edit ? json_decode( $edit_pickup_dropoff_service->route_rates ?? '[]', true ) : array();
$route_rates = Hotel_Rates_DB::normalize_pickup_dropoff_route_rates( is_array( $route_rates ) ? $route_rates : array(), $locations );
$pickup_times = $is_edit ? json_decode( $edit_pickup_dropoff_service->pickup_times ?? '[]', true ) : $default_pickup_times;
$pickup_times = Hotel_Rates_DB::normalize_motorbike_text_lines( $pickup_times );
$pickup_times = ! empty( $pickup_times ) ? $pickup_times : $default_pickup_times;
$addons = $is_edit ? json_decode( $edit_pickup_dropoff_service->addons ?? '[]', true ) : array( array( 'label' => 'Midnight Surcharge', 'rate' => 500 ) );
$addons = Hotel_Rates_DB::normalize_port_transfer_addons( is_array( $addons ) ? $addons : array() );
$addons = ! empty( $addons ) ? $addons : array( array( 'label' => 'Midnight Surcharge', 'rate' => 500 ) );
$pickup_dropoff_pair_key = static function ( $from, $to ) {
        $pair = array( (string) $from, (string) $to );

        natcasesort( $pair );
        $pair = array_values( $pair );

        return strtolower( $pair[0] ) . "\0" . strtolower( $pair[1] );
};
$rate_lookup = array();

foreach ( $route_rates as $route_rate ) {
        $from = $route_rate['from'] ?? '';
        $to = $route_rate['to'] ?? '';

        if ( '' === $from || '' === $to ) {
                continue;
        }

        $rate_lookup[ $pickup_dropoff_pair_key( $from, $to ) ] = $route_rate['rate'] ?? '';
}
?>
<div class="wrap hr-wrap">
        <h1><?php esc_html_e( 'Pick-up & Drop-off Services', 'hotel-rates' ); ?></h1>

        <?php if ( isset( $_GET['saved'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Pick-up & Drop-off service saved successfully.', 'hotel-rates' ); ?></p></div>
        <?php endif; ?>
        <?php if ( isset( $_GET['deleted'] ) ) : ?>
                <div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Pick-up & Drop-off service deleted.', 'hotel-rates' ); ?></p></div>
        <?php endif; ?>

        <div class="hr-rate-section">
                <h2><?php echo $is_edit ? esc_html__( 'Edit Pick-up & Drop-off Service', 'hotel-rates' ) : esc_html__( 'Add Pick-up & Drop-off Service', 'hotel-rates' ); ?></h2>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-reg-form hr-pickup-dropoff-admin-form">
                        <input type="hidden" name="action" value="hr_save_pickup_dropoff_service">
                        <input type="hidden" name="id" value="<?php echo esc_attr( $is_edit ? (int) $edit_pickup_dropoff_service->id : 0 ); ?>">
                        <?php wp_nonce_field( 'hr_save_pickup_dropoff_service' ); ?>

                        <div class="hr-reg-form-col">
                                <div class="hr-reg-field">
                                        <label for="hr-pickup-dropoff-service-name"><?php esc_html_e( 'Service Name', 'hotel-rates' ); ?></label>
                                        <input type="text" id="hr-pickup-dropoff-service-name" name="service_name" class="hr-reg-input" value="<?php echo esc_attr( $is_edit ? $edit_pickup_dropoff_service->service_name : 'Pick-up and Drop-off Services' ); ?>" placeholder="<?php esc_attr_e( 'e.g., Dumaguete Pick-up and Drop-off Services', 'hotel-rates' ); ?>" required>
                                </div>
                        </div>

                        <div class="hr-reg-form-row">
                                <div class="hr-reg-field">
                                        <label for="hr-pickup-dropoff-locations"><?php esc_html_e( 'From / Drop-off Location Options', 'hotel-rates' ); ?></label>
                                        <textarea id="hr-pickup-dropoff-locations" name="locations" class="large-text hr-pickup-dropoff-locations" rows="10" placeholder="<?php esc_attr_e( 'One location per line', 'hotel-rates' ); ?>" required><?php echo esc_textarea( implode( "\n", $locations ) ); ?></textarea>
                                        <p class="description"><?php esc_html_e( 'This one list powers both the From and Drop-off To dropdowns.', 'hotel-rates' ); ?></p>
                                </div>
                        </div>

                        <div class="hr-pickup-dropoff-admin-routes">
                                <h3><?php esc_html_e( 'Route Rate Matrix', 'hotel-rates' ); ?></h3>
                                <p class="description"><?php esc_html_e( 'Set one rate for each location combination. The same rate is used after guests switch From and Drop-off To.', 'hotel-rates' ); ?></p>
                                <table class="widefat fixed striped hr-pickup-dropoff-rate-matrix-table">
                                        <thead>
                                                <tr>
                                                        <th><?php esc_html_e( 'From', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Drop-off To', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Rate', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php $rate_index = 0; ?>
                                                <?php foreach ( $locations as $from_index => $from_location ) : ?>
                                                        <?php foreach ( $locations as $to_index => $to_location ) : ?>
                                                                <?php if ( $to_index <= $from_index ) { continue; } ?>
                                                                <?php $lookup_key = $pickup_dropoff_pair_key( $from_location, $to_location ); ?>
                                                                <tr class="hr-pickup-dropoff-rate-row" data-from="<?php echo esc_attr( $from_location ); ?>" data-to="<?php echo esc_attr( $to_location ); ?>">
                                                                        <td>
                                                                                <strong><?php echo esc_html( $from_location ); ?></strong>
                                                                                <input type="hidden" name="route_rates[<?php echo esc_attr( $rate_index ); ?>][from]" value="<?php echo esc_attr( $from_location ); ?>" class="hr-pickup-dropoff-rate-from">
                                                                        </td>
                                                                        <td>
                                                                                <strong><?php echo esc_html( $to_location ); ?></strong>
                                                                                <input type="hidden" name="route_rates[<?php echo esc_attr( $rate_index ); ?>][to]" value="<?php echo esc_attr( $to_location ); ?>" class="hr-pickup-dropoff-rate-to">
                                                                        </td>
                                                                        <td><input type="number" name="route_rates[<?php echo esc_attr( $rate_index ); ?>][rate]" value="<?php echo esc_attr( $rate_lookup[ $lookup_key ] ?? '' ); ?>" class="widefat hr-pickup-dropoff-rate-value" step="0.01" min="0" placeholder="0.00" required></td>
                                                                </tr>
                                                                <?php $rate_index++; ?>
                                                        <?php endforeach; ?>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                                <p><button type="button" class="button hr-pickup-dropoff-rate-matrix-refresh"><?php esc_html_e( 'Refresh Rate Matrix', 'hotel-rates' ); ?></button></p>
                        </div>

                        <div class="hr-reg-form-row">
                                <div class="hr-reg-field">
                                        <label for="hr-pickup-dropoff-times"><?php esc_html_e( 'Pick-up Time Options', 'hotel-rates' ); ?></label>
                                        <textarea id="hr-pickup-dropoff-times" name="pickup_times" class="large-text" rows="8" placeholder="<?php esc_attr_e( 'One option per line', 'hotel-rates' ); ?>"><?php echo esc_textarea( implode( "\n", $pickup_times ) ); ?></textarea>
                                </div>
                        </div>

                        <div class="hr-pickup-dropoff-admin-addons">
                                <h3><?php esc_html_e( 'Add-ons', 'hotel-rates' ); ?></h3>
                                <table class="widefat fixed striped hr-pickup-dropoff-addon-table">
                                        <thead>
                                                <tr>
                                                        <th><?php esc_html_e( 'Add-on Label', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Rate', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php foreach ( $addons as $index => $addon ) : ?>
                                                        <tr class="hr-pickup-dropoff-addon-row">
                                                                <td><input type="text" name="addons[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $addon['label'] ?? '' ); ?>" class="widefat hr-pickup-dropoff-addon-label" placeholder="<?php esc_attr_e( 'Midnight Surcharge', 'hotel-rates' ); ?>"></td>
                                                                <td><input type="number" name="addons[<?php echo esc_attr( $index ); ?>][rate]" value="<?php echo esc_attr( $addon['rate'] ?? '' ); ?>" class="widefat hr-pickup-dropoff-addon-rate" step="0.01" min="0" placeholder="0.00"></td>
                                                                <td><button type="button" class="button-link-delete hr-pickup-dropoff-addon-remove"><?php esc_html_e( 'Remove', 'hotel-rates' ); ?></button></td>
                                                        </tr>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                                <p><button type="button" class="button hr-pickup-dropoff-addon-add"><?php esc_html_e( 'Add Add-on', 'hotel-rates' ); ?></button></p>
                        </div>

                        <p class="submit">
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Pick-up & Drop-off Service', 'hotel-rates' ); ?></button>
                                <?php if ( $is_edit ) : ?>
                                        <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=hotel-rates-pickup-dropoff' ) ); ?>"><?php esc_html_e( 'Cancel Edit', 'hotel-rates' ); ?></a>
                                <?php endif; ?>
                        </p>
                </form>
        </div>

        <div class="hr-rate-section">
                <h2><?php esc_html_e( 'Saved Pick-up & Drop-off Services', 'hotel-rates' ); ?></h2>

                <?php if ( ! empty( $pickup_dropoff_services ) ) : ?>
                        <div class="hr-table-wrap">
                                <table class="wp-list-table widefat fixed striped hr-rate-table">
                                        <thead>
                                                <tr>
                                                        <th><?php esc_html_e( 'Service', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Locations', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Rates / Add-ons', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Times', 'hotel-rates' ); ?></th>
                                                        <th class="hr-actions-col"><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php foreach ( $pickup_dropoff_services as $service ) : ?>
                                                        <?php
                                                        $saved_locations = Hotel_Rates_DB::normalize_pickup_dropoff_locations( json_decode( $service->locations ?? '[]', true ) ?: array() );
                                                        $saved_routes = Hotel_Rates_DB::normalize_pickup_dropoff_route_rates( json_decode( $service->route_rates ?? '[]', true ) ?: array(), $saved_locations );
                                                        $saved_times = Hotel_Rates_DB::normalize_motorbike_text_lines( json_decode( $service->pickup_times ?? '[]', true ) ?: array() );
                                                        $saved_addons = Hotel_Rates_DB::normalize_port_transfer_addons( json_decode( $service->addons ?? '[]', true ) ?: array() );
                                                        $addon_labels = array_map( static function ( $addon ) {
                                                                return sprintf(
                                                                        '%1$s (%2$s)',
                                                                        $addon['label'] ?? '',
                                                                        function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( (float) ( $addon['rate'] ?? 0 ) ) ) : number_format( (float) ( $addon['rate'] ?? 0 ), 2 )
                                                                );
                                                        }, $saved_addons );
                                                        ?>
                                                        <tr>
                                                                <td><strong><?php echo esc_html( $service->service_name ); ?></strong></td>
                                                                <td><?php echo esc_html( sprintf( _n( '%d location', '%d locations', count( $saved_locations ), 'hotel-rates' ), count( $saved_locations ) ) ); ?></td>
                                                                <td>
                                                                        <?php echo esc_html( sprintf( _n( '%d route rate', '%d route rates', count( $saved_routes ), 'hotel-rates' ), count( $saved_routes ) ) ); ?>
                                                                        <?php if ( ! empty( $addon_labels ) ) : ?>
                                                                                <br><span class="description"><?php echo esc_html( implode( ', ', $addon_labels ) ); ?></span>
                                                                        <?php endif; ?>
                                                                </td>
                                                                <td><?php echo esc_html( implode( ', ', array_slice( $saved_times, 0, 8 ) ) ); ?><?php echo count( $saved_times ) > 8 ? esc_html__( ', ...', 'hotel-rates' ) : ''; ?></td>
                                                                <td class="hr-actions-col">
                                                                        <a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'hotel-rates-pickup-dropoff', 'edit_pickup_dropoff_service' => (int) $service->id ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'hotel-rates' ); ?></a>
                                                                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                                                                                <input type="hidden" name="action" value="hr_delete_pickup_dropoff_service">
                                                                                <input type="hidden" name="id" value="<?php echo esc_attr( $service->id ); ?>">
                                                                                <?php wp_nonce_field( 'hr_delete_pickup_dropoff_service' ); ?>
                                                                                <button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this Pick-up & Drop-off service?', 'hotel-rates' ); ?>')"><?php esc_html_e( 'Del', 'hotel-rates' ); ?></button>
                                                                        </form>
                                                                </td>
                                                        </tr>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                        </div>
                <?php else : ?>
                        <p class="hr-no-rates"><?php esc_html_e( 'No Pick-up & Drop-off services added yet.', 'hotel-rates' ); ?></p>
                <?php endif; ?>
        </div>
</div>
