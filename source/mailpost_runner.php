<?php
require_once(__DIR__ . '/bootstrap.php');

// fread() が通常のファイルでない時に 8192 バイトしか返さない仕様があるので
// それがZend_Mail_Part_File::getContent() で不完全な結果を返すことを引き起こして
// ファイルが壊れるので、一旦テンポラリファイルに書く
$tempfile_handle = tmpfile();
fwrite($tempfile_handle, file_get_contents('php://STDIN'));
fseek($tempfile_handle, 0, SEEK_SET);
Oj_Runner::run($tempfile_handle);
