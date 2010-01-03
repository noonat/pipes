<?php

describe("routes()", function() {
    it("should return the array of routes", function() {
        $routes = pipes\routes();
        expect($routes)->to_be_type('array')->and_to_be_empty();
    });
    
    it("should allow you to override the current array", function() {
        $oldRoutes = pipes\routes();
        $newRoutes = pipes\routes(array('foo', 'bar'));
        expect($oldRoutes)->to_be_empty();
        expect($newRoutes)->to_be(array('foo', 'bar'));
        expect(pipes\routes())->to_be($newRoutes);
        pipes\routes($oldRoutes);
    });
    
    it("should return the array by reference", function() {
        $routes =& pipes\routes();
        $routes[] = 'foo';
        $routes[] = 'bar';
        expect(pipes\routes())->to_be(array('foo', 'bar'));
        pipes\routes(array());
    });
});

describe("route()", function() {
    before_each(function() {
        pipes\routes(array());
    });
    
    it("should create a new Route object and return it", function() {
        $route = pipes\route('/foo/bar', function() {}); 
        expect($route)->to_be_a('pipes\Route');
    });
    
    it("should add the new route to the list of routes", function() {
        expect(pipes\routes())->to_be_empty();
        $route = pipes\route('/foo/bar', function() {});
        expect(pipes\routes())->to_have_count(1);
        expect(pipes\routes())->to_be(array($route));
    });
});

describe("delete()", function() {
    it("should create a route for the DELETE method", function() {
        pipes\routes(array());
        $route = pipes\delete('/foo/bar', function() {}); 
        expect($route)->to_be_a('pipes\Route');
        expect($route->opts->method)->to_be('DELETE');
    });
});

describe("get()", function() {
    it("should create a route for the GET method", function() {
        pipes\routes(array());
        $route = pipes\get('/foo/bar', function() {}); 
        expect($route)->to_be_a('pipes\Route');
        expect($route->opts->method)->to_be('GET');
        expect(pipes\routes())->to_be(array($route));
    });
});

describe("post()", function() {
    it("should create a route for the POST method", function() {
        pipes\routes(array());
        $route = pipes\post('/foo/bar', function() {}); 
        expect($route)->to_be_a('pipes\Route');
        expect($route->opts->method)->to_be('POST');
        expect(pipes\routes())->to_be(array($route));
    });
});

describe("put()", function() {
    it("should create a route for the PUT method", function() {
        pipes\routes(array());
        $route = pipes\put('/foo/bar', function() {}); 
        expect($route)->to_be_a('pipes\Route');
        expect($route->opts->method)->to_be('PUT');
        expect(pipes\routes())->to_be(array($route));
    });
});

describe("trace()", function() {
    it("should create a route for the TRACE method", function() {
        pipes\routes(array());
        $route = pipes\trace('/foo/bar', function() {}); 
        expect($route)->to_be_a('pipes\Route');
        expect($route->opts->method)->to_be('TRACE');
        expect(pipes\routes())->to_be(array($route));
    });
});

