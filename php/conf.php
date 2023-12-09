<?php
// Product details
define("NAME", "HejPanel");
define("DESCRIPTION", "Panel pro rychou a efektivní distribuci informací mezi žáky Gymnázia Hejčín");

define("DATE_DM_FORMAT", "j. n.");
define("DATE_DMY_FORMAT", "j. n. Y");
define("TIME_HM_FORMAT", "G:i");
define("TIME_HMS_FORMAT", "G:i:s");

define('PREFIX', str_replace(["php\conf.php", "php/conf.php"], '', __FILE__));
set_include_path(PREFIX);

// Database, prefix
if (substr($_SERVER['SERVER_NAME'], -9) == "localhost" || substr($_SERVER['SERVER_NAME'], -13) == "192.168.137.1") {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    define("DEV", true);
    define("SERVICE_WORKER_ENABLED", false);

    require_once("php/fx.php");

    $con = new Conn("localhost", "root", "", "hejpanel");

    $v = "?v=1";

    $prefix = (substr($_SERVER['SERVER_NAME'], -9) == "localhost" ? "http://localhost/" : "http://192.168.137.1/") . "HejPanel";
} else {
    define("DEV", false);
    define("SERVICE_WORKER_ENABLED", true);

    require_once("php/fx.php");

    $con = new Conn("", "", "", "");

    $v = "?v=1";

    $prefix = "https://example.com/";
}

// Valid subpages
$validPagesDynamic = [
    "logged-out" => [
        "panel" => "",
        "submit" => "",
        "login" => "",
        "register" => ""
    ],
    "logged-in" => [
        "live" => "",
        "current" => "",
        "review" => "",
        "archive" => "",
        "account" => "",
        "panel" => ""
    ],
    "logged-in-superadmin" => [
        "live" => "",
        "current" => "",
        "review" => "",
        "archive" => "",
        "users" => "",
        "account" => "",
        "panel" => ""
    ]
];

// Will be filled later
$validPages = [];

// Subpages names
$pageNames = array(
    "submit" => "Přidat panel",
    "login" => "Přihlášení",
    "register" => "Registrace",
    "live" => "Živý náhled",
    "current" => "Momentálně viditelné",
    "review" => "Čeká na povolení",
    "archive" => "Archiv panelů",
    "account" => "Účet",
    "users" => "Uživatelé"
);
