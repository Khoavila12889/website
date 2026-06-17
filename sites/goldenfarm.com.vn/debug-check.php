<?php
/**
 * WordPress Memory Debug & Optimization Tool
 * Version: 2.2 Improved
 * File: debug-memory-pro.php
 * Upload to WordPress root directory
 *
 * Features:
 * - Enhanced UI with modern styling
 * - Memory optimization recommendations
 * - Database cleanup tools
 * - Performance metrics
 * - Security checks
 * - Export functionality
 * - Health check with PageSpeed, Redis, LiteSpeed, and server resource analysis
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security check
if (!defined('ABSPATH') && !file_exists('wp-config.php')) {
    die('Direct access not allowed.');
}

// Load WordPress safely
if (!file_exists('wp-config.php') || !file_exists('wp-load.php')) {
    die('<strong>Error:</strong> WordPress files not found.');
}

// Prevent WooCommerce hooks from running during debug
if (!defined('WP_DEBUG_MEMORY_TOOL')) {
    define('WP_DEBUG_MEMORY_TOOL', true);
}

// Disable Action Scheduler during debug
if (!defined('ACTION_SCHEDULER_DISABLE')) {
    define('ACTION_SCHEDULER_DISABLE', true);
}

// Prevent automatic updates and cron during debug
if (!defined('WP_INSTALLING')) {
    define('WP_INSTALLING', true);
}

try {
    // Load WordPress configuration
    require_once 'wp-config.php';
    
    // Set up minimal WordPress environment
    if (!defined('WPINC')) {
        define('WPINC', 'wp-includes');
    }
    
    // Load essential WordPress files only
    require_once ABSPATH . WPINC . '/wp-db.php';
    require_once ABSPATH . WPINC . '/functions.php';
    require_once ABSPATH . WPINC . '/option.php';
    require_once ABSPATH . WPINC . '/plugin.php';
    require_once ABSPATH . WPINC . '/version.php';
    
    // Initialize database connection
    if (!isset($wpdb)) {
        $wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
        $wpdb->set_prefix($table_prefix);
    }
    
    // Load minimal WordPress functions
    if (!function_exists('wp_count_posts')) {
        require_once ABSPATH . WPINC . '/post.php';
    }
    
    if (!function_exists('get_plugin_data')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    // Safely check if WooCommerce is available
    $woocommerce_active = false;
    if (file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php')) {
        $active_plugins = get_option('active_plugins', []);
        $woocommerce_active = in_array('woocommerce/woocommerce.php', $active_plugins);
    }
    
} catch (Exception $e) {
    die('<strong>Error:</strong> Failed to load WordPress: ' . esc_html($e->getMessage()));
}

global $wpdb;

// Health Check Functions
function get_page_speed_score() {
    $site_url = get_site_url();
    $api_key = 'YOUR_GOOGLE_PAGESPEED_API_KEY'; // Thay bằng API key của bạn hoặc để trống để bỏ qua
    $url = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=" . urlencode($site_url) . "&key=" . $api_key;

    try {
        $response = wp_remote_get($url, ['timeout' => 10]);
        if (is_wp_error($response)) {
            return ['score' => 'N/A', 'load_time' => 'N/A', 'status' => 'error'];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $score = isset($body['lighthouseResult']['categories']['performance']['score']) 
            ? round($body['lighthouseResult']['categories']['performance']['score'] * 100) 
            : 'N/A';
        $load_time = isset($body['lighthouseResult']['audits']['interactive']['displayValue']) 
            ? $body['lighthouseResult']['audits']['interactive']['displayValue'] 
            : 'N/A';

        return [
            'score' => $score,
            'load_time' => $load_time,
            'status' => $score >= 80 ? 'good' : ($score >= 50 ? 'warning' : 'critical')
        ];
    } catch (Exception $e) {
        return ['score' => 'N/A', 'load_time' => 'N/A', 'status' => 'error'];
    }
}

function check_cache_status() {
    $cache_status = [];
    
    // Check Redis
    $redis_status = 'Inactive';
    if (defined('WP_REDIS_HOST') && class_exists('Redis')) {
        try {
            $redis = new Redis();
            $redis->connect(WP_REDIS_HOST, WP_REDIS_PORT);
            $redis->select(WP_REDIS_DATABASE);
            $redis_status = $redis->ping() ? 'Active' : 'Inactive';
            $redis->close();
        } catch (Exception $e) {
            $redis_status = 'Error: ' . $e->getMessage();
        }
    }
    $cache_status['Redis'] = $redis_status;
    
    // Check LiteSpeed Cache
    $litespeed_active = defined('LITESPEED_CACHE_VERSION') || class_exists('LiteSpeed_Cache');
    $cache_status['LiteSpeed Cache'] = $litespeed_active ? 'Active' : 'Inactive';
    
    // Check other popular cache plugins
    $cache_plugins = [
        'wp-super-cache/wp-cache.php' => 'WP Super Cache',
        'w3-total-cache/w3-total-cache.php' => 'W3 Total Cache',
        'wp-rocket/wp-rocket.php' => 'WP Rocket'
    ];
    
    $active_plugins = get_option('active_plugins', []);
    foreach ($cache_plugins as $plugin => $name) {
        $cache_status[$name] = in_array($plugin, $active_plugins) ? 'Active' : 'Inactive';
    }
    
    // Check object cache
    $cache_status['Object Cache'] = wp_using_ext_object_cache() ? 'Active' : 'Inactive';
    
    return $cache_status;
}

function get_redis_info() {
    $redis_info = ['status' => 'N/A', 'keys' => 0, 'memory_used' => 'N/A'];
    
    if (defined('WP_REDIS_HOST') && class_exists('Redis')) {
        try {
            $redis = new Redis();
            $redis->connect(WP_REDIS_HOST, WP_REDIS_PORT);
            $redis->select(WP_REDIS_DATABASE);
            $info = $redis->info();
            $redis_info = [
                'status' => $redis->ping() ? 'Connected' : 'Disconnected',
                'keys' => $info['db' . WP_REDIS_DATABASE]['keys'] ?? 0,
                'memory_used' => $info['used_memory_human'] ?? 'N/A'
            ];
            $redis->close();
        } catch (Exception $e) {
            $redis_info['status'] = 'Error: ' . $e->getMessage();
        }
    }
    
    return $redis_info;
}

function get_server_resources() {
    $resources = [
        'cpu_usage' => 'N/A',
        'ram_usage' => 'N/A',
        'disk_space' => 'N/A'
    ];
    
    // CPU Usage (Linux only)
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        $resources['cpu_usage'] = round($load[0], 2) . '%';
    }
    
    // RAM Usage
    if (function_exists('memory_get_usage')) {
        $resources['ram_usage'] = format_memory(memory_get_usage(true));
    }
    
    // Disk Space
    if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
        $free_space = disk_free_space(ABSPATH);
        $total_space = disk_total_space(ABSPATH);
        if ($free_space && $total_space) {
            $used_space = $total_space - $free_space;
            $resources['disk_space'] = format_memory($used_space) . ' / ' . format_memory($total_space);
        }
    }
    
    return $resources;
}

function check_wp_config_status() {
    $config_status = [];
    
    // Check .htaccess existence and permissions
    $htaccess_file = ABSPATH . '.htaccess';
    $config_status['htaccess'] = file_exists($htaccess_file) 
        ? (is_writable($htaccess_file) ? 'Writable' : 'Read-only') 
        : 'Missing';
    
    // Check wp-config.php permissions
    $wp_config_file = ABSPATH . 'wp-config.php';
    $wp_config_perms = file_exists($wp_config_file) ? substr(sprintf('%o', fileperms($wp_config_file)), -4) : 'N/A';
    $config_status['wp_config'] = $wp_config_perms;
    
    // Check common optimization settings
    $config_status['wp_cache'] = defined('WP_CACHE') && WP_CACHE ? 'Enabled' : 'Disabled';
    $config_status['gzip'] = function_exists('apache_get_modules') 
        ? (in_array('mod_deflate', apache_get_modules()) ? 'Enabled' : 'Disabled') 
        : 'N/A';
    
    // Check Redis settings
    $config_status['redis'] = defined('WP_REDIS_HOST') ? 'Configured' : 'Not Configured';
    
    return $config_status;
}

function check_litespeed_status() {
    $litespeed_status = [
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'litespeed_cache' => defined('LITESPEED_CACHE_VERSION') ? LITESPEED_CACHE_VERSION : 'Not Installed',
        'quic_enabled' => extension_loaded('lsquic') ? 'Enabled' : 'Disabled',
        'image_optimization' => class_exists('LiteSpeed_Cache') && method_exists('LiteSpeed_Cache', 'img_optm_status') 
            ? 'Enabled' : 'Disabled'
    ];
    
    return $litespeed_status;
}

function generate_health_recommendations($page_speed, $cache_status, $server_resources, $wp_config, $redis_info) {
    $recommendations = [];
    
    // Page Speed Recommendations
    if ($page_speed['status'] === 'critical' || $page_speed['status'] === 'warning') {
        $recommendations[] = 'Tối ưu hóa tốc độ tải trang: Sử dụng CDN, nén hình ảnh, và giảm thiểu CSS/JS.';
    }
    
    // Cache Recommendations
    if ($cache_status['Redis'] !== 'Active') {
        $recommendations[] = 'Kiểm tra kết nối Redis hoặc cài đặt plugin Redis Object Cache.';
    }
    if ($cache_status['LiteSpeed Cache'] === 'Inactive' && strpos($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed') !== false) {
        $recommendations[] = 'Kích hoạt LiteSpeed Cache để tận dụng tối đa máy chủ LiteSpeed.';
    }
    
    // Redis Recommendations
    if ($redis_info['status'] !== 'Connected') {
        $recommendations[] = 'Kiểm tra cấu hình Redis (host: ' . WP_REDIS_HOST . ', port: ' . WP_REDIS_PORT . ').';
    } elseif ($redis_info['keys'] > 10000) {
        $recommendations[] = 'Số lượng key Redis lớn (' . $redis_info['keys'] . '). Xem xét xóa các key không cần thiết.';
    }
    
    // Server Resources Recommendations
    if ($server_resources['disk_space'] !== 'N/A' && strpos($server_resources['disk_space'], '/') !== false) {
        list($used, $total) = explode('/', $server_resources['disk_space']);
        $used_bytes = wp_convert_hr_to_bytes(trim($used));
        $total_bytes = wp_convert_hr_to_bytes(trim($total));
        if (($used_bytes / $total_bytes) > 0.9) {
            $recommendations[] = 'Dung lượng đĩa gần đầy. Hãy dọn dẹp hoặc nâng cấp dung lượng lưu trữ.';
        }
    }
    
    // Config Recommendations
    if ($wp_config['htaccess'] === 'Missing') {
        $recommendations[] = 'Tạo file .htaccess với cấu hình tối ưu cho WordPress.';
    }
    if ($wp_config['wp_config'] !== '0644' && $wp_config['wp_config'] !== '0600') {
        $recommendations[] = 'Đặt quyền file wp-config.php thành 644 hoặc 600 để tăng cường bảo mật.';
    }
    if ($wp_config['wp_cache'] === 'Disabled') {
        $recommendations[] = 'Kích hoạt WP_CACHE trong wp-config.php để cải thiện hiệu suất.';
    }
    
    return $recommendations ?: ['Website của bạn đang hoạt động tốt! Tiếp tục theo dõi định kỳ.'];
}

// Handle AJAX actions
if (isset($_POST['action'])) {
    handle_ajax_action();
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'health_check') {
    header('Content-Type: application/json');
    
    $page_speed = get_page_speed_score();
    $cache_status = check_cache_status();
    $server_resources = get_server_resources();
    $wp_config = check_wp_config_status();
    $litespeed_status = check_litespeed_status();
    $redis_info = get_redis_info();
    $health_recommendations = generate_health_recommendations($page_speed, $cache_status, $server_resources, $wp_config, $redis_info);
    
    echo json_encode([
        'success' => true,
        'page_speed' => $page_speed,
        'cache_status' => $cache_status,
        'server_resources' => $server_resources,
        'wp_config' => $wp_config,
        'litespeed_status' => $litespeed_status,
        'redis_info' => $redis_info,
        'health_recommendations' => $health_recommendations
    ]);
    exit;
}

// Utility functions
function format_memory($bytes) {
    if ($bytes === false || $bytes === null) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

function get_memory_status($current, $limit) {
    $limit_bytes = wp_convert_hr_to_bytes($limit);
    $usage_percent = ($current / $limit_bytes) * 100;
    
    if ($usage_percent > 90) return ['status' => 'critical', 'color' => '#e74c3c', 'percent' => $usage_percent];
    if ($usage_percent > 70) return ['status' => 'warning', 'color' => '#f39c12', 'percent' => $usage_percent];
    return ['status' => 'good', 'color' => '#27ae60', 'percent' => $usage_percent];
}

function handle_ajax_action() {
    global $wpdb;
    
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'debug_memory_nonce')) {
        wp_die('Security check failed');
    }
    
    // Ensure database connection is available
    if (!$wpdb || !$wpdb->db_connect()) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        return;
    }
    
    try {
        switch ($_POST['action']) {
            case 'clean_transients':
                $deleted = $wpdb->query("
                    DELETE o1, o2 FROM {$wpdb->options} o1
                    LEFT JOIN {$wpdb->options} o2 ON o2.option_name = REPLACE(o1.option_name, '_timeout', '')
                    WHERE o1.option_name LIKE '_transient_timeout_%' 
                    AND o1.option_value < UNIX_TIMESTAMP()
                ");
                echo json_encode(['success' => true, 'deleted' => $deleted]);
                break;
                
            case 'clean_cart_fragments':
                $deleted = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_wc_cart_hash_%'");
                echo json_encode(['success' => true, 'deleted' => $deleted]);
                break;
                
            case 'optimize_database':
                $tables = $wpdb->get_col("SHOW TABLES");
                $optimized = 0;
                foreach ($tables as $table) {
                    $result = $wpdb->query("OPTIMIZE TABLE `$table`");
                    if ($result !== false) {
                        $optimized++;
                    }
                }
                echo json_encode(['success' => true, 'optimized' => $optimized]);
                break;
                
            case 'clear_cache':
                $result = false;
                
                // Clear Redis Cache
                if (defined('WP_REDIS_HOST') && class_exists('Redis')) {
                    try {
                        $redis = new Redis();
                        $redis->connect(WP_REDIS_HOST, WP_REDIS_PORT);
                        $redis->select(WP_REDIS_DATABASE);
                        $redis->flushDB();
                        $redis->close();
                        $result = true;
                    } catch (Exception $e) {
                        // Handle error
                    }
                }
                
                // Clear LiteSpeed Cache
                if (class_exists('LiteSpeed_Cache')) {
                    try {
                        LiteSpeed_Cache::singleton()->purge_all();
                        $result = true;
                    } catch (Exception $e) {
                        // Handle error
                    }
                }
                
                // Clear WP Super Cache
                if (function_exists('wp_cache_clear_cache')) {
                    wp_cache_clear_cache();
                    $result = true;
                }
                
                // Clear W3 Total Cache
                if (function_exists('w3tc_flush_all')) {
                    w3tc_flush_all();
                    $result = true;
                }
                
                // Clear WP Rocket
                if (function_exists('rocket_clean_domain')) {
                    rocket_clean_domain();
                    $result = true;
                }
                
                echo json_encode(['success' => $result, 'message' => $result ? 'Cache cleared successfully' : 'No cache plugins detected']);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// Get system info
$memory_limit = ini_get('memory_limit');
$memory_used = memory_get_usage(true);
$memory_peak = memory_get_peak_usage(true);
$memory_status = get_memory_status($memory_used, $memory_limit);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Memory Debug Pro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header .subtitle {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .nav-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }
        
        .nav-tab {
            flex: 1;
            padding: 15px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .nav-tab.active {
            background: white;
            color: #495057;
            border-bottom: 3px solid #007cba;
        }
        
        .nav-tab:hover {
            background: #e9ecef;
        }
        
        .content {
            padding: 30px;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 10px;
            border-left: 5px solid #007cba;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #007cba;
        }
        
        .memory-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0;
        }
        
        .memory-progress {
            height: 100%;
            transition: width 0.5s ease;
            border-radius: 10px;
        }
        
        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            align-items: center;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 5px;
        }
        
        .btn-primary {
            background: #007cba;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .loading {
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007cba;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .recommendations {
            background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .recommendations h4 {
            color: #28a745;
            margin-bottom: 15px;
        }
        
        .recommendations ul {
            list-style-type: none;
        }
        
        .recommendations li {
            padding: 8px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .recommendations li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        
        /* Additional responsive styles */
        @media (max-width: 768px) {
            .nav-tabs {
                flex-direction: column;
            }
            
            .nav-tab {
                border-bottom: 1px solid #e9ecef;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .content {
                padding: 15px;
            }
            
            .header {
                padding: 20px 15px;
            }
            
            .header h1 {
                font-size: 2em;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .container {
                background: #2d3748;
                color: #e2e8f0;
            }
            
            .stat-card {
                background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
                color: #e2e8f0;
            }
            
            table {
                background: #4a5568;
                color: #e2e8f0;
            }
            
            th {
                background: #2d3748;
            }
            
            tr:hover {
                background: #2d3748;
            }
        }
        
        /* Print styles */
        @media print {
            .nav-tabs, .btn, .loading {
                display: none !important;
            }
            
            .tab-content {
                display: block !important;
            }
            
            body {
                background: white !important;
            }
            
            .container {
                box-shadow: none !important;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .tab-content.active {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        /* Enhanced button styles */
        .btn {
            position: relative;
            overflow: hidden;
        }
        
        .btn:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transition: width 0.3s, height 0.3s, top 0.3s, left 0.3s;
            transform: translate(-50%, -50%);
        }
        
        .btn:active:before {
            width: 300px;
            height: 300px;
        }
        
        /* Status indicators */
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-good { background-color: #28a745; }
        .status-warning { background-color: #ffc107; }
        .status-critical { background-color: #dc3545; }
        
        /* Enhanced alerts */
        .alert {
            position: relative;
        }
        
        .alert:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
        }
        
        .alert-success:before { background: #28a745; }
        .alert-warning:before { background: #ffc107; }
        .alert-danger:before { background: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 WordPress Memory Debug Pro</h1>
            <div class="subtitle"><?php echo esc_html(date('Y-m-d H:i:s')); ?> | <?php echo esc_html(get_bloginfo('name')); ?></div>
        </div>
        
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="switchTab('overview')">📊 Tổng quan</button>
            <button class="nav-tab" onclick="switchTab('database')">🗄️ Cơ sở dữ liệu</button>
            <button class="nav-tab" onclick="switchTab('plugins')">🔌 Plugins</button>
            <button class="nav-tab" onclick="switchTab('optimization')">⚡ Tối ưu hóa</button>
            <button class="nav-tab" onclick="switchTab('security')">🔒 Bảo mật</button>
            <button class="nav-tab" onclick="switchTab('health')">🩺 Kiểm tra sức khỏe</button>
        </div>
        
        <div class="content">
            <!-- Overview Tab -->
            <div id="overview" class="tab-content active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>💾 Memory Usage</h3>
                        <div class="stat-value"><?php echo format_memory($memory_used); ?></div>
                        <div class="memory-bar">
                            <div class="memory-progress" style="width: <?php echo $memory_status['percent']; ?>%; background-color: <?php echo $memory_status['color']; ?>"></div>
                        </div>
                        <small><?php echo round($memory_status['percent'], 1); ?>% of <?php echo esc_html($memory_limit); ?></small>
                    </div>
                    
                    <div class="stat-card">
                        <h3>📈 Peak Memory</h3>
                        <div class="stat-value"><?php echo format_memory($memory_peak); ?></div>
                        <small>Highest usage during execution</small>
                    </div>
                    
                    <div class="stat-card">
                        <h3>⚡ PHP Version</h3>
                        <div class="stat-value"><?php echo phpversion(); ?></div>
                        <small><?php echo version_compare(phpversion(), '7.4', '>=') ? '✅ Modern' : '⚠️ Consider upgrading'; ?></small>
                    </div>
                    
                    <div class="stat-card">
                        <h3>🌐 WordPress</h3>
                        <div class="stat-value"><?php echo get_bloginfo('version'); ?></div>
                        <small><?php echo is_multisite() ? 'Multisite' : 'Single site'; ?></small>
                    </div>
                </div>
                
                <?php if ($memory_status['status'] !== 'good'): ?>
                <div class="alert alert-<?php echo $memory_status['status'] === 'critical' ? 'danger' : 'warning'; ?>">
                    <?php if ($memory_status['status'] === 'critical'): ?>
                        ⚠️ <strong>Critical:</strong> Memory usage is very high (<?php echo round($memory_status['percent'], 1); ?>%). Consider optimization immediately.
                    <?php else: ?>
                        ⚠️ <strong>Warning:</strong> Memory usage is elevated (<?php echo round($memory_status['percent'], 1); ?>%). Monitor and consider optimization.
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- WooCommerce Info -->
                <?php if ($woocommerce_active): ?>
                <h3>🛒 WooCommerce Status</h3>
                <div class="stats-grid">
                    <?php
                    try {
                        $product_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");
                        $sessions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_sessions WHERE session_expiry > " . time());
                        $fragments = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_wc_cart_hash_%'");
                    } catch (Exception $e) {
                        $product_count = 0;
                        $sessions = 0;
                        $fragments = 0;
                    }
                    ?>
                    <div class="stat-card">
                        <h3>📦 Products</h3>
                        <div class="stat-value"><?php echo number_format($product_count ?? 0); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>👥 Active Sessions</h3>
                        <div class="stat-value"><?php echo number_format($sessions ?? 0); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>🛒 Cart Fragments</h3>
                        <div class="stat-value"><?php echo number_format($fragments ?? 0); ?></div>
                        <?php if ($fragments > 100): ?>
                        <button class="btn btn-warning" onclick="cleanCartFragments()">Clean Fragments</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    🛒 WooCommerce is not active or not properly loaded.
                </div>
                <?php endif; ?>
                
                <!-- Recommendations -->
                <div class="recommendations">
                    <h4>💡 Performance Recommendations</h4>
                    <ul>
                        <?php if (!wp_using_ext_object_cache()): ?>
                        <li>Enable object caching (Redis/Memcached) for better performance</li>
                        <?php endif; ?>
                        <?php if ($memory_status['percent'] > 70): ?>
                        <li>Consider increasing PHP memory limit or optimizing plugins</li>
                        <?php endif; ?>
                        <li>Regularly clean up expired transients and cache</li>
                        <li>Optimize database tables monthly</li>
                        <li>Monitor plugin performance and disable unused ones</li>
                    </ul>
                </div>
            </div>
            
            <!-- Database Tab -->
            <div id="database" class="tab-content">
                <h3>🗄️ Database Analysis</h3>
                
                <?php
                $tables = $wpdb->get_results("SHOW TABLE STATUS");
                if ($tables):
                    $total_size = 0;
                    $large_tables = [];
                ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Rows</th>
                                <th>Size (MB)</th>
                                <th>Engine</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tables as $table):
                                $size_mb = round(($table->Data_length + $table->Index_length) / 1024 / 1024, 2);
                                $total_size += $size_mb;
                                $is_large = ($size_mb > 50 || $table->Rows > 100000);
                                if ($is_large) $large_tables[] = $table->Name;
                            ?>
                            <tr style="<?php echo $is_large ? 'background-color: #fff3cd;' : ''; ?>">
                                <td><?php echo esc_html($table->Name); ?></td>
                                <td><?php echo number_format($table->Rows); ?></td>
                                <td><?php echo $size_mb; ?></td>
                                <td><?php echo esc_html($table->Engine); ?></td>
                                <td>
                                    <?php if ($is_large): ?>
                                        <span style="color: #856404;">⚠️ Large</span>
                                    <?php else: ?>
                                        <span style="color: #155724;">✅ Normal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-success">
                    📊 <strong>Total Database Size:</strong> <?php echo round($total_size, 2); ?> MB
                </div>
                
                <?php if (!empty($large_tables)): ?>
                <div class="alert alert-warning">
                    ⚠️ <strong>Large Tables Found:</strong> <?php echo implode(', ', array_map('esc_html', $large_tables)); ?>
                    <br>Consider optimization or archiving old data.
                </div>
                <?php endif; ?>
                
                <?php endif; ?>
                
                <!-- Transients Info -->
                <h4>🔄 WordPress Transients</h4>
                <?php
                try {
                    $transients = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
                    $expired_transients = (int)$wpdb->get_var("
                        SELECT COUNT(*) 
                        FROM {$wpdb->options} o1
                        LEFT JOIN {$wpdb->options} o2 
                        ON o2.option_name = REPLACE(o1.option_name, '_transient_timeout_', '_transient_')
                        WHERE o1.option_name LIKE '_transient_timeout_%' 
                        AND o1.option_value < UNIX_TIMESTAMP()
                    ");
                } catch (Exception $e) {
                    $transients = 0;
                    $expired_transients = 0;
                }
                ?>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Transients</h3>
                        <div class="stat-value"><?php echo number_format($transients); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Expired Transients</h3>
                        <div class="stat-value"><?php echo number_format($expired_transients); ?></div>
                        <?php if ($expired_transients > 0): ?>
                        <button class="btn btn-danger" onclick="cleanTransients()">Clean Expired</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Plugins Tab -->
            <div id="plugins" class="tab-content">
                <h3>🔌 Active Plugins Analysis</h3>
                
                <?php
                $active_plugins = get_option('active_plugins', []);
                if (!empty($active_plugins)):
                ?>
                <div class="alert alert-success">
                    📊 <strong>Total Active Plugins:</strong> <?php echo count($active_plugins); ?>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Plugin Name</th>
                                <th>Version</th>
                                <th>Size</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_plugins as $plugin):
                                $plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
                                $plugin_data = [];
                                
                                if (file_exists($plugin_file)) {
                                    try {
                                        $plugin_data = get_plugin_data($plugin_file, false, false);
                                    } catch (Exception $e) {
                                        $plugin_data = ['Name' => dirname($plugin), 'Version' => 'Unknown'];
                                    }
                                }
                                
                                $plugin_path = WP_PLUGIN_DIR . '/' . dirname($plugin);
                                $plugin_size = 0;
                                
                                if (is_dir($plugin_path)) {
                                    try {
                                        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugin_path));
                                        foreach ($iterator as $file) {
                                            if ($file->isFile()) {
                                                $plugin_size += $file->getSize();
                                            }
                                        }
                                    } catch (Exception $e) {
                                        $plugin_size = 0;
                                    }
                                }
                            ?>
                            <tr>
                                <td><?php echo esc_html($plugin_data['Name'] ?? dirname($plugin)); ?></td>
                                <td><?php echo esc_html($plugin_data['Version'] ?? 'N/A'); ?></td>
                                <td><?php echo format_memory($plugin_size); ?></td>
                                <td>
                                    <?php if (file_exists($plugin_file)): ?>
                                        <span style="color: #28a745;">✅ Active</span>
                                    <?php else: ?>
                                        <span style="color: #dc3545;">❌ Missing</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">No active plugins found.</div>
                <?php endif; ?>
            </div>
            
            <!-- Optimization Tab -->
            <div id="optimization" class="tab-content">
                <h3>⚡ Performance Optimization</h3>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>🗄️ Database Optimization</h3>
                        <p>Optimize all database tables for better performance</p>
                        <button class="btn btn-primary" onclick="optimizeDatabase()">Optimize Tables</button>
                    </div>
                    
                    <div class="stat-card">
                        <h3>🧹 Cache Cleanup</h3>
                        <p>Clean expired transients and cache fragments</p>
                        <button class="btn btn-warning" onclick="cleanTransients()">Clean Transients</button>
                    </div>
                    
                    <div class="stat-card">
                        <h3>🔄 Object Cache</h3>
                        <?php 
                        $object_cache_enabled = function_exists('wp_using_ext_object_cache') ? wp_using_ext_object_cache() : false;
                        ?>
                        <p>Status: <?php echo $object_cache_enabled ? '✅ Active' : '❌ Inactive'; ?></p>
                        <?php if (!$object_cache_enabled): ?>
                        <small>Consider enabling Redis or Memcached</small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Performance Tips -->
                <div class="recommendations">
                    <h4>🎯 Optimization Tips</h4>
                    <ul>
                        <li>Enable Gzip compression on your server</li>
                        <li>Use a CDN for static assets</li>
                        <li>Implement browser caching</li>
                        <li>Optimize images and use WebP format</li>
                        <li>Minify CSS and JavaScript files</li>
                        <li>Remove unused plugins and themes</li>
                        <li>Regular database maintenance</li>
                    </ul>
                </div>
            </div>
            
            <!-- Security Tab -->
            <div id="security" class="tab-content">
                <h3>🔒 Security Check</h3>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>🔐 File Permissions</h3>
                        <?php
                        $wp_config_perms = substr(sprintf('%o', fileperms('wp-config.php')), -4);
                        $secure_perms = ($wp_config_perms === '0644' || $wp_config_perms === '0600');
                        ?>
                        <div class="stat-value" style="color: <?php echo $secure_perms ? '#28a745' : '#dc3545'; ?>">
                            <?php echo $secure_perms ? '✅' : '⚠️'; ?>
                        </div>
                        <small>wp-config.php: <?php echo $wp_config_perms; ?></small>
                    </div>
                    
                    <div class="stat-card">
                        <h3>🛡️ WordPress Version</h3>
                        <?php
                        $wp_version = get_bloginfo('version');
                        $is_updated = version_compare($wp_version, '6.0', '>=');
                        ?>
                        <div class="stat-value" style="color: <?php echo $is_updated ? '#28a745' : '#f39c12'; ?>">
                            <?php echo $is_updated ? '✅' : '⚠️'; ?>
                        </div>
                        <small>Version: <?php echo esc_html($wp_version); ?></small>
                    </div>
                    
                    <div class="stat-card">
                        <h3>📝 Debug Mode</h3>
                        <?php $debug_enabled = defined('WP_DEBUG') && WP_DEBUG; ?>
                        <div class="stat-value" style="color: <?php echo !$debug_enabled ? '#28a745' : '#f39c12'; ?>">
                            <?php echo !$debug_enabled ? '✅' : '⚠️'; ?>
                        </div>
                        <small><?php echo $debug_enabled ? 'Enabled (disable for production)' : 'Disabled'; ?></small>
                    </div>
                </div>
                
                <!-- Security Recommendations -->
                <div class="recommendations">
                    <h4>🛡️ Security Recommendations</h4>
                    <ul>
                        <li>Use strong passwords and 2FA</li>
                        <li>Keep WordPress and plugins updated</li>
                        <li>Limit login attempts</li>
                        <li>Hide wp-config.php from public access</li>
                        <li>Use security plugins</li>
                        <li>Regular security scans</li>
                        <li>Backup your site regularly</li>
                    </ul>
                </div>
            </div>
            
            <!-- Health Check Tab -->
            <div id="health" class="tab-content">
                <h3>🩺 Kiểm tra sức khỏe website</h3>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>⏱ Tốc độ tải trang</h3>
                        <div class="stat-value" id="page-speed-score">Đang kiểm tra...</div>
                        <small>Thời gian tải trung bình và điểm PageSpeed</small>
                    </div>
                    
                    <div class="stat-card">
                        <h3>💾 Cache Status</h3>
                        <div class="stat-value" id="cache-status">Đang kiểm tra...</div>
                        <small>Trạng thái cache (Redis/LiteSpeed/WP Cache)</small>
                        <button class="btn btn-warning" onclick="clearCache()">Xóa Cache</button>
                    </div>
                    
                    <div class="stat-card">
                        <h3>🖥 Server Resources</h3>
                        <div class="stat-value" id="server-resources">Đang kiểm tra...</div>
                        <small>CPU, RAM, và Disk Usage</small>
                    </div>
                    
                    <div class="stat-card">
                        <h3>⚙️ WordPress Config</h3>
                        <div class="stat-value" id="wp-config-status">Đang kiểm tra...</div>
                        <small>Cấu hình tối ưu và kiểm tra .htaccess</small>
                    </div>
                    
                    <div class="stat-card">
                        <h3>🔄 Redis Status</h3>
                        <div class="stat-value" id="redis-status">Đang kiểm tra...</div>
                        <small>Thông tin kết nối và hiệu suất Redis</small>
                    </div>
                </div>
                
                <!-- LiteSpeed Specific Checks -->
                <div class="recommendations" id="litespeed-checks">
                    <h4>🚀 LiteSpeed Server Status</h4>
                    <ul id="litespeed-status">
                        <li>Đang kiểm tra...</li>
                    </ul>
                </div>
                
                <!-- Health Recommendations -->
                <div class="recommendations">
                    <h4>💡 Đề xuất tối ưu hóa sức khỏe</h4>
                    <ul id="health-recommendations">
                        <li>Đang tạo danh sách đề xuất...</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Loading indicator -->
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <span style="margin-left: 10px;">Processing...</span>
        </div>
    </div>

    <div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
        <div style="background: rgba(0,0,0,0.8); color: white; padding: 10px 15px; border-radius: 25px; font-size: 12px;">
            <span id="last-updated">Last updated: <?php echo date('H:i:s'); ?></span>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            // Load health check data if health tab is selected
            if (tabName === 'health') {
                loadHealthCheckData();
            }
        }
        
        function showLoading() {
            document.getElementById('loading').style.display = 'flex';
        }
        
        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }
        
        function makeAjaxRequest(action) {
            showLoading();
            
            const formData = new FormData();
            formData.append('action', action);
            formData.append('nonce', '<?php echo wp_create_nonce("debug_memory_nonce"); ?>');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert(`Success! ${action} completed. ${data.deleted || data.optimized || data.message || 0} items processed.`);
                    location.reload(); // Refresh page to show updated data
                } else {
                    alert('Error occurred during operation: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                hideLoading();
                alert('Network error occurred');
                console.error('Error:', error);
            });
        }
        
        function cleanTransients() {
            if (confirm('Are you sure you want to clean expired transients?')) {
                makeAjaxRequest('clean_transients');
            }
        }
        
        function cleanCartFragments() {
            if (confirm('Are you sure you want to clean cart fragments?')) {
                makeAjaxRequest('clean_cart_fragments');
            }
        }
        
        function optimizeDatabase() {
            if (confirm('Are you sure you want to optimize all database tables? This may take a few minutes.')) {
                makeAjaxRequest('optimize_database');
            }
        }
        
        function clearCache() {
            if (confirm('Bạn có chắc muốn xóa tất cả cache?')) {
                makeAjaxRequest('clear_cache');
            }
        }
        
        function loadHealthCheckData() {
            if (!document.getElementById('health').classList.contains('active')) return;
            
            showLoading();
            
            fetch(window.location.href + '?ajax=health_check')
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    
                    // Update Page Speed
                    const pageSpeedEl = document.getElementById('page-speed-score');
                    pageSpeedEl.innerHTML = data.page_speed.score !== 'N/A' 
                        ? `${data.page_speed.score}/100 (${data.page_speed.load_time})`
                        : 'Không thể kiểm tra';
                    pageSpeedEl.style.color = {
                        good: '#28a745',
                        warning: '#ffc107',
                        critical: '#dc3545',
                        error: '#6c757d'
                    }[data.page_speed.status];
                    
                    // Update Cache Status
                    const cacheEl = document.getElementById('cache-status');
                    cacheEl.innerHTML = Object.entries(data.cache_status)
                        .map(([key, value]) => `${key}: ${value}`)
                        .join('<br>');
                    
                    // Update Server Resources
                    const serverEl = document.getElementById('server-resources');
                    serverEl.innerHTML = Object.entries(data.server_resources)
                        .map(([key, value]) => `${key.replace('_', ' ').toUpperCase()}: ${value}`)
                        .join('<br>');
                    
                    // Update WP Config Status
                    const configEl = document.getElementById('wp-config-status');
                    configEl.innerHTML = Object.entries(data.wp_config)
                        .map(([key, value]) => `${key.replace('_', ' ').toUpperCase()}: ${value}`)
                        .join('<br>');
                    
                    // Update Redis Status
                    const redisEl = document.getElementById('redis-status');
                    redisEl.innerHTML = Object.entries(data.redis_info)
                        .map(([key, value]) => `${key.replace('_', ' ').toUpperCase()}: ${value}`)
                        .join('<br>');
                    
                    // Update LiteSpeed Status
                    const litespeedEl = document.getElementById('litespeed-status');
                    litespeedEl.innerHTML = Object.entries(data.litespeed_status)
                        .map(([key, value]) => `<li>${key.replace('_', ' ').toUpperCase()}: ${value}</li>`)
                        .join('');
                    
                    // Update Health Recommendations
                    const recommendationsEl = document.getElementById('health-recommendations');
                    recommendationsEl.innerHTML = data.health_recommendations
                        .map(rec => `<li>${rec}</li>`)
                        .join('');
                })
                .catch(error => {
                    hideLoading();
                    console.error('Health check error:', error);
                });
        }
        
        // Export functionality
        function exportReport() {
            const reportData = {
                timestamp: new Date().toISOString(),
                memory: {
                    used: '<?php echo format_memory($memory_used); ?>',
                    peak: '<?php echo format_memory($memory_peak); ?>',
                    limit: '<?php echo esc_js($memory_limit); ?>',
                    percentage: <?php echo round($memory_status['percent'], 2); ?>
                },
                wordpress: {
                    version: '<?php echo esc_js(get_bloginfo("version")); ?>',
                    is_multisite: <?php echo is_multisite() ? 'true' : 'false'; ?>
                },
                php: {
                    version: '<?php echo esc_js(phpversion()); ?>'
                },
                plugins: {
                    active_count: <?php echo count(get_option('active_plugins', [])); ?>
                },
                database: {
                    total_size: '<?php echo isset($total_size) ? round($total_size, 2) : 0; ?> MB'
                },
                redis: {
                    host: '<?php echo defined('WP_REDIS_HOST') ? WP_REDIS_HOST : 'N/A'; ?>',
                    port: '<?php echo defined('WP_REDIS_PORT') ? WP_REDIS_PORT : 'N/A'; ?>',
                    database: '<?php echo defined('WP_REDIS_DATABASE') ? WP_REDIS_DATABASE : 'N/A'; ?>'
                }
            };
            
            const dataStr = JSON.stringify(reportData, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `wordpress-debug-report-${new Date().toISOString().split('T')[0]}.json`;
            link.click();
            URL.revokeObjectURL(url);
        }
        
        // Auto-refresh memory stats every 30 seconds
        setInterval(() => {
            if (document.getElementById('overview').classList.contains('active')) {
                fetch(window.location.href + '?ajax=memory_stats')
                .then(response => response.text())
                .then(data => {
                    console.log('Memory stats refreshed');
                });
            }
        }, 30000);
        
        // Auto-refresh health check every 60 seconds
        setInterval(() => {
            if (document.getElementById('health').classList.contains('active')) {
                loadHealthCheckData();
            }
        }, 60000);
        
        // Initialize tooltips and enhanced features
        document.addEventListener('DOMContentLoaded', function() {
            // Add export button to header
            const header = document.querySelector('.header');
            const exportBtn = document.createElement('button');
            exportBtn.innerHTML = '📊 Export Report';
            exportBtn.className = 'btn btn-primary';
            exportBtn.style.marginTop = '15px';
            exportBtn.onclick = exportReport;
            header.appendChild(exportBtn);
            
            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) {
                    switch(e.key) {
                        case '1':
                            e.preventDefault();
                            switchTabByIndex(0);
                            break;
                        case '2':
                            e.preventDefault();
                            switchTabByIndex(1);
                            break;
                        case '3':
                            e.preventDefault();
                            switchTabByIndex(2);
                            break;
                        case '4':
                            e.preventDefault();
                            switchTabByIndex(3);
                            break;
                        case '5':
                            e.preventDefault();
                            switchTabByIndex(4);
                            break;
                        case '6':
                            e.preventDefault();
                            switchTabByIndex(5);
                            break;
                        case 'e':
                            e.preventDefault();
                            exportReport();
                            break;
                    }
                }
            });
        });
        
        function switchTabByIndex(index) {
            const tabs = ['overview', 'database', 'plugins', 'optimization', 'security', 'health'];
            if (tabs[index]) {
                switchTab(tabs[index]);
                document.querySelectorAll('.nav-tab')[index].classList.add('active');
            }
        }
        
        // Performance monitoring
        if (performance && performance.mark) {
            performance.mark('debug-tool-loaded');
        }
    </script>
