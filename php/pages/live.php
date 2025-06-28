<h1>Živý náhled</h1>
<div style="width: 60%;height: auto;aspect-ratio: 16 / 9;display: inline-block;overflow:hidden;">
    <iframe src="<?= $prefix ?>/panel" class="object-fit-fill" style="width:1920px;height:1080px"></iframe>
</div>
<?php
$app->jsManager->require("panel");
