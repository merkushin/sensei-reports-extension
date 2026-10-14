<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei;

use DateTimeZone;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * The Sensei Reports page request: which report is open and its filters.
 *
 * Reads the same query arguments Sensei does, and accepts only exact values, Y-m-d dates and
 * valid timezone identifiers, so nothing else from the request reaches the plugin.
 */
class ReportRequest {
	/**
	 * Sensei's Reports page, admin.php?page=sensei_reports.
	 */
	public const PAGE = 'sensei_reports';

	/**
	 * Query arguments that open a report on one course, lesson or student.
	 */
	private const DETAIL_ARGS = [ 'course_id', 'lesson_id', 'user_id' ];

	/**
	 * @var array<mixed>
	 */
	private $query;

	/**
	 * @param array<mixed> $query Query arguments, usually $_GET.
	 */
	public function __construct( array $query ) {
		$this->query = $query;
	}

	/**
	 * Whether this is the Students report with all students (Sensei's default report).
	 */
	public function is_students_report(): bool {
		if ( self::PAGE !== $this->get( 'page' ) ) {
			return false;
		}
		foreach ( self::DETAIL_ARGS as $arg ) {
			if ( (int) $this->get( $arg ) > 0 ) {
				return false;
			}
		}
		$view = $this->get( 'view' );

		return '' === $view || 'students' === $view;
	}

	/**
	 * The report's "From" date, Y-m-d, or an empty string. DateRange validates it.
	 */
	public function get_start_date(): string {
		return $this->get( 'start_date' );
	}

	/**
	 * The report's "To" date, Y-m-d, or an empty string. DateRange validates it.
	 */
	public function get_end_date(): string {
		return $this->get( 'end_date' );
	}

	/**
	 * The viewer's timezone, which Sensei's report filter sends from the browser, or $fallback.
	 *
	 * @param DateTimeZone $fallback The site's timezone.
	 */
	public function get_timezone( DateTimeZone $fallback ): DateTimeZone {
		$timezone = $this->get( 'timezone' );
		if ( '' === $timezone ) {
			return $fallback;
		}

		try {
			return new DateTimeZone( $timezone );
		} catch ( Exception $e ) {
			return $fallback;
		}
	}

	private function get( string $arg ): string {
		$value = $this->query[ $arg ] ?? '';

		return is_string( $value ) ? $value : '';
	}
}
