(function () {
  const gulp = require('gulp');
  const plugins = require('gulp-load-plugins')();
  const sass = require('gulp-dart-sass');
  const multipipe = require('multipipe');
  const cssnano = require('cssnano');
  const autoprefixer = require('autoprefixer');
  const rtlcss = require('rtlcss');
  const parseChangelog = require('parse-changelog');
  const mergeJson = require('merge-json');
  const extend = require('xtend');
  const yaml = require('js-yaml');
  const log = require('fancy-log');
  const del = require('del');
  const fs = require('fs-extra');
  const exec = require('child_process').exec;
  const path = require('path');

  const pkg = require('./package.json');
  const config = require('./gulp.config.json');

  let env = config.env;
  const banner = config.banner.join('\n');

  const debug = /--debug/.test(process.argv.slice(2));
  const patch = /--patch/.test(process.argv.slice(2)); // bump a patch?

  try {
    env = extend(config.env, yaml.safeLoad(fs.readFileSync('./environment.yml', { encoding: 'utf-8' }), { json: true }));
  } catch (e) {
    log.warn('no environment.yml loaded!');
  }

  gulp.task('dev:tinify', function () {
    return gulp.src(config.input.images)
      .pipe(plugins.newer(config.output.images))
      .pipe(plugins.tinypngExtended({
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

  function i18nCommand (command, callback) {
    exec('wp i18n ' + command, function (err, stdout, stderr) {
      if (stdout) {
        log.info('WP-CLI:', stdout.trim());
      }
      if (stderr) {
        log.error('Errors:', stderr.trim());
      }
      callback(err);
    });
  }

  // JSON.stringify does not support unicode escaping
  // @REF: https://stackoverflow.com/a/4901205
  function stringifyJSON (json, emitUnicode) {
    const string = JSON.stringify(json);
    return emitUnicode
      ? string
      : string.replace(/[\u007f-\uffff]/g, function (match) {
        return '\\u' + ('0000' + match.charCodeAt(0).toString(16)).slice(-4);
      });
  }

  // clears the destination directory
  gulp.task('i18n:core:clean', function (done) {
    fs.emptyDirSync(config.i18n.core.dist);
    done();
  });

  // clears original wp-cli generated jsons
  gulp.task('i18n:core:done', function (done) {
    // fs.emptyDirSync(config.i18n.core.temp); // WORKING BUT DISABLED
    done();
  });

  // makes a copy of original .po file for make-json and purged by wp-cli
  gulp.task('i18n:core:copy', function (done) {
    fs.copySync(config.i18n.core.raw, config.i18n.core.dist);
    done();
  });

  // dispatches the wp-cli to make-json and purge the .po files
  gulp.task('i18n:core:make', function (cb) {
    i18nCommand('make-json ' +
      config.i18n.core.dist + ' ' +
      config.i18n.core.temp + ' ' +
      // '--no-purge ' + // the whole point of this is not to use the flag!
      '--skip-plugins --skip-themes --skip-packages' +
      (debug ? ' --debug' : ''),
    cb);
  });

  // combines jsons with same name (generated incorrectly by wp-cli)
  gulp.task('i18n:core:json', function () {
    return gulp.src(config.i18n.core.temp + '/*.json')
      .pipe(plugins.rename(function (file) {
        file.basename = file.basename.replace(/^admin-/, '');
      }))
      .pipe(plugins.tap(function (file) {
        let data = JSON.parse(file.contents.toString());
        const dest = path.join(config.i18n.core.dist, file.path.split(path.sep).pop());

        if (fs.existsSync(dest)) {
          data = mergeJson.merge(require('./' + dest), data);
        }

        // to avoid unnecessary commits!
        data['translation-revision-date'] = '';
        data.generator = pkg.productName; // + ' v' + pkg.version;

        file.contents = Buffer.from(stringifyJSON(data));
      }))
      .pipe(gulp.dest(config.i18n.core.dist));
  });

  // the parent task for json support of the core translations
  gulp.task('i18n:core', gulp.series(
    'i18n:core:clean',
    'i18n:core:copy',
    'i18n:core:make',
    'i18n:core:json',
    'i18n:core:done'
  ));

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
      .pipe(sass.sync(config.sass).on('error', sass.logError))
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
      .pipe(sass.sync(config.sass).on('error', sass.logError))
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

  gulp.task('dev:scripts', function () {
    return gulp.src(config.input.js, { base: '.' })
      .pipe(plugins.rename({
        suffix: '.min'
      }))
      .pipe(plugins.uglify())
      .pipe(plugins.size({ title: 'JS:', showFiles: true }))
      .pipe(gulp.dest('.'));
  });

  gulp.task('build:styles', function () {
    return gulp.src(config.input.sass)
      .pipe(sass(config.sass).on('error', sass.logError))
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
      // .pipe(plugins.sass(config.sass).on('error', plugins.sass.logError))
      .pipe(sass(config.sass).on('error', sass.logError))
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
      .pipe(plugins.size({ title: 'JS:', showFiles: true }))
      .pipe(gulp.dest('.'));
  });

  gulp.task('build:banner', function () {
    return gulp.src(config.input.banner, { base: '.' })
      .pipe(plugins.header(banner, {
        pkg: pkg
      }))
      .pipe(gulp.dest('.'));
  });

  gulp.task('build:copy', function () {
    return gulp.src(config.input.final, { base: '.' })
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

  gulp.task('github:package', function (done) {
    if (!env.github) {
      log.error('Error: missing required token for github');
      return done();
    }

    const changes = parseChangelog(fs.readFileSync('CHANGES.md', { encoding: 'utf-8' }), { title: false });
    const options = {
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
      .pipe(plugins.bump({
        type: patch ? 'patch' : 'minor' // `major|minor|patch|prerelease`
      }).on('error', log.error))
      .pipe(gulp.dest('.'));
  });

  gulp.task('bump:plugin', function () {
    return gulp.src(config.pot.metadataFile)
      .pipe(plugins.bump({
        type: patch ? 'patch' : 'minor' // `major|minor|patch|prerelease`
      }).on('error', log.error))
      .pipe(gulp.dest('.'));
  });

  gulp.task('bump:constant', function () {
    return gulp.src(config.pot.metadataFile)
      .pipe(plugins.bump({
        type: patch ? 'patch' : 'minor', // `major|minor|patch|prerelease`
        key: config.constants.version, // for error reference
        regex: new RegExp('([<|\'|"]?(' + config.constants.version + ')[>|\'|"]?[ ]*[:=,]?[ ]*[\'|"]?[a-z]?)(\\d+.\\d+.\\d+)(-[0-9A-Za-z.-]+)?(\\+[0-9A-Za-z\\.-]+)?([\'|"|<]?)', 'i')
      }).on('error', log.error))
      .pipe(gulp.dest('.'));
  });

  gulp.task('bump', gulp.series(
    'bump:package',
    'bump:plugin',
    'bump:constant',
    function (done) {
      log(patch ? 'Bumped to a Patched Version!' : 'Bumped to a Minor Version!');
      done();
    }
  ));

  gulp.task('ready', function (done) {
    log.info('Must build the release!');
    done();
  });

  gulp.task('default', function (done) {
    log.info('Hi, I\'m Gulp!');
    log.info('Sass is:\n' + require('sass').info);
    done();
  });
}());
