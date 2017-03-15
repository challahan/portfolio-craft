
module.exports = function(grunt) {

  // Configuration
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    sass: {
      dist: {
        options: {
          style: 'compressed'
        },
        files: {
        'css/style.css': '_sass/main.scss',
        'css/resume.css': '_sass/resume.scss'
        }
      }
    },
    criticalcss: {
      custom: {
        options: {
          url: "http://chris.web",
                width: 1200,
                height: 900,
                outputfile: "css/critical.css",
                filename: "css/style.css", // Using path.resolve( path.join( ... ) ) is a good idea here
                buffer: 800*1024,
                ignoreConsole: false
        }
      }
    },
    uglify: {
      my_target: {
        files: {
          'scripts/min/scripts-min.js': ['scripts/priority.js','scripts/analytics.js','scripts/scripts.js'],
          'scripts/min/loadcss-min.js': ['scripts/loadcss.js'],
          'scripts/min/loadcss-resume-min.js': ['scripts/loadcss-resume.js']
        }
      }
    },
    autoprefixer: {
      your_target: {
        files: {
          'css/style.css': 'css/style.css'
        }
      },
    },
    shell: {
      patternlab: {
        command: "php lab/core/console -gp"
      }
    },
    watch: {
      css: {
				files: '**/_sass/*.scss',
				tasks: ['sass', 'autoprefixer'],
        options: {
          livereload: true,
        },
			},
      js: {
				files: '**/scripts/*.js',
				tasks: ['uglify'],
        options: {
          livereload: true,
        },
			},
      html: {
        files: ['lab/source/_patterns/**/*.mustache', 'lab/source/_patterns/**/*.md',  'lab/source/**/*.json'],
        tasks: ['shell:patternlab'],
        options: {
          spawn: false,
          livereload: true
        }
      }
    }
  });

  // Plugins
  grunt.loadNpmTasks('grunt-autoprefixer');
  grunt.loadNpmTasks('grunt-contrib-imagemin');
  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-criticalcss');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-shell');

  // Tasks
  grunt.registerTask('default', ['watch']);
  grunt.registerTask('critical', ['criticalcss']);
};
