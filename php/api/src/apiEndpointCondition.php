<?php

class ApiEndpointCondition {
    public $condition;
    public ApiResponse $failureResponse;

    public function __construct($condition, ApiResponse $failureResponse) {
        $this->condition = $condition;
        $this->failureResponse = $failureResponse;
    }

    public function validate(): bool {
        $condition = $this->condition;
        if ($condition()) {
            return true;
        } else {
            $this->failureResponse->send();
            return false;
        }
    }
}
