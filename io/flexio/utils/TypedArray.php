<?php

namespace io\flexio\utils;

use \ArrayObject;
use \Exception;

class TypedArray extends ArrayObject implements \JsonSerializable {

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
        parent::offsetSet( $this->count(), ($this->validate)( $newval ) );
    }

    public function jsonSerialize() {
        return array_values( $this->getArrayCopy() );
    }
}
