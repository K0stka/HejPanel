<?php
class AppManager {
    public bool $authenticated = false;
    public ?User $user = null;

    public ?ActionManager $actionManager = null;
    public ?NotificationManager $notificationManager = null;

    public PageManager $pageManager;

    public ModuleManager $jsManager;
    public ModuleManager $cssManager;

    public ?array $tables = null;

    function authenticate(?User $user) {
        if ($user) {
            $this->authenticated = true;
            $this->user = $user;

            // $this->actionManager = new ActionManager($this->user); Not needed here

            // $this->notificationManager = new NotificationManager($this->user); Not needed here
        }
    }

    function initiateRouter(array $validSubpagesByUserType, array $pageNames) {
        $this->cssManager = new ModuleManager(ModuleType::CSS, false);

        $this->jsManager = new ModuleManager(ModuleType::JS, false);
        $this->jsManager->defer(true);

        $this->pageManager = new PageManager(
            $validSubpagesByUserType[($this->user ? $this->user->type->value : UserType::cases()[0]->value)],
            $pageNames
        );
    }

    function synchronizeTables() {
    }

    function updateManifest() {
        global $folder;
        $manifest = '{
            "name": "' . NAME . '",
            "short_name": "' . NAME . '",
            "start_url": "/' . $folder . '/",
            "scope": "/' . $folder . '/",
            "background_color": "' . COLOR . '",
            "theme_color": "' . COLOR . '",
            "description": "' . DESCRIPTION . '",
            "orientation": "portrait",
            "display": "standalone",
            "lang": "cs-CZ",
            "icons": [
                { "purpose": "maskable", "sizes": "512x512", "src": "icons/icon512_maskable.png", "type": "image/png" },
                { "purpose": "any", "sizes": "512x512", "src": "icons/icon512_rounded.png", "type": "image/png" }
            ]
        }';
        file_put_contents("assets/manifest.json", $manifest);
    }
}
