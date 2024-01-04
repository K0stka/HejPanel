<?php

enum FileResponseMode {
    case Download;
    case Inline;
}

class ApiFileResponse extends ApiResponse {
    public array $headers = [];

    public string $filePath;
    public string $fileName;
    public FileResponseMode $mode;

    public function __construct(string $filePath, string $fileName, FileResponseMode $mode = FileResponseMode::Inline) {
        $this->filePath = PREFIX . ltrim($filePath, "/");
        $this->fileName = $fileName;
        $this->mode = $mode;
    }

    public function send() {
        foreach ($this->headers as $headerName => $headerValue) {
            header("$headerName: $headerValue");
        }

        if ($this->handle304) $this->dieIfCacheHit();
        else http_response_code($this->http_code);

        if (file_exists($this->filePath)) {
            header('Content-Length: ' . filesize($this->filePath));
            if ($this->mode == FileResponseMode::Download) {
                header('Content-Disposition: attachment; filename="' . $this->fileName . '"');
            } else {
                header('Content-Disposition: inline; filename="' . $this->fileName . '"');
            }
            header('Content-Type: ' . mime_content_type($this->filePath));

            readfile($this->filePath);
        } else {
            http_response_code(404);
        }

        exit; // End the API script after sending a response
    }
}
