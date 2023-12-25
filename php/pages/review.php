<h1>Čeká na povolení</h1>
<?php
$panels = Panel::getWaitingPanels();

$fingerprintMap = User::getFingerprintToUsersMap();

foreach ($panels as $panel) {
?>
    <?= PanelReview::render($panel, $fingerprintMap, function () use ($app, $panel) { ?>
        <button <?= $app->bind->onClick(function () use ($app, $panel) {
                    $panel->approved = true;
                    $panel->approvedBy = $app->user;
                    $panel->approvedAt = new DateTime();
                    $panel->updateAll();
                })->then(RELOAD) ?>>Povolit</button>
        <button class="danger" <?= $app->bind->onClick(function () use ($app, $panel) {
                                    $panel->approved = false;
                                    $panel->approvedBy = $app->user;
                                    $panel->approvedAt = new DateTime();
                                    $panel->updateAll();
                                })->then(RELOAD) ?>>Zamítnout</button>
    <?php
        // <button class="notice">Upravit</button>
    }) ?>
<?php
}

if (empty($panels)) {
?>
    <div class="note">
        Žádné panely nečekají na povolení.
    </div>
<?php
}
$app->jsManager->require("panel");
