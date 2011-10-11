<?php
class Oj_Uuid {
    const NIL_UUID          = '00000000-0000-0000-0000-000000000000';
    const NAMESPACE_DNS     = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
    const NAMESPACE_URL     = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
    const NAMESPACE_OID     = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';
    const NAMESPACE_X500    = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

    const VERSION_NIL   = 0x0;
    const VERSION_1     = 0x1;
    const VERSION_2     = 0x2;
    const VERSION_3     = 0x3;
    const VERSION_4     = 0x4;
    const VERSION_5     = 0x5;

    const FORMAT_LOWER  = 'lower';
    const FORMAT_UPPER  = 'upper';

    protected
        $binary         = null,
        $format_case    = self::FORMAT_LOWER,
        $format_bracket = false,
        $gettable_keys =
            array(
                'time_low', 'time_mid', 'time_high',
                'version', 'clock_seq', 'node',
                'time_high_and_version',
                'clock_seq_high_and_reserved',
                'clock_seq_high', 'clock_seq_low'),
        $magic_keys =
            array(
                'rfc_format', 'microsoft_format',
                'lower_case', 'upper_case',
                'with_bracket', 'without_bracket',
                'generate', 'generate_v4'),
        $magic_keys2 =
            array(
                'lower' => 'lower_case',
                'upper' => 'upper_case');

    static public function factory($uuid = false) {
        $obj = new self();
        if($uuid) {
            $obj->set($uuid);
        } else {
            $obj->generate();
        }
        return $obj;
    }

    static public function factoryV3($namespace, $id) {
        return self::factory()->generateV3($namespace, $id);
    }

    static public function factoryV5($namespace, $id) {
        return self::factory()->generateV5($namespace, $id);
    }

    public function __construct($uuid = self::NIL_UUID) {
        $this->set($uuid)->rfcFormat();
    }

    public function set($uuid) {
        if(is_string($uuid) && strlen($uuid) === 16) {
            $this->binary = $uuid;
        } elseif(is_string($uuid) && strlen($uuid) === 32 && preg_match('/^[[:xdigit:]]{32}$/', $uuid)) {
            $this->binary = '';
            for($i = 0; $i < 32; $i += 2) {
                $this->binary .= chr(hexdec(substr($uuid, $i, 2)));
            }
        } elseif(is_string($uuid) &&
                 (strlen($uuid) === strlen(self::NIL_UUID) || strlen($uuid) === strlen(self::NIL_UUID) + 2) &&
                 preg_match('/^\{?([[:xdigit:]]{8})-([[:xdigit:]]{4})-([[:xdigit:]]{4})-([[:xdigit:]]{4})-([[:xdigit:]]{12})\}?$/', $uuid, $match))
        {
            $this->binary = '';
            for($i = 1; $i <= 5; ++$i) {
                $len = strlen($match[$i]);
                for($j = 0; $j < $len; $j += 2) {
                    $this->binary .= chr(hexdec(substr($match[$i], $j, 2)));
                }
            }
        } else {
            throw new Oj_Uuid_Exception('Invalid uuid format');
        }

        switch($this->getVersion()) {
        case self::VERSION_NIL:
            if($this->binary !== str_repeat(chr(0x00), 16)) {
                throw new Oj_Uuid_Exception('Uuid verion is zero. But uuid is not `nil uuid`');
            }
            break;

        case self::VERSION_1:
        case self::VERSION_2:
        case self::VERSION_3:
        case self::VERSION_4:
        case self::VERSION_5:
            break;

        default:
            throw new Oj_Uuid_Exception('Invalid uuid version');
        }
        return $this;
    }

    public function rfcFormat() {
        return $this->lowerCase()->withoutBracket();
    }

    public function microsoftFormat() {
        return $this->upperCase()->withBracket();
    }

    public function lowerCase() {
        return $this->setCase(self::FORMAT_LOWER);
    }

    public function upperCase() {
        return $this->setCase(self::FORMAT_UPPER);
    }

    public function setCase($format) {
        $this->format_case = ($format === self::FORMAT_UPPER) ? self::FORMAT_UPPER : self::FORMAT_LOWER;
        return $this;
    }

    public function getCase() {
        return $this->format_case;
    }

    public function isUpperCase() {
        return $this->getCase() === self::FORMAT_UPPER;
    }

    public function isLowerCase() {
        return !$this->isUpperCase();
    }

    public function withBracket() {
        return $this->setBracket(true);
    }

