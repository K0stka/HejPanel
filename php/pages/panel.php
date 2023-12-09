<div class="panel-container">
    <?php
    $panels = Panel::getVisiblePanels();

    if (empty($panels)) {
        $panels[] = new Panel([
            "id" => -1,
            "posted_by" => -1,
            "posted_at" => -1,
            "approved" => true,
            "approved_by" => -1,
            "approved_at" => -1,
            "show_from" => -1,
            "show_till" => -1,
            "type" => "text",
            "content" => "Víte, jak zajistit, že zde vždy bude něco ke čtení?<br>Přesně takto :)",
            "note" => ""
        ]);
    }

    foreach ($panels as $panel) {
        echo $panel->render();
    }
    ?>
</div>
<?php
$jsManager->require("panel");
