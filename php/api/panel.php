<?php
// COPY-PASTE SETUP

require_once("../conf.php");
require_once("php/session.php");
require_once("php/api/src/api.php");
require_once("php/classes/panel.php");

$api = new Api(); // Initiate api instance

// Create app object
$app = new AppManager();
$app->authenticate(User::getUser());

$authenticated = new ApiEndpointCondition(function () use ($app) {
    return $app->user != false;
}, new ApiErrorResponse("Pro dokončení této akce musíš být přihlášený", 403));

$isAdmin = new ApiEndpointCondition(function () use ($app) {
    return $app->user->type == UserType::admin || $app->user->type == UserType::superadmin;
}, new ApiErrorResponse("Nemáš dostatečná práva pro provedení této akce", 403));

$api->addEndpoint(Method::POST, ["type" => "addPanel", "show_from" => Type::date, "show_till" => Type::date, "fingerprint" => DataType::array, "panel_type" => array_map(fn ($e) => $e->value, PanelType::cases()), "content" => DataType::string, "note" => DataType::string], [], function () use ($app, $con) {
    $_POST["fingerprint"]["ip"] = getClientIP();

    if ($app->user == false) {
        $app->user = new User(User::register("Temp" . substr(strval(time()), -6), "temp" . time(), "", UserType::temp));
        User::login($app->user->id);
        $app->user->update("lastFingerprint", $_POST["fingerprint"]);
    }

    $_SESSION["fingerprint"] = $_POST["fingerprint"];

    $show_from = new DateTime($_POST["show_from"]);
    $show_till = new DateTime($_POST["show_till"]);

    if ($show_from >= $show_till) return new ApiErrorResponse("Neplatný časový rozsah");

    switch ($panelType = PanelType::from($_POST["panel_type"])) {
        case PanelType::image:
            $fileExists = $con->select("id", "files")->where(["name" => $_POST["content"]])->fetchRow();
            if (empty($fileExists)) return new ApiErrorResponse("Nahraný soubor nebylo možné nalézt.");
            break;
        case PanelType::text:
            $_POST["content"] = escapeConservative($_POST["content"], true);
            break;
    }

    $panel = new Panel(
        [
            "postedBy" => $app->user->id,
            "showFrom" => $_POST["show_from"] . " 00:00:00",
            "showTill" => $_POST["show_till"] . " 00:00:00",
            "type" => $panelType->value,
            "content" => $_POST["content"],
            "note" => escapeConservative($_POST["note"], true)
        ],
        true
    );

    $panel->insert();

    return new ApiSuccessResponse();
});

// Get all visible panels
$api->addEndpoint(Method::GET, ["t" => "a"], [], function () {
    $panels = Panel::getVisiblePanels();
    if (empty($panels)) $panels = [Panel::getEmptyPanel()];
    return new ApiResponse(["data" => array_map(fn ($e) => $e->serialize(), $panels)]);
});

// Get all visible panels with ids
$api->addEndpoint(Method::GET, ["t" => "b", "ids" => DataType::int_array], [], function () {
    $panels = Panel::getPanelsByIds($_GET["ids"]);
    if (empty($panels)) $panels = [Panel::getEmptyPanel()];
    return new ApiResponse(["data" => array_map(fn ($e) => $e->serialize(), $panels)]);
});

// Get ids of all currently visible panels
$api->addEndpoint(Method::GET, ["t" => "c"], [], function () {
    $panelIds = Panel::getVisiblePanelsIds();
    if (empty($panelIds)) $panelIds = [Panel::getEmptyPanel()->id];
    return new ApiResponse($panelIds);
});

$api->listen(); // Execute all the api logic (automaticaly handles respones)

// Optional - will only trigger if no endpoint was triggered, because sending a response stops further code from running
$response = new ApiErrorResponse("Nebylo odesláno dostatek vstupů");
$response->send();
