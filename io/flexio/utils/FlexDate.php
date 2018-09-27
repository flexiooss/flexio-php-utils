<?php

namespace io\flexio\utils;

use \DateTime;
use \JsonSerializable;

class FlexDate extends DateTime implements JsonSerializable {

    private $format;

    const datetimePattern = '/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(\.(\d*))?(Z)?$/';
    const tzDatetimePattern = '/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(\.(\d*))?(Z)?([+-](\d{2}):(\d{2}))/';
    const datePattern = '/^(\d{4})-(\d{2})-(\d{2})$/';
    const timePattern = '/^(\d{2}):(\d{2}):(\d{2})(\.(\d*))?(Z)?/';

    const dateFormat = "Y-m-d";
    const timeFormat = "G:i:s\Z";
    const datetimeFormat = "Y-m-d\TG:i:s\Z";

    public function __construct( string $format, string $time ) {
        parent::__construct( $time );
        $this->format = $format;
    }

    public static function newTime( string $time ) {
        return new FlexDate( FlexDate::timeFormat, $time );
    }

    public static function newDate( string $time ) {
        return new FlexDate( FlexDate::dateFormat, $time );
    }

    public static function newDateTime( string $time ) {
        return new FlexDate( FlexDate::datetimeFormat, $time );
    }

    public static function newtZDateTime( string $time ) {
        return new FlexDate( FlexDate::zonedDatetimeFormat, $time );
    }

    public function jsonSerialize() {
        return $this->format( $this->format );
    }

    /**
     * @param $date
     * @return FlexDate
     * @throws \Exception
     */
    public static function parse( $date ) {
        if( preg_match( FlexDate::timePattern, $date ) ) {
            return FlexDate::newTime( $date );
        } else if( preg_match( FlexDate::datePattern, $date ) ) {
            return FlexDate::newDate( $date );
        } else if( preg_match( FlexDate::datetimePattern, $date ) ) {
            return FlexDate::newDateTime( $date );
        } else if( preg_match( FlexDate::tzDatetimePattern, $date ) ) {
            return FlexDate::newtZDateTime( $date );
        }
        throw new \Exception( "Unparsable date" ); // #TODO: tz date not implemented yet
    }

}
