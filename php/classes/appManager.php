<?php
class AppManager {
    public bool $authenticated = false;
    public ?User $user = null;

    public ActionManager $actionManager;
    public NotificationManager $notificationManager;

    public PageManager $pageManager;

    public ModuleManager $jsManager;
    public ModuleManager $cssManager;

    public BindManager $bind;

    public ?array $tables = null;

    public function __construct() {
        $this->cssManager = new ModuleManager(ModuleType::CSS, false);
        $this->jsManager = new ModuleManager(ModuleType::JS, false, true);

        $this->bind = new BindManager();
    }

    public function authenticate(?User $user) {
        if ($user) {
            $this->authenticated = true;
            $this->user = $user;

            // $this->actionManager = new ActionManager($this->user); Not needed here

            $this->notificationManager = new NotificationManager($this->user); //Not needed here
        }
    }

    public function initiateRouter(array $validSubpagesByUserType, array $pageNames) {
        $this->pageManager = new PageManager(
            $validSubpagesByUserType[($this->user ? $this->user->type->value : UserType::cases()[0]->value)],
            $pageNames
        );
    }

    public function synchronizeTables() {
    }

    public function updateManifest() {
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
}
';
        file_put_contents("assets/manifest.json", $manifest);
    }

    public function incrementVersion() {
        global $v;

        $str = file_get_contents('php/conf.php');

        $str = str_replace("\$v = \"$v\";", "\$v = \"?v=" . (intval(substr($v, 3)) + 1) . "\";", $str);

        file_put_contents('php/conf.php', $str);
    }

    public function clearMinifiedPackages() {
        $files = array_filter(scandir("generated/packages"), fn ($e) => is_file("generated/packages/" . $e));
        foreach ($files as $file) {
            unlink("generated/packages/" . $file);
        }
    }
}
