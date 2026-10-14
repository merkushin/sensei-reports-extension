=== Reports Graph for Sensei LMS ===
Contributors: merkushin
Tags: sensei, lms, reports, chart, courses
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: sensei-lms
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a chart of course completions over time to the Sensei LMS Students report.

== Description ==

See at a glance how many students finish your courses, and when. Reports Graph for Sensei LMS adds a bar chart of course completions above the Students report in **Sensei LMS → Reports**.

* The chart follows the report's **Last Activity** dates. Without dates, it shows the last 30 days.
* Ranges up to three months are shown by day, longer ranges by month.
* Days are counted in your browser's timezone, as in the rest of the report.
* Teachers see completions of their own courses only; administrators see all courses.
* Works with both of Sensei's progress storages: the default one and High-Performance Progress Storage.

There are no settings: activate the plugin and open the report.

= For developers =

Add your own lines to the chart with the `reports_graph_for_sensei_lms_datasets` filter. Each dataset is a [Chart.js dataset](https://www.chartjs.org/docs/latest/charts/bar.html#dataset-properties) with one value per period:

`
add_filter(
	'reports_graph_for_sensei_lms_datasets',
	function ( array $datasets, array $periods, $range ) {
		$datasets[] = [
			'label' => __( 'Enrolments', 'my-plugin' ),
			'data'  => my_plugin_count_enrolments( $periods, $range ),
		];
		return $datasets;
	},
	10,
	3
);
`

`$periods` lists the chart's days (`Y-m-d`) or months (`Y-m`), oldest first. `$range` is a `Merkushin\ReportsGraphForSensei\DateRange` with the timezone and the UTC start and end of the range.

= Privacy =

The plugin stores no data and sends nothing anywhere. It reads course progress that Sensei LMS already keeps, and shows it only to users who can see Sensei's reports. The chart library, Chart.js, is bundled with the plugin; nothing is loaded from other sites.

= Source code =

The uncompiled JavaScript and CSS and the build instructions are at https://github.com/merkushin/sensei-reports-extension.

This plugin is not affiliated with or endorsed by Automattic or Sensei LMS.

== Installation ==

1. Install and activate [Sensei LMS](https://wordpress.org/plugins/sensei-lms/).
2. In your dashboard, go to **Plugins → Add New Plugin** and search for "Reports Graph for Sensei LMS", or upload the plugin zip.
3. Activate the plugin.
4. Go to **Sensei LMS → Reports**.

== Frequently Asked Questions ==

= Where is the chart? =

Above the table in **Sensei LMS → Reports → Students**. It isn't shown on the Courses and Lessons reports or on the report for a single student or course.

= Why does the chart show no completions? =

It counts courses completed in the selected dates. Choose a longer range with the **Last Activity** dates above the table, and press **Filter**.

= Which date does a completion count on? =

The day the student completed the course, in your browser's timezone. Sensei keeps only the time of the latest change to a student's course status, so if Sensei updates a completed course's status later, the completion counts on the day of that update.

= Can teachers see other teachers' courses? =

No. Users who can't manage Sensei LMS, such as teachers, see completions of courses they are the author of.

== Screenshots ==

1. Course completions by day above the Students report.

== Changelog ==

= 1.0.0 =
* Initial release.
