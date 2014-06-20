<?php

/**
 * WebClient utility class
 * @method string get()
 * @method string post()
 */
class Bitcoin_Utils_WebClient {
	
	protected $timeout;

	/**
	 * Constructor
	 */
	public function __construct() {

		$client       = 'Bitcoin_Utils_WebClient_' . function_exists('curl_init') ? 'Curl' : 'FOpen';
		$this->client = new $client($this);

	}

	public function __call($method, $arguments) {
		return $this->client->$method($arguments);
	}

}