const TerserPlugin = require("terser-webpack-plugin");
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');
const webpack = require( 'webpack' );

module.exports = {
	mode: 'production',
	performance: {
		maxEntrypointSize: 500000,
		maxAssetSize: 500000,
	},
	optimization: {
		minimize: true,
		minimizer: [new TerserPlugin( {
			terserOptions: {
				comments: 'all',
			},
			extractComments: ( astNode, comment ) => {
				return !! /@(deps|license)/.test( comment.value );
			},
			test: /\.js(\?.*)?$/i,
		} ) ],
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /(node_modules)/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [
							'@babel/preset-env',
						],
						plugins: [ '@babel/plugin-transform-react-jsx' ],
					},
				},
			},
		],
	},
	devtool: 'source-map',
};
