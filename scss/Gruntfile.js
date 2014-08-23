module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    sass: {
      options: {
        includePaths: ['foundation/bower_components/foundation/scss']
      },
      custom: {
        options: {
        },
        files: {
          '../css/boltbb.custom.css': 'boltbb.custom.scss'
        }        
      },
      custom_min: {
        options: {
          outputStyle: 'compressed'
        },
        files: {
          '../css/boltbb.custom.min.css': 'boltbb.custom.scss'
        }        
      },
      dist: {
        options: {
        },
        files: {
          '../css/boltbb.css': 'boltbb.scss'
        }        
      },
      dist_min: {
        options: {
          outputStyle: 'compressed'
        },
        files: {
          '../css/boltbb.min.css': 'boltbb.scss'
        }        
      }
    },
    
    uglify: {
        options: {
            includePaths: ['']
        },
        jquery: {
            options: {
                mangle: true,
                sourceMap: true,
                preserveComments: 'some'
            },
            src: '../js/boltbb.js',
            dest: '../js/boltbb.min.js'
        }
    },

    watch: {
      grunt: { files: ['Gruntfile.js'] },

      custom: {
        files: './**/*.scss',
        tasks: ['sass:custom','sass:custom_min']
      },
      dist: {
          files: './**/*.scss',
          tasks: ['sass:dist','sass:dist_min']
        }
    }
  });

  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');

  grunt.registerTask('custom', ['sass:custom','sass:custom_min']);
  grunt.registerTask('default', ['sass:dist','sass:dist_min']);
}
