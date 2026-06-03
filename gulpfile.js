/**
 * Self-contained asset build pipeline.
 *
 * Replaces the legacy @kunoichi/gulp-assets-task-set (which pulled the
 * unmaintained node-sass). Pipeline:
 *   - SCSS -> dist/css : dart-sass (compressed) + autoprefixer + sourcemaps
 *   - JS   -> dist/js  : esbuild (JSX -> wp.element.createElement, minified, sourcemaps)
 *   - dump -> wp-dependencies.json : @kunoichi/grab-deps reads the @deps/@handle
 *             banner comments from the built files.
 *
 * The loud `/*! ... *​/` banner in each source file is preserved into dist
 * (sass keeps loud comments; esbuild keeps legal comments inline), so
 * grab-deps can derive handles and deps from dist/{js,css}.
 */
const fs = require( 'node:fs' );
const gulp = require( 'gulp' );
const sass = require( 'gulp-sass' )( require( 'sass' ) );
const autoprefixer = require( 'gulp-autoprefixer' ).default;
const sourcemaps = require( 'gulp-sourcemaps' );
const esbuild = require( 'esbuild' );
const { dumpSetting } = require( '@kunoichi/grab-deps' );

/**
 * Compile SCSS into dist/css.
 *
 * @return {NodeJS.ReadWriteStream} Gulp stream.
 */
function css() {
	return gulp
		.src( 'assets/scss/**/*.scss' )
		.pipe( sourcemaps.init() )
		.pipe( sass( { outputStyle: 'compressed' } ).on( 'error', sass.logError ) )
		.pipe( autoprefixer() )
		.pipe( sourcemaps.write( './map' ) )
		.pipe( gulp.dest( 'dist/css' ) );
}

/**
 * Transpile / minify JS into dist/js.
 *
 * Sources rely on WordPress runtime globals (wp.*, jQuery) declared via
 * `@deps` banners, so there is nothing to bundle across files.
 *
 * @return {NodeJS.ReadWriteStream} Gulp stream.
 */
function js() {
	const entryPoints = fs
		.readdirSync( 'assets/js' )
		.filter( ( file ) => file.endsWith( '.js' ) )
		.map( ( file ) => `assets/js/${ file }` );

	return esbuild.build( {
		entryPoints,
		outdir: 'dist/js',
		bundle: false,
		minify: true,
		sourcemap: true,
		target: 'es2017',
		legalComments: 'inline',
		loader: { '.js': 'jsx' },
		jsxFactory: 'wp.element.createElement',
		jsxFragment: 'wp.element.Fragment',
	} );
}

/**
 * Regenerate wp-dependencies.json from the built assets.
 *
 * @param {Function} done Async completion callback.
 */
function dump( done ) {
	dumpSetting( 'dist', './wp-dependencies.json' );
	done();
}

const build = gulp.series( gulp.parallel( css, js ), dump );

/**
 * Watch sources and rebuild on change.
 */
function watch() {
	gulp.watch( 'assets/scss/**/*.scss', gulp.series( css, dump ) );
	gulp.watch( 'assets/js/**/*.js', gulp.series( js, dump ) );
}

exports.css = css;
exports.js = js;
exports.dump = dump;
exports.build = build;
exports.watch = watch;
exports.default = build;
