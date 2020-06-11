<?php


/**
 * Test autoloader.
 *
 * @package kunoichi
 */
class AutoloaderTest extends WP_UnitTestCase {

	public function test_register() {
		$this->assertTrue( class_exists( "Kunoichi\\GaCommunicator" ) );
	}
}
