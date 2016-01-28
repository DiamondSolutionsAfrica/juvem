module.exports = function (grunt) {
    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        resourcesPath: 'app/Resources',

        clean: {
            dep: ['app/dep/*'],
            css: ['web/css/*'],
            font: ['web/font/*'],
            js: ['web/js/*']
        },

        copy: {
            font: {
                files: [
                    {
                        expand: true,
                        flatten: true,
                        cwd: 'node_modules/bootstrap-sass/assets/fonts/bootstrap/',
                        src: '**',
                        dest: 'web/font/',
                        filter: 'isFile'
                    }
                ]
            }
        },

        concat: {
            options: {
                sourceMap: true,
                separator: ';'
            },
            distJs: {
                src: [
                    '<%= resourcesPath %>/js/jquery-1.12.0.js',
                    'node_modules/bootstrap-table/src/bootstrap-table.js',
                    'node_modules/bootstrap-table/src/locale/bootstrap-table-de-DE.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/alert.js',
                    '<%= resourcesPath %>/js/jquery.bsAlerts.min.js',
                    '<%= resourcesPath %>/js/main.js'
                ],
                dest: 'web/js/all.js'
            },
            distCss: {
                src: [
                    'app/cache/dep/all-sass.css',
                    'node_modules/bootstrap-table/src/bootstrap-table.css'
                ],
                dest: 'web/css/all.css'
            }
        },

        sass: {
            dist: {
                options: {
                    style: 'expanded'
                },
                files: {
                    'app/cache/dep/all-sass.css': '<%= resourcesPath %>/scss/main.scss'
                }
            }
        },

        jshint: {
            all: ['Gruntfile.js', '<%= resourcesPath %>/js/**/*.js']
        },

        uglify: {
            options: {
                sourceMap: true,
                mangle: {
                    except: ['jQuery', 'Backbone']
                }
            },
            js: {
                files: {
                    'web/js/all.min.js': ['web/js/all.js']
                }
            }
        },

        cssmin: {
            options: {
                shorthandCompacting: false,
                roundingPrecision: -1,
                sourceMap: true
            },
            target: {
                files: {
                    'web/css/all.min.css': ['web/css/all.css']
                }
            }
        },

        watch: {
            js: {
                files: '<%= resourcesPath %>/js/**/*.js',
                tasks: ['clean:js', 'concat:distJs', 'uglify'],
                options: {
                    livereload: true
                }
            },
            sass: {
                files: '<%= resourcesPath %>/scss/**/*.scss',
                tasks: ['sass', 'concat:distCss', 'cssmin'],
                options: {
                    livereload: true
                }
            }
        }

    });

    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-watch');


    grunt.registerTask('default', ['watch']);
    grunt.registerTask(
        'deploy',
        [
            'clean:font', 'copy',
            'clean:dep', 'clean:css', 'sass', 'concat:distCss', 'cssmin',
            'clean:js', 'concat:distJs', 'uglify',
            'clean:dep'
        ]
    );
};