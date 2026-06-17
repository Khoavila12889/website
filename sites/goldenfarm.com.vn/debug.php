<?php
/**
 * WordPress Memory Debug & Optimization Tool - FIXED VERSION
 * Version: 2.0 Improved & Fixed
 * File: debug-memory-pro-fixed.php
 * Upload to WordPress root directory
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security check
if (!file_exists('wp-config.php')) {
    die('<strong>Error:</strong> wp-config.php not found. Please place this file in WordPress root directory.');
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
    // Load WordPress configuration first
    require_once 'wp-config.php';
    
    // FIXED: Load minimal WordPress to avoid function conflicts
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(__FILE__) . '/');
    }
    
    // Try to load WordPress properly first
    if (file_exists(ABSPATH . 'wp-load.php')) {
        // Load WordPress completely to avoid function conflicts
        require_once ABSPATH . 'wp-load.php';
    } else {
        // Fallback: Load WordPress core files manually
        if (!defined('WPINC')) {
            define('WPINC', 'wp-includes');
        }
        
        // Load essential WordPress files only if they exist
        $required_files = [
            ABSPATH . WPINC . '/wp-db.php',
            ABSPATH . WPINC . '/functions.php',
            ABSPATH . WPINC . '/option.php', 
            ABSPATH . WPINC . '/plugin.php',
            ABSPATH . WPINC . '/version.php'
        ];
        
        foreach ($required_files as $file) {
            if (!file_exists($file)) {
                throw new Exception("Required file not found: $file");
            }
            require_once $file;
        }
    }
    
    // Initialize database connection safely
    if (!isset($wpdb)) {
        $wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
        $wpdb->set_prefix($table_prefix ?? 'wp_');
    }
    
    // Test database connection
    if (!$wpdb->check_connection()) {
        throw new Exception("Database connection failed");
    }
    
    // Load additional WordPress functions if needed
    if (!function_exists('wp_count_posts') && file_exists(ABSPATH . WPINC . '/post.php')) {
        require_once ABSPATH . WPINC . '/post.php';
    }
    
    if (!function_exists('get_plugin_data') && file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    // FIXED: Safely check WooCommerce without causing errors
    $woocommerce_active = false;
    if (defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php')) {
        $active_plugins = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'active_plugins'");
        if ($active_plugins) {
            $active_plugins = maybe_unserialize($active_plugins);
            $woocommerce_active = is_array($active_plugins) && in_array('woocommerce/woocommerce.php', $active_plugins);
        }
    }
    
} catch (Exception $e) {
    die('<div style="background:#f8d7da;color:#721c24;padding:20px;border:1px solid #f5c6cb;border-radius:5px;margin:20px;"><strong>Error:</strong> ' . esc_html($e->getMessage()) . '<br><small>Please ensure this file is in the WordPress root directory and WordPress is properly installed.</small></div>');
}

// FIXED: Ensure $wpdb is available globally
global $wpdb;

// Handle AJAX actions with better error handling
if (isset($_POST['action'])) {
    handle_ajax_action();
    exit;
}

// FIXED: Better memory conversion function - only declare if not exists
if (!function_exists('wp_convert_hr_to_bytes')) {
    function wp_convert_hr_to_bytes($size) {
        if (is_numeric($size)) {
            return (int) $size;
        }
        
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;
        
        switch($last) {
            case 'g': $size *= 1024;
            case 'm': $size *= 1024; 
            case 'k': $size *= 1024;
        }
        
        return $size;
    }
}

// Utility functions
function format_memory($bytes) {
    if ($bytes === false || $bytes === null || $bytes === 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

function get_memory_status($current, $limit) {
    $limit_bytes = wp_convert_hr_to_bytes($limit);
    if ($limit_bytes <= 0) return ['status' => 'unknown', 'color' => '#6c757d', 'percent' => 0];
    
    $usage_percent = ($current / $limit_bytes) * 100;
    
    if ($usage_percent > 90) return ['status' => 'critical', 'color' => '#e74c3c', 'percent' => $usage_percent];
    if ($usage_percent > 70) return ['status' => 'warning', 'color' => '#f39c12', 'percent' => $usage_percent];
    return ['status' => 'good', 'color' => '#27ae60', 'percent' => $usage_percent];
}

// FIXED: Better AJAX handler with proper security and error handling
function handle_ajax_action() {
    global $wpdb;
    
    // Simple security check (since wp_verify_nonce might not be available)
    if (!isset($_POST['security']) || $_POST['security'] !== 'debug_memory_security') {
        wp_die('Security check failed');
    }
    
    // Ensure database connection
    if (!$wpdb || !$wpdb->check_connection()) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        return;
    }
    
    try {
        switch ($_POST['action']) {
            case 'clean_transients':
                $deleted = $wpdb->query("
                    DELETE o1, o2 FROM {$wpdb->options} o1
                    LEFT JOIN {$wpdb->options} o2 ON o2.option_name = REPLACE(o1.option_name, '_transient_timeout_', '_transient_')
                    WHERE o1.option_name LIKE '_transient_timeout_%' 
                    AND o1.option_value < UNIX_TIMESTAMP()
                ");
                echo json_encode(['success' => true, 'deleted' => intval($deleted)]);
                break;
                
            case 'clean_cart_fragments':
                $deleted = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_wc_cart_hash_%'");
                echo json_encode(['success' => true, 'deleted' => intval($deleted)]);
                break;
                
            case 'optimize_database':
                $tables = $wpdb->get_col("SHOW TABLES");
                $optimized = 0;
                foreach ($tables as $table) {
                    $result = $wpdb->query("OPTIMIZE TABLE `" . esc_sql($table) . "`");
                    if ($result !== false) {
                        $optimized++;
                    }
                }
                echo json_encode(['success' => true, 'optimized' => $optimized]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// FIXED: Better function existence checks - only declare if not exists
if (!function_exists('wp_using_ext_object_cache')) {
    function wp_using_ext_object_cache() {
        return function_exists('wp_cache_get') && !defined('WP_CACHE') || (defined('WP_CACHE') && WP_CACHE);
    }
}

if (!function_exists('is_multisite')) {
    function is_multisite() {
        return defined('MULTISITE') && MULTISITE;
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '') {
        global $wp_version;
        switch ($show) {
            case 'version':
                return $wp_version ?? 'Unknown';
            case 'name':
                global $wpdb;
                if ($wpdb) {
                    $name = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'blogname'");
                    return $name ?: 'WordPress Site';
                }
                return 'WordPress Site';
            default:
                return '';
        }
    }
}

if (!function_exists('get_option')) {
    function get_option($option_name, $default = false) {
        global $wpdb;
        if (!$wpdb) return $default;
        
        $value = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $option_name));
        if ($value === null) return $default;
        
        return maybe_unserialize($value);
    }
}

if (!function_exists('maybe_unserialize')) {
    function maybe_unserialize($original) {
        if (is_serialized($original)) {
            return @unserialize($original);
        }
        return $original;
    }
}

if (!function_exists('is_serialized')) {
    function is_serialized($data) {
        if (!is_string($data)) return false;
        $data = trim($data);
        if ('N;' == $data) return true;
        if (strlen($data) < 4) return false;
        if (':' !== $data[1]) return false;
        $semicolon = strpos($data, ';');
        $brace = strpos($data, '}');
        if (false === $semicolon && false === $brace) return false;
        return @unserialize($data) !== false;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_sql')) {
    function esc_sql($data) {
        global $wpdb;
        return $wpdb ? $wpdb->_escape($data) : addslashes($data);
    }
}

if (!function_exists('esc_js')) {
    function esc_js($text) {
        return json_encode($text);
    }
}

// Get system info safely
$memory_limit = ini_get('memory_limit') ?: '128M';
$memory_used = memory_get_usage(true);
$memory_peak = memory_get_peak_usage(true);
$memory_status = get_memory_status($memory_used, $memory_limit);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Memory Debug Pro - Fixed</title>
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
        
        .content {
            padding: 30px;
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
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
        
        @media (max-width: 768px) {
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 WordPress Memory Debug Pro - Fixed</h1>
            <div><?php echo esc_html(date('Y-m-d H:i:s')); ?> | <?php echo esc_html(get_bloginfo('name')); ?></div>
        </div>
        
        <div class="content">
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
            
            <!-- Database Info -->
            <h3>🗄️ Database Status</h3>
            <div class="stats-grid">
                <?php
                try {
                    $table_count = $wpdb->get_var("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
                    $db_size = $wpdb->get_var("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
                    $transients = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
                } catch (Exception $e) {
                    $table_count = 'Unknown';
                    $db_size = 'Unknown'; 
                    $transients = 'Unknown';
                }
                ?>
                <div class="stat-card">
                    <h3>📊 Database Tables</h3>
                    <div class="stat-value"><?php echo $table_count; ?></div>
                </div>
                <div class="stat-card">
                    <h3>📦 Database Size</h3>
                    <div class="stat-value"><?php echo $db_size; ?> MB</div>
                </div>
                <div class="stat-card">
                    <h3>🔄 Transients</h3>
                    <div class="stat-value"><?php echo $transients; ?></div>
                    <?php if ($transients > 100): ?>
                    <button class="btn btn-warning" onclick="cleanTransients()">Clean</button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- WooCommerce Status -->
            <?php if ($woocommerce_active): ?>
            <h3>🛒 WooCommerce Status</h3>
            <div class="alert alert-success">
                ✅ WooCommerce is active and detected
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                🛒 WooCommerce is not active or not detected
            </div>
            <?php endif; ?>
            
            <!-- Recommendations -->
            <div class="recommendations">
                <h4>💡 Performance Recommendations</h4>
                <ul>
                    <li>Enable object caching (Redis/Memcached) for better performance</li>
                    <?php if ($memory_status['percent'] > 70): ?>
                    <li>Consider increasing PHP memory limit or optimizing plugins</li>
                    <?php endif; ?>
                    <li>Regularly clean up expired transients and cache</li>
                    <li>Optimize database tables monthly</li>
                    <li>Monitor plugin performance and disable unused ones</li>
                </ul>
            </div>
            
            <!-- Action Buttons -->
            <div style="text-align: center; margin-top: 30px;">
                <button class="btn btn-primary" onclick="location.reload()">🔄 Refresh Data</button>
                <button class="btn btn-success" onclick="optimizeDatabase()">⚡ Optimize Database</button>
                <button class="btn btn-warning" onclick="cleanTransients()">🧹 Clean Transients</button>
            </div>
        </div>
    </div>

    <script>
        function makeAjaxRequest(action) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('security', 'debug_memory_security');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Success! ${action} completed. ${data.deleted || data.optimized || 0} items processed.`);
                    location.reload();
                } else {
                    alert('Error occurred: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Network error occurred');
                console.error('Error:', error);
            });
        }
        
        function cleanTransients() {
            if (confirm('Are you sure you want to clean expired transients?')) {
                makeAjaxRequest('clean_transients');
            }
        }
        
        function optimizeDatabase() {
            if (confirm('Are you sure you want to optimize database tables?')) {
                makeAjaxRequest('optimize_database');
            }
        }
        
        // Auto-update timestamp
        setInterval(() => {
            const now = new Date();
            document.querySelector('.header div').innerHTML = 
                now.getFullYear() + '-' + 
                String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                String(now.getDate()).padStart(2, '0') + ' ' + 
                String(now.getHours()).padStart(2, '0') + ':' + 
                String(now.getMinutes()).padStart(2, '0') + ':' + 
                String(now.getSeconds()).padStart(2, '0') + 
                ' | <?php echo esc_js(get_bloginfo("name")); ?>';
        }, 1000);
    </script>
</body>
</html>

<?php
// Cleanup
if (function_exists('gc_collect_cycles')) {
    gc_collect_cycles();
}
?>