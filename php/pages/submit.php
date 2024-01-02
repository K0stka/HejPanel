<h1>Přidat nový panel</h1>
<div class="two-columns">
    <div class="preview">
        <div class="object-fit-fill" style="width:1920px;height:1080px" id="panel-container">
        </div>
    </div>
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
            <input type="date" id="show-till" required value="<?= (new DateTime("tomorrow"))->format("Y-m-d") ?>" min="<?= date("Y-m-d") ?>" not-empty>
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
</div>
<?php
$app->jsManager->require("submit", "panel");
