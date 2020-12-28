/**
 * Gulp task for Web development.
 *
 * @package @kunoichi-gulp-assets-task-set
 */

const gulp = require( 'gulp' );
const gulpTask = require( '@kunoichi/gulp-assets-task-set' );

/*
 * Register all defined tasks.
 *
 * Try run `npx gulp --tasks` to list all tasks.
 * Each directory should be like:
 *
 * @param {string} src  Source directory of assets.
 * @param {string} dist Target directory
 */
gulpTask.all( 'assets', 'dist' );
