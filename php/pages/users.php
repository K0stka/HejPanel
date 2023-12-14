<h1>Uživatelé</h1>
<?php
$sessions = array_map(
    fn ($e) => ["session_id" => $e["session_id"], "user" => $e["user"], "expires" => date(TIME_HMS_FORMAT . " " . DATE_DMY_FORMAT, $e["expires"])],
    $con
        ->select(["session_id", "user", "expires"], "sessions")
        ->addSQL("WHERE user IS NOT NULL")->fetchAll()
);
$userAccounts = array_map(fn ($e) => new User($e), $con->select("id", "users")->where(["type" => UserType::temp->value])->fetchAll());
foreach ($userAccounts as $userAccount) {
    $userSessions = array_filter($sessions, fn ($e) => $e["user"] == $userAccount->id);
?>
    <div class="panel-preview">
        <div class="two-columns-grid">
            <span style="font-weight: var(--semibold);">Jméno:</span><span><?= $userAccount ?></span>
            <span style="font-weight: var(--semibold);">Typ účtu:</span><span><?= $userAccount->type->value ?></span>
            <span style="font-weight: var(--semibold);">Přihlášen na:</span><span><?php prettyPrint(array_map(fn ($e) => ["session_id" => $e["session_id"], "expires" => $e["expires"]], $userSessions)); ?></span>
        </div>
    </div>
<?php
}
?>