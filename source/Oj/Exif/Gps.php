<?php
class Oj_Exif_Gps {
    private
        $latitude = null,
        $longitude = null;

    public function __construct(array $gpsinfo) {
        if(!isset($gpsinfo['GPSLatitudeRef']) ||
           !isset($gpsinfo['GPSLatitude']) ||
           !isset($gpsinfo['GPSLongitudeRef']) ||
           !isset($gpsinfo['GPSLongitude']))
        {
            throw new Oj_Exception('Cannot parse GPS data');
        }
        $this->latitude = self::parse($gpsinfo['GPSLatitudeRef'] === 'N', $gpsinfo['GPSLatitude']);
        $this->longitude = self::parse($gpsinfo['GPSLongitudeRef'] === 'E', $gpsinfo['GPSLongitude']);
    }

    public function __get($key) {
        switch($key) {
        case 'latitude':    return $this->latitude;
        case 'longitude':   return $this->longitude;
        }
    }

    static private function parse($is_positive, array $values) {
        $value =
            self::parseFractional($values[0]) +
            self::parseFractional($values[1]) / 60 +
            self::parseFractional($values[2]) / 3600;
        return $is_positive ? $value : -$value;
    }

    static private function parseFractional($str) {
        if(!preg_match('!^(\d+)/(\d+)$!', $str, $match)) {
            throw new Oj_Exception('Cannot parse fractional number');
        }
        $denominator = (int)$match[2];
        $numerator   = (int)$match[1];
        if($denominator === 0) {
            throw new Oj_Exception('Denominator is zero(div by 0)');
        }
        return $numerator / $denominator;
    }
}