describe("Route", function() {
    it("should accept a pattern as the first parameter", function() {
        $route = new pipes\Route('/foo/:bar', function(){});
        expect($route->rawPattern)->to_be('/foo/:bar');
    });
    
    it("should not compile the pattern until used", function() {
        $route = new pipes\Route('/foo/:bar', function(){});
        expect($route->pattern)->to_be_null();
    });
    
    it("should accept an array or hash of options as the second parameter", function() {
        $callback = function(){};
        
        // try with an array
        $opts = array('foo'=>'bar', 'callback'=>$callback);
        $route = new pipes\Route('/foo/:bar', $opts);
        expect($route->opts)->to_be_a('pipes\Hash');
        expect($route->opts)->to_have_count(3);
        expect($route->opts->foo)->to_be('bar');
        expect($route->opts->paths)->to_be(array());
        expect($route->opts->callback)->to_be($callback);
        
        // try it again with a hash
        $opts = new pipes\Hash(array('foo'=>'bar', 'callback'=>$callback));
        $route = new pipes\Route('/foo/:bar', $opts);
        expect($route->opts)->to_be_a('pipes\Hash');
        expect($route->opts)->to_have_count(3);
        expect($route->opts->foo)->to_be('bar');
        expect($route->opts->paths)->to_be(array());
        expect($route->opts->callback)->to_be($callback);
    });
    
    it("should accept a callback instead of options as the second parameter", function() {
        $callback = function(){};
        $route = new pipes\Route('/foo/:bar', $callback);
        expect($route->opts->callback)->to_be($callback);
    });
    
    it("should allow the paths option to be a string or array", function() {
        $route = new pipes\Route('/foo/:bar', array('path'=>'xyzzy'));
        expect($route->opts->paths)->to_be(array('xyzzy'));
        $route = new pipes\Route('/foo/:bar', array('paths'=>array('xyzzy')));
        expect($route->opts->paths)->to_be(array('xyzzy'));
        $route = new pipes\Route('/foo/:bar', array('paths'=>array('xyzzy', 'biff')));
        expect($route->opts->paths)->to_be(array('xyzzy', 'biff'));
    });
    
    it("should require a callback or paths option", function() {
        expect(function() {
            new pipes\Route('/foo/:bar', array());
        })->to_throw('Exception', 'paths or callback required for route');
    });
    
    describe("compile()", function() {
        it("should compile the \$rawPattern into a real \$pattern", function() {
            $route = new pipes\Route('/foo/:bar', function(){});
            expect($route->rawPattern)->to_be('/foo/:bar');
            expect($route->pattern)->to_be_null();
            $route->compile();
            expect($route->pattern)->to_be('/^\/foo\/(?<bar>\w+)\/?$/');
        });
    });
    
    describe("dispatchToCallback()", function() {
        it("should invoke the callback function with the arguments", function() {
            $count = 0;
            $args = array();
            $route = new pipes\Route('/', function() use(&$count, &$args) {
                $count += 1;
                $args[] = func_get_args();
            });
            $route->dispatchToCallback(array(1, 2, 3));
            $route->dispatchToCallback(array(4, 5, 6));
            expect($count)->to_be(2);
            expect($args)->to_have_length(2);
            expect($args[0])->to_be(array(1, 2, 3));
            expect($args[1])->to_be(array(4, 5, 6));
        });
    });
    
    describe("dispatchToPaths()", function() {
        before_each(function($context) {
            $context['route'] = new pipes\Route('/(?<path>.*)', array(
                'paths' => array(__DIR__.'/mock/path1', __DIR__.'/mock/path2')
            ));
            return $context;
        });
        
        it("should include matching files in the paths in order", function($context) {
            extract($context);
            ob_start();
            expect($route->dispatchToPaths('/foo'))->to_be('foo1foo2');
            expect(ob_get_clean())->to_be('');
        });
        
        it("should stop iterating over the path if \$route->bubble is set to false", function($context) {
            extract($context);
            ob_start();
            expect($route->dispatchToPaths('/bar'))->to_be('bar1');
            expect(ob_get_clean())->to_be('');
        });
        
        it("should not fail if at least one matching file exists", function($context) {
            extract($context);
            ob_start();
            expect($route->dispatchToPaths('/baz'))->to_be('baz1');
            expect(ob_get_clean())->to_be('');
        });
        
        it("should fail if no matching files exist", function($context) {
            extract($context);
            ob_start();
            expect(function() use($route) {
                $route->dispatchToPaths('/biff');
            })->to_throw('Exception', 'no matching files in route paths');
            expect(ob_get_clean())->to_be('');
        });
    });
    
    describe("dispatch()", function() {
        it("should dispatch to callback if set", function() {
            $route = new pipes\Route('/', function() { return 'huzzah!'; });
            expect($route->dispatch('/', array()))->to_equal('huzzah!');
        });
        
        it("should dispatch to paths if set", function() {
            $route = new pipes\Route('/foo', array(
                'paths' => array(__DIR__.'/mock/path1', __DIR__.'/mock/path2')
            ));
            ob_start();
            expect($route->dispatch('/foo', array()))->to_equal('foo1foo2');
            expect(ob_get_clean())->to_be('');
        });
        
        it("should use the 'path' sub-pattern, if matched", function() {
            $route = new pipes\Route('/toe/(?<path>\w+)', array(
                'paths' => array(__DIR__.'/mock/path1', __DIR__.'/mock/path2')
            ));
            ob_start();
            expect($route->dispatch('/toe/foo', array('path'=>'foo')))
                ->to_equal('foo1foo2');
            expect(ob_get_clean())->to_be('');
        });
        
        it("should not dispatch to paths if callback is set ", function() {
            // FIXME: ... or should it?
            $route = new pipes\Route('/foo', array(
                'callback' => function() { return 'xyzzy'; },
                'paths' => array(__DIR__.'/mock/path1', __DIR__.'/mock/path2')
            ));
            ob_start();
            expect($route->dispatch('/foo', array()))->to_equal('xyzzy');
            expect(ob_get_clean())->to_be('');
        });
        
        it("should fail if neither callback or paths are set", function() {
            $route = new pipes\Route('/', function(){});
            unset($route->opts->callback);
            expect(function() use($route) {
                $route->dispatch('/', array());
            })->to_throw('Exception', 'paths or callback required for route');
        });
        
        it("should move all named matches into \$request->params", function() {
            $route = new pipes\Route('/', function(){ return 'xyzzy'; });
            $matches = array(
                'path' => '/foo',
                0 => 'x', 'a' => 'x',
                1 => 'y', 'b' => 'y');
            expect($route->dispatch('/foo', $matches))->to_equal('xyzzy');
            expect(pipes\request()->params->toArray())->to_be(array(
                'format' => 'html',
                'path' => 'foo',
                'a' => 'x',
                'b' => 'y'
            ));
        });
    });
    
    describe("matches()", function() {
        it("should automatically compile the pattern if needed", function() {
            $route = new pipes\Route('/foo/:bar', function(){});
            expect($route->pattern)->to_be_null();
            $route->matches(new pipes\Request());
            expect($route->pattern)->not_to_be_null();
        });
        
        it("should return true if \$request->uri matches \$pattern", function() {
            $route = new pipes\Route('/foo/:bar', function(){});
            $request = new pipes\Request('/foo/xyzzy');
            expect($route->matches($request))->to_be_true();
        });
        
        it("should return false if the pattern does not match", function() {
            $route = new pipes\Route('/foo/:bar', function(){});
            $request = new pipes\Request('/bar/xyzzy');
            expect($route->matches($request))->to_be_false();
        });
        
        it("should return false if the http method does not match", function() {
            $route = new pipes\Route('/foo/:bar', array(
                'callback' => function(){},
                'method' => 'GET'
            ));
            $request = new pipes\Request('/foo/xyzzy', 'PUT');
            expect($route->matches($request))->to_be_false();
        });
        
        it("should store the regex matches in the second argument by reference", function() {
            $route = new pipes\Route('/foo/:bar/:baz', function(){});
            $request = new pipes\Request('/foo/xy/zzy');
            expect($route->matches($request, $matches))->to_be_true();
            expect($matches)->to_be(array(
                0 => "/foo/xy/zzy",
                "bar" => "xy", 1 => "xy",
                "baz" => "zzy", 2 => "zzy"
            ));
        });
    });
});
