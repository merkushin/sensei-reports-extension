<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei\Completions;

defined( 'ABSPATH' ) || exit;

/**
 * Counts completions in Sensei's default progress storage: one `sensei_course_status`
 * comment per student and course, approved as `complete` once the course is completed.
 * The comment date is the time of the last status change, which for a completed course
 * is its completion.
 */
class CommentsCompletionsQuery extends CompletionsQuery {
	protected function count_by_bucket( string $start_utc, string $end_utc, int $author_id ): array {
		$wpdb = $this->wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- An aggregate no WordPress API provides, read once per report view.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT FLOOR( TIMESTAMPDIFF( MINUTE, '1970-01-01 00:00:00', c.comment_date_gmt ) / 15 ) AS bucket, COUNT(*) AS completions
				FROM %i c
				INNER JOIN %i p ON p.ID = c.comment_post_ID
				WHERE c.comment_type = 'sensei_course_status'
					AND c.comment_approved = 'complete'
					AND c.comment_date_gmt >= %s
					AND c.comment_date_gmt < %s
					AND ( 0 = %d OR p.post_author = %d )
				GROUP BY bucket",
				$wpdb->comments,
				$wpdb->posts,
				$start_utc,
				$end_utc,
				$author_id,
				$author_id
			)
		);

		return (array) $rows;
	}
}
