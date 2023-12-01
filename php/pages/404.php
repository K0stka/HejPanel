<?php
// Include default dependencies
require_once("../conf.php");
$cssManager = new ModuleManager(ModuleType::CSS, false);
$jsManager = new ModuleManager(ModuleType::JS, false);

$cssManager->require("reset", "fonts", "phone", "transitions", "dialog", "index");
$jsManager->require("ajax", "index", "api", "transitions");
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

    <title><?= NAME ?></title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="mobile-web-app-capable" content="yes">

    <link rel="manifest" href="<?= $prefix ?>/assets/manifest.json">
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
    <header>
        <?= NAME ?>
    </header>
    <div class="mainWrapper">
        <main>
            <div>Chyba 404</div>
            Nejsme schopni kontaktovat server, zkontrolujte prosím připojení k internetu a zkuste to znovu.<br><br>
            (Požadovaná adresa: <?= "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>)<br>
            (Technické specifikace požadavku: <?= utf8json($_GET) ?>)
        </main>
    </div>
    <footer>
        <a href="<?= $prefix ?>/" data-hierarchy="0">
            Obnovit stránku
        </a>
    </footer>
</body>

</html>