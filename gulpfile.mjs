import gulp from 'gulp';
import sass from 'gulp-dart-sass';
import * as compiler from 'sass';
import sourcemaps from 'gulp-sourcemaps';
import postcss from 'gulp-postcss';
import cssnano from 'cssnano';
import autoprefixer from 'autoprefixer';
import rename from 'gulp-rename';
import size from 'gulp-size';
import tap from 'gulp-tap';
import gulpdebug from 'gulp-debug';
import gulpif from 'gulp-if';
import header from 'gulp-header';
import uglify from 'gulp-uglify';
import zip from 'gulp-zip';
import bump from 'gulp-bump';
import changedInPlace from 'gulp-changed-in-place';
import githubRelease from 'gulp-github-release';
import excludeGitignore from 'gulp-exclude-gitignore';
import log from 'fancy-log';
import rtlcss from 'rtlcss';
import livereload from 'gulp-livereload';
import parseChangelog from 'parse-changelog';
import checkTextDomain from 'gulp-checktextdomain';
import multipipe from 'multipipe';
import mergeJson from 'merge-json';
import extend from 'xtend';
import yaml from 'js-yaml';

import { exec } from 'child_process';
import path from 'path';

import fs from 'fs-extra';
import { deleteSync } from 'del';
import { readFileSync } from 'node:fs';
// import { readFile } from 'fs/promises';
// import { emptyDirSync } from 'fs-extra';

// @REF: https://www.stefanjudis.com/snippets/how-to-import-json-files-in-es-modules-node-js/
import { createRequire } from 'module';
const require = createRequire(import.meta.url);

const { src, dest, watch, series, parallel, task } = gulp;

// @REF: https://www.stefanjudis.com/snippets/how-to-import-json-files-in-es-modules-node-js/
// const conf = JSON.parse(await readFile(new URL('./gulp.config.json', import.meta.url))); // eslint-disable-line
// const pkg = JSON.parse(await readFile(new URL('./package.json', import.meta.url))); // eslint-disable-line

const conf = require('./gulp.config.json');
const pkg = require('./package.json');

// @REF: https://www.sitepoint.com/pass-parameters-gulp-tasks/
const devBuild = ((process.env.NODE_ENV || 'development').trim().toLowerCase() === 'development'); // eslint-disable-line

// @REF: https://www.sitepoint.com/pass-parameters-gulp-tasks/
const args=(argList=>{let arg={},a,opt,thisOpt,curOpt;for(a=0;a<argList.length;a++){thisOpt=argList[a].trim();opt=thisOpt.replace(/^\-+/,'');if(opt===thisOpt){if(curOpt)arg[curOpt]=opt;curOpt=null;}else{curOpt=opt;arg[curOpt]=true;}}return arg;})(process.argv); // eslint-disable-line

// @REF: https://stackoverflow.com/a/7224605
const capitalize = s => s && s[0].toUpperCase() + s.slice(1); // eslint-disable-line

// @REF: https://stackoverflow.com/a/49968211
const normalizeEOL = s => s.replace(/^\s*[\r\n]/gm, '\r\n');

// @REF: https://flaviocopes.com/how-to-check-if-file-exists-node/
function fsExists(path){try{if(fs.existsSync(path)){return true;}}catch(err){log.error(err)};return false;} // eslint-disable-line

let env = conf.env;
const banner = conf.banner.join('\n');

const debug = /--debug/.test(process.argv.slice(2));
const patch = /--patch/.test(process.argv.slice(2)); // bump a patch?

try {
  env = extend(conf.env, yaml.load(readFileSync('./environment.yml', { encoding: 'utf-8' }), { json: true }));
} catch (e) {
  log.warn('no environment.yml loaded!');
}

function i18nCommand (command, callback) {
  exec('wp i18n ' + command, function (err, stdout, stderr) {
    if (stdout) log.info('WP-CLI:', stdout.trim());
    if (stderr) log.error('Errors:', stderr.trim());
    callback(err);
  });
}

