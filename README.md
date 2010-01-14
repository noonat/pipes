**pipes** is a micro-framework for PHP, inspired by [Sinatra]. It uses the new
features in PHP 5.3 to create a lightweight syntax:

    require 'pipes.php';

    pipes\get('/hi', function() {
        return 'Hello, world!';
    });

    pipes\run();

The first parameter to `pipes\get()` is the path that the route should handle,
and the second is the function to call when the route is matched.

The string returned by the function `echo`-ed as the response. You can also just
`echo` directly, if desired.

`pipes\get()` matches the GET HTTP method. You can also use `pipes\post()`,
`pipes\put()`, and `pipes\delete()`. To specify a route that matches any HTTP
methods, use `pipes\route()`. The first matching route is used, in the order
that the routes were defined.

## Named path parameters

The first parameter to your route callback is a `$params` hash. Named captures
are copied into this object:

    pipes\get('/hi/:name', function($params) {
        return "Hello, {$params->name}!";
    });

## Regular expression path parameters

Regular expression patterns can also be used in the route. An array of any
unnamed captures will be copied into `$params->captures`:

    pipes\get('/archive/([0-9]{4})/([0-9]{2})/([0-9]{2})', function($params) {
        list($year, $month, $day) = $params->captures;
        return "Archive for {$year}-{$month}-{$day}";
    });

## More to come...

[Sinatra]: http://www.sinatrarb.com
