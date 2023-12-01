<?php

class PageManager {
    public string $pageName;
    public string $pageTitle;

    public string $page;
    public ?string $subpage = null;

    public ?string $subpageQuery = null;

    public bool $isNormalRequest = true;
    public bool $isHydrationRequest = false;

    public string $pagePath;
    public ?string $subpagePath = null;

    public function __construct(array $validPages, array $pageNames) {
        // Pages logic
        if (isset($_GET["page"]) && isset($validPages[$_GET["page"]])) {
            $this->page = $_GET["page"];
        } else {
            $this->page = array_key_first($validPages);
        }

        $this->pagePath = "php/pages/" . $this->page . ".php";

        // Subpage logic
        if (isset($_GET['subpage']) && is_array($validPages[$this->page]) && in_array($_GET['subpage'], $validPages[$this->page])) {
            $this->subpage = $_GET['subpage'];
            $this->subpagePath = "php/subpages/$this->page-$this->subpage.php";
        } else if (isset($validPages[$this->page]) && is_array($validPages[$this->page]) && !empty($validPages[$this->page])) {
            $this->subpage = $validPages[$this->page][array_key_first($validPages[$this->page])];
            $this->subpagePath = "php/subpages/$this->page-$this->subpage.php";
        } elseif (isset($validPages[$this->page]) && $validPages[$this->page] == "QUERY") {
            $this->subpageQuery = isset($_GET["subpage"]) && trim($_GET["subpage"]) != "" ? $_GET["subpage"] : null;
        }

        // Title Logic
        $this->pageName = ($pageNames[$this->page . "/" . $this->subpage] ??  $pageNames[$this->page]  ?? "Stránka");
        $this->pageTitle = ((isset($pageNames[$this->page . "/" . $this->subpage])) ? $pageNames[$this->page . "/" . $this->subpage] . " | " : ((isset($pageNames[$this->page])) ? $pageNames[$this->page] . " | " : "")) . NAME;

        // Request type logic
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $this->isNormalRequest = false;
            $this->isHydrationRequest = true;
            header("Title: " . urlencode($this->pageTitle));
        }
    }

    public function isNested(): bool {
        return ($this->subpageQuery && $this->page != "registrace") || ($this->subpage && $this->subpage != "prehled");
    }
}
