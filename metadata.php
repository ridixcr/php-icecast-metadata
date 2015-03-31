<?php

$url = "awesome.icecaststream.com";
$resource = "mystream";
$maxattempts = 1;

$fp = fsockopen($url, 80, $errno, $errstr, 30);
if ($fp == false) {
    die("$errstr ($errno)");
}

fputs($fp, "GET /$resource HTTP/1.1\r\n");
fputs($fp, "Icy-MetaData:1\r\n");
fputs($fp, "User-Agent: WinampMPEG/2.9\r\n");
fputs($fp, "Accept: */*\r\n");
fputs($fp, "Connection: Close\r\n\r\n");

$header = fread($fp, 1024);
$headers = array();
$headers = explode("\n", $header);

$interval = 0;

foreach ($headers as $value) {
    if (strstr($value, "icy-metaint") !== false) {
        $val = explode(':', $value);
        $interval = trim($val[1]);
    }
}

$meta = [];

if ($interval) {
    $attempts = 0;
    while (!count($meta) && $attempts < $maxattempts) {
        for ($j = 0; $j < $interval; $j++) {
            fread($fp, 1);
        }

        $meta_length = ord(fread($fp, 1)) * 16;

        if ($meta_length) {
            $metadata = fread($fp, $meta_length);
            $m = substr(substr(trim(substr($metadata, strstr($metadata, ";"))), 13), 0, -2);
            $meta = explode(" - ", $m);
        }
        $attempts++;
    }
} else {
    die("The stream did not specify an interval.");
}

fclose($fp);

echo json_encode([
    'title' => $meta[1],
    'artist' => $meta[0]
], true);
