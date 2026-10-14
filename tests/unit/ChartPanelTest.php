<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Merkushin\ReportsGraphForSensei\ChartPanel;
use Merkushin\ReportsGraphForSensei\Completions\CompletionsQuery;
use Merkushin\ReportsGraphForSensei\DateRange;
use Merkushin\Wpal\Service\Capabilities;
use Merkushin\Wpal\Service\Dates;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\Service\Json;
use Merkushin\Wpal\Service\Localization;
use Merkushin\Wpal\Service\Options;
use Merkushin\Wpal\Service\Users;
use Merkushin\Wpal\ServiceFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChartPanelTest extends TestCase {
	/**
	 * @var CompletionsQuery&MockObject
	 */
	private $query;

	/**
	 * Capabilities the current user has.
	 *
	 * @var string[]
	 */
	private $capabilities = [ 'manage_sensei_grades', 'manage_sensei' ];

	protected function setUp(): void {
		$_GET = [
			'page'       => 'sensei_reports',
			'start_date' => '2026-10-01',
			'end_date'   => '2026-10-02',
		];

		$this->query = $this->createMock( CompletionsQuery::class );

		$hooks = $this->createMock( Hooks::class );
		$hooks->method( 'apply_filters' )->willReturnArgument( 1 );
		ServiceFactory::set_custom_hooks( $hooks );

		$capabilities = $this->createMock( Capabilities::class );
		$capabilities->method( 'current_user_can' )->willReturnCallback(
			function ( string $capability ): bool {
				return in_array( $capability, $this->capabilities, true );
			}
		);
		ServiceFactory::set_custom_capabilities( $capabilities );

		$users = $this->createMock( Users::class );
		$users->method( 'get_current_user_id' )->willReturn( 7 );
		ServiceFactory::set_custom_users( $users );

		$dates = $this->createMock( Dates::class );
		$dates->method( 'wp_timezone' )->willReturn( new DateTimeZone( 'Europe/Berlin' ) );
		$dates->method( 'current_datetime' )->willReturn( new DateTimeImmutable( '2026-10-14 12:00:00', new DateTimeZone( 'Europe/Berlin' ) ) );
		$dates->method( 'wp_date' )->willReturnCallback(
			function ( string $format, int $timestamp, DateTimeZone $timezone ): string {
				return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone )->format( $format );
			}
		);
		ServiceFactory::set_custom_dates( $dates );

		$options = $this->createMock( Options::class );
		$options->method( 'get_option' )->with( 'date_format' )->willReturn( 'j F Y' );
		ServiceFactory::set_custom_options( $options );

		$localization = $this->createMock( Localization::class );
		$localization->method( 'number_format_i18n' )->willReturnCallback(
			function ( $number ): string {
				return number_format( (float) $number );
			}
		);
		ServiceFactory::set_custom_localization( $localization );

		$json = $this->createMock( Json::class );
		$json->method( 'wp_json_encode' )->willReturnCallback( 'json_encode' );
		ServiceFactory::set_custom_json( $json );
	}

	protected function tearDown(): void {
		$_GET = [];
	}

	private function render(): string {
		ob_start();
		( new ChartPanel( $this->query ) )->render();

		return (string) ob_get_clean();
	}

	public function testIsVisible_StudentsReportForReportViewer_ReturnsTrue(): void {
		self::assertTrue( ( new ChartPanel( $this->query ) )->is_visible() );
	}

	public function testIsVisible_OtherReport_ReturnsFalse(): void {
		$_GET['view'] = 'courses';

		self::assertFalse( ( new ChartPanel( $this->query ) )->is_visible() );
	}

	public function testIsVisible_UserCannotSeeReports_ReturnsFalse(): void {
		$this->capabilities = [];

		self::assertFalse( ( new ChartPanel( $this->query ) )->is_visible() );
	}

	public function testRender_NotVisible_OutputsNothingAndQueriesNothing(): void {
		$this->capabilities = [];
		$this->query->expects( self::never() )->method( 'count' );

		self::assertSame( '', $this->render() );
	}

	public function testRender_Admin_CountsAllCoursesInReportRange(): void {
		$this->query->expects( self::once() )
			->method( 'count' )
			->with(
				self::callback(
					function ( DateRange $range ): bool {
						return '2026-10-01' === $range->get_start_date()
							&& '2026-10-02' === $range->get_end_date()
							&& 'Europe/Berlin' === $range->get_timezone()->getName();
					}
				),
				0
			)
			->willReturn( [] );

		$this->render();
	}

	public function testRender_Teacher_CountsOwnCourses(): void {
		$this->capabilities = [ 'manage_sensei_grades' ];
		$this->query->expects( self::once() )->method( 'count' )->with( self::anything(), 7 )->willReturn( [] );

		$this->render();
	}

	public function testRender_ViewerTimezone_UsesIt(): void {
		$_GET['timezone'] = 'America/New_York';
		$this->query->expects( self::once() )
			->method( 'count' )
			->with(
				self::callback(
					function ( DateRange $range ): bool {
						return 'America/New_York' === $range->get_timezone()->getName();
					}
				)
			)
			->willReturn( [] );

		$this->render();
	}

	public function testRender_Completions_OutputsSummaryAndChartData(): void {
		$this->query->method( 'count' )->willReturn(
			[
				'2026-10-01' => 1,
				'2026-10-02' => 1,
			]
		);

		$html = $this->render();

		self::assertStringContainsString( '<h2 class="reports-graph-for-sensei-lms__title">Course completions</h2>', $html );
		self::assertStringContainsString( '2 courses completed from 1 October 2026 to 2 October 2026.', $html );
		self::assertStringContainsString( 'aria-label="2 courses completed from 1 October 2026 to 2 October 2026."', $html );

		self::assertSame( 1, preg_match( '/data-chart="([^"]*)"/', $html, $matches ) );
		$data = json_decode( html_entity_decode( $matches[1], ENT_QUOTES ), true );
		self::assertSame( [ 'Oct 1', 'Oct 2' ], $data['labels'] );
		self::assertSame( [ 1, 1 ], $data['datasets'][0]['data'] );
	}

	public function testRender_OneCompletion_UsesSingular(): void {
		$this->query->method( 'count' )->willReturn( [ '2026-10-01' => 1 ] );

		self::assertStringContainsString( '1 course completed from', $this->render() );
	}

	public function testRender_NoCompletions_SaysSoWithoutChart(): void {
		$this->query->method( 'count' )->willReturn(
			[
				'2026-10-01' => 0,
				'2026-10-02' => 0,
			]
		);

		$html = $this->render();

		self::assertStringContainsString( 'No courses were completed from 1 October 2026 to 2 October 2026.', $html );
		self::assertStringNotContainsString( '<canvas', $html );
	}

	public function testRender_DatasetLabelWithMarkup_EscapesChartData(): void {
		$this->query->method( 'count' )->willReturn( [ '2026-10-01' => 1 ] );
		$hooks = $this->createMock( Hooks::class );
		$hooks->method( 'apply_filters' )->willReturn( [ [ 'label' => '"><script>alert(1)</script>' ] ] );
		ServiceFactory::set_custom_hooks( $hooks );

		$html = $this->render();

		self::assertStringNotContainsString( '<script>', $html );
	}
}
