<?php

spl_autoload_register(function ($name) {
    require_once("php/classes/" . lcfirst($name) . ".php");
});

function prettyPrint($array) {
    echo ("<pre>");
    echo utf8json($array, true);
    echo ("</pre>");
}

function escape($string, $multiline = false) {
    $from = array("'", "\\");
    $to = array("&#39;", "&#8726;");
    $r = trim(str_replace($from, $to, htmlspecialchars($string)));
    if ($multiline) {
        $r = str_replace("\n", "<br>", $r);
    }
    return $r;
}

function escape_r($input) {
    if (is_string($input) || is_numeric($input)) {
        return escape($input);
    } elseif (is_array($input)) {
        foreach ($input as $key => $val) {
            $input[escape($key)] = escape_r($val);
        }
        return $input;
    }
}

function is_json($string) {
    if (!is_string($string)) {
        return false;
    }
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

function cache($seconds_to_cache = 300) { // Generate cache header
    header("Cache-Control: max-age=$seconds_to_cache,private");
}

function eTag($int_time) { // Generate E-tag 
    $etag = md5($int_time);

    header("Last-Modified: " . gmdate("D, d M Y H:i:s", $int_time) . " GMT");
    header("Etag: $etag");

    if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $int_time || @trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
        header("HTTP/1.1 304 Not Modified");
        exit;
    }
}

function utf8json($array, $prettyPrint = false) { // json_encode() alternative
    $json = json_encode($array, $prettyPrint ? JSON_PRETTY_PRINT : 0);
    return preg_replace_callback(
        '/\\\\u([0-9a-fA-F]{4})/',
        function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        },
        $json
    );
}

function getUserIP() {
    // Get real visitor IP behind CloudFlare network
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if (filter_var($client, FILTER_VALIDATE_IP)) {
        $ip = $client;
    } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
        $ip = $forward;
    } else {
        $ip = $remote;
    }

    return $ip;
}

function str_replace_first(string|array $from, string $to, string $in) {
    if (is_string($from)) {
        $from = [$from];
    }

    foreach ($from as $from_element) {
        $pos = strpos($in, $from_element);
        if ($pos !== false) {
            return substr_replace($in, $to, $pos, strlen($from_element));
        }
    }

    return $in;
}
