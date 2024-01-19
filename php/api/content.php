<?php
require_once("../conf.php");
require_once("php/session.php");
require_once("php/api/src/api.php");

$api = new Api(); // Initiate api instance

// Create app object
$app = new AppManager();
$app->authenticate(User::getUser());

$missingFile = new ApiFileResponse("/assets/images/missingPanel.svg", "Missing panel.svg");
$missingFile->cacheWEtag(1, 0, true);

$authenticated = new ApiEndpointCondition(function () use ($app) {
    return $app->authenticated;
}, new ApiErrorResponse("Pro dokončení této akce musíš být přihlášený", 403));

$ensureAuth = new ApiEndpointCondition(function () use ($app) {
    $_POST["fingerprint"] = json_decode($_POST["fingerprint"], true);

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

$api->addEndpoint(Method::GET, [], [], function () use ($missingFile, $con, $app) {
    if (empty($_GET) || count($_GET) > 1 || $_GET[array_key_first($_GET)] != "") return $missingFile;

    $id = array_key_first($_GET);

    if (!is_numeric($id)) return $missingFile;

    try {
        $panel = new Panel(intval($id));
    } catch (Exception) {
        return $missingFile;
    }

    if ($panel->type != PanelType::image) return $missingFile;

    if (!$panel->isVisible() && !in_array($app->user->type, [UserType::admin, UserType::superadmin])) return $missingFile;

    $file = $con->select(["file"], "files")->where(["id" => $panel->content])->fetchRow();

    $response = new ApiFileResponse("uploads/" . $file["file"], "Panel " . $panel->id . ".webp");
    $response->cacheWEtag(1, 1, true);
    $response->send();
});

$api->addFileUploadEndpoint(["fingerprint" => DataType::json], [$ensureAuth], new ApiFileUploadConfiguration(
    saveName: fn ($index, $count) => "content_" . $con->select("auto_increment", "INFORMATION_SCHEMA.TABLES")->where(["table_schema" => "hejpanel", "table_name" => "files"])->fetchValue(),
    maxFileSizeMB: 20,
    allowedFileTypes: AUTOCONVERTIBLE_IMAGES,
    imageSaveAs: FileType::webp,
    savePath: "uploads/",
    onAfterUpload: fn ($newFilePath, $index, $count) => new ApiSuccessResponse($con->insert("files", ["uploaded_by" => $app->user->id, "file" => basename($newFilePath)])),
));

$api->listen(); // Execute all the api logic (automaticaly handles respones)
