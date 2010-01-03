<?php

namespace pipes;

/// Find the matching route and invoke its function
function dispatch($opts=array()) {
    $opts = new Hash($opts);
    $request = request();
    $response = response();
    foreach (routes() as $route) {
        if ($route->matches($request, $matches)) {
            $data = $route->dispatch($request, $matches);
            if ($data)
                $response->write($data);
            if ($opts->get('flush', true))
                $response->flush();
            return $route;
        }
    }
    return null;
}

/// Include a file if it exists. Values in $context will be extracted
/// into the file's local context, if specified.
function includeIfExists($filename, $context=array()) {
    if (file_exists($filename)) {
        extract($context instanceof Hash ? $context->toArray() : $context);
        include $filename;
        return true;
    }
    else
        return false;
}

/// Return the current Request object
function request($newRequest=null) {
    static $request;
    if (!isset($request) || isset($newRequest))
        $request = $newRequest ?: new Request();
    return $request;
}


/// Return the current Response object
function response($newResponse=null) {
    static $response;
    if (!isset($response) || isset($newResponse))
        $response = $newResponse ?: new Response();
    return $response;
}

/// Return the array of defined routes
function &routes($newRoutes=null) {
    static $routes = array();
    if (isset($newRoutes))
        $routes = $newRoutes;
    return $routes;
}

/// Define a new route
function route($pattern, $opts) {
    $route = new Route($pattern, $opts);
    array_push(routes(), $route);
    return $route;
}

function delete($pattern, $opts) {
    $route = route($pattern, $opts);
    $route->opts->method = 'DELETE';
    return $route;
}

function get($pattern, $opts) {
    $route = route($pattern, $opts);
    $route->opts->method = 'GET';
    return $route;
}

function post($pattern, $opts) {
    $route = route($pattern, $opts);
    $route->opts->method = 'POST';
    return $route;
}

function put($pattern, $opts) {
    $route = route($pattern, $opts);
    $route->opts->method = 'PUT';
    return $route;
}

function trace($pattern, $opts) {
    $route = route($pattern, $opts);
    $route->opts->method = 'TRACE';
    return $route;
}

class Request {
    function __construct($uri=null, $method=null, $params=null) {
        $this->server = new Hash();
        $this->server->merge($_SERVER);
        
        // set uri, method, and params from $_SERVER and $_REQUEST
        $this->uri = $uri ?: $this->server->get('REQUEST_URI', '/');
        $this->method = $method ?: $this->server->get('REQUEST_METHOD', 'GET');
        $this->method = strtoupper($this->method);
        $this->params = new Hash();
        $this->params->merge($params ?: $_REQUEST);
        
        // split the uri into route and format
        $info = pathinfo($this->uri);
        $this->route = rtrim($info['dirname'], '/').'/'.$info['filename'];
        if (!empty($info['extension']))
            $this->params->format = strtolower($info['extension']);
        else if (!$this->params->format)
            $this->params->format = 'html';
        $this->format = $this->params->format;
    }
    
    function ajax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}

class Response {
    function __construct() {
        $this->body = array();
        $this->headers = new Hash();
        $this->length = 0;
    }
    
    function flush() {
        foreach ($this->headers as $key => $value)
            header("$key: $value");
        echo implode("", $this->body);
    }
    
    function write($string) {
        $string = (string)$string;
        $this->body[] = $string;
        $this->length += strlen($string);
    }
}

class Route {
    function __construct($pattern, $opts) {
        $this->pattern = null;
        $this->rawPattern = $pattern;
        $this->opts = new Hash();
        if (is_callable($opts) && is_object($opts))
            $opts = array('callback'=>$opts);
        $this->opts->merge($opts);
        $this->opts->paths = (array)$this->opts->get('paths', $this->opts->path);
        $this->opts->delete('path');
        if (empty($this->opts->paths) && empty($this->opts->callback))
            throw new \Exception('paths or callback required for route');
    }
    
    function compile() {
        $this->pattern = preg_replace('/:(\w+)/', '(?<$1>\w+)', $this->rawPattern);
        $this->pattern = '/^'.str_replace('/', '\\/', $this->pattern.'/?').'$/';
        return $this->pattern;
    }
    
    function dispatch($path, $matches) {
        $request = request();
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                if (!isset($request->params->$key))
                    $request->params->$key = $value;
                unset($matches[$key]);
            }
        }
        $path = $request->params->get('path', $path);
        if ($this->opts->callback)
            return $this->dispatchToCallback(array_slice($matches, 1));
        else if ($this->opts->paths)
            return $this->dispatchToPaths($path);
        else
            throw new \Exception('paths or callback required for route');
    }
    
    function dispatchToCallback($args) {
        return call_user_func_array($this->opts->callback, $args);
    }
    
    function dispatchToPaths($tail) {
        $buffer = '';
        $context = new Hash(array(
            'response' => response(),
            'request' => request(),
            'route' => $this
        ));
        $included = false;
        $this->bubble = $this->opts->get('bubble', true);
        foreach ($this->opts->paths as $path) {
            $filename = "{$path}/{$tail}.php";
            $filename = realpath($filename);
            if (!$filename)
                continue;
            if (strncmp($filename, $path, strlen($path)) !== 0)
                throw new \Exception("route tried to access unsafe path");
            ob_start();
            if (includeIfExists($filename, $context))
                $included = true;
            $buffer .= ob_get_clean();
            if (!$this->bubble)
                break;
        }
        if (!$included)
            throw new \Exception("no matching files in route paths");
        return $buffer;
    }
    
    function matches($request, &$matches=null) {
        if ($this->opts->method && $request->method !== $this->opts->method)
            return false;
        if (is_null($this->pattern))
            $this->compile();
        return (boolean)preg_match($this->pattern, $request->route, $matches);
    }
}

class Hash extends \ArrayObject {
    function __construct($values=array()) {
        parent::__construct($values, \ArrayObject::ARRAY_AS_PROPS);
    }
    
    function delete($key, $defaultValue=null) {
        $value = $this->get($key, $defaultValue);
        if (isset($this->$key))
            unset($this->$key);
        return $value;
    }
    
    function get($key, $defaultValue=null) {
        return parent::offsetExists($key) ?
               parent::offsetGet($key) : $defaultValue;
    }
    
    function merge($values) {
        foreach ($values as $key => $value)
            $this[$key] = $value;
    }

    function offsetGet($key) {
        return $this->get($key);
    }
    
    function toArray() {
        return $this->getArrayCopy();
    }    
}
