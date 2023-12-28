<?php
require_once("../conf.php");
require_once("php/session.php");
require_once("php/api/src/api.php");

$api = new Api(); // Initiate api instance

// Create app object
$app = new AppManager();
$app->authenticate(User::getUser());

$missingFile = new ApiFileResponse(PREFIX . "/assets/images/missingPanel.svg", "Missing panel.svg");

$authenticated = new ApiEndpointCondition(function () use ($app) {
    return $app->authenticated;
}, new ApiErrorResponse("Pro dokončení této akce musíš být přihlášený", 403));

$api->addEndpoint(Method::GET, [], [], function () use ($missingFile) {
    if (empty($_GET) || count($_GET) > 1 || $_GET[array_key_first($_GET)] != "") return $missingFile;

    $id = array_key_first($_GET);

    if (!is_numeric($id)) return $missingFile;

    try {
        $panel = new Panel(intval($id));
    } catch (Exception) {
        return $missingFile;
    }

    if ($panel->type != PanelType::image) return $missingFile;

    if (!$panel->showOverride && (!$panel->approved || $panel->showTill < new DateTime() || $panel->showFrom > new DateTime())) return $missingFile;

    return new ApiFileResponse(PREFIX . "/uploads/panels/" . $panel->content, "Panel_" . $panel->id . ".webp");
});

$api->listen(); // Execute all the api logic (automaticaly handles respones)

// Optional - will only trigger if no endpoint was triggered, because sending a response stops further code from running
$response = new ApiErrorResponse("Nebylo odesláno dostatek vstupů");
$response->send();
