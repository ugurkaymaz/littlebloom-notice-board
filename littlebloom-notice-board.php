<?php
/**
 * Plugin Name: LittleBloom Notice Board
 * Description: Customisable nursery notice board with sticky notes and a simple archive. Advanced filtering is available with the Pro add-on.
 * Version: 0.3.6
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: LittleBloom
 * Text Domain: littlebloom-notice-board
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Freemius SDK initialization for Nursery Notice Board (parent Free plugin).
 *
 * Freemius SDK files are bundled locally under vendor/freemius.
 * The Free edition works without a paid licence or mandatory account.
 */
if ( ! function_exists( 'lbnb_freemius' ) ) {
    // Create a helper function for early SDK access.
    function lbnb_freemius() {
        global $lbnb_freemius;

        if ( ! isset( $lbnb_freemius ) ) {
            require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';

            $lbnb_freemius = fs_dynamic_init( array(
                'id'               => '42743',
                'slug'             => 'littlebloom-notice-board',
                'type'             => 'plugin',
                'public_key'       => 'pk_fd89b1a1d48ed3715b58982afccee',
                'is_premium'       => false,
                'has_addons'       => true,
                'has_paid_plans'   => false,
                'is_org_compliant' => true,
                'menu'             => array(
                    'slug'    => 'lbnb-settings',
                    'account' => false,
                    'support' => false,
                    'parent'  => array(
                        'slug' => 'edit.php?post_type=littlebloom_notice',
                    ),
                ),
            ) );
        }

        return $lbnb_freemius;
    }

    // Initialize Freemius before the plugin registers its WordPress hooks.
    lbnb_freemius();
    do_action( 'lbnb_freemius_loaded' );
}

if ( defined( 'LBNB_VERSION' ) ) {
    return;
}
define( 'LBNB_VERSION', '0.3.6' );
define( 'LBNB_EDITION', 'free' );
define( 'LBNB_PLUGIN_FILE', __FILE__ );
require_once plugin_dir_path( __FILE__ ) . 'includes/settings-and-colors.php';

/**
 * First-run migration for existing sites only. Preserve pre-v0.3.0 brand text
 * where the original notice board was installed but no settings were saved.
 * Fresh sites get the neutral Nursery Notice Board defaults.
 */
function lbnb_preserve_legacy_branding() {
    if ( false !== get_option( 'lbnb_free_install_marker', false ) ) {
        return;
    }

    if ( false === get_option( 'lbnb_settings', false ) &&
         ( get_option( 'lbnb_archive_page_id', 0 ) || get_option( 'littlebloom_step628_page_id', 0 ) ) ) {
        add_option( 'lbnb_settings', lbnb_legacy_settings_defaults(), '', false );
    }

    add_option( 'lbnb_free_install_marker', '0.3.0', '', false );
}
add_action( 'init', 'lbnb_preserve_legacy_branding', 11 );

/**
 * Extensibility marker for companion add-ons. There is no paid-code lock,
 * trial expiry, activation requirement or remote call in the Free edition.
 */
function lbnb_fire_ready_action() {
    do_action( 'lbnb_free_ready', LBNB_VERSION );
}
add_action( 'init', 'lbnb_fire_ready_action', 25 );

/**
 * Preserve the original post type key. Existing Step 627 notices must remain usable.
 */
function lbnb_register_post_type() {
    register_post_type( 'littlebloom_notice', array(
        'labels' => array(
            'name'          => __( 'Nursery Notices', 'littlebloom-notice-board' ),
            'singular_name' => __( 'Nursery Notice', 'littlebloom-notice-board' ),
            'add_new_item'  => __( 'Add Nursery Notice', 'littlebloom-notice-board' ),
            'edit_item'     => __( 'Edit Nursery Notice', 'littlebloom-notice-board' ),
            'all_items'     => __( 'All Nursery Notices', 'littlebloom-notice-board' ),
            'menu_name'     => __( 'Nursery Notices', 'littlebloom-notice-board' ),
        ),
        'description'        => __( 'Nursery announcements and family updates.', 'littlebloom-notice-board' ),
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-megaphone',
        'menu_position'      => 22,
        'supports'           => array( 'title', 'editor', 'excerpt' ),
        'has_archive'        => false,
        'rewrite'            => false,
    ) );
}
add_action( 'init', 'lbnb_register_post_type', 10 );

