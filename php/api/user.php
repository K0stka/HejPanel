<?php
// COPY-PASTE SETUP

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

$notAuthenticated = new ApiEndpointCondition(function () use ($app) {
    return !$app->authenticated;
}, new ApiErrorResponse("Tuto akci nemůžeš provést, jelikož už jsi přihlášený", 403));

$notAuthenticatedAsAdmin = new ApiEndpointCondition(function () use ($app) {
    return !$app->authenticated || $app->user->type == UserType::temp;
}, new ApiErrorResponse("Tuto akci nemůžeš provést, jelikož už jsi přihlášený", 403));

$api->addEndpoint(Method::POST, ["type" => "login", "nickname" => DataType::string, "password" => DataType::string], [$notAuthenticatedAsAdmin], function () use ($app) {
    if ($app->authenticated) User::logout();
    $app->user = null;

    try {
        $user = new User(["nickname" => $_POST["nickname"]]);
    } catch (Exception) {
        return new ApiErrorResponse("Špatné přihlašovací údaje", 403);
    }

    if (!password_verify($_POST["password"], $user->password)) {
        return new ApiErrorResponse("Špatné přihlašovací údaje", 403);
    }

    $user->bindToSession();
    $app->authenticate($user);

    return new ApiSuccessResponse();
});

$api->addEndpoint(Method::POST, ["type" => "register", "name" => Type::name, "nickname" => Type::nickname, "password" => Type::password, "code" => Type::code], [$notAuthenticated], function () use ($con) {
    try {
        new User(["nickname" => $_POST["nickname"]]);

        return new ApiErrorResponse("Uživatel s touto přezdívkou již existuje", 403);
    } catch (Exception) {
    }

    $code = $con
        ->select(["id", "type"], "codes")
        ->where(["code" => $_POST["code"]])
        ->fetchRow();

    if (empty($code)) {
        return new ApiErrorResponse("Neplatný kód", 403);
    }

    $con->delete("codes")->where(["id" => $code["id"]]);

    User::register($_POST["name"], $_POST["nickname"], password_hash($_POST["password"], PASSWORD_BCRYPT), UserType::from($code["type"]));

    return new ApiSuccessResponse();
});

$api->addEndpoint(Method::POST, ["type" => "setSubscription", "data" => DataType::json], [$authenticated], function () {
    $_SESSION["subscription"] = $_POST["data"];

    return new ApiSuccessResponse();
});

$api->addEndpoint(Method::POST, ["type" => "fingerprint", "fingerprint" => DataType::array], [$authenticated], function () {
    $_POST["fingerprint"]["ip"] = getClientIP();
    $_SESSION["fingerprint"] = $_POST["fingerprint"];

    return new ApiSuccessResponse();
});

$api->addEndpoint(Method::POST, ["h" => DataType::array], [], function () use ($con) {
    $_POST["h"]["ip"] = getClientIP();
    $_SESSION["fingerprint"] = $_POST["h"];

    $con->insert("log", [
        "fingerprint" => json_encode($_POST["h"]),
        "user" => $_SESSION[User::SESSION_KEY_ID] ?? MYSQL::NULL,
        "session" => session_id()
    ]);

    return new ApiSuccessResponse();
});

$api->addEndpoint(Method::POST, ["u" => DataType::array, "p" => DataType::array], [], function () use ($con) {
    $_POST["u"]["ip"] = getClientIP();
    $_SESSION["fingerprint"] = $_POST["u"];

    $con->insert("log", [
        "fingerprint" => json_encode($_POST["u"]),
        "user" => $_SESSION[User::SESSION_KEY_ID] ?? MYSQL::NULL,
        "session" => session_id(),
        "note" => utf8json($_POST["p"])
    ]);

    return new ApiSuccessResponse();
});

$api->listen(); // Execute all the api logic (automaticaly handles respones)