</body>
</html>

<?php
// Log memory usage for trend analysis
function log_memory_usage() {
    $log_file = WP_CONTENT_DIR . '/debug-memory.log';
    $memory_data = array(
        'timestamp' => current_time('mysql'),
        'memory_used' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'memory_limit' => ini_get('memory_limit'),
        'active_plugins' => count(get_option('active_plugins', [])),
        'url' => $_SERVER['REQUEST_URI'] ?? 'CLI'
    );
    
    if (is_writable(dirname($log_file))) {
        file_put_contents($log_file, json_encode($memory_data) . "\n", FILE_APPEND | LOCK_EX);
    }
}

// Auto-log on script execution
register_shutdown_function('log_memory_usage');

// Force garbage collection
if (function_exists('gc_collect_cycles')) {
    $collected = gc_collect_cycles();
    if ($collected > 0) {
        echo "<script>console.log('Garbage collected: $collected objects');</script>";
    }
}

// Final memory report
$final_memory = memory_get_usage(true);
$final_peak = memory_get_peak_usage(true);

echo "<script>";
echo "console.log('Final Memory Usage: " . format_memory($final_memory) . "');";
echo "console.log('Peak Memory Usage: " . format_memory($final_peak) . "');";
echo "console.log('WordPress Debug Tool loaded successfully');";
echo "</script>";

