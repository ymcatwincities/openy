'use strict';

var gulp           = require('gulp'),
    autoprefixer   = require('gulp-autoprefixer'),
    sass           = require('gulp-sass'),
    sourcemaps     = require('gulp-sourcemaps'),
    rigger         = require('gulp-rigger'),
    clean          = require('gulp-clean'),
    cssclean       = require('gulp-clean-css'),
    imagemin       = require('gulp-imagemin'),
    pngquant       = require('imagemin-pngquant'),
    rename         = require('gulp-rename'),
    postcss        = require('gulp-postcss'),
    assets         = require('postcss-assets'),
    cheerio        = require('gulp-cheerio'),
    svgmin         = require('gulp-svgmin'),
    svgsprite      = require('gulp-svg-sprite'),
    stripDebug     = require('gulp-strip-debug'),
    uncss          = require('gulp-uncss'),
    gutil          = require('gulp-util'),
    browserSync    = require('browser-sync').create();

var path = {
    build : {
        css : 'css/',
    },

    src : {
        style           : [
            'scss/screen.scss',
            'scss/screen-schedule.scss',
            'scss/openy-digital-signage-screen.scss',
        ],
    },

    watch : {
        style : 'scss/**/*.scss',
    },

    clean : [
        'css/*',
    ]
};

gulp.task('serve', ['style:build'], function() {
    gulp.watch("./scss/*.scss", ['style:build']);
});

gulp.task('style:build', function(){
    gulp.src(path.src.style)
        .pipe(sass.sync().on('error', sass.logError))
        .pipe(autoprefixer({
            browsers : ['last 9 versions', 'ie 9', '> 1%']
        }))
        .pipe(gulp.dest(path.build.css))
        .pipe(browserSync.stream());
});

gulp.task('clean', function () {
    //return gulp.src(
    //    path.clean
    //    , {
    //        read: false
    //    })
    //    .pipe(clean());
});

gulp.task('build', [
    'style:build',
]);

gulp.task('watch', function(){
    gulp.watch(path.watch.style, { interval: 750 }, ['style:build']);
    //gulp.watch('bower_components/**/*.*', { interval: 750 }, ['bowerCopy']);
});

gulp.task('default', ['serve', 'build', 'watch']);
