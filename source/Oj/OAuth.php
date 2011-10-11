<?php
class Oj_OAuth {
    static public function buildAuthorization(
        $http_method,           // "GET" "POST"
        Zend_Uri_Http $uri,
        $http_post_content,     // string
        $realm,                 // string
        $oauth_consumer_key,    // string
        $oauth_consumer_secret, // string
        $oauth_token,           // string
        $oauth_token_secret)    // string
    {
        return
            Oj_OAuth_Detail::buildAuthorization(
                $http_method, $uri, $http_post_content, $realm,
                $oauth_consumer_key, $oauth_consumer_secret,
                $oauth_token, $oauth_token_secret);
    }
}
