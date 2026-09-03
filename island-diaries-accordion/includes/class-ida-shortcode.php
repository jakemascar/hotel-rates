<?php
/**
 * Front-end shortcode renderer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IDA_Shortcode {
	/** @var int Number of shortcode instances rendered on the page. */
	private static $instance = 0;

	/** Register hooks. */
	public function __construct() {
		add_shortcode( 'tour_details_accordion', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/** Register without loading until a shortcode is rendered. */
	public function register_assets() {
		wp_register_style( 'ida-accordion', IDA_PLUGIN_URL . 'public/css/accordion.css', array(), IDA_VERSION );
		wp_register_script( 'ida-accordion', IDA_PLUGIN_URL . 'public/js/accordion.js', array(), IDA_VERSION, true );
	}

	/**
	 * Render [tour_details_accordion].
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'       => 0,
				'open'     => 'saved',
				'multiple' => 'no',
			),
			$atts,
			'tour_details_accordion'
		);

		$post_id = absint( $atts['id'] );
		if ( ! $post_id || 'ida_accordion' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			return current_user_can( 'edit_posts' )
				? '<p class="ida-message">' . esc_html__( 'Accordion shortcode: select a published Accordion Set with a valid id.', 'island-diaries-accordion' ) . '</p>'
				: '';
		}

		$sections = get_post_meta( $post_id, '_ida_sections', true );
		if ( ! is_array( $sections ) || empty( $sections ) ) {
			return current_user_can( 'edit_post', $post_id )
				? '<p class="ida-message">' . esc_html__( 'This accordion set has no sections yet.', 'island-diaries-accordion' ) . '</p>'
				: '';
		}

		$open_mode = strtolower( sanitize_key( $atts['open'] ) );
		if ( ! in_array( $open_mode, array( 'saved', 'first', 'all', 'none' ), true ) ) {
			$open_mode = 'saved';
		}
		$multiple = in_array( strtolower( (string) $atts['multiple'] ), array( '1', 'yes', 'true', 'on' ), true );

		wp_enqueue_style( 'ida-accordion' );
		wp_enqueue_script( 'ida-accordion' );

		self::$instance++;
		$instance_id = 'ida-accordion-' . $post_id . '-' . self::$instance;
		$opened_one  = false;

		ob_start();
		?>
		<div class="ida-accordion" id="<?php echo esc_attr( $instance_id ); ?>" data-ida-accordion data-multiple="<?php echo $multiple ? 'true' : 'false'; ?>">
			<?php foreach ( $sections as $index => $section ) : ?>
				<?php
				if ( ! is_array( $section ) || empty( $section['title'] ) ) {
					continue;
				}
				$is_open = $this->is_open( $open_mode, $index, $section );
				if ( ! $multiple && $is_open && $opened_one ) {
					$is_open = false;
				}
				if ( $is_open ) {
					$opened_one = true;
				}
				$button_id = $instance_id . '-button-' . absint( $index );
				$panel_id  = $instance_id . '-panel-' . absint( $index );
				?>
				<section class="ida-item<?php echo $is_open ? ' is-open' : ''; ?>">
					<h3 class="ida-heading">
						<button
							type="button"
							class="ida-trigger"
							id="<?php echo esc_attr( $button_id ); ?>"
							aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
							aria-controls="<?php echo esc_attr( $panel_id ); ?>"
						>
							<span><?php echo esc_html( $section['title'] ); ?></span>
							<span class="ida-icon" aria-hidden="true"></span>
						</button>
					</h3>
					<div
						class="ida-panel"
						id="<?php echo esc_attr( $panel_id ); ?>"
						role="region"
						aria-labelledby="<?php echo esc_attr( $button_id ); ?>"
						<?php echo $is_open ? '' : ' hidden'; ?>
					>
						<?php $this->render_section_content( $section ); ?>
					</div>
				</section>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Determine initial state. */
	private function is_open( $mode, $index, $section ) {
		if ( 'all' === $mode ) {
			return true;
		}
		if ( 'none' === $mode ) {
			return false;
		}
		if ( 'first' === $mode ) {
			return 0 === (int) $index;
		}
		return ! empty( $section['initially_open'] );
	}

	/** Render the selected layout. */
	private function render_section_content( $section ) {
		$layout = isset( $section['layout'] ) ? $section['layout'] : 'content';
		if ( 'rates' === $layout ) {
			$this->render_rates( $section );
			return;
		}
		if ( 'itinerary' === $layout ) {
			$this->render_itinerary( $section );
			return;
		}
		?>
		<div class="ida-rich-content"><?php echo $this->format_content( $section['content'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php
	}

	/** Render rates with a shared details column. */
	private function render_rates( $section ) {
		$rates = isset( $section['rates'] ) && is_array( $section['rates'] ) ? $section['rates'] : array();
		?>
		<div class="ida-rates-layout">
			<div class="ida-table-scroll">
				<table class="ida-table ida-rates-table">
					<thead><tr>
						<th scope="col"><?php echo esc_html( $section['left_heading'] ?? 'No. of Pax' ); ?></th>
						<th scope="col"><?php echo esc_html( $section['right_heading'] ?? 'Rate per Person' ); ?></th>
					</tr></thead>
					<tbody>
						<?php foreach ( $rates as $row ) : ?>
							<tr><td><?php echo esc_html( $row['label'] ?? '' ); ?></td><td><?php echo esc_html( $row['value'] ?? '' ); ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<div class="ida-package-details">
				<div class="ida-details-heading"><?php echo esc_html( $section['details_heading'] ?? 'Details of the Package' ); ?></div>
				<div class="ida-details-content"><?php echo $this->format_content( $section['details'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			</div>
		</div>
		<?php
	}

	/** Render itinerary table. */
	private function render_itinerary( $section ) {
		$rows = isset( $section['itinerary'] ) && is_array( $section['itinerary'] ) ? $section['itinerary'] : array();
		?>
		<div class="ida-table-scroll">
			<table class="ida-table ida-itinerary-table">
				<thead class="ida-visually-hidden"><tr>
					<th scope="col"><?php echo esc_html( $section['left_heading'] ?? 'Time' ); ?></th>
					<th scope="col"><?php echo esc_html( $section['right_heading'] ?? 'Activity' ); ?></th>
				</tr></thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<th scope="row" data-label="<?php echo esc_attr( $section['left_heading'] ?? 'Time' ); ?>"><?php echo esc_html( $row['label'] ?? '' ); ?></th>
							<td data-label="<?php echo esc_attr( $section['right_heading'] ?? 'Activity' ); ?>">
								<?php if ( ! empty( $row['summary'] ) ) : ?><p><?php echo esc_html( $row['summary'] ); ?></p><?php endif; ?>
								<?php if ( ! empty( $row['subheading'] ) ) : ?><h4><?php echo esc_html( $row['subheading'] ); ?></h4><?php endif; ?>
								<?php $this->render_bullets( $row['bullets'] ?? '' ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/** Print one-item-per-line bullets. */
	private function render_bullets( $value ) {
		$items = preg_split( '/\r\n|\r|\n/', (string) $value );
		$items = array_values( array_filter( array_map( 'trim', $items ), 'strlen' ) );
		if ( empty( $items ) ) {
			return;
		}
		echo '<ul>';
		foreach ( $items as $item ) {
			echo '<li>' . esc_html( $item ) . '</li>';
		}
		echo '</ul>';
	}

	/** Apply normal WordPress content formatting to already-sanitized editor HTML. */
	private function format_content( $content ) {
		return do_shortcode( wpautop( wp_kses_post( (string) $content ) ) );
	}
}
