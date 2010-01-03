<?php

// these methods override the global header() method for testing

namespace pipes;

function &headers($newHeaders=null) {
    static $headers = array();
    if (isset($newHeaders))
        $headers = $newHeaders;
    return $headers;
}

function header($header) {
    array_push(headers(), $header);
}
