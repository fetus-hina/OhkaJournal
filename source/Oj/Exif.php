<?php
class Oj_Exif {
    private $info = null;

    public function __construct($binary) {
        if(!$tmpfile = @tempnam('/tmp', __CLASS__)) {
            throw new Oj_Exception('Cannot create tmpfile');
        }
        file_put_contents($tmpfile, $binary);
        $this->info = @exif_read_data($tmpfile, 'EXIF', true, false);
        @unlink($tmpfile);
        if(!$this->info) {
            throw new Oj_Exception('Cannot get exif info');
        }
    }

    public function __get($key) {
        switch($key) {
        case 'gps': return $this->getGps();
        }
    }

    public function getGps() {
        return isset($this->info['GPS']) ? new Oj_Exif_Gps($this->info['GPS']) : null;
    }
}
