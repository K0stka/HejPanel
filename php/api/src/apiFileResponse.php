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
        $this->filePath = $filePath;
        $this->fileName = $fileName;
        $this->mode = $mode;
    }

    public function send() {
        foreach ($this->headers as $header) {
            header($header);
        }

        if ($this->cached) $this->dieIfCacheHit();
        else http_response_code($this->http_code);

        if (file_exists($this->filePath)) {
            header('Content-Length: ' . filesize($this->filePath) + 1); // Magical + 1 :D ?
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
