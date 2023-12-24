<h1>Ahoj <?= $app->user ?></h1>
<?php
out(array_filter((array)$app->user, fn ($e) => !$e instanceof Conn));
?>
<button id="logout">Odhlásit se</button>
<?php
$app->jsManager->require("account");
