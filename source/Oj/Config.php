<?php
class Oj_Config {
    private
        $oauth = null;

    static public function getInstance() {
        static $obj = null;
        if(!$obj) {
            $obj = new self();
        }
        return $obj;
    }

    public function __get($key) {
        switch($key) {
        case 'oauth': return $this->oauth;
        }
    }

    private function __construct() {
        $filename = __DIR__ . '/../../config/config.ini';
        $this->oauth = new Zend_Config_Ini($filename, 'oauth');
    }
}
