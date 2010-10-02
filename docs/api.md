pipes -- a micro-framework for PHP 5.3
======================================

## METHODS

### pipes\run([ $options = _array()_ ])

Finds the matching route and calls its function. If `$options` is passed, it
will be merged into the current `options()` object.

    pipes\run();
    pipes\run(array('flush' => false));

### pipes\options([ $newOptions ]) => _pipes\Hash_

Returns the current options object, which can be used to set global settings
for pipes. This object is an instance of `pipes\Hash`. If `$newOptions` is
passed, it will replace the current options object.

    // set the views path option
    pipes\options()->views = '/path/to/views';

### pipes\php($filename [, $locals = _array()_ ]) => _boolean_

Include a PHP file, if it exists. Values in the `$locals` array will be
made available as local variables to the included PHP file. Returns `false`
if the file doesn't exist.

    // include ./foo.php
    pipes\render('foo.php');

### pipes\render($template [, $locals = _array()_ ]) => _string_

Like `php()`, but assumes the `$template` path is relative to the views folder,
and returns the captured output as a string. Returns `false` if the file doesn't
exist.

    // render ./views/foo.php
    pipes\render('foo.php');
    
    // render and pass it a variable to use
    pipes\render('foo.php', array('name' => 'Bob'));
    
    // render /other/views/folder/foo.php
    pipes\options()->views = '/other/views/folder';
    pipes\render('foo.php');


## REQUESTS

### pipes\request([ $newRequest = _null_ ]) => _pipes\Request_

Returns the current request that is being handled. If `$newRequest` is passed,
it will replace the current request object.

### new pipes\Request([ $uri = _null_, $method = _null_, $params = _null_ ])

The `Request` object is a wrapper around the normal HTTP request information
(URI, method, params, etc.). You should never need to instantiate this class
directly, as that is handled for you by `pipes\run()`.

### $request->uri

`String`. Defaults to the value of `$_SERVER['REQUEST_URI']`.

### $request->path

`String`. The directory and filename of `$request->uri`, without the extension.

    $request = new pipes\Request('/foo/bar/biff.xml');
    assert($request->path == '/foo/bar/biff');

### $request->format

`String`. The file extension of `$request->uri`, or `"html"`.

    $request = new pipes\Request('/foo/bar/biff');
    assert($request->format == 'html');
    
    $request = new pipes\Request('/foo/bar/biff.xml');
    assert($request->format == 'xml');

### $request->method

`String`. Defaults to the value of `$_SERVER['REQUEST_METHOD']`.

### $request->params

`pipes\Hash`. Defaults to a copy of the contents of `$_REQUEST`.

### $request->ajax() => _boolean_

Returns `true` if this request looks like an AJAX request. A request is
considered an AJAX request if the HTTP\_X\_REQUESTED\_WITH header is set to
XMLHttpRequest.

    if (pipes\request()->ajax()) {
        // do something ajaxy
    }


## RESPONSES

### pipes\response([ $newResponse = _null_ ]) => _pipes\Response_

Returns the current response for the request that is being handled. If
`$newResponse` is passed, it will replace the current response object.

### new pipes\Response();

Wraps the HTTP response, buffering output and headers until `flush()` is
called. `pipes` constructs a response object for you automatically, which you
can get using `pipes\response()`. You should not need to create this object
directly.

### $response->body

`Array`. Each time `$response->write()` is called, a new string is appended to
this array.

### $response->headers

`pipes\Hash`. Set of key-value pairs for all of the headers that should be
written when `$response->flush()` is called.

    pipes\response()->headers['Content-Type'] = 'application/json';

### $response->status

`Integer`. Status code for the HTTP status line. pipes will automatically fill
in the reason string, so `$response->status = 301` will result in:

    HTTP/1.1 301 Moved Permanently

### $response->length

`Integer`. Cumulative length of all the strings in the `$response->body` array.

### $response->flush()

Flushes the response to the HTTP stream. This:

1. Sends the status header, if `$response->status` has been set.
2. Sends all the headers in `$response->headers`.
3. Echoes all the strings in the `$response->body` array.

