<?php
/**
 * Nursery Notice Board: theme-independent branding and colour controls.
 * These settings are stored in WordPress; no licences or external services.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lbnb_settings_defaults() {
    return array(
        'button_label'     => 'Announcements',
        'eyebrow'          => 'NEWS FROM OUR NURSERY',
        'board_title'      => 'Nursery Notice Board',
        'board_subtitle'   => 'Little notes and updates for our families.',
        'archive_title'    => 'Nursery Announcements',
        'archive_subtitle' => 'Updates, little reminders and important information for our families.',
        'palette_1'        => '#fff0b9',
        'palette_2'        => '#f8e2ec',
        'palette_3'        => '#dff1e8',
        'palette_4'        => '#e1edfc',
        'pin_color'        => '#e88b61',
    );
}

/**
 * Legacy defaults are used exclusively for sites upgrading without a saved
 * settings option. Sites with saved custom branding retain their exact values.
 */
function lbnb_legacy_settings_defaults() {
    $defaults = lbnb_settings_defaults();
    $defaults['board_title'] = 'LittleBloom Notice Board';
    $defaults['archive_title'] = 'LittleBloom Announcements';
    return $defaults;
}

function lbnb_get_settings() {
    $saved = get_option( 'lbnb_settings', array() );
    return wp_parse_args( is_array( $saved ) ? $saved : array(), lbnb_settings_defaults() );
}

function lbnb_palette() {
    $settings = lbnb_get_settings();
    return array( $settings['palette_1'], $settings['palette_2'], $settings['palette_3'], $settings['palette_4'] );
}

function lbnb_clean_hex( $candidate, $fallback = '' ) {
    if ( ! is_string( $candidate ) ) {
        return $fallback;
    }
    $clean = sanitize_hex_color( trim( $candidate ) );
    return $clean ? strtolower( $clean ) : $fallback;
}

function lbnb_sanitize_settings( $input ) {
    $defaults = lbnb_settings_defaults();
    if ( ! is_array( $input ) ) {
        return $defaults;
    }
    $out = array();
    foreach ( $defaults as $key => $default ) {
        if ( 0 === strpos( $key, 'palette_' ) || 'pin_color' === $key ) {
            $out[ $key ] = lbnb_clean_hex( isset( $input[ $key ] ) ? $input[ $key ] : '', $default );
        } else {
            $out[ $key ] = isset( $input[ $key ] ) && is_string( $input[ $key ] )
                ? sanitize_text_field( $input[ $key ] )
                : $default;
            $out[ $key ] = substr( $out[ $key ], 0, 140 );
        }
    }
    return $out;
}

function lbnb_register_settings() {
    register_setting( 'lbnb_settings_group', 'lbnb_settings', array(
        'type'              => 'array',
        'sanitize_callback' => 'lbnb_sanitize_settings',
        'default'           => lbnb_settings_defaults(),
    ) );
}
add_action( 'admin_init', 'lbnb_register_settings' );

function lbnb_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=littlebloom_notice',
        __( 'Notice Board Settings', 'littlebloom-notice-board' ),
        __( 'Settings', 'littlebloom-notice-board' ),
        'manage_options',
        'lbnb-settings',
        'lbnb_settings_page'
    );
}
add_action( 'admin_menu', 'lbnb_admin_menu' );

function lbnb_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $settings = lbnb_get_settings();
    $text_fields = array(
        'button_label'     => __( 'Button label', 'littlebloom-notice-board' ),
        'eyebrow'          => __( 'Small heading above title', 'littlebloom-notice-board' ),
        'board_title'      => __( 'Popup title', 'littlebloom-notice-board' ),
        'board_subtitle'   => __( 'Popup description', 'littlebloom-notice-board' ),
        'archive_title'    => __( 'Archive title', 'littlebloom-notice-board' ),
        'archive_subtitle' => __( 'Archive description', 'littlebloom-notice-board' ),
    );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Nursery Notice Board Settings', 'littlebloom-notice-board' ); ?></h1>
        <p><?php esc_html_e( 'Customize the public-facing text and the four rotating card colours. These settings apply to all themes.', 'littlebloom-notice-board' ); ?></p>
        <?php if ( function_exists( 'lbnb_archive_advanced_filters_enabled' ) && ! lbnb_archive_advanced_filters_enabled() ) : ?>
            <div class="notice notice-info inline">
                <p><strong><?php esc_html_e( 'Need more control over your announcements?', 'littlebloom-notice-board' ); ?></strong>
                <?php esc_html_e( 'Nursery Notice Board Pro adds archive search, date and category filters, flexible sorting, featured notices and display scheduling.', 'littlebloom-notice-board' ); ?></p>
            </div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'lbnb_settings_group' ); ?>
            <h2><?php esc_html_e( 'Branding', 'littlebloom-notice-board' ); ?></h2>
            <table class="form-table" role="presentation"><tbody>
            <?php foreach ( $text_fields as $key => $label ) : ?>
                <tr><th scope="row"><label for="lbnb-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
                    <td><input class="regular-text" maxlength="140" id="lbnb-<?php echo esc_attr( $key ); ?>" name="lbnb_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings[ $key ] ); ?>" type="text" /></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <h2><?php esc_html_e( 'Sticky-card colours', 'littlebloom-notice-board' ); ?></h2>
            <p><?php esc_html_e( 'These default colours rotate across cards. Individual notices can override them in the notice editor.', 'littlebloom-notice-board' ); ?></p>
            <table class="form-table" role="presentation"><tbody>
            <?php foreach ( array( 'palette_1' => 'Sticky card 1', 'palette_2' => 'Sticky card 2', 'palette_3' => 'Sticky card 3', 'palette_4' => 'Sticky card 4', 'pin_color' => 'Pin icon colour' ) as $key => $label ) : ?>
                <tr><th scope="row"><label for="lbnb-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
                    <td><input id="lbnb-<?php echo esc_attr( $key ); ?>" name="lbnb_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings[ $key ] ); ?>" type="color" /></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <?php submit_button( __( 'Save Notice Board Settings', 'littlebloom-notice-board' ) ); ?>
        </form>
    </div>
    <?php
}

