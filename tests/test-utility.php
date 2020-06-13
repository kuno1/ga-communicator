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
	
	/**
	 * Check path to post id.
	 */
	public function test_path_to_post_id() {
		$url = \Kunoichi\GaCommunicator::get_instance()->path_to_url( 'hoge' );
		$this->assertEquals( 'http://example.org/hoge', $url );
		// Change url to subdomain installation.
		update_option( 'home', 'https://example.org/wp' );
		update_option( 'site_url', 'https://example.org/wp' );
		$url = \Kunoichi\GaCommunicator::get_instance()->path_to_url( '/wp/hoge' );
		$this->assertEquals( 'https://example.org/wp/hoge', $url );
	}
}
