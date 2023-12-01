<?php

class ApiErrorResponse extends ApiResponse {
    public int $http_code = 400;

    public function __construct(string $errorMessage, int $http_code = 400) {
        $this->JSONbody = ["result" => "error", "message" => $errorMessage];
        $this->http_code = $http_code;
    }
}
