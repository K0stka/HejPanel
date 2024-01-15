<h1>Momentálně viditelné</h1>
<button <?= $app->bind->onClick(
            function () use ($forceReload) {
                $str = file_get_contents('php/conf.php');

                $str = str_replace("\$forceReload = $forceReload;", "\$forceReload = " . ($forceReload + 1) . ";", $str);

                file_put_contents('php/conf.php', $str);
            },
            "forceReload"
        )->then(CREATE_MODAL("Požadavek odeslán úspěšně", "Do zhruba 30s budou všichni aktivní klienti obnoveni")) ?>>
    Obnovit klienty
</button><br><br>
<?php

$panels = Panel::getVisiblePanels();

$fingerprintMap = User::getFingerprintToUsersMap();

foreach ($panels as $panel) {
?>
    <?= PanelReview::render($panel, $fingerprintMap, function () use ($app, $panel) {
        if ($panel->showOverride != ShowOverride::null) {
    ?>
            <button <?= $app->bind->onClick(function () use ($panel) {
                        $panel->update("showOverride", ShowOverride::null);
                    }, "c-p:" . $panel->id)->then(RELOAD()) ?>>
                Zrušit přepsání viditelnosti</button>
        <?php
        }
        ?>
        <button class="danger" <?= $app->bind->onClick(function () use ($panel) {
                                    $panel->update("showOverride", ShowOverride::hide);
                                }, "h-p:" . $panel->id)->then(RELOAD()) ?>>Skrýt</button>
    <?php
    }) ?>
<?php
}

if (empty($panels)) {
?>
    <div class="note">
        Nejsou aktivní žádné panely, zobrazuji hlášku.
    </div>
<?php
}
$app->jsManager->require("panel");
