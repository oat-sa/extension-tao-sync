module.exports = function (grunt) {
    'use strict';

    var sass    = grunt.config('sass') || {};
    var watch   = grunt.config('watch') || {};
    var notify  = grunt.config('notify') || {};
    var root    = grunt.option('root') + '/taoSync/views/';

    sass.taosync = {
        options : {},
        files : {}
    };

    sass.taosync.files[root + 'css/styles.css'] = root + 'scss/styles.scss';

    watch.taosyncsass = {
        files : [
            root + 'scss/**/*.scss',
        ],
        tasks : ['sass:taosync', 'notify:taosyncsass'],
        options : {
            debounceDelay : 1000
        }
    };

    notify.taosyncsass = {
        options: {
            title: 'Grunt SASS',
            message: 'SASS files compiled to CSS'
        }
    };

    grunt.config('sass', sass);
    grunt.config('watch', watch);
    grunt.config('notify', notify);

    grunt.registerTask('taosyncsass', ['sass:taosync']);
};
