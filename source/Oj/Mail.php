<?php
class Oj_Mail {
    static public function run($filename) {
        $mail = new Zend_Mail_Message_File(array('file' => $filename));
        $data = self::procMail($mail);
        if($data->subject || $data->text || $data->file) {
            return $data;
        }
        return null;
    }

    static private function procMail(Zend_Mail_Message_File $mail) {
        $ret = (object)array('subject' => null, 'text' => null, 'file' => array(), 'gps' => null);
        $ret = self::mergeInfo($ret, self::procMailPart($mail));
        if($mail->isMultipart()) {
            foreach($mail as $part) {
                if($partdata = self::procMailPart($part)) {
                    $ret = self::mergeInfo($ret, $partdata);
                }
            }
        }
        return $ret;
    }

    static private function mergeInfo(stdClass $a, stdClass $b) {
        $c = clone $a;
        foreach($b as $k => $v) {
            if(!isset($c->$k)) {
                $c->$k = $v;
            }
            if(is_array($v)) {
                $c->$k = array_merge($c->$k, $v);
            }
        }
        return $c;
    }

    static private function procMailPart($mail) {
        $ret =
            array(
                'subject'   => null,
                'text'      => null,
                'file'      => array(),
                'gps'       => null);
        if($mail->headerExists('subject')) {
            $ret['subject'] = $mail->subject;
        }
        if($mail->headerExists('content-type')) {
            $content_type = strtolower($mail->contentType);
            switch($content_type = strtok($content_type, ';')) {
            case 'text/plain':
                //FIXME: charset
                $ret['text'] =
                    preg_replace(
                        '/[[:space:]]+/',
                        ' ',
                        trim(
                            Normalizer::normalize(
                                mb_convert_encoding($mail->__toString(), 'UTF-8', 'ISO-2022-JP'))));
                break;

            case 'image/png':
            case 'image/jpeg':
            case 'image/gif':
                if($tmp = self::procImagePart($mail, $content_type)) {
                    $ret['file'][] = $tmp;
                    if($tmp->gps && !$ret['gps']) {
                        $ret['gps'] = $tmp->gps;
                    }
                }
                break;
            }
        }
        return (object)$ret;
    }

    static private function procImagePart($mail, $content_type) {
        $body = $mail->getContent();
        if($mail->headerExists('content-transfer-encoding')) {
            switch($mail->getHeader('content-transfer-encoding', 'string')) {
            case 'plain':
                break;
            case 'base64':
                if(!$body = @base64_decode($body)) {
                    return null;
                }
                break;
            default:
                return null;
            }
        }
        if(!$image = @imagecreatefromstring($body)) {
            return null;
        }
        imagedestroy($image);
        unset($image);

        $gps = null;
        if($content_type === 'image/jpeg') {
            try {
                $exif = new Oj_Exif($body);
                $gps = $exif->gps;
            } catch(Exception $e) {
            }
        }
        return
            (object)array(
                'content_type'  => $content_type,
                'binary'        => $body,
                'gps'           => $gps);
    }
}
