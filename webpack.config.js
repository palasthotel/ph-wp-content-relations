
module.exports = {
	entry: {
		meta: './js_src/meta.js',
	},
	output: {
		path: './js',
		filename: '[name].js',
		sourceMapFilename: '[name].map'
	},
	devtool: 'source-map',
	module: {
		loaders: [{
			test: /\.js$/,
			exclude: /node_modules|bower_components/,
			loader: 'babel-loader',
			query: {
				presets: ["es2015", "react"],
				plugins: ["transform-object-rest-spread"]
			}
		}]
	},
	watch: true,
};