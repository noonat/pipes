<?php

describe("Hash", function() {
    it("should extend pipes\\ArrayObject", function() {
       expect(new pipes\Hash())->to_be_an_instance_of('ArrayObject');
    });
    
    it("should have the ARRAY_AS_PROPS flag set", function() {
        $hash = new pipes\Hash();
        expect(($hash->getFlags() & \ArrayObject::ARRAY_AS_PROPS) ?
               true : false)->to_be_true();
    });
    
    it("should be empty by default", function() {
        $hash = new pipes\Hash();
        expect($hash->toArray())->to_have_count(0);
    });

    it("should copy a passed array into the hash", function() {
        $hash = new pipes\Hash(array('foo', 'bar', 'baz'));
        expect($hash[0])->to_be('foo');
        expect($hash[1])->to_be('bar');
        expect($hash[2])->to_be('baz');
    });

    it("should allow values to be set via the array operator", function() {
        $hash = new pipes\Hash();
        $hash[0] = 'foo';
        expect($hash[0])->to_be('foo');
        $hash[1] = 'bar';
        expect($hash[0])->to_be('foo');
        expect($hash[1])->to_be('bar');
    });

    it("should allow values to be accessed via object attributes", function() {
        $hash = new pipes\Hash(array('foo'=>1));
        expect($hash->foo)->to_equal(1);
        expect($hash->bar)->to_be_null();
        $hash['bar'] = 2;
        expect($hash->bar)->to_equal(2);
        $hash->bar = 3;
        expect($hash->bar)->to_equal(3);
        expect($hash['bar'])->to_equal(3);
        $hash->baz = 4;
        expect($hash->baz)->to_equal(4);
        expect($hash['baz'])->to_equal(4);
        unset($hash->baz);
        expect($hash->baz)->to_be_null();
        expect($hash['baz'])->to_be_null();
    });
    
    it("should allow count() to be used", function() {
        $hash = new pipes\Hash();
        expect($hash)->to_have_count(0);
        $hash[] = 'foo';
        expect($hash)->to_have_count(1);
        $hash[] = 'bar';
        expect($hash)->to_have_count(2);
    });

    it("should return null for values that are not set", function() {
        $hash = new pipes\Hash();
        expect($hash[0])->to_be_null();
        $hash[0] = 'foo';
        expect($hash[0])->to_be('foo');
        unset($hash[0]);
        expect($hash[0])->to_be_null();
    });
    
    describe("get()", function() {
        before_each(function($context) {
            $context['hash'] = new pipes\Hash(array('foo'=>1));
            return $context;
        });
        
        it("should return the value of the matching key", function($context) {
            extract($context);
            expect($hash->get('foo'))->to_be(1);
            expect($hash->get('foo', 2))->to_be(1);
        });
        
        it("should fallback to null for missing values by default", function($context) {
            extract($context);
            expect($hash->get('bar'))->to_be_null();
        });
        
        it("should fallback to a custom value if specified", function($context) {
            extract($context);
            expect($hash->get('bar', 2))->to_equal(2);
            $hash['bar'] = 3;
            expect($hash->get('bar'))->to_equal(3);
            expect($hash->get('bar', 4))->to_equal(3);
            unset($hash['bar']);
            expect($hash->get('bar', 4))->to_equal(4);
        });
    });
    
    describe("merge()", function() {
       it("should copy the passed values into the hash, by key", function() {
           $hash = new pipes\Hash();
           $hash['foo'] = 1;
           $hash['bar'] = 2;
           $hash->merge(array('bar'=>3, 'baz'=>5));
           expect($hash->toArray())->to_be(array('foo'=>1, 'bar'=>3, 'baz'=>5));
       });
    });
    
    describe("toArray()", function() {
        it("should return a native array copy of the object", function() {
            $hash = new pipes\Hash(array(1, 2, 3));
            $nativeArray = $hash->toArray();
            expect($nativeArray)->to_be_type('array');
            expect($nativeArray)->to_be(array(1, 2, 3));
        });
    });
});
