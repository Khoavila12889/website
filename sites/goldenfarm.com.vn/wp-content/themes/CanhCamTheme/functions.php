<?php
define('GENERATE_VERSION', '1.1.0');

require get_template_directory() . '/inc/function-root.php';
require get_template_directory() . '/inc/function-custom.php';
require get_template_directory() . '/inc/function-field.php';
require get_template_directory() . '/inc/function-pagination.php';
require get_template_directory() . '/inc/function-setup.php';
require get_template_directory() . '/inc/function-optimize-pagespeed.php';
require get_template_directory() . '/inc/function-woocommerce.php';
require get_template_directory() . '/inc/walker-menu.php';

// ============================================================
// ACF: Relationship query - order by newest
// ============================================================
add_filter('acf/fields/relationship/query', function($args, $field, $post_id) {
    $args['orderby'] = 'date';
    $args['order']   = 'DESC';
    return $args;
}, 10, 3);

// ============================================================
// Search: Tìm theo title, excerpt, content - CHỈ main search query
// FIX: Dùng $wpdb->prepare() tránh SQL injection
// ============================================================
add_filter('posts_where', function($where, $wp_query) {
    global $wpdb;

    if (!$wp_query->is_main_query() || !$wp_query->is_search()) {
        return $where;
    }

    if ($keyword = $wp_query->get('s')) {
        $like = '%' . $wpdb->esc_like($keyword) . '%';
        $where .= $wpdb->prepare(
            " AND (
                {$wpdb->posts}.post_title   LIKE %s
                OR {$wpdb->posts}.post_excerpt LIKE %s
                OR {$wpdb->posts}.post_content LIKE %s
            )",
            $like, $like, $like
        );
    }

    return $where;
}, 10, 2);

// ============================================================
// Admin: Giới hạn quyền user (bọc trong hook để tránh gọi sớm)
// ============================================================
add_action('init', function() {
    if (!is_admin()) return;

    $userID    = get_current_user_id();
    $user_meta = get_userdata($userID);

    if (!$user_meta || !$userID) return;

    $allowed_users = ['admin', 'webrocket'];

    if (!in_array($user_meta->user_login, $allowed_users, true)) {
        defined('WP_AUTO_UPDATE_CORE')        || define('WP_AUTO_UPDATE_CORE', false);
        defined('AUTOMATIC_UPDATER_DISABLED') || define('AUTOMATIC_UPDATER_DISABLED', true);
        defined('DISALLOW_FILE_EDIT')         || define('DISALLOW_FILE_EDIT', true);

        add_filter('auto_update_plugin', '__return_false');
        add_filter('auto_update_theme',  '__return_false');

        add_action('admin_menu', function() {
            remove_menu_page('plugins.php');
            remove_menu_page('edit.php?post_type=acf-field-group');
        }, 999);
    }
});

// ============================================================
// Lazy load images trong content & thumbnail
// FIX: Regex xử lý cả self-closing tag, thêm flag case-insensitive
// ============================================================
add_filter('the_content',       'theme_enable_lazy_load_images');
add_filter('post_thumbnail_html', 'theme_enable_lazy_load_images');

function theme_enable_lazy_load_images($content) {
    $content = preg_replace('/<img(.*?)data-src=/i', '<img$1src=', $content);
    $content = preg_replace('/<img((?![^>]*\bloading\b)[^>]*)\/?>/i', '<img$1 loading="lazy">', $content);
    return $content;
}

// ============================================================
// Contact Form 7: async + defer
// ============================================================
add_action('wp_enqueue_scripts', function() {
    if (wp_script_is('contact-form-7', 'enqueued')) {
        wp_script_add_data('contact-form-7', 'async', true);
    }
}, 20);

add_filter('script_loader_tag', function($tag, $handle, $src) {
    if ('contact-form-7' === $handle) {
        return str_replace(' src', ' defer src', $tag);
    }
    return $tag;
}, 10, 3);

// ============================================================
// WooCommerce: autoparagraph cho short description
// NOTE: KHÔNG add wpautop vào the_content vì đã là default filter
// ============================================================
add_filter('woocommerce_short_description', 'wpautop');

