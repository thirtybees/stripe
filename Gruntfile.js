module.exports = function(grunt) {

    grunt.initConfig({
        compress: {
            main: {
                options: {
                    archive: 'stripe.zip'
                },
                files: [
                    {src: ['classes/**'], dest: 'stripe/', filter: 'isFile'},
                    {src: ['controllers/**'], dest: 'stripe/', filter: 'isFile'},
                    {src: ['sql/**'], dest: 'stripe/', filter: 'isFile'},
                    {src: ['translations/**'], dest: 'stripe/', filter: 'isFile'},
                    {src: ['vendor/**'], dest: 'stripe/', filter: 'isFile'},
                    {src: ['views/**'], dest: 'stripe/', filter: 'isFile'},
                    {src: ['docs/**'], dest: 'stripe/', filter: 'isFile'},
                    {src: ['override/**'], dest: 'stripe/', filter: 'isFile'},
                    {src: ['logs/**'], dest: 'stripe/', filter: 'isFile'},
                    {src: ['upgrade/**'], dest: 'stripe/', filter: 'isFile'},
                    {src: ['optionaloverride/**'], dest: 'stripe/', filter: 'isFile'},
                    {src: ['oldoverride/**'], dest: 'stripe/', filter: 'isFile'},
                    {src: ['lib/**'], dest: 'stripe/', filter: 'isFile'},
                    {src: ['defaultoverride/**'], dest: 'stripe/', filter: 'isFile'},
                    {src: 'config.xml', dest: 'stripe/'},
                    {src: 'index.php', dest: 'stripe/'},
                    {src: 'stripe.php', dest: 'stripe/'},
                    {src: 'cloudunlock.php', dest: 'stripe/'},
                    {src: 'logo.png', dest: 'stripe/'},
                    {src: 'logo.gif', dest: 'stripe/'},
                    {src: 'LICENSE.md', dest: 'stripe/'},
                    {src: 'CONTRIBUTORS.md', dest: 'stripe/'},
                    {src: 'README.md', dest: 'stripe/'}
                ]
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-compress');

    grunt.registerTask('default', ['compress']);
};
