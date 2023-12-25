<h1>Archiv panelů</h1>

<?php

$panels = Panel::getExpiredPanels();

$fingerprintMap = User::getFingerprintToUsersMap();

foreach ($panels as $panel) {
?>
    <?= PanelReview::render($panel, $fingerprintMap, function () use ($app, $panel) {
    ?>
        <button <?= $app->bind->onClick(function () use ($panel) {
                    $panel->update("showOverride", ShowOverride::show);
                })->then(RELOAD) ?>>
            Zobrazit</button>
    <?php
    }) ?>
<?php
}

if (empty($panels)) {
?>
    <div class="note">
        Ještě zde nejsou žádné archivované panely.
    </div>
<?php
}
$app->jsManager->require("panel");
