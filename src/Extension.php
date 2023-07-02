<?php
namespace Merkushin\SenseiReportsExtension;

use Merkushin\Wpal\Service\Assets;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\ServiceFactory;

class Extension {
	/**
	 * @var Hooks
	*/
	private $hooks;

	/**
	 * @var Assets
	 */
	private $assets; 

	public function __construct() {
		$this->hooks = ServiceFactory::create_hooks();
		$this->assets = ServiceFactory::create_assets();
	}

	public function init() {
		$is_students_page = !empty( $_GET['page'] ) && $_GET['page'] == 'sensei_reports' && ( empty( $_GET['view'] ) || $_GET['view'] === 'students' );
		if ( ! $is_students_page ) {
			return;
		}

		(new CompletedCoursesGraph())->init();

		$this->hooks->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		$this->hooks->add_action( 'sensei_analysis_after_headers', array( $this, 'add_graph_canvas' ) );
	}

	public function enqueue_scripts() {
		$this->assets->wp_enqueue_script(
			'sensei-reports-extension',
			'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js',
			[],
			'2.9.4',
			true
		);
		$this->assets->wp_add_inline_script( 'sensei-reports-extension', $this->get_chart_script() );
	}

	public function add_graph_canvas() {
		echo "<canvas id='myChart' styles='max-height: 300px;width: 100%;'></canvas>";
	}

	public function get_chart_script() {
		$start_date = !empty( $_GET['start_date'] ) ? $_GET['start_date'] : date( 'Y-m-d', strtotime( '-1 month' ) );
		$end_date   = !empty( $_GET['end_date'] ) ? $_GET['end_date'] : date( 'Y-m-d' );
		$labels     = $this->get_labels($start_date, $end_date);

		$datasets = $this->hooks->apply_filters(
			'sensei_reports_extension_chart_datasets',
			[],
			$labels,
			$start_date,
			$end_date
		);

		$json_labels = json_encode( $labels );
		$json_datasets = json_encode( $datasets );

		return <<<EOT
			jQuery(document).ready(function() {
				new Chart("myChart", {
						type: "line",
						data: {
						labels: $json_labels,
						datasets: $json_datasets
					},
					options:{}
				});
			});
EOT;
	}

	private function get_labels( string $start_date, string $end_date) {
		$date = new \DateTime( $start_date );
		$labels = [];
		while ( $date->format( 'Y-m-d' ) <= $end_date ) {
			$labels[] = $date->format( 'Y-m-d' );
			$date->modify( '+1 day' );
		}

		return $this->hooks->apply_filters( 'sensei_reports_extension_chart_labels', $labels );
	}

}
