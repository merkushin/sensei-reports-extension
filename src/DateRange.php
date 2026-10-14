<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

defined( 'ABSPATH' ) || exit;

/**
 * The days the chart covers, in the timezone the report is viewed in.
 *
 * Short ranges are charted by day, long ones by month, so the chart stays readable.
 */
class DateRange {
	public const GRANULARITY_DAY = 'day';

	public const GRANULARITY_MONTH = 'month';

	/**
	 * Days shown when the report has no start date.
	 */
	public const DEFAULT_DAYS = 30;

	/**
	 * Longest range charted by day; longer ones are charted by month.
	 */
	public const MAX_DAYS_BY_DAY = 92;

	/**
	 * Midnight of the first day.
	 *
	 * @var DateTimeImmutable
	 */
	private $start;

	/**
	 * Midnight of the day after the last day.
	 *
	 * @var DateTimeImmutable
	 */
	private $end;

	private function __construct( DateTimeImmutable $start, DateTimeImmutable $end ) {
		$this->start = $start;
		$this->end   = $end;
	}

	/**
	 * Builds the range from the report's date filters.
	 *
	 * An empty or invalid end date means today, an empty or invalid start date means
	 * DEFAULT_DAYS days up to the end date. Dates given in the wrong order are swapped.
	 *
	 * @param string            $start_date First day, Y-m-d.
	 * @param string            $end_date   Last day, Y-m-d.
	 * @param DateTimeImmutable $now        Current time in the report's timezone.
	 */
	public static function from_filters( string $start_date, string $end_date, DateTimeImmutable $now ): self {
		$timezone = $now->getTimezone();
		$end      = self::parse_date( $end_date, $timezone ) ?? $now->setTime( 0, 0 );
		$start    = self::parse_date( $start_date, $timezone ) ?? $end->sub( new DateInterval( 'P' . ( self::DEFAULT_DAYS - 1 ) . 'D' ) );

		if ( $start > $end ) {
			list( $start, $end ) = [ $end, $start ];
		}

		return new self( $start, $end->add( new DateInterval( 'P1D' ) ) );
	}

	/**
	 * Returns a midnight in the timezone for a valid Y-m-d date, null otherwise.
	 */
	private static function parse_date( string $date, DateTimeZone $timezone ): ?DateTimeImmutable {
		$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $timezone );

		return $parsed && $parsed->format( 'Y-m-d' ) === $date ? $parsed : null;
	}

	public function get_timezone(): DateTimeZone {
		return $this->start->getTimezone();
	}

	public function get_granularity(): string {
		return $this->get_days() > self::MAX_DAYS_BY_DAY ? self::GRANULARITY_MONTH : self::GRANULARITY_DAY;
	}

	/**
	 * Number of days in the range, counting both the first and the last day.
	 */
	public function get_days(): int {
		// Calendar days, not 24-hour periods: a range over a DST change still counts whole days.
		$start = new DateTimeImmutable( $this->start->format( 'Y-m-d' ), new DateTimeZone( 'UTC' ) );
		$end   = new DateTimeImmutable( $this->end->format( 'Y-m-d' ), new DateTimeZone( 'UTC' ) );

		return (int) $start->diff( $end )->days;
	}

	/**
	 * The first day, Y-m-d.
	 */
	public function get_start_date(): string {
		return $this->start->format( 'Y-m-d' );
	}

	/**
	 * The last day, Y-m-d.
	 */
	public function get_end_date(): string {
		return $this->end->sub( new DateInterval( 'P1D' ) )->format( 'Y-m-d' );
	}

	/**
	 * Start of the range in UTC, as MySQL DATETIME. Inclusive.
	 */
	public function get_start_utc(): string {
		return $this->start->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	}

	/**
	 * End of the range in UTC, as MySQL DATETIME. Exclusive.
	 */
	public function get_end_utc(): string {
		return $this->end->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	}

	/**
	 * The chart's periods, oldest first: days (Y-m-d) or months (Y-m).
	 *
	 * @return array<string, DateTimeImmutable> Start of each period, keyed by period.
	 */
	public function get_periods(): array {
		$step    = new DateInterval( self::GRANULARITY_DAY === $this->get_granularity() ? 'P1D' : 'P1M' );
		$periods = [];
		$period  = self::GRANULARITY_DAY === $this->get_granularity() ? $this->start : $this->start->modify( 'first day of this month' );
		while ( $period < $this->end ) {
			$periods[ $this->get_period( $period ) ] = $period;
			$period                                  = $period->add( $step );
		}

		return $periods;
	}

	/**
	 * The period a moment falls into: Y-m-d or Y-m, depending on the granularity.
	 */
	public function get_period( DateTimeImmutable $moment ): string {
		$local = $moment->setTimezone( $this->get_timezone() );

		return $local->format( self::GRANULARITY_DAY === $this->get_granularity() ? 'Y-m-d' : 'Y-m' );
	}
}