/**
 * Keep notice content private to editors in the raw WordPress REST API.
 *
 * The public notice board and archive use separately rendered, filtered
 * queries. Showing a published notice in REST would bypass a Pro add-on's
 * scheduled visibility window. Gutenberg and authorised editors still have
 * their normal REST access.
 *
 * Do not change show_in_rest: Gutenberg needs that setting.
 *
 * @param mixed           $result  Pre-dispatch result from other filters.
 * @param WP_REST_Server  $server  REST server instance.
 * @param WP_REST_Request $request Current REST request.
 * @return mixed|WP_Error
 */
function lbnb_guard_public_notice_rest( $result, $server, $request ) {
    if ( null !== $result || ! ( $request instanceof WP_REST_Request ) ) {
        return $result;
    }

    $route = $request->get_route();
    $base  = '/wp/v2/littlebloom_notice';
    if ( $base !== $route && 0 !== strpos( $route, $base . '/' ) ) {
        return $result;
    }

    $method = strtoupper( (string) $request->get_method() );
    if ( $base === $route ) {
        // The collection can disclose every published notice. Editors may
        // list it; authors/contributors can still submit a new record.
        $allowed = 'POST' === $method
            ? current_user_can( 'edit_posts' )
            : current_user_can( 'edit_others_posts' );
    } elseif ( preg_match( '#^/wp/v2/littlebloom_notice/([0-9]+)(?:/|$)#D', $route, $matches ) ) {
        // An author may access only records WordPress allows them to edit.
        $allowed = current_user_can( 'edit_post', (int) $matches[1] );
    } else {
        // Unknown child routes stay private to users who can edit all notices.
        $allowed = current_user_can( 'edit_others_posts' );
    }

    if ( $allowed ) {
        return $result;
    }

    return new WP_Error(
        'lbnb_rest_editor_only',
        __( 'Nursery notice records are available only to authorised editors through the REST API.', 'littlebloom-notice-board' ),
        array( 'status' => rest_authorization_required_code() )
    );
}
add_filter( 'rest_pre_dispatch', 'lbnb_guard_public_notice_rest', 10, 3 );


/**
 * Preserve Pro-created display windows even when the Pro add-on is disabled.
 * The metadata uses site-local, sortable YYYY-MM-DD HH:MM:SS strings.
 * This is a public display rule, not a way to store confidential content.
 */
function lbnb_schedule_window_meta_query() {
    $now = current_datetime()->format( 'Y-m-d H:i:s' );
    return array(
        'relation' => 'AND',
        array(
            'relation' => 'OR',
            array( 'key' => '_nnbpro_start_at', 'compare' => 'NOT EXISTS' ),
            array( 'key' => '_nnbpro_start_at', 'value' => '', 'compare' => '=' ),
            array( 'key' => '_nnbpro_start_at', 'value' => $now, 'compare' => '<=', 'type' => 'CHAR' ),
        ),
        array(
            'relation' => 'OR',
            array( 'key' => '_nnbpro_end_at', 'compare' => 'NOT EXISTS' ),
            array( 'key' => '_nnbpro_end_at', 'value' => '', 'compare' => '=' ),
            array( 'key' => '_nnbpro_end_at', 'value' => $now, 'compare' => '>=', 'type' => 'CHAR' ),
        ),
    );
}

/** Find schedule keys recursively in a WordPress meta_query. */
function lbnb_meta_query_has_keys( $clauses, $keys ) {
    if ( ! is_array( $clauses ) ) {
        return false;
    }
    lbnb_collect_meta_query_keys( $clauses, $keys );
    return isset( $keys['_nnbpro_start_at'], $keys['_nnbpro_end_at'] );
}

/** Collect meta keys without altering the query. */
function lbnb_collect_meta_query_keys( $clauses, &$keys ) {
    if ( isset( $clauses['key'] ) && is_string( $clauses['key'] ) ) {
        $keys[ $clauses['key'] ] = true;
    }
    foreach ( $clauses as $clause ) {
        if ( is_array( $clause ) ) {
            lbnb_collect_meta_query_keys( $clause, $keys );
        }
    }
}

/**
 * Apply saved display windows to all dedicated front-end notice WP_Queries.
 * Pro v0.5.1 already provides this same rule to popup/archive queries; skip
 * duplicate clauses when both window keys are already present.
 *
 * Do not change admin queries or authenticated Gutenberg REST editor queries.
 */
