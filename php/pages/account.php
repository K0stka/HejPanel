<h1>Ahoj <?= $user ?></h1>
<?php
out(array_filter((array)$user, fn ($e) => !$e instanceof Conn and !$e instanceof NotificationManager));
?>
<button id="logout">Odhlásit se</button>
<?php
$jsManager->require("account");
