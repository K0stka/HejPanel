<?php
// Include default dependencies
require_once("../../php/conf.php");

$app = new AppManager();
$app->initiateRouter([UserType::temp->value => [""]], []);

$app->cssManager->require("reset", "fonts", "transitions", "dialog", "index", "phone");
$app->jsManager->require("ajax", "util", "index", "api", "transitions", "bind");
?>
<!DOCTYPE html>
<html lang="cs">

<head>
    <script re="true">
        const base_url = "<?= $prefix ?>";

        <?php
        if (!DEV) {
        ?>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register(base_url + '/serviceworker.js?base_url=<?= urlEncode($prefix) ?>&cacheId=<?= substr($v, 3) ?>');
            }
        <?php
        }
        ?>
    </script>

    <title>OFFLINE | <?= NAME ?></title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="mobile-web-app-capable" content="yes">

    <link rel="manifest" href="<?= $prefix ?>/assets/manifest.json">
    <meta name="theme-color" content="<?= COLOR ?>">
    <meta name="description" content="<?= DESCRIPTION ?>">

    <link rel="icon" href="<?= $prefix ?>/assets/icons/icon.png">

    <?php
    // Include main modules
    $app->cssManager->fetch();
    $app->jsManager->fetch();
    ?>
</head>

<body>
    <header>
        <img src="<?= $prefix ?>/assets/icons/icon.png" class="logo">
        <?= NAME ?>
    </header>
    <div class="mainWrapper">
        <main>
            <h1>Jsi offline</h1>
            Prosím zkontroluj svůl přístup k internetu a zkus to znovu.
        </main>
    </div>
    <footer>
        <a href="<?= $prefix ?>">
            Obnovit stránku
        </a>
    </footer>
</body>

</html>