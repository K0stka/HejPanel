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

    $user = new User(["nickname" => $_POST["nickname"]]);

    if (!$user->exists) {
        return new ApiErrorResponse("Špatné přihlašovací údaje", 403);
    }

    if (!password_verify($_POST["password"], $user->password)) {
        return new ApiErrorResponse("Špatné přihlašovací údaje", 403);
    }

    User::login($user->id);
    $app->authenticate($user);

    return new ApiSuccessResponse();
});

$api->addEndpoint(Method::POST, ["type" => "logout"], [$authenticated], function () {
    User::logout();

    return new ApiSuccessResponse();
});

$api->addEndpoint(Method::POST, ["type" => "register", "name" => Type::name, "nickname" => Type::nickname, "password" => Type::password, "code" => Type::code], [$notAuthenticated], function () use ($con) {
    if ((new User(["nickname" => $_POST["nickname"]]))->exists) {
        return new ApiErrorResponse("Uživatel s touto přezdívkou již existuje", 403);
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

$api->listen(); // Execute all the api logic (automaticaly handles respones)

// Optional - will only trigger if no endpoint was triggered, because sending a response stops further code from running
$response = new ApiErrorResponse("Nebylo odesláno dostatek vstupů");
$response->send();
