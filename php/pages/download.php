<h1>Panely ke stažení</h1>
<div class="note">
    Panely jsou k dispozici ve formátu .webp. Pokud je potřebujete v jiném formátu, doporučujeme využít <a normal target="_blank" href="https://convertio.co/webp-png/">https://convertio.co/webp-png/</a>
</div><br><br>
<?php

$panels = Panel::getVisiblePanels();
// $panels = [];
foreach ($panels as $panel) {
    if ($panel->type === PanelType::image)
    ?>
    <div style="margin: 1rem 0;position:relative;display:inline-block;">
        <div style="display:inline-block;position:relative; width: 480px; height: 270px; overflow: hidden;">
            <?= $panel->render() ?>
        </div>
        <a normal href="<?= $prefix ?>/api/download/<?= $panel->id ?>" download="panel_<?= $panel->id ?>.webp " class="button" style="position:absolute;top:0;right:0;transform:translate(20%, -20%);border-color:var(--background);;">Stáhnout</a>
    </div>
    <br>
    <?php
}

?>
<?php
if (!empty($panels)) {
    ?>
    <br><br>
    <?php
}
?>
<div class="note">
    <?php
    if (empty($panels)) {
        ?>
        Momentálně nejsou zobrazovány žádné panely, které by šlo stáhnout.<br><br>
        <?php
    }
    ?>
    Pokud máte zájem o panel, který již není zobrazovány, kontaktujte prosím <a href="mailto:kostkaj@gytool.cz" normal target="_blank">kostkaj@gytool.cz</a>
</div>