<h1>Čeká na zobrazení</h1>

<?php

$panels = Panel::getPanelsWaitingToBeDisplayed();

$fingerprintMap = User::getFingerprintToUsersMap();

foreach ($panels as $panel) {
?>
    <?= PanelReview::render($panel, $fingerprintMap, function () use ($app, $panel) {
    ?>
        <button <?= $app->bind->onClick(function () use ($panel) {
                    $panel->update("showOverride", ShowOverride::show);
                }, "s-p:" . $panel->id)->then(RELOAD()) ?>>
            Zobrazit</button>
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
        Žádné panely nečekají na zobrazení.
    </div>
<?php
}
$app->jsManager->require("panel");
