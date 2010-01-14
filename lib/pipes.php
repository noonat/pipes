<?php

namespace pipes;

/// Find the matching route and invoke its function
function run($opts=array()) {
    $opts = new Hash($opts);
    $request = request();
    $response = response();
    foreach (routes() as $route) {
        if ($route->matches($request, $matches)) {
            $path = isset($matches['path']) ? $matches['path'] : $request->path;
            $data = $route->run($path, $matches);
            if ($data)
                $response->write($data);
            if ($opts->get('flush', true))
                $response->flush();
            return $route;
        }
    }
    return null;
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

/// Define a new route, limited to the DELETE HTTP method.
function delete($pattern, $opts) {
    $route = route($pattern, $opts);
    $route->opts->method = 'DELETE';
    return $route;
}

/// Define a new route, limited to the GET HTTP method.
function get($pattern, $opts) {
    $route = route($pattern, $opts);
    $route->opts->method = 'GET';
    return $route;
}

/// Define a new route, limited to the POST HTTP method.
function post($pattern, $opts) {
    $route = route($pattern, $opts);
    $route->opts->method = 'POST';
    return $route;
}

/// Define a new route, limited to the PUT HTTP method.
function put($pattern, $opts) {
    $route = route($pattern, $opts);
    $route->opts->method = 'PUT';
    return $route;
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

/// Wraps the HTTP request information (URI, method, params).
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

/// Wraps the HTTP response, buffering output and headers until flushed.
class Response {
    function __construct() {
        $this->body = array();
        $this->headers = new Hash();
        $this->length = 0;
    }
    
    /// Send the headers, then echo the body.
    function flush() {
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

/// Describes an individual route handler.
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
    
    /// Compiles the route pattern into an actual regex.
    function compile() {
        $this->pattern = preg_replace('/:(\w+)/', '(?<$1>\w+)', $this->rawPattern);
        $this->pattern = '/^'.str_replace('/', '\\/', $this->pattern.'/?').'$/';
        return $this->pattern;
    }
    
    /// Returns true if this route matches the given request.
    function matches($request, &$matches=null) {
        if ($this->opts->method && $request->method !== $this->opts->method)
            return false;
        if (is_null($this->pattern))
            $this->compile();
        return (boolean)preg_match($this->pattern, $request->path, $matches);
    }
    
    /// Executes the appropriate handler, either invoking a callback function or
    /// including a PHP file.
    function run($path, $matches) {
        $request = request();
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                if (!isset($request->params->$key))
                    $request->params->$key = $value;
                unset($matches[$key]);
            }
        }
        $request->params->captures = array_slice($matches, 1);
        if ($this->opts->callback)
            return $this->runCallback(array($request->params));
        else if ($this->opts->paths)
            return $this->runPaths($path);
        else
            throw new \Exception('paths or callback required for route');
    }
    
    function runCallback($args) {
        return call_user_func_array($this->opts->callback, $args);
    }
    
    /// Iterates over the paths supplied in the route options and includes any
    /// PHP files that match the request route path.
    function runPaths($tail) {
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
            if (!$filename) {
                continue;
            }
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
