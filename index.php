<?php
// Include default dependencies
require_once("./php/conf.php");

// Handle session
require_once("php/session.php");

// Create app object
$app = new AppManager();
$app->authenticate(User::getUser());
$app->initiateRouter($validPagesPerUserType, $pageNames);

// Start output buffering to allow for response rewrite
ob_start();

if ($app->pageManager->isNormalRequest) { // Only for initial page load
    $app->cssManager->require("reset", "fonts", "transitions", "dialog", "index", "phone");
    $app->jsManager->require("ajax", "util", "index", "api", "transitions", "bind");
?>
    <!DOCTYPE html>
    <html lang="cs">

    <head>
        <script>
            const base_url = "<?= $prefix ?>";
            const PUBLIC_KEY = "<?= PUBLIC_KEY ?>";

            <?php
            Validator::generateJsValues();
            ?>

            <?php
            if (SERVICE_WORKER_ENABLED) {
            ?>
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.register(base_url + '/serviceworker.js?base_url=<?= urlEncode($prefix) ?>&cacheId=<?= substr($v, 3) ?>');

                    const channel = new BroadcastChannel("notifications");
                    channel.addEventListener("message", (event) => {
                        createModal(event.data.title, event.data.body + '<br><button onclick="fadeTo(\'' + event.data.url + '\')">Otevřít</button>');
                    });

                    document.addEventListener("visibilitychange", () => {
                        navigator.serviceWorker.controller?.postMessage({
                            hidden: document.hidden,
                        });
                    });
                }
            <?php
            }
            ?>
        </script>

        <title><?= $app->pageManager->pageTitle ?></title>

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="mobile-web-app-capable" content="yes">

        <link rel="manifest" href="<?= $prefix ?>/assets/manifest.json<?= $v ?>">
        <meta name="theme-color" content="<?= COLOR ?>">
        <meta name="description" content="<?= DESCRIPTION ?>">

        <link rel="icon" href="<?= $prefix ?>/assets/icons/icon.png">

        <?php
        // Include main modules
        $app->cssManager->fetch();
        $app->jsManager->fetch();

        // Enable sort for future modules
        $app->cssManager->sort();
        $app->jsManager->sort();
        ?>
    </head>

    <body>
    <?php
}
if ($app->pageManager->page == "panel") {
    include($app->pageManager->pagePath);
} else {
    if ($app->authenticated && $app->user->type != UserType::temp && !($_SESSION["subscription"] ?? null)) {
        $app->jsManager->require("notifications");
    }
    if ($app->authenticated && !($_SESSION["fingerprint"] ?? null)) {
        $app->jsManager->require("fingerprint");
    }
    ?>
        <header>
            <img src="<?= $prefix ?>/assets/icons/icon.png" class="logo">
            <?= NAME ?>
        </header>
        <div class="mainWrapper">
            <?php
            if ($app->user && $app->user->type != UserType::temp) {
            ?>
                <nav>
                    <?php
                    foreach ($app->pageManager->validPages as $pageIndex => $query) {
                        if ($pageIndex == "panel") continue;
                    ?>
                        <a href="<?= $prefix ?>/<?= $pageIndex ?>" class="navBtn<?= ($pageIndex == $app->pageManager->page ? " active" : "") ?>">
                            <?= $pageNames[$pageIndex] ?>
                            <?php if ($pageIndex == "review" && ($count = Panel::countWaitingPanels()) > 0) { ?> <div class="notification"> <?= $count ?> </div> <?php } ?>
                        </a>
                    <?php
                    }
                    ?>
                </nav>
            <?php
            }
            ?>
            <main <?= ($app->user && $app->user->type != UserType::temp ? "class=\"shrinkForNav\"" : "") ?>>
                <?php
                include($app->pageManager->pagePath);
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
