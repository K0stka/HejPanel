<?php

class ApiEndpoint {
    public Method $method;
    public array $requiredRequestStructure;

    /** @var ApiEndpointCondition[] $conditions */
    public array $conditions;

    public $callback;

    public function __construct(Method $method, array $requiredRequestStructure, array $conditions, callable $callback) {
        $this->method = $method;
        $this->requiredRequestStructure = $requiredRequestStructure;
        $this->conditions = $conditions;
        $this->callback = $callback;
    }

    public function validateConditions() {
        foreach ($this->conditions as $condition) {
            if (!$condition->validate()) {
                return false;
            }
        }

        return true;
    }

    public function execute() {
        $temp = $this->callback;
        $return = $temp();
        if ($return instanceof ApiResponse) $return->send();
        else {
            $response = new ApiErrorResponse("Invalid return value: " . utf8json($return));
            $response->send();
        }
    }
}
