<h1>Přidat nový panel</h1>
<div class="two-columns">
    <form id="submit-form">
        <div class="input-label">
            <label for="show-from">Vyvěsit od (včetně):</label>
            <input type="date" id="show-from" required>
        </div>
        <div class="input-label">
            <label for="show-till">Vyvěsit do (včetně):</label>
            <input type="date" id="show-till" required>
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
    <div class="preview"></div>
</div>
<?php
$jsManager->require("submit");
