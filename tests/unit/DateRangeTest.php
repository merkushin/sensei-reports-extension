<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Merkushin\ReportsGraphForSensei\DateRange;
use PHPUnit\Framework\TestCase;

class DateRangeTest extends TestCase {
	private function now( string $timezone = 'UTC' ): DateTimeImmutable {
		return new DateTimeImmutable( '2026-10-14 15:30:00', new DateTimeZone( $timezone ) );
	}

	public function testFromFilters_NoDates_CoversLastThirtyDaysUpToToday(): void {
		$range = DateRange::from_filters( '', '', $this->now() );

		self::assertSame( '2026-09-15', $range->get_start_date() );
		self::assertSame( '2026-10-14', $range->get_end_date() );
		self::assertSame( DateRange::DEFAULT_DAYS, $range->get_days() );
	}

	public function testFromFilters_OnlyStartDate_EndsToday(): void {
		$range = DateRange::from_filters( '2026-10-01', '', $this->now() );

		self::assertSame( '2026-10-01', $range->get_start_date() );
		self::assertSame( '2026-10-14', $range->get_end_date() );
	}

	public function testFromFilters_OnlyEndDate_CoversThirtyDaysUpToIt(): void {
		$range = DateRange::from_filters( '', '2026-03-31', $this->now() );

		self::assertSame( '2026-03-02', $range->get_start_date() );
		self::assertSame( '2026-03-31', $range->get_end_date() );
	}

	public function testFromFilters_DatesInWrongOrder_SwapsThem(): void {
		$range = DateRange::from_filters( '2026-10-10', '2026-10-01', $this->now() );

		self::assertSame( '2026-10-01', $range->get_start_date() );
		self::assertSame( '2026-10-10', $range->get_end_date() );
	}

	/**
	 * @dataProvider providerInvalidDates
	 */
	public function testFromFilters_InvalidDate_UsesDefault( string $date ): void {
		$range = DateRange::from_filters( $date, $date, $this->now() );

		self::assertSame( '2026-09-15', $range->get_start_date() );
		self::assertSame( '2026-10-14', $range->get_end_date() );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public function providerInvalidDates(): array {
		return [
			'not a date'       => [ 'yesterday' ],
			'no such day'      => [ '2026-02-30' ],
			'other format'     => [ '14/10/2026' ],
			'trailing content' => [ '2026-10-01 OR 1=1' ],
		];
	}

	public function testFromFilters_SameDay_CoversOneDay(): void {
		$range = DateRange::from_filters( '2026-10-01', '2026-10-01', $this->now() );

		self::assertSame( 1, $range->get_days() );
		self::assertSame( [ '2026-10-01' ], array_keys( $range->get_periods() ) );
	}

	public function testUtcBounds_Timezone_AreLocalMidnightsInUtc(): void {
		$range = DateRange::from_filters( '2026-10-01', '2026-10-02', $this->now( 'Europe/Berlin' ) );

		self::assertSame( '2026-09-30 22:00:00', $range->get_start_utc() );
		self::assertSame( '2026-10-02 22:00:00', $range->get_end_utc() );
	}

	public function testGetDays_OverDstChange_CountsCalendarDays(): void {
		$range = DateRange::from_filters( '2026-10-24', '2026-10-26', $this->now( 'Europe/Berlin' ) );

		self::assertSame( 3, $range->get_days() );
		self::assertSame( [ '2026-10-24', '2026-10-25', '2026-10-26' ], array_keys( $range->get_periods() ) );
	}

	public function testGetGranularity_UpToMaxDays_IsDay(): void {
		$range = DateRange::from_filters( '2026-01-01', '2026-04-02', $this->now() );

		self::assertSame( DateRange::MAX_DAYS_BY_DAY, $range->get_days() );
		self::assertSame( DateRange::GRANULARITY_DAY, $range->get_granularity() );
	}

	public function testGetPeriods_LongerThanMaxDays_ListsMonths(): void {
		$range = DateRange::from_filters( '2026-01-15', '2026-05-15', $this->now() );

		self::assertSame( DateRange::GRANULARITY_MONTH, $range->get_granularity() );
		self::assertSame( [ '2026-01', '2026-02', '2026-03', '2026-04', '2026-05' ], array_keys( $range->get_periods() ) );
	}

	public function testGetPeriods_ByMonthFromThirtyFirst_ListsEveryMonth(): void {
		$range = DateRange::from_filters( '2025-12-31', '2026-05-01', $this->now() );

		self::assertSame( [ '2025-12', '2026-01', '2026-02', '2026-03', '2026-04', '2026-05' ], array_keys( $range->get_periods() ) );
	}

	public function testGetPeriod_UtcMoment_IsTheLocalDay(): void {
		$range = DateRange::from_filters( '2026-10-01', '2026-10-02', $this->now( 'America/New_York' ) );

		$period = $range->get_period( new DateTimeImmutable( '2026-10-02 03:00:00', new DateTimeZone( 'UTC' ) ) );

		self::assertSame( '2026-10-01', $period );
	}
}
