<?php
require_once("php/classes/validator.php");

require_once("php/api/src/apiEndpoint.php");
require_once("php/api/src/apiFileUploadEndpoint.php");
require_once("php/api/src/apiEndpointCondition.php");
require_once("php/api/src/apiResponse.php");
require_once("php/api/src/apiContinuousResponse.php");
require_once("php/api/src/apiFileResponse.php");
require_once("php/api/src/apiSuccessResponse.php");
require_once("php/api/src/apiErrorResponse.php");

enum Method {
    case GET;
    case POST;
}

class Api {

    private array $req = [];

    /** @var ApiEndpoint[] $endpoints */
    public array $endpoints = [];

    public function __construct() {
        $this->req = $_SERVER["REQUEST_METHOD"] == "POST" ? $_POST : $_GET;
    }

    public function addEndpoint(Method $method, array $requiredRequestStructure, array $conditions, $callback) {
        if ($_SERVER["REQUEST_METHOD"] == ($method == Method::POST ? "POST" : "GET")) // Only process relevant endpoints
            array_push($this->endpoints, new ApiEndpoint($method, $requiredRequestStructure, $conditions, $callback));
    }

    public function addFileUploadEndpoint(array $requiredRequestStructure, string $fileInputName, string $savePath, array $conditions, array $allowedExtentions, $beforeUploadCallback = null, $afterUploadCallback = null) {
        if ($_SERVER["REQUEST_METHOD"] == "POST") // Only process relevant endpoints
            array_push($this->endpoints, new ApiFileUploadEndpoint($requiredRequestStructure, $fileInputName, $savePath, $allowedExtentions, $conditions, $beforeUploadCallback, $afterUploadCallback));
    }

    public function validateRequestStructure(array $requiredRequestStructure) {
        foreach ($requiredRequestStructure as $key => $value) {
            if (!isset($this->req[$key])) {
                return false;
            }

            if ($value instanceof \Type && !Validator::validate($this->req[$key], $value)) {
                // $request = new ApiErrorResponse("Parameter $key is not in the required format " . $value->value);
                // $request->send();

                return false;
            }

            if ($value instanceof \DataType && !Validator::validateDataType($this->req[$key], $value)) {
                // $request = new ApiErrorResponse("Parameter $key is not in the required format " . $value->value);
                // $request->send();

                return false;
            }

            if ($value instanceof ApiEndpointCondition) {
                $fx = $value->condition;
                if (!$fx($this->req[$key])) {
                    return false;
                };
            }

            if (is_array($value) && !in_array($this->req[$key], $value)) {
                return false;
            }

            if ((is_string($value) || is_numeric($value)) && $this->req[$key] != $value) {
                return false;
            }
        }
        return true;
    }

    public function listen() {
        global $prefix;
        // Allow access from base domain
        header("Access-Control-Allow-Origin: " . str_replace("Zkouseni", "", $prefix));

        foreach ($this->endpoints as $endpoint) {
            if (!$this->validateRequestStructure($endpoint->requiredRequestStructure)) {
                continue; // This endpoint was not triggered
            }

            if (!$endpoint->validateConditions()) {
                return; // Conditions for this endpoint were not met. Response already generated.
            }

            $endpoint->execute();
            return; // Api successfully responded to the request
        }
    }
}
