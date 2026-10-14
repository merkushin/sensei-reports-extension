<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei\Tests\Completions;

use DateTimeImmutable;
use DateTimeZone;
use Merkushin\ReportsGraphForSensei\Completions\CommentsCompletionsQuery;
use Merkushin\ReportsGraphForSensei\Completions\CompletionsQuery;
use Merkushin\ReportsGraphForSensei\Completions\TablesCompletionsQuery;
use Merkushin\ReportsGraphForSensei\DateRange;
use Merkushin\ReportsGraphForSensei\Tests\FakeWpdb;
use PHPUnit\Framework\TestCase;

class CompletionsQueryTest extends TestCase {
	/**
	 * 15-minute bucket of a UTC time, as the database returns it.
	 */
	private static function bucket( string $utc ): int {
		return intdiv( ( new DateTimeImmutable( $utc, new DateTimeZone( 'UTC' ) ) )->getTimestamp(), 15 * 60 );
	}

	private static function range( string $start, string $end, string $timezone = 'UTC' ): DateRange {
		return DateRange::from_filters( $start, $end, new DateTimeImmutable( 'now', new DateTimeZone( $timezone ) ) );
	}

	/**
	 * @return array<string, array{class-string<CompletionsQuery>}>
	 */
	public function providerQueries(): array {
		return [
			'comments' => [ CommentsCompletionsQuery::class ],
			'tables'   => [ TablesCompletionsQuery::class ],
		];
	}

	/**
	 * @dataProvider providerQueries
	 *
	 * @param class-string<CompletionsQuery> $query_class Query class.
	 */
	public function testCount_NoCompletions_ReturnsZeroForEachDay( string $query_class ): void {
		$query = new $query_class( new FakeWpdb() );

		$counts = $query->count( self::range( '2026-10-01', '2026-10-03' ) );

		self::assertSame(
			[
				'2026-10-01' => 0,
				'2026-10-02' => 0,
				'2026-10-03' => 0,
			],
			$counts
		);
	}

	/**
	 * @dataProvider providerQueries
	 *
	 * @param class-string<CompletionsQuery> $query_class Query class.
	 */
	public function testCount_Completions_AddsBucketsUpByLocalDay( string $query_class ): void {
		$wpdb  = new FakeWpdb(
			[
				// 23:30 on Oct 1 in UTC is Oct 2 in Berlin (UTC+2).
				[
					'bucket'      => (string) self::bucket( '2026-10-01 21:45:00' ),
					'completions' => '2',
				],
				[
					'bucket'      => (string) self::bucket( '2026-10-01 22:00:00' ),
					'completions' => '3',
				],
				[
					'bucket'      => (string) self::bucket( '2026-10-02 12:15:00' ),
					'completions' => '1',
				],
			]
		);
		$query = new $query_class( $wpdb );

		$counts = $query->count( self::range( '2026-10-01', '2026-10-02', 'Europe/Berlin' ) );

		self::assertSame(
			[
				'2026-10-01' => 2,
				'2026-10-02' => 4,
			],
			$counts
		);
	}

	/**
	 * @dataProvider providerQueries
	 *
	 * @param class-string<CompletionsQuery> $query_class Query class.
	 */
	public function testCount_ByMonth_AddsBucketsUpByLocalMonth( string $query_class ): void {
		$wpdb  = new FakeWpdb(
			[
				[
					'bucket'      => self::bucket( '2026-01-10 10:00:00' ),
					'completions' => 4,
				],
				[
					'bucket'      => self::bucket( '2026-01-31 23:00:00' ),
					'completions' => 1,
				],
				[
					'bucket'      => self::bucket( '2026-03-01 00:00:00' ),
					'completions' => 2,
				],
			]
		);
		$query = new $query_class( $wpdb );

		$counts = $query->count( self::range( '2026-01-01', '2026-04-30' ) );

		self::assertSame(
			[
				'2026-01' => 5,
				'2026-02' => 0,
				'2026-03' => 2,
				'2026-04' => 0,
			],
			$counts
		);
	}

	public function testCount_Comments_CountsCompletedCourseStatusesInRange(): void {
		$wpdb = new FakeWpdb();

		( new CommentsCompletionsQuery( $wpdb ) )->count( self::range( '2026-10-01', '2026-10-02', 'Europe/Berlin' ) );

		self::assertSame( [ 'wp_comments', 'wp_posts', '2026-09-30 22:00:00', '2026-10-02 22:00:00', 0, 0 ], $wpdb->args );
		self::assertStringContainsString( "c.comment_type = 'sensei_course_status'", $wpdb->query );
		self::assertStringContainsString( "c.comment_approved = 'complete'", $wpdb->query );
		self::assertMatchesRegularExpression( '/c.comment_date_gmt >= %s\s+AND c.comment_date_gmt < %s/', $wpdb->query );
	}

	public function testCount_Tables_CountsCompletedCourseProgressInRange(): void {
		$wpdb         = new FakeWpdb();
		$wpdb->prefix = 'site2_';

		( new TablesCompletionsQuery( $wpdb ) )->count( self::range( '2026-10-01', '2026-10-02', 'Europe/Berlin' ) );

		self::assertSame( [ 'site2_sensei_lms_progress', 'wp_posts', '2026-09-30 22:00:00', '2026-10-02 22:00:00', 0, 0 ], $wpdb->args );
		self::assertStringContainsString( "pr.type = 'course'", $wpdb->query );
		self::assertStringContainsString( "pr.status = 'complete'", $wpdb->query );
		self::assertMatchesRegularExpression( '/pr.completed_at >= %s\s+AND pr.completed_at < %s/', $wpdb->query );
	}

	/**
	 * @dataProvider providerQueries
	 *
	 * @param class-string<CompletionsQuery> $query_class Query class.
	 */
	public function testCount_ByAuthor_FiltersByCourseAuthor( string $query_class ): void {
		$wpdb  = new FakeWpdb();
		$query = new $query_class( $wpdb );

		$query->count( self::range( '2026-10-01', '2026-10-01' ), 42 );

		self::assertSame( [ 42, 42 ], array_slice( $wpdb->args, -2 ) );
		self::assertStringContainsString( '( 0 = %d OR p.post_author = %d )', $wpdb->query );
	}

	/**
	 * @dataProvider providerQueries
	 *
	 * @param class-string<CompletionsQuery> $query_class Query class.
	 */
	public function testCount_Always_PreparesEveryValue( string $query_class ): void {
		$wpdb  = new FakeWpdb();
		$query = new $query_class( $wpdb );

		$query->count( self::range( '2026-10-01', '2026-10-01' ), 42 );

		self::assertSame( count( $wpdb->args ), preg_match_all( '/%[ids]/', $wpdb->query ) );
	}
}
