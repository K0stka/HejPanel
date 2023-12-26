<?php
$sessions = array_map(
    fn ($e) => ["session_id" => $e["session_id"], "user" => $e["user"], "expires" => date(TIME_HMS_FORMAT . " " . DATE_DMY_FORMAT, $e["expires"])],
    $con
        ->select(["session_id", "user", "expires"], "sessions")
        ->addSQL("WHERE user IS NOT NULL")->fetchAll()
);

$userAccounts = array_map(fn ($e) => new User($e), $con->select("id", "users")->where(["type" => UserType::temp->value])->fetchAll());
?>
<h1>Dočasní uživatelé (<?= count($userAccounts) ?>)</h1>
<?php
foreach ($userAccounts as $userAccount) {
    $userSessions = array_filter($sessions, fn ($e) => $e["user"] == $userAccount->id);
?>
    <div class="panel-review">
        <div class="two-columns-grid">
            <span style="font-weight: var(--semibold);">Jméno:</span><span><?= $userAccount ?></span>
            <span style="font-weight: var(--semibold);">Přihlášen někde:</span><span><?= count($userSessions) == 0 ? "Ne" : "Ano" ?></span>
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