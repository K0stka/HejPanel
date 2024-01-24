<?php
$sessions = array_map(
    fn ($e) => ["session_id" => $e["session_id"], "user" => $e["user"], "expires" => date(TIME_HMS_FORMAT . " " . DATE_DMY_FORMAT, $e["expires"])],
    $con
        ->select(["session_id", "user", "expires"], "sessions")
        ->addSQL("WHERE user IS NOT NULL")->fetchAll()
);

$userAccounts = array_map(fn ($e) => new User($e), $con->select("id", "users")->where(["type" => UserType::temp->value])->fetchAll());

$fingerprintMap = User::getFingerprintToUsersMap();
?>
<h1>Dočasní uživatelé (<?= count($userAccounts) ?>)</h1>
<?php
foreach ($userAccounts as $userAccount) {
    $userSessions = array_filter($sessions, fn ($e) => $e["user"] == $userAccount->id);
    $groupId = $userAccount->categorizeByFingerprint($fingerprintMap);
?>
    <div class="panel-review auto-color" style="background-color: <?= assignColorById($groupId) ?>;">
        <div class="two-columns-grid">
            <span style="font-weight: var(--semibold);">Jméno:</span><span><?= $userAccount ?></span>
            <span style="font-weight: var(--semibold);">Přihlášen někde:</span><span><?= count($userSessions) == 0 ? "Ne" : "Ano" ?></span>
            <span style="font-weight: var(--semibold);">Skupina:</span><span><?= $groupId ?></span>
            <span style="font-weight: var(--semibold);">Otisk:</span><span style="max-height: 10rem; overflow: auto;"><?= prettyPrint($userAccount->lastFingerprint) ?></span>
            <?php
            /*
            <span style="font-weight: var(--semibold);">Naposledy přihlášen na:</span><span><?php prettyPrint($userAccount->lastFingerprint); ?></span>
            <span style="font-weight: var(--semibold);">Přihlášen na:</span><span><?php prettyPrint(array_map(fn ($e) => ["session_id" => $e["session_id"], "expires" => $e["expires"]], $userSessions)); ?></span>
            */
            ?>
        </div>
    </div>
<?php
}
?>