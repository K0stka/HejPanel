<h1>Zaregistrovat se</h1>
<form id="login-form">
    <div class="input-label">
        <label for="nickname">Jméno:</label>
        <input type="text" id="name" required data-type="name">
    </div>
    <div class="input-label">
        <label for="nickname">Přezdívka:</label>
        <input type="text" id="nickname" required data-type="nickname">
    </div>
    <div class="input-label">
        <label for="password">Heslo:</label>
        <input type="password" id="password" required data-type="password">
    </div>
    <div class="input-label">
        <label for="password">Kód:</label>
        <input type="text" id="code" required data-type="code">
    </div>
    <button id="submit">Zaregistrovat se</button>
</form>
<?php
$jsManager->require("register");
