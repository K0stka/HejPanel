<h1>Čeká na povolení</h1>
<?php
$panels = Panel::getWaitingPanels();

$fingerprintMap = User::getFingerprintToUsersMap();

foreach ($panels as $panel) {
?>
    <?= PanelReview::render($panel, $fingerprintMap, function () use ($con, $app, $panel, $panels) { ?>
        <button <?= $app->bind->onClick(function () use ($app, $panel) {
                    $panel->approved = true;
                    $panel->approvedBy = $app->user;
                    $panel->approvedAt = new DateTime();
                    $panel->updateAll();
                })->then(RELOAD()) ?>>Povolit</button>
        <div class="button danger">
            Zamítnout
            <div class="tooltip horizontal">
                <button class="danger" <?= $app->bind->onClick(function () use ($app, $panel) {
                                            $panel->approved = false;
                                            $panel->approvedBy = $app->user;
                                            $panel->approvedAt = new DateTime();
                                            $panel->updateAll();
                                        })->then(RELOAD()) ?>>Pouze tento panel</button>

                <button class="danger" <?= $app->bind->onClick(function () use ($con, $app, $panel, $panels) {
                                            $panelsToRemove = array_map(fn ($e) => "id = " . $e->id, array_filter($panels, function ($e) use ($panel) {
                                                return $e->postedBy->id == $panel->postedBy->id;
                                            }));
                                            $con->update("panels", ["approved" => 0, "approved_by" => $app->user->id, "approved_at" => date(MYSQL_DATETIME)])
                                                ->addSQL("WHERE " . join(" OR ", $panelsToRemove))
                                                ->execute();
                                        })->then(RELOAD()) ?>>Vše od <?= $panel->postedBy ?></button>
                <button class="danger" <?= $app->bind->onClick(function () use ($con, $app, $panel, $panels) {
                                            $panelsToRemove = array_map(fn ($e) => "id = " . $e->id, array_filter($panels, function ($e) use ($panel) {
                                                return $e->postedBy->fingerprintGroupId == $panel->postedBy->fingerprintGroupId;
                                            }));
                                            $con->update("panels", ["approved" => 0, "approved_by" => $app->user->id, "approved_at" => date(MYSQL_DATETIME)])
                                                ->addSQL("WHERE " . join(" OR ", $panelsToRemove))
                                                ->execute();
                                        })->then(RELOAD()) ?>>Vše od skupiny <?= $panel->postedBy->fingerprintGroupId ?></button>
            </div>
        </div>
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
