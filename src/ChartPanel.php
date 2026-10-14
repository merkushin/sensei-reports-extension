<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei;

use DateTimeImmutable;
use Merkushin\ReportsGraphForSensei\Completions\CommentsCompletionsQuery;
use Merkushin\ReportsGraphForSensei\Completions\CompletionsQuery;
use Merkushin\ReportsGraphForSensei\Completions\TablesCompletionsQuery;
use Merkushin\Wpal\Service\Capabilities;
use Merkushin\Wpal\Service\Dates;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\Service\Json;
use Merkushin\Wpal\Service\Localization;
use Merkushin\Wpal\Service\Options;
use Merkushin\Wpal\Service\Users;
use Merkushin\Wpal\ServiceFactory;

defined( 'ABSPATH' ) || exit;

/**
 * The chart above the Students report, for the report's date range.
 *
 * Sensei shows the Reports page to users who can `manage_sensei_grades`, teachers included.
 * Users who can't `manage_sensei` (teachers) see completions of their own courses only, as in
 * the rest of Sensei's reports.
 */
class ChartPanel {
	/**
	 * Fired by Sensei after the Reports page navigation, above the report table.
	 */
	public const HOOK = 'sensei_analysis_after_headers';

	/**
	 * @var CompletionsQuery|null
	 */
	private $query;

	/**
	 * @var Hooks
	 */
	private $hooks;

	/**
	 * @var Capabilities
	 */
	private $capabilities;

	/**
	 * @var Users
	 */
	private $users;

	/**
	 * @var Dates
	 */
	private $dates;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var Localization
	 */
	private $localization;

	/**
	 * @var Json
	 */
	private $json;

	/**
	 * @param CompletionsQuery|null $query Counts completions; by default, in the progress storage Sensei uses.
	 */
	public function __construct( ?CompletionsQuery $query = null ) {
		$this->query        = $query;
		$this->hooks        = ServiceFactory::create_hooks();
		$this->capabilities = ServiceFactory::create_capabilities();
		$this->users        = ServiceFactory::create_users();
		$this->dates        = ServiceFactory::create_dates();
		$this->options      = ServiceFactory::create_options();
		$this->localization = ServiceFactory::create_localization();
		$this->json         = ServiceFactory::create_json();
	}

	/**
	 * Whether the current request shows the chart: the Students report, to a user who can see reports.
	 */
	public function is_visible(): bool {
		return $this->get_request()->is_students_report()
			&& $this->capabilities->current_user_can( 'manage_sensei_grades' );
	}

	public function render(): void {
		if ( ! $this->is_visible() ) {
			return;
		}

		$request = $this->get_request();
		$range   = DateRange::from_filters(
			$request->get_start_date(),
			$request->get_end_date(),
			$this->dates->current_datetime()->setTimezone( $request->get_timezone( $this->dates->wp_timezone() ) )
		);

		$author_id = $this->capabilities->current_user_can( 'manage_sensei' ) ? 0 : (int) $this->users->get_current_user_id();
		$chart     = new CompletionsChart( $this->get_query(), $this->hooks, $this->dates );
		$data      = $chart->get_data( $range, $author_id );
		$summary   = $this->get_summary( $data['total'], $range );
		?>
		<div class="reports-graph-for-sensei-lms">
			<h2 class="reports-graph-for-sensei-lms__title"><?php esc_html_e( 'Course completions', 'reports-graph-for-sensei-lms' ); ?></h2>
			<p class="reports-graph-for-sensei-lms__summary"><?php echo esc_html( $summary ); ?></p>
			<?php if ( $data['total'] > 0 ) : ?>
				<div class="reports-graph-for-sensei-lms__chart">
					<canvas role="img" aria-label="<?php echo esc_attr( $summary ); ?>" data-chart="<?php echo esc_attr( (string) $this->json->wp_json_encode( $data ) ); ?>"></canvas>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function get_summary( int $total, DateRange $range ): string {
		$format = (string) $this->options->get_option( 'date_format', 'F j, Y' );
		$start  = (string) $this->dates->wp_date( $format, $this->noon( $range->get_start_date(), $range ), $range->get_timezone() );
		$end    = (string) $this->dates->wp_date( $format, $this->noon( $range->get_end_date(), $range ), $range->get_timezone() );

		if ( 0 === $total ) {
			/* translators: 1: first day of the report, 2: last day of the report. */
			return sprintf( __( 'No courses were completed from %1$s to %2$s.', 'reports-graph-for-sensei-lms' ), $start, $end );
		}

		return sprintf(
			/* translators: 1: number of completed courses, 2: first day of the report, 3: last day of the report. */
			_n(
				'%1$s course completed from %2$s to %3$s.',
				'%1$s courses completed from %2$s to %3$s.',
				$total,
				'reports-graph-for-sensei-lms'
			),
			$this->localization->number_format_i18n( $total ),
			$start,
			$end
		);
	}

	/**
	 * Timestamp of noon on a day, far from midnight, so the formatted date is the same day.
	 */
	private function noon( string $date, DateRange $range ): int {
		return ( new DateTimeImmutable( $date . ' 12:00:00', $range->get_timezone() ) )->getTimestamp();
	}

	private function get_request(): ReportRequest {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filters, the same Sensei reads; ReportRequest accepts only exact values, dates and timezone names.
		return new ReportRequest( $_GET );
	}

	private function get_query(): CompletionsQuery {
		if ( null === $this->query ) {
			global $wpdb;
			$this->query = self::uses_progress_tables() ? new TablesCompletionsQuery( $wpdb ) : new CommentsCompletionsQuery( $wpdb );
		}

		return $this->query;
	}

	/**
	 * Whether Sensei reads progress from its own tables (High-Performance Progress Storage,
	 * Sensei LMS 4.17+) rather than from comments.
	 */
	private static function uses_progress_tables(): bool {
		$settings = '\Sensei\Internal\Services\Progress_Storage_Settings';

		return is_callable( [ $settings, 'is_hpps_enabled' ] )
			&& is_callable( [ $settings, 'is_tables_repository' ] )
			&& call_user_func( [ $settings, 'is_hpps_enabled' ] )
			&& call_user_func( [ $settings, 'is_tables_repository' ] );
	}
}
