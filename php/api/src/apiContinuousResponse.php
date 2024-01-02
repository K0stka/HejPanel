<?php

class ApiContinuousResponse extends ApiResponse {
    public array $headers = ["Content-Type: text/event-stream", "Cache-Control: no-cache"];

    public function __construct() {
    }

    public function open() {
        foreach ($this->headers as $headerName => $headerValue) {
            header("$headerName: $headerValue");
        }
    }

    public function send_message(int|string $id, string $message, int $progress) {
        $d = array('message' => $message, 'progress' => $progress);

        echo "id: $id" . PHP_EOL;
        echo "data: " . json_encode($d) . PHP_EOL;
        echo PHP_EOL;

        ob_flush();
        flush();
    }

    public function close(string $message = "Task completed") {
        $this->send_message("CLOSE", $message, 100);
        exit;
    }
}
