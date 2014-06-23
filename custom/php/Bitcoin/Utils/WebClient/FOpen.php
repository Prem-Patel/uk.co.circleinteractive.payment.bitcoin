<?php

class Bitcoin_Utils_WebClient_FOpen implements Bitcoin_Utils_WebClient_Interface {

    public $controller;

    /**
     * Constructor
     * @param Bitcoin_Utils_WebClient $controller  reference to calling class so we can access properties
     */
    public function __construct(Bitcoin_Utils_WebClient $controller) {
        $this->controller = $controller;
        @ini_set('allow_url_fopen', 1);
    }

    /**
     * Destructor
     */
    public function __destruct() {
        @ini_restore('allow_url_fopen');
    }

    /**
     * Perform GET request
     * @param  string $url    url to send request to
     * @param  array  $params optional querystring parameters as key/value pairs
     * @return string         response body
     * @access public
     */
    public function get($url, $params = array()) {
        
        if ($params)
            $url .= '?' . implode('&', $params);

        return file_get_contents($url, false,
            stream_context_create(array(
                'http' => array(
                    'timeout' => $this->controller->timeout
                )
            ))
        );

    }

    /**
     * Perform POST request
     * @param  string $url    url to send request to
     * @param  array  $params querystring parameters as key/value pairs
     * @return string         response body
     * @access public
     */
    public function post($url, $params) {

    }

}