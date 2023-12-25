<?php

define("RELOAD", "fadeTo(window.location.href);");

class BindManager {
    private array $eventHandlers = [];

    public bool $handlerRequest = false;

    public function __construct() {
        $this->handlerRequest = $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["e"]);
    }

    public function onClick(callable $serverCallback) {
        $this->eventHandlers[] = [
            "serverCallback" => $serverCallback,
            "clientCallback" => "",
        ];

        echo ("bind=\"" . array_key_last($this->eventHandlers) . "\"");

        return $this;
    }

    public function then(string $clientCallback) {
        $this->eventHandlers[array_key_last($this->eventHandlers)]["clientCallback"] = $clientCallback;
    }

    public function handleEventHandlers() {
        if (!$this->handlerRequest) return;

        ob_clean();

        foreach ($this->eventHandlers as $id => $eventHandler) {
            if ($id == $_POST["e"]) {
                echo ("const output = " . utf8json($eventHandler["serverCallback"]() ?? null) . ";\n");
                echo ($eventHandler["clientCallback"]);
                return;
            }
        }

        echo ("Invalid event id " . $_POST["e"]);
    }
}
