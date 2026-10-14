<?php
/**
 * A stand-in for WordPress' wpdb class for the smoke test: one course completed now, whatever
 * the query. PHPStan reads WordPress' own definition instead.
 *
 * @package Merkushin\ReportsGraphForSensei
 */

declare( strict_types=1 );

/**
 * Stands in for $wpdb: one completion today, whatever the query.
 */
// phpcs:ignore PEAR.NamingConventions.ValidClassName.StartWithCapital -- WordPress' class name.
class wpdb {
	/**
	 * @var string
	 */
	public $prefix = 'wp_';

	/**
	 * @var string
	 */
	public $comments = 'wp_comments';

	/**
	 * @var string
	 */
	public $posts = 'wp_posts';

	/**
	 * Takes WordPress' connection arguments and connects nowhere.
	 */
	public function __construct( string $dbuser, string $dbpassword, string $dbname, string $dbhost ) {
	}

	/**
	 * @param mixed ...$args Values.
	 */
	public function prepare( string $query, ...$args ): string {
		return $query;
	}

	/**
	 * @return array<object>
	 */
	public function get_results( string $query ): array {
		return [
			(object) [
				'bucket'      => intdiv( time(), 900 ),
				'completions' => 1,
			],
		];
	}
}
