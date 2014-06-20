<?php

class Bitcoin_Utils_WebClient_Curl implements Bitcoin_Utils_WebClient_Interface {

    protected $controller;

    /**
     * Constructor
     * @param Bitcoin_Utils_WebClient $controller  reference to calling class so we can access properties
     */
    public function __construct(Bitcoin_Utils_WebClient $controller) {
        $this->controller = $controller;
    }

    /**
     * Perform GET request
     * @param  string $url    url to send request to 
     * @param  array  $params optional querystring parameters as key/value pairs
     * @return string         response body
     * @access public
     */
    public function get($url, $params = array()) {

    }
    /**
     * Perform POST request
     * @param  string $url    url to send request to
     * @param  array  $params querystring parameters as key/value pairs
     * @return string         response body
     * @access public
     */
    public function post($url) {
        
    }

}