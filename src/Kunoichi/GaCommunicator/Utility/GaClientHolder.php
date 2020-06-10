<?php

namespace Kunoichi\GaCommunicator\Utility;


use Kunoichi\GaCommunicator;

trait GaClientHolder {
	
	
	
	/**
	 * Get ga client.
	 *
	 * @return GaCommunicator
	 */
	public function ga() {
		return GaCommunicator::get_instance();
	}
	
}
