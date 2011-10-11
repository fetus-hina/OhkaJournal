<?php
class Oj_Runner {
    const UA_NAME       = 'OhkaJournal';
    const UA_VERSION    = '0.1';
    const UA_AUTHOR     = 'https://twitter.com/#!/fetus_hina';

    static public function run($mail_filename) {
        if($mail = Oj_Mail::run($mail_filename)) {
            self::postToTwitter($mail);
        }
    }

    static private function postToTwitter(stdClass $postinfo) {
        if(!$postinfo->file) {
            self::postToTwitterSimple($postinfo);
        } else {
            self::postToTwitterMedia($postinfo);
        }
    }

    static private function postToTwitterSimple(stdClass $postinfo) {
        $uri = Zend_Uri::factory('http://api.twitter.com/1/statuses/update.json');
        if(!$status = self::formatMessage($postinfo)) {
            return null;
        }
        $parameters = array('status' => $status);
        if($postinfo->gps) {
            $parameters['lat']  = sprintf('%.6f', $postinfo->gps->latitude);
            $parameters['long'] = sprintf('%.6f', $postinfo->gps->longitude);
        }
        $config = Oj_Config::getInstance()->oauth;
        $oauth =
            Oj_OAuth::buildAuthorization(
                Zend_Http_Client::POST,
                $uri,
                $parameters,
                '',
                $config->consumer->key,
                $config->consumer->secret,
                $config->access->token,
                $config->access->secret);
        $client = new Zend_Http_Client();
        $client->setMethod(Zend_Http_Client::POST);
        $client->setUri($uri);
        $client->setHeaders(
            array(
                'Authorization' => $oauth,
                'User-Agent'    => self::buildHttpUserAgent()));
        $client->setParameterPost($parameters);
        $resp = $client->request();
    }

    static private function postToTwitterMedia(stdClass $postinfo) {
        $uri = Zend_Uri::factory('https://upload.twitter.com/1/statuses/update_with_media.json');
        $parameters = array('status' => self::formatMessage($postinfo));
        if($postinfo->gps) {
            $parameters['lat']  = sprintf('%.6f', $postinfo->gps->latitude);
            $parameters['long'] = sprintf('%.6f', $postinfo->gps->longitude);
        }
        $config = Oj_Config::getInstance()->oauth;
        $oauth =
            Oj_OAuth::buildAuthorization(
                Zend_Http_Client::POST,
                $uri,
                '',
                '',
                $config->consumer->key,
                $config->consumer->secret,
                $config->access->token,
                $config->access->secret);
        $client = new Zend_Http_Client();
        $client->setMethod(Zend_Http_Client::POST);
        $client->setUri($uri);
        $client->setEncType(Zend_Http_Client::ENC_FORMDATA);
        $client->setHeaders(
            array(
                'Authorization' => $oauth,
                'User-Agent'    => self::buildHttpUserAgent()));
        $client->setParameterPost($parameters);
        foreach($postinfo->file as $i => $fileinfo) {
            $client->setFileUpload(
                self::buildDummyFilename($fileinfo),
                'media[]',
                $fileinfo->binary,
                $fileinfo->content_type);
        }
        $resp = $client->request();
        var_dump($resp->getBody());
        //var_dump($client->getLastRequest());
    }

    static private function formatMessage(stdClass $postinfo) {
        if($postinfo->text != '') {
            return $postinfo->text;
        }
        if($postinfo->subject != '') {
            return $postinfo->subject;
        }
        if($postinfo->file) {
            return '.';
        }
        return null;
    }

    static private function buildHttpUserAgent() {
        return
            sprintf(
                '%s/%s(%s) %s/%s(%s) %s/%s',
                self::UA_NAME, self::UA_VERSION, self::UA_AUTHOR,
                'ZendFramework', Zend_Version::VERSION, 'Zend_Http_Client',
                'Oj_OAuth', substr(sha1_file(__DIR__ . '/OAuth/Detail.php'), 0, 8));
    }

    static private function buildDummyFilename(stdClass $fileinfo) {
        return
            sprintf(
                '%s.%s',
                Oj_Uuid::factory()->__toString(),
                self::getStandardExtension($fileinfo->content_type));
    }

    static private function getStandardExtension($content_type, $def = 'dat') {
        switch(strtolower($content_type)) {
        case 'image/jpeg':  return 'jpg';
        case 'image/png':   return 'png';
        case 'image/gif':   return 'gif';
        default:            return $def;
        }
    }
}
