(function() {
	'use strict';

	var
		gulp = require('gulp'),
		sass = require('gulp-sass'), // https://github.com/dlmanning/gulp-sass
		changed = require('gulp-changed'),
		tinypng = require('gulp-tinypng'), // https://github.com/creativeaura/gulp-tinypng
		nano = require('gulp-cssnano'), // https://github.com/ben-eb/gulp-cssnano
		sourcemaps = require('gulp-sourcemaps'),
		smushit = require('gulp-smushit'), // https://github.com/heldr/gulp-smushit
		excludeGitignore = require('gulp-exclude-gitignore'), // https://github.com/sboudrias/gulp-exclude-gitignore
		wpPot = require('gulp-wp-pot'), // https://github.com/rasmusbe/gulp-wp-pot
		sort = require('gulp-sort'),
		fs = require('fs');

	var
		json = JSON.parse(fs.readFileSync('./package.json'));

	gulp.task('tinypng', function() {

		return gulp.src('./assets/images/raw/*.png')

		.pipe(tinypng(''))

		.pipe(gulp.dest('./assets/images'));
	});

	gulp.task('smushit', function() {

		return gulp.src('./assets/images/raw/**/*.{jpg,png}')

		.pipe(smushit())

		.pipe(gulp.dest('./assets/images'));
	});

	gulp.task('pot', function() {

		return gulp.src([
			'./**/*.php',
			'!./assets/libs/**',
		])

		.pipe(excludeGitignore())

		.pipe(sort())

		.pipe(wpPot(json._pot))

		.pipe(gulp.dest('./languages'));
	});

	gulp.task('sass', function() {

		return gulp.src('./assets/sass/**/*.scss')

		.pipe(sourcemaps.init())

		.pipe(sass().on('error', sass.logError))

		.pipe(nano({
			// http://cssnano.co/optimisations/
			zindex: false,
			discardComments: {
				removeAll: true
			}
		}))

		.pipe(sourcemaps.write('./maps'))

		.pipe(gulp.dest('./assets/css'));

		//.pipe(livereload())

		// .pipe(notify({
		// 	message: "Sass Compiled."
		// }));

	});

	gulp.task('watch', function() {

		gulp.watch('./assets/sass/**/*.scss', [
			'sass'
		]);
	});

	gulp.task('default', function() {

		console.log('Hi, I\'m Gulp!');
	});

}());
