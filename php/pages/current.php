<h1>Momentálně viditelné</h1>

<?php

$panels = Panel::getVisiblePanels();

$fingerprintMap = User::getFingerprintToUsersMap();

foreach ($panels as $panel) {
?>
    <?= PanelReview::render($panel, $fingerprintMap, function () use ($app, $panel) {
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
