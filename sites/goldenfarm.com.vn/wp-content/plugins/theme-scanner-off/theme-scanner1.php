<?php
/*
Plugin Name: WordPress Theme Scanner
Description: Scans the active WordPress theme for backdoors, hidden ads, remote code, and license restrictions.
Version: 1.0
Author: Grok
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Admin menu
add_action('admin_menu', 'theme_scanner_menu');
function theme_scanner_menu() {
    add_menu_page(
        'Theme Scanner',
        'Theme Scanner',
        'manage_options',
        'theme-scanner',
        'theme_scanner_page',
        'dashicons-shield'
    );
}

// Scanner page
function theme_scanner_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    echo '<div class="wrap"><h1>WordPress Theme Scanner</h1>';
    echo '<p>Scanning the active theme: ' . wp_get_theme()->get('Name') . '</p>';

    // Define suspicious patterns
    $patterns = array(
        'backdoor' => array(
            'regex' => '/(eval|base64_decode|exec|shell_exec|system|passthru)\s*\(/i',
            'desc' => 'Potential backdoor or code execution',
            'fix' => 'Remove or comment out the suspicious function call. Verify its purpose.'
        ),
        'hidden_links' => array(
            'regex' => '/(<a[^>]+style\s*=\s*[\'"](display\s*:\s*none|visibility\s*:\s*hidden)[\'"][^>]*>|<script[^>]*>.*?(atob|document\.write).*</script>)/i',
            'desc' => 'Hidden links or injected JavaScript for ads',
            'fix' => 'Remove hidden links or scripts. Check for external domains.'
        ),
        'remote_code' => array(
            'regex' => '/(file_get_contents|curl_exec|wp_remote_get|wp_remote_post)\s*\(\s*[\'"](https?:\/\/[^\'"]+)[\'"]\s*\)/i',
            'desc' => 'Remote code fetching or execution',
            'fix' => 'Verify the external URL. Remove if untrusted or unnecessary.'
        ),
        'license_check' => array(
            'regex' => '/(license|verify_license|license_expired|is_license_valid)\s*\(/i',
            'desc' => 'License verification or restriction code',
            'fix' => 'Check if license code hides CSS or features. Remove or bypass if malicious.'
        ),
        'css_hiding' => array(
            'regex' => '/(display\s*:\s*none|visibility\s*:\s*hidden).*(product|feature)/i',
            'desc' => 'CSS hiding product features or elements',
            'fix' => 'Remove conditional CSS hiding. Restore visibility of elements.'
        )
    );

    // Get active theme directory
    $theme_dir = get_template_directory();
    $results = array();

    // Recursively scan theme files
    $files = theme_scanner_scan_directory($theme_dir);
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $lines = explode("\n", $content);

        foreach ($patterns as $type => $pattern) {
            if (preg_match_all($pattern['regex'], $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line_number = theme_scanner_get_line_number($content, $match[1]);
                    $results[] = array(
                        'file' => str_replace($theme_dir, '', $file),
                        'line' => $line_number,
                        'code' => esc_html(trim($match[0])),
                        'type' => $type,
                        'desc' => $pattern['desc'],
                        'fix' => $pattern['fix']
                    );
                }
            }
        }
    }

    // Display results
    if (empty($results)) {
        echo '<p>No suspicious code found.</p>';
    } else {
        echo '<table class="widefat"><thead><tr><th>File</th><th>Line</th><th>Code</th><th>Type</th><th>Description</th><th>Fix</th></tr></thead><tbody>';
        foreach ($results as $result) {
            echo '<tr>';
            echo '<td>' . esc_html($result['file']) . '</td>';
            echo '<td>' . esc_html($result['line']) . '</td>';
            echo '<td><code>' . $result['code'] . '</code></td>';
            echo '<td>' . esc_html($result['type']) . '</td>';
            echo '<td>' . esc_html($result['desc']) . '</td>';
            echo '<td>' . esc_html($result['fix']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}

// Helper function to scan directory
function theme_scanner_scan_directory($dir) {
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
function theme_scanner_get_line_number($content, $offset) {
    $before = substr($content, 0, $offset);
    return substr_count($before, "\n") + 1;
}

// Enqueue styles for the scanner page
add_action('admin_enqueue_scripts', 'theme_scanner_styles');
function theme_scanner_styles($hook) {
    if ($hook !== 'toplevel_page_theme-scanner') {
        return;
    }
    wp_enqueue_style('theme-scanner-style', plugin_dir_url(__FILE__) . 'style.css');
}
?>