function lbnb_notice_color_meta_box() {
    add_meta_box(
        'lbnb-notice-colours',
        __( 'Sticky Card and Pin Colours', 'littlebloom-notice-board' ),
        'lbnb_notice_color_fields',
        'littlebloom_notice',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'lbnb_notice_color_meta_box' );

function lbnb_notice_color_fields( $post ) {
    wp_nonce_field( 'lbnb_save_colours', 'lbnb_colours_nonce' );
    $card = lbnb_clean_hex( get_post_meta( $post->ID, '_lbnb_card_color', true ) );
    $pin  = lbnb_clean_hex( get_post_meta( $post->ID, '_lbnb_pin_color', true ) );
    $settings = lbnb_get_settings();
    ?>
    <p><label><input type="checkbox" name="lbnb_override_card" value="1" <?php checked( (bool) $card ); ?> /> <?php esc_html_e( 'Use a custom card colour', 'littlebloom-notice-board' ); ?></label></p>
    <p><input type="color" name="lbnb_card_color" value="<?php echo esc_attr( $card ? $card : $settings['palette_1'] ); ?>" /> </p>
    <p><label><input type="checkbox" name="lbnb_override_pin" value="1" <?php checked( (bool) $pin ); ?> /> <?php esc_html_e( 'Use a custom pin colour', 'littlebloom-notice-board' ); ?></label></p>
    <p><input type="color" name="lbnb_pin_color" value="<?php echo esc_attr( $pin ? $pin : $settings['pin_color'] ); ?>" /></p>
    <p class="description"><?php esc_html_e( 'Tick each checkbox to save its colour for this notice. Untick to use the global palette.', 'littlebloom-notice-board' ); ?></p>
    <?php
}

function lbnb_save_notice_colours( $post_id ) {
    if ( ! isset( $_POST['lbnb_colours_nonce'] ) || ! is_string( $_POST['lbnb_colours_nonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lbnb_colours_nonce'] ) ), 'lbnb_save_colours' ) ||
        ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
        wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    foreach ( array( 'card' => '_lbnb_card_color', 'pin' => '_lbnb_pin_color' ) as $kind => $meta_key ) {
        $checkbox = 'lbnb_override_' . $kind;
        $field = 'lbnb_' . $kind . '_color';
        $value = isset( $_POST[ $field ] ) && is_string( $_POST[ $field ] )
            ? lbnb_clean_hex( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) )
            : '';
        if ( ! empty( $_POST[ $checkbox ] ) && $value ) {
            update_post_meta( $post_id, $meta_key, $value );
        } else {
            delete_post_meta( $post_id, $meta_key );
        }
    }
}
add_action( 'save_post_littlebloom_notice', 'lbnb_save_notice_colours' );

function lbnb_note_style( $post_id ) {
    $settings = lbnb_get_settings();
    $card = lbnb_clean_hex( get_post_meta( $post_id, '_lbnb_card_color', true ) );
    $pin  = lbnb_clean_hex( get_post_meta( $post_id, '_lbnb_pin_color', true ) );
    $pieces = array();
    if ( $card ) {
        $pieces[] = '--lbnb-card-color:' . $card;
    }
    if ( $pin ) {
        $pieces[] = '--lbnb-pin-color:' . $pin;
    }
    return implode( ';', $pieces );
}

function lbnb_palette_attribute() {
    // Escape at the HTML attribute output site, not inside this data helper.
    return wp_json_encode( lbnb_palette() );
}

function lbnb_pin_control( $post_id ) {
    /* translators: %s: title of the nursery notice whose card colour is previewed. */
    $label = sprintf( __( 'Change card colour for %s (preview only)', 'littlebloom-notice-board' ), get_the_title( $post_id ) );
    return '<button class="lbnb-card-pin" type="button" aria-label="' . esc_attr( $label ) . '" title="' . esc_attr( $label ) . '">' .
        '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 3h6l-1 5 3.5 3.5v2H6.5v-2L10 8 9 3Z" fill="currentColor"/><path d="M12 13.5v7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' .
        '</button>';
}

/**
 * Elements and attributes allowed in the notice pin icon.
 * The pin is generated by the plugin, but whitelist its HTML on output.
 *
 * @return array<string, array<string, bool>>
 */
function lbnb_pin_allowed_html() {
    return array(
        'button' => array(
            'class'      => true,
            'type'       => true,
            'aria-label' => true,
            'title'      => true,
        ),
        'svg' => array(
            'viewbox'     => true,
            'aria-hidden' => true,
            'focusable'   => true,
        ),
        'path' => array(
            'd'                => true,
            'fill'             => true,
            'stroke'           => true,
            'stroke-width'     => true,
            'stroke-linecap'   => true,
        ),
    );
}

function lbnb_palette_style() {
    $settings = lbnb_get_settings();
    $styles = array();
    foreach ( array( 'palette_1' => '--lbnb-palette-1', 'palette_2' => '--lbnb-palette-2', 'palette_3' => '--lbnb-palette-3', 'palette_4' => '--lbnb-palette-4', 'pin_color' => '--lbnb-default-pin-color' ) as $key => $css_variable ) {
        $defaults = lbnb_settings_defaults();
        $styles[] = $css_variable . ':' . lbnb_clean_hex( $settings[ $key ], $defaults[ $key ] );
    }
    return implode( ';', $styles );
}
