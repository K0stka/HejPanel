<?php
$jidelna = new Jidelna();
$dayData = $jidelna->fetchDay(new DateTime("today"));

$panels = Panel::getVisiblePanels();
if (empty($panels)) $panels = [Panel::getEmptyPanel()];
?>
<img src="<?= $prefix ?>/assets/icons/icon.png" class="panel-logo-button onlyPHONE" id="panel-logo-button">
<div class="panel-counter" id="panel-counter">1/<?= count($panels) ?></div>
<div class="radial-graph" id="panel-radial-graph"></div>
<div class="panel-container" id="panel-container">
    <?php
    foreach ($panels as $panel) echo ($panel->render());

    $app->jsManager->passToJs([
        "JIDELNA_PRELOAD" => $dayData,
        "PANELS_PRELOAD" => array_map(fn ($e) => $e->id, $panels),
        "PANEL_DETAILS_PRELOAD" => array_map(fn ($e) => ["id" => $e->id, "url" => $e->url], $panels),
        "FORCE_RELOAD_PRELOAD" => $forceReload,
    ]);
    ?>
</div>
<div class="panel-info" id="panel-info">
    <img src="<?= $prefix ?>/assets/icons/icon.png" class="panel-logo onlyPC">
    <div class="panel-header onlyPHONE">
        HejPanel
    </div>
    <div class="panel-time-container">
        <div class="panel-time" id="panel-time">
            00:00:00
        </div>
        <div class="panel-timetable" id="panel-timetable"></div>
    </div>
    <div class="panel-food" id="panel-jidelna">
        <b>
            Načítání...
        </b>
    </div>
    <div class="panel-qr onlyPC" id="panel-qr">
        <b>
            Příloha panelu
        </b>
    </div>
    <div class="panel-food-row onlyPHONE">
        <b>
            Tipy:
        </b>
        <span>
            <b>Kliknutím</b> přeskočíte panel.<br>
            <b>Podržením</b> pozastavíte procházení panelů.<br>
            <b>Přetažením do stran</b> posunete panely.<br>
            <b>Přetažením dolů</b> otevřete čas a obědy.
            <br>
        </span>
        <br>
        <span>
            Připomínky nebo návrhy na zlepšení můžete psát na <a href="https://www.instagram.com/studentskaradagh/" class="link" target="__blank">Instagram ŠRGH</a>.
        </span>
    </div>
</div>
<a class="panel-cta onlyPHONE" id="panel-cta">Příloha</a>
<?php
$app->jsManager->require("panel", "panel_live", "a_qrcode");
