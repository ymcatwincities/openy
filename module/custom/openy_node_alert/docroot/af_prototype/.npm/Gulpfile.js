'use strict';

var gulp = require('gulp'),
  browserSync = require('browser-sync'),
  filter = require('gulp-filter'),
  twig = require('gulp-twig'),
  sass = require('gulp-ruby-sass'),
  //sourcemaps = require('gulp-sourcemaps'),
  autoprefixer = require('gulp-autoprefixer'),
  prettify = require('gulp-html-prettify'),
  //data = require('gulp-data'),
  path = require('path'),
  reload = browserSync.reload,
  src = {
    scss: '../scss/**/*.scss',
    css: '../css',
    html: '../templates/*.twig',
    html_blocks: '../templates/**/*.twig',
    //dataJson: '../data/*.json',
    javascript: '../js/*.js',
  };

/**
 * Start the BrowserSync Static Server + Watch files
 */
gulp.task('serve', ['sass', 'templates'], function () {

  browserSync({
    server: "../",
    files: ["../css/styles.css", src.html]
  });

  gulp.watch(src.scss, ['sass']);
  gulp.watch([src.html, src.html_blocks], ['templates']);
  gulp.watch(src.javascript, reload);
  //gulp.watch(src.dataJson, reload);
});


/**
 * Kick off the sass stream with source maps + error handling
 */
function sassStream() {
  return sass('../scss', {
    sourcemap: false,
    style: 'expanded',
    unixNewlines: true
  })
    .on('error', function (err) {
      console.error('Error!', err.message);
    })
    .pipe(autoprefixer({add: false, browsers: []}));
    //.pipe(sourcemaps.write('./', {
      //includeContent: false,
      //sourceRoot: '../scss'
    //}));
}


/**
 * Compile sass, filter the results, inject CSS into all browsers.
 */
gulp.task('sass', function () {
  return sassStream()
    .pipe(gulp.dest(src.css))
    .pipe(filter("**/*.css"))
    .pipe(reload({
      stream: true
    }));
});

/**
 * Generate templates.
 */
gulp.task('templates', function () {
  return gulp.src(src.html)
    //.pipe(data(function (file) {
      //return require('../data/global.json');
      //return require('../data/' + path.basename(file.path, '.twig') + '.json');
    //}))
    .pipe(twig())
    .pipe(prettify({indent_char: ' ', indent_size: 2}))
    .pipe(gulp.dest('../'))
    .on("end", reload);
});


// CIBOX.
if (process.env.APP_ENV === 'dev') {
  gulp.task('default', ['sass', 'templates']);
} else {
  gulp.task('default', ['serve']);
}

