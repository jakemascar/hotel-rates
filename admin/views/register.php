<?php if ( ! defined( 'ABSPATH' ) ) exit;
// $tab, $regions, $places are provided by the controller.
$allowed_tabs = array( 'place', 'hotels', 'day-tours', 'joiners', 'airport-transfers', 'package-deals' );
if ( ! in_array( $tab, $allowed_tabs, true ) ) $tab = 'place';

$places_grouped  = Hotel_Rates_DB::get_places_grouped();
$hotels_grouped  = Hotel_Rates_DB::get_hotels_grouped();
$all_places_flat = $places;
$hotels_by_place = array();
$package_hotel_default_rates = array();
$activities_by_place = array();

foreach ( Hotel_Rates_DB::get_hotels() as $hotel_row ) {
	$place_key = (int) $hotel_row->place_id;
	if ( ! isset( $hotels_by_place[ $place_key ] ) ) {
		$hotels_by_place[ $place_key ] = array();
	}
	$hotels_by_place[ $place_key ][] = array(
		'id'   => (int) $hotel_row->id,
		'name' => (string) $hotel_row->name,
	);

	$hotel_default_rates = array();

	for ( $nights = 1; $nights <= Hotel_Rates_DB::MAX_NIGHTS; $nights++ ) {
		$rates = Hotel_Rates_DB::get_rates( (int) $hotel_row->id, $nights );
		$preferred_rate = null;
		$fallback_rate  = null;

		foreach ( $rates as $rate ) {
			if ( ! is_object( $rate ) ) {
				continue;
			}

			if ( null === $fallback_rate ) {
				$fallback_rate = $rate;
			}

			$meal_plan = isset( $rate->meal_plan ) ? strtoupper( trim( (string) $rate->meal_plan ) ) : '';

			if ( '' === $meal_plan || 'X' === $meal_plan ) {
				$preferred_rate = $rate;
				break;
			}
		}

		$preferred_rate = $preferred_rate ? $preferred_rate : $fallback_rate;
		$hotel_default_rates[ (string) $nights ] = $preferred_rate && isset( $preferred_rate->pax_1 ) && '' !== $preferred_rate->pax_1 && null !== $preferred_rate->pax_1
			? (float) $preferred_rate->pax_1
			: null;
	}

	$package_hotel_default_rates[ (string) (int) $hotel_row->id ] = $hotel_default_rates;
}

