<?php

/**
 * Test utility functions.
 *
 */
class UtilityTest extends WP_UnitTestCase {

	/**
	 * Check test case.
	 */
	public function test_permalink() {
		foreach ( [
			'/article/%post_id%' => '/article/2348',
			'/%year%/%monthnum%/%day%/%hour%%minute%%second%/%postname%' => '/2020/10/10/121100/example-article',
			'/prefix/%category%/%author%/%postname%' => '/prefix/term-slug/user%2F/wordpress-communicate-with-google-analytics'
		] as $permalink_structure => $permalink ) {
			$regexp = \Kunoichi\GaCommunicator::get_instance()->get_permalink_filter( $permalink_structure );
			// Is permalink structure has been properly changed?
			$this->assertTrue( $regexp !== $permalink_structure );
			// Is this regexp matches?
			$this->assertRegExp( "#{$regexp}#", $permalink, sprintf( '%s not match %s', $regexp, $permalink ) );
		}
	}
}
