<?php

declare( strict_types=1 );

use Isolated\Symfony\Component\Finder\Finder;

// You can do your own things here, e.g. collecting symbols to expose dynamically
// or files to exclude.
// However beware that this file is executed by PHP-Scoper, hence if you are using
// the PHAR it will be loaded by the PHAR. So it is highly recommended to avoid
// to auto-load any code here: it can result in a conflict or even corrupt
// the PHP-Scoper analysis.

return [
	// see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#prefix
	'prefix'                  => 'GaCommunicatorVendor',

	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#finders-and-paths
	'finders'                 => [
		Finder::create()->files()->in( 'src' ),
		Finder::create()
			  ->files()
			  ->ignoreVCS( true )
			  ->notName( '/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/' )
			  ->exclude( [
				  'doc',
				  'test',
				  'test_old',
				  'tests',
				  'Tests',
				  'vendor-bin',
			  ] )
			  ->in( 'vendor' ),
		Finder::create()->append( [
			'composer.json',
		] ),
	],

	// List of excluded files, i.e. files for which the content will be left untouched.
	// Paths are relative to the configuration file unless if they are already absolute
	//
	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
	'exclude-files'           => [
		'src/a-whitelisted-file.php',
	],

	// When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
	// original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
	// support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
	// heart contents.
	//
	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
	'patchers'                => [
		static function ( string $filePath, string $prefix, string $contents ): string {
			// Change the contents here.

			return $contents;
		},
	],

	// For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#excluded-symbols
	'exclude-namespaces'      => [
		'/^cli/',
		'/^Composer/',
		'/^DeepCopy/',
		'/^Doctrine/',
		'/^Kunoichi/',
		'/^PharIo/',
		'/^PHP_CodeSniffer/',
		'/^PHPCompatibility/',
		'/^PHPCSStandards/',
		'/^PHPUnit/',
		'/^Yoast/',
		'/^SebastianBergmann/',
		'/^TheSeer/',
		'/^WP_/',
	],
	'exclude-classes'         => [
		'/^WP_/',
		'/^Composer/',
		'/^PHPUnit/',
	],
	'exclude-functions'       => [
		// 'mb_str_split',
	],
	'exclude-constants'       => [
		// 'STDIN',
	],

	// List of symbols to expose.
	//
	// For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#exposed-symbols
	'expose-global-constants' => true,
	'expose-global-classes'   => false,
	'expose-global-functions' => false,
	'expose-namespaces'       => [
	],
	'expose-classes'          => [],
	'expose-functions'        => [],
	'expose-constants'        => [],
];
