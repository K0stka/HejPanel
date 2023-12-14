<?php

class BindManager {
    private array $binds = [];

    public function __construct() {
    }

    public function Value(string $index) {
    }

    public function GetValue(string $index) {
    }

    public function OnClick(): string {
        return "onclick=\"alert(1)\"";
    }
}