function githubCommand (command, callback) {
  exec('gh ' + command, function (err, stdout, stderr) {
    if (stdout) log.info('GitHub:', stdout.trim());
    if (stderr) log.error('Errors:', stderr.trim());
    callback(err);
  });
}

// JSON.stringify does not support Unicode escaping
// @REF: https://stackoverflow.com/a/4901205
function stringifyJSON (json, emitUnicode) {
  const string = JSON.stringify(json);
  return emitUnicode
    ? string
    : string.replace(/[\u007f-\uffff]/g, function (match) {
      return '\\u' + ('0000' + match.charCodeAt(0).toString(16)).slice(-4);
    });
}

// Clears the destination directory
task('i18n:core:clean', function (done) {
  fs.emptyDirSync(conf.i18n.core.dist);
  done();
});

// Clears original `wp-cli` generated JSON
task('i18n:core:done', function (done) {
  fs.emptyDirSync(conf.i18n.core.temp);
  done();
});

// Makes a copy of original .po file for `make-json` and purged by `wp-cli`
task('i18n:core:copy', function (done) {
  fs.copySync(conf.i18n.core.raw, conf.i18n.core.dist);
  done();
});

// Dispatches the `wp-cli` to `make-json` and purge the `.po` files
task('i18n:core:make', function (cb) {
  i18nCommand('make-json ' +
    conf.i18n.core.dist + ' ' +
    conf.i18n.core.temp + ' ' +
    // '--no-purge ' + // the whole point of this is not to use the flag!
    '--update-mo-files ' + // @REF: https://github.com/wp-cli/i18n-command/issues/126
    '--skip-plugins --skip-themes --skip-packages' +
    (debug ? ' --debug' : ''),
  cb);
});

// Dispatches the `wp-cli` to make-php
task('i18n:core:php', function (cb) {
  i18nCommand('make-php ' +
    conf.i18n.core.dist + ' ' +
    '--skip-plugins --skip-themes --skip-packages ' +
    // '--pretty-print' +
    (debug ? ' --debug' : ''),
  cb);
});

// Combines JSON files with same name (generated incorrectly by `wp-cli`)
task('i18n:core:json', function () {
  return src(conf.i18n.core.temp + '/*.json')
    .pipe(rename(function (file) {
      file.basename = file.basename.replace(/^admin-/, '');
    }))
    .pipe(tap(function (file) {
      let data = JSON.parse(file.contents.toString());
      const dest = path.join(conf.i18n.core.dist, file.path.split(path.sep).pop());

      if (fsExists(dest)) {
        data = mergeJson.merge(require('./' + dest), data);
      }

      // to avoid unnecessary commits!
      data['translation-revision-date'] = '';
      data.generator = pkg.productName; // + ' v' + pkg.version;

      file.contents = Buffer.from(stringifyJSON(data));
    }))
    .pipe(dest(conf.i18n.core.dist));
});

// The parent task for JSON support of the core translations
task('i18n:core', series(
  'i18n:core:clean',
  'i18n:core:copy',
  'i18n:core:make',
  'i18n:core:php',
  'i18n:core:json',
  'i18n:core:done'
));

task('textdomain', function () {
  return src(conf.input.php)
    .pipe(excludeGitignore())
    .pipe(checkTextDomain(conf.textdomain));
});

task('dev:clean', function (done) {
  deleteSync([conf.output.clean]);
  done();
});

task('dev:rtl', function () {
  return src(conf.input.sass)
    .pipe(sourcemaps.init())
    .pipe(sass.sync(conf.sass).on('error', sass.logError))
    .pipe(postcss([
      cssnano(conf.cssnano.dev),
      autoprefixer(conf.autoprefixer.dev)
    ]))
    .pipe(sourcemaps.write(conf.output.sourcemaps))
    .pipe(size({ title: 'CSS:', showFiles: true }))
    .pipe(dest(conf.output.css)).on('error', log.error)
    .pipe(gulpif(conf.input.rtldev,
      multipipe(
        postcss([rtlcss()]),
        rename({ suffix: '-rtl' }),
        dest(conf.output.css)
      )
    ))
    .pipe(changedInPlace())
    .pipe(size({ title: 'RTL:', showFiles: true }))
    .pipe(gulpdebug({ title: 'Changed' }))
    .pipe(gulpif(function (file) {
      if (file.extname !== '.map') return true;
    }, livereload()));
});

