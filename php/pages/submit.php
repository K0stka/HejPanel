<h1>Přidat nový panel</h1>
<div class="two-columns">
    <form id="submit-form">
        <div class="note">
            V případě vysokého množství panelů je možné,<br>
            že Váš panel nebude zobrazen po celou dobu, co zadáte.
        </div>
        <div class="input-label">
            <label for="show-from">Vyvěsit od (včetně):</label>
            <input type="date" id="show-from" required value="<?= date("Y-m-d") ?>" min="<?= date("Y-m-d") ?>" not-empty>
        </div>
        <div class="input-label">
            <label for="show-till">Vyvěsit do (včetně):</label>
            <input type="date" id="show-till" required value="<?= date("Y-m-d") ?>" not-empty>
        </div>
        <div class="button-group">
            <button id="set-type-image">Obrázek</button>
            <button id="set-type-text">Text</button>
        </div>
        <div id="additional-settings"></div>
        <div class="input-label">
            <label for="note">Dodatečná poznámka:</label>
            <textarea id="note"></textarea>
        </div>
        <button id="submit">Odeslat ke schválení</button>
    </form>
    <div class="preview">
        <div class="auto-scale" style="width: 1920px;height: 1080px;position:absolute;top:0;left:0;" data-target-width="100" id="panel-container">
        </div>
    </div>
</div>
<?php
$app->jsManager->require("submit", "panel");
