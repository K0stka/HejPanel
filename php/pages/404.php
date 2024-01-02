<?php
// Include default dependencies
require_once("../conf.php");

$app = new AppManager();
$app->initiateRouter([UserType::temp->value => [""]], []);

$app->cssManager->require("reset", "fonts", "transitions", "dialog", "index", "phone");
$app->jsManager->require("ajax", "util", "index", "api", "transitions", "bind");
?>
<!DOCTYPE html>
<html lang="cs">

<head>
    <script>
        const base_url = "<?= $prefix ?>";

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register(base_url + '/serviceworker.js?base_url=<?= urlEncode($prefix) ?>&cacheId=<?= substr($v, 3) ?>');
        }
    </script>

    <title>404 | <?= NAME ?></title>

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
            <h1>Chyba 404</h1>
            Nejsme schopni kontaktovat server, zkontrolujte prosím připojení k internetu a zkuste to znovu.<br><br>
            (Požadovaná adresa: <?= "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>)<br>
            (Technické specifikace požadavku: <?= utf8json($_GET) ?>)
        </main>
    </div>
    <footer>
        <a href="<?= $prefix ?>/">
            Obnovit stránku
        </a>
    </footer>
</body>

</html>