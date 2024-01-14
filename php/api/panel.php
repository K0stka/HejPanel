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
        "url" => Type::nullableUrl,
        "note" => DataType::string
    ],
    [$ensureAuth],
    function () use ($app, $con) {
        $show_from = new DateTime($_POST["show_from"]);
        $show_till = new DateTime($_POST["show_till"]);

        if ($show_from > $show_till) return new ApiErrorResponse("Neplatný časový rozsah");

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

        $panel = new Panel(
            [
                "postedBy" => $app->user->id,
                "showFrom" => $_POST["show_from"] . " 00:00:00",
                "showTill" => $_POST["show_till"] . " 23:59:59",
                "type" => $panelType->value,
                "content" => $_POST["content"],
                "url" => $_POST["url"] != "" ? $_POST["url"] : null,
                "note" => escapeConservative($_POST["note"], true)
            ],
            true
        );

        if ($app->user->type != UserType::temp) {
            $panel->approved = true;
            $panel->approvedBy = $app->user;
            $panel->approvedAt = new DateTime();
        }

        $panel->insert();

        return new ApiSuccessResponse();
    }
);

$api->addEndpoint(Method::GET, ["i" => DataType::int_array], [], function () {
    $panelIds = Panel::getVisiblePanelsIds();
    if (empty($panelIds)) $panelIds = [Panel::getEmptyPanel()->id];

    $clientHas = array_map(fn ($e) => intval($e), $_GET["i"]);

    $panelIdsToAdd = array_diff($panelIds, $clientHas);
    $panelIdsToRemove = array_diff($clientHas, $panelIds);

    if ($panelIdsToAdd != [-1])
        $panelsToAdd = array_map(fn ($e) => $e->serializePanel(), Panel::getPanelsByIds($panelIdsToAdd));
    else
        $panelsToAdd = [Panel::getEmptyPanel()->serializePanel()];

    return new ApiResponse(["a" => array_values($panelsToAdd), "r" => array_values($panelIdsToRemove)]);
});

// Get current Jidelna status
$api->addEndpoint(Method::GET, ["j" => null], [], function () {
    $response = new ApiSuccessResponse((new Jidelna())->fetchDay(new DateTime("today")));
    $tomorrow00 = strtotime("tomorrow");
    $response->cache($tomorrow00 - time() - 1, true);
    $response->LastModified($tomorrow00 - 86401);
    $response->send();
});

$api->listen(); // Execute all the api logic (automaticaly handles respones)
