<?php

/**
 * WebClient utility class
 * @method  string get()
 * @method  string post()
 * @author  andyw@circle
 * @package uk.co.circleinteractive.payment.bitcoin
 */
class Bitcoin_Utils_WebClient {
	
	public $timeout = 20;

	/**
	 * Constructor
	 */
	public function __construct() {

		$client       = 'Bitcoin_Utils_WebClient_' . /*function_exists('curl_init') ? 'Curl' :*/ 'FOpen';
		$this->client = new $client($this);

		if ($timeout = bitcoin_setting('updater_timeout'))
			$this->timeout = $timeout;

	}

	/**
	 * Route called methods to the instanced $client class
	 * @param string $method
	 * @param array  $arguments
	 */
	public function __call($method, $arguments) {
		
		return call_user_func_array(
			array($this->client, $method),
			$arguments
		);

	}

}