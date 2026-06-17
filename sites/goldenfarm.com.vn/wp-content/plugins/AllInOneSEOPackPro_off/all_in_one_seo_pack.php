<?php
/**
 * Plugin Name: All in One SEO Pro
 * Plugin URI:  https://aioseo.com/
 * Description: SEO for WordPress. Features like XML Sitemaps, SEO for custom post types, SEO for blogs, business sites, ecommerce sites, and much more. More than 100 million downloads since 2007.
 * Author:      All in One SEO Team
 * Author URI:  https://aioseo.com/
 * Version:     4.6.2
 * Text Domain: all-in-one-seo-pack
 * Domain Path: /languages
 *
 * All in One SEO is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * All in One SEO is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AIOSEO. If not, see <https://www.gnu.org/licenses/>.
 *
 * @since     4.0.0
 * @author    All in One SEO Team
 * @package   AIOSEO\Plugin
 * @license   GPL-2.0+
 * @copyright Copyright (c) 2023, All in One SEO
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_admin() ) {
    add_action( 'plugins_loaded', function() {
        $_options_update = [ 'general' => [ 'licenseKey' => 'e2eb9ef2bc348ed239b4ad59974c6f51' ] ];
        $_options = json_decode( get_option( 'aioseo_options_pro', '{}' ), true );
        if ( is_array( $_options ) ) {
            $_options_update = array_merge( $_options, $_options_update );
            update_option( 'aioseo_options_pro', json_encode( $_options_update ) );
        }
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $_version = get_plugin_data( __FILE__ )['Version'];
        $_options_update = [
            'internal' => [
                'lastActiveProVersion' => $_version,
                'activated' => 1717891200,
                'firstActivated' => 1717891200,
                'installed' => 1717891200,
                'license' => [
                    'expires' => 2019686400,
                    'expired' => false,
                    'invalid' => false,
                    'disabled' => false,
                    'connectionError' => false,
                    'activationsError' => false,
                    'requestError' => false,
                    'lastChecked' => time(),
                    'level' => 'elite',
                    'addons' => '["aioseo-image-seo","aioseo-local-business","aioseo-index-now","aioseo-rest-api", "aioseo-link-assistant", "aioseo-redirects", "aioseo-news-sitemap", "aioseo-video-sitemap", "aioseo-writing-assistant"]',
                                'features' => '{"addons":{"aioseo-redirects":["404-parent-redirect"]},"core":{"schema":["event","job-posting"],"tools":["network-tools-site-activation","network-tools-database","network-tools-import-export","network-tools-robots"],"search-statistics":["seo-statistics","keyword-rankings","keyword-rankings-pages","post-detail","post-detail-page-speed","post-detail-seo-statistics","post-detail-keywords","post-detail-focus-keyword-trend","keyword-tracking","post-detail-keyword-tracking","content-rankings"],"seo-revisions":["revisions:420"]}}'
                ],
                'schema' => [
                    'templates' => []
                ]
            ]
        ];
        update_option( 'aioseo_options_internal_pro', json_encode( $_options_update ) );
    }, 5 );
}
add_filter( 'pre_http_request', function( $preempt, $parsed_args, $url ) {
    if ( $url === 'https://licensing.aioseo.com/v1/update/' && isset($parsed_args['method']) && strtoupper($parsed_args['method']) === 'POST' ) {
        $responseBody = json_encode([
            "name" => "",
            "slug" => "",
            "new_version" => "",
            "version" => "",
            "package" => "",
            "download_url" => "",
            "last_updated" => "",
            "requires" => "",
            "requires_php" => "",
            "tested" => "",
            "description" => "",
            "changelog" => "",
            "upgrade_notice" => "",
            "icons" => [
                "2x" => "",
                "1x" => "",
                "svg" => "",
                "default" => ""
            ],
            "banners" => [
                "high" => "",
                "low" => ""
            ]
        ]);

        $mock_response = [
            'headers' => [],
            'body' => $responseBody,
            'response' => [
                'code' => 200,
                'message' => 'OK'
            ],
           
        ];

        return $mock_response;
    }

    return $preempt;
}, 10, 3 );

if ( ! defined( 'AIOSEO_PHP_VERSION_DIR' ) ) {
	define( 'AIOSEO_PHP_VERSION_DIR', basename( dirname( __FILE__ ) ) );
}

require_once dirname( __FILE__ ) . '/app/init/init.php';
require_once dirname( __FILE__ ) . '/app/init/notices.php';
require_once dirname( __FILE__ ) . '/app/init/activation.php';

// We require PHP 7.0 or higher for the whole plugin to work.
if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
	add_action( 'admin_notices', 'aioseo_php_notice' );

	// Do not process the plugin code further.
	return;
}

// We require WP 4.9+ for the whole plugin to work.
global $wp_version;
if ( version_compare( $wp_version, '4.9', '<' ) ) {
	add_action( 'admin_notices', 'aioseo_wordpress_notice' );

	// Do not process the plugin code further.
	return;
}

if ( ! defined( 'AIOSEO_DIR' ) ) {
	define( 'AIOSEO_DIR', __DIR__ );
}
if ( ! defined( 'AIOSEO_FILE' ) ) {
	define( 'AIOSEO_FILE', __FILE__ );
}

// Don't allow multiple versions to be active.
if ( function_exists( 'aioseo' ) ) {
	add_action( 'activate_all-in-one-seo-pack/all_in_one_seo_pack.php', 'aioseo_lite_just_activated' );
	add_action( 'deactivate_all-in-one-seo-pack/all_in_one_seo_pack.php', 'aioseo_lite_just_deactivated' );
	add_action( 'activate_all-in-one-seo-pack-pro/all_in_one_seo_pack.php', 'aioseo_pro_just_activated' );
	add_action( 'admin_notices', 'aioseo_lite_notice' );

	// Do not process the plugin code further.
	return;
}

// We will be deprecating these versions of PHP in the future, so let's let the user know.
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	add_action( 'admin_notices', 'aioseo_php_notice_deprecated' );
}

// Define the class and the function.
// The AIOSEOAbstract class is required here because it can't be autoloaded.
require_once dirname( __FILE__ ) . '/app/AIOSEOAbstract.php';
require_once dirname( __FILE__ ) . '/app/AIOSEO.php';

add_action( 'init', 'aioseo', 1 );