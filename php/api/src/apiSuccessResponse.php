<?php

class ApiSuccessResponse extends ApiResponse {
    public function __construct(array|string|null|ApiMessage $aditionalData = null) {
        if (is_array($aditionalData) && count($aditionalData) > 0) {
            $this->JSONbody = ["result" => "success", ...$aditionalData];
        } elseif (is_string($aditionalData) && strlen($aditionalData) > 0) {
            $this->JSONbody = ["result" => "success", "message" => $aditionalData];
        } elseif ($aditionalData instanceof ApiMessage) {
            $this->JSONbody = ["result" => "success", "message" => $aditionalData->value];
        } else {
            $this->JSONbody = ["result" => "success"];
        }
    }
}
