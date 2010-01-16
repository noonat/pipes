<?php

namespace pipes;

/// Extended array that tries to pretend it's a Ruby hash.
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
        return $this;
    }

    function offsetGet($key) {
        return $this->get($key);
    }
    
    function toArray() {
        return $this->getArrayCopy();
    }    
}
