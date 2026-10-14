<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei\Tests;

use Merkushin\ReportsGraphForSensei\ChartPanel;
use Merkushin\ReportsGraphForSensei\Plugin;
use Merkushin\Wpal\Service\Assets;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\Service\Plugins;
use Merkushin\Wpal\ServiceFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Each WordPress function the plugin calls goes through a WPAL service; the tests swap the
 * services for mocks with ServiceFactory::set_custom_*() and check the calls.
 */
class PluginTest extends TestCase {
	private const PLUGIN_URL = 'https://example.com/wp-content/plugins/reports-graph-for-sensei-lms/';

	/**
	 * A plugin directory with a build of assets: admin has a stylesheet and uses wp-i18n.
	 */
	private const PLUGIN_FILE = __DIR__ . '/../fixtures/plugin/reports-graph-for-sensei-lms.php';

	/**
	 * @var Hooks&MockObject
	 */
	private $hooks;

	/**
	 * @var Assets&MockObject
	 */
	private $assets;

	/**
	 * @var ChartPanel&MockObject
	 */
	private $chart_panel;

	protected function setUp(): void {
		$this->hooks  = $this->createMock( Hooks::class );
		$this->assets = $this->createMock( Assets::class );
		$plugins      = $this->createMock( Plugins::class );
		$plugins->method( 'plugin_dir_url' )->willReturn( self::PLUGIN_URL );

		ServiceFactory::set_custom_hooks( $this->hooks );
		ServiceFactory::set_custom_assets( $this->assets );
		ServiceFactory::set_custom_plugins( $plugins );

		$this->chart_panel = $this->createMock( ChartPanel::class );
		$this->chart_panel->method( 'is_visible' )->willReturn( true );
	}

	private function plugin( string $file = self::PLUGIN_FILE ): Plugin {
		return new Plugin( $file, $this->chart_panel );
	}

	public function testInit_Always_HooksChartAndAssets(): void {
		$plugin = $this->plugin();
		$added  = [];
		$this->hooks->method( 'add_action' )->willReturnCallback(
			function ( string $hook, callable $callback ) use ( &$added ): bool {
				$added[ $hook ] = $callback;
				return true;
			}
		);

		$plugin->init();

		self::assertEquals(
			[
				'admin_enqueue_scripts'         => [ $plugin, 'enqueue_admin_assets' ],
				'sensei_analysis_after_headers' => [ $this->chart_panel, 'render' ],
			],
			$added
		);
	}

	public function testEnqueueAdminAssets_ChartHidden_EnqueuesNothing(): void {
		$chart_panel = $this->createMock( ChartPanel::class );
		$chart_panel->method( 'is_visible' )->willReturn( false );
		$this->assets->expects( self::never() )->method( 'wp_enqueue_style' );
		$this->assets->expects( self::never() )->method( 'wp_enqueue_script' );

		( new Plugin( self::PLUGIN_FILE, $chart_panel ) )->enqueue_admin_assets();
	}

	public function testEnqueueAdminAssets_WithStylesheet_EnqueuesStyleWithBuildVersion(): void {
		$this->assets->expects( self::once() )
			->method( 'wp_enqueue_style' )
			->with( 'reports-graph-for-sensei-lms-admin', self::PLUGIN_URL . 'assets/build/admin.css', [], 'admin-hash' );

		$this->plugin()->enqueue_admin_assets();
	}

	public function testEnqueueAdminAssets_WithStylesheet_UsesRtlStylesheetForRtlLanguages(): void {
		$this->assets->expects( self::once() )
			->method( 'wp_style_add_data' )
			->with( 'reports-graph-for-sensei-lms-admin', 'rtl', 'replace' );

		$this->plugin()->enqueue_admin_assets();
	}

	public function testEnqueueAdminAssets_Always_EnqueuesScriptWithBuildDependencies(): void {
		$this->assets->expects( self::once() )
			->method( 'wp_enqueue_script' )
			->with(
				'reports-graph-for-sensei-lms-admin',
				self::PLUGIN_URL . 'assets/build/admin.js',
				[ 'wp-i18n' ],
				'admin-hash',
				[ 'in_footer' => true ]
			);

		$this->plugin()->enqueue_admin_assets();
	}

	public function testEnqueueAdminAssets_ScriptUsesI18n_LoadsScriptTranslations(): void {
		$this->assets->expects( self::once() )
			->method( 'wp_set_script_translations' )
			->with( 'reports-graph-for-sensei-lms-admin', 'reports-graph-for-sensei-lms' );

		$this->plugin()->enqueue_admin_assets();
	}

	public function testEnqueueAdminAssets_NotBuilt_EnqueuesNothing(): void {
		$this->assets->expects( self::never() )->method( 'wp_enqueue_style' );
		$this->assets->expects( self::never() )->method( 'wp_enqueue_script' );

		$this->plugin( __DIR__ . '/reports-graph-for-sensei-lms.php' )->enqueue_admin_assets();
	}
}
