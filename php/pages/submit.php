<h1>Přidat nový panel</h1>
<div class="two-columns">
    <div class="preview">
        <div class="object-fit-fill" style="width:1920px;height:1080px" id="panel-container">
        </div>
    </div>
    <form id="submit-form">
        <div class="note">
            V případě vysokého množství panelů je možné,<br>
            že Váš panel nebude zobrazen po celou dobu, co zadáte.<br>
            Doporučená doba zobrazení je týden.
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
            <label for="show-till">URL adresa (Nepovinné):</label>
            <input type="string" id="url" data-type="nullableUrl" placeholder="Např.: https://gytool.cz">
        </div>
        <div class="note">
            Na email Vás budeme kontaktovat pouze<br> pokud se vyskytne nějaký problém s Vaším panelem.
        </div>
        <?php
        if (!$app->authenticated || $app->user->type == UserType::temp) {
            ?>
            <div class="input-label">
                <label for="show-till">Email:</label>
                <input type="string" id="mail" data-type="mail">
            </div>
            <?php
        } else {
            ?>
            <input type="hidden" id="mail" value="null@null.null">
            <?php
        }
        ?>
        <div class="input-label">
            <label for="note">Dodatečná poznámka (Nepovinné):</label>
            <textarea id="note"></textarea>
        </div>
        <button id="submit">Odeslat ke schválení</button>
    </form>
</div>
<?php
$app->jsManager->require("submit", "panel");
