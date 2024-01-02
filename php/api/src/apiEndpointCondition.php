<?php

class ApiEndpointCondition {
    public $condition;
    public ApiResponse $failureResponse;

    public function __construct($condition, ApiResponse $failureResponse) {
        $this->condition = $condition;
        $this->failureResponse = $failureResponse;
    }

    public function validate() {
        if (!($this->condition)()) $this->failureResponse->send();
    }
}
