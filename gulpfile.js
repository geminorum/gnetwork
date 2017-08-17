(function() {

  var
    gulp = require('gulp'),
    gutil = require('gulp-util'),
    plugins = require('gulp-load-plugins')(),
    parseChangelog = require('parse-changelog'),
    prettyjson = require('prettyjson'),
    extend = require('xtend'),
    yaml = require('js-yaml'),
    del = require('del'),
    fs = require('fs'),

    pkg = require('./package.json'),
    config = require('./gulpconfig.json'),

    env = config.env,
    banner = config.banner.join('\n');

  try {
    env = extend(config.env, yaml.safeLoad(fs.readFileSync('./environment.yml', {encoding: 'utf-8'}), {'json': true}));
  } catch (e) {
    gutil.log('no environment.yml loaded!');
  }

  gulp.task('dev:tinify', function () {
    return gulp.src(config.input.images)
    .pipe(plugins.newer(config.output.images))
    .pipe(plugins.tinypngCompress({
      key: env.tinypng,
      sigFile: config.logs.tinypng,
      summarize: true,
      log: true
    }))
    .pipe(gulp.dest(config.output.images));
  });

  gulp.task('svgmin', function() {
    return gulp.src(config.input.svg)
    .pipe(plugins.newer(config.output.images))
    .pipe(plugins.svgmin()) // SEE: http://dbushell.com/2016/03/01/be-careful-with-your-viewbox/
    .pipe(gulp.dest(config.output.images));
  });

  gulp.task('smushit', function() {
    return gulp.src(config.input.images)
    .pipe(plugins.newer(config.output.images))
    .pipe(plugins.smushit())
    .pipe(gulp.dest(config.output.images));
  });

  gulp.task('pot', function() {
    return gulp.src(config.input.php)
    .pipe(plugins.excludeGitignore())
    .pipe(plugins.wpPot(config.pot))
    .pipe(gulp.dest(config.output.languages));
  });

  gulp.task('textdomain', function() {
    return gulp.src(config.input.php)
      .pipe(plugins.excludeGitignore())
      .pipe(plugins.checktextdomain(config.textdomain));
  });

  gulp.task('dev:sass', function() {
    return gulp.src(config.input.sass)
    .pipe(plugins.sourcemaps.init())
    .pipe(plugins.sass.sync(config.sass).on('error', plugins.sass.logError))
    .pipe(plugins.cssnano({
      core: false,
      zindex: false,
      discardComments: false,
    }))
    .pipe(plugins.sourcemaps.write(config.output.sourcemaps))
    .pipe(gulp.dest(config.output.css)).on('error', gutil.log)
    .pipe(plugins.changedInPlace())
    .pipe(plugins.debug({title: 'unicorn:'}))
    .pipe(plugins.if( function(file){
      if (file.extname != '.map') return true;
    }, plugins.livereload()));
  });

  gulp.task('dev:watch', function() {
    plugins.livereload.listen();
    gulp.watch(config.input.sass, gulp.series('dev:sass'));
  });

  // all styles / without livereload
  gulp.task('dev:styles', function() {
    return gulp.src(config.input.sass)
    .pipe(plugins.sourcemaps.init())
    .pipe(plugins.sass.sync(config.sass).on('error', plugins.sass.logError))
    .pipe(plugins.cssnano({
      core: false,
      zindex: false,
      discardComments: false,
    }))
    .pipe(plugins.header(banner, {
      pkg: pkg
    }))
    .pipe(plugins.sourcemaps.write(config.output.sourcemaps))
    .pipe(plugins.debug({title: 'unicorn:'}))
    .pipe(gulp.dest(config.output.css)).on('error', gutil.log);
  });

  gulp.task('build:styles', function() {
    return gulp.src(config.input.sass)
    .pipe(plugins.sass(config.sass).on('error', plugins.sass.logError))
    .pipe(plugins.cssnano({
      zindex: false,
      discardComments: {
        removeAll: true
      }
    }))
    .pipe(gulp.dest(config.output.css));
  });

  gulp.task('build:scripts', function() {
    return gulp.src(config.input.js, {base: '.'})
    .pipe(plugins.rename({
      suffix: '.min',
    }))
    .pipe(plugins.uglify());
  });

  gulp.task('build:banner', function() {
    return gulp.src(config.input.banner, {'base': '.'})
    .pipe(plugins.header(banner, {
      pkg: pkg
    }))
    .pipe(gulp.dest('.'));
  });

  gulp.task('build:copy', function() {
    return gulp.src(config.input.final, {'base': '.'})
    .pipe(gulp.dest(config.output.ready + pkg.name));
  });

  gulp.task('build:clean', function(done) {
    del.sync([config.output.ready]);
    done();
  });

  gulp.task('build:zip', function() {
    return gulp.src(config.input.ready)
    .pipe(plugins.zip(pkg.name + '-' + pkg.version + '.zip'))
    .pipe(gulp.dest(config.output.final));
  });

  gulp.task('build', gulp.series(
    gulp.parallel('build:styles', 'build:scripts'),
    'build:banner',
    'build:clean',
    'build:copy',
    'build:zip',
    function(done) {
      gutil.log('Done!');
      done();
  }));

  gulp.task('release', function(){

    var changes = parseChangelog(fs.readFileSync('CHANGES.md', {encoding: 'utf-8'}), {title: false});

    return gulp.src(pkg.name + '-' + pkg.version + '.zip')
      .pipe(plugins.githubRelease({
        token: env.github,
        tag: pkg.version,
        notes: changes.versions[0].rawNote,
        manifest: pkg,
        draft: true
      }));
  });

  gulp.task('bump:package', function(){
    return gulp.src('./package.json')
    .pipe(plugins.bump().on('error', gutil.log))
    .pipe(gulp.dest('.'));
  });

  gulp.task('bump:plugin', function(){
    return gulp.src(config.pot.metadataFile)
    .pipe(plugins.bump().on('error', gutil.log))
    .pipe(gulp.dest('.'));
  });

  gulp.task('bump:constant', function(){
    return gulp.src(config.pot.metadataFile)
    .pipe(plugins.bump({
      regex: new RegExp( "([<|\'|\"]?"+config.constants.version+"[>|\'|\"]?[ ]*[:=,]?[ ]*[\'|\"]?[a-z]?)(\\d+\\.\\d+\\.\\d+)(-[0-9A-Za-z\.-]+)?([\'|\"|<]?)", "i" ),
    }).on('error', gutil.log))
    .pipe(gulp.dest('.'));
  });

  gulp.task('bump', gulp.series(
    'bump:package',
    'bump:plugin',
    'bump:constant',
    function(done) {
      gutil.log('Bumped!');
      done();
  }));

  gulp.task('default', function(done) {
    gutil.log('Hi, I\'m Gulp!');
    gutil.log("Sass is:\n"+require('node-sass').info);
    gutil.log("\n");
    console.log(prettyjson.render(pkg));
    gutil.log("\n");
    console.log(prettyjson.render(config));
    done();
  });
}());
