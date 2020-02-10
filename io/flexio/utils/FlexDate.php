<?php

namespace io\flexio\utils;

use \DateTime;
use \Exception;
use \JsonSerializable;


class FlexDate extends DateTime implements JsonSerializable
{

    private $format;

    const datetimePattern = '/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(\.(\d*))?$/';
    const zonedDatetimePattern = '/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(\.(\d*))?([+-](\d{2}):(\d{2}))/';
    const zonedDatetimeZuluPattern = '/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(\.(\d*))?(Z)/';
    const datePattern = '/^(\d{4})-(\d{2})-(\d{2})$/';
    const timePattern = '/^(\d{2}):(\d{2}):(\d{2})(\.(\d*))?(Z)?/';

    const dateFormat = "Y-m-d";
    const timeFormat = "H:i:s.u\Z";
    const datetimeFormat = "Y-m-d\TH:i:s.u";
    const zonedDatetimeFormat = "Y-m-d\TH:i:s.uP";
    const zonedDatetimeFormatZulu = "Y-m-d\TH:i:s.u\Z";

    public function __construct(FlexDateTypeEnum $format, string $time = "now")
    {
        parent::__construct($time);
        $this->format = $format;
    }

    public static function newTime(string $time = "now")
    {
        return new FlexDate(FlexDateTypeEnum::TIME(), $time);
    }

    public static function newDate(string $time = "now")
    {
        return new FlexDate(FlexDateTypeEnum::DATE(), $time);
    }

    public static function newDateTime(string $time = "now")
    {
        return new FlexDate(FlexDateTypeEnum::DATETIME(), $time);
    }

    public static function newTzDateTime(string $time = "now")
    {
        $format = FlexDateTypeEnum::ZONED_DATETIME();
        if ($time !== 'now') {
            if (preg_match(FlexDate::zonedDatetimeZuluPattern, $time)) {
                $format = FlexDateTypeEnum::ZONED_DATETIME_ZULU();
            }
        }
        return new FlexDate($format, $time);
    }

    public function jsonSerialize()
    {
        switch ($this->format) {
            case FlexDateTypeEnum::TIME():
                return $this->format("H:i:s.") . substr($this->format("u"), 0, 3) . 'Z';
            case FlexDateTypeEnum::DATE():
                return $this->format(FlexDate::dateFormat);
            case FlexDateTypeEnum::DATETIME():
                return $this->format("Y-m-d\TH:i:s.") . substr($this->format("u"), 0, 3);
            case FlexDateTypeEnum::ZONED_DATETIME():
                return $this->format("Y-m-d\TH:i:s.") . substr($this->format("u"), 0, 3) . $this->format('P');
            case FlexDateTypeEnum::ZONED_DATETIME_ZULU():
                return $this->format("Y-m-d\TH:i:s.") . substr($this->format("u"), 0, 3) . 'Z';
        }

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
        } else if (preg_match(FlexDate::zonedDatetimePattern, $date) || preg_match(FlexDate::zonedDatetimeFormatZulu, $date)) {
            return FlexDate::newTzDateTime($date);
        }
        throw new \Exception("Unparsable date"); // #TODO: tz date not implemented yet
    }

}


class FlexDateTypeEnum implements JsonSerializable
{

    protected $value;

    private function __construct($value)
    {
        $this->value = $value;
    }

    public function value()
    {
        return $this->value;
    }

    public static function DATETIME(): FlexDateTypeEnum
    {
        return new FlexDateTypeEnum('DATETIME');
    }

    public static function TIME(): FlexDateTypeEnum
    {
        return new FlexDateTypeEnum('TIME');
    }

    public static function DATE(): FlexDateTypeEnum
    {
        return new FlexDateTypeEnum('DATE');
    }

    public static function ZONED_DATETIME(): FlexDateTypeEnum
    {
        return new FlexDateTypeEnum('ZONED_DATETIME');
    }

    public static function ZONED_DATETIME_ZULU(): FlexDateTypeEnum
    {
        return new FlexDateTypeEnum('ZONED_DATETIME_ZULU');
    }


    public static function valueOf(string $value): FlexDateTypeEnum
    {
        if (in_array($value, FlexDateTypeEnum::values())) {
            return new FlexDateTypeEnum($value);
        } else {
            throw new Exception('No enum constant ' . $value);
        }
    }

    public static function values()
    {
        return array('DATETIME', 'TIME', 'DATE', 'ZONED_DATETIME', 'ZONED_DATETIME_ZULU');
    }

    public function jsonSerialize()
    {
        return $this->value;
    }
}