task('watch:styles', function () {
  livereload.listen();
  watch(conf.input.sass, series('dev:rtl'));
});

// all styles / without livereload
task('dev:styles', function () {
  return src(conf.input.sass)
    .pipe(sourcemaps.init())
    .pipe(sass.sync(conf.sass).on('error', sass.logError))
    .pipe(postcss([
      cssnano(conf.cssnano.dev),
      autoprefixer(conf.autoprefixer.dev)
    ]))
    .pipe(header(banner, { pkg }))
    .pipe(sourcemaps.write(conf.output.sourcemaps))
    .pipe(size({ title: 'CSS:', showFiles: true }))
    .pipe(gulpdebug({ title: 'Created' }))
    .pipe(dest(conf.output.css)).on('error', log.error)
    .pipe(gulpif(conf.input.rtldev,
      multipipe(
        postcss([rtlcss()]),
        rename({ suffix: '-rtl' }),
        size({ title: 'RTL:', showFiles: true }),
        gulpdebug({ title: 'RTLed' })
      )
    ))
    .pipe(dest(conf.output.css)).on('error', log.error);
});

task('dev:scripts', function () {
  return src(conf.input.js, { base: '.' })
    .pipe(rename({
      suffix: '.min'
    }))
    .pipe(uglify())
    .pipe(size({ title: 'JS:', showFiles: true }))
    .pipe(dest('.'));
});

task('build:styles', function () {
  return src(conf.input.sass)
    .pipe(sass(conf.sass).on('error', sass.logError))
    .pipe(postcss([
      cssnano(conf.cssnano.build),
      autoprefixer(conf.autoprefixer.build)
    ]))
    .pipe(size({ title: 'CSS:', showFiles: true }))
    .pipe(dest(conf.output.css)).on('error', log.error);
});

// seperated because of stripping rtl directives in compression
task('build:rtl', function () {
  return src(conf.input.rtl)
    .pipe(sass.sync().on('error', sass.logError))
    // .pipe(postcss([rtlcss()])) // divided to avoid cssnano messing with rtl directives
    .pipe(postcss([
      rtlcss(),
      cssnano(conf.cssnano.build),
      autoprefixer(conf.autoprefixer.build)
    ]))
    .pipe(rename({ suffix: '-rtl' }))
    .pipe(size({ title: 'RTL:', showFiles: true }))
    .pipe(dest(conf.output.css)).on('error', log.error);
});

task('build:scripts', function () {
  return src(conf.input.js, { base: '.' })
    .pipe(rename({
      suffix: '.min'
    }))
    .pipe(uglify())
    .pipe(size({ title: 'JS:', showFiles: true }))
    .pipe(dest('.'));
});

task('build:banner', function () {
  return src(conf.input.banner, { base: '.' })
    .pipe(header(banner, {
      pkg
    }))
    .pipe(dest('.'));
});

task('build:copy', function () {
  return src(conf.input.final, {
    base: '.',
    allowEmpty: true,
    buffer: true,
    encoding: false,
    removeBOM: false
  })
    .pipe(dest(conf.output.ready + pkg.name));
});

task('build:clean', function (done) {
  deleteSync([conf.output.ready]);
  done();
});

task('build:zip', function () {
  return src(conf.input.ready, {
    allowEmpty: true,
    buffer: true,
    encoding: false,
    removeBOM: false
  })
    .pipe(zip(pkg.name + '-' + pkg.version + '.zip'))
    .pipe(dest(conf.output.final));
});

