<?php

namespace pipes;

/// Return the current options hash
function options($newOptions=null) {
    static $options;
    if (!isset($options) || isset($newOptions))
        $options = $newOptions ?: new Hash();
    return $options;
}

/// Include a PHP file if it exists. Values in $context will be extracted
/// into the file's local context, if specified.
function php($filename, $context=array()) {
    if (file_exists($filename)) {
        extract($context instanceof Hash ? $context->toArray() : $context);
        include $filename;
        return true;
    }
    else
        return false;
}

/// Like php(), but assumes path is relative to the views folder, and
/// returns the output as a string. Returns false if no file was found.
function render($template, $locals=array()) {
    $context = new Hash(array(
        'response' => response(),
        'request' => request(),
        'route' => route(),
    ));
    $context->params = $context->request->params;
    $context->merge($locals);
    $views = options()->get('views', __DIR__.'/views');
    $filename = realpath("{$views}/{$template}");
    ob_start();
    $included = php($filename, $context);
    $output = ob_get_clean();
    return $included ? $output : false;
}

/// Find the matching route and invoke its function
function run($options=array()) {
    $options = options()->merge($options);
    $request = request();
    $response = response();
    foreach (routes() as $route) {
        if ($route->matches($request, $matches)) {
            route($route);
            $path = isset($matches['path']) ? $matches['path'] : $request->path;
            $data = $route->run($path, $matches);
            if ($data)
                $response->write($data);
            if ($options->get('flush', true))
                $response->flush();
            return $route;
        }
    }
    return null;
}
