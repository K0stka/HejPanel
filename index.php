<?php
// Include default dependencies
require_once("./php/conf.php");

// Handle session
require_once("php/session.php");

// Auth logic
$user = User::getUser();

if ($user) {
    if ($user->type == UserType::admin) {
        $validPages = $validPagesDynamic["logged-in"];
    } else {
        $validPages = $validPagesDynamic["logged-in-superadmin"];
    }
} else {
    $validPages = $validPagesDynamic["logged-out"];
}

// Initiate page manager
$pageManager = new PageManager($validPages, $pageNames);

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
if ($user && !($_SESSION["fingerprint"] ?? null)) {
    $jsManager->require("fingerprint");
}
if ($pageManager->page == "panel") {
    include($pageManager->pagePath);
} else {
    ?>
        <header>
            <?= NAME ?>
        </header>
        <div class="mainWrapper">
            <?php
            if ($user) {
            ?>
                <nav>
                    <?php
                    foreach ($validPages as $pageIndex => $query) {
                    ?>
                        <a href="<?= $prefix ?>/<?= $pageIndex ?>" class="navBtn<?= ($pageIndex == $pageManager->page ? " active" : "") ?>" data-hierarchy="0" data-direction="-1"><?= $pageNames[$pageIndex] ?></a>
                    <?php
                    }
                    ?>
                </nav>
            <?php
            }
            ?>
            <main <?= ($user ? "class=\"shrinkForNav\"" : "") ?>>
                <?php
                include($pageManager->pagePath);
                ?>
            </main>
        </div>
        <footer>
            <span>
                Upozornění: Z důvodu zabránění spamu logujeme otisk Vašeho zařízení.
            </span>
            <span>
                V případě problémů/dotazů prosím kontaktujte&nbsp;<a href="mailto:kostkaj@gytool.cz">kostkaj@gytool.cz</a>
            </span>
        </footer>
    <?php
}
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
