<?php
require_once("php/classes/validator.php");

require_once("php/api/src/apiMessages.php");

require_once("php/api/src/apiEndpoint.php");
require_once("php/api/src/apiFileUploadEndpoint.php");
require_once("php/api/src/apiFileUploadConfiguration.php");
require_once("php/api/src/apiEndpointCondition.php");
require_once("php/api/src/apiResponse.php");
require_once("php/api/src/apiContinuousResponse.php");
require_once("php/api/src/apiFileResponse.php");
require_once("php/api/src/apiSuccessResponse.php");
require_once("php/api/src/apiErrorResponse.php");

enum Method: string {
    case GET = "GET";
    case POST = "POST";
}

class Api {

    private array $req = [];

    /** @var ApiEndpoint[] $endpoints */
    public array $endpoints = [];

    public function __construct() {
        $this->req = $_SERVER["REQUEST_METHOD"] == "POST" ? $_POST : $_GET;
    }

    public function addEndpoint(Method $method, array $requiredRequestStructure, array $conditions, $callback) {
        if ($_SERVER["REQUEST_METHOD"] == $method->value) // Only process relevant endpoints
            array_push($this->endpoints, new ApiEndpoint($method, $requiredRequestStructure, $conditions, $callback));
    }

    public function addFileUploadEndpoint(array $requiredRequestStructure, array $conditions, ApiFileUploadConfiguration $config) {
        if ($_SERVER["REQUEST_METHOD"] == "POST") // Only process relevant endpoints
            array_push($this->endpoints, new ApiFileUploadEndpoint($requiredRequestStructure, $conditions, $config));
    }

    public function validateRequestStructure(array $requiredRequestStructure) {
        foreach ($requiredRequestStructure as $key => $value) {
            if (!isset($this->req[$key])) {
                return false;
            }

            if ($value instanceof Type && !Validator::validate($this->req[$key], $value)) {
                if (DEV) (new ApiErrorResponse("Parameter $key is not in the required format Type::" . $value->name))->send();

                return false;
            }

            if ($value instanceof DataType && !Validator::validateDataType($this->req[$key], $value)) {
                if (DEV) (new ApiErrorResponse("Parameter $key is not in the required format DataType::" . $value->name . ", detected " . gettype($this->req[$key])))->send();

                return false;
            }

            if ($value instanceof ApiEndpointCondition) {
                if (!($value->condition)($this->req[$key])) {
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
        header("Access-Control-Allow-Origin: $prefix/");

        foreach ($this->endpoints as $endpoint) {
            if (!$this->validateRequestStructure($endpoint->requiredRequestStructure)) {
                continue; // This endpoint was not triggered
            }

            $endpoint->validateConditions(); // If conditions for this endpoint were not met, stops further execution & generates error response

            $endpoint->execute();
        }

        // No endpoint was triggered
        (new ApiErrorResponse(ApiMessage::noEndpointTriggered))->send();
    }
}
