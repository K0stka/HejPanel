<h1>Přihlásit se</h1>
<form id="login-form">
    <div class="input-label">
        <label for="nickname">Přezdívka:</label>
        <input type="text" id="nickname" required data-type="string">
    </div>
    <div class="input-label">
        <label for="password">Heslo:</label>
        <input type="password" id="password" required data-type="string">
    </div>
    <button id="submit">Přihlásit se</button>
</form>
<?php
$jsManager->require("login");