/**
 * Draws the course completions chart from the data ChartPanel puts on the canvas.
 */
import {
	BarController,
	BarElement,
	CategoryScale,
	Chart,
	Legend,
	LinearScale,
	Tooltip,
} from 'chart.js';

Chart.register(
	BarController,
	BarElement,
	CategoryScale,
	Legend,
	LinearScale,
	Tooltip
);

/**
 * Colors for datasets without their own; the first is replaced by the admin color scheme's.
 */
export const PALETTE = [
	'#2271b1',
	'#d63638',
	'#00a32a',
	'#dba617',
	'#8c5ad4',
];

/**
 * Gives each dataset without a color one from the palette.
 *
 * @param {Object[]} datasets   Chart.js datasets.
 * @param {string}   themeColor Admin color scheme's main color, if known.
 * @return {Object[]} The datasets, colored.
 */
export function colorDatasets( datasets, themeColor = '' ) {
	const palette = themeColor
		? [ themeColor, ...PALETTE.slice( 1 ) ]
		: PALETTE;

	return datasets.map( ( dataset, index ) => {
		const color = palette[ index % palette.length ];

		return {
			backgroundColor: color,
			borderColor: color,
			...dataset,
		};
	} );
}

/**
 * Builds the Chart.js configuration.
 *
 * @param {{labels: string[], datasets: Object[]}} data       Labels and datasets from PHP.
 * @param {string}                                 themeColor Admin color scheme's main color.
 * @return {Object} Chart.js configuration.
 */
export function buildConfig( data, themeColor = '' ) {
	const datasets = colorDatasets( data.datasets, themeColor );

	return {
		type: 'bar',
		data: {
			labels: data.labels,
			datasets,
		},
		options: {
			maintainAspectRatio: false,
			interaction: { mode: 'index', intersect: false },
			plugins: {
				// One dataset needs no legend: the heading names it.
				legend: { display: datasets.length > 1 },
			},
			scales: {
				x: { grid: { display: false } },
				y: { beginAtZero: true, ticks: { precision: 0 } },
			},
		},
	};
}

/**
 * Reads the admin color scheme's main color, set by WordPress on the admin page.
 *
 * @return {string} A CSS color, or an empty string.
 */
export function getThemeColor() {
	return window
		.getComputedStyle( document.documentElement )
		.getPropertyValue( '--wp-admin-theme-color' )
		.trim();
}

/**
 * Draws a chart on each canvas with chart data inside the root element.
 *
 * @param {Element|Document} root       Element to search.
 * @param {string}           themeColor Admin color scheme's main color.
 * @return {Chart[]} The charts drawn.
 */
export function renderCharts( root, themeColor = '' ) {
	return Array.from(
		root.querySelectorAll(
			'.reports-graph-for-sensei-lms canvas[data-chart]'
		)
	).map(
		( canvas ) =>
			new Chart(
				canvas,
				buildConfig( JSON.parse( canvas.dataset.chart ), themeColor )
			)
	);
}