    public function withoutBracket() {
        return $this->setBracket(false);
    }

    public function setBracket($with) {
        $this->format_bracket = !!$with;
        return $this;
    }

    public function getBracket() {
        return $this->format_bracket;
    }

    public function isWithBracket() {
        return $this->getBracket();
    }

    public function isWithoutBracket() {
        return !$this->isWithBracket();
    }

    public function getTimeLow() {
        return $this->fetchInteger(0, 4);
    }

    public function getTimeMid() {
        return $this->fetchInteger(4, 2);
    }

    public function getTimeHighAndVersion() {
        return $this->fetchInteger(6, 2);
    }

    public function getTimeHigh() {
        return $this->getTimeHighAndVersion() & 0x0FFF;
    }

    public function getVersion() {
        return ($this->getTimeHighAndVersion() & 0xF000) >> 12;
    }

    public function getClockSeqHighAndReserved() {
        return $this->fetchInteger(8, 1);
    }

    public function getClockSeqHigh() {
        return $this->getClockSeqHighAndReserved() & 0x3F;
    }

    public function getClockSeqLow() {
        return $this->fetchInteger(9, 1);
    }

    public function getClockSeq() {
        return ($this->getClockSeqHigh() << 8) + $this->getClockSeqLow();
    }

    public function getNode($delimiter = ':') {
        $result = array();
        for($i = 10; $i < 16; ++$i) {
            $result[] = bin2hex(substr($this->binary, $i, 1));
        }
        return implode($delimiter, $result);
    }

    public function generate() {
        return $this->generateV4();
    }

    public function generateV4() {
        $this->binary = '';
        for($i = 0; $i < 16; ++$i) {
            $this->binary .= chr(mt_rand(0, 0xFF));
        }
        $this->fixBinary(4);
        return $this;
    }

    public function generateV3($namespace, $id) {
        $this->binary = $this->generateHashed('md5', $namespace, $id);
        $this->fixBinary(3);
        return $this;
    }

    public function generateV5($namespace, $id) {
        $this->binary = $this->generateHashed('sha1', $namespace, $id);
        $this->fixBinary(5);
        return $this;
    }

    protected function generateHashed($function, $namespace, $id) {
        if(is_string($namespace)) {
            $namespace = new self($namespace);
        }
        if(!$namespace instanceof self) {
            throw new Oj_Uuid_Exception('Invalid namespace');
        }
        return substr($function($namespace->binary . $id, true), 0, 16);
    }

    protected function fixBinary($version) {
        $this->fixBinary_(6, 0x0F, ($version & 0x0F) << 4);
        $this->fixBinary_(8, 0x3F, 0x80);
    }

    protected function fixBinary_($offset, $mask, $add) {
        $mask = $mask & 0xFF;
        $add  = $add  & 0xFF;
        $value = ord($this->binary[$offset]);
        $value = ($value & $mask) | $add;
        $this->binary[$offset] = chr($value);
    }

    public function __toString() {
        return $this->toString();
    }

    public function toString($case = null, $bracket = null) {
        if(is_null($case)) {
            $case = $this->format_case;
        }
        $case = ($case === self::FORMAT_UPPER) ? self::FORMAT_UPPER : self::FORMAT_LOWER;
        if(is_null($bracket)) {
            $bracket = $this->format_bracket;
        }
        $bracket = !!$bracket;
        $result = bin2hex($this->binary);
        $result = substr($result,  0, 8) . '-' .
            substr($result,  8, 4) . '-' .
            substr($result, 12, 4) . '-' .
            substr($result, 16, 4) . '-' .
            substr($result, 20, 12);
        if($bracket) {
            $result = '{' . $result . '}';
        }
        return
            ($case === self::FORMAT_UPPER)
                ? strtoupper($result)
                : strtolower($result);
    }

    protected function fetchInteger($start_octet, $length) {
        return hexdec(bin2hex(substr($this->binary, $start_octet, $length)));
    }

    public function __get($key) {
        if(in_array($key, $this->gettable_keys)) {
            $method = 'get' . str_replace('_', '', $key);
            return $this->$method();
        }

        if(isset($this->magic_keys2[$key])) {
            $key = $this->magic_keys2[$key];
        }

        if(in_array($key, $this->magic_keys)) {
            $method = str_replace('_', '', $key);
            return $this->$method();
        }

        if($key === 'value' || $key === 'string') {
            return $this->toString();
        }

        if($key === 'binary') {
            return $this->binary;
        }
    }
}
