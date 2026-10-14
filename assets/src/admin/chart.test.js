import { beforeEach, describe, expect, it, vi } from 'vitest';
import { Chart } from 'chart.js';
import { PALETTE, buildConfig, colorDatasets, renderCharts } from './chart';

vi.mock( 'chart.js', () => {
	const MockChart = vi.fn( function ( canvas, config ) {
		this.canvas = canvas;
		this.config = config;
	} );
	MockChart.register = vi.fn();

	return {
		Chart: MockChart,
		BarController: {},
		BarElement: {},
		CategoryScale: {},
		Legend: {},
		LinearScale: {},
		Tooltip: {},
	};
} );

const data = {
	labels: [ 'Oct 1', 'Oct 2' ],
	datasets: [ { label: 'Completed courses', data: [ 1, 3 ] } ],
};

describe( 'colorDatasets', () => {
	it( 'uses the admin theme color for the first dataset', () => {
		const [ dataset ] = colorDatasets( data.datasets, '#3858e9' );

		expect( dataset.backgroundColor ).toBe( '#3858e9' );
		expect( dataset.borderColor ).toBe( '#3858e9' );
	} );

	it( 'falls back to the palette without a theme color', () => {
		const datasets = colorDatasets( [ {}, {} ] );

		expect( datasets.map( ( d ) => d.backgroundColor ) ).toEqual(
			PALETTE.slice( 0, 2 )
		);
	} );

	it( 'keeps colors a dataset already has', () => {
		const [ dataset ] = colorDatasets(
			[ { backgroundColor: 'red' } ],
			'#3858e9'
		);

		expect( dataset.backgroundColor ).toBe( 'red' );
		expect( dataset.borderColor ).toBe( '#3858e9' );
	} );

	it( 'repeats the palette for many datasets', () => {
		const datasets = colorDatasets(
			Array.from( { length: PALETTE.length + 1 }, () => ( {} ) )
		);

		expect( datasets[ PALETTE.length ].backgroundColor ).toBe(
			PALETTE[ 0 ]
		);
	} );
} );

describe( 'buildConfig', () => {
	it( 'draws a bar chart of the labels and datasets', () => {
		const config = buildConfig( data );

		expect( config.type ).toBe( 'bar' );
		expect( config.data.labels ).toEqual( data.labels );
		expect( config.data.datasets[ 0 ].data ).toEqual( [ 1, 3 ] );
	} );

	it( 'counts in whole numbers from zero', () => {
		const { y } = buildConfig( data ).options.scales;

		expect( y.beginAtZero ).toBe( true );
		expect( y.ticks.precision ).toBe( 0 );
	} );

	it( 'shows the legend only with more than one dataset', () => {
		const two = {
			...data,
			datasets: [ ...data.datasets, { label: 'Other', data: [ 0, 1 ] } ],
		};

		expect( buildConfig( data ).options.plugins.legend.display ).toBe(
			false
		);
		expect( buildConfig( two ).options.plugins.legend.display ).toBe(
			true
		);
	} );
} );

describe( 'renderCharts', () => {
	beforeEach( () => {
		Chart.mockClear();
	} );

	it( 'draws a chart from the data on each chart canvas', () => {
		const canvas = { dataset: { chart: JSON.stringify( data ) } };
		const root = { querySelectorAll: vi.fn( () => [ canvas ] ) };

		const charts = renderCharts( root, '#3858e9' );

		expect( root.querySelectorAll ).toHaveBeenCalledWith(
			'.reports-graph-for-sensei-lms canvas[data-chart]'
		);
		expect( charts ).toHaveLength( 1 );
		expect( Chart ).toHaveBeenCalledWith(
			canvas,
			expect.objectContaining( { type: 'bar' } )
		);
		expect( charts[ 0 ].config.data.datasets[ 0 ].backgroundColor ).toBe(
			'#3858e9'
		);
	} );

	it( 'draws nothing without chart canvases', () => {
		expect( renderCharts( { querySelectorAll: () => [] } ) ).toEqual( [] );
		expect( Chart ).not.toHaveBeenCalled();
	} );
} );
