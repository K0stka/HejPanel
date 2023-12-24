<h1>Přihlásit se</h1>
<form id="login-form">
    <div class="input-label">
        <label for="nickname">Přezdívka:</label>
        <input type="text" id="nickname" required>
    </div>
    <div class="input-label">
        <label for="password">Heslo:</label>
        <input type="password" id="password" required>
    </div>
    <button id="submit">Přihlásit se</button>
</form>
<?php
$app->jsManager->require("login");
