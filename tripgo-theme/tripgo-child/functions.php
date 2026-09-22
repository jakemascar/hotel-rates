<?php
/**
 * Setup tripgo Child Theme's textdomain.
 *
 * Declare textdomain for this child theme.
 * Translations can be filed in the /languages/ directory.
 */
 
function tripgo_child_theme_setup() {
	load_child_theme_textdomain( 'tripgo-child', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'tripgo_child_theme_setup' );


add_action( 'wp_enqueue_scripts', 'tripgo_enqueue_styles' );
function tripgo_enqueue_styles() {
    $parenthandle = 'tripgo-style'; // This is 'twentyfifteen-style' for the Twenty Fifteen theme.
    $theme = wp_get_theme();
    wp_enqueue_style( $parenthandle, get_template_directory_uri() . '/style.css', 
        array(),  // if the parent theme code has a dependency, copy it to here
        $theme->parent()->get('Version')
    );
    wp_enqueue_style( 'child-style', get_stylesheet_uri(),
        array( $parenthandle ),
        $theme->get('Version') // this only works if you have Version in the style header
    );
}

/**
 * Allow visitors to drag the Chaty launcher away from covered content.
 */
add_action( 'wp_enqueue_scripts', 'island_diaries_enqueue_chaty_draggable', 100 );
function island_diaries_enqueue_chaty_draggable() {
    $script_path = get_stylesheet_directory() . '/assets/js/chaty-draggable.js';

    if ( ! file_exists( $script_path ) ) {
        return;
    }

    wp_enqueue_script(
        'island-diaries-chaty-draggable',
        get_stylesheet_directory_uri() . '/assets/js/chaty-draggable.js',
        array(),
        (string) filemtime( $script_path ),
        true
    );
}

add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );

// Export Custom Taxonomy and Custom Checkout Fields
add_action( 'rss2_head', function() {
    if ( is_admin() ) {
        // Custom Taxonomies
        $custom_taxonomies = recursive_array_replace( '\\', '', get_option( 'ovabrw_custom_taxonomy', [] ) );

        if ( ! empty( $custom_taxonomies ) && is_array( $custom_taxonomies ) ) {
            foreach ( $custom_taxonomies as $slug => $items ) {
                echo "<ovabrw_custom_taxonomies>\n";
                    if ( $slug ) echo "\t<slug>".$slug."</slug>\n";
                    if ( $items['name'] ) echo "\t<name>".$items['name']."</name>\n";
                    if ( $items['singular_name'] ) echo "\t<singular_name>".$items['singular_name']."</singular_name>\n";
                    if ( $items['label_frontend'] ) echo "\t<label_frontend>".$items['label_frontend']."</label_frontend>\n";
                    if ( $items['enabled'] ) echo "\t<enabled>".$items['enabled']."</enabled>\n";
                    if ( $items['show_listing'] ) echo "\t<show_listing>".$items['show_listing']."</show_listing>\n";
                echo "</ovabrw_custom_taxonomies>\n";
            }
        }

        // Custom Checkout Fields
        $checkout_fields = recursive_array_replace( '\\', '', get_option( 'ovabrw_booking_form', [] ) );

        if ( ! empty( $checkout_fields ) && is_array( $checkout_fields ) ) {
            foreach ( $checkout_fields as $slug => $items ) {
                // Select
                $options_key    = isset( $items['ova_options_key'] ) && $items['ova_options_key'] ? $items['ova_options_key'] : '';
                $options_text   = isset( $items['ova_options_text'] ) && $items['ova_options_text'] ? $items['ova_options_text'] : '';
                $options_price  = isset( $items['ova_options_price'] ) && $items['ova_options_price'] ? $items['ova_options_price'] : '';

                // Radio
                $radio_values   = isset( $items['ova_radio_values'] ) && $items['ova_radio_values'] ? $items['ova_radio_values'] : '';
                $radio_prices   = isset( $items['ova_radio_prices'] ) && $items['ova_radio_prices'] ? $items['ova_radio_prices'] : '';

                // Checkbox
                $checkbox_key   = isset( $items['ova_checkbox_key'] ) && $items['ova_checkbox_key'] ? $items['ova_checkbox_key'] : '';
                $checkbox_text  = isset( $items['ova_checkbox_text'] ) && $items['ova_checkbox_text'] ? $items['ova_checkbox_text'] : '';
                $checkbox_price = isset( $items['ova_checkbox_price'] ) && $items['ova_checkbox_price'] ? $items['ova_checkbox_price'] : '';

                // File
                $max_file_size  = isset( $items['max_file_size'] ) && $items['max_file_size'] ? $items['max_file_size'] : '';

                echo "<ovabrw_custom_checkout_fields>\n";
                    if ( $slug ) echo "\t<slug>".$slug."</slug>\n";
                    if ( $items['type'] ) echo "\t<type>".$items['type']."</type>\n";
                    if ( $items['label'] ) echo "\t<label>".$items['label']."</label>\n";
                    if ( $items['default'] ) echo "\t<default>".$items['default']."</default>\n";
                    if ( $items['placeholder'] ) echo "\t<placeholder>".$items['placeholder']."</placeholder>\n";
                    if ( $items['class'] ) echo "\t<class>".$items['class']."</class>\n";
                    if ( $items['required'] ) echo "\t<required>".$items['required']."</required>\n";
                    if ( $items['enabled'] ) echo "\t<enabled>".$items['enabled']."</enabled>\n";
                    
                    // Select Keys
                    if ( ! empty( $options_key ) && is_array( $options_key ) ) {
                        echo "\t<select_keys>".implode( '|', $options_key )."</select_keys>\n";
                    }
                    // Select Texts
                    if ( ! empty( $options_text ) && is_array( $options_text ) ) {
                        echo "\t<select_texts>".implode( '|', $options_text )."</select_texts>\n";
                    }
                    // Select Prices
                    if ( ! empty( $options_price ) && is_array( $options_price ) ) {
                        echo "\t<select_prices>".implode( '|', $options_price )."</select_prices>\n";
                    }
                    // Radio Values
                    if ( ! empty( $radio_values ) && is_array( $radio_values ) ) {
                        echo "\t<radio_values>".implode( '|', $radio_values )."</radio_values>\n";
                    }
                    // Radio Prices
                    if ( ! empty( $radio_prices ) && is_array( $radio_prices ) ) {
                        echo "\t<radio_prices>".implode( '|', $radio_prices )."</radio_prices>\n";
                    }
                    // Checkbox Keys
                    if ( ! empty( $checkbox_key ) && is_array( $checkbox_key ) ) {
                        echo "\t<checkbox_keys>".implode( '|', $checkbox_key )."</checkbox_keys>\n";
                    }
                    // Checkbox Texts
                    if ( ! empty( $checkbox_text ) && is_array( $checkbox_text ) ) {
                        echo "\t<checkbox_texts>".implode( '|', $checkbox_text )."</checkbox_texts>\n";
                    }
                    // Checkbox Prices
                    if ( ! empty( $checkbox_price ) && is_array( $checkbox_price ) ) {
                        echo "\t<checkbox_prices>".implode( '|', $checkbox_price )."</checkbox_prices>\n";
                    }
                    // Max File Size
                    if ( $max_file_size ) {
                        echo "\t<max_file_size>".$max_file_size."</max_file_size>\n";
                    }
                echo "</ovabrw_custom_checkout_fields>\n";
            }
        }
    }
});


