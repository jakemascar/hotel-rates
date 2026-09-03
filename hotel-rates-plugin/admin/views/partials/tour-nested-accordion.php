<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Renders the nested Region → Place → Tours accordion.
 * Expects $nested (from Hotel_Rates_DB::get_tours_nested()) in scope.
 * The tour type is inferred from the tours themselves.
 */
?>
<div class="hr-accordion">
	<?php foreach ( $nested as $region_id => $region_data ) :
		$has_tours = false;
		foreach ( $region_data['places'] as $pd ) {
			if ( ! empty( $pd['tours'] ) ) { $has_tours = true; break; }
		}
	?>
	<div class="hr-accordion-item<?php echo $has_tours ? ' hr-accordion-item--open' : ''; ?>">
		<button type="button" class="hr-accordion-header">
			<?php echo esc_html( $region_data['name'] ); ?>
			<span class="hr-accordion-icon">&#x25BE;</span>
		</button>
		<div class="hr-accordion-body">
			<?php if ( ! empty( $region_data['places'] ) ) :
				foreach ( $region_data['places'] as $place_id => $place_data ) :
					if ( empty( $place_data['tours'] ) ) continue;
			?>
			<div class="hr-accordion hr-accordion--nested">
				<div class="hr-accordion-item hr-accordion-item--open">
					<button type="button" class="hr-accordion-header hr-accordion-header--place">
						<?php echo esc_html( $place_data['name'] ); ?>
						<span class="hr-accordion-icon">&#x25BE;</span>
					</button>
					<div class="hr-accordion-body">
						<?php foreach ( $place_data['tours'] as $tour ) :
							$tour_type = ( isset( $tour->type ) ) ? $tour->type : 'Day Tour';
							$del_tab   = ( $tour_type === 'Joiner' ) ? 'joiners' : 'day-tours';
						?>
						<div class="hr-group-item hr-tour-item" data-tour-id="<?php echo esc_attr( $tour->id ); ?>">
							<!-- View mode -->
							<div class="hr-tour-view">
								<span class="hr-tour-name-text"><?php echo esc_html( $tour->tour_name ); ?></span>
								<div class="hr-tour-item-actions">
									<button type="button" class="hr-tour-edit-btn" title="<?php esc_attr_e( 'Edit name', 'hotel-rates' ); ?>">
										<span class="dashicons dashicons-edit"></span>
									</button>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hr-inline-delete">
										<input type="hidden" name="action" value="hr_delete_tour_reg">
										<input type="hidden" name="id"   value="<?php echo esc_attr( $tour->id ); ?>">
										<input type="hidden" name="type" value="<?php echo esc_attr( $tour_type ); ?>">
										<?php wp_nonce_field( 'hr_delete_tour_reg' ); ?>
										<button type="submit" class="hr-delete-x" onclick="return confirm('<?php esc_attr_e( 'Delete this tour?', 'hotel-rates' ); ?>')">×</button>
									</form>
								</div>
							</div>
							<!-- Edit mode (hidden by default) -->
							<div class="hr-tour-edit-mode" style="display:none;">
								<input type="text" class="hr-tour-inline-input" value="<?php echo esc_attr( $tour->tour_name ); ?>">
								<div class="hr-tour-edit-actions">
									<button type="button" class="hr-tour-save-inline" title="<?php esc_attr_e( 'Save', 'hotel-rates' ); ?>">✓</button>
									<button type="button" class="hr-tour-cancel-inline" title="<?php esc_attr_e( 'Cancel', 'hotel-rates' ); ?>">×</button>
								</div>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
			<?php else : ?>
				<p class="hr-empty-msg" style="padding: 10px 16px;"><?php esc_html_e( 'No tours in this region yet.', 'hotel-rates' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php endforeach; ?>
</div>
