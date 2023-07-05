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
		$this->assertRegExp( '#https?://[^/]+/hoge#', $url );
		// Change url to subdomain installation.
		update_option( 'home', 'https://example.org/wp' );
		update_option( 'site_url', 'https://example.org/wp' );
		$url = \Kunoichi\GaCommunicator::get_instance()->path_to_url( '/wp/hoge' );
		$this->assertRegExp( '#https?://[^/]+/wp/hoge#', $url );
	}

	/**
	 * Test merge function.
	 *
	 * @return void
	 */
	public function test_merge() {
		$ga = \Kunoichi\GaCommunicator::get_instance();
		$default_json = $ga->ga4_default_json();
		$override_json = [
			'dimensions' => [
				[
					'name' => 'date',
				],
			],
			'orderBys' => [
				[
					'dimension' => [
						'dimensionName' => 'date',
						'orderType'     => 'NUMERIC',
					]
				],
			],
			'limit' => 100,
		];
		$merged = array_merge( $default_json, $override_json );
		$this->assertEquals( 1, count( $merged['dimensions'] ), 'Dimension should be 1. Not 2.' );
		$this->assertEquals( 1, count( $merged['metrics'] ), 'Metric still exists.' );
		$this->assertEquals( 1, count( $merged['orderBys'] ), 'Orderby should have 1 length.' );
		$this->assertFalse( isset( $merged['orderBys'][0]['metric'] ), 'OrderBy is overridden.' );
	}
}
