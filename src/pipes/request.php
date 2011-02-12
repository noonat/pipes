<?php

namespace pipes;

/// Return the current Request object
function request($newRequest=null) {
    static $request;
    if (!isset($request) || isset($newRequest))
        $request = $newRequest ?: new Request();
    return $request;
}

/// Wraps the HTTP request information (URI, method, params).
class Request {
    function __construct($uri=null, $method=null, $params=null) {
        $this->server = new Hash();
        $this->server->merge($_SERVER);
        
        // set uri, method, and params from $_SERVER and $_REQUEST
        $this->uri = $uri ?: $this->server->get('REQUEST_URI', '/');
        $this->params = new Hash();
        $this->params->merge($params ?: $_REQUEST);
        $this->method = $method ?: $this->server->get('REQUEST_METHOD', 'GET');
        $this->method = strtoupper($this->method);
        if ($this->method === 'POST' && options()->get('requestMethodOverride', true)) {
            $method = strtoupper($this->params->get('_method', ''));
            if (in_array($method, array('DELETE', 'GET', 'PUT', 'POST')))
                $this->method = $method;
        }
        
        // strip basePath from uri, if it is set and matches
        $basePath = rtrim(options()->get('requestBasePath', ''), '/');
        if ($basePath && strpos($this->uri, $basePath) === 0)
            $this->uri = substr($this->uri, strlen($basePath)) ?: '/';
        
        // split the uri into route and format
        $info = pathinfo($this->uri);
        $this->path = rtrim($info['dirname'], '/').'/'.$info['filename'];
        if (!empty($info['extension']))
            $this->params->format = strtolower($info['extension']);
        else if (!$this->params->format)
            $this->params->format = 'html';
        $this->format = $this->params->format;
    }
    
    /// Returns true if this is an AJAX request.
    function ajax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}
