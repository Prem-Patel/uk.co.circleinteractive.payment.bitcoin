<?php

interface Bitcoin_Utils_WebClient_Interface {

    /**
     * Constructor
     * @param Bitcoin_Utils_WebClient $controller  reference to calling class so we can access properties
     */
	public function __construct(Bitcoin_Utils_WebClient $controller);

	/**
     * Perform GET request
     * @param  string $url    url to send request to
     * @param  array  $params optional querystring parameters as key/value pairs
     * @return string         response body
     * @access public
     */
	public function get($url);

	/**
     * Perform POST request
     * @param  string $url    url to send request to
     * @param  array  $params querystring parameters as key/value pairs
     * @return string         response body
     * @access public
     */
	public function post($url);

}