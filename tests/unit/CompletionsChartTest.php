<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Merkushin\ReportsGraphForSensei\Completions\CompletionsQuery;
use Merkushin\ReportsGraphForSensei\CompletionsChart;
use Merkushin\ReportsGraphForSensei\DateRange;
use Merkushin\Wpal\Service\Dates;
use Merkushin\Wpal\Service\Hooks;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompletionsChartTest extends TestCase {
	/**
	 * @var CompletionsQuery&MockObject
	 */
	private $query;

	/**
	 * @var Hooks&MockObject
	 */
	private $hooks;

	/**
	 * @var Dates&MockObject
	 */
	private $dates;

	protected function setUp(): void {
		$this->query = $this->createMock( CompletionsQuery::class );
		$this->hooks = $this->createMock( Hooks::class );
		$this->hooks->method( 'apply_filters' )->willReturnArgument( 1 );
		$this->dates = $this->createMock( Dates::class );
		$this->dates->method( 'wp_date' )->willReturnCallback(
			function ( string $format, int $timestamp, DateTimeZone $timezone ): string {
				return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone )->format( $format );
			}
		);
	}

	private function chart(): CompletionsChart {
		return new CompletionsChart( $this->query, $this->hooks, $this->dates );
	}

	private static function range( string $start, string $end ): DateRange {
		return DateRange::from_filters( $start, $end, new DateTimeImmutable( 'now', new DateTimeZone( 'Asia/Tokyo' ) ) );
	}

	public function testGetData_ByDay_LabelsDaysAndCountsCompletions(): void {
		$range = self::range( '2026-10-01', '2026-10-02' );
		$this->query->method( 'count' )->with( $range, 0 )->willReturn(
			[
				'2026-10-01' => 3,
				'2026-10-02' => 0,
			]
		);

		$data = $this->chart()->get_data( $range, 0 );

		self::assertSame(
			[
				'labels'   => [ 'Oct 1', 'Oct 2' ],
				'datasets' => [
					[
						'label' => 'Completed courses',
						'data'  => [ 3, 0 ],
					],
				],
				'total'    => 3,
			],
			$data
		);
	}

	public function testGetData_ByMonth_LabelsMonths(): void {
		$range = self::range( '2026-01-01', '2026-04-30' );
		$this->query->method( 'count' )->willReturn(
			[
				'2026-01' => 1,
				'2026-02' => 2,
				'2026-03' => 3,
				'2026-04' => 4,
			]
		);

		$data = $this->chart()->get_data( $range, 0 );

		self::assertSame( [ 'Jan 2026', 'Feb 2026', 'Mar 2026', 'Apr 2026' ], $data['labels'] );
		self::assertSame( 10, $data['total'] );
	}

	public function testGetData_Author_CountsTheirCourses(): void {
		$range = self::range( '2026-10-01', '2026-10-01' );
		$this->query->expects( self::once() )->method( 'count' )->with( $range, 7 )->willReturn( [ '2026-10-01' => 1 ] );

		$this->chart()->get_data( $range, 7 );
	}

	public function testGetData_Always_LetsPluginsFilterDatasets(): void {
		$range = self::range( '2026-10-01', '2026-10-02' );
		$this->query->method( 'count' )->willReturn(
			[
				'2026-10-01' => 1,
				'2026-10-02' => 2,
			]
		);
		$hooks = $this->createMock( Hooks::class );
		$hooks->expects( self::once() )
			->method( 'apply_filters' )
			->with(
				CompletionsChart::DATASETS_FILTER,
				[
					[
						'label' => 'Completed courses',
						'data'  => [ 1, 2 ],
					],
				],
				[ '2026-10-01', '2026-10-02' ],
				$range
			)
			->willReturnCallback(
				function ( string $hook, array $datasets ): array {
					$datasets[] = [
						'label' => 'Enrolments',
						'data'  => [ 5, 6 ],
					];
					return $datasets;
				}
			);

		$data = ( new CompletionsChart( $this->query, $hooks, $this->dates ) )->get_data( $range, 0 );

		self::assertSame( [ 'Completed courses', 'Enrolments' ], array_column( $data['datasets'], 'label' ) );
		self::assertSame( 3, $data['total'] );
	}

	public function testGetData_FilterReturnsKeyedArray_ReturnsList(): void {
		$range = self::range( '2026-10-01', '2026-10-01' );
		$this->query->method( 'count' )->willReturn( [ '2026-10-01' => 1 ] );
		$hooks = $this->createMock( Hooks::class );
		$hooks->method( 'apply_filters' )->willReturn( [ 'mine' => [ 'data' => [ 1 ] ] ] );

		$data = ( new CompletionsChart( $this->query, $hooks, $this->dates ) )->get_data( $range, 0 );

		self::assertSame( [ [ 'data' => [ 1 ] ] ], $data['datasets'] );
	}
}
