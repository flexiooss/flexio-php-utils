<?php

namespace io\flexio\utils;

use \ArrayObject;
use \Exception;

class TypedArray extends ArrayObject {
   
    private $clazz;
   
    public function __construct( $clazz, $input = array() ){
        if( !class_exists( $clazz ) ){
            throw new Exception( 'Invalid class: the specified class is not found' );
        }
        $this->clazz = $clazz;
        foreach( $input as $item ){
            if(! $item instanceof $clazz ){
                throw new Exception( 'Invalid class: found an item in the initial array that is not an instance of the specified class' );
            }else{
                $this->offsetSet( null, $item );
            }
        }
    }
   
    public function append ( $value ){
        $this->offsetSet( null, $value );
    }
   
    public function offsetSet ( $index , $newval ){
        if( $newval instanceof $this->clazz ){
            parent::offsetSet( $this->count(), $newval );
        }
        else{
            throw new Exception( 'Invalid class: inserted object is not an instance of expected class' );
        }
    }
}
