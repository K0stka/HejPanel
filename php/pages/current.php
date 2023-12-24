<h1>Momentálně viditelné</h1>

<?php

$panels = Panel::getVisiblePanels();

$fingerprintMap = User::getFingerprintToUsersMap();

foreach ($panels as $panel) {
?>
    <?= PanelReview::render($panel, $fingerprintMap, function () {
    ?>
        <button class="danger">Skrýt</button>
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