task('build', series(
  parallel('build:styles', 'build:rtl', 'build:scripts'),
  'build:banner',
  'build:clean',
  'build:copy',
  'build:zip',
  function (done) {
    log('Done!');
    done();
  }
));

task('github:package:old', function (done) {
  if (!env.github) {
    log.error('Error: missing required token for github');
    return done();
  }

  const changes = parseChangelog(fs.readFileSync(conf.root.changelog, { encoding: 'utf-8' }), { title: false });
  const options = {
    token: env.github,
    tag: pkg.version,
    notes: changes.versions[0].rawNote,
    manifest: pkg,
    skipIfPublished: true,
    draft: true
  };

  return src(pkg.name + '-' + pkg.version + '.zip')
    .pipe(githubRelease(options));
});

task('github:package', function (done) {
  // if (!env.github) {
  //   log.error('Error: missing required token for github');
  //   return done();
  // }

  const filename = pkg.name + '-' + pkg.version + '.zip';

  if (!fsExists('./' + filename)) {
    log.error('Error: missing required package for github');
    return done();
  }

  // const changes = parseChangelog(fs.readFileSync(conf.root.changelog, { encoding: 'utf-8' }), { title: false });

  // @REF: https://cli.github.com/manual/gh_release_create
  githubCommand('release create ' +
    '"' + pkg.version + '" ' +
    '"./' + filename + '#WordPress Plugin" ' +
    '--draft' + ' ' +
    '--latest' + ' ' + // default: automatic based on date and version
    '--title "' + pkg.version + '" ' +
    // '--notes "' + normalizeEOL(changes.versions[0].rawNote.toString()) + '" ' +
    '--notes-from-tag ' +
    '--fail-on-no-commits ' + // Create a release only if there are new commits available since the last release
    '',
  done);
});

task('bump:package', function () {
  return src('./package.json')
    .pipe(bump({
      type: patch ? 'patch' : 'minor' // `major|minor|patch|prerelease`
    }).on('error', log.error))
    .pipe(dest('.'));
});

task('bump:plugin', function () {
  return src(conf.pot.metadataFile)
    .pipe(bump({
      type: patch ? 'patch' : 'minor' // `major|minor|patch|prerelease`
    }).on('error', log.error))
    .pipe(dest('.'));
});

task('bump:constant', function () {
  return src(conf.pot.metadataFile)
    .pipe(bump({
      type: patch ? 'patch' : 'minor', // `major|minor|patch|prerelease`
      key: conf.constants.version, // for error reference
      regex: new RegExp('([<|\'|"]?(' + conf.constants.version + ')[>|\'|"]?[ ]*[:=,]?[ ]*[\'|"]?[a-z]?)(\\d+.\\d+.\\d+)(-[0-9A-Za-z.-]+)?(\\+[0-9A-Za-z\\.-]+)?([\'|"|<]?)', 'i')
    }).on('error', log.error))
    .pipe(dest('.'));
});

task('bump', series(
  'bump:package',
  'bump:plugin',
  'bump:constant',
  function (done) {
    log(patch ? 'Bumped to a Patched Version!' : 'Bumped to a Minor Version!');
    done();
  }
));

task('ready', function (done) {
  log.info('Must build the release!');
  done();
});

task('default', function (done) {
  log.info('Hi, I\'m Gulp!');
  log.info('Sass is:\n' + compiler.default.info);
  done();
});

task('test:package', function (done) {
  const file = pkg.name + '-' + pkg.version + '.zip';

  if (!fsExists('./' + file)) {
    log.error('Error: missing required package for github');
    return done();
  }

  done();
});

task('test:changelog', function (done) {
  const changes = parseChangelog(fs.readFileSync(conf.root.changelog, { encoding: 'utf-8' }), { title: false });
  // log.info(normalizeEOL(changes.versions[0].rawNote));
  log.info(normalizeEOL(changes.versions[0].rawNote.toString()));
  done();
});
