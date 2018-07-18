<?php

namespace io\flexio\utils;

user \DateTime;
use \JsonSerializable;

class FlexDate extends DateTime implements JsonSerializable {

    private $format;

    private function __construct( string $format, string $time ){
        parent::createFromFormat( $format, $time );
        $this->format = $format;
    }

    public static function newTime( string $time ){
        return new FlexDate( 'G:i:s' );
    }

    public static function newDate( string $time ){
        return new FlexDate( 'Y-m-d' );
    }

    public static function newDateTime( string $time ){
        return new FlexDate( 'Y-m-dTG:i:s' );
    }

    public function jsonSerialize() {
       return $this->format( $this->format );
    }

}