pipes automatically calls `flush()` for you, unless you tell it not to:

    pipes\options()->flush = false;  // don't automatically flush

### $response->write($string)

Adds a string to the `$response->body` array and updates `$response->length`.

    $response = pipes\response();
    $response->write('Hello, ');
    $response->write('world!');
    $response->flush();  // will echo "Hello, world!"


## ROUTES

### pipes\get($pattern, $callback) => _pipes\Route_

Define a new route for a GET request to the matching URL.

    pipes\get('/foo', function() {
        echo "This will be printed when someone visits /foo.";
    });

### pipes\put($pattern, $callback) => _pipes\Route_

Like `pipes\get()`, but for the PUT method.

### pipes\post($pattern, $callback) => _pipes\Route_

Like `pipes\get()`, but for the POST method.

### pipes\delete($pattern, $callback) => _pipes\Route_

Like `pipes\get()`, but for the DELETE method.

### pipes\any($pattern, $callback) => _pipes\Route_

Define a new route for a request to the matching URL, with any method.

### pipes\route([ $newRoute = _null_ ]) => _pipes\Route_

Returns the `pipes\Route` object for the current route. This gives you a way to
refer to the full route object from within your callback.

    pipes\get('/foo', function() {
        echo pipes\route()->rawPattern;  // => '/foo'
    });

### pipes\routes([ $newRoutes = _null_ ]) => _array_

Returns an array (by reference) containing all the defined routes.

    pipes\get('/foo', function() {
        return 'foo!';
    });

    pipes\get('/bar', function() {
        return 'bar!';
    });

    $routes = pipes\routes();  // will contain the two routes above

### pipes\redirect($url [, $status = _302_ ])

Sends the appropriate headers to redirect the user, and breaks out of any
route callbacks, ending the response immediately.

    pipes\redirect('/foo');
    
    // use a "301 Moved Permanently" status instead of "302 Found"
    pipes\redirect('/foo', 301);
    
    // send the user to an absolute url instead of a relative one
    pipes\redirect('http://google.com');

### pipes\halt([ $status, $body ])

Kills the current route and returns the response immediately. It can optionally
be passed a parameter to use as the response.

    pipes\halt();
    pipes\halt(404);              // replaces the status of the response
    pipes\halt('OHNOES!');        // replaces the body of the response
    pipes\halt(404, 'OHONOES!');  // replaces both the status and the body 

Under the hood, this method is throwing `pipes\HaltException`, which is caught
and handled internally by pipes.

### new pipes\Route($pattern, $callback)

Describes an individual route handler, as created via `pipes\get()`,
`pipes\post()`, etc. This object wraps up the patterns and callbacks associated
with the route.

    $route = new Route('/foo', function() {
        return 'foo!';
    });

    // can also pass an options hash in with a callback
    $route = new Route('/foo', new Hash(array(
        'method' => 'GET',
        'callback' => function() {
            return 'huzzah!';
        }
    });

### $route->options

`pipes\Hash`. This is an options object used internally by the route. It may
have these keys:

* `callback`: the anonymous function passed to the constructor.
* `method`: HTTP method to restrict this route to (not set for `pipes\any()`).

If a hash is passed into the constructor instead of a callback, it will be
merged into this options hash.

### $route->pattern

`String`. The compiled regexp pattern, created from `$route->rawPattern`. This
will be `null` until the first time the route is checked. 

### $route->rawPattern

`String`. The original route pattern passed into the constructor.

### $route->compile() => _string_

Translates the original `$rawPattern` into a real regexp pattern. Called
automatically as needed by `$route->matches()`.

### $route->matches($request [, &$matches = _null_ ]) => _boolean_

Returns `true` if this route matches the given request. Pattern captures will
be stored in `$matches`, if specified.

### $route->run($path, $matches) => _..._

Invokes the routes callback and returns the result. `$path` is the URL path
that this route matched, and `$matches` contains any string keys captured by
the route's regexp. You should not need to call this function directly, as
`pecs\run()` will automatically find the matching route and run it.

    $route('/hello/:name', function($params) {
        return "Hello, {$params->name}!";
    });
    echo $route->run('/hello/world', array('name' => 'hello');

