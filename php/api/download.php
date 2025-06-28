<?php
require_once ("../conf.php");
require_once ("php/session.php");
require_once ("php/api/src/api.php");

$api = new Api(); // Initiate api instance

// Create app object
$app = new AppManager();
$app->authenticate(User::getUser());

$missingFile = new ApiFileResponse("/assets/images/missingPanel.svg", "Missing panel.svg");
$missingFile->cacheWEtag(1, 0, true);

$api->addEndpoint(Method::GET, [], [], function () use ($missingFile, $con, $app) {
    if (empty ($_GET) || count($_GET) > 1 || $_GET[array_key_first($_GET)] != "")
        return $missingFile;

    $id = array_key_first($_GET);

    if (!is_numeric($id))
        return $missingFile;

    try {
        $panel = new Panel(intval($id));
    } catch (Exception) {
        return $missingFile;
    }

    if ($panel->type != PanelType::image)
        return $missingFile;

    if (!$panel->isVisible() && !in_array($app->user->type, [UserType::admin, UserType::superadmin]))
        return $missingFile;

    $file = $con->select(["file"], "files")->where(["id" => $panel->content])->fetchRow();

    $response = new ApiFileResponse("uploads/" . $file["file"], "Panel " . $panel->id . ".webp", FileResponseMode::Download);
    $response->cacheWEtag(1, 1, true);
    $response->send();
});

$api->listen(); // Execute all the api logic (automaticaly handles respones)
