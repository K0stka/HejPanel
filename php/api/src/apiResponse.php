<?php

class ApiResponse {
    // Http code needs to be set separately!
    public int $http_code = 200;
    public array $headers = ["Content-type: application/json"];

    public array $JSONbody;

    protected bool $cached = false;
    protected int $lastEditedUNIX = 0;
    protected string $etag = "";

    public function __construct(array $JSONbody) {
        $this->JSONbody = $JSONbody;
    }

    public function addHeader(string|array $header) {
        if (is_array($header)) {
            array_merge($this->headers, $header);
        } else {
            array_push($this->headers, $header);
        }
    }

    public function cache(int $maxAgeSeconds = 3600, int $lastEditedUNIX = 0) {
        $this->addHeader("Cache-Control: max-age=$maxAgeSeconds,private");

        if ($lastEditedUNIX != 0) {
            $etag = md5($lastEditedUNIX);

            $this->addHeader("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastEditedUNIX) . " GMT");
            $this->addHeader("Etag: $etag");

            $this->cached = true;
            $this->lastEditedUNIX = $lastEditedUNIX;
            $this->etag = $etag;
        }
    }

    public function send() {
        foreach ($this->headers as $header) {
            header($header);
        }

        if ($this->cached) $this->dieIfCacheHit();
        else http_response_code($this->http_code);

        echo ($this->utf8json($this->JSONbody));

        exit; // End the API script after sending a response
    }

    protected function dieIfCacheHit() {
        if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $this->lastEditedUNIX || @trim($_SERVER['HTTP_IF_NONE_MATCH']) == $this->etag) { // Handle cache
            header("HTTP/1.1 304 Not Modified");
            exit;
        }
    }

    protected function utf8json(array $array, bool $prettyPrint = false): string {
        $json = json_encode($array, $prettyPrint ? JSON_PRETTY_PRINT : 0);
        return preg_replace_callback(
            '/\\\\u([0-9a-fA-F]{4})/',
            function ($match) {
                return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
            },
            $json
        );
    }
}
