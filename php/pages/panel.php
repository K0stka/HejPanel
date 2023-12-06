<?php
$panels = Panel::getVisiblePanels();
foreach ($panels as $panel) {
    echo $panel->render();
}
