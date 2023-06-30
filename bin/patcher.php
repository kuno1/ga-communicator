<?php
/**
 * Add patch for php-scoper.
 */

foreach ( [
	'vendor/google/auth/src/HttpHandler/HttpHandlerFactory.php' => function ( string $content ) {
		return preg_replace( '/(GuzzleHttp.*ClientInterface::)(MAJOR_VERSION|VERSION)/u', 'GaCommunicatorVendor\\\\' . '$1$2', $content );
	},
	'vendor/guzzlehttp/guzzle/src/HandlerStack.php' => function ( string $content ) {
		return preg_replace( '/(\$handler \?: )(choose_handler\(\))/u', '$1\\GaCommunicatorVendor\\GuzzleHttp\\\\$2', $content );
	},
] as $path => $callable ) {
	$path = dirname( __DIR__ ) . '/vendor-prefixed/' . ltrim( $path, '' );
	if ( ! file_exists( $path ) ) {
		echo "File missing: {$path}" . PHP_EOL;
		continue;
	}
	$content = file_get_contents( $path );
	file_put_contents( $path, $callable( $content ) );
	echo "File Updated: {$path}" . PHP_EOL;
}
