<?php
// Site-wide configuration
define("NAME", "HejPanel");
define("DESCRIPTION", "Panel pro rychou a efektivní distribuci informací mezi žáky Gymnázia Hejčín");
define("COLOR", "#FFAB00");

define("DATE_DM_FORMAT", "j. n.");
define("DATE_DMY_FORMAT", "j. n. Y");
define("TIME_HM_FORMAT", "G:i");
define("TIME_HMS_FORMAT", "G:i:s");
define("MYSQL_DATETIME", "Y-m-d H:i:s");

// Define side-wide constants
define('PREFIX', str_replace(["php\conf.php", "php/conf.php"], '', __FILE__));
set_include_path(PREFIX);
define("DEV", (substr($_SERVER['SERVER_NAME'], -9) == "localhost" || substr($_SERVER['SERVER_NAME'], -13) == "192.168.137.1"));

// Include dependencies
require_once(".env.php");
require_once("php/fx.php");

// Environmental settings
if (DEV) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    define("SERVICE_WORKER_ENABLED", false);

    $v = "?v=1";

    $rootDir = (substr($_SERVER['SERVER_NAME'], -9) == "localhost" ? "http://localhost/" : "http://192.168.137.1/");
    $folder = "HejPanel";
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    define("SERVICE_WORKER_ENABLED", true);

    $v = "?v=1";

    $rootDir = "https://krychlic.com/";
    $folder = "hejpanel";
}

$prefix =  $rootDir . $folder;

// Router settings
require_once("php/classes/user.php");
$validPagesPerUserType = [
    UserType::temp->value => ["panel", "submit", "login", "register"],
    UserType::admin->value => ["live", "submit", "current", "review", "archive", "account", "panel"],
    UserType::superadmin->value => ["live", "submit", "current", "review", "archive", "users", "account", "panel"]
];

$pageNames = array(
    "submit" => "Přidat panel",
    "login" => "Přihlášení",
    "register" => "Registrace",
    "live" => "Živý náhled",
    "current" => "Momentálně viditelné",
    "review" => "Čeká na povolení",
    "archive" => "Archiv panelů",
    "account" => "Účet",
    "users" => "Dočasní uživatelé"
);


// Connect to the databases
$con = new Conn(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
