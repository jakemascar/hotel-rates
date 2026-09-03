<?php
/**
 * Accordion set editor.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IDA_Admin {
	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_ida_accordion', array( $this, 'save_accordion' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'manage_ida_accordion_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_ida_accordion_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
	}

	/**
	 * Register private admin-only content type.
	 */
	public static function register_post_type() {
		register_post_type(
			'ida_accordion',
			array(
				'labels' => array(
					'name'               => __( 'Accordion Sets', 'island-diaries-accordion' ),
					'singular_name'      => __( 'Accordion Set', 'island-diaries-accordion' ),
					'add_new'            => __( 'Add New', 'island-diaries-accordion' ),
					'add_new_item'       => __( 'Add New Accordion Set', 'island-diaries-accordion' ),
					'edit_item'          => __( 'Edit Accordion Set', 'island-diaries-accordion' ),
					'new_item'           => __( 'New Accordion Set', 'island-diaries-accordion' ),
					'view_item'          => __( 'View Accordion Set', 'island-diaries-accordion' ),
					'search_items'       => __( 'Search Accordion Sets', 'island-diaries-accordion' ),
					'not_found'          => __( 'No accordion sets found.', 'island-diaries-accordion' ),
					'not_found_in_trash' => __( 'No accordion sets found in Trash.', 'island-diaries-accordion' ),
					'menu_name'          => __( 'Accordion Sets', 'island-diaries-accordion' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'menu_icon'           => 'dashicons-editor-justify',
				'menu_position'       => 26,
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'rewrite'             => false,
				'query_var'           => false,
			)
		);
	}

	/**
	 * Add editor and shortcode boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'ida_sections',
			__( 'Accordion Sections', 'island-diaries-accordion' ),
			array( $this, 'render_sections_box' ),
			'ida_accordion',
			'normal',
			'high'
		);

		add_meta_box(
			'ida_shortcode',
			__( 'Shortcode', 'island-diaries-accordion' ),
			array( $this, 'render_shortcode_box' ),
			'ida_accordion',
			'side',
			'high'
		);
	}

	/**
	 * Load editor assets only on this content type.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || 'ida_accordion' !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_editor();
		wp_enqueue_style( 'ida-admin', IDA_PLUGIN_URL . 'admin/css/admin.css', array(), IDA_VERSION );
		wp_enqueue_script( 'ida-admin', IDA_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery', 'editor' ), IDA_VERSION, true );
		wp_localize_script(
			'ida-admin',
			'idaAdmin',
			array(
				'confirmRemove' => __( 'Remove this section?', 'island-diaries-accordion' ),
			)
		);
	}

	/**
	 * Render all editable sections.
	 *
	 * @param WP_Post $post Current accordion set.
	 */
	public function render_sections_box( $post ) {
		wp_nonce_field( 'ida_save_accordion', 'ida_accordion_nonce' );
		$sections = get_post_meta( $post->ID, '_ida_sections', true );
		$sections = is_array( $sections ) ? $sections : array();
		?>
		<p class="description ida-intro">
			<?php esc_html_e( 'Add sections, choose a layout, and arrange them with the arrow buttons. Rich Content fields support the WordPress visual and text editors.', 'island-diaries-accordion' ); ?>
		</p>
		<div id="ida-sections">
			<?php foreach ( $sections as $index => $section ) : ?>
				<?php $this->render_section( 's' . absint( $index ), $section ); ?>
			<?php endforeach; ?>
		</div>
		<p><button type="button" class="button button-primary" id="ida-add-section"><?php esc_html_e( 'Add Accordion Section', 'island-diaries-accordion' ); ?></button></p>

		<script type="text/html" id="tmpl-ida-section"><?php $this->render_section( '__SECTION__', array() ); ?></script>
		<script type="text/html" id="tmpl-ida-rate-row"><?php $this->render_rate_row( '__SECTION__', '__ROW__', array() ); ?></script>
		<script type="text/html" id="tmpl-ida-itinerary-row"><?php $this->render_itinerary_row( '__SECTION__', '__ROW__', array() ); ?></script>
		<?php
	}

	/**
	 * Render one accordion section editor.
	 *
	 * @param string $key     Form key.
	 * @param array  $section Saved values.
	 */
	private function render_section( $key, $section ) {
		$section = wp_parse_args(
			is_array( $section ) ? $section : array(),
			array(
				'title'           => '',
				'layout'          => 'content',
				'initially_open'  => 0,
				'left_heading'    => '',
				'right_heading'   => '',
				'details_heading' => '',
				'details'         => '',
				'content'         => '',
				'rates'           => array(),
				'itinerary'       => array(),
			)
		);
		$editor_id = 'ida_editor_' . sanitize_html_class( $key );
		?>
		<div class="ida-section" data-section="<?php echo esc_attr( $key ); ?>">
			<div class="ida-section-bar">
				<strong class="ida-section-label"><?php echo esc_html( $section['title'] ? $section['title'] : __( 'New section', 'island-diaries-accordion' ) ); ?></strong>
				<div class="ida-section-actions">
					<button type="button" class="button ida-move-up" aria-label="<?php esc_attr_e( 'Move section up', 'island-diaries-accordion' ); ?>">↑</button>
					<button type="button" class="button ida-move-down" aria-label="<?php esc_attr_e( 'Move section down', 'island-diaries-accordion' ); ?>">↓</button>
					<button type="button" class="button-link-delete ida-remove-section"><?php esc_html_e( 'Remove', 'island-diaries-accordion' ); ?></button>
				</div>
			</div>

			<div class="ida-section-settings">
				<label>
					<span><?php esc_html_e( 'Section title', 'island-diaries-accordion' ); ?></span>
					<input type="text" class="widefat ida-title-input" name="ida_sections[<?php echo esc_attr( $key ); ?>][title]" value="<?php echo esc_attr( $section['title'] ); ?>" required>
				</label>
				<label>
					<span><?php esc_html_e( 'Content layout', 'island-diaries-accordion' ); ?></span>
					<select class="widefat ida-layout-select" name="ida_sections[<?php echo esc_attr( $key ); ?>][layout]">
						<option value="rates" <?php selected( $section['layout'], 'rates' ); ?>><?php esc_html_e( 'Rates + package details', 'island-diaries-accordion' ); ?></option>
						<option value="itinerary" <?php selected( $section['layout'], 'itinerary' ); ?>><?php esc_html_e( 'Two-column itinerary', 'island-diaries-accordion' ); ?></option>
						<option value="content" <?php selected( $section['layout'], 'content' ); ?>><?php esc_html_e( 'Rich content', 'island-diaries-accordion' ); ?></option>
					</select>
				</label>
				<label class="ida-checkbox-label">
					<input type="checkbox" name="ida_sections[<?php echo esc_attr( $key ); ?>][initially_open]" value="1" <?php checked( ! empty( $section['initially_open'] ) ); ?>>
					<?php esc_html_e( 'Open by default', 'island-diaries-accordion' ); ?>
				</label>
			</div>

			<div class="ida-layout-panel" data-layout-panel="rates">
				<div class="ida-heading-fields">
					<label><?php esc_html_e( 'First column heading', 'island-diaries-accordion' ); ?><input type="text" name="ida_sections[<?php echo esc_attr( $key ); ?>][rates_left_heading]" value="<?php echo esc_attr( 'rates' === $section['layout'] && $section['left_heading'] ? $section['left_heading'] : 'No. of Pax' ); ?>"></label>
					<label><?php esc_html_e( 'Second column heading', 'island-diaries-accordion' ); ?><input type="text" name="ida_sections[<?php echo esc_attr( $key ); ?>][rates_right_heading]" value="<?php echo esc_attr( 'rates' === $section['layout'] && $section['right_heading'] ? $section['right_heading'] : 'Rate per Person' ); ?>"></label>
					<label><?php esc_html_e( 'Details heading', 'island-diaries-accordion' ); ?><input type="text" name="ida_sections[<?php echo esc_attr( $key ); ?>][details_heading]" value="<?php echo esc_attr( $section['details_heading'] ?: 'Details of the Package' ); ?>"></label>
				</div>
				<div class="ida-rate-rows">
					<?php foreach ( (array) $section['rates'] as $row_index => $row ) : ?>
						<?php $this->render_rate_row( $key, 'r' . absint( $row_index ), $row ); ?>
					<?php endforeach; ?>
				</div>
				<p><button type="button" class="button ida-add-rate-row"><?php esc_html_e( 'Add Rate Row', 'island-diaries-accordion' ); ?></button></p>
				<label class="ida-field-label" for="<?php echo esc_attr( $editor_id . '_details' ); ?>"><?php esc_html_e( 'Package details', 'island-diaries-accordion' ); ?></label>
				<textarea id="<?php echo esc_attr( $editor_id . '_details' ); ?>" class="ida-rich-editor" name="ida_sections[<?php echo esc_attr( $key ); ?>][details]"><?php echo esc_textarea( $section['details'] ); ?></textarea>
			</div>

			<div class="ida-layout-panel" data-layout-panel="itinerary">
				<div class="ida-heading-fields ida-heading-fields--two">
					<label><?php esc_html_e( 'First column heading', 'island-diaries-accordion' ); ?><input type="text" name="ida_sections[<?php echo esc_attr( $key ); ?>][itinerary_left_heading]" value="<?php echo esc_attr( 'itinerary' === $section['layout'] && $section['left_heading'] ? $section['left_heading'] : 'Time' ); ?>"></label>
					<label><?php esc_html_e( 'Second column heading', 'island-diaries-accordion' ); ?><input type="text" name="ida_sections[<?php echo esc_attr( $key ); ?>][itinerary_right_heading]" value="<?php echo esc_attr( 'itinerary' === $section['layout'] && $section['right_heading'] ? $section['right_heading'] : 'Activity' ); ?>"></label>
				</div>
				<div class="ida-itinerary-rows">
					<?php foreach ( (array) $section['itinerary'] as $row_index => $row ) : ?>
						<?php $this->render_itinerary_row( $key, 'r' . absint( $row_index ), $row ); ?>
					<?php endforeach; ?>
				</div>
				<p><button type="button" class="button ida-add-itinerary-row"><?php esc_html_e( 'Add Itinerary Row', 'island-diaries-accordion' ); ?></button></p>
			</div>

			<div class="ida-layout-panel" data-layout-panel="content">
				<label class="ida-field-label" for="<?php echo esc_attr( $editor_id . '_content' ); ?>"><?php esc_html_e( 'Section content', 'island-diaries-accordion' ); ?></label>
				<textarea id="<?php echo esc_attr( $editor_id . '_content' ); ?>" class="ida-rich-editor" name="ida_sections[<?php echo esc_attr( $key ); ?>][content]"><?php echo esc_textarea( $section['content'] ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a rate row.
	 */
	private function render_rate_row( $section_key, $row_key, $row ) {
		$row = wp_parse_args( is_array( $row ) ? $row : array(), array( 'label' => '', 'value' => '' ) );
		?>
		<div class="ida-data-row">
			<input type="text" name="ida_sections[<?php echo esc_attr( $section_key ); ?>][rates][<?php echo esc_attr( $row_key ); ?>][label]" value="<?php echo esc_attr( $row['label'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. 1', 'island-diaries-accordion' ); ?>">
			<input type="text" name="ida_sections[<?php echo esc_attr( $section_key ); ?>][rates][<?php echo esc_attr( $row_key ); ?>][value]" value="<?php echo esc_attr( $row['value'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. ₱ 4,250', 'island-diaries-accordion' ); ?>">
			<button type="button" class="button-link-delete ida-remove-row" aria-label="<?php esc_attr_e( 'Remove row', 'island-diaries-accordion' ); ?>">×</button>
		</div>
		<?php
	}

	/**
	 * Render an itinerary row.
	 */
	private function render_itinerary_row( $section_key, $row_key, $row ) {
		$row = wp_parse_args(
			is_array( $row ) ? $row : array(),
			array( 'label' => '', 'summary' => '', 'subheading' => '', 'bullets' => '' )
		);
		?>
		<div class="ida-itinerary-row ida-data-row">
			<div class="ida-itinerary-fields">
				<input type="text" name="ida_sections[<?php echo esc_attr( $section_key ); ?>][itinerary][<?php echo esc_attr( $row_key ); ?>][label]" value="<?php echo esc_attr( $row['label'] ); ?>" placeholder="<?php esc_attr_e( 'Time or label', 'island-diaries-accordion' ); ?>">
				<input type="text" name="ida_sections[<?php echo esc_attr( $section_key ); ?>][itinerary][<?php echo esc_attr( $row_key ); ?>][summary]" value="<?php echo esc_attr( $row['summary'] ); ?>" placeholder="<?php esc_attr_e( 'Main activity', 'island-diaries-accordion' ); ?>">
				<input type="text" name="ida_sections[<?php echo esc_attr( $section_key ); ?>][itinerary][<?php echo esc_attr( $row_key ); ?>][subheading]" value="<?php echo esc_attr( $row['subheading'] ); ?>" placeholder="<?php esc_attr_e( 'Optional list heading', 'island-diaries-accordion' ); ?>">
				<textarea name="ida_sections[<?php echo esc_attr( $section_key ); ?>][itinerary][<?php echo esc_attr( $row_key ); ?>][bullets]" placeholder="<?php esc_attr_e( 'Optional bullet list — one item per line', 'island-diaries-accordion' ); ?>"><?php echo esc_textarea( $row['bullets'] ); ?></textarea>
			</div>
			<button type="button" class="button-link-delete ida-remove-row" aria-label="<?php esc_attr_e( 'Remove row', 'island-diaries-accordion' ); ?>">×</button>
		</div>
		<?php
	}

	/**
	 * Render copyable shortcode.
	 */
	public function render_shortcode_box( $post ) {
		if ( 'auto-draft' === $post->post_status ) {
			echo '<p>' . esc_html__( 'Publish or save the accordion set to generate its shortcode.', 'island-diaries-accordion' ) . '</p>';
			return;
		}
		?>
		<p><?php esc_html_e( 'Paste this into a page, post, product, or Shortcode widget:', 'island-diaries-accordion' ); ?></p>
		<input class="widefat ida-shortcode-copy" type="text" readonly value="<?php echo esc_attr( '[tour_details_accordion id=&quot;' . $post->ID . '&quot;]' ); ?>" onclick="this.select();">
		<p class="description"><code>open="saved|first|all|none"</code><br><code>multiple="yes|no"</code></p>
		<?php
	}

	/**
	 * Sanitize and save nested editor data.
	 */
	public function save_accordion( $post_id ) {
		if ( ! isset( $_POST['ida_accordion_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ida_accordion_nonce'] ) ), 'ida_save_accordion' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw_sections = isset( $_POST['ida_sections'] ) && is_array( $_POST['ida_sections'] ) ? wp_unslash( $_POST['ida_sections'] ) : array();
		$sections     = array();

		foreach ( $raw_sections as $raw_section ) {
			if ( ! is_array( $raw_section ) ) {
				continue;
			}

			$layout = isset( $raw_section['layout'] ) ? sanitize_key( $raw_section['layout'] ) : 'content';
			$layout = in_array( $layout, array( 'rates', 'itinerary', 'content' ), true ) ? $layout : 'content';
			$section = array(
				'title'          => sanitize_text_field( $raw_section['title'] ?? '' ),
				'layout'         => $layout,
				'initially_open' => empty( $raw_section['initially_open'] ) ? 0 : 1,
			);

			if ( '' === $section['title'] ) {
				continue;
			}

			if ( 'rates' === $layout ) {
				$section['left_heading']    = sanitize_text_field( $raw_section['rates_left_heading'] ?? 'No. of Pax' );
				$section['right_heading']   = sanitize_text_field( $raw_section['rates_right_heading'] ?? 'Rate per Person' );
				$section['details_heading'] = sanitize_text_field( $raw_section['details_heading'] ?? 'Details of the Package' );
				$section['details']         = wp_kses_post( $raw_section['details'] ?? '' );
				$section['rates']           = $this->sanitize_rates( $raw_section['rates'] ?? array() );
			} elseif ( 'itinerary' === $layout ) {
				$section['left_heading']  = sanitize_text_field( $raw_section['itinerary_left_heading'] ?? 'Time' );
				$section['right_heading'] = sanitize_text_field( $raw_section['itinerary_right_heading'] ?? 'Activity' );
				$section['itinerary']     = $this->sanitize_itinerary( $raw_section['itinerary'] ?? array() );
			} else {
				$section['content'] = wp_kses_post( $raw_section['content'] ?? '' );
			}

			$sections[] = $section;
		}

		update_post_meta( $post_id, '_ida_sections', $sections );
	}

	/** @return array<int, array<string, string>> */
	private function sanitize_rates( $rows ) {
		$clean = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = sanitize_text_field( $row['label'] ?? '' );
			$value = sanitize_text_field( $row['value'] ?? '' );
			if ( '' !== $label || '' !== $value ) {
				$clean[] = array( 'label' => $label, 'value' => $value );
			}
		}
		return $clean;
	}

	/** @return array<int, array<string, string>> */
	private function sanitize_itinerary( $rows ) {
		$clean = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$item = array(
				'label'      => sanitize_text_field( $row['label'] ?? '' ),
				'summary'    => sanitize_text_field( $row['summary'] ?? '' ),
				'subheading' => sanitize_text_field( $row['subheading'] ?? '' ),
				'bullets'    => sanitize_textarea_field( $row['bullets'] ?? '' ),
			);
			if ( implode( '', $item ) !== '' ) {
				$clean[] = $item;
			}
		}
		return $clean;
	}

	/** Customize list table columns. */
	public function columns( $columns ) {
		return array(
			'cb'            => $columns['cb'],
			'title'         => __( 'Accordion Set', 'island-diaries-accordion' ),
			'ida_shortcode' => __( 'Shortcode', 'island-diaries-accordion' ),
			'date'          => $columns['date'],
		);
	}

	/** Print list table shortcode. */
	public function column_content( $column, $post_id ) {
		if ( 'ida_shortcode' === $column ) {
			echo '<code>[tour_details_accordion id=&quot;' . esc_html( $post_id ) . '&quot;]</code>';
		}
	}

	/** Customize the saved message. */
	public function updated_messages( $messages ) {
		$messages['ida_accordion'] = array_fill( 0, 11, __( 'Accordion set updated.', 'island-diaries-accordion' ) );
		return $messages;
	}
}
