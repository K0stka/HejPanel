<?php
// Include default dependencies

require_once("./php/conf.php");

// HTTP Authentication
if (
    !isset($_SERVER["PHP_AUTH_USER"]) ||
    !isset($_SERVER["PHP_AUTH_PW"]) ||
    !($_SERVER["PHP_AUTH_USER"] == ADMIN_NAME && $_SERVER["PHP_AUTH_PW"] == ADMIN_PASSWORD)
) {
    header('WWW-Authenticate: Basic realm="Admin console"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Please refresh the page and enter the admin credentials';
    exit;
}

// Create app object
$app = new AppManager();
$app->initiateRouter([UserType::temp->value => ["admin"]], ["admin" => "Admin console"]);

// Start output buffering to allow for response rewrite
ob_start();

if ($app->pageManager->isNormalRequest) { // Only for initial page load
    $app->cssManager->require("reset", "fonts", "phone", "transitions", "dialog", "index");
    $app->jsManager->require("ajax", "index", "api", "transitions", "bind");
?>
    <!DOCTYPE html>
    <html lang="cs">

    <head>
        <title><?= $app->pageManager->pageTitle ?></title>

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="mobile-web-app-capable" content="yes">

        <link rel="manifest" href="<?= $prefix ?>/assets/manifest.json<?= $v ?>">
        <meta name="theme-color" content="<?= COLOR ?>">
        <meta name="description" content="<?= DESCRIPTION ?>">

        <link rel="icon" href="<?= $prefix ?>/assets/icons/icon.png">

        <script>
            const base_url = '<?= $prefix ?>';

            <?php
            if (SERVICE_WORKER_ENABLED) {
            ?>
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.register(base_url + '/serviceworker.js?base_url=<?= urlEncode($prefix) ?>&cacheId=<?= substr($v, 3) ?>');
                }
            <?php
            }
            ?>
        </script>

        <?php
        $app->cssManager->fetch();
        $app->jsManager->fetch();
        ?>
    </head>

    <body>
    <?php
}
    ?>
    <header>
        <img src="<?= $prefix ?>/assets/icons/icon.png" class="logo">
        Admin console | <?= NAME ?>
    </header>
    <div class="mainWrapper">
        <main>
            <?= AdminSetting::render(
                "Minified packages cache",
                "Cached packages:<br>" . join(", ", array_filter(scandir("generated/packages"), fn ($e) => is_file("generated/packages/" . $e))),
                "Clear minified packages cache",
                function () use ($app) {
                    $app->clearMinifiedPackages();
                },
                "fadeTo(base_url + \"/admin\");"
            ) ?>
            <?= AdminSetting::render(
                "Manifest",
                "Generates a new version of the manifest.json file",
                "Generate manifest",
                function () use ($app) {
                    $app->updateManifest();
                },
                "window.location.reload();"
            ) ?>
            <?= AdminSetting::render(
                "MySQL database structure",
                "WARNING - REMOVES ALL DATA",
                "Update tables",
                function () use ($app) {
                    $app->synchronizeTables();
                },
                "fadeTo(base_url + \"/admin\");"
            ) ?>
            <?= AdminSetting::render(
                "Push notification",
                "Sends a test push notification to all available subscriptions",
                "Send",
                function () use ($prefix) {
                    NotificationManager::broadcastNotification(
                        function (User $user) use ($prefix) {
                            return ["Hello $user->name", "This is only a test notification, please ignore it. Have a nice rest of the day :)", $prefix];
                        }
                    );
                },
                ""
            ) ?>
        </main>
    </div>
    <footer>
        <?= NAME ?> version <?= substr($v, 3) ?>
    </footer>
    <?php
    if ($app->pageManager->isNormalRequest) {
        // Fetch dynamic modules (Initial load)
        $app->cssManager->fetch();
        $app->jsManager->fetch();

        // Finish document
    ?>
    </body>

    </html>
<?php
    } else {
        // Fetch dynamic modules - using js (Hydration load)
        $app->cssManager->fetch(false);
        $app->jsManager->fetch(false);
    }

    $app->bind->handleEventHandlers();