function lbnb_enforce_notice_schedule( $query ) {
    if ( ! ( $query instanceof WP_Query ) || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return;
    }

    $post_type = $query->get( 'post_type' );
    if ( 'littlebloom_notice' !== $post_type && array( 'littlebloom_notice' ) !== $post_type ) {
        return;
    }

    $meta_query = $query->get( 'meta_query' );
    $meta_query = is_array( $meta_query ) ? $meta_query : array();
    if ( lbnb_meta_query_has_keys( $meta_query, array() ) ) {
        return;
    }

    $window = lbnb_schedule_window_meta_query();
    $query->set( 'meta_query', $meta_query
        ? array( 'relation' => 'AND', $meta_query, $window )
        : $window );
}
add_action( 'pre_get_posts', 'lbnb_enforce_notice_schedule', 100 );


/**
 * Step 627/628 originally lived in the theme. Suppress those hooks while this
 * plugin is active; neither delete legacy theme files nor alter stored notices.
 * Runs after WordPress has loaded the theme functions.php file.
 */
function lbnb_disable_legacy_theme_hooks() {
    remove_action( 'init', 'littlebloom_step627_register_notices' );
    remove_action( 'init', 'littlebloom_step628_create_page', 30 );
    remove_action( 'wp_enqueue_scripts', 'littlebloom_step627_enqueue_assets', 90 );
    remove_action( 'wp_enqueue_scripts', 'littlebloom_step628_enqueue', 110 );
    remove_action( 'wp_footer', 'littlebloom_step627_render_panel', 20 );
    remove_filter( 'body_class', 'littlebloom_step628_body_class', 10 );
    remove_shortcode( 'littlebloom_notices_archive' );
    // The new shortcode is registered below on init, once the theme has loaded.
}
add_action( 'after_setup_theme', 'lbnb_disable_legacy_theme_hooks', 100 );

function lbnb_valid_archive_page( $page_id ) {
    $page_id = absint( $page_id );
    if ( ! $page_id || 'page' !== get_post_type( $page_id ) || 'publish' !== get_post_status( $page_id ) ) {
        return 0;
    }
    $page = get_post( $page_id );
    return $page && has_shortcode( $page->post_content, 'littlebloom_notices_archive' ) ? $page_id : 0;
}

function lbnb_get_archive_page_id() {
    // Important: honour the existing theme-managed Step 628 page.
    $existing = lbnb_valid_archive_page( get_option( 'littlebloom_step628_page_id', 0 ) );
    if ( $existing ) {
        return $existing;
    }
    return lbnb_valid_archive_page( get_option( 'lbnb_archive_page_id', 0 ) );
}

/**
 * Create only when no matching page exists. Never overwrite customer pages.
 * Called at activation, not on every public page view.
 */
function lbnb_ensure_archive_page() {
    if ( lbnb_get_archive_page_id() ) {
        return;
    }
    foreach ( array( 'announcements', 'nursery-announcements' ) as $slug ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( $page && 'publish' === $page->post_status && has_shortcode( $page->post_content, 'littlebloom_notices_archive' ) ) {
            update_option( 'lbnb_archive_page_id', (int) $page->ID, false );
            return;
        }
    }
    $slug = 'announcements';
    if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
        $slug = 'nursery-announcements';
    }
    if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
        // Existing pages belong to the site owner. Leave them untouched.
        return;
    }
    $page_id = wp_insert_post( array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'post_title'     => __( 'Announcements', 'littlebloom-notice-board' ),
        'post_name'      => $slug,
        'post_content'   => "<!-- wp:shortcode -->\n[littlebloom_notices_archive]\n<!-- /wp:shortcode -->",
        'comment_status' => 'closed',
    ), true );
    if ( ! is_wp_error( $page_id ) && $page_id ) {
        update_option( 'lbnb_archive_page_id', (int) $page_id, false );
    }
}

function lbnb_on_activation() {
    lbnb_preserve_legacy_branding();
    lbnb_register_post_type();
    lbnb_ensure_archive_page();
    // Notices are not publicly queryable; no custom rewrite needed.
}
register_activation_hook( __FILE__, 'lbnb_on_activation' );

/**
 * Preserve all notices, pages, options and shortcode content on deactivation.
 */
function lbnb_on_deactivation() {
    // No destructive operations.
}
register_deactivation_hook( __FILE__, 'lbnb_on_deactivation' );

