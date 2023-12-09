<h1>Čeká na povolení</h1>
<?php
$panels = Panel::getWaitingPanels();

$fingerprintMap = User::getFingerprintToUsersMap();

foreach ($panels as $panel) {
?>
    <?= PanelReview::render($panel, $fingerprintMap, function () { ?>
        <button>Povolit</button>
        <button class="danger">Zamítnout</button>
        <button class="notice">Upravit</button>
    <?php
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
$jsManager->require("panel");
