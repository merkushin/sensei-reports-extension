<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei\Completions;

use DateTimeImmutable;
use Merkushin\ReportsGraphForSensei\DateRange;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Counts course completions per chart period.
 *
 * Sensei keeps progress either in comments or in its own tables (High-Performance Progress
 * Storage); a subclass queries one of them. Completion times are stored in UTC, and the chart
 * shows days in the viewer's timezone, so the database counts completions in 15-minute UTC
 * buckets (every timezone offset is a multiple of 15 minutes), and PHP adds the buckets up into
 * local days or months. That keeps the counts right across DST changes without relying on
 * MySQL's timezone tables.
 */
abstract class CompletionsQuery {
	/**
	 * Keep in sync with the `/ 15` in the subclasses' queries.
	 */
	protected const BUCKET_MINUTES = 15;

	/**
	 * @var wpdb
	 */
	protected $wpdb;

	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Counts completions in 15-minute UTC buckets.
	 *
	 * @param string $start_utc Start of the range, UTC DATETIME, inclusive.
	 * @param string $end_utc   End of the range, UTC DATETIME, exclusive.
	 * @param int    $author_id Count only courses by this user; 0 counts all courses.
	 * @return array<object> Rows with `bucket` (15-minute periods since the Unix epoch) and `completions`.
	 */
	abstract protected function count_by_bucket( string $start_utc, string $end_utc, int $author_id ): array;

	/**
	 * Counts the completions in each period of the range.
	 *
	 * @param DateRange $range     Days to count.
	 * @param int       $author_id Count only courses by this user; 0 counts all courses.
	 * @return array<string, int> Completions keyed by period (see DateRange::get_periods()), 0 included.
	 */
	public function count( DateRange $range, int $author_id = 0 ): array {
		$rows   = $this->count_by_bucket( $range->get_start_utc(), $range->get_end_utc(), $author_id );
		$counts = array_fill_keys( array_keys( $range->get_periods() ), 0 );
		foreach ( $rows as $row ) {
			// A '@timestamp' date is in UTC.
			$moment = new DateTimeImmutable( '@' . ( (int) $row->bucket * self::BUCKET_MINUTES * 60 ) );
			$period = $range->get_period( $moment );
			if ( isset( $counts[ $period ] ) ) {
				$counts[ $period ] += (int) $row->completions;
			}
		}

		return $counts;
	}
}
