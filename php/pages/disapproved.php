<h1>Zamítnuté panely</h1>

<?php

$panels = Panel::getDisapprovedPanels();

$fingerprintMap = User::getFingerprintToUsersMap();

foreach ($panels as $panel) {
?>
    <?= PanelReview::render($panel, $fingerprintMap, function () use ($app, $panel) {
    ?>
        <button <?= $app->bind->onClick(function () use ($panel) {
                    $panel->update("showOverride", ShowOverride::show);
                }, "s-p:" . $panel->id)->then(RELOAD()) ?>>
            Zobrazit</button>
    <?php
    }) ?>
<?php
}

if (empty($panels)) {
?>
    <div class="note">
        Ještě zde nejsou žádné zamítnuté panely.
    </div>
<?php
}
$app->jsManager->require("panel");
