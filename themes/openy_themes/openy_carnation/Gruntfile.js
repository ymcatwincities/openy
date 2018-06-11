module.exports = function(grunt) {
  "use strict";
  require('time-grunt')(grunt);

  var global_vars = {
    theme_dist_css: 'dist/css',
    theme_dist_js: 'dist/js',
    theme_src_scss: 'src/scss',
    theme_src_js: 'src/js',
  };

  grunt.initConfig({
    global_vars: global_vars,
    pkg: grunt.file.readJSON('package.json'),

    sass_globbing: {
      dist: {
        files: {
          '<%= global_vars.theme_src_scss %>/_init.scss': [
            '<%= global_vars.theme_src_scss %>/presentation/_functions.scss',
            '<%= global_vars.theme_src_scss %>/presentation/_variables.scss',
            '<%= global_vars.theme_src_scss %>/presentation/_mixins.scss',
          ],
          '<%= global_vars.theme_src_scss %>/_base.scss': [
            '<%= global_vars.theme_src_scss %>/component/**/*.scss',
            '<%= global_vars.theme_src_scss %>/jquery-ui/**/*.scss',
            '<%= global_vars.theme_src_scss %>/modules/**/*.scss',
            '<%= global_vars.theme_src_scss %>/paragraphs/**/*.scss',
            '<%= global_vars.theme_src_scss %>/layouts/**/*.scss',
            '<%= global_vars.theme_src_scss %>/_overrides.scss'
          ]
        }
      }
    },

    sass: {
      modules: {
        options: {
          includePaths: [
            'scss'
          ],
          outputStyle: 'nested',
          sourceMap: true
        }
      },
      dist: {
        options: {
          includePaths: [
            'node_modules/bootstrap/scss/',
            'node_modules/breakpoint-sass/stylesheets'
          ],
          outputStyle: 'nested',
          sourceMap: true
        },
        files: {
          '<%= global_vars.theme_dist_css %>/style.css': '<%= global_vars.theme_src_scss %>/style.scss'
        }
      }
    },

    autoprefixer: {
      dist: {
        options: {
          map: true
        },
        expand: true,
        flatten: true,
        src: [
          '<%= global_vars.theme_src_scss %>/*.css'
        ],
        dest: ['<%= global_vars.theme_dist_css %>/']
      }
    },

    // copy bootstrap asset
    copy: {
      main: {
        files: [{
          expand: true,
          src: [
            'node_modules/bootstrap/dist/js/bootstrap.js',
            'node_modules/jquery-match-height/dist/jquery.matchHeight.js'
          ],
          dest: '<%= global_vars.theme_src_js %>',
          flatten: true
        }]
      }
    },

    // compress js
    uglify: {
      dist: {
        options: {
          sourceMap: true,
          includeSources: true
        },
        files: [{
          expand: true,
          cwd: '<%= global_vars.theme_src_js %>',
          src: ['*.js', '*.min.js'],
          dest: '<%= global_vars.theme_dist_js %>',
          rename: function (dst, src) {
            return dst + '/' + src.replace('.js', '.min.js');
          }
        }]
      }
    },

    // linting
    sasslint: {
      options: {
        configFile: '.sass-lint.yml'
      },
      files: ['<%= global_vars.theme_src_scss %>/**/*.scss']
    },

    jshint: {
      options: {
        curly: true,
        eqeqeq: true,
        eqnull: true,
        browser: true,
        globals: {
          jQuery: true
        },
        ignores: [
          '<%= global_vars.theme_src_js %>/bootstrap.js',
          '<%= global_vars.theme_src_js %>/popper.js',
        ]
      },
      files: ['<%= global_vars.theme_src_js %>/*.js']
    },

    // watch
    watch: {
      grunt: { files: ['Gruntfile.js'] },
      sass: {
        files: ['<%= sasslint.files %>'],
        tasks: ['sass', 'autoprefixer', 'sasslint'],
        options: {
          livereload: true
        }
      },
      js: {
        files: ['<%= jshint.files %>'],
        tasks: ['jshint']
      }
    },

  });

  grunt.loadNpmTasks('grunt-autoprefixer');
  grunt.loadNpmTasks('grunt-sass-globbing');
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-sass-lint');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-copy');

  grunt.registerTask('build', ['sasslint', 'sass_globbing', 'sass', 'autoprefixer', 'copy', 'uglify']);
  grunt.registerTask('default', ['build', 'watch']);
};
