<?php
require_once("../php/conf.php");
require_once("php/classes/moduleManager.php");

if (!isset($_GET["modules"]) || !isset($_GET["type"]) || ModuleType::tryFrom($_GET["type"]) == null) {
    http_response_code(400);
    exit;
}

$moduleManager = new ModuleManager(ModuleType::from($_GET["type"]), false);

$moduleManager->require(...explode("-", $_GET["modules"]));

// To allow better integration with DevTools
if (count($moduleManager->files) == 1 && DEV && file_exists("./" . $moduleManager->modulesDir . $moduleManager->files[0] . "." . $moduleManager->type->value)) {
    // Return unminified module
    header('Content-Type: text/' . ($moduleManager->type == ModuleType::CSS ? "css" : "javascript"));
    readfile(PREFIX . "generated/" . $moduleManager->modulesDir . $moduleManager->files[0] . "." . $moduleManager->type->value, true);
    die();
}

// Return package
header("Content-Type: text/" . ($moduleManager->type == ModuleType::CSS ? "css" : "javascript"));
header("Cache-Control: public, max-age=86400");

// echo ("/* Minified with the help of https://github.com/matthiasmullie/minify */\n");
echo ("/* Pack version: " . substr($v, 3) . " */\n");
$moduleManager->generate();
