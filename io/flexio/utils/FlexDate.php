<?php

namespace io\flexio\utils;

use \DateTime;
use \JsonSerializable;

class FlexDate extends DateTime implements JsonSerializable {

    private $format;

    public function __construct( string $format, string $time ){
        parent::createFromFormat( $format, $time );
        $this->format = $format;
    }

    public static function newTime( string $time ){
        return new FlexDate( 'G:i:s', $time );
    }

    public static function newDate( string $time ){
        return new FlexDate( 'Y-m-d', $time );
    }

    public static function newDateTime( string $time ){
        return new FlexDate( 'Y-m-dTG:i:s', $time );
    }

    public function jsonSerialize() {
       return $this->format( $this->format );
    }

}
