<h1>Ahoj <?= $app->user ?></h1>
<button id="logout">Odhlásit se</button>
<?php
$app->jsManager->require("account");
