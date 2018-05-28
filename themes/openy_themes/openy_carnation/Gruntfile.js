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
          '<%= theme_dist_css %>/style.css': '<%= theme_src_scss %>/style.scss'
        }
      }
    },

    uglify: {
      dist: {
        options: {
          sourceMap: true,
          includeSources: true
        },
        files: [{
          expand: true,
          cwd: '<%= theme_src_js %>',
          src: ['*.js', '*.min.js'],
          dest: '<%= theme_dist_js %>',
          rename: function (dst, src) {
            return dst + '/' + src.replace('.js', '.min.js');
          }
        }]
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
          '<%= theme_src_scss %>/*.css'
        ],
        dest: '<%= theme_dist_css %>'
      }
    },

    sasslint: {
      options: {
        configFile: '.sass-lint.yml'
      },
      files: ['<%= theme_src_scss %>/**/*.scss']
    },

    jshint: {
      options: {
        curly: true,
        eqeqeq: true,
        eqnull: true,
        browser: true,
        globals: {
          jQuery: true
        }
      },
      files: ['<%= theme_src_js %>/*.js']
    },

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

  grunt.registerTask('build', ['sasslint', 'webfont', 'sass_globbing', 'sass', 'autoprefixer', 'uglify', 'imagemin']);
  grunt.registerTask('default', ['build', 'watch']);
};
