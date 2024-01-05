<?php

class ApiResponse {
    // Http code needs to be set separately!
    public int $http_code = 200;
    public array $headers = ["Content-type" => "application/json"];

    public array $JSONbody;

    protected bool $handle304 = false;
    protected int $lastModifiedUNIX = 0;
    protected string $etag = "";

    public function __construct(array $JSONbody) {
        $this->JSONbody = $JSONbody;
    }

    public function addHeader(string $headerName, string $headerValue) {
        $this->headers[$headerName] = $headerValue;
    }

    public function cache(int $maxAgeSeconds = 3600, bool $public = false) {
        header_remove("Pragma");
        $this->addHeader("Cache-Control", ($public ? "public" : "private") . ", max-age=$maxAgeSeconds");
        // $this->addHeader("Expires", gmdate("D, d M Y H:i:s", time() + $maxAgeSeconds) . " GMT"); Should not be required?
    }

    public function cacheWEtag(int $maxAgeSeconds = 3600, int $lastModifiedUNIX = 0, bool $public = false) {
        $this->cache($maxAgeSeconds, $public);

        if ($lastModifiedUNIX != 0) {
            $etag = md5($lastModifiedUNIX);

            $this->LastModified($lastModifiedUNIX);
            $this->addHeader("Etag", $etag);

            $this->handle304 = true;
            $this->etag = $etag;
        }
    }

    public function LastModified(int $lastModifiedUNIX = 0) {
        if ($lastModifiedUNIX != 0) {
            $this->addHeader("Last-Modified", gmdate("D, d M Y H:i:s", $lastModifiedUNIX) . " GMT");
            $this->lastModifiedUNIX = $lastModifiedUNIX;
        }
    }

    public function send() {
        if (!isset($this->headers["Cache-Control"])) $this->addHeader("Cache-Control", "no-store");
        foreach ($this->headers as $headerName => $headerValue) {
            header("$headerName: $headerValue");
        }

        if ($this->handle304) $this->dieIfCacheHit();
        else http_response_code($this->http_code);

        echo (utf8json($this->JSONbody));

        exit; // End the API script after sending a response
    }

    protected function dieIfCacheHit() {
        if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $this->lastModifiedUNIX || @trim($_SERVER['HTTP_IF_NONE_MATCH']) == $this->etag) { // Handle cache
            header("HTTP/1.1 304 Not Modified");
            exit;
        }
    }
}
