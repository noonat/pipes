<?php

namespace pipes;

class HaltException extends \Exception {};

function halt() {
    throw new HaltException();
}

/// Return the route that matches the current request
function route($newRoute=null) {
    static $route;
    if (isset($newRoute))
        $route = $newRoute;
    return $route;
}

/// Return the array of defined routes
function &routes($newRoutes=null) {
    static $routes = array();
    if (isset($newRoutes))
        $routes = $newRoutes;
    return $routes;
}

/// Define a new route for any HTTP method
function any($pattern, $options) {
    $route = new Route($pattern, $options);
    array_push(routes(), $route);
    return $route;
}

/// Define a new route, limited to the DELETE HTTP method.
function delete($pattern, $options) {
    $route = any($pattern, $options);
    $route->options->method = 'DELETE';
    return $route;
}

/// Define a new route, limited to the GET HTTP method.
function get($pattern, $options) {
    $route = any($pattern, $options);
    $route->options->method = 'GET';
    return $route;
}

/// Define a new route, limited to the POST HTTP method.
function post($pattern, $options) {
    $route = any($pattern, $options);
    $route->options->method = 'POST';
    return $route;
}

/// Define a new route, limited to the PUT HTTP method.
function put($pattern, $options) {
    $route = any($pattern, $options);
    $route->options->method = 'PUT';
    return $route;
}

/// Describes an individual route handler.
class Route {
    function __construct($pattern, $options) {
        $this->pattern = null;
        $this->rawPattern = $pattern;
        $this->options = new Hash();
        if (is_callable($options) && is_object($options))
            $options = array('callback'=>$options);
        $this->options->merge($options);
        $this->options->paths = (array)$this->options->get('paths', $this->options->path);
        $this->options->delete('path');
        if (empty($this->options->paths) && empty($this->options->callback))
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
        if ($this->options->method && $request->method !== $this->options->method)
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
        try {
            $request->params->captures = array_slice($matches, 1);
            if ($this->options->callback)
                return $this->runCallback(array($request->params, $path));
            else if ($this->options->paths)
                return $this->runPaths($path);
            else
                throw new \Exception('paths or callback required for route');
        } catch (HaltException $err) {
            // pass
        }
    }
    
    function runCallback($args) {
        return call_user_func_array($this->options->callback, $args);
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
        $context->params = $context->request->params;
        $included = false;
        $this->bubble = $this->options->get('bubble', true);
        foreach ($this->options->paths as $path) {
            $filename = "{$path}/{$tail}.php";
            $filename = realpath($filename);
            if (!$filename) {
                continue;
            }
            if (strncmp($filename, $path, strlen($path)) !== 0)
                throw new \Exception("route tried to access unsafe path");
            ob_start();
            if (php($filename, $context))
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
