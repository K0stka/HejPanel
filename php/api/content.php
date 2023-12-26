<?php
require_once("../conf.php");
require_once("php/session.php");
require_once("php/api/src/api.php");

$api = new Api(); // Initiate api instance

// Create app object
$app = new AppManager();
$app->authenticate(User::getUser());

$authenticated = new ApiEndpointCondition(function () use ($app) {
    return $app->authenticated;
}, new ApiErrorResponse("Pro dokončení této akce musíš být přihlášený", 403));

$api->addEndpoint(Method::GET, ["panel_id" => DataType::int], [], function () {
    try {
        $panel = new Panel(intval($_GET["panel_id"]));

        if ($panel->type != PanelType::image) throw new Exception();
    } catch (Exception) {
        return new ApiFileResponse(PREFIX . "/assets/images/missingPanel.svg", "Missing panel.svg");
    }

    return new ApiFileResponse(PREFIX . "/uploads/panels/" . $panel->content, "Panel_" . $panel->id . ".webp");
});

$api->listen(); // Execute all the api logic (automaticaly handles respones)

// Optional - will only trigger if no endpoint was triggered, because sending a response stops further code from running
$response = new ApiErrorResponse("Nebylo odesláno dostatek vstupů");
$response->send();
