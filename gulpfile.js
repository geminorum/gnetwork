(function () {
	'use strict';

	var
		gulp       = require('gulp'),
		sass       = require('gulp-sass'), // https://github.com/dlmanning/gulp-sass
		changed    = require('gulp-changed'),
		tinypng    = require('gulp-tinypng'), // https://github.com/creativeaura/gulp-tinypng
		nano       = require('gulp-cssnano'), // https://github.com/ben-eb/gulp-cssnano
		sourcemaps = require('gulp-sourcemaps'),
		smushit    = require('gulp-smushit'), // https://github.com/heldr/gulp-smushit
		pngquant   = require('imagemin-pngquant'); // https://github.com/imagemin/imagemin-pngquant

	gulp.task( 'tinypng', function() {

		return gulp.src( './assets/images/raw/*.png' )

			.pipe( tinypng( '' ) )

			.pipe( gulp.dest( './assets/images' ) );
	});

	gulp.task( 'pngquant', function() {

		return gulp.src( './assets/images/raw/*.png' )

			.pipe( pngquant({
				quality: '65-80',
				speed: 4
			} ) )

			.pipe( gulp.dest( './assets/images' ) );
	});

	gulp.task( 'smushit', function () {

		return gulp.src( './assets/images/raw/**/*.{jpg,png}' )

			.pipe( smushit() )

			.pipe( gulp.dest( './assets/images' ) );
	});

	gulp.task( 'sass', function() {

		return gulp.src('./assets/sass/**/*.scss')

			.pipe( sourcemaps.init() )

			.pipe( sass().on( 'error', sass.logError ) )

			.pipe( nano( {
				discardComments: {
					removeAll: true
				}
			} ) )

			.pipe( sourcemaps.write( './maps' ) )

			.pipe( gulp.dest( './assets/css' ) );
	});

	gulp.task( 'watch', function () {

		gulp.watch( './assets/sass/**/*.scss', [
			'sass'
		] );
	});

	gulp.task( 'default', function() {

		console.log( 'Hi, I\'m Gulp!' );
	});

}());
