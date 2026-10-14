<?php
/**
 * A stand-in for WordPress' wpdb class, for tests/unit/FakeWpdb.php to extend.
 * PHPStan reads WordPress' own definition instead.
 *
 * @package Merkushin\ReportsGraphForSensei
 */

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

if ( ! class_exists( 'wpdb' ) ) {
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
		 * @param string $query   Query with placeholders.
		 * @param mixed  ...$args Values.
		 * @return string
		 */
		public function prepare( $query, ...$args ) {
			return $query;
		}

		/**
		 * @param string|null $query  Query.
		 * @param string      $output Output type.
		 * @return array<object>|null
		 */
		public function get_results( $query = null, $output = OBJECT ) {
			return [];
		}
	}
}
