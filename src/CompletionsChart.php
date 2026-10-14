<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei;

use Merkushin\ReportsGraphForSensei\Completions\CompletionsQuery;
use Merkushin\Wpal\Service\Dates;
use Merkushin\Wpal\Service\Hooks;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the chart's data: a label per period and the completions dataset, which other
 * plugins can add to with the `reports_graph_for_sensei_lms_datasets` filter.
 */
class CompletionsChart {
	public const DATASETS_FILTER = 'reports_graph_for_sensei_lms_datasets';

	/**
	 * @var CompletionsQuery
	 */
	private $query;

	/**
	 * @var Hooks
	 */
	private $hooks;

	/**
	 * @var Dates
	 */
	private $dates;

	public function __construct( CompletionsQuery $query, Hooks $hooks, Dates $dates ) {
		$this->query = $query;
		$this->hooks = $hooks;
		$this->dates = $dates;
	}

	/**
	 * @param DateRange $range     Days to chart.
	 * @param int       $author_id Chart only courses by this user; 0 charts all courses.
	 * @return array{labels: string[], datasets: array<array<string, mixed>>, total: int} The data
	 *         for Chart.js, and the total number of completions in the range.
	 */
	public function get_data( DateRange $range, int $author_id ): array {
		$counts = $this->query->count( $range, $author_id );
		$format = DateRange::GRANULARITY_DAY === $range->get_granularity()
			/* translators: Date format for a day on the chart's axis, see https://www.php.net/manual/datetime.format.php */
			? _x( 'M j', 'chart day label', 'reports-graph-for-sensei-lms' )
			/* translators: Date format for a month on the chart's axis, see https://www.php.net/manual/datetime.format.php */
			: _x( 'M Y', 'chart month label', 'reports-graph-for-sensei-lms' );

		$labels = [];
		foreach ( $range->get_periods() as $start ) {
			$labels[] = (string) $this->dates->wp_date( $format, $start->getTimestamp(), $range->get_timezone() );
		}

		$datasets = [
			[
				'label' => __( 'Completed courses', 'reports-graph-for-sensei-lms' ),
				'data'  => array_values( $counts ),
			],
		];

		/**
		 * Filters the chart's datasets, to add lines of your own.
		 *
		 * Each dataset is a Chart.js dataset with one value in `data` per period. Datasets
		 * without colors get them from the chart.
		 *
		 * @param array<array<string, mixed>> $datasets Chart.js datasets.
		 * @param string[]                    $periods  The periods, oldest first: days (Y-m-d) or months (Y-m).
		 * @param DateRange                   $range    The report's date range and timezone.
		 */
		$datasets = $this->hooks->apply_filters( self::DATASETS_FILTER, $datasets, array_keys( $counts ), $range );

		return [
			'labels'   => $labels,
			'datasets' => array_values( (array) $datasets ),
			'total'    => array_sum( $counts ),
		];
	}
}
