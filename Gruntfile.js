/* jshint node:true */
module.exports = function( grunt ){
	'use strict';

	grunt.initConfig({

		// Store project settings.
		pkg: grunt.file.readJSON( 'package.json' ),

		// Generate POT files.
		makepot: {
			options: {
				type: 'wp-plugin',
				domainPath: 'languages',
				potHeaders: {
					'report-msgid-bugs-to': '',
					'language-team': 'LANGUAGE <EMAIL@ADDRESS>'
				}
			},
			dist: {
				options: {
					potFilename: 'hbl-payment-for-woocommerce.pot',
					exclude: [
						'vendor/.*'
					]
				}
			}
		},

		checktextdomain: {
			options: {
				text_domain: '<%= pkg.name %>',
				keywords: [
				'__:1,2d',
				'_e:1,2d',
				'_x:1,2c,3d',
				'esc_html__:1,2d',
				'esc_html_e:1,2d',
				'esc_html_x:1,2c,3d',
				'esc_attr__:1,2d',
				'esc_attr_e:1,2d',
				'esc_attr_x:1,2c,3d',
				'_ex:1,2c,3d',
				'_n:1,2,4d',
				'_nx:1,2,4c,5d',
				'_n_noop:1,2,3d',
				'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src: [
				'**/*.php',               // Include all files
				'!includes/libraries/**', // Exclude libraries/
				'!node_modules/**',       // Exclude node_modules/
				'!vendor/**'              // Exclude vendor/
				],
				expand: true
			}
		},

		// Compress files and folders.
		compress: {
			options: {
				archive: '<%= pkg.name %>.zip'
			},
			files: {
				src: [
					'**',
					'!.*',
					'!*.md',
					'!*.zip',
					'!.*/**',
					'!phpcs.xml',
					'!Gruntfile.js',
					'!package.json',
					'!composer.json',
					'!composer.lock',
					'!node_modules/**',
					'!package-lock.json',
				],
				dest: '<%= pkg.name %>',
				expand: true
			}
		}
	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-checktextdomain' );
	grunt.loadNpmTasks( 'grunt-contrib-compress' );
	grunt.registerTask( 'i18n', [
		'makepot',
		'compress',
		'checktextdomain'
	]);
};
