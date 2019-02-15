<?php

namespace io\flexio\utils;

use \DateTime;
use \JsonSerializable;

class FlexDate extends DateTime implements JsonSerializable
{

    private $format;

    const datetimePattern = '/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(\.(\d*))?(Z)?$/';
    const zonedDatetimePattern = '/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(\.(\d*))?(Z)?([+-](\d{2}):(\d{2}))/';
    const datePattern = '/^(\d{4})-(\d{2})-(\d{2})$/';
    const timePattern = '/^(\d{2}):(\d{2}):(\d{2})(\.(\d*))?(Z)?/';

    const dateFormat = "Y-m-d";
    const timeFormat = "G:i:s\Z";
    const datetimeFormat = "Y-m-d\TG:i:s\Z";
    const zonedDatetimeFormat = "Y-m-d\TG:i:sP";

    public function __construct(string $format, string $time = "now")
    {
        parent::__construct($time);
        $this->format = $format;
    }

    public static function newTime(string $time = "now")
    {
        return new FlexDate(FlexDate::timeFormat, $time = "now");
    }

    public static function newDate(string $time)
    {
        return new FlexDate(FlexDate::dateFormat, $time = "now");
    }

    public static function newDateTime(string $time)
    {
        return new FlexDate(FlexDate::datetimeFormat, $time = "now");
    }

    public static function newTzDateTime(string $time)
    {
        return new FlexDate(FlexDate::zonedDatetimeFormat, $time = "now");
    }

    public function jsonSerialize()
    {
        return $this->format($this->format);
    }

    /**
     * @param $date
     * @return FlexDate
     * @throws \Exception
     */
    public static function parse($date)
    {
        if (preg_match(FlexDate::timePattern, $date)) {
            return FlexDate::newTime($date);
        } else if (preg_match(FlexDate::datePattern, $date)) {
            return FlexDate::newDate($date);
        } else if (preg_match(FlexDate::datetimePattern, $date)) {
            return FlexDate::newDateTime($date);
        } else if (preg_match(FlexDate::zonedDatetimePattern, $date)) {
            return FlexDate::newTzDateTime($date);
        }
        throw new \Exception("Unparsable date"); // #TODO: tz date not implemented yet
    }

}
