<?php
$jidelna = new Jidelna();
$dayData = $jidelna->fetchDay(new DateTime());
?>

<div class="panel-info">
    <img src="<?= $prefix ?>/assets/icons/icon.png" class="panel-logo">
    <div class="panel-time" id="panel-time">
        00:00:00
    </div>
    <div class="panel-food">
        <?php
        if (!isset($dayData["result"]) or $dayData["result"] != "error") {
        ?>
            <div class="panel-food-row">
                <b>Polévka:</b> <?= $dayData["X1"] ?>
            </div>
            <div class="panel-food-row">
                <b>Oběd 1:</b> <?= $dayData["O1"] ?>
            </div>
            <div class="panel-food-row">
                <b>Oběd 2:</b> <?= $dayData["O2"] ?>
            </div>
            <div class="panel-food-row">
                <b>Oběd 3:</b> <?= $dayData["O3"] ?>
            </div>
            <div class="panel-food-row">
                <b>Svačina:</b> <?= $dayData["SV"] ?>
            </div>
        <?php
        } else {
        ?>
            <b>
                Nemohli jsme načíst data z jídelny.
            </b>
        <?php
        }
        ?>
    </div>
</div>
<div class="panel-counter" id="panel-counter"></div>
<div class="panel-container" id="panel-container">
    <div class="panel panel-text animate-in" id="panel-loading">
        Velmi rychle procházíme vnitřní dokumentaci školy pro ty nejnovější novinky 😉
    </div>
</div>
<?php
$jsManager->require("panel", "panel_live");
