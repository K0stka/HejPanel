<?php
require_once("vendor/autoload.php");

enum ModuleType: string {
    case CSS = "css";
    case JS = "js";
}

class NoConverterCSS extends MatthiasMullie\Minify\CSS {
    protected function getPathConverter($source, $target) {
        return new MatthiasMullie\PathConverter\NoConverter();
    }
}

class ModuleManager {
    public array $files = [];
    private array $fetchedFiles = [];

    public ModuleType $type;

    private bool $sort;
    private bool $defer;

    private string $packagesDir = "packages/";
    public string $modulesDir = "modules/";

    private string $includePath;

    public function __construct(ModuleType $type, bool $sort = true, bool $defer = false) {
        $this->type = $type;
        $this->sort = $sort;
        $this->defer = $defer;

        $this->modulesDir .= $type->value . "/";

        $this->includePath = get_include_path() . "/generated/";
    }

    public function require(string ...$files) {
        $adding = array_diff($files, $this->fetchedFiles);
        array_push($this->files, ...$adding);
        array_push($this->fetchedFiles, ...$adding);
    }

    public function sort(bool $sort = true) {
        $this->sort = $sort;
    }

    public function defer(bool $defer = false) {
        $this->defer = $defer;
    }

    public function fetch(bool $normalFetch = true) {
        if (count($this->files) == 0) {
            return;
        }

        global $prefix, $v;

        if ($this->sort)
            sort($this->files);

        $this->fetchedFiles = array_merge($this->fetchedFiles, $this->files);

        if ($normalFetch) {
            if (DEV) {
                if ($this->type == ModuleType::CSS) {
                    foreach ($this->files as $file) {
                        echo ('<link rel="stylesheet" href="' . $prefix . '/css/' . $file . '.css' . $v . '" type="text/css" />');
                    }
                } else {
                    foreach ($this->files as $file) {
                        echo ('<script src="' . $prefix . '/js/' . $file . '.js' . $v . '" type="text/javascript"' . ($this->defer ? " defer" : "") . '></script>');
                    }
                }
            } else {
                if ($this->type == ModuleType::CSS) {
                    echo ('<link rel="stylesheet" href="' . $prefix . '/css/' . implode('-', $this->files) . '.css' . $v . '" type="text/css" />');
                } else {
                    echo ('<script src="' . $prefix . '/js/' . implode('-', $this->files) . '.js' . $v . '" type="text/javascript"' . ($this->defer ? " defer" : "") . '></script>');
                }
            }
        } else {
            echo ("<resource data-type=\"" . ($this->type == ModuleType::CSS ? "css" : "js") . "\" data-modules=\"" . implode('-', $this->files) . "\" data-version=\"" . substr($v, 3) . "\" />");
        }

        $this->files = [];
    }

    public function generate() {
        if (count($this->files) == 0) {
            return;
        }

        if ($this->sort)
            sort($this->files);

        $this->cacheHitOrBundle();

        $this->files = [];
    }

    private function cacheHitOrBundle(): void {
        $files = array_map(fn ($file) => $file . "." . $this->type->value, $this->files);
        $savePath = $this->includePath . $this->packagesDir . implode("-", $this->files) . "." . $this->type->value;
        $this->files = [];

        if (file_exists($savePath)) {
            readfile($savePath, true);
            return;
        }
        foreach ($files as $file) {
            if (!file_exists($this->includePath . $this->modulesDir . $file)) {
                echo "/* Error: Module $file not found. */";
                return;
            }
        }

        $files = array_map(fn ($file) => $this->includePath . $this->modulesDir . $file, $files);

        require_once("vendor/autoload.php");

        switch ($this->type) {
            case ModuleType::CSS:
                $minifier = new NoConverterCSS($files);
                break;
            case ModuleType::JS:
                $minifier = new MatthiasMullie\Minify\JS($files);
                break;
        }

        $minified = $minifier->minify();

        if (!DEV) {
            file_put_contents($savePath, $minified);
        }

        echo $minified;
    }
    public function passToJs(array $values) {
        global $pageManager;
        if ($pageManager->isNormalRequest) {
            echo ("<script>");
            foreach ($values as $name => $value) {
                echo ("window[\"$name\"]=" . utf8json($value) . ";");
            }
            echo ("</script>");
        } else {
            echo ("<phpValuesHydrate style=\"display:none\">");
            echo utf8json($values);
            echo ("</phpValuesHydrate>");
        }
    }
}
