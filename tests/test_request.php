<?php

describe("request()", function() {
    it("should return the current pipes\\Request instance", function() {
        $request = pipes\request();
        expect($request)->to_be_a('pipes\Request');
        expect(pipes\request())->to_be($request);
    });
    
    it("should allow you to override the current instance", function() {
        $oldRequest = pipes\request();
        $newRequest = new pipes\Request();
        expect(pipes\request($newRequest))->to_be($newRequest);
        expect(pipes\request())->to_be($newRequest);
        expect(pipes\request($oldRequest))->to_be($oldRequest);
        expect(pipes\request())->to_be($oldRequest);
    });
});

describe("Request", function() {
    before_each(function($context) {
        $oldServer = $_SERVER;
        $oldRequest = $_REQUEST;
        $_SERVER['REQUEST_URI'] = '/foo/bar/baz.biff';
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_REQUEST['a'] = 1;
        $_REQUEST['b'] = 2;
        $request = new pipes\Request();
        return array_merge($context, compact('oldServer', 'oldRequest', 'request'));
    });
    
    after_each(function($context) {
        $_SERVER = $context['oldServer'];
        $_REQUEST = $context['oldRequest'];
    });
    
    it("should copy \$_SERVER['REQUEST_URI'] to \$request->uri", function($context) {
        extract($context);
        expect($request->uri)->to_be($_SERVER['REQUEST_URI']);
    });
    
    it("should copy \$_SERVER['REQUEST_METHOD'] to \$request->method", function($context) {
        extract($context);
        expect($request->method)->to_be($_SERVER['REQUEST_METHOD']);
    });
    
    it("should merge \$_REQUEST into \$request->params hash", function($context) {
        extract($context);
        expect($request->params->a)->to_be(1);
        expect($request->params->b)->to_be(2);
    });
    
    it("should allow you to override \$_SERVER and \$_REQUEST by passing values " .
       "to the constructor instead", function($context) {
        $request = new pipes\Request('/i/have/the/power', 'GET', array('a'=>3, 'b'=>4));
        expect($request->uri)->to_be('/i/have/the/power');
        expect($request->method)->to_be('GET');
        expect($request->params->a)->to_be(3);
        expect($request->params->b)->to_be(4);
    });
    
    it("should set \$request->route to \$request->uri without the extension", function($context) {
        extract($context);
        expect($request->route)->to_be('/foo/bar/baz');
    });
    
    it("should copy the lowercased extension to \$request->format", function($context) {
        extract($context);
        expect($request->format)->to_be('biff');
    });
    
    it("should also copy the format into \$request->params->format", function($context) {
        extract($context);
        expect($request->params->format)->to_be('biff');
    });
    
    it("should have a default format of 'html' for routes without an extension", function($context) {
        $route = new pipes\Request('/foo/bar');
        expect($route->format)->to_be('html');
    });
    
    describe("ajax()", function() {
        it("should return true if the request was an ajax request", function($context) {
            extract($context);
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']))
                unset($_SERVER['HTTP_X_REQUESTED_WITH']);
            expect($request->ajax())->to_be_false();
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'MisterCringerpants';
            expect($request->ajax())->to_be_false();
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
            expect($request->ajax())->to_be_true();
        });
    });
});