function lbnb_enqueue_assets() {
    $base = plugin_dir_url( LBNB_PLUGIN_FILE );
    wp_enqueue_style( 'lbnb-notice-board', $base . 'assets/css/notice-board.css', array(), LBNB_VERSION );
    wp_enqueue_style( 'lbnb-notice-archive', $base . 'assets/css/notice-archive.css', array(), LBNB_VERSION );
    wp_enqueue_script( 'lbnb-notice-board', $base . 'assets/js/notice-board.js', array(), LBNB_VERSION, true );
    wp_enqueue_script( 'lbnb-card-colors', $base . 'assets/js/card-colors.js', array(), LBNB_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'lbnb_enqueue_assets', 100 );

/**
 * Can be embedded in the header of any WordPress theme.
 * LittleBloom's original Announcements header control is supported automatically.
 */
function lbnb_trigger_shortcode() {
    return '<button class="lbnb-trigger" type="button" aria-controls="lb-notice-board-627" aria-expanded="false">' . esc_html( lbnb_get_settings()['button_label'] ) . '</button>';
}

function lbnb_register_shortcodes() {
    // Override the theme version only while this plugin is active.
    add_shortcode( 'littlebloom_notices_archive', 'lbnb_render_archive' );
    add_shortcode( 'littlebloom_notice_button', 'lbnb_trigger_shortcode' );
}
add_action( 'init', 'lbnb_register_shortcodes', 20 );

function lbnb_render_popup() {
    $popup_query_args = array(
        'post_type'           => 'littlebloom_notice',
        'post_status'         => 'publish',
        'numberposts'         => 4,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'suppress_filters'    => false,
    );
    // Extend display rules without changing the Free package or its database.
    $popup_query_args = apply_filters( 'lbnb_popup_query_args', $popup_query_args );
    $notices = get_posts( $popup_query_args );
    $page_id = lbnb_get_archive_page_id();
    $settings = lbnb_get_settings();
    ?>
    <aside id="lb-notice-board-627" class="lb-notice-board-627" hidden style="<?php echo esc_attr( lbnb_palette_style() ); ?>" data-lbnb-palette="<?php echo esc_attr( lbnb_palette_attribute() ); ?>"
           role="region" aria-label="<?php echo esc_attr__( 'Nursery announcements', 'littlebloom-notice-board' ); ?>" tabindex="-1">
        <div class="lb-notice-board-627__heading">
            <div>
                <span class="lb-notice-board-627__eyebrow"><?php echo esc_html( $settings['eyebrow'] ); ?></span>
                <h2><?php echo esc_html( $settings['board_title'] ); ?></h2>
                <p><?php echo esc_html( $settings['board_subtitle'] ); ?></p>
            </div>
            <button type="button" class="lb-notice-board-627__close" aria-label="<?php echo esc_attr__( 'Close announcements', 'littlebloom-notice-board' ); ?>">&times;</button>
        </div>
        <?php if ( $notices ) : ?>
            <div class="lb-notice-board-627__grid">
                <?php foreach ( $notices as $notice ) :
                    $excerpt = has_excerpt( $notice ) ? get_the_excerpt( $notice ) : wp_trim_words( wp_strip_all_tags( $notice->post_content ), 28, '…' );
                    ?>
                    <article class="lb-notice-board-627__note" style="<?php echo esc_attr( lbnb_note_style( $notice->ID ) ); ?>">
                        <?php echo wp_kses( lbnb_pin_control( $notice->ID ), lbnb_pin_allowed_html() ); ?>
                        <time datetime="<?php echo esc_attr( get_the_date( 'c', $notice ) ); ?>"><?php echo esc_html( get_the_date( '', $notice ) ); ?></time>
                        <h3><?php echo esc_html( get_the_title( $notice ) ); ?></h3>
                        <?php if ( '' !== $excerpt ) : ?><p><?php echo esc_html( wp_strip_all_tags( $excerpt ) ); ?></p><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="lb-notice-board-627__empty">
                <span aria-hidden="true">📌</span>
                <p><?php esc_html_e( 'No new announcements at the moment.', 'littlebloom-notice-board' ); ?></p>
                <p><?php esc_html_e( 'Please check back for nursery news and family updates.', 'littlebloom-notice-board' ); ?></p>
            </div>
        <?php endif; ?>
        <?php if ( $page_id ) : ?>
            <div class="lb-notice-board-628__archive">
                <a class="lb-notice-board-628__archive-link" href="<?php echo esc_url( get_permalink( $page_id ) ); ?>"><?php esc_html_e( 'View All Announcements', 'littlebloom-notice-board' ); ?></a>
            </div>
        <?php endif; ?>
    </aside>
    <?php
}
add_action( 'wp_footer', 'lbnb_render_popup', 20 );

function lbnb_valid_date_param( $value ) {
    if ( ! is_string( $value ) ) {
        return '';
    }
    $value = sanitize_text_field( $value );
    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $value ) ) {
        return '';
    }
    list( $year, $month, $day ) = array_map( 'intval', explode( '-', $value ) );
    return checkdate( $month, $day, $year ) ? $value : '';
}

