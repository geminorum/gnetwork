(function () {
  var gulp = require('gulp');
  var plugins = require('gulp-load-plugins')();
  var multipipe = require('multipipe');
  var cssnano = require('cssnano');
  var autoprefixer = require('autoprefixer');
  var rtlcss = require('rtlcss');
  var parseChangelog = require('parse-changelog');
  var prettyjson = require('prettyjson');
  var extend = require('xtend');
  var yaml = require('js-yaml');
  var log = require('fancy-log');
  var del = require('del');
  var fs = require('fs');

  var pkg = require('./package.json');
  var config = require('./gulp.config.json');

  var env = config.env;
  var banner = config.banner.join('\n');

  try {
    env = extend(config.env, yaml.safeLoad(fs.readFileSync('./environment.yml', { encoding: 'utf-8' }), { 'json': true }));
  } catch (e) {
    log.warn('no environment.yml loaded!');
  }

  gulp.task('dev:tinify', function () {
    return gulp.src(config.input.images)
      .pipe(plugins.newer(config.output.images))
      .pipe(plugins.tinypngUnlimited({
        key: env.tinypng,
        sigFile: config.logs.tinypng,
        summarize: true,
        keepMetadata: false,
        keepOriginal: true,
        log: true
      }))
      .pipe(gulp.dest(config.output.images));
  });

  gulp.task('svgmin', function () {
    return gulp.src(config.input.svg)
      .pipe(plugins.newer(config.output.images))
      .pipe(plugins.svgmin()) // SEE: http://dbushell.com/2016/03/01/be-careful-with-your-viewbox/
      .pipe(gulp.dest(config.output.images));
  });

  gulp.task('smushit', function () {
    return gulp.src(config.input.images)
      .pipe(plugins.newer(config.output.images))
      .pipe(plugins.smushit())
      .pipe(gulp.dest(config.output.images));
  });

  gulp.task('pot', function () {
    return gulp.src(config.input.php)
      .pipe(plugins.excludeGitignore())
      .pipe(plugins.wpPot(config.pot))
      .pipe(gulp.dest(config.output.languages));
  });

  gulp.task('textdomain', function () {
    return gulp.src(config.input.php)
      .pipe(plugins.excludeGitignore())
      .pipe(plugins.checktextdomain(config.textdomain));
  });

  gulp.task('dev:clean', function (done) {
    del.sync([config.output.clean]);
    done();
  });

  gulp.task('dev:rtl', function () {
    return gulp.src(config.input.sass)
      .pipe(plugins.sourcemaps.init())
      .pipe(plugins.sass.sync(config.sass).on('error', plugins.sass.logError))
      .pipe(plugins.postcss([
        cssnano(config.cssnano.dev),
        autoprefixer(config.autoprefixer.dev)
      ]))
      .pipe(plugins.sourcemaps.write(config.output.sourcemaps))
      .pipe(plugins.size({ title: 'CSS:', showFiles: true }))
      .pipe(gulp.dest(config.output.css)).on('error', log.error)
      .pipe(plugins.if(config.input.rtldev,
        multipipe(
          plugins.postcss([rtlcss()]),
          plugins.rename({ suffix: '-rtl' }),
          gulp.dest(config.output.css)
        )
      ))
      .pipe(plugins.changedInPlace())
      .pipe(plugins.size({ title: 'RTL:', showFiles: true }))
      .pipe(plugins.debug({ title: 'Changed' }))
      .pipe(plugins.if(function (file) {
        if (file.extname !== '.map') return true;
      }, plugins.livereload()));
  });

  gulp.task('watch:styles', function () {
    plugins.livereload.listen();
    gulp.watch(config.input.sass, gulp.series('dev:rtl'));
  });

  // all styles / without livereload
  gulp.task('dev:styles', function () {
    return gulp.src(config.input.sass)
      .pipe(plugins.sourcemaps.init())
      .pipe(plugins.sass.sync(config.sass).on('error', plugins.sass.logError))
      .pipe(plugins.postcss([
        cssnano(config.cssnano.dev),
        autoprefixer(config.autoprefixer.dev)
      ]))
      .pipe(plugins.header(banner, { pkg: pkg }))
      .pipe(plugins.sourcemaps.write(config.output.sourcemaps))
      .pipe(plugins.size({ title: 'CSS:', showFiles: true }))
      .pipe(plugins.debug({ title: 'Created' }))
      .pipe(gulp.dest(config.output.css)).on('error', log.error)
      .pipe(plugins.if(config.input.rtldev,
        multipipe(
          plugins.postcss([rtlcss()]),
          plugins.rename({ suffix: '-rtl' }),
          plugins.size({ title: 'RTL:', showFiles: true }),
          plugins.debug({ title: 'RTLed' })
        )
      ))
      .pipe(gulp.dest(config.output.css)).on('error', log.error);
  });

  gulp.task('build:styles', function () {
    return gulp.src(config.input.sass)
      .pipe(plugins.sass(config.sass).on('error', plugins.sass.logError))
      .pipe(plugins.postcss([
        cssnano(config.cssnano.build),
        autoprefixer(config.autoprefixer.build)
      ]))
      .pipe(plugins.size({ title: 'CSS:', showFiles: true }))
      .pipe(gulp.dest(config.output.css)).on('error', log.error);
  });

  // seperated because of stripping rtl directives in compression
  gulp.task('build:rtl', function () {
    return gulp.src(config.input.rtl)
      .pipe(plugins.sass(config.sass).on('error', plugins.sass.logError))
      .pipe(plugins.postcss([
        rtlcss(),
        cssnano(config.cssnano.build),
        autoprefixer(config.autoprefixer.build)
      ]))
      .pipe(plugins.rename({ suffix: '-rtl' }))
      .pipe(plugins.size({ title: 'RTL:', showFiles: true }))
      .pipe(gulp.dest(config.output.css)).on('error', log.error);
  });

  gulp.task('build:scripts', function () {
    return gulp.src(config.input.js, { base: '.' })
      .pipe(plugins.rename({
        suffix: '.min'
      }))
      .pipe(plugins.uglify())
      .pipe(plugins.size({ title: 'JS:', showFiles: true }));
  });

  gulp.task('build:banner', function () {
    return gulp.src(config.input.banner, { 'base': '.' })
      .pipe(plugins.header(banner, {
        pkg: pkg
      }))
      .pipe(gulp.dest('.'));
  });

  gulp.task('build:copy', function () {
    return gulp.src(config.input.final, { 'base': '.' })
      .pipe(gulp.dest(config.output.ready + pkg.name));
  });

  gulp.task('build:clean', function (done) {
    del.sync([config.output.ready]);
    done();
  });

  gulp.task('build:zip', function () {
    return gulp.src(config.input.ready)
      .pipe(plugins.zip(pkg.name + '-' + pkg.version + '.zip'))
      .pipe(gulp.dest(config.output.final));
  });

  gulp.task('build', gulp.series(
    gulp.parallel('build:styles', 'build:rtl', 'build:scripts'),
    'build:banner',
    'build:clean',
    'build:copy',
    'build:zip',
    function (done) {
      log('Done!');
      done();
    }
  ));

  gulp.task('github:package', function () {
    var changes = parseChangelog(fs.readFileSync('CHANGES.md', { encoding: 'utf-8' }), { title: false });
    var options = {
      token: env.github,
      tag: pkg.version,
      notes: changes.versions[0].rawNote,
      manifest: pkg,
      skipIfPublished: true,
      draft: true
    };

    return gulp.src(pkg.name + '-' + pkg.version + '.zip')
      .pipe(plugins.githubRelease(options));
  });

  gulp.task('bump:package', function () {
    return gulp.src('./package.json')
      .pipe(plugins.bump().on('error', log.error))
      .pipe(gulp.dest('.'));
  });

  gulp.task('bump:plugin', function () {
    return gulp.src(config.pot.metadataFile)
      .pipe(plugins.bump().on('error', log.error))
      .pipe(gulp.dest('.'));
  });

  gulp.task('bump:constant', function () {
    return gulp.src(config.pot.metadataFile)
      .pipe(plugins.bump({
        // regex: new RegExp("([<|\'|\"]?" + config.constants.version + "[>|\'|\"]?[ ]*[:=,]?[ ]*[\'|\"]?[a-z]?)(\\d+\\.\\d+\\.\\d+)(-[0-9A-Za-z\.-]+)?([\'|\"|<]?)", "i")
        regex: new RegExp("([<|'|\"]?" + config.constants.version + "[>|'|\"]?[ ]*[:=,]?[ ]*['|\"]?[a-z]?)(\\d+\\.\\d+\\.\\d+)(-[0-9A-Za-z.-]+)?(['|\"|<]?)", 'i')
      }).on('error', log.error))
      .pipe(gulp.dest('.'));
  });

  gulp.task('bump', gulp.series(
    'bump:package',
    'bump:plugin',
    'bump:constant',
    function (done) {
      log('Bumped!');
      done();
    }
  ));

  gulp.task('ready', function (done) {
    log.info('Must build the release!');
    done();
  });

  gulp.task('default', function (done) {
    log.info('Hi, I\'m Gulp!');
    log.info('Sass is:\n' + require('node-sass').info);
    log.info('\n');
    console.log(prettyjson.render(pkg));
    log.info('\n');
    console.log(prettyjson.render(config));
    done();
  });
}());
