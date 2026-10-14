<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei;

use Merkushin\Wpal\Service\Assets;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\Service\Plugins;
use Merkushin\Wpal\ServiceFactory;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin into WordPress: the chart on Sensei's Students report and its assets.
 *
 * WordPress functions are called through WPAL services, so tests can replace them with
 * mocks: see tests/unit/PluginTest.php.
 */
class Plugin {
	/**
	 * Keep in sync with the plugin header, readme.txt and package.json: `make version-check`.
	 */
	public const VERSION = '1.0.0';

	public const TEXT_DOMAIN = 'reports-graph-for-sensei-lms';

	/**
	 * Main plugin file path.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * @var ChartPanel
	 */
	private $chart_panel;

	/**
	 * @var Hooks
	 */
	private $hooks;

	/**
	 * @var Assets
	 */
	private $assets;

	/**
	 * @var Plugins
	 */
	private $plugins;

	/**
	 * @param string          $plugin_file Main plugin file path.
	 * @param ChartPanel|null $chart_panel The chart; created when not given.
	 */
	public function __construct( string $plugin_file, ?ChartPanel $chart_panel = null ) {
		$this->plugin_file = $plugin_file;
		$this->chart_panel = $chart_panel ?? new ChartPanel();
		$this->hooks       = ServiceFactory::create_hooks();
		$this->assets      = ServiceFactory::create_assets();
		$this->plugins     = ServiceFactory::create_plugins();
	}

	public function init(): void {
		$this->hooks->add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		$this->hooks->add_action( ChartPanel::HOOK, [ $this->chart_panel, 'render' ] );
	}

	/**
	 * Loads assets/build/admin.{js,css}, built from assets/src/admin, where the chart is shown.
	 */
	public function enqueue_admin_assets(): void {
		if ( ! $this->chart_panel->is_visible() ) {
			return;
		}

		$this->enqueue_entry( 'admin' );
	}

	/**
	 * Enqueues the script and stylesheet `npm run build` produced for an entry point.
	 *
	 * Dependencies and the version come from the generated <entry>.asset.php: importing
	 * `@wordpress/i18n` adds `wp-i18n`, and the version changes whenever the file does,
	 * so browsers never keep a stale copy.
	 *
	 * @param string $entry Entry point name in webpack.config.js.
	 */
	private function enqueue_entry( string $entry ): void {
		$build_dir = dirname( $this->plugin_file ) . '/assets/build/';
		$asset     = $build_dir . $entry . '.asset.php';
		if ( ! is_file( $asset ) ) {
			return;
		}

		/** @var array{dependencies: string[], version: string} $meta */
		$meta   = require $asset;
		$handle = self::TEXT_DOMAIN . '-' . $entry;
		$url    = $this->plugins->plugin_dir_url( $this->plugin_file ) . 'assets/build/';

		if ( is_file( $build_dir . $entry . '.css' ) ) {
			$this->assets->wp_enqueue_style( $handle, $url . $entry . '.css', [], $meta['version'] );
			// The build also writes <entry>-rtl.css, which WordPress loads for right-to-left languages.
			$this->assets->wp_style_add_data( $handle, 'rtl', 'replace' );
		}

		$this->assets->wp_enqueue_script(
			$handle,
			$url . $entry . '.js',
			$meta['dependencies'],
			$meta['version'],
			[ 'in_footer' => true ]
		);

		if ( in_array( 'wp-i18n', $meta['dependencies'], true ) ) {
			$this->assets->wp_set_script_translations( $handle, self::TEXT_DOMAIN );
		}
	}
}