/**
 * Show archive search, date filters and sorting only while the Pro archive
 * module is running. This bridges the existing Pro v0.5.1 callback without
 * requiring a Pro update or adding inactive premium features to Free.
 *
 * The extensibility hook allows a compatible companion to opt in later.
 * Advanced filter parameters are ignored when this returns false; hiding the
 * form alone is not enough to separate Free and Pro functionality.
 */
function lbnb_archive_advanced_filters_enabled() {
    $pro_archive_loaded = false !== has_filter( 'lbnb_archive_query_args', 'nnbpro_archive_query_args' );
    return (bool) apply_filters( 'lbnb_archive_advanced_filters_enabled', $pro_archive_loaded );
}

function lbnb_render_archive() {
    $settings = lbnb_get_settings();
    // The public archive GET parameters are read-only filters; no state is changed.
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Public search and pagination require no nonce.
    $from = isset( $_GET['lb_notice_from'] ) && is_string( $_GET['lb_notice_from'] )
        ? lbnb_valid_date_param( sanitize_text_field( wp_unslash( $_GET['lb_notice_from'] ) ) ) : '';
    $to = isset( $_GET['lb_notice_to'] ) && is_string( $_GET['lb_notice_to'] )
        ? lbnb_valid_date_param( sanitize_text_field( wp_unslash( $_GET['lb_notice_to'] ) ) ) : '';
    $search = isset( $_GET['lb_notice_search'] ) && is_string( $_GET['lb_notice_search'] )
        ? substr( sanitize_text_field( wp_unslash( $_GET['lb_notice_search'] ) ), 0, 100 ) : '';
    $sort_by = isset( $_GET['lb_notice_sort'] ) && is_string( $_GET['lb_notice_sort'] )
        ? sanitize_key( wp_unslash( $_GET['lb_notice_sort'] ) ) : 'date';
    $sort_by = in_array( $sort_by, array( 'date', 'title' ), true ) ? $sort_by : 'date';
    $direction = isset( $_GET['lb_notice_order'] ) && is_string( $_GET['lb_notice_order'] )
        ? strtoupper( sanitize_text_field( wp_unslash( $_GET['lb_notice_order'] ) ) ) : 'DESC';
    $direction = in_array( $direction, array( 'ASC', 'DESC' ), true ) ? $direction : 'DESC';
    $advanced_filters = lbnb_archive_advanced_filters_enabled();
    if ( ! $advanced_filters ) {
        // Free: use the standard newest-first view, irrespective of GET args.
        $from = '';
        $to = '';
        $search = '';
        $sort_by = 'date';
        $direction = 'DESC';
    }
    $has_filter = $advanced_filters && ( '' !== $from || '' !== $to || '' !== $search || 'date' !== $sort_by || 'DESC' !== $direction );
    $bad_range = $from && $to && $from > $to;
    $date_range = array( 'column' => 'post_date', 'inclusive' => true );
    if ( $from ) {
        list( $y, $m, $d ) = array_map( 'intval', explode( '-', $from ) );
        $date_range['after'] = array( 'year' => $y, 'month' => $m, 'day' => $d );
    }
    if ( $to ) {
        list( $y, $m, $d ) = array_map( 'intval', explode( '-', $to ) );
        $date_range['before'] = array( 'year' => $y, 'month' => $m, 'day' => $d );
    }
    $page = 1;
    if ( isset( $_GET['lb_notice_page'] ) && is_scalar( $_GET['lb_notice_page'] ) ) {
        $page = max( 1, absint( wp_unslash( $_GET['lb_notice_page'] ) ) );
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended
    $query_args = array(
        'post_type'           => 'littlebloom_notice',
        'post_status'         => 'publish',
        'posts_per_page'      => 9,
        'paged'               => $page,
        'orderby'             => $sort_by,
        'order'               => $direction,
        'no_found_rows'       => false,
        'ignore_sticky_posts' => true,
    );
    if ( '' !== $search ) {
        $query_args['s'] = $search;
    }
    if ( $from || $to ) {
        $query_args['date_query'] = array( $date_range );
    }
    if ( $bad_range ) {
        // Restrict the query to an impossible ID without generating SQL from user input.
        $query_args['post__in'] = array( 0 );
    }
    $query_args = apply_filters( 'lbnb_archive_query_args', $query_args, array(
        'from'      => $from,
        'to'        => $to,
        'search'    => $search,
        'sort_by'   => $sort_by,
        'direction' => $direction,
        'page'      => $page,
    ) );
    $query = new WP_Query( $query_args );
    $page_id = lbnb_get_archive_page_id();
    $permalink = $page_id ? get_permalink( $page_id ) : get_permalink();
    $page_args = array( 'lb_notice_page' => '%#%' );
    foreach ( array( 'lb_notice_from' => $from, 'lb_notice_to' => $to, 'lb_notice_search' => $search ) as $key => $value ) {
        if ( '' !== $value ) {
            $page_args[ $key ] = $value;
        }
    }
    if ( 'date' !== $sort_by ) {
        $page_args['lb_notice_sort'] = $sort_by;
    }
    if ( 'DESC' !== $direction ) {
        $page_args['lb_notice_order'] = $direction;
    }
    ob_start();
    ?>
    <section class="lb-notice-archive-628" style="<?php echo esc_attr( lbnb_palette_style() ); ?>" data-lbnb-palette="<?php echo esc_attr( lbnb_palette_attribute() ); ?>" aria-labelledby="lb-notice-archive-title">
        <div class="lb-notice-archive-628__intro">
            <span class="lb-notice-archive-628__eyebrow"><?php echo esc_html( $settings['eyebrow'] ); ?></span>
            <h1 id="lb-notice-archive-title"><?php echo esc_html( $settings['archive_title'] ); ?></h1>
            <p><?php echo esc_html( $settings['archive_subtitle'] ); ?></p>
        </div>
        <?php if ( $advanced_filters ) : ?>
        <form class="lb-notice-archive-630__form" method="get" action="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr__( 'Search, filter and sort announcements', 'littlebloom-notice-board' ); ?>">
            <div class="lb-notice-archive-630__field">
                <label for="lbnb-from"><?php esc_html_e( 'From date', 'littlebloom-notice-board' ); ?></label>
                <input id="lbnb-from" type="date" name="lb_notice_from" value="<?php echo esc_attr( $from ); ?>" />
            </div>
            <div class="lb-notice-archive-630__field">
                <label for="lbnb-to"><?php esc_html_e( 'To date', 'littlebloom-notice-board' ); ?></label>
                <input id="lbnb-to" type="date" name="lb_notice_to" value="<?php echo esc_attr( $to ); ?>" />
            </div>
            <div class="lb-notice-archive-630__field lbnb-search-field">
                <label for="lbnb-search"><?php esc_html_e( 'Search announcements', 'littlebloom-notice-board' ); ?></label>
                <input id="lbnb-search" type="search" name="lb_notice_search" maxlength="100" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr__( 'Search title or text', 'littlebloom-notice-board' ); ?>" />
            </div>
            <div class="lb-notice-archive-630__field lbnb-sort-field">
                <label for="lbnb-sort"><?php esc_html_e( 'Sort by', 'littlebloom-notice-board' ); ?></label>
                <select id="lbnb-sort" name="lb_notice_sort">
                    <option value="date" <?php selected( 'date', $sort_by ); ?>><?php esc_html_e( 'Publish date', 'littlebloom-notice-board' ); ?></option>
                    <option value="title" <?php selected( 'title', $sort_by ); ?>><?php esc_html_e( 'Title (alphabetical)', 'littlebloom-notice-board' ); ?></option>
                </select>
            </div>
            <div class="lb-notice-archive-630__field lbnb-order-field">
                <label for="lbnb-order"><?php esc_html_e( 'Direction', 'littlebloom-notice-board' ); ?></label>
                <select id="lbnb-order" name="lb_notice_order">
                    <option value="DESC" <?php selected( 'DESC', $direction ); ?>><?php esc_html_e( 'Descending (Z–A / Newest)', 'littlebloom-notice-board' ); ?></option>
                    <option value="ASC" <?php selected( 'ASC', $direction ); ?>><?php esc_html_e( 'Ascending (A–Z / Oldest)', 'littlebloom-notice-board' ); ?></option>
                </select>
            </div>
            <button class="lb-notice-archive-630__apply" type="submit"><?php esc_html_e( 'Filter notices', 'littlebloom-notice-board' ); ?></button>
            <?php if ( $has_filter ) : ?>
                <a class="lb-notice-archive-630__clear" href="<?php echo esc_url( $permalink ); ?>"><?php esc_html_e( 'Clear', 'littlebloom-notice-board' ); ?></a>
            <?php endif; ?>
        </form>
        <?php endif; ?>
        <?php if ( $bad_range ) : ?>
            <p class="lb-notice-archive-630__message" role="alert"><?php esc_html_e( 'The start date cannot be after the end date.', 'littlebloom-notice-board' ); ?></p>
        <?php else : ?>
            <p class="lb-notice-archive-630__message" role="status"><?php
                /* translators: %s: number of announcements that match the current archive filters. */
                $count_format = _n( '%s announcement found', '%s announcements found', (int) $query->found_posts, 'littlebloom-notice-board' );
                echo esc_html( sprintf( $count_format, number_format_i18n( (int) $query->found_posts ) ) );
            ?></p>
        <?php endif; ?>
        <?php if ( $query->have_posts() ) : ?>
            <div class="lb-notice-archive-628__grid">
                <?php while ( $query->have_posts() ) : $query->the_post();
                    $post_id = get_the_ID();
                    $content = get_post_field( 'post_content', $post_id );
                    $excerpt = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $content ) ), 35, '…' );
                    $full_text = trim( wp_strip_all_tags( strip_shortcodes( $content ) ) );
                    ?>
                    <article class="lb-notice-archive-628__note" style="<?php echo esc_attr( lbnb_note_style( $post_id ) ); ?>">
                        <?php echo wp_kses( lbnb_pin_control( $post_id ), lbnb_pin_allowed_html() ); ?>
                        <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                        <h2><?php echo esc_html( get_the_title() ); ?></h2>
                        <?php if ( $excerpt ) : ?><p><?php echo esc_html( wp_strip_all_tags( $excerpt ) ); ?></p><?php endif; ?>
                        <?php if ( strlen( $full_text ) > 215 ) : ?>
                            <details class="lb-notice-archive-628__details">
                                <summary><?php esc_html_e( 'Read full notice', 'littlebloom-notice-board' ); ?></summary>
                                <div><?php echo wp_kses_post( wpautop( $full_text ) ); ?></div>
                            </details>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
            </div>
            <?php if ( $query->max_num_pages > 1 && $page_id ) :
                $pagination = paginate_links( array(
                    'base'      => add_query_arg( $page_args, $permalink ),
                    'format'    => '',
                    'current'   => $page,
                    'total'     => (int) $query->max_num_pages,
                    'prev_text' => __( '← Newer', 'littlebloom-notice-board' ),
                    'next_text' => __( 'Older →', 'littlebloom-notice-board' ),
                    'type'      => 'list',
                ) );
                if ( $pagination ) : ?>
                    <nav class="lb-notice-archive-628__pagination" aria-label="<?php echo esc_attr__( 'Announcement pages', 'littlebloom-notice-board' ); ?>">
                        <?php echo wp_kses_post( $pagination ); ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        <?php else : ?>
            <div class="lb-notice-archive-628__empty">
                <span aria-hidden="true">📌</span>
                <h2><?php echo esc_html( $has_filter ? __( 'No announcements match these filters', 'littlebloom-notice-board' ) : __( 'No announcements yet', 'littlebloom-notice-board' ) ); ?></h2>
                <p><?php echo esc_html( $has_filter ? __( 'Try a different search, date range or sort order, or clear the filters.', 'littlebloom-notice-board' ) : __( 'Please check back for nursery news and family updates.', 'littlebloom-notice-board' ) ); ?></p>
            </div>
        <?php endif; ?>
    </section>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}

function lbnb_archive_body_class( $classes ) {
    $page_id = lbnb_get_archive_page_id();
    if ( $page_id && is_page( $page_id ) ) {
        $classes[] = 'lb-notice-archive-page-628';
    }
    return $classes;
}
add_filter( 'body_class', 'lbnb_archive_body_class' );
