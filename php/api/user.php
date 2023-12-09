<?php
// COPY-PASTE SETUP

require_once("../conf.php");
require_once("php/session.php");
require_once("php/api/src/api.php");

$api = new Api(); // Initiate api instance

$user = User::getUser();

$authenticated = new ApiEndpointCondition(function () use ($user) {
    return $user != false;
}, new ApiErrorResponse("Pro dokončení této akce musíš být přihlášený", 403));

$notAuthenticated = new ApiEndpointCondition(function () use ($user) {
    return $user == false;
}, new ApiErrorResponse("Tuto akci nemůžeš provést, jelikož už jsi přihlášený", 403));

$api->addEndpoint(Method::POST, ["type" => "login", "nickname" => DataType::string, "password" => DataType::string], [$notAuthenticated], function () {
    $user = new User(["nickname" => $_POST["nickname"]]);

    if (!$user->exists) {
        return new ApiErrorResponse("Špatné přihlašovací údaje", 403);
    }

    if (!password_verify($_POST["password"], $user->password)) {
        return new ApiErrorResponse("Špatné přihlašovací údaje", 403);
    }

    User::login($user->id);

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

    $code = $con->query("SELECT id, type FROM codes WHERE code = ", [$_POST["code"]])->fetchRow();

    if (empty($code)) {
        return new ApiErrorResponse("Neplatný kód", 403);
    }

    $con->query("DELETE FROM codes WHERE id = ", [$code["id"]]);

    User::register($_POST["name"], $_POST["nickname"], password_hash($_POST["password"], PASSWORD_BCRYPT), UserType::from($code["type"]));

    return new ApiSuccessResponse();
});

$api->addEndpoint(Method::POST, ["type" => "setSubscription", "data" => DataType::json], [$authenticated], function () use ($con, $user) {
    $_SESSION["subscription"] = $_POST["data"];

    return new ApiSuccessResponse();
});

$api->addEndpoint(Method::POST, ["type" => "fingerprint", "fingerprint" => DataType::array], [$authenticated], function () use ($con, $user) {
    $_SESSION["fingerprint"] = $_POST["fingerprint"];
    $_POST["fingerprint"]["ip"] = getUserIP();

    return new ApiSuccessResponse();
});

$api->listen(); // Execute all the api logic (automaticaly handles respones)

// Optional - will only trigger if no endpoint was triggered, because sending a response stops further code from running
$response = new ApiErrorResponse("Nebylo odesláno dostatek vstupů");
$response->send();
