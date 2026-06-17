<?php
/*
Plugin Name: WordPress Theme & Plugin Scanner
Description: Scans themes and plugins for backdoors, tracking code, hidden services, and license restrictions, with a focus on Canhcam - Licsence and similar plugins.
Version: 2.1
Author: Grok
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Admin menu
add_action('admin_menu', 'scanner_menu');
function scanner_menu() {
    add_menu_page(
        'Theme & Plugin Scanner',
        'Theme & Plugin Scanner',
        'manage_options',
        'scanner',
        'scanner_page',
        'dashicons-shield'
    );
}

// Enqueue styles and scripts
add_action('admin_enqueue_scripts', 'scanner_enqueue_assets');
function scanner_enqueue_assets($hook) {
    if ($hook !== 'toplevel_page_scanner') {
        return;
    }
    // Tailwind CSS via CDN
    wp_enqueue_style('tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');
    // Custom JavaScript for table sorting, filtering, and export
    wp_enqueue_script('scanner-js', plugin_dir_url(__FILE__) . 'scanner.js', ['jquery'], '2.1', true);
    // Localize script for AJAX
    wp_localize_script('scanner-js', 'scannerAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('scanner_nonce')
    ]);
}

// Scanner page
function scanner_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    ?>
    <div class="wrap p-4">
        <h1 class="text-2xl font-bold mb-4">Theme & Plugin Scanner</h1>
        <p class="mb-4">Scanning active theme: <?php echo esc_html(wp_get_theme()->get('Name')); ?> and all plugins, including Canhcam - Licsence.</p>
        <form method="post" class="mb-4 flex items-center space-x-4">
            <button type="submit" name="run_scan" id="run-scan" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Run Scan</button>
            <button type="button" id="export-csv" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Export CSV</button>
            <select id="filter-type" class="border rounded p-2">
                <option value="">All Issue Types</option>
                <option value="backdoor">Backdoor</option>
                <option value="tracking">Tracking</option>
                <option value="hidden_service">Hidden Service</option>
                <option value="license_check">License Check</option>
                <option value="css_hiding">CSS/Feature Hiding</option>
            </select>
            <input type="text" id="filter-plugin" placeholder="Filter by Plugin (e.g., Canhcam)" class="border rounded p-2">
        </form>
        <div id="scan-progress" class="hidden mb-4">
            <div class="w-full bg-gray-200 rounded">
                <div id="progress-bar" class="bg-blue-500 h-4 rounded" style="width: 0%"></div>
            </div>
            <p id="progress-text" class="text-sm text-gray-600"></p>
        </div>
        <div id="scanner-results" class="overflow-x-auto">
            <?php
            $results = get_transient('scanner_results');
            if ($results) {
                scanner_display_results($results);
            } else {
                echo '<p class="text-gray-600">Run a scan to see results.</p>';
            }
            ?>
        </div>
    </div>
    <?php
}

// Display results
function scanner_display_results($results) {
    if (empty($results)) {
        echo '<p class="text-green-600">No suspicious code found.</p>';
        return;
    }
    ?>
    <table id="scanner-table" class="min-w-full bg-white border border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2 border cursor-pointer" data-sort="file">File</th>
                <th class="px-4 py-2 border cursor-pointer" data-sort="line">Line</th>
                <th class="px-4 py-2 border">Code</th>
                <th class="px-4 py-2 border cursor-pointer" data-sort="type">Type</th>
                <th class="px-4 py-2 border">Description</th>
                <th class="px-4 py-2 border">Fix</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result) : ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border"><?php echo esc_html($result['file']); ?></td>
                    <td class="px-4 py-2 border"><?php echo esc_html($result['line']); ?></td>
                    <td class="px-4 py-2 border"><code class="bg-gray-100 p-1 rounded"><?php echo esc_html($result['code']); ?></code></td>
                    <td class="px-4 py-2 border"><?php echo esc_html($result['type']); ?></td>
                    <td class="px-4 py-2 border"><?php echo esc_html($result['desc']); ?></td>
                    <td class="px-4 py-2 border"><?php echo esc_html($result['fix']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

// AJAX handler for scanning
add_action('wp_ajax_scanner_run', 'scanner_run_ajax');
function scanner_run_ajax() {
    check_ajax_referer('scanner_nonce', 'nonce');
    $results = scanner_run();
    set_transient('scanner_results', $results, HOUR_IN_SECONDS);
    ob_start();
    scanner_display_results($results);
    wp_send_json_success(['html' => ob_get_clean()]);
}

// Scan function
function scanner_run() {
    $patterns = array(
        'backdoor' => array(
            'regex' => '/(eval|base64_decode|exec|shell_exec|system|passthru)\s*\(/i',
            'desc' => 'Potential backdoor or code execution',
            'fix' => 'Remove or comment out the suspicious function call. Verify its purpose.'
        ),
        'tracking' => array(
            'regex' => '/(<script[^>]*src\s*=\s*[\'"](https?:\/\/[^\'"]+track[^\'"]*)[\'"][^>]*>|wp_remote_get\s*\(\s*[\'"](https?:\/\/[^\'"]+track[^\'"]*)[\'"]\s*\)|beacon|analytics)/i',
            'desc' => 'Tracking script or external beacon, possibly from Canhcam - Licsence',
            'fix' => 'Remove tracking scripts or requests. Verify the external domain.'
        ),
        'hidden_service' => array(
            'regex' => '/(wp_schedule_event|wp_remote_post\s*\(\s*[\'"](https?:\/\/[^\'"]+)[\'"]|include\s*\(\s*[\'"][^\'"]+remote[^\'"]*[\'"]\s*\))/i',
            'desc' => 'Hidden service or remote connection',
            'fix' => 'Remove cron jobs or remote includes unless verified as safe.'
        ),
        'license_check' => array(
            'regex' => '/(license|verify_license|license_expired|is_license_valid|check_license|canhcam_license)\s*\(/i',
            'desc' => 'License verification or restriction code, likely from Canhcam - Licsence',
            'fix' => 'Bypass license checks by returning true or remove restrictive code. Add to child theme or custom plugin.'
        ),
        'css_hiding' => array(
            'regex' => '/(display\s*:\s*none|visibility\s*:\s*hidden|opacity\s*:\s*0).*(product|feature|woocommerce)|document\.querySelector\s*\(\s*[\'"][^\'"]*(product|feature|woocommerce)[^\'"]*[\'"][^)]*\)\.style\.(display|visibility|opacity)/i',
            'desc' => 'CSS or JS hiding product/features, possibly tied to license expiration',
            'fix' => 'Remove conditional hiding. Override in child theme CSS: .product { display: block !important; }'
        )
    );

    $results = array();
    $directories = array(
        'theme' => get_template_directory(),
        'plugins' => WP_PLUGIN_DIR
    );

    foreach ($directories as $type => $dir) {
        $files = scanner_scan_directory($dir);
        foreach ($files as $file) {
            $is_functions = (basename($file) === 'functions.php');
            $content = file_get_contents($file);
            $lines = explode("\n", $content);

            foreach ($patterns as $ptype => $pattern) {
                if (preg_match_all($pattern['regex'], $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        $line_number = scanner_get_line_number($content, $match[1]);
                        $results[] = array(
                            'file' => str_replace(WP_CONTENT_DIR, '', $file),
                            'line' => $line_number,
                            'code' => esc_html(trim($match[0])),
                            'type' => $ptype . ($is_functions ? ' (in functions.php)' : ''),
                            'desc' => $pattern['desc'],
                            'fix' => $pattern['fix']
                        );
                    }
                }
            }
        }
    }

    return $results;
}

// Helper function to scan directory
function scanner_scan_directory($dir) {
    $files = array();
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($items as $item) {
        if ($item->isFile() && in_array(strtolower($item->getExtension()), ['php', 'js', 'css'])) {
            $files[] = $item->getPathname();
        }
    }
    return $files;
}

// Helper function to get line number
function scanner_get_line_number($content, $offset) {
    $before = substr($content, 0, $offset);
    return substr_count($before, "\n") + 1;
}
?>