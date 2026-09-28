<?php if ( ! defined( 'ABSPATH' ) ) exit;

$is_edit = ! empty( $edit_motorbike_rental );
$models = $is_edit ? json_decode( $edit_motorbike_rental->models ?? '[]', true ) : array();
$models = is_array( $models ) ? $models : array();
$start_times = $is_edit ? json_decode( $edit_motorbike_rental->start_times ?? '[]', true ) : array();
$start_times = is_array( $start_times ) ? $start_times : array( '8:00 AM', '9:00 AM', '10:00 AM' );
$day_options = $is_edit ? json_decode( $edit_motorbike_rental->day_options ?? '[]', true ) : array();
$day_options = is_array( $day_options ) ? $day_options : array( 1, 2, 3, 4, 5, 6, 7 );

if ( empty( $models ) ) {
        $models[] = array( 'name' => '', 'engine' => '', 'rate' => '', 'stock' => 1 );
}
?>
<div class="wrap hr-wrap">
        <h1><?php esc_html_e( 'Motorbike Rentals', 'hotel-rates' ); ?></h1>

        <?php if ( isset( $_GET['saved'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Motorbike rental saved successfully.', 'hotel-rates' ); ?></p></div>
        <?php endif; ?>
        <?php if ( isset( $_GET['deleted'] ) ) : ?>
                <div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Motorbike rental deleted.', 'hotel-rates' ); ?></p></div>
        <?php endif; ?>

        <div class="hr-rate-section">
                <h2><?php echo $is_edit ? esc_html__( 'Edit Motorbike Rental', 'hotel-rates' ) : esc_html__( 'Add Motorbike Rental', 'hotel-rates' ); ?></h2>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-reg-form hr-motorbike-admin-form">
                        <input type="hidden" name="action" value="hr_save_motorbike_rental">
                        <input type="hidden" name="id" value="<?php echo esc_attr( $is_edit ? (int) $edit_motorbike_rental->id : 0 ); ?>">
                        <?php wp_nonce_field( 'hr_save_motorbike_rental' ); ?>

                        <div class="hr-reg-form-col">
                                <div class="hr-reg-field">
                                        <label for="hr-motorbike-rental-name"><?php esc_html_e( 'Rental Name', 'hotel-rates' ); ?></label>
                                        <input type="text" id="hr-motorbike-rental-name" name="rental_name" class="hr-reg-input" value="<?php echo esc_attr( $is_edit ? $edit_motorbike_rental->rental_name : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., Dumaguete Motorbike Rentals', 'hotel-rates' ); ?>" required>
                                </div>

                                <div class="hr-reg-field hr-reg-field--sm">
                                        <label for="hr-motorbike-delivery-fee"><?php esc_html_e( 'Delivery Fee', 'hotel-rates' ); ?></label>
                                        <input type="number" id="hr-motorbike-delivery-fee" name="delivery_fee" class="hr-reg-input" value="<?php echo esc_attr( $is_edit ? $edit_motorbike_rental->delivery_fee : '500.00' ); ?>" step="0.01" min="0" placeholder="500.00">
                                </div>
                        </div>

                        <div class="hr-motorbike-admin-models">
                                <h3><?php esc_html_e( 'Motorbike Models', 'hotel-rates' ); ?></h3>
                                <table class="widefat fixed striped hr-motorbike-model-table">
                                        <thead>
                                                <tr>
                                                        <th><?php esc_html_e( 'Model', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Engine', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Daily Rate', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Available Qty', 'hotel-rates' ); ?></th>
                                                        <th><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php foreach ( $models as $index => $model ) : ?>
                                                <tr class="hr-motorbike-model-row">
                                                        <td><input type="text" name="models[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $model['name'] ?? '' ); ?>" class="widefat hr-motorbike-model-name" placeholder="<?php esc_attr_e( 'Yamaha Fazzio', 'hotel-rates' ); ?>" required></td>
                                                        <td><input type="text" name="models[<?php echo esc_attr( $index ); ?>][engine]" value="<?php echo esc_attr( $model['engine'] ?? '' ); ?>" class="widefat hr-motorbike-model-engine" placeholder="<?php esc_attr_e( 'e.g., 125cc', 'hotel-rates' ); ?>" required></td>
                                                        <td><input type="number" name="models[<?php echo esc_attr( $index ); ?>][rate]" value="<?php echo esc_attr( $model['rate'] ?? '' ); ?>" class="widefat hr-motorbike-model-rate" step="0.01" min="0" placeholder="0.00" required></td>
                                                        <td><input type="number" name="models[<?php echo esc_attr( $index ); ?>][stock]" value="<?php echo esc_attr( $model['stock'] ?? 1 ); ?>" class="widefat hr-motorbike-model-stock" step="1" min="1" required></td>
                                                        <td><button type="button" class="button-link-delete hr-motorbike-model-remove"><?php esc_html_e( 'Remove', 'hotel-rates' ); ?></button></td>
                                                </tr>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                                <p><button type="button" class="button hr-motorbike-model-add"><?php esc_html_e( 'Add Motorbike Model', 'hotel-rates' ); ?></button></p>
                        </div>

                        <div class="hr-reg-form-row">
                                <div class="hr-reg-field">
                                        <label for="hr-motorbike-start-times"><?php esc_html_e( 'Pick-up/Delivery Time Options', 'hotel-rates' ); ?></label>
                                        <textarea id="hr-motorbike-start-times" name="start_times" class="large-text" rows="5" placeholder="<?php esc_attr_e( 'One option per line', 'hotel-rates' ); ?>"><?php echo esc_textarea( implode( "\n", $start_times ) ); ?></textarea>
                                </div>
                                <div class="hr-reg-field">
                                        <label for="hr-motorbike-day-options"><?php esc_html_e( 'No. of Days Options', 'hotel-rates' ); ?></label>
                                        <textarea id="hr-motorbike-day-options" name="day_options" class="large-text" rows="5" placeholder="<?php esc_attr_e( 'One number per line', 'hotel-rates' ); ?>"><?php echo esc_textarea( implode( "\n", $day_options ) ); ?></textarea>
                                </div>
                        </div>

                        <p class="submit">
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Motorbike Rental', 'hotel-rates' ); ?></button>
                                <?php if ( $is_edit ) : ?>
                                        <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=hotel-rates-motorbikes' ) ); ?>"><?php esc_html_e( 'Cancel Edit', 'hotel-rates' ); ?></a>
                                <?php endif; ?>
                        </p>
                </form>
        </div>

        <div class="hr-rate-section">
                <h2><?php esc_html_e( 'Saved Motorbike Rentals', 'hotel-rates' ); ?></h2>

                <?php if ( ! empty( $motorbike_rentals ) ) : ?>
                <div class="hr-table-wrap">
                        <table class="wp-list-table widefat fixed striped hr-rate-table">
                                <thead>
                                        <tr>
                                                <th><?php esc_html_e( 'Rental', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Models', 'hotel-rates' ); ?></th>
                                                <th><?php esc_html_e( 'Delivery Fee', 'hotel-rates' ); ?></th>
                                                <th class="hr-actions-col"><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
                                        </tr>
                                </thead>
                                <tbody>
                                        <?php foreach ( $motorbike_rentals as $rental ) : ?>
                                        <?php
                                        $rental_models = json_decode( $rental->models ?? '[]', true );
                                        $rental_models = is_array( $rental_models ) ? $rental_models : array();
                                        $model_labels = array();

                                        foreach ( $rental_models as $model ) {
                                                $model_labels[] = sprintf(
                                                        '%1$s - %2$s/day x %3$d',
                                                        $model['name'] ?? '',
                                                        function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( (float) ( $model['rate'] ?? 0 ) ) ) : number_format( (float) ( $model['rate'] ?? 0 ), 2 ),
                                                        (int) ( $model['stock'] ?? 0 )
                                                );
                                        }
                                        ?>
                                        <tr>
                                                <td><strong><?php echo esc_html( $rental->rental_name ); ?></strong></td>
                                                <td><?php echo esc_html( implode( ', ', $model_labels ) ); ?></td>
                                                <td><?php echo esc_html( function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( (float) $rental->delivery_fee ) ) : number_format( (float) $rental->delivery_fee, 2 ) ); ?></td>
                                                <td class="hr-actions-col">
                                                        <a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'hotel-rates-motorbikes', 'edit_motorbike_rental' => (int) $rental->id ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'hotel-rates' ); ?></a>
                                                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                                                                <input type="hidden" name="action" value="hr_delete_motorbike_rental">
                                                                <input type="hidden" name="id" value="<?php echo esc_attr( $rental->id ); ?>">
                                                                <?php wp_nonce_field( 'hr_delete_motorbike_rental' ); ?>
                                                                <button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this motorbike rental?', 'hotel-rates' ); ?>')"><?php esc_html_e( 'Del', 'hotel-rates' ); ?></button>
                                                        </form>
                                                </td>
                                        </tr>
                                        <?php endforeach; ?>
                                </tbody>
                        </table>
                </div>
                <?php else : ?>
                        <p class="hr-no-rates"><?php esc_html_e( 'No motorbike rentals added yet.', 'hotel-rates' ); ?></p>
                <?php endif; ?>
        </div>
</div>
