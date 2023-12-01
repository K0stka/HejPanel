<?php
switch ($actionManager->type) {
    default:
?>
        Neznámý typ vyžadované akce: <?= $actionManager->type ?><br><b>Prosím, kontaktujte správce</b>
<?php
}
