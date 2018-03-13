module.exports = function(grunt) {

    'use strict';

    var requirejs   = grunt.config('requirejs') || {};
    var clean       = grunt.config('clean') || {};
    var copy        = grunt.config('copy') || {};

    var root        = grunt.option('root');
    var libs        = grunt.option('mainlibs');
    var ext         = require(root + '/tao/views/build/tasks/helpers/extensions')(grunt, root);
    var out         = 'output';

    /**
     * Remove bundled and bundling files
     */
    clean.taosyncbundle = [out];

    /**
     * Compile tao files into a bundle
     */
    requirejs.taosyncbundle = {
        options: {
            baseUrl : '../js',
            dir : out,
            mainConfigFile : './config/requirejs.build.js',
            paths : {
                'taoSync' : root + '/taoSync/views/js',
                'taoTaskQueue' : root + '/taoTaskQueue/views/js'
            },
            modules : [{
                name: 'taoSync/controller/routes',
                include : ext.getExtensionsControllers(['taoSync']),
                exclude : [].concat(libs)
            }]
        }
    };

    /**
     * copy the bundles to the right place
     */
    copy.taosyncbundle = {
        files: [
            { src: [out + '/taoSync/controller/routes.js'],  dest: root + '/taoSync/views/js/controllers.min.js' },
            { src: [out + '/taoSync/controller/routes.js.map'],  dest: root + '/taoSync/views/js/controllers.min.js.map' }
        ]
    };

    grunt.config('clean', clean);
    grunt.config('requirejs', requirejs);
    grunt.config('copy', copy);

    // bundle task
    grunt.registerTask('taosyncbundle', ['clean:taosyncbundle', 'requirejs:taosyncbundle', 'copy:taosyncbundle']);
};
