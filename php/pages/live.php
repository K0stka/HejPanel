<?php
$panels = Panel::getVisiblePanels();

foreach ($panels as $panel) {
?>
    <div>
        <?= $panel->id ?>
        <?= $panel->type->value ?>
        <?= $panel->content ?>
    </div>
<?php
}
