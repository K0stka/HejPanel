<h1>Živý náhled</h1>
<div style="width: 60%;height: auto;aspect-ratio: 16 / 9;display: inline-block;overflow:hidden;">
    <iframe src="<?= $prefix ?>/panel" style="width: 1920px;height: 1080px;position:absolute;top:0;left:0;" class="auto-scale" data-target-width="100"></iframe>
</div>
<?php
$app->jsManager->require("panel");
