<?php

namespace io\flexio\utils;

use \ArrayObject;
use \Exception;

class TypedArray extends ArrayObject {

    private $validate;

    public function __construct( $validate, $input = array() ){
        if( !is_callable( $validate ) ){
            throw new Exception( 'The specified validation function is not callable' );
        }
        $this->validate = $validate;
        foreach( $input as $item ){
            $this->offsetSet( null, $item );
        }
    }
   
    public function append ( $value ){
        $this->offsetSet( null, $this->validate($value) );
    }
   
    public function offsetSet ( $index , $newval ){
        $this->validate($item);
        parent::offsetSet( $this->count(), $newval );
    }

}
