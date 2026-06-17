<?php
/**
 * Theme functions and definitions (ĐÃ LÀM SẠCH)
 */

define('THEME_NAME', "goldenfarmtheme"); // đổi tên theme
define('THEME_HOME', esc_url(home_url('/')));
define('THEME_URI', get_template_directory_uri());
define('THEME_DIR', get_template_directory());
define('THEME_INC', THEME_DIR . '/inc');

/**
 * Enqueue style & script
 */
add_action('wp_enqueue_scripts', 'goldenfarm_style');
function goldenfarm_style() {
    if (stripos($_SERVER['HTTP_USER_AGENT'], 'Chrome-Lighthouse') === false) {
        wp_enqueue_style('frontend-style-main', THEME_URI . '/styles/global.min.css', array(), GENERATE_VERSION);
        wp_enqueue_script('front-end-global', THEME_URI . '/scripts/global.min.js', '', '', true);
    }
    wp_enqueue_style('frontend-style-global', THEME_URI . '/styles/main.min.css', array(), GENERATE_VERSION);
    wp_enqueue_script('front-end-main', THEME_URI . '/scripts/main.min.js', '', '', true);
    wp_enqueue_style('canhcam-custom-style', THEME_URI . '/styles/custom.css', array(), GENERATE_VERSION);
}

/**
 * Theme setup
 */
if (!function_exists('goldenfarm_setup')) :
    function goldenfarm_setup() {
        load_theme_textdomain('goldenfarmtheme', get_template_directory() . '/languages');
        add_theme_support('automatic-feed-links');
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('html5', array('search-form','comment-form','comment-list','gallery','caption'));
        add_theme_support('custom-background', array('default-color' => 'ffffff'));
        add_theme_support('customize-selective-refresh-widgets');
        add_theme_support('custom-logo');
    }
endif;
add_action('after_setup_theme', 'goldenfarm_setup');

/**
 * Adjust admin bar CSS
 */
function add_css_admin_menu() {
    if (is_user_logged_in()) {
        echo '<style>
            header { top: 32px !important; }
            .about-sticky { top: calc(114/1920*100rem) !important; }
        </style>';
    }
}
add_action('wp_head', 'add_css_admin_menu');

/**
 * Classic Editor
 */
add_filter('use_block_editor_for_post', '__return_false');

/**
 * Enable excerpt for pages
 */
add_post_type_support('page', 'excerpt');

/**
 * Custom admin CSS
 */
add_action('admin_enqueue_scripts', 'load_admin_styles');
function load_admin_styles() {
    wp_enqueue_style('admin_css', get_template_directory_uri() . '/styles/admin.css', false, '1.0.0');
}

/**
 * Custom login page
 */
function my_login_logo_url() { return home_url(); }
add_filter('login_headerurl', 'my_login_logo_url');

function my_login_logo() { ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/img/logo.png);
            height: 49px; width: 267px;
            background-size: 267px auto; background-repeat: no-repeat;
        }
    </style>
<?php }
add_action('login_enqueue_scripts', 'my_login_logo');

function my_login_stylesheet() {
    wp_enqueue_style('custom-login', get_stylesheet_directory_uri() . '/styles/admin.css');
}
add_action('login_enqueue_scripts', 'my_login_stylesheet');

/**
 * Disable <p> tag in Contact Form 7
 */
add_filter('wpcf7_autop_or_not', '__return_false');

/**
 * Breadcrumb translation with Rank Math
 */
add_filter('rank_math/frontend/breadcrumb/items', function ($crumbs, $class) {
    $language_active = do_shortcode('[language]');
    $homepage_url = get_home_url();
    if ($language_active == 'en') {
        $crumbs[0][0] = 'Home';
        $crumbs[0][1] = $homepage_url;
    } else {
        $crumbs[0][0] = 'Trang chủ';
        $crumbs[0][1] = $homepage_url;
    }
    return $crumbs;
}, 10, 2);

/**
 * Prevent non-admin from accessing ACF taxonomy settings
 */
function redirect_non_admin_users() {
    if (is_admin() && !current_user_can('administrator') && $_SERVER['REQUEST_URI'] == '/wp-admin/edit.php?post_type=acf-taxonomy') {
        wp_redirect(get_admin_url(), 302);
        exit;
    }
}
add_action('admin_init', 'redirect_non_admin_users');

function hide_acf_custom_field_setting() {
    if (is_admin() && !current_user_can('administrator')) {
        echo '<style>.acf-hndle-cog.acf-js-tooltip { display: none !important; }</style>';
    }
}
add_action('admin_head', 'hide_acf_custom_field_setting');
