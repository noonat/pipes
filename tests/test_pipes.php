<?php

describe("run", function() {
    before_each(function($context) {
        $oldServer = $_SERVER;
        $oldRequest = $_REQUEST;
        $_SERVER['REQUEST_URI'] = '/foo';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_REQUEST['a'] = 1;
        $_REQUEST['b'] = 2;
        pipes\routes(array());
        $request = pipes\request(new pipes\Request());
        $response = pipes\response(new pipes\Response());
        return array_merge($context, compact('oldServer', 'oldRequest', 'request'));
    });
    
    after_each(function($context) {
        $_SERVER = $context['oldServer'];
        $_REQUEST = $context['oldRequest'];
    });
    
    it("should run and return the first matching route", function() {
        $route1 = pipes\get('/foo', function() {
            return 'bar';
        });
        $route2 = pipes\get('/foo', function() {
            return 'baz';
        });
        ob_start();
        expect(pipes\run())->to_be_type('object')->and_to_be($route1);
        expect(ob_get_clean())->to_be('bar');
    });
    
    it("should return null if no route matched the request", function() {
        ob_start();
        expect(pipes\run())->to_be_null();
        expect(ob_get_clean())->to_be('');
    });
    
    it("should not flush the response if \$opts->flush is false", function() {
        pipes\get('/foo', function() {
            return 'bar';
        });
        ob_start();
        pipes\run(array('flush'=>false));
        expect(ob_get_clean())->to_be('');
        ob_start();
        pipes\response()->flush();
        expect(ob_get_clean())->to_be('bar');
    });
    
    it("should use the 'path' sub-pattern, if matched", function() {
        $_SERVER['REQUEST_URI'] = '/toe/foo';
        $request = pipes\request(new pipes\Request());
        pipes\get('/toe/(?<path>\w+)', array(
            'paths' => array(__DIR__.'/mock/path1', __DIR__.'/mock/path2')
        ));
        ob_start();
        pipes\run();
        expect(ob_get_clean())->to_be('foo1foo2');
    });
});

describe("includeIfExists", function() {
    it("should return true if the file was included", function() {
        ob_start();
        $filename = __DIR__.'/mock/path1/foo.php';
        expect(pipes\includeIfExists($filename))->to_be_true();
        expect(ob_get_clean())->to_be('foo1');
    });
    
    it("should return false if the file was not included", function() {
        ob_start();
        $filename = __DIR__.'/does/not/exist.php';
        expect(pipes\includeIfExists($filename))->to_be_false();
        expect(ob_get_clean())->to_be('');
    });
    
    it("should extract \$context as local variables for the include", function() {
        ob_start();
        $context = array('xyzzy' => 1337);
        $filename = __DIR__.'/mock/path1/context.php';
        expect(pipes\includeIfExists($filename, $context))->to_be_true();
        expect(ob_get_clean())->to_be("int(1337)\n");
    });
});
