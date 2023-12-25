<?php

spl_autoload_register(function ($name) {
    $components = ["PanelReview", "AdminSetting"];
    if (in_array($name, $components)) {
        require_once("php/components/" . lcfirst($name) . ".php");
    } else {
        require_once("php/classes/" . lcfirst($name) . ".php");
    }
});

function prettyPrint($array) {
    echo ("<pre style=\"text-align:left\">");
    echo utf8json($array, true);
    echo ("</pre>");
}

function out($var) {
    echo ("<pre style=\"text-align:left\">");
    echo var_dump($var);
    echo ("</pre>");
}

function escapeConservative($string, $multiline = false) {
    $r = trim(htmlspecialchars($string));
    if ($multiline) {
        $r = str_replace("\n", "<br>", $r);
    }
    return $r;
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

function getClientIP() {
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

function printError(string $title, array $details = null) {
    global $prefix, $v;

    echo ("<h1>$title</h1>");
    echo ("<h3>Please contact the site admin with the following information</h3>");
    out([
        "url" => $prefix . $_SERVER["REQUEST_URI"],
        "session" => session_id(),
        "time" => (new DateTime())->format(TIME_HMS_FORMAT . " " . DATE_DMY_FORMAT),
        "version" => substr($v, 3)
    ]);

    if ($details != null) {
        echo ("<h3>Details</h3>");
        foreach ($details as $name => $value) {
            echo ("<b>$name:</b>");
            if (is_string($value)) echo ($value . "<br>");
            else prettyPrint($value);
        }
    }

    exit;
}

function assignColorById(int $id): string {
    if ($id < 0) return "#000000";
    $colors = ["#69d2e7", "#a7dbd8", "#e0e4cc", "#f38630", "#fa6900", "#0048BA", "#B0BF1A", "#7CB9E8", "#C9FFE5", "#B284BE", "#5D8AA8", "#00308F", "#72A0C1", "#AF002A", "#F2F0E6", "#F0F8FF", "#84DE02", "#E32636", "#C46210", "#EFDECD", "#E52B50", "#9F2B68", "#F19CBB", "#AB274F", "#D3212D", "#3B7A57", "#00C4B0", "#FFBF00", "#FF7E00", "#FF033E", "#9966CC", "#A4C639", "#F2F3F4", "#CD9575", "#665D1E", "#915C83", "#841B2D", "#FAEBD7", "#008000", "#8DB600", "#FBCEB1", "#00FFFF", "#7FFFD4", "#D0FF14", "#4B5320", "#3B444B", "#8F9779", "#E9D66B", "#B2BEB5", "#87A96B", "#FF9966", "#A52A2A", "#FDEE00", "#6E7F80", "#568203", "#FF2052", "#C39953", "#007FFF", "#F0FFFF", "#F0FFFF", "#DBE9F4", "#89CFF0", "#A1CAF1", "#F4C2C2", "#FEFEFA", "#FF91AF", "#21ABCD", "#FAE7B5", "#FFE135", "#006A4E", "#E0218A", "#7C0A02", "#1DACD6", "#848482", "#98777B", "#BCD4E6", "#9F8170", "#FA6E79", "#F5F5DC", "#2E5894", "#9C2542", "#E88E5A", "#FFE4C4", "#3D2B1F", "#967117", "#CAE00D", "#BFFF00", "#FE6F5E", "#BF4F51", "#000000", "#3D0C02", "#54626F", "#253529", "#3B3C36", "#BFAFB2", "#FFEBCD", "#A57164", "#318CE7", "#ACE5EE", "#FAF0BE", "#0000FF", "#1F75FE", "#0093AF", "#0087BD", "#0018A8", "#333399", "#0247FE", "#A2A2D0", "#00B9FB", "#6699CC"];
    return $colors[$id % count($colors)];
}

function array_keys_map(callable $callback, array $array): array {
    return array_map($callback, array_keys($array));
}

function array_map_with_keys(callable $callback, array $array): array {
    return array_map($callback, array_keys($array), array_values($array));
}

function getWeekDay(DateTime $dateTime): string {
    return ([
        "Mon" => "Po",
        "Tue" => "Út",
        "Wed" => "St",
        "Thu" => "Čt",
        "Fri" => "Pá",
        "Sat" => "So",
        "Sun" => "Ne"
    ])[$dateTime->format("D")];
}