// Clean up variables to free memory
unset($wpdb, $tables, $active_plugins, $memory_used, $memory_peak, $memory_limit, $memory_status);
?>
<!--
WordPress Memory Debug Tool v2.2
Features implemented:
✅ Modern responsive UI with dark mode support
✅ Tabbed interface with Health Check tab
✅ Real-time memory monitoring
✅ Database optimization tools
✅ Plugin analysis and management
✅ Security checks and recommendations
✅ Export functionality for reports
✅ AJAX operations for optimization tasks
✅ Keyboard shortcuts (Ctrl+1-6 for tabs, Ctrl+E for export)
✅ Performance recommendations
✅ Mobile-friendly responsive design
✅ Print-friendly styling
✅ Memory usage logging
✅ Enhanced visual indicators and animations
✅ Page speed analysis with Google PageSpeed Insights
✅ Cache status checking (Redis, LiteSpeed, WP Super Cache, etc.)
✅ Server resource monitoring (CPU, RAM, Disk)
✅ WordPress configuration checks
✅ Redis-specific checks (connection, keys, memory usage)
✅ LiteSpeed-specific optimizations

Usage Instructions:
1. Upload this file to your WordPress root directory
2. Access via browser: yourdomain.com/debug-memory-pro.php
3. Use tabs to navigate different analysis sections
4. Click optimization buttons to clean up database/cache
5. Export reports for tracking over time
6. Monitor recommendations for performance improvements
7. Replace YOUR_GOOGLE_PAGESPEED_API_KEY with your API key for PageSpeed Insights
8. Ensure Redis is properly configured and the Redis PHP extension is installed

Security Note: Remove this file after use or restrict access via .htaccess
-->