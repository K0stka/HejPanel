<?php
class AdminSetting {
    public static function render(string $header, string $details, string $buttonText, callable $serverCallback, string $clientCallback): void {
        global $app;
?>
        <div style="max-width: 600px; margin: 0 auto 2em;">
            <h1><?= $header ?></h1>
            <div style="text-align: left; border: 2px solid var(--primary);padding: 1em; margin: 0 1em 1em;"><?= $details ?></div>
            <button <?= $app->bind->onClick($serverCallback)->then($clientCallback) ?>> <?= $buttonText ?> </button>
        </div>
<?php
    }
}
