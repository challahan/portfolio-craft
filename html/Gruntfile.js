
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
        'css/resume.css': '_sass/resume.scss',
        '../craft/templates/_includes/critical-home.css': '../craft/templates/_includes/critical-home.css',
        '../craft/templates/_includes/critical-work.css': '../craft/templates/_includes/critical-work.css',
        '../craft/templates/_includes/critical-project.css': '../craft/templates/_includes/critical-project.css'
        }
      }
    },
    criticalcss: {
      home: {
        options: {
          url: "http://chris.web",
                width: 1200,
                height: 900,
                outputfile: "../craft/templates/_includes/critical-home.css",
                filename: "css/style.css", // Using path.resolve( path.join( ... ) ) is a good idea here
                buffer: 800*1024,
                ignoreConsole: true
        }
      },
      projects: {
        options: {
          url: "http://chris.web/work",
                width: 1200,
                height: 900,
                outputfile: "../craft/templates/_includes/critical-work.css",
                filename: "css/style.css", // Using path.resolve( path.join( ... ) ) is a good idea here
                buffer: 800*1024,
                ignoreConsole: true
        }
      },
      projectsingle: {
        options: {
          url: "http://chris.web/projects/new-project-test",
                width: 1200,
                height: 900,
                outputfile: "../craft/templates/_includes/critical-project.css",
                filename: "css/style.css", // Using path.resolve( path.join( ... ) ) is a good idea here
                buffer: 800*1024,
                ignoreConsole: true
        }
      }
    },
    uglify: {
      my_target: {
        files: {
          'scripts/min/scripts-min.js': ['scripts/priority.js','scripts/analytics.js', 'scripts/lightgallery.js', 'scripts/lg-zoom.js', 'scripts/scripts.js'],
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
