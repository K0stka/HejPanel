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

$ensureAuth = new ApiEndpointCondition(function () use ($app) {
    $_POST["fingerprint"]["ip"] = getClientIP();

    if (!$app->authenticated) {
        $app->authenticated = true;
        $app->user = User::register("Temp" . substr(strval(time()), -6), "temp" . time(), "", UserType::temp);
        $app->user->bindToSession();
        $app->user->update("lastFingerprint", $_POST["fingerprint"]);
    }

    $_SESSION["fingerprint"] = $_POST["fingerprint"];

    return true;
}, new ApiErrorResponse(""));

$api->addEndpoint(
    Method::POST,
    [
        "type" => "addPanel",
        "show_from" => Type::date,
        "show_till" => Type::date,
        "fingerprint" => DataType::array,
        "panel_type" => array_map(fn ($e) => $e->value, PanelType::cases()),
        "content" => DataType::string,
        "note" => DataType::string
    ],
    [$ensureAuth],
    function () use ($app, $con) {
        $show_from = new DateTime($_POST["show_from"]);
        $show_till = new DateTime($_POST["show_till"]);

        if ($show_from >= $show_till) return new ApiErrorResponse("Neplatný časový rozsah");

        switch ($panelType = PanelType::from($_POST["panel_type"])) {
            case PanelType::image:
                $file = $con->select("id", "files")->where(["id" => $_POST["content"], "uploaded_by" => $app->user->id])->fetchRow();

                if (empty($file)) return new ApiErrorResponse("Nahraný soubor nebylo možné nalézt.");

                $_POST["content"] = $file["id"];
                break;
            case PanelType::text:
                $_POST["content"] = escapeConservative($_POST["content"], true);
                break;
        }

        if ($app->user->type == UserType::temp) {
            $panel = new Panel(
                [
                    "postedBy" => $app->user->id,
                    "showFrom" => $_POST["show_from"] . " 00:00:00",
                    "showTill" => $_POST["show_till"] . " 23:59:59",
                    "type" => $panelType->value,
                    "content" => $_POST["content"],
                    "note" => escapeConservative($_POST["note"], true)
                ],
                true
            );
        } else {
            $panel = new Panel(
                [
                    "postedBy" => $app->user->id,
                    "approved" => true,
                    "approvedBy" => $app->user->id,
                    "approvedAt" => date(MYSQL_DATETIME),
                    "showFrom" => $_POST["show_from"] . " 00:00:00",
                    "showTill" => $_POST["show_till"] . " 23:59:59",
                    "type" => $panelType->value,
                    "content" => $_POST["content"],
                    "note" => escapeConservative($_POST["note"], true)
                ],
                true
            );
        }

        $panel->insert();

        return new ApiSuccessResponse();
    }
);

// Get all visible panels with ids
$api->addEndpoint(Method::GET, ["i" => DataType::int_array], [], function () {
    $panels = Panel::getPanelsByIds($_GET["i"]);
    if (empty($panels)) $panels = [Panel::getEmptyPanel()];
    $response = new ApiResponse(array_map(fn ($e) => $e->serialize(), $panels));
    $response->cache(3600, true);
    $response->send();
});

// Get ids of all currently visible panels
$api->addEndpoint(Method::GET, ["t" => "c"], [], function () {
    $panelIds = Panel::getVisiblePanelsIds();
    if (empty($panelIds)) $panelIds = [Panel::getEmptyPanel()->id];
    return new ApiResponse($panelIds);
});

// Get current Jidelna status
$api->addEndpoint(Method::GET, ["j" => null], [], function () {
    $response = new ApiSuccessResponse((new Jidelna())->fetchDay(new DateTime()));
    $tomorrow00 = strtotime("tomorrow");
    $response->cache($tomorrow00 - time() - 1, true);
    $response->LastModified($tomorrow00 - 86401);
    $response->send();
});

$api->listen(); // Execute all the api logic (automaticaly handles respones)
