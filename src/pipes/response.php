<?php

namespace pipes;

/// Return the current Response object
function response($newResponse=null) {
    static $response;
    if (!isset($response) || isset($newResponse))
        $response = $newResponse ?: new Response();
    return $response;
}

/// Wraps the HTTP response, buffering output and headers until flushed.
class Response {
    function __construct() {
        $this->body = array();
        $this->headers = new Hash();
        $this->status = null;
        $this->length = 0;
    }
    
    /// Send the headers, then echo the body.
    function flush() {
        if (!is_null($this->status))
            header('.', true, $this->status);
        foreach ($this->headers as $key => $value)
            header("$key: $value");
        echo implode("", $this->body);
    }
    
    /// Append a string to the body.
    function write($string) {
        $string = (string)$string;
        $this->body[] = $string;
        $this->length += strlen($string);
    }
}
