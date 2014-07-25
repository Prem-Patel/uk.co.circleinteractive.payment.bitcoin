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
    public function post($url, $params = array()) {
    $url = parse_url($url);

    if (!isset($url['port'])) {
      if ($url['scheme'] == 'http') { $url['port']=80; }
      elseif ($url['scheme'] == 'https') { $url['port']=443; }
    }
    $url['query']=isset($url['query'])?$url['query']:'';

    $url['protocol']=$url['scheme'].'://';
    $eol="\r\n";

    $headers =  "POST ".$url['protocol'].$url['host'].$url['path']." HTTP/1.0".$eol.
                "Host: ".$url['host'].$eol.
                "Referer: ".$url['protocol'].$url['host'].$url['path'].$eol.
                "Content-Type: application/x-www-form-urlencoded".$eol.
                "Content-Length: ".strlen($url['query']).$eol.
                $eol.$url['query'];
    $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30);
    if($fp) {
      fputs($fp, $headers);
      $result = '';
      while(!feof($fp)) { $result .= fgets($fp, 128); }
      fclose($fp);
      if (!$headers) {
        //removes headers
        $pattern="/^.*\r\n\r\n/s";
        $result=preg_replace($pattern,'',$result);
      }
      return $result;
    }

    }

}