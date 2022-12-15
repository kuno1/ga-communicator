<?php


/**
 * Test version conpatibility.
 *
 * @package kunoichi
 */
class VersionComnpatibilityTest extends WP_UnitTestCase {

	public function test_register() {
		// Raise error?
		$ga = \Kunoichi\GaCommunicator::get_instance();
		$should = [ 'a', '20', 10 ];
		$actual = $ga->ga4_parse_report_result( [
			'dimensionValues' => [
				[
					'value' => 'a',
				],
				[
					'value' => '20',
				],
			],
			'metricValues' => [
				[
					'value' => 10,
				]
			],
		] );
		$this->assertEqualSetsWithIndex( $should, $actual );
	}
}
