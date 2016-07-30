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
		zip = require('gulp-zip'), // https://github.com/sindresorhus/gulp-zip
		del = require('del'),
		fs = require('fs');

	var
		pkg = JSON.parse(fs.readFileSync('./package.json'), 'utf8');

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

		.pipe(wpPot(pkg._pot))

		.pipe(gulp.dest('./languages'));
	});

	gulp.task('sass', function() {

		return gulp.src('./assets/sass/**/*.scss')

		.pipe(sourcemaps.init())

		.pipe(sass().on('error', sass.logError))

		.pipe(nano({
			// http://cssnano.co/optimisations/
			core: false,
			zindex: false,
			discardComments: false,
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

	gulp.task('build-styles', function() {

		return gulp.src('./assets/sass/**/*.scss')

		.pipe(sass().on('error', sass.logError))

		.pipe(nano({
			zindex: false,
			discardComments: {
				removeAll: true
			}
		}))

		.pipe(gulp.dest('./assets/css'));

	});


	gulp.task('build-copy', ['build-ready'], function() {

		del(['./ready']);

		return gulp.src([
			'./assets/css/**/*.css',
			'./assets/css/**/*.html',
			// './assets/fonts/**/*',
			'./assets/images/**/*',
			'./assets/js/**/*.min.js',
			'./assets/js/**/*.html',
			'./assets/layouts/**/*',
			'./assets/libs/**/*',
			'./assets/vendor/**/*.php',
			'!./assets/vendor/**/test/*',
			'!./assets/vendor/**/Tests/*',
			'!./assets/vendor/**/examples/*',
			'!./assets/vendor/**/.git',
			'./assets/index.html',
			'./includes/**/*',
			'./languages/**/*',
			'!./languages/**/*.po',
			'./locale/**/*',
			'!./locale/**/*.po',
			'./*.php',
			'./*.md',
			'./LICENSE',
			'./index.html',
		], {
			"base": "."
		})

		.pipe(gulp.dest('./ready/' + pkg.name));
	});

	gulp.task('build-zip', ['build-copy'], function() {

		return gulp.src('./ready/**/*')

		.pipe(zip(pkg.name + '-' + pkg.version + '.zip'))

		.pipe(gulp.dest('..'));
	});

	gulp.task('build-ready', ['build-styles']);

	gulp.task('build', ['build-zip']);

	gulp.task('default', function() {

		console.log('Hi, I\'m Gulp!');
	});

}());
