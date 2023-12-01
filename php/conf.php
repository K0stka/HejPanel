<?php
/* CONSTANTS TO SET:
    PAGE_NAME
    PAGE_DESCRIPTION
    PAGE_DIR
    DB_NAME
*/

// Product details
define("NAME", "PAGE_NAME");
define("DESCRIPTION", "PAGE_DESCRIPTION");

define("DATE_FORMAT", "j. n. Y");
define("TIME_FORMAT", "G:i:s");

define('PREFIX', str_replace(["php\conf.php", "php/conf.php"], '', __FILE__));
set_include_path(PREFIX);

// Database, prefix
if (substr($_SERVER['SERVER_NAME'], -9) == "localhost" || substr($_SERVER['SERVER_NAME'], -13) == "192.168.137.1") {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    define("DEV", true);

    require_once("php/fx.php");

    $con = new Conn("localhost", "root", "", "DB_NAME");

    $v = "?v=1";

    $prefix = (substr($_SERVER['SERVER_NAME'], -9) == "localhost" ? "http://localhost/" : "http://192.168.137.1/") . "PAGE_DIR";
} else {
    define("DEV", false);

    require_once("php/fx.php");

    $con = new Conn("", "", "", "");

    $v = "?v=1";

    $prefix = "https://example.com/";
}

// Valid subpages
$validPagesDynamic = [
    "logged-out" => [],
    "logged-in" => [],
];

// Will be filled later
$validPages = [];

// Subpages names
$page_names = array();