// kikid 
// implement your “Booking Form” button inside the Publish box using WooCommerce + ACF + a form plugin (e.g., Contact Form 7):

add_action('admin_head', function() {
    global $post;
    if (isset($post) && $post->post_type === 'product') {
        echo '<style>
        #submitdiv .button,
        #submitdiv .button-primary {
            width: 100% !important;
            padding: 5px !important;
            border-radius: 6px !important;
            font-weight: 600 !important;
            font-size: 15px !important;
        }
        </style>';
    }
});

add_action('admin_footer', 'move_booking_button_top');
function move_booking_button_top() {
    global $post;

    if (!$post || $post->post_type !== 'product') return;

    // ✅ Get ACF field
    $shortcode = get_field('booking_form_shortcode', $post->ID);

    // ❌ If empty OR post not saved yet → don't show button
    if (empty($shortcode) || $post->post_status === 'auto-draft') return;
?>
<script>
jQuery(document).ready(function($) {

    // ✅ Create button safely
    if (!$('#open-booking-form').length) {
        const button = $('<button>', {
            text: 'Booking Form',
            id: 'open-booking-form',
            class: 'button button-primary',
            type: 'button', // ✅ IMPORTANT FIX
            css: { width: '100%', marginBottom: '10px', background: '#1e40af', padding: '5px', fontWeight: '600', fontSize: '15px', borderRadius: '6px', }
        });

        $('.submitbox .preview.button').first().before(button);
    }

    // ✅ Delegated click (works even if button is injected later)
    $(document).on('click', '#open-booking-form', function(e){
        e.preventDefault();      // ✅ stop form submit
        e.stopPropagation();     // ✅ extra safety
        $('#booking-form-modal').fadeIn();
    });

    // ✅ Close modal
    $(document).on('click', '.close-modal', function(){
        $('#booking-form-modal').fadeOut();
    });

    // ✅ Click outside to close
    $(document).on('click', function(e){
        if ($(e.target).is('#booking-form-modal')) {
            $('#booking-form-modal').fadeOut();
        }
    });

});
</script>
<?php
}


add_action('admin_footer', 'booking_form_modal_html');
function booking_form_modal_html() {
    global $post;

    if ($post->post_type !== 'product') return;

    $shortcode = get_field('booking_form_shortcode', $post->ID);

    ?>
    <div id="booking-form-modal" style="display:none;">
        <div class="booking-modal-content">
            <span class="close-modal">&times;</span>
            <?php echo do_shortcode($shortcode); ?>
        </div>
    </div>
    <?php
}

add_action('admin_footer', 'booking_form_modal_script');
function booking_form_modal_script() {
    global $post;

    if ($post->post_type !== 'product') return;
?>
<style>
#booking-form-modal {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 9999;
}
.booking-modal-content {
    background: #fff;
    padding: 20px;
    width: 600px;
    margin: 10% auto;
    position: relative;
}
.close-modal {
    position: absolute;
    top: 10px;
    right: 15px;
    cursor: pointer;
    font-size: 20px;
}
</style>

<script>
jQuery(document).ready(function($){
    $('#open-booking-form').on('click', function(){
        $('#booking-form-modal').fadeIn();
    });

    $('.close-modal').on('click', function(){
        $('#booking-form-modal').fadeOut();
    });

    $(document).on('click', function(e){
        if ($(e.target).is('#booking-form-modal')) {
            $('#booking-form-modal').fadeOut();
        }
    });
});
</script>

<?php
}