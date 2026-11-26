<?php
/**
 * Check that the PHP-Scoper build stays isolated (#50).
 *
 * In CI, the Scoper=yes job prepends tests/conflict/vendor/autoload.php
 * (unscoped Guzzle etc., like another plugin) before PHPUnit starts.
 *
 * @package kunoichi
 */
class ScoperIsolationTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		if ( ! class_exists( 'GaCommunicatorVendor\\GuzzleHttp\\Client' ) ) {
			if ( getenv( 'GA_COMMUNICATOR_EXPECT_CONFLICT' ) ) {
				// CI's Scoper=yes job: a missing scoped class is a broken build, not a reason to skip.
				$this->fail( 'Scoped Guzzle is missing from the PHP-Scoper build.' );
			}
			$this->markTestSkipped( 'Not a PHP-Scoper build.' );
		}
	}

	/**
	 * Make sure the conflicting dependencies are really loaded when CI says so.
	 */
	public function test_conflict_fixture_is_loaded() {
		if ( ! getenv( 'GA_COMMUNICATOR_EXPECT_CONFLICT' ) ) {
			$this->markTestSkipped( 'Conflict fixture is not prepended.' );
		}
		// Guzzle 7 registers this via Composer "files" autoload, the same mechanism that collided.
		$this->assertTrue( function_exists( 'GuzzleHttp\\choose_handler' ), 'Unscoped Guzzle should be loaded first.' );
	}

	/**
	 * Scoped function files must load even if an unscoped copy registered the same Composer file identifier.
	 */
	public function test_scoped_function_files_are_loaded() {
		$this->assertTrue( function_exists( 'GaCommunicatorVendor\\trigger_deprecation' ) );
	}

	/**
	 * The scoped HTTP stack used by the connectors works.
	 */
	public function test_scoped_http_stack() {
		$handler = \GaCommunicatorVendor\Google\Auth\HttpHandler\HttpHandlerFactory::build( null, false );
		$this->assertInstanceOf( \GaCommunicatorVendor\Google\Auth\HttpHandler\Guzzle7HttpHandler::class, $handler );
		$mock     = new \GaCommunicatorVendor\GuzzleHttp\Handler\MockHandler( [
			new \GaCommunicatorVendor\GuzzleHttp\Psr7\Response( 200, [], 'ok' ),
		] );
		$client   = new \GaCommunicatorVendor\GuzzleHttp\Client( [
			'handler' => \GaCommunicatorVendor\GuzzleHttp\HandlerStack::create( $mock ),
		] );
		$response = $client->get( 'https://example.com/' );
		$this->assertSame( 'ok', (string) $response->getBody() );
	}
}
