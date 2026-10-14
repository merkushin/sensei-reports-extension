/**
 * Extends the @wordpress/scripts webpack config with the plugin's entry points.
 *
 * Each entry builds assets/build/<name>.js, <name>.css (from CSS the entry imports) and
 * <name>.asset.php, which Plugin::enqueue_entry() reads for dependencies and the version.
 * Add an entry here, then enqueue it from PHP.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		admin: './assets/src/admin/index.js',
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'assets/build' ),
	},
};
