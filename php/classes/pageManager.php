<?php

class PageManager {
    public array $validPages;

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
        $validPagesNormalized = [];
        foreach ($validPages as $page => $validSubpages) {
            if (is_numeric($page)) {
                $validPagesNormalized[$validSubpages] = "";
            } else {
                $validPagesNormalized[$page] = $validSubpages;
            }
        }

        $this->validPages = $validPagesNormalized;

        // Pages logic
        if (isset($_GET["page"]) && isset($this->validPages[$_GET["page"]])) {
            $this->page = $_GET["page"];
        } else {
            $this->page = array_key_first($this->validPages);
        }

        $this->pagePath = "php/pages/" . $this->page . ".php";

        // Subpage logic
        if (isset($_GET['subpage']) && is_array($this->validPages[$this->page]) && in_array($_GET['subpage'], $this->validPages[$this->page])) {
            $this->subpage = $_GET['subpage'];
            $this->subpagePath = "php/subpages/$this->page-$this->subpage.php";
        } else if (isset($this->validPages[$this->page]) && is_array($this->validPages[$this->page]) && !empty($this->validPages[$this->page])) {
            $this->subpage = $this->validPages[$this->page][array_key_first($this->validPages[$this->page])];
            $this->subpagePath = "php/subpages/$this->page-$this->subpage.php";
        } elseif (isset($this->validPages[$this->page]) && $this->validPages[$this->page] == "QUERY") {
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
