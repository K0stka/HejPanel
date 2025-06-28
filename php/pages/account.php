<h1>Ahoj <?= $app->user ?></h1>
<button <?= $app->bind->onClick(function () {
            User::logout();
        })->then(FADE_TO("/login")) ?>>Odhlásit se</button>