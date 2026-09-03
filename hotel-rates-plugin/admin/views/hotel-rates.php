<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * $regions, $region_id, $places, $place_id, $hotels, $hotel_id, $hotel
 * provided by Hotel_Rates_Admin::page_hotel_rates()
 */
$max_nights = Hotel_Rates_DB::MAX_NIGHTS;
$max_pax    = Hotel_Rates_DB::MAX_PAX;
$default_pax_visible = 8; // initially shown pax columns
?>
<div class="wrap hr-wrap">
	<h1><?php esc_html_e( 'Hotel Rates', 'hotel-rates' ); ?></h1>

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Rate saved successfully.', 'hotel-rates' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Rate deleted.', 'hotel-rates' ); ?></p></div>
	<?php endif; ?>

	<?php if ( empty( $regions ) ) : ?>
		<div class="notice notice-warning"><p><?php printf( esc_html__( 'No regions found. %sRegister a place and hotel first.%s', 'hotel-rates' ), '<a href="' . esc_url( admin_url( 'admin.php?page=hotel-rates-register' ) ) . '">', '</a>' ); ?></p></div>
	<?php else : ?>

	<!-- ── Cascading Dropdowns ──────────────────────────────────────── -->
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
			<label><?php esc_html_e( 'Choose your Hotels', 'hotel-rates' ); ?></label>
			<select id="hr-hotel" data-current="<?php echo esc_attr( $hotel_id ); ?>">
				<?php foreach ( $hotels as $h ) : ?>
					<option value="<?php echo esc_attr( $h->id ); ?>" <?php selected( $h->id, $hotel_id ); ?>><?php echo esc_html( $h->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="hr-filter-group hr-filter-btn">
			<button id="hr-load-rates" class="button button-primary"><?php esc_html_e( 'Load Rates', 'hotel-rates' ); ?></button>
		</div>
	</div>

	<?php if ( $hotel ) :
		$all_rates = Hotel_Rates_DB::get_all_rates( $hotel_id );
	?>

	<!-- ── Breadcrumb + Night +/- Controls ──────────────────────────── -->
	<div class="hr-hotel-header">
		<div class="hr-breadcrumb">
			<span><?php esc_html_e( 'Hotels', 'hotel-rates' ); ?></span>
			<span class="hr-arrow">→</span>
			<strong><?php echo esc_html( $hotel->name ); ?></strong>
		</div>
		<div class="hr-night-controls">
			<button type="button" id="hr-night-add" class="hr-ctrl-btn" title="<?php esc_attr_e( 'Show next night', 'hotel-rates' ); ?>">+</button>
			<button type="button" id="hr-night-rem" class="hr-ctrl-btn hr-ctrl-btn--minus" title="<?php esc_attr_e( 'Hide last night', 'hotel-rates' ); ?>">−</button>
		</div>
	</div>

	<!-- ── Night Toggle Checkboxes ──────────────────────────────────── -->
	<div class="hr-night-checks" id="hr-night-checks">
		<?php for ( $n = 1; $n <= $max_nights; $n++ ) : ?>
		<label class="hr-night-label">
			<input type="checkbox" class="hr-night-chk" value="<?php echo $n; ?>" <?php checked( $n <= 5 ); ?>>
			<?php echo $n; ?>N
		</label>
		<?php endfor; ?>
	</div>

	<!-- ── Rate Sections (all nights pre-loaded) ────────────────────── -->
	<?php for ( $n = 1; $n <= $max_nights; $n++ ) :
		$rates    = $all_rates[ $n ];
		$hidden   = $n > 5 ? 'style="display:none;"' : '';
	?>
	<div class="hr-rate-section" id="hr-night-<?php echo $n; ?>" data-night="<?php echo $n; ?>" <?php echo $hidden; ?>>
		<h2><?php printf( esc_html__( '%dN Rates', 'hotel-rates' ), $n ); ?></h2>

		<div class="hr-table-wrap">
			<?php if ( ! empty( $rates ) ) : ?>
			<table class="wp-list-table widefat fixed hr-rate-table" data-night="<?php echo $n; ?>">
				<thead>
					<tr>
						<th class="hr-col-meal"><?php esc_html_e( 'Breakfast', 'hotel-rates' ); ?></th>
						<?php for ( $p = 1; $p <= $max_pax; $p++ ) : ?>
						<th class="hr-col-pax<?php echo $p > $default_pax_visible ? ' hr-pax-hidden' : ''; ?>" data-pax="<?php echo $p; ?>"><?php echo $p; ?> pax</th>
						<?php endfor; ?>
						<th class="hr-col-pax-ctrl">
							<button type="button" class="hr-pax-add" title="Add pax column">+</button>
							<button type="button" class="hr-pax-rem" title="Remove pax column">−</button>
						</th>
						<th class="hr-actions-col"><?php esc_html_e( 'Actions', 'hotel-rates' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rates as $rate ) : ?>
					<tr>
						<td class="hr-col-meal"><?php echo esc_html( $rate->meal_plan ); ?></td>
						<?php for ( $p = 1; $p <= $max_pax; $p++ ) : $col = 'pax_' . $p; ?>
						<td class="hr-col-pax<?php echo $p > $default_pax_visible ? ' hr-pax-hidden' : ''; ?>" data-pax="<?php echo $p; ?>">
							<?php echo $rate->$col !== null ? '₱' . number_format( (float) $rate->$col, 2 ) : '—'; ?>
						</td>
						<?php endfor; ?>
						<td class="hr-col-pax-ctrl"></td>
						<td class="hr-actions-col">
							<button class="button button-small hr-edit-rate-btn"
								data-id="<?php echo esc_attr( $rate->id ); ?>"
								data-meal="<?php echo esc_attr( $rate->meal_plan ); ?>"
								data-nights="<?php echo esc_attr( $n ); ?>"
								<?php for ( $p = 1; $p <= $max_pax; $p++ ) : $col = 'pax_' . $p; ?>
								data-pax-<?php echo $p; ?>="<?php echo esc_attr( $rate->$col ?? '' ); ?>"
								<?php endfor; ?>
							><?php esc_html_e( 'Edit', 'hotel-rates' ); ?></button>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
								<input type="hidden" name="action" value="hr_delete_rate">
								<input type="hidden" name="id" value="<?php echo esc_attr( $rate->id ); ?>">
								<input type="hidden" name="region_id" value="<?php echo esc_attr( $region_id ); ?>">
								<input type="hidden" name="place_id"  value="<?php echo esc_attr( $place_id ); ?>">
								<input type="hidden" name="hotel_id"  value="<?php echo esc_attr( $hotel_id ); ?>">
								<?php wp_nonce_field( 'hr_delete_rate' ); ?>
								<button type="submit" class="button button-small button-link-delete"
									onclick="return confirm('<?php esc_attr_e( 'Delete this rate row?', 'hotel-rates' ); ?>')"
								><?php esc_html_e( 'Del', 'hotel-rates' ); ?></button>
							</form>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?>
			<p class="hr-no-rates"><?php esc_html_e( 'No rates added yet.', 'hotel-rates' ); ?></p>
			<?php endif; ?>
		</div>

		<button class="button hr-add-rate-btn" data-nights="<?php echo esc_attr( $n ); ?>" data-hotel="<?php echo esc_attr( $hotel_id ); ?>">
			+ <?php printf( esc_html__( 'Add %dN Rate', 'hotel-rates' ), $n ); ?>
		</button>
	</div>
	<?php endfor; ?>

	<!-- ── Add / Edit Rate Modal ───────────────────────────────────── -->
	<div id="hr-rate-modal" class="hr-modal" style="display:none;">
		<div class="hr-modal-inner">
			<button class="hr-modal-close" type="button">✕</button>
			<h2 id="hr-modal-title"><?php esc_html_e( 'Add Rate', 'hotel-rates' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="hr_save_rate">
				<input type="hidden" name="id"        id="hr-rate-id"     value="">
				<input type="hidden" name="hotel_id"  value="<?php echo esc_attr( $hotel_id ); ?>">
				<input type="hidden" name="region_id" value="<?php echo esc_attr( $region_id ); ?>">
				<input type="hidden" name="place_id"  value="<?php echo esc_attr( $place_id ); ?>">
				<input type="hidden" name="nights"    id="hr-rate-nights" value="">
				<?php wp_nonce_field( 'hr_save_rate' ); ?>

				<div class="hr-modal-field">
					<label><?php esc_html_e( 'Meal Plan', 'hotel-rates' ); ?></label>
					<select name="meal_plan" id="hr-meal-plan">
						<option value="X"><?php esc_html_e( 'X — No Breakfast', 'hotel-rates' ); ?></option>
						<option value="BB"><?php esc_html_e( 'BB — Bed &amp; Breakfast', 'hotel-rates' ); ?></option>
						<option value="HB"><?php esc_html_e( 'HB — Half Board', 'hotel-rates' ); ?></option>
						<option value="FB"><?php esc_html_e( 'FB — Full Board', 'hotel-rates' ); ?></option>
					</select>
				</div>

				<div class="hr-modal-pax-grid">
					<?php for ( $p = 1; $p <= $max_pax; $p++ ) : ?>
					<div class="hr-modal-pax-cell">
						<label for="hr-pax-<?php echo $p; ?>"><?php printf( esc_html__( '%d pax (₱)', 'hotel-rates' ), $p ); ?></label>
						<input type="number" step="0.01" min="0" name="pax_<?php echo $p; ?>" id="hr-pax-<?php echo $p; ?>" class="hr-pax-input" placeholder="0.00">
					</div>
					<?php endfor; ?>
				</div>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Rate', 'hotel-rates' ); ?></button>
					<button type="button" class="button hr-modal-close"><?php esc_html_e( 'Cancel', 'hotel-rates' ); ?></button>
				</p>
			</form>
		</div>
	</div>
	<div id="hr-overlay" class="hr-overlay" style="display:none;"></div>

	<?php else : ?>
		<div class="notice notice-info"><p><?php esc_html_e( 'Please select a region, place, and hotel above, then click Load Rates.', 'hotel-rates' ); ?></p></div>
	<?php endif; ?>
	<?php endif; ?>
</div>
