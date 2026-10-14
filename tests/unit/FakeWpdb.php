<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei\Tests;

use wpdb;

/**
 * A $wpdb that records the prepared query and returns the given rows.
 */
class FakeWpdb extends wpdb {
	/**
	 * The query passed to prepare().
	 *
	 * @var string
	 */
	public $query = '';

	/**
	 * The values passed to prepare().
	 *
	 * @var array<mixed>
	 */
	public $args = [];

	/**
	 * Rows get_results() returns.
	 *
	 * @var array<object>
	 */
	public $rows = [];

	/**
	 * @param array<array{bucket: int|string, completions: int|string}> $rows Rows for get_results().
	 */
	public function __construct( array $rows = [] ) {
		$this->prefix   = 'wp_';
		$this->comments = 'wp_comments';
		$this->posts    = 'wp_posts';
		$this->rows     = array_map(
			function ( array $row ): object {
				return (object) $row;
			},
			$rows
		);
	}

	/**
	 * @param string $query   Query with placeholders.
	 * @param mixed  ...$args Values.
	 * @return string
	 */
	public function prepare( $query, ...$args ) {
		$this->query = $query;
		$this->args  = $args;

		return 'prepared';
	}

	/**
	 * @param string|null $query  Query.
	 * @param string      $output Output type.
	 * @return array<object>|null
	 */
	public function get_results( $query = null, $output = OBJECT ) {
		return 'prepared' === $query ? $this->rows : null;
	}
}
