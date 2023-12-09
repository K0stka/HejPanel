<?php
// COPY-PASTE SETUP

require_once("../conf.php");
require_once("php/session.php");
require_once("php/api/src/api.php");
require_once("php/classes/panel.php");

$api = new Api(); // Initiate api instance

$user = User::getUser();

$authenticated = new ApiEndpointCondition(function () use ($user) {
    return $user != false;
}, new ApiErrorResponse("Pro dokončení této akce musíš být přihlášený", 403));

$isAdmin = new ApiEndpointCondition(function () use ($user) {
    return $user->type == UserType::admin || $user->type == UserType::superadmin;
}, new ApiErrorResponse("Nemáš dostatečná práva pro provedení této akce", 403));

$api->addEndpoint(Method::POST, ["type" => "addPanel", "show_from" => Type::date, "show_till" => Type::date, "fingerprint" => DataType::array, "panel_type" => array_map(fn ($e) => $e->value, PanelType::cases()), "content" => DataType::string, "note" => DataType::string], [], function () use ($user, $con) {
    $_POST["fingerprint"]["ip"] = getUserIP();
    if ($user == false) {
        $user = new User(User::register("Temp" . substr(strval(time()), -6), "temp" . time(), "", UserType::temp));
        User::login($user->id);
        $user->updateLastFingerprint($_POST["fingerprint"]);
    }

    $_SESSION["fingerprint"] = $_POST["fingerprint"];

    $show_from = new DateTime($_POST["show_from"]);
    $show_till = new DateTime($_POST["show_till"]);

    if ($show_from >= $show_till) return new ApiErrorResponse("Neplatný časový rozsah");

    switch ($panelType = PanelType::from($_POST["panel_type"])) {
        case PanelType::image:
            $fileExists = $con->query("SELECT id FROM files WHERE name = ", [$_POST["content"]])->fetchRow();
            if (empty($fileExists)) return new ApiErrorResponse("Nahraný soubor nebylo možné nalézt.");
            break;
        case PanelType::text:
            $_POST["content"] = escapeConservative($_POST["content"], true);
            break;
    }

    $con->query("INSERT INTO panels (posted_by, show_from, show_till, type, content, note) VALUES (", [$user->id], ", ", [$_POST["show_from"]], ", ", [$_POST["show_till"]], ", ", [$panelType->value], ", ", [$_POST["content"]], ", ", [escapeConservative($_POST["note"], true)], ")");

    return new ApiSuccessResponse();
});

$api->listen(); // Execute all the api logic (automaticaly handles respones)

// Optional - will only trigger if no endpoint was triggered, because sending a response stops further code from running
$response = new ApiErrorResponse("Nebylo odesláno dostatek vstupů");
$response->send();
