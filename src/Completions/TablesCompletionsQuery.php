<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei\Completions;

defined( 'ABSPATH' ) || exit;

/**
 * Counts completions in Sensei's High-Performance Progress Storage: the
 * `sensei_lms_progress` table, where `completed_at` is in UTC.
 */
class TablesCompletionsQuery extends CompletionsQuery {
	protected function count_by_bucket( string $start_utc, string $end_utc, int $author_id ): array {
		$wpdb = $this->wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- An aggregate no WordPress API provides, read once per report view.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT FLOOR( TIMESTAMPDIFF( MINUTE, '1970-01-01 00:00:00', pr.completed_at ) / 15 ) AS bucket, COUNT(*) AS completions
				FROM %i pr
				INNER JOIN %i p ON p.ID = pr.post_id
				WHERE pr.type = 'course'
					AND pr.status = 'complete'
					AND pr.completed_at >= %s
					AND pr.completed_at < %s
					AND ( 0 = %d OR p.post_author = %d )
				GROUP BY bucket",
				$wpdb->prefix . 'sensei_lms_progress',
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
