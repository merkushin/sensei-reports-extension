<?php
/**
 * Plugin Name:       Reports Graph for Sensei LMS
 * Plugin URI:        https://github.com/merkushin/sensei-reports-extension
 * Description:       Adds a chart of course completions over time to the Sensei LMS Students report.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  sensei-lms
 * Author:            Dmitry Merkushin
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       reports-graph-for-sensei-lms
 *
 * @package Merkushin\ReportsGraphForSensei
 */

namespace Merkushin\ReportsGraphForSensei;

defined( 'ABSPATH' ) || exit;

// Release builds ship dependencies prefixed by wp-scoper in vendor-prefixed/;
// a development checkout uses the regular Composer autoloader.
if ( file_exists( __DIR__ . '/vendor-prefixed/autoload.php' ) ) {
	require_once __DIR__ . '/vendor-prefixed/autoload.php';
} else {
	require_once __DIR__ . '/vendor/autoload.php';
}

add_action( 'init', [ new Plugin( __FILE__ ), 'init' ] );
