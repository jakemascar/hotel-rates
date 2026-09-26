<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * $regions, $region_id, $places, $place_id, $tour_type, $tour_rates
 * provided by Hotel_Rates_Admin::page_tour_rates()
 */
$tour_types        = Hotel_Rates_DB::TOUR_TYPES;
$max_pax           = 'Day Tour' === $tour_type ? Hotel_Rates_DB::DAY_TOUR_MAX_PAX : Hotel_Rates_DB::MAX_PAX;
$default_pax_shown = 8;
$places_lookup     = array();

foreach ( Hotel_Rates_DB::get_places() as $lookup_place ) {
	$places_lookup[ (int) $lookup_place->id ] = $lookup_place->name;
}
?>
<div class="wrap hr-wrap">
	<h1><?php esc_html_e( 'Tour Rates', 'hotel-rates' ); ?></h1>

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Tour rate saved successfully.', 'hotel-rates' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Tour rate deleted.', 'hotel-rates' ); ?></p></div>
	<?php endif; ?>

	<?php if ( empty( $regions ) ) : ?>
		<div class="notice notice-warning"><p><?php printf( esc_html__( 'No regions found. %sRegister a place first.%s', 'hotel-rates' ), '<a href="' . esc_url( admin_url( 'admin.php?page=hotel-rates-register' ) ) . '">', '</a>' ); ?></p></div>
	<?php else : ?>

	<div class="hr-filters">
		<div class="hr-filter-group">
			<label><?php esc_html_e( 'Choose your Region', 'hotel-rates' ); ?></label>
			<select id="hr-region" data-current="<?php echo esc_attr( $region_id ); ?>">
				<?php foreach ( $regions as $r ) : ?>
					<option value="<?php echo esc_attr( $r->id ); ?>" <?php selected( $r->id, $region_id ); ?>><?php echo esc_html( $r->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="hr-filter-group">
			<label><?php esc_html_e( 'Choose your Place', 'hotel-rates' ); ?></label>
			<select id="hr-place" data-current="<?php echo esc_attr( $place_id ); ?>">
				<?php foreach ( $places as $p ) : ?>
					<option value="<?php echo esc_attr( $p->id ); ?>" <?php selected( $p->id, $place_id ); ?>><?php echo esc_html( $p->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="hr-filter-group">
			<label><?php esc_html_e( 'Type', 'hotel-rates' ); ?></label>
			<select id="hr-tour-type" data-current="<?php echo esc_attr( $tour_type ); ?>">
				<option value=""><?php esc_html_e( 'Select type…', 'hotel-rates' ); ?></option>
				<?php foreach ( $tour_types as $tt ) : ?>
					<option value="<?php echo esc_attr( $tt ); ?>" <?php selected( $tt, $tour_type ); ?>><?php echo esc_html( $tt ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="hr-filter-group hr-filter-btn">
			<button id="hr-load-tour-rates" class="button button-primary"><?php esc_html_e( 'Load Rates', 'hotel-rates' ); ?></button>
		</div>
	</div>

	<?php if ( $place_id && $tour_type ) : ?>

	<div class="hr-breadcrumb">
		<span><?php esc_html_e( 'Tours', 'hotel-rates' ); ?></span>
		<span class="hr-arrow">&rarr;</span>
		<strong><?php echo esc_html( $tour_type ); ?></strong>
	</div>

	<div class="hr-rate-section">
		<div class="hr-table-wrap">
			<?php if ( ! empty( $tour_rates ) ) : ?>
				<?php if ( 'Package Deal' === $tour_type ) : ?>
				<table class="wp-list-table widefat fixed hr-rate-table hr-tour-rate-display" data-type="<?php echo esc_attr( $tour_type ); ?>">
					<thead>
						<tr>
							<th class="hr-col-meal"><?php esc_html_e( 'Package Deal', 'hotel-rates' ); ?></th>
							<th><?php esc_html_e( 'Days', 'hotel-rates' ); ?></th>
							<th><?php esc_html_e( 'Places', 'hotel-rates' ); ?></th>
							<th class="hr-actions-col"><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $tour_rates as $package_deal ) : ?>
							<?php
							$package_place_ids = json_decode( $package_deal->place_ids ?? '[]', true );
							$package_place_ids = is_array( $package_place_ids ) ? array_map( 'intval', $package_place_ids ) : array();
							$package_place_names = array();
							foreach ( $package_place_ids as $package_place_id ) {
								if ( isset( $places_lookup[ $package_place_id ] ) ) {
									$package_place_names[] = $places_lookup[ $package_place_id ];
								}
							}
							?>
						<tr>
							<td class="hr-col-meal"><strong><?php echo esc_html( $package_deal->package_name ); ?></strong></td>
							<td><?php echo esc_html( sprintf( _n( '%d day', '%d days', (int) $package_deal->number_of_days, 'hotel-rates' ), (int) $package_deal->number_of_days ) ); ?></td>
							<td><?php echo esc_html( implode( ', ', $package_place_names ) ); ?></td>
							<td class="hr-actions-col">
								<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'hotel-rates-register', 'tab' => 'package-deals', 'edit_package_deal' => (int) $package_deal->id ), admin_url( 'admin.php' ) ) ); ?>">
									<?php esc_html_e( 'Edit', 'hotel-rates' ); ?>
								</a>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
									<input type="hidden" name="action" value="hr_delete_package_deal">
									<input type="hidden" name="id" value="<?php echo esc_attr( $package_deal->id ); ?>">
									<input type="hidden" name="from_tour_rates" value="1">
									<input type="hidden" name="region_id" value="<?php echo esc_attr( $region_id ); ?>">
									<input type="hidden" name="place_id" value="<?php echo esc_attr( $place_id ); ?>">
									<?php wp_nonce_field( 'hr_delete_package_deal' ); ?>
									<button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this package deal?', 'hotel-rates' ); ?>')">
										<?php esc_html_e( 'Del', 'hotel-rates' ); ?>
									</button>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php else : ?>
				<?php if ( 'Airport Transfer' === $tour_type ) : ?>
				<table class="wp-list-table widefat fixed hr-rate-table hr-tour-rate-display" data-type="<?php echo esc_attr( $tour_type ); ?>">
					<thead>
						<tr>
							<th class="hr-col-meal"><?php esc_html_e( 'Airport Transfer', 'hotel-rates' ); ?></th>
							<th><?php esc_html_e( 'From/To Places', 'hotel-rates' ); ?></th>
							<th class="hr-actions-col"><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $tour_rates as $tr ) : ?>
						<?php $transfer_routes = Hotel_Rates_DB::normalize_airport_transfer_routes( $tr->transfer_routes ?? '' ); ?>
						<tr>
							<td class="hr-col-meal"><strong><?php echo esc_html( $tr->tour_name ); ?></strong></td>
							<td>
								<?php
								$route_labels = array_map( static function ( $route ) {
									$label = $route['label'] ?? '';
									$rate  = isset( $route['rate'] ) ? (float) $route['rate'] : null;

									return null === $rate ? $label : $label . ' - &#8369;' . number_format( $rate, 2 );
								}, $transfer_routes );
								echo wp_kses_post( implode( ', ', array_filter( $route_labels ) ) );
								?>
							</td>
							<td class="hr-actions-col">
								<button class="button button-small hr-edit-tour-rate-btn"
									data-id="<?php echo esc_attr( $tr->id ); ?>"
									data-tour-name="<?php echo esc_attr( $tr->tour_name ); ?>"
									data-type="<?php echo esc_attr( $tr->type ); ?>"
									data-transfer-routes="<?php echo esc_attr( wp_json_encode( $transfer_routes ) ); ?>"
								><?php esc_html_e( 'Edit', 'hotel-rates' ); ?></button>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
									<input type="hidden" name="action"    value="hr_delete_tour_rate">
									<input type="hidden" name="id"        value="<?php echo esc_attr( $tr->id ); ?>">
									<input type="hidden" name="region_id" value="<?php echo esc_attr( $region_id ); ?>">
									<input type="hidden" name="place_id"  value="<?php echo esc_attr( $place_id ); ?>">
									<input type="hidden" name="type"      value="<?php echo esc_attr( $tour_type ); ?>">
									<?php wp_nonce_field( 'hr_delete_tour_rate' ); ?>
									<button type="submit" class="button button-small button-link-delete"
										onclick="return confirm('<?php esc_attr_e( 'Delete this airport transfer rate?', 'hotel-rates' ); ?>')"
									><?php esc_html_e( 'Del', 'hotel-rates' ); ?></button>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php else : ?>
				<table class="wp-list-table widefat fixed hr-rate-table hr-tour-rate-display" data-type="<?php echo esc_attr( $tour_type ); ?>">
					<thead>
						<tr>
							<th class="hr-col-meal"><?php esc_html_e( 'Tours', 'hotel-rates' ); ?></th>
							<?php for ( $p = 1; $p <= $max_pax; $p++ ) : ?>
							<th class="hr-col-pax<?php echo $p > $default_pax_shown ? ' hr-pax-hidden' : ''; ?>" data-pax="<?php echo $p; ?>"><?php echo $p; ?> pax</th>
							<?php endfor; ?>
							<th class="hr-col-pax-ctrl">
								<button type="button" class="hr-pax-add" title="<?php esc_attr_e( 'Add pax column', 'hotel-rates' ); ?>">+</button>
								<button type="button" class="hr-pax-rem" title="<?php esc_attr_e( 'Remove pax column', 'hotel-rates' ); ?>">&minus;</button>
							</th>
							<th class="hr-actions-col"><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $tour_rates as $tr ) : ?>
						<?php $tour_addons = Hotel_Rates_DB::normalize_tour_optional_addons( $tr->optional_addons ?? '', $tr->type ); ?>
						<?php $tour_booking_fields = Hotel_Rates_DB::normalize_tour_booking_fields( $tr->booking_fields ?? '' ); ?>
						<?php $tour_children_rates = Hotel_Rates_DB::normalize_tour_children_rates( $tr->children_rates ?? '' ); ?>
						<?php $tour_package_details = Hotel_Rates_DB::normalize_tour_package_details( $tr->package_details ?? '' ); ?>
						<tr>
							<td class="hr-col-meal"><strong><?php echo esc_html( $tr->tour_name ); ?></strong></td>
							<?php for ( $p = 1; $p <= $max_pax; $p++ ) : $col = 'pax_' . $p; ?>
							<td class="hr-col-pax<?php echo $p > $default_pax_shown ? ' hr-pax-hidden' : ''; ?>" data-pax="<?php echo $p; ?>">
								<?php echo $tr->$col !== null ? '&#8369;' . number_format( (float) $tr->$col, 2 ) : '&mdash;'; ?>
							</td>
							<?php endfor; ?>
							<td class="hr-col-pax-ctrl"></td>
							<td class="hr-actions-col">
								<button class="button button-small hr-edit-tour-rate-btn"
									data-id="<?php echo esc_attr( $tr->id ); ?>"
									data-tour-name="<?php echo esc_attr( $tr->tour_name ); ?>"
									data-type="<?php echo esc_attr( $tr->type ); ?>"
									data-addons="<?php echo esc_attr( wp_json_encode( $tour_addons ) ); ?>"
									data-booking-fields="<?php echo esc_attr( wp_json_encode( $tour_booking_fields ) ); ?>"
									data-children-rates="<?php echo esc_attr( wp_json_encode( $tour_children_rates ) ); ?>"
									data-package-details="<?php echo esc_attr( wp_json_encode( $tour_package_details ) ); ?>"
									<?php for ( $p = 1; $p <= $max_pax; $p++ ) : $col = 'pax_' . $p; ?>
									data-pax-<?php echo $p; ?>="<?php echo esc_attr( $tr->$col ?? '' ); ?>"
									<?php endfor; ?>
								><?php esc_html_e( 'Edit', 'hotel-rates' ); ?></button>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
									<input type="hidden" name="action"    value="hr_delete_tour_rate">
									<input type="hidden" name="id"        value="<?php echo esc_attr( $tr->id ); ?>">
									<input type="hidden" name="region_id" value="<?php echo esc_attr( $region_id ); ?>">
									<input type="hidden" name="place_id"  value="<?php echo esc_attr( $place_id ); ?>">
									<input type="hidden" name="type"      value="<?php echo esc_attr( $tour_type ); ?>">
									<?php wp_nonce_field( 'hr_delete_tour_rate' ); ?>
									<button type="submit" class="button button-small button-link-delete"
										onclick="return confirm('<?php esc_attr_e( 'Delete this tour rate?', 'hotel-rates' ); ?>')"
									><?php esc_html_e( 'Del', 'hotel-rates' ); ?></button>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
				<?php endif; ?>
			<?php else : ?>
				<p class="hr-no-rates"><?php echo 'Package Deal' === $tour_type ? esc_html__( 'No package deals found for this place yet.', 'hotel-rates' ) : esc_html__( 'No tour rates added yet.', 'hotel-rates' ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( 'Package Deal' === $tour_type ) : ?>
		<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'hotel-rates-register', 'tab' => 'package-deals' ), admin_url( 'admin.php' ) ) ); ?>">
			+ <?php esc_html_e( 'Add Package Deal', 'hotel-rates' ); ?>
		</a>
		<?php else : ?>
		<button class="button hr-add-tour-rate-btn"
			data-place-id="<?php echo esc_attr( $place_id ); ?>"
			data-region-id="<?php echo esc_attr( $region_id ); ?>"
			data-type="<?php echo esc_attr( $tour_type ); ?>"
		>+ <?php printf( esc_html__( 'Add %s Rate', 'hotel-rates' ), esc_html( $tour_type ) ); ?></button>
		<?php endif; ?>
	</div>

	<?php if ( 'Package Deal' !== $tour_type ) : ?>
	<div id="hr-tour-rate-modal" class="hr-modal" style="display:none;">
		<div class="hr-modal-inner">
			<button class="hr-modal-close" type="button">&#10005;</button>
			<h2 id="hr-tour-modal-title"><?php esc_html_e( 'Add Tour Rate', 'hotel-rates' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action"    value="hr_save_tour_rate">
				<input type="hidden" name="id"        id="hr-tour-rate-id"    value="">
				<input type="hidden" name="region_id" id="hr-tour-region-id"  value="<?php echo esc_attr( $region_id ); ?>">
				<input type="hidden" name="place_id"  id="hr-tour-place-id"   value="<?php echo esc_attr( $place_id ); ?>">
				<input type="hidden" name="type"      id="hr-tour-type-input" value="<?php echo esc_attr( $tour_type ); ?>">
				<input type="hidden" name="transfer_routes_json" id="hr-airport-transfer-routes-json" value="">
				<?php wp_nonce_field( 'hr_save_tour_rate' ); ?>

				<div class="hr-modal-field">
					<label for="hr-tour-name-input" id="hr-tour-name-label"><?php esc_html_e( 'Tour Name', 'hotel-rates' ); ?></label>
					<input type="text" id="hr-tour-name-input" name="tour_name" class="hr-reg-input" placeholder="<?php esc_attr_e( 'e.g., Dumaguete City Tour', 'hotel-rates' ); ?>" required>
				</div>

				<div class="hr-tour-booking-fields">
					<h3><?php esc_html_e( 'Booking form fields', 'hotel-rates' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Enable or rename the extra fields shown on this Day Tour booking form.', 'hotel-rates' ); ?></p>
					<div class="hr-tour-booking-field-setting" data-field-key="hotel_flight_no">
						<label class="hr-tour-booking-field-toggle">
							<input type="hidden" name="tour_booking_fields[hotel_flight_no][enabled]" value="0">
							<input type="checkbox" name="tour_booking_fields[hotel_flight_no][enabled]" value="1" class="hr-tour-booking-field-enabled" checked>
							<?php esc_html_e( 'Show field', 'hotel-rates' ); ?>
						</label>
						<input type="text" name="tour_booking_fields[hotel_flight_no][label]" class="hr-tour-booking-field-label" value="<?php esc_attr_e( 'Hotel / Flight No.', 'hotel-rates' ); ?>" placeholder="<?php esc_attr_e( 'Hotel / Flight No.', 'hotel-rates' ); ?>">
					</div>
					<div class="hr-tour-booking-field-setting" data-field-key="pickup_time">
						<label class="hr-tour-booking-field-toggle">
							<input type="hidden" name="tour_booking_fields[pickup_time][enabled]" value="0">
							<input type="checkbox" name="tour_booking_fields[pickup_time][enabled]" value="1" class="hr-tour-booking-field-enabled" checked>
							<?php esc_html_e( 'Show field', 'hotel-rates' ); ?>
						</label>
						<input type="text" name="tour_booking_fields[pickup_time][label]" class="hr-tour-booking-field-label" value="<?php esc_attr_e( 'Pick-up Time', 'hotel-rates' ); ?>" placeholder="<?php esc_attr_e( 'Pick-up Time', 'hotel-rates' ); ?>">
					</div>
				</div>

				<div class="hr-day-tour-pax-range" hidden>
					<h3><?php esc_html_e( 'Passenger Range', 'hotel-rates' ); ?></h3>
					<p class="description hr-tour-pax-range-help"><?php esc_html_e( 'Choose the minimum and maximum paying guests supported by this tour.', 'hotel-rates' ); ?></p>
					<div class="hr-day-tour-pax-range-controls">
						<label>
							<span><?php esc_html_e( 'Minimum Pax', 'hotel-rates' ); ?></span>
							<button type="button" class="button hr-day-tour-pax-step" data-boundary="minimum" data-direction="-1">-</button>
							<input type="number" id="hr-day-tour-min-pax" value="1" min="1" max="30" readonly>
							<button type="button" class="button hr-day-tour-pax-step" data-boundary="minimum" data-direction="1">+</button>
						</label>
						<label>
							<span><?php esc_html_e( 'Maximum Pax', 'hotel-rates' ); ?></span>
							<button type="button" class="button hr-day-tour-pax-step" data-boundary="maximum" data-direction="-1">-</button>
							<input type="number" id="hr-day-tour-max-pax" value="12" min="1" max="30" readonly>
							<button type="button" class="button hr-day-tour-pax-step" data-boundary="maximum" data-direction="1">+</button>
						</label>
					</div>
				</div>

				<div class="hr-modal-pax-grid">
					<?php for ( $p = 1; $p <= $max_pax; $p++ ) : ?>
					<div class="hr-modal-pax-cell" data-pax="<?php echo esc_attr( $p ); ?>">
						<label for="hr-tour-pax-<?php echo $p; ?>"><?php printf( esc_html__( '%d pax (₱)', 'hotel-rates' ), $p ); ?></label>
						<input type="number" step="0.01" min="0" name="pax_<?php echo $p; ?>" id="hr-tour-pax-<?php echo $p; ?>" class="hr-pax-input" placeholder="0.00">
					</div>
					<?php endfor; ?>
				</div>

				<div class="hr-tour-children-rates">
					<h3><?php esc_html_e( 'Children Guest Rates', 'hotel-rates' ); ?></h3>
					<p class="description hr-tour-children-rate-help"><?php esc_html_e( 'Enable Children guests for this tour.', 'hotel-rates' ); ?></p>
					<label class="hr-tour-children-rate-toggle">
						<input type="hidden" name="tour_children_rates[enabled]" value="0">
						<input type="checkbox" name="tour_children_rates[enabled]" value="1" id="hr-tour-children-rates-enabled">
						<?php esc_html_e( 'Show/include Children guests and rates', 'hotel-rates' ); ?>
					</label>
					<div class="hr-tour-child-label-field" style="display:none;">
						<label for="hr-tour-child-label"><?php esc_html_e( 'Children Label', 'hotel-rates' ); ?></label>
						<input type="text" name="tour_children_rates[label]" id="hr-tour-child-label" class="hr-reg-input" value="<?php esc_attr_e( 'Children', 'hotel-rates' ); ?>" placeholder="<?php esc_attr_e( 'Children', 'hotel-rates' ); ?>">
						<p class="description"><?php esc_html_e( 'Shown in the Day Tour and Joiner guest selector, checkout, and order details.', 'hotel-rates' ); ?></p>
					</div>
					<div class="hr-tour-child-discount-field" style="display:none;">
						<label for="hr-tour-child-discount-percent"><?php esc_html_e( 'Child Discount (%)', 'hotel-rates' ); ?></label>
						<input type="number" step="0.01" min="0" max="100" name="tour_children_rates[discount_percent]" id="hr-tour-child-discount-percent" class="hr-pax-input" placeholder="0">
						<p class="description"><?php esc_html_e( 'Each child uses the adult rate for the total adults plus children count, then this discount is applied.', 'hotel-rates' ); ?></p>
					</div>
					<div class="hr-modal-pax-grid hr-tour-children-pax-grid">
						<?php for ( $p = 1; $p <= $max_pax; $p++ ) : ?>
						<div class="hr-modal-pax-cell">
							<label for="hr-tour-children-pax-<?php echo $p; ?>"><?php printf( esc_html__( '%d child', 'hotel-rates' ), $p ); ?> (&#8369;)</label>
							<input type="number" step="0.01" min="0" name="tour_children_rates[pax_<?php echo $p; ?>]" id="hr-tour-children-pax-<?php echo $p; ?>" class="hr-pax-input hr-tour-children-pax-input" placeholder="0.00">
						</div>
						<?php endfor; ?>
					</div>
				</div>

				<div class="hr-tour-package-details">
					<h3><?php esc_html_e( 'Details of the Package', 'hotel-rates' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Add one or more information sections for the tour-rate table. Enter one sub-content item per line.', 'hotel-rates' ); ?></p>
					<div class="hr-tour-package-detail-groups"></div>
					<p class="hr-tour-package-detail-actions">
						<button type="button" class="button hr-tour-package-detail-add">
							<?php esc_html_e( 'Add Details Section', 'hotel-rates' ); ?>
						</button>
					</p>
				</div>

				<div class="hr-airport-transfer-routes" style="display:none;">
					<h3><?php esc_html_e( 'From/To Places', 'hotel-rates' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Add one or more airport transfer places, each with one rate charged per booking.', 'hotel-rates' ); ?></p>
					<div class="hr-airport-transfer-route-list"></div>
					<p>
						<button type="button" class="button hr-airport-transfer-route-add">
							<?php esc_html_e( 'Add From/To Place', 'hotel-rates' ); ?>
						</button>
					</p>
				</div>

				<div class="hr-tour-addons">
					<h3><?php esc_html_e( 'Optional add-ons', 'hotel-rates' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Add the dropdown groups shown on this Day Tour or Joiner booking form. Each group can have its own label/rate selections.', 'hotel-rates' ); ?></p>
					<div class="hr-tour-addon-groups"></div>
					<div class="hr-tour-addon-group-actions">
						<button type="button" class="button hr-tour-addon-group-add">
							<?php esc_html_e( 'Add Optional Add-on Group', 'hotel-rates' ); ?>
						</button>
					</div>
				</div>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Rate', 'hotel-rates' ); ?></button>
					<button type="button" class="button hr-modal-close"><?php esc_html_e( 'Cancel', 'hotel-rates' ); ?></button>
				</p>
			</form>
		</div>
	</div>
	<div id="hr-tour-overlay" class="hr-overlay" style="display:none;"></div>
	<?php endif; ?>

	<?php else : ?>
		<div class="notice notice-info"><p><?php esc_html_e( 'Select a Region, Place, and Type above, then click Load Rates.', 'hotel-rates' ); ?></p></div>
	<?php endif; ?>

	<?php endif; ?>
</div>
