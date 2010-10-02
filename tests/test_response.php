<?php

describe("response()", function() {
    it("should return the current pipes\\Response instance", function() {
        $response = pipes\response();
        expect($response)->to_be_a('pipes\Response');
        expect(pipes\response())->to_be($response);
    });
    
    it("should allow you to override the current instance", function() {
        $oldResponse = pipes\response();
        $newResponse = new pipes\Request();
        expect(pipes\response($newResponse))->to_be($newResponse);
        expect(pipes\response())->to_be($newResponse);
        expect(pipes\response($oldResponse))->to_be($oldResponse);
        expect(pipes\response())->to_be($oldResponse);
    });
});

describe("Response", function() {
    before_each(function($context) {
        pipes\headers(array());
        $context['response'] = new pipes\Response();
        return $context;
    });
    
    it("should be empty by default", function($context) {
        extract($context);
        expect($response->body)->to_be_type('array')->and_to_be_empty();
        expect($response->headers)->to_be_a('pipes\\Hash')->to_have_count(0);
        expect($response->length)->to_be(0);
    });
    
    it("should allow you to specify headers", function($context) {
        extract($context);
        $response->headers['Content-Type'] = 'text/html';
        $response->headers['Set-Cookie'] = 'username=skeletor';
        expect($response->headers)->to_have_count(2);
        expect($response->headers['Content-Type'])->to_be('text/html');
        expect($response->headers['Set-Cookie'])->to_be('username=skeletor');
    });
    
    describe("flush()", function() {
        it("should send all headers", function($context) {
            extract($context);
            ob_start();
            $response->headers['Content-Type'] = 'text/html';
            $response->headers['Set-Cookie'] = 'username=skeletor';
            $response->flush();
            $headers = pipes\headers();
            expect($headers[0])->to_be('Content-Type: text/html');
            expect($headers[1])->to_be('Set-Cookie: username=skeletor');
            expect(ob_get_clean())->to_be_empty();
        });
        
        it("should implode and echo \$body", function($context) {
            extract($context);
            ob_start();
            $response->write("foo\n");
            $response->write("bar");
            $response->write("baz");
            $response->flush();
            expect(ob_get_clean())->to_be("foo\nbarbaz");
        });
    });
    
    describe("write()", function() {
        it("should append the string to \$body and increase \$length", function($context) {
            extract($context);
            expect($response->body)->to_have_count(0);
            $response->write("foo");
            expect($response->body)->to_have_count(1);
            $response->write("bar");
            expect($response->body)->to_have_count(2);
            expect($response->body)->to_be(array('foo', 'bar'));
            expect($response->length)->to_be(6);
        });
    });
});
