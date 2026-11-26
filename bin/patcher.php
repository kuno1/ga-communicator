<?php
/**
 * Post-process vendor-prefixed/ after php-scoper and `composer dump-autoload`.
 *
 * Composer loads "files" autoloads (global function files) only once per
 * request, keyed by md5( package name . ':' . path ) in
 * $GLOBALS['__composer_autoload_files']. php-scoper keeps package names, so the
 * scoped copy gets the same keys as an unscoped copy bundled in another plugin.
 * If that plugin loads first, Composer skips our scoped function files and
 * calls such as GaCommunicatorVendor\GuzzleHttp\choose_handler() fail (#50).
 *
 * Re-key our file identifiers with the scoper prefix so they never collide.
 */

$root   = dirname( __DIR__ ) . '/vendor-prefixed/vendor/composer/';
$failed = false;

// scoper.inc.php needs php-scoper's bundled Finder, so read the prefix as text.
if ( ! preg_match( "/'prefix'\s*=>\s*'([^']+)'/", file_get_contents( dirname( __DIR__ ) . '/scoper.inc.php' ), $matches ) ) {
	echo 'Could not find prefix in scoper.inc.php' . PHP_EOL;
	exit( 1 );
}
$prefix = $matches[1];

foreach ( [ 'autoload_static.php', 'autoload_files.php' ] as $file ) {
	$path = $root . $file;
	if ( ! file_exists( $path ) ) {
		// autoload_files.php does not exist when no package has "files" autoloads.
		echo "Skipped (missing): {$path}" . PHP_EOL;
		continue;
	}
	$count   = 0;
	$content = preg_replace_callback( "/'([0-9a-f]{32})'(\s*=>)/", function ( $matches ) use ( $prefix ) {
		return "'" . md5( $prefix . $matches[1] ) . "'" . $matches[2];
	}, file_get_contents( $path ), -1, $count );
	if ( null === $content ) {
		echo "Failed to patch: {$path}" . PHP_EOL;
		$failed = true;
		continue;
	}
	if ( strlen( $content ) !== file_put_contents( $path, $content ) ) {
		echo "Failed to write: {$path}" . PHP_EOL;
		$failed = true;
		continue;
	}
	echo "Re-keyed {$count} file autoload(s): {$path}" . PHP_EOL;
}

exit( $failed ? 1 : 0 );
