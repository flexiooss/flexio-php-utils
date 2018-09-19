<?php

namespace io\flexio\utils;

use \DateTime;
use \JsonSerializable;

class FlexDate extends DateTime implements JsonSerializable {

    private $format;

    public function __construct( string $format, string $time ){
        parent::__construct( $time );
        $this->format = $format;
    }

    public static function newTime( string $time ){
        return new FlexDate( 'G:i:s\Z', $time );
    }

    public static function newDate( string $time ){
        return new FlexDate( 'Y-m-d', $time );
    }

    public static function newDateTime( string $time ){
        return new FlexDate( 'Y-m-d\TG:i:s\Z', $time );
    }

    public static function newtZDateTime( string $time ){
        return new FlexDate( 'Y-m-d\TG:i:sP', $time );
    }

    public function jsonSerialize() {
       return $this->format( $this->format );
    }

    public static function parse( $date ){
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if( $dt !== false && !array_sum($dt->getLastErrors()) ){
            return FlexDate::newDate( $date );
        }
        $dt = DateTime::createFromFormat('Y-m-d\TG:i:s', $date);
        if( $dt !== false && !array_sum($dt->getLastErrors()) ){
            return FlexDate::newDateTime( $date );
        }
        $dt = DateTime::createFromFormat('Y-m-d\TG:i:sP', $date);
        if( $dt !== false && !array_sum($dt->getLastErrors()) ){
            return FlexDate::newTzDateTime( $date );
        }
        $dt = DateTime::createFromFormat('G:i:s', $date);
        if( $dt !== false && !array_sum($dt->getLastErrors()) ){
            return FlexDate::newTime( $date );
        }
        throw new \Exception( "Unparsable date" );
    }

}