// ============================================================
// Preload fonts - kiểm tra file tồn tại trước khi output
// ============================================================
add_action('wp_head', function() {
    $font_dir = get_template_directory();
    $font_uri = get_template_directory_uri();

    $fonts = ['main-font.woff2', 'heading-font.woff2'];

    foreach ($fonts as $file) {
        if (file_exists("{$font_dir}/fonts/{$file}")) {
            printf(
                '<link rel="preload" href="%s/fonts/%s" as="font" type="font/woff2" crossorigin>' . "\n",
                esc_url($font_uri),
                esc_attr($file)
            );
        }
    }
}, 1);

// ============================================================
// Defer scripts - frontend only, bỏ qua jquery/gtm/gtag
// FIX: Tránh inject defer trùng bằng cách check trước
// ============================================================
add_filter('script_loader_tag', function($tag, $handle, $src) {
    if (is_admin()) return $tag;

    if (
        strpos($src, 'jquery') !== false ||
        strpos($src, 'gtm.js') !== false ||
        strpos($src, 'gtag/js') !== false
    ) {
        return $tag;
    }

    // Chỉ thêm defer nếu chưa có
    if (!str_contains($tag, ' defer') && !str_contains($tag, ' async')) {
        $tag = str_replace('<script ', '<script defer ', $tag);
    }

    return $tag;
}, 10, 3);

// ============================================================
// Tắt Font Awesome thừa
// ============================================================
add_action('wp_enqueue_scripts', function() {
    $fa_handles = [
        'font-awesome', 'fontawesome', 'fa-all',
        'font-awesome-solid', 'font-awesome-regular',
        'font-awesome-light', 'font-awesome-thin', 'font-awesome-brands',
    ];
    foreach ($fa_handles as $handle) {
        wp_dequeue_style($handle);
        wp_deregister_style($handle);
    }
}, 999);

// ============================================================
// WooCommerce: Tắt mua hàng, thông báo, scripts thừa
// ============================================================
add_filter('woocommerce_is_purchasable',    '__return_false');
add_filter('woocommerce_is_sold_individually', '__return_false');

add_action('init', function() {
    remove_action('woocommerce_before_single_product', 'woocommerce_output_all_notices', 10);
    remove_action('woocommerce_before_shop_loop',      'woocommerce_output_all_notices', 10);
});

add_action('wp_enqueue_scripts', function() {
    wp_dequeue_script('wc-add-to-cart');
    wp_dequeue_script('wc-cart-fragments');
}, 100);

// ============================================================
// WooCommerce: Tắt các cron jobs không cần thiết
// ============================================================
add_action('init', function() {
    if (!function_exists('as_next_scheduled_action')) return;

    $actions_to_remove = [
        'wc_admin_daily',
        'woocommerce_cleanup_personal_data',
        'woocommerce_cleanup_logs',
        'wc_privacy_cleanup_personal_data',
    ];

    foreach ($actions_to_remove as $action) {
        if (as_next_scheduled_action($action)) {
            as_unschedule_all_actions($action);
        }
    }
});

// ============================================================
// WooCommerce: Tắt analytics & admin dashboard widgets
// ============================================================
add_filter('woocommerce_admin_disabled',    '__return_true');
add_filter('woocommerce_analytics_enabled', '__return_false');

add_action('wp_dashboard_setup', function() {
    remove_meta_box('dashboard_activity',           'dashboard', 'normal');
    remove_meta_box('dashboard_quick_press',        'dashboard', 'side');
    remove_meta_box('dashboard_primary',            'dashboard', 'side');
    remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
});

// ============================================================
// Tắt Heartbeat API ở admin để giảm tải server
// ============================================================
add_action('init', function() {
    if (is_admin()) {
        wp_deregister_script('heartbeat');
    }
});

// ============================================================
// Admin: Chỉ load AIOSEO scripts khi đang ở trang AIOSEO
// ============================================================
add_action('admin_enqueue_scripts', function() {
    if (!isset($_GET['page']) || strpos($_GET['page'], 'aioseo') === false) {
        wp_dequeue_script('aioseo-admin-js');
        wp_dequeue_style('aioseo-admin-css');
    }
}, 100);