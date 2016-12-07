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
    uncss          = require('gulp-uncss'),
    gutil          = require('gulp-util'),
    browserSync    = require('browser-sync').create();

var path = {
    build : {
        html      : 'style-guide/',
        js        : 'scripts/',
        css       : 'css/',
        img       : 'images/',
        fonts     : 'fonts/',
        bowerCopy : 'prototypes/js/lib/'
    },

    src : {
        html            : 'prototypes/html/*.html',
        js              : 'prototypes/js/**/*.js',
        style           : [
            'prototypes/scss/screen.scss',
            'prototypes/scss/docs.scss'
        ],
        img             : 'prototypes/images/**/*.*',
        fonts           : [
            'prototypes/fonts/**/*.eot',
            'prototypes/fonts/**/*.svg',
            'prototypes/fonts/**/*.ttf',
            'prototypes/fonts/**/*.woff'
        ],
        bowerCopy       : [],
        svg             : 'prototypes/sprite/svg/*.svg',
        svgScssDest     : 'prototypes/scss/base/',
        svgScssTemplate : 'prototypes/scss/spriteTemplate'
    },

    watch : {
        html    : 'prototypes/**/*.html',
        js      : 'prototypes/js/**/*.js',
        style   : 'prototypes/scss/**/*.scss',
        img     : 'prototypes/images/**/*.*',
        fonts   : 'prototypes/fonts/**/*.*',
        svg     : 'prototypes/sprite/svg/*.svg'
    },

    clean : [
        'html/*',
        'js/*',
        'css/*',
        'images/*',
        'fonts/*'
    ]
};

gulp.task('serve', ['style:build'], function() {

    browserSync.init({
        server: {
            baseDir: "./",
            index: "./style-guide/index.html"
        }
    });

    gulp.watch("./src/scss/*.scss", ['style:build']);
    gulp.watch("./style-guide/*.html").on('change',browserSync.reload);
});

gulp.task('html:build', function(){
    gulp.src(path.src.html)
        .pipe(rigger())
        .pipe(gulp.dest(path.build.html))
        .pipe(browserSync.stream());
});

gulp.task('js:build', function(){
    gulp.src(path.src.js)
        .pipe(gulp.dest(path.build.js))
        .pipe(browserSync.stream());
});

gulp.task('style:build', function(){
    gulp.src(path.src.style)
        .pipe(sourcemaps.init())
        .pipe(sass.sync().on('error', sass.logError))
        .pipe(sourcemaps.write())
        .pipe(autoprefixer({
            browsers : ['last 9 versions', 'ie 9', '> 1%']
        }))
        .pipe(postcss([assets({
            loadPaths : ['src/images/*']
        })]))
        .pipe(gulp.dest(path.build.css))
        .pipe(browserSync.stream());
});

gulp.task('image:build', function(){
    gulp.src(path.src.img)
        .pipe(gulp.dest(path.build.img))
        .pipe(browserSync.stream());
});

gulp.task('fonts:build', function(){
    gulp.src(path.src.fonts)
        .pipe(gulp.dest(path.build.fonts))
        .pipe(browserSync.stream());
});

gulp.task('bowerCopy', function(){
    return gulp.src(path.src.bowerCopy)
        .pipe(gulp.dest(path.build.bowerCopy))
        .pipe(browserSync.stream());
});

gulp.task('svgSprite:build', function(){
    return gulp.src(path.src.svg)
        .pipe(svgmin())
        .pipe(cheerio({
            run: function ($, file) {
                $('[style]').removeAttr('style');
                $('[font-family]').removeAttr('font-family');
                $('[overflow]').removeAttr('overflow');
                $('[color]').removeAttr('color');
            },
            parserOptions: { xmlMode: true }
        }))
        .pipe(svgsprite({
            shape     : {
                spacing : {
                    padding : 5
                }
            },
            mode      : {
                css : {
                    dest   : './',
                    layout : 'diagonal',
                    //sprite: path.sprite.svg,
                    sprite : '../../images/sprite.svg',
                    bust   : false,
                    render : {
                        scss : {
                            dest     : 'sprite.scss',
                            template : path.src.svgScssTemplate
                        }
                    }
                }
            },
            variables : {
                mapname : 'icons'
            }
        }))
        .pipe(gulp.dest(path.src.svgScssDest))
        .pipe(browserSync.stream());
});

gulp.task('clean', function () {
    return gulp.src(
        path.clean
        , {
            read: false
        })
        .pipe(clean());
});

gulp.task('build', [
    'bowerCopy',
    'html:build',
    'js:build',
    'style:build',
    'fonts:build',
    'image:build',
    'svgSprite:build'
]);

gulp.task('watch', function(){
    gulp.watch(path.watch.html, {interval: 750}, ['html:build']);
    gulp.watch(path.watch.style, {interval: 750}, ['style:build']);
    gulp.watch('bower_components/**/*.*', {interval: 750}, ['bowerCopy']);
    gulp.watch(path.watch.js, {interval: 750}, ['js:build']);
    gulp.watch(path.watch.img, {interval: 750}, ['image:build']);
    gulp.watch(path.watch.fonts, {interval: 750}, ['fonts:build']);
    gulp.watch(path.watch.svg, {interval: 750}, ['svgSprite:build']);
});

gulp.task('default', ['serve', 'build', 'watch']);
