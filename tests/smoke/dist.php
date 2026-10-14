<?php
/**
 * Smoke test for a release build: php tests/smoke/dist.php <build-dir>
 *
 * Loads the built plugin (scoped, with unused WPAL services pruned) with stand-ins for the
 * WordPress functions it calls, then runs its hooks. A WPAL service missing from the build
 * or a scoping problem fails here with a fatal error.
 *
 * When the plugin calls a new WordPress function, add a stand-in below, and run the new
 * code path from the bottom of this file.
 *
 * @package Merkushin\ReportsGraphForSensei
 */

declare( strict_types=1 );

$build_dir = rtrim( $argv[1] ?? '', '/' );
$main_file = $build_dir . '/reports-graph-for-sensei-lms.php';
if ( ! is_file( $main_file ) ) {
	fwrite( STDERR, "Usage: php tests/smoke/dist.php <build-dir>\n" );
	exit( 1 );
}
if ( is_dir( $build_dir . '/vendor' ) ) {
	fwrite( STDERR, "The build still has vendor/: run make dist.\n" );
	exit( 1 );
}

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['smoke_actions']  = [];
$GLOBALS['smoke_enqueued'] = [];

function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
	$GLOBALS['smoke_actions'][ $hook ][] = $callback;
	return true;
}

function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
	return add_action( $hook, $callback, $priority, $accepted_args );
}

/**
 * @param mixed ...$args Arguments for the callbacks.
 */
function do_action( string $hook, ...$args ): void {
	foreach ( $GLOBALS['smoke_actions'][ $hook ] ?? [] as $callback ) {
		$callback( ...$args );
	}
}

/**
 * @param mixed $value   Value to filter.
 * @param mixed ...$args Arguments for the callbacks.
 * @return mixed
 */
function apply_filters( string $hook, $value, ...$args ) {
	return $value;
}

function plugin_dir_url( string $file ): string {
	return 'https://example.com/wp-content/plugins/reports-graph-for-sensei-lms/';
}

function wp_enqueue_style( string $handle, string $src = '' ): void {
	$GLOBALS['smoke_enqueued'][] = $src;
}

/**
 * @param mixed $value Data value.
 */
function wp_style_add_data( string $handle, string $key, $value ): bool {
	return true;
}

function wp_enqueue_script( string $handle, string $src = '' ): void {
	$GLOBALS['smoke_enqueued'][] = $src;
}

function wp_set_script_translations( string $handle, string $domain = 'default', string $path = '' ): bool {
	return true;
}

/**
 * @param mixed ...$args Object ID and other arguments.
 */
function current_user_can( string $capability, ...$args ): bool {
	return true;
}

function get_current_user_id(): int {
	return 1;
}

function wp_timezone(): DateTimeZone {
	return new DateTimeZone( 'Europe/Berlin' );
}

function current_datetime(): DateTimeImmutable {
	return new DateTimeImmutable( 'now', wp_timezone() );
}

function wp_date( string $format, ?int $timestamp = null, ?DateTimeZone $timezone = null ): string {
	return ( new DateTimeImmutable( '@' . ( $timestamp ?? time() ) ) )->setTimezone( $timezone ?? wp_timezone() )->format( $format );
}

/**
 * @param mixed $default_value Default value.
 * @return mixed
 */
function get_option( string $option, $default_value = false ) {
	return 'date_format' === $option ? 'F j, Y' : $default_value;
}

/**
 * @param float|int $number Number.
 */
function number_format_i18n( $number, int $decimals = 0 ): string {
	return number_format( (float) $number, $decimals );
}

/**
 * @param mixed $value Value to encode.
 * @return string|false
 */
function wp_json_encode( $value, int $flags = 0, int $depth = 512 ) {
	return json_encode( $value, $flags, $depth );
}

function __( string $text, string $domain = 'default' ): string {
	return $text;
}

function _x( string $text, string $context, string $domain = 'default' ): string {
	return $text;
}

function _n( string $single, string $plural, int $number, string $domain = 'default' ): string {
	return 1 === $number ? $single : $plural;
}

function esc_html( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES );
}

function esc_attr( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES );
}

function esc_html_e( string $text, string $domain = 'default' ): void {
	echo esc_html( $text );
}

require __DIR__ . '/wpdb.php';

$GLOBALS['wpdb'] = new wpdb( 'user', 'password', 'database', 'localhost' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- There is no WordPress here.
$_GET            = [ 'page' => 'sensei_reports' ];

require $main_file;

do_action( 'init' );
do_action( 'admin_enqueue_scripts', 'sensei-lms_page_sensei_reports' );

ob_start();
do_action( 'sensei_analysis_after_headers' );
$chart = (string) ob_get_clean();

$failures = [];
foreach ( $GLOBALS['smoke_enqueued'] as $url ) {
	$file = $build_dir . '/' . substr( $url, strlen( plugin_dir_url( $main_file ) ) );
	if ( ! is_file( $file ) ) {
		$failures[] = "Enqueued $url, but the build has no $file.";
	}
}
if ( [] === $GLOBALS['smoke_enqueued'] ) {
	$failures[] = 'Nothing was enqueued.';
}
if ( false === strpos( $chart, '1 course completed' ) || false === strpos( $chart, 'data-chart=' ) ) {
	$failures[] = "The Students report has no chart:\n$chart";
}

if ( [] !== $failures ) {
	fwrite( STDERR, implode( "\n", $failures ) . "\n" );
	exit( 1 );
}

echo 'Smoke test passed: ' . count( $GLOBALS['smoke_enqueued'] ) . " assets enqueued, chart rendered.\n";
