<?php declare(strict_types=1);

namespace Merkushin\SenseiReportsExtension;

use Merkushin\Wpal\ServiceFactory;

class CompletedCoursesGraph {
	/**
	 * @var Hooks
	 */
	private $hooks;

	public function __construct() {
		$this->hooks = ServiceFactory::create_hooks();
	}

	public function init() {
		$this->hooks->add_filter( 'sensei_reports_extension_chart_datasets', array( $this, 'get_chart_data' ), 10, 4 );
	}


	/**
	 * @access private
	 *
	 * @param array $datasets
	 * @param array $labels
	 * @param string $start_date
	 * @param string $end_date
	 * @return array $datasets
	*/
	public function get_chart_data($datasets, $labels, $start_date, $end_date) {
		$sql = "
		select 
			date(comment_date) cdate,
			count(*) cnt
		from wp_comments
		where comment_approved = 'complete'
			and comment_date between '%s' and '%s'
		group by cdate
		order by cdate";

		global $wpdb; 

		$sql = $wpdb->prepare( $sql, $start_date, $end_date );

		$results = $wpdb->get_results( $sql );
		foreach ( $results as $result ) {
			$results[$result->cdate] = $result->cnt;
		}

		$values = [];
		foreach ( $labels as $label ) {
			$values[] = isset( $results[ $label ] ) ? $results[ $label ] : 0;
		}

		$datasets[] = [
			'label' => 'Completed Courses',
			'borderColor' => 'rgba(0,0,255,0.7)',
			'fill' => false,
			'data' => $values,
		];

		return $datasets;
	}
}
