<?php
// Include default dependencies
require_once("./php/conf.php");

// Handle session
require_once("php/session.php");

// Auth logic
$user = User::getUser();

// Required action logic
$actionManager = new ActionManager($user);

if (!$user) {
    $validPages = $validPagesDynamic["logged-out"];
} else {
    $validPages = $validPagesDynamic["logged-in"];
}

// Initiate page manager
$pageManager = new PageManager($validPages, $page_names);

// Initiate module managers
$cssManager = new ModuleManager(ModuleType::CSS, false);
$jsManager = new ModuleManager(ModuleType::JS, false);
$jsManager->defer(true);

if ($pageManager->isNormalRequest) { // Only for initial page load
    $cssManager->require("reset", "fonts", "phone", "transitions", "dialog", "index");
    $jsManager->require("ajax", "index", "api", "transitions");
?>
    <!DOCTYPE html>
    <html lang="cs">

    <head>
        <script>
            const base_url = "<?= $prefix ?>";

            <?php
            Validator::generateJsValues();
            ?>

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register(base_url + '/serviceworker.js?base_url=<?= urlEncode($prefix) ?>&cacheId=<?= substr($v, 3) ?>');
            }
        </script>

        <title><?= $pageManager->pageTitle ?></title>

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="mobile-web-app-capable" content="yes">

        <link rel="manifest" href="<?= $prefix ?>/assets/manifest.json<?= $v ?>">
        <meta name="theme-color" content="#ffffff">
        <meta name="description" content="<?= DESCRIPTION ?>">

        <link rel="icon" href="<?= $prefix ?>/assets/icons/icon.png">

        <?php
        // Include main modules
        $cssManager->fetch();
        $jsManager->fetch();

        // Enable sort for future modules
        $cssManager->sort();
        $jsManager->sort();
        ?>
    </head>

    <body>
    <?php
}
// Initial page load + hydration load
if ($user && !($_SESSION["subscription"] ?? null)) {
    $jsManager->passToJS(["PUBLIC_KEY" => $user->notificationManager::PUBLIC_KEY]);
    $jsManager->require("notifications");
}
    ?>
    <header>
        <?= NAME ?>
    </header>
    <div class="mainWrapper">
        <main>
            <?php
            include($pageManager->pagePath);
            ?>
        </main>
    </div>
    <footer>
    </footer>
    <?php
    if ($pageManager->isNormalRequest) {
        // Fetch dynamic modules (Initial load)
        $cssManager->fetch();
        $jsManager->fetch();
    ?>
    </body>

    </html>
<?php
    } else {
        // Fetch dynamic modules - using js (Hydration load)
        $cssManager->fetch(false);
        $jsManager->fetch(false);
    }