foreach ( $all_places_flat as $place_row ) {
	$place_id = (int) $place_row->id;

	foreach ( array( 'Day Tour', 'Joiner' ) as $tour_type ) {
		$tour_rows = Hotel_Rates_DB::get_tour_rates( $place_id, $tour_type );

		foreach ( $tour_rows as $tour_row ) {
			if ( empty( $activities_by_place[ $place_id ] ) ) {
				$activities_by_place[ $place_id ] = array();
			}

			$activities_by_place[ $place_id ][] = array(
				'id'   => (int) $tour_row->id,
				'name' => (string) $tour_row->tour_name,
				'type' => (string) $tour_type,
			);
		}
	}
}
?>
<div class="wrap hr-wrap">
	<h1><?php esc_html_e( 'Register Hotels, Places, &amp; Tours', 'hotel-rates' ); ?></h1>

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved successfully.', 'hotel-rates' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Deleted.', 'hotel-rates' ); ?></p></div>
	<?php endif; ?>

	<div class="hr-reg-box">

		<!-- ── Tab Navigation ─────────────────────────────────────── -->
		<div class="hr-reg-tabs">
			<?php
			$tabs = array(
				'place'     => 'Place',
				'hotels'    => 'Hotels',
				'day-tours' => 'Day Tours',
				'joiners'   => 'Joiners',
				'airport-transfers' => 'Airport Transfers',
				'package-deals' => 'Package Deals',
			);
			foreach ( $tabs as $key => $label ) :
				$active = ( $tab === $key );
				$url    = add_query_arg( array( 'page' => 'hotel-rates-register', 'tab' => $key ), admin_url( 'admin.php' ) );
			?>
			<a href="<?php echo esc_url( $url ); ?>" class="hr-reg-tab<?php echo $active ? ' hr-reg-tab--active' : ''; ?>">
				<span class="hr-tab-checkbox"><?php echo $active ? '✓' : ''; ?></span>
				<?php echo esc_html( $label ); ?>
			</a>
			<?php endforeach; ?>
		</div>

		<!-- ════════════════════════════════════════════════════════ -->
		<!-- Tab: Place -->
		<!-- ════════════════════════════════════════════════════════ -->
		<?php if ( $tab === 'place' ) : ?>

		<h2><?php esc_html_e( 'Register Place', 'hotel-rates' ); ?></h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-reg-form">
			<input type="hidden" name="action" value="hr_save_place">
			<?php wp_nonce_field( 'hr_save_place' ); ?>
			<div class="hr-reg-form-row">
				<div class="hr-reg-field">
					<label><?php esc_html_e( 'Enter Place Name', 'hotel-rates' ); ?></label>
					<input type="text" name="name" class="hr-reg-input" placeholder="<?php esc_attr_e( 'e.g., Boracay', 'hotel-rates' ); ?>" required>
				</div>
				<div class="hr-reg-field hr-reg-field--sm">
					<label><?php esc_html_e( 'LuzViMin', 'hotel-rates' ); ?></label>
					<select name="region_id" class="hr-reg-select" required>
						<option value=""><?php esc_html_e( '— Select —', 'hotel-rates' ); ?></option>
						<?php foreach ( $regions as $r ) : ?>
							<option value="<?php echo esc_attr( $r->id ); ?>"><?php echo esc_html( $r->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'hotel-rates' ); ?></button>
		</form>

		<?php if ( ! empty( $places_grouped ) ) : ?>
		<div class="hr-saved-section">
			<h3><?php esc_html_e( 'Saved Places:', 'hotel-rates' ); ?></h3>
			<div class="hr-grouped-list">
				<?php foreach ( $places_grouped as $region_name => $region_places ) : ?>
				<div class="hr-group-header"><?php echo esc_html( $region_name ); ?></div>
				<?php foreach ( $region_places as $pl ) : ?>
				<div class="hr-group-item">
					<span><?php echo esc_html( $pl->name ); ?></span>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-inline-delete">
						<input type="hidden" name="action" value="hr_delete_place">
						<input type="hidden" name="id" value="<?php echo esc_attr( $pl->id ); ?>">
						<?php wp_nonce_field( 'hr_delete_place' ); ?>
						<button type="submit" class="hr-delete-x" onclick="return confirm('<?php esc_attr_e( 'Delete this place and all associated data?', 'hotel-rates' ); ?>')">×</button>
					</form>
				</div>
				<?php endforeach; ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php else : ?>
			<p class="hr-empty-msg"><?php esc_html_e( 'No places registered yet.', 'hotel-rates' ); ?></p>
		<?php endif; ?>

		<!-- ════════════════════════════════════════════════════════ -->
		<!-- Tab: Hotels -->
		<!-- ════════════════════════════════════════════════════════ -->
		<?php elseif ( $tab === 'hotels' ) : ?>

		<h2><?php esc_html_e( 'Register Hotels', 'hotel-rates' ); ?></h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-reg-form">
			<input type="hidden" name="action" value="hr_save_hotel">
			<?php wp_nonce_field( 'hr_save_hotel' ); ?>
			<div class="hr-reg-form-col">
				<div class="hr-reg-field">
					<label><?php esc_html_e( 'Enter Hotel Name', 'hotel-rates' ); ?></label>
					<input type="text" name="name" class="hr-reg-input" placeholder="<?php esc_attr_e( 'e.g., Blue Horizon Hotel', 'hotel-rates' ); ?>" required>
				</div>
				<div class="hr-reg-field">
					<label><?php esc_html_e( 'LuzViMin', 'hotel-rates' ); ?></label>
					<select id="hotel-region-filter" class="hr-reg-select">
						<option value=""><?php esc_html_e( 'Filter by island group…', 'hotel-rates' ); ?></option>
						<?php foreach ( $regions as $r ) : ?>
							<option value="<?php echo esc_attr( $r->id ); ?>"><?php echo esc_html( $r->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="hr-reg-field">
					<label><?php esc_html_e( 'Dedicated Place', 'hotel-rates' ); ?></label>
					<select id="hotel-place" name="place_id" class="hr-reg-select" required>
						<option value=""><?php esc_html_e( 'Select a place…', 'hotel-rates' ); ?></option>
						<?php foreach ( $all_places_flat as $p ) : ?>
							<option value="<?php echo esc_attr( $p->id ); ?>" data-region="<?php echo esc_attr( $p->region_id ); ?>"><?php echo esc_html( $p->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'hotel-rates' ); ?></button>
		</form>

		<?php if ( ! empty( $hotels_grouped ) ) : ?>
		<div class="hr-saved-section">
			<h3><?php esc_html_e( 'Saved Hotels:', 'hotel-rates' ); ?></h3>
			<div class="hr-grouped-list">
				<?php foreach ( $hotels_grouped as $group_label => $group_hotels ) : ?>
				<div class="hr-group-header"><?php echo esc_html( $group_label ); ?></div>
				<?php foreach ( $group_hotels as $h ) : ?>
				<div class="hr-group-item">
					<span><?php echo esc_html( $h->name ); ?></span>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-inline-delete">
						<input type="hidden" name="action" value="hr_delete_hotel">
						<input type="hidden" name="id" value="<?php echo esc_attr( $h->id ); ?>">
						<?php wp_nonce_field( 'hr_delete_hotel' ); ?>
						<button type="submit" class="hr-delete-x" onclick="return confirm('<?php esc_attr_e( 'Delete this hotel and all its rates?', 'hotel-rates' ); ?>')">×</button>
					</form>
				</div>
				<?php endforeach; ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php else : ?>
			<p class="hr-empty-msg"><?php esc_html_e( 'No hotels registered yet.', 'hotel-rates' ); ?></p>
		<?php endif; ?>

		<!-- ════════════════════════════════════════════════════════ -->
		<!-- Tab: Day Tours -->
		<!-- ════════════════════════════════════════════════════════ -->
		<?php elseif ( $tab === 'day-tours' ) :
			$nested = Hotel_Rates_DB::get_tours_nested( 'Day Tour' );
		?>

		<h2><?php esc_html_e( 'Register Day Tours', 'hotel-rates' ); ?></h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-reg-form">
			<input type="hidden" name="action" value="hr_save_tour_reg">
			<input type="hidden" name="type" value="Day Tour">
			<?php wp_nonce_field( 'hr_save_tour_reg' ); ?>
			<div class="hr-reg-form-col">
				<div class="hr-reg-field">
					<label><?php esc_html_e( 'Enter Tour Name', 'hotel-rates' ); ?></label>
					<input type="text" name="tour_name" class="hr-reg-input" placeholder="<?php esc_attr_e( 'e.g., City Tour', 'hotel-rates' ); ?>" required>
				</div>
				<div class="hr-reg-field">
					<label><?php esc_html_e( 'Dedicated Place', 'hotel-rates' ); ?></label>
					<select name="place_id" class="hr-reg-select" required>
						<option value=""><?php esc_html_e( 'Select a place…', 'hotel-rates' ); ?></option>
						<?php foreach ( $all_places_flat as $p ) : ?>
							<option value="<?php echo esc_attr( $p->id ); ?>"><?php echo esc_html( $p->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'hotel-rates' ); ?></button>
		</form>

		<div class="hr-saved-section">
			<h3><?php esc_html_e( 'Saved Day Tours:', 'hotel-rates' ); ?></h3>
			<?php include __DIR__ . '/partials/tour-nested-accordion.php'; ?>
		</div>

		<!-- ════════════════════════════════════════════════════════ -->
		<!-- Tab: Joiners -->
		<!-- ════════════════════════════════════════════════════════ -->
		<?php elseif ( $tab === 'joiners' ) :
			$nested = Hotel_Rates_DB::get_tours_nested( 'Joiner' );
		?>

		<h2><?php esc_html_e( 'Register Joiners', 'hotel-rates' ); ?></h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-reg-form">
			<input type="hidden" name="action" value="hr_save_tour_reg">
			<input type="hidden" name="type" value="Joiner">
			<?php wp_nonce_field( 'hr_save_tour_reg' ); ?>
			<div class="hr-reg-form-col">
				<div class="hr-reg-field">
					<label><?php esc_html_e( 'Enter Tour Name', 'hotel-rates' ); ?></label>
					<input type="text" name="tour_name" class="hr-reg-input" placeholder="<?php esc_attr_e( 'e.g., Island Hopping Joiner', 'hotel-rates' ); ?>" required>
				</div>
				<div class="hr-reg-field">
					<label><?php esc_html_e( 'Dedicated Place', 'hotel-rates' ); ?></label>
					<select name="place_id" class="hr-reg-select" required>
						<option value=""><?php esc_html_e( 'Select a place…', 'hotel-rates' ); ?></option>
						<?php foreach ( $all_places_flat as $p ) : ?>
							<option value="<?php echo esc_attr( $p->id ); ?>"><?php echo esc_html( $p->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'hotel-rates' ); ?></button>
		</form>

		<div class="hr-saved-section">
			<h3><?php esc_html_e( 'Saved Joiners:', 'hotel-rates' ); ?></h3>
			<?php include __DIR__ . '/partials/tour-nested-accordion.php'; ?>
		</div>

		<!-- Tab: Airport Transfers -->
		<?php elseif ( $tab === 'airport-transfers' ) :
			$nested = Hotel_Rates_DB::get_tours_nested( 'Airport Transfer' );
		?>

		<h2><?php esc_html_e( 'Register Airport Transfers', 'hotel-rates' ); ?></h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-reg-form">
			<input type="hidden" name="action" value="hr_save_tour_reg">
			<input type="hidden" name="type" value="Airport Transfer">
			<?php wp_nonce_field( 'hr_save_tour_reg' ); ?>
			<div class="hr-reg-form-col">
				<div class="hr-reg-field">
					<label><?php esc_html_e( 'Enter Airport Transfer Name', 'hotel-rates' ); ?></label>
					<input type="text" name="tour_name" class="hr-reg-input" placeholder="<?php esc_attr_e( 'e.g., Panglao International Airport', 'hotel-rates' ); ?>" required>
				</div>
				<div class="hr-reg-field">
					<label><?php esc_html_e( 'Dedicated Place', 'hotel-rates' ); ?></label>
					<select name="place_id" class="hr-reg-select" required>
						<option value=""><?php esc_html_e( 'Select a place...', 'hotel-rates' ); ?></option>
						<?php foreach ( $all_places_flat as $p ) : ?>
							<option value="<?php echo esc_attr( $p->id ); ?>"><?php echo esc_html( $p->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<p class="description"><?php esc_html_e( 'After saving, open Tour Rates and choose Airport Transfer to add the From/To Places and per-booking rates.', 'hotel-rates' ); ?></p>
			</div>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'hotel-rates' ); ?></button>
		</form>

		<div class="hr-saved-section">
			<h3><?php esc_html_e( 'Saved Airport Transfers:', 'hotel-rates' ); ?></h3>
			<?php include __DIR__ . '/partials/tour-nested-accordion.php'; ?>
		</div>

		<?php elseif ( $tab === 'package-deals' ) : ?>
		<?php
		$saved_package_deals      = Hotel_Rates_DB::get_package_deals();
		$editing_package          = ! empty( $edit_package_deal );
		$editing_package_id       = $editing_package ? (int) $edit_package_deal->id : 0;
		$editing_package_name     = $editing_package ? (string) $edit_package_deal->package_name : '';
		$editing_airport_transfer_fee = $editing_package && isset( $edit_package_deal->airport_transfer_fee ) ? $edit_package_deal->airport_transfer_fee : '';
		$editing_seaport_transfer_fee = $editing_package && isset( $edit_package_deal->seaport_transfer_fee ) ? $edit_package_deal->seaport_transfer_fee : '';
		$editing_number_of_days   = $editing_package ? (int) $edit_package_deal->number_of_days : '';
		$editing_place_ids        = $editing_package ? json_decode( $edit_package_deal->place_ids ?? '[]', true ) : array();
		$editing_hotel_ids        = $editing_package ? json_decode( $edit_package_deal->hotel_ids ?? '{}', true ) : array();
		$editing_default_hotel_ids = $editing_package ? json_decode( $edit_package_deal->default_hotel_ids ?? '{}', true ) : array();
		$editing_hotel_nights     = $editing_package ? json_decode( $edit_package_deal->hotel_nights ?? '{}', true ) : array();
		$editing_day_activities   = $editing_package ? json_decode( $edit_package_deal->activities ?? '{}', true ) : array();
		$editing_default_day_activities = $editing_package ? json_decode( $edit_package_deal->default_activities ?? '{}', true ) : array();
		$editing_free_activity_options = $editing_package ? json_decode( $edit_package_deal->free_activity_options ?? '{}', true ) : array();
		$editing_transit_day_fees = $editing_package ? json_decode( $edit_package_deal->transit_day_fees ?? '{}', true ) : array();
		$editing_transit_day_days = $editing_package ? json_decode( $edit_package_deal->transit_day_days ?? '{}', true ) : array();
		$editing_place_ids        = is_array( $editing_place_ids ) ? array_map( 'intval', $editing_place_ids ) : array();
		$editing_hotel_ids        = is_array( $editing_hotel_ids ) ? $editing_hotel_ids : array();
		$editing_default_hotel_ids = is_array( $editing_default_hotel_ids ) ? $editing_default_hotel_ids : array();
		$editing_hotel_nights     = is_array( $editing_hotel_nights ) ? $editing_hotel_nights : array();
		$editing_day_activities   = is_array( $editing_day_activities ) ? $editing_day_activities : array();
		$editing_default_day_activities = is_array( $editing_default_day_activities ) ? $editing_default_day_activities : array();
		$editing_free_activity_options = is_array( $editing_free_activity_options ) ? $editing_free_activity_options : array();
		$editing_transit_day_fees = is_array( $editing_transit_day_fees ) ? $editing_transit_day_fees : array();
		$editing_transit_day_days = is_array( $editing_transit_day_days ) ? $editing_transit_day_days : array();
		?>

		<h2><?php echo $editing_package ? esc_html__( 'Edit Package Deal', 'hotel-rates' ) : esc_html__( 'Register Package Deals', 'hotel-rates' ); ?></h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-reg-form">
			<input type="hidden" name="action" value="hr_save_package_deal">
			<input type="hidden" name="id" value="<?php echo esc_attr( $editing_package_id ); ?>">
			<?php wp_nonce_field( 'hr_save_package_deal' ); ?>
			<div class="hr-reg-form-col">
				<div class="hr-reg-field">
					<label><?php esc_html_e( 'Package Deal Name', 'hotel-rates' ); ?></label>
					<input type="text" name="package_name" class="hr-reg-input" placeholder="<?php esc_attr_e( 'e.g., Siquijor 3D2N Package', 'hotel-rates' ); ?>" value="<?php echo esc_attr( $editing_package_name ); ?>" required>
				</div>
				<div class="hr-reg-field hr-reg-field--sm">
					<label><?php esc_html_e( 'Airport Transfer Fee', 'hotel-rates' ); ?></label>
					<input type="number" min="0" step="0.01" name="airport_transfer_fee" class="hr-reg-input" placeholder="<?php esc_attr_e( 'e.g., 500', 'hotel-rates' ); ?>" value="<?php echo esc_attr( $editing_airport_transfer_fee ); ?>">
				</div>
				<div class="hr-reg-field hr-reg-field--sm">
					<label><?php esc_html_e( 'Seaport Transfer Fee', 'hotel-rates' ); ?></label>
					<input type="number" min="0" step="0.01" name="seaport_transfer_fee" class="hr-reg-input" placeholder="<?php esc_attr_e( 'e.g., 500', 'hotel-rates' ); ?>" value="<?php echo esc_attr( $editing_seaport_transfer_fee ); ?>">
				</div>
				<div class="hr-reg-field hr-reg-field--sm">
					<label><?php esc_html_e( 'Number of Days', 'hotel-rates' ); ?></label>
					<input type="number" min="1" step="1" id="hr-package-days" name="number_of_days" class="hr-reg-input" placeholder="<?php esc_attr_e( 'e.g., 3', 'hotel-rates' ); ?>" value="<?php echo esc_attr( $editing_number_of_days ); ?>" required>
				</div>
				<div class="hr-reg-field">
					<label><?php esc_html_e( 'Places Included', 'hotel-rates' ); ?></label>
					<select id="hr-package-places" name="place_ids[]" class="hr-reg-select" multiple size="6" required>
						<?php foreach ( $all_places_flat as $p ) : ?>
							<option value="<?php echo esc_attr( $p->id ); ?>" <?php selected( in_array( (int) $p->id, $editing_place_ids, true ) ); ?>>
								<?php echo esc_html( $p->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<div class="hr-package-transit-day-fees">
						<h3><?php esc_html_e( 'Transit Day Fee', 'hotel-rates' ); ?></h3>
						<p class="description"><?php esc_html_e( 'Enter the Transit Day fee by paid guest count. Adults and children count toward pax; infants are free.', 'hotel-rates' ); ?></p>
						<div class="hr-modal-pax-grid">
							<?php for ( $p = 1; $p <= Hotel_Rates_DB::MAX_PAX; $p++ ) : ?>
								<?php
								$transit_fee_value = $editing_transit_day_fees[ (string) $p ] ?? ( $editing_transit_day_fees[ $p ] ?? '' );
								?>
								<div class="hr-modal-pax-cell">
									<label for="hr-package-transit-day-fee-<?php echo esc_attr( $p ); ?>">
										<?php printf( esc_html__( '%d pax (₱)', 'hotel-rates' ), $p ); ?>
									</label>
									<input
										type="number"
										step="0.01"
										min="0"
										name="package_transit_day_fees[<?php echo esc_attr( $p ); ?>]"
										id="hr-package-transit-day-fee-<?php echo esc_attr( $p ); ?>"
										class="hr-pax-input"
										value="<?php echo esc_attr( '' === $transit_fee_value || null === $transit_fee_value ? '' : $transit_fee_value ); ?>"
										placeholder="0.00"
									>
								</div>
							<?php endfor; ?>
						</div>
					</div>
						<button type="button" id="hr-package-confirm-places" class="button"><?php esc_html_e( 'Confirm Selected Places', 'hotel-rates' ); ?></button>
					</p>
				</div>
				<div class="hr-reg-field">
					<label><?php esc_html_e( 'Hotels Per Place', 'hotel-rates' ); ?></label>
					<div id="hr-package-hotels-wrap">
						<p class="description"><?php esc_html_e( 'Select one or more places above to assign optional hotels for each place.', 'hotel-rates' ); ?></p>
					</div>
				</div>
				<div class="hr-reg-field">
					<label><?php esc_html_e( 'Activities Per Day', 'hotel-rates' ); ?></label>
					<div id="hr-package-activities-wrap">
						<p class="description"><?php esc_html_e( 'Enter the number of days and confirm the selected places to load Day Tours and Joiners for each day.', 'hotel-rates' ); ?></p>
					</div>
				</div>
			</div>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'hotel-rates' ); ?></button>
		</form>

		<script type="application/json" id="hr-package-edit-hotel-ids"><?php echo wp_json_encode( $editing_hotel_ids ); ?></script>
		<script type="application/json" id="hr-package-edit-default-hotel-ids"><?php echo wp_json_encode( $editing_default_hotel_ids ); ?></script>
		<script type="application/json" id="hr-package-edit-hotel-nights"><?php echo wp_json_encode( $editing_hotel_nights ); ?></script>
		<script type="application/json" id="hr-package-edit-day-activities"><?php echo wp_json_encode( $editing_day_activities ); ?></script>
		<script type="application/json" id="hr-package-edit-default-day-activities"><?php echo wp_json_encode( $editing_default_day_activities ); ?></script>
		<script type="application/json" id="hr-package-edit-free-activity-options"><?php echo wp_json_encode( $editing_free_activity_options ); ?></script>
		<script type="application/json" id="hr-package-edit-transit-day-days"><?php echo wp_json_encode( $editing_transit_day_days ); ?></script>
		<script type="application/json" id="hr-package-hotel-default-rates"><?php echo wp_json_encode( $package_hotel_default_rates ); ?></script>

		<div id="hr-package-hotel-templates" style="display:none;">
			<?php foreach ( $all_places_flat as $p ) : ?>
				<?php
				$place_id     = (int) $p->id;
				$place_hotels = isset( $hotels_by_place[ $place_id ] ) ? $hotels_by_place[ $place_id ] : array();
				$size         = max( 3, min( 6, count( $place_hotels ) + 1 ) );
				?>
				<div class="hr-package-hotel-template" data-place-id="<?php echo esc_attr( $place_id ); ?>">
					<div class="hr-reg-field">
						<label><?php echo esc_html( $p->name ); ?> <?php esc_html_e( 'Hotels', 'hotel-rates' ); ?></label>
						<select class="hr-reg-select hr-package-hotel-choice-select" data-place-id="<?php echo esc_attr( $place_id ); ?>" name="package_hotels[<?php echo esc_attr( $place_id ); ?>][]" multiple size="<?php echo esc_attr( $size ); ?>">
							<option value=""><?php esc_html_e( 'None', 'hotel-rates' ); ?></option>
							<?php foreach ( $place_hotels as $hotel_item ) : ?>
								<option value="<?php echo esc_attr( $hotel_item['id'] ); ?>"><?php echo esc_html( $hotel_item['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<label><?php esc_html_e( 'Default Hotel', 'hotel-rates' ); ?></label>
						<select class="hr-reg-select hr-package-default-hotel-select" data-place-id="<?php echo esc_attr( $place_id ); ?>" name="package_default_hotels[<?php echo esc_attr( $place_id ); ?>]">
							<option value=""><?php esc_html_e( 'No default', 'hotel-rates' ); ?></option>
						</select>
						<label><?php esc_html_e( '# of Nights', 'hotel-rates' ); ?></label>
						<input type="number" min="1" max="<?php echo esc_attr( Hotel_Rates_DB::MAX_NIGHTS ); ?>" step="1" class="hr-reg-input hr-package-hotel-nights-input" data-place-id="<?php echo esc_attr( $place_id ); ?>" name="package_hotel_nights[<?php echo esc_attr( $place_id ); ?>]" value="">
						<p class="description"><?php esc_html_e( 'Leave empty or select "None" if no hotel should be assigned for this place.', 'hotel-rates' ); ?></p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div id="hr-package-activity-templates" style="display:none;">
			<?php foreach ( $all_places_flat as $p ) : ?>
				<?php
				$place_id       = (int) $p->id;
				$place_activity = isset( $activities_by_place[ $place_id ] ) ? $activities_by_place[ $place_id ] : array();
				?>
				<div class="hr-package-activity-template" data-place-id="<?php echo esc_attr( $place_id ); ?>">
					<optgroup label="<?php echo esc_attr( $p->name ); ?>">
						<?php if ( ! empty( $place_activity ) ) : ?>
							<?php foreach ( $place_activity as $activity_item ) : ?>
								<option value="<?php echo esc_attr( $activity_item['id'] ); ?>">
									<?php echo esc_html( $activity_item['name'] . ' (' . $activity_item['type'] . ')' ); ?>
								</option>
							<?php endforeach; ?>
						<?php else : ?>
							<option value="" disabled><?php esc_html_e( 'No activities available', 'hotel-rates' ); ?></option>
						<?php endif; ?>
					</optgroup>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="hr-saved-section">
			<p class="hr-empty-msg"><?php esc_html_e( 'Package Deals setup is in progress. Save logic and package composition will be added next.', 'hotel-rates' ); ?></p>
		</div>

		<div class="hr-saved-section">
			<h3><?php esc_html_e( 'Saved Package Deals:', 'hotel-rates' ); ?></h3>
			<?php if ( ! empty( $saved_package_deals ) ) : ?>
				<div class="hr-grouped-list">
					<?php foreach ( $saved_package_deals as $package_deal ) : ?>
						<div class="hr-group-item">
							<span><?php echo esc_html( $package_deal->package_name ); ?><?php echo ' - ' . esc_html( sprintf( _n( '%d day', '%d days', (int) $package_deal->number_of_days, 'hotel-rates' ), (int) $package_deal->number_of_days ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="hr-empty-msg"><?php esc_html_e( 'No package deals saved yet.', 'hotel-rates' ); ?></p>
			<?php endif; ?>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function () {
			var places = document.getElementById('hr-package-places');
			var days = document.getElementById('hr-package-days');
			var button = document.getElementById('hr-package-confirm-places');
			var wrap = document.getElementById('hr-package-hotels-wrap');
			var activitiesWrap = document.getElementById('hr-package-activities-wrap');
			var templates = document.getElementById('hr-package-hotel-templates');
			var activityTemplates = document.getElementById('hr-package-activity-templates');
			var editHotelIdsNode = document.getElementById('hr-package-edit-hotel-ids');
			var editDefaultHotelIdsNode = document.getElementById('hr-package-edit-default-hotel-ids');
			var editHotelNightsNode = document.getElementById('hr-package-edit-hotel-nights');
			var editDayActivitiesNode = document.getElementById('hr-package-edit-day-activities');
			var editDefaultDayActivitiesNode = document.getElementById('hr-package-edit-default-day-activities');
			var editFreeActivityOptionsNode = document.getElementById('hr-package-edit-free-activity-options');
			var editTransitDayDaysNode = document.getElementById('hr-package-edit-transit-day-days');
			var hotelDefaultRatesNode = document.getElementById('hr-package-hotel-default-rates');
			var transitDayActivityValue = 'hr_transit_day';
			var packageFreeActivityOptions = [
				{ value: 'hr_airport_seaport_pickup', label: '<?php echo esc_js( __( 'Airport/Seaport Pick-up', 'hotel-rates' ) ); ?>' },
				{ value: 'hr_airport_seaport_dropoff', label: '<?php echo esc_js( __( 'Airport/Seaport Drop-off', 'hotel-rates' ) ); ?>' },
				{ value: 'hr_free_time', label: '<?php echo esc_js( __( 'Free Time', 'hotel-rates' ) ); ?>' }
			];
			var editHotelIds = {};
			var editDefaultHotelIds = {};
			var editHotelNights = {};
			var editDayActivities = {};
			var editDefaultDayActivities = {};
			var editFreeActivityOptions = {};
			var editTransitDayDays = {};
			var hotelDefaultRates = {};

			if ( ! places || ! days || ! button || ! wrap || ! activitiesWrap || ! templates || ! activityTemplates ) {
				return;
			}

			try {
				editHotelIds = JSON.parse(editHotelIdsNode ? editHotelIdsNode.textContent : '{}') || {};
			} catch (error) {
				editHotelIds = {};
			}

			try {
				editDefaultHotelIds = JSON.parse(editDefaultHotelIdsNode ? editDefaultHotelIdsNode.textContent : '{}') || {};
			} catch (error) {
				editDefaultHotelIds = {};
			}

			try {
				editHotelNights = JSON.parse(editHotelNightsNode ? editHotelNightsNode.textContent : '{}') || {};
			} catch (error) {
				editHotelNights = {};
			}

			try {
				editDayActivities = JSON.parse(editDayActivitiesNode ? editDayActivitiesNode.textContent : '{}') || {};
			} catch (error) {
				editDayActivities = {};
			}

			try {
				editDefaultDayActivities = JSON.parse(editDefaultDayActivitiesNode ? editDefaultDayActivitiesNode.textContent : '{}') || {};
			} catch (error) {
				editDefaultDayActivities = {};
			}

			try {
				editFreeActivityOptions = JSON.parse(editFreeActivityOptionsNode ? editFreeActivityOptionsNode.textContent : '{}') || {};
			} catch (error) {
				editFreeActivityOptions = {};
			}

			try {
				editTransitDayDays = JSON.parse(editTransitDayDaysNode ? editTransitDayDaysNode.textContent : '{}') || {};
			} catch (error) {
				editTransitDayDays = {};
			}

			try {
				hotelDefaultRates = JSON.parse(hotelDefaultRatesNode ? hotelDefaultRatesNode.textContent : '{}') || {};
			} catch (error) {
				hotelDefaultRates = {};
			}

			function escapeHtml(value) {
				return String(value)
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;')
					.replace(/'/g, '&#039;');
			}

			function getSelectedChoiceOptions(select) {
				return Array.prototype.filter.call(select.options, function (option) {
					return option.value !== '' && option.selected && ! option.disabled;
				});
			}

			function getDefaultFreeActivityValues() {
				return packageFreeActivityOptions.map(function (option) {
					return option.value;
				});
			}

			function getEnabledFreeActivityValues(day) {
				var dayKey = String(day);

				if (Object.prototype.hasOwnProperty.call(editFreeActivityOptions, dayKey) && Array.isArray(editFreeActivityOptions[dayKey])) {
					return editFreeActivityOptions[dayKey].map(String);
				}

				return getDefaultFreeActivityValues();
			}

			function syncDefaultSelect(sourceSelect, defaultSelect, preferredValue) {
				var selectedOptions = getSelectedChoiceOptions(sourceSelect);
				var nextValue = typeof preferredValue === 'undefined' ? String(defaultSelect.value || '') : String(preferredValue || '');
				var hasNextValue = false;

				defaultSelect.innerHTML = '<option value=""><?php echo esc_js( __( 'No default', 'hotel-rates' ) ); ?></option>';

				selectedOptions.forEach(function (option) {
					var defaultOption = document.createElement('option');
					defaultOption.value = option.value;
					defaultOption.textContent = option.textContent;
					defaultSelect.appendChild(defaultOption);

					if ( String(option.value) === nextValue ) {
						hasNextValue = true;
					}
				});

				defaultSelect.disabled = selectedOptions.length === 0;
				defaultSelect.value = hasNextValue ? nextValue : '';
			}

			function getHotelNightsForPlace(placeId) {
				var nightsInput = wrap.querySelector('.hr-package-hotel-nights-input[data-place-id="' + placeId + '"]');
				var packageDays = parseInt(days.value, 10);
				var fallbackNights = packageDays > 1 ? packageDays - 1 : 1;
				var nights = nightsInput ? parseInt(nightsInput.value, 10) : 0;

				if (!nights || nights < 1) {
					nights = fallbackNights;
				}

				return Math.max(1, Math.min(<?php echo (int) Hotel_Rates_DB::MAX_NIGHTS; ?>, nights));
			}

			function getCheapestHotelDefaultValue(sourceSelect) {
				var placeId = String(sourceSelect.getAttribute('data-place-id'));
				var nights = String(getHotelNightsForPlace(placeId));
				var selectedOptions = getSelectedChoiceOptions(sourceSelect);
				var cheapestValue = '';
				var cheapestRate = null;

				selectedOptions.forEach(function (option) {
					var hotelId = String(option.value || '');
					var hotelRates = hotelDefaultRates[hotelId] || {};
					var rate = hotelRates[nights];

					if (rate === null || typeof rate === 'undefined' || rate === '' || Number.isNaN(Number(rate))) {
						return;
					}

					rate = Number(rate);

					if (cheapestRate === null || rate < cheapestRate) {
						cheapestRate = rate;
						cheapestValue = hotelId;
					}
				});

				return cheapestValue;
			}

			function syncHotelDefault(select, preferredMap) {
				var placeId = String(select.getAttribute('data-place-id'));
				var defaultSelect = wrap.querySelector('.hr-package-default-hotel-select[data-place-id="' + placeId + '"]');

				if ( defaultSelect ) {
					syncDefaultSelect(select, defaultSelect, getCheapestHotelDefaultValue(select));
				}
			}

			function syncActivityDefault(select, preferredMap) {
				var day = String(select.getAttribute('data-day'));
				var defaultSelect = activitiesWrap.querySelector('.hr-package-default-activity-select[data-day="' + day + '"]');

				if ( defaultSelect ) {
					syncDefaultSelect(select, defaultSelect, preferredMap ? preferredMap[day] : undefined);
				}
			}

			function isTransitDaySelected(select) {
				return Array.prototype.some.call(select.options, function (option) {
					return option.value === transitDayActivityValue && option.selected;
				});
			}

			function syncTransitDayChoice(select) {
				var transitSelected = isTransitDaySelected(select);

				Array.prototype.forEach.call(select.options, function (option) {
					if (transitSelected && option.value !== transitDayActivityValue) {
						option.selected = false;
					}
				});
			}

			function renderActivities(selected) {
				var numberOfDays = parseInt(days.value, 10);

				activitiesWrap.innerHTML = '';

				if ( ! numberOfDays || numberOfDays < 1 ) {
					activitiesWrap.innerHTML = '<p class="description"><?php echo esc_js( __( 'Enter the number of days first to load the activity selectors.', 'hotel-rates' ) ); ?></p>';
					return;
				}

				if ( ! selected.length ) {
					activitiesWrap.innerHTML = '<p class="description"><?php echo esc_js( __( 'Select one or more places first to load the activity selectors.', 'hotel-rates' ) ); ?></p>';
					return;
				}

				for ( var day = 1; day <= numberOfDays; day++ ) {
					var html = '<div class="hr-reg-field">' +
						'<label><?php echo esc_js( __( 'Day', 'hotel-rates' ) ); ?> ' + day + ' <?php echo esc_js( __( 'Activities', 'hotel-rates' ) ); ?></label>' +
						'<select class="hr-reg-select hr-package-activity-choice-select" data-day="' + day + '" name="package_day_activities[' + day + '][]" multiple size="8">' +
						'<option value="' + transitDayActivityValue + '"><?php echo esc_js( __( 'Transit Day', 'hotel-rates' ) ); ?></option>';

					selected.forEach(function (option) {
						var placeId = String(option.value);
						var template = activityTemplates.querySelector('.hr-package-activity-template[data-place-id="' + placeId + '"]');

						if ( template ) {
							html += template.innerHTML;
						}
					});

					html += '</select>' +
						'<fieldset class="hr-package-free-activity-options" data-day="' + day + '">' +
						'<legend><?php echo esc_js( __( 'Free Activity Options', 'hotel-rates' ) ); ?></legend>' +
						'<p class="description"><?php echo esc_js( __( 'Choose which free options appear in this day activity dropdown on the Package Deal booking form.', 'hotel-rates' ) ); ?></p>';

					var enabledFreeActivityValues = getEnabledFreeActivityValues(day);
					packageFreeActivityOptions.forEach(function (option) {
						var checked = enabledFreeActivityValues.indexOf(option.value) !== -1 ? ' checked' : '';

						html += '<label class="hr-package-free-activity-option">' +
							'<input type="checkbox" name="package_free_activity_options[' + day + '][]" value="' + escapeHtml(option.value) + '"' + checked + '> ' +
							escapeHtml(option.label) +
							'</label>';
					});

					html += '</fieldset>' +
						'<label><?php echo esc_js( __( 'Default Activity', 'hotel-rates' ) ); ?></label>' +
						'<select class="hr-reg-select hr-package-default-activity-select" data-day="' + day + '" name="package_default_day_activities[' + day + ']">' +
						'<option value=""><?php echo esc_js( __( 'No default', 'hotel-rates' ) ); ?></option>' +
						'</select>' +
						'<p class="description"><?php echo esc_js( __( 'Select the Day Tours and/or Joiners to run on this day, then choose one default activity.', 'hotel-rates' ) ); ?></p>' +
						'</div>';

					activitiesWrap.insertAdjacentHTML('beforeend', html);
				}

				Array.prototype.forEach.call(activitiesWrap.querySelectorAll('.hr-package-activity-choice-select[data-day]'), function (select) {
					var dayKey = String(select.getAttribute('data-day'));
					var selectedActivityIds = Array.isArray(editDayActivities[dayKey]) ? editDayActivities[dayKey].map(String) : [];
					var hasTransitDay = !! editTransitDayDays[dayKey];

					Array.prototype.forEach.call(select.options, function (option) {
						option.selected = hasTransitDay
							? option.value === transitDayActivityValue
							: selectedActivityIds.indexOf(String(option.value)) !== -1;
					});

					syncTransitDayChoice(select);
					syncActivityDefault(select, editDefaultDayActivities);
				});
			}

			button.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();

				var selected = Array.prototype.filter.call( places.options, function (option) {
					return option.selected;
				} );

				wrap.innerHTML = '';

				if ( ! selected.length ) {
					wrap.innerHTML = '<p class="description"><?php echo esc_js( __( 'Select one or more places above to assign optional hotels for each place.', 'hotel-rates' ) ); ?></p>';
					return;
				}

				selected.forEach(function (option) {
					var selector = '.hr-package-hotel-template[data-place-id="' + option.value + '"]';
						var template = templates.querySelector(selector);

						if ( template ) {
							wrap.insertAdjacentHTML('beforeend', template.innerHTML);
						}
					});

					Array.prototype.forEach.call(wrap.querySelectorAll('.hr-package-hotel-choice-select[data-place-id]'), function (select) {
						var placeId = String(select.getAttribute('data-place-id'));
						var selectedHotelIds = Array.isArray(editHotelIds[placeId]) ? editHotelIds[placeId].map(String) : [];
						var nightsInput = wrap.querySelector('.hr-package-hotel-nights-input[data-place-id="' + placeId + '"]');

						Array.prototype.forEach.call(select.options, function (option) {
							option.selected = selectedHotelIds.indexOf(String(option.value)) !== -1;
						});

						if ( nightsInput ) {
							nightsInput.value = editHotelNights[placeId] ? String(editHotelNights[placeId]) : '';
						}

						syncHotelDefault(select);
					});

					renderActivities(selected);
				});

				wrap.addEventListener('change', function (e) {
					if ( e.target && e.target.classList.contains('hr-package-hotel-choice-select') ) {
						syncHotelDefault(e.target);
					}

					if ( e.target && e.target.classList.contains('hr-package-hotel-nights-input') ) {
						var placeId = String(e.target.getAttribute('data-place-id'));
						var select = wrap.querySelector('.hr-package-hotel-choice-select[data-place-id="' + placeId + '"]');

						if ( select ) {
							syncHotelDefault(select);
						}
					}
				});

				var packageForm = button.closest('form');

				if ( packageForm ) {
					packageForm.addEventListener('submit', function () {
						Array.prototype.forEach.call(wrap.querySelectorAll('.hr-package-hotel-choice-select[data-place-id]'), function (select) {
							syncHotelDefault(select);
						});
					});
				}

				activitiesWrap.addEventListener('change', function (e) {
					if ( e.target && e.target.classList.contains('hr-package-activity-choice-select') ) {
						syncTransitDayChoice(e.target);
						syncActivityDefault(e.target);
					}
				});

				var editingPackageId = <?php echo (int) $editing_package_id; ?>;
				if ( editingPackageId ) {
					button.click();
				}
			});
		</script>

		<?php endif; ?>

	</div><!-- .hr-reg-box -->
</div>
