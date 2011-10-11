<?php
date_default_timezone_set('Asia/Tokyo');
mb_internal_encoding('UTF-8');
iconv_set_encoding('internal_encoding', 'UTF-8');
set_include_path(__DIR__ . PATH_SEPARATOR . get_include_path());

require_once('Zend/Loader/Autoloader.php');
$autoloader =
    Zend_Loader_Autoloader::getInstance()
        ->unregisterNamespace(array('Zend_', 'ZendX_'))
        ->setFallbackAutoloader(true);
