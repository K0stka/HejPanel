<?php

class ApiFileUploadEndpoint extends ApiEndpoint {
    public array $file;
    public string $savePath;
    public array $allowedExtensions;
    public $beforeUploadCallback;
    public $afterUploadCallback;

    public function __construct(array $requiredRequestStructure, string $fileInputName, string $savePath, array $allowedExtensions, array $conditions, $beforeUploadCallback, $afterUploadCallback) {
        $this->requiredRequestStructure = $requiredRequestStructure;
        $this->file = $_FILES[$fileInputName] ?? [];
        $this->savePath = $savePath;
        $this->allowedExtensions = $allowedExtensions;
        $this->conditions = $conditions;
        $this->beforeUploadCallback = $beforeUploadCallback;
        $this->afterUploadCallback = $afterUploadCallback;
    }

    public function validateConditions() {
        if (empty($this->file)) {
            $response = new ApiErrorResponse("No file was uploaded");
            $response->send();
            return false;
        } elseif (!in_array(strtolower(pathinfo($this->file['name'], PATHINFO_EXTENSION)), $this->allowedExtensions)) {
            $response = new ApiErrorResponse("Illegal file type. The extension must be one of the following: " . implode(", ", array_map(fn ($e) => "." . $e, $this->allowedExtensions)));
            $response->send();
            return false;
        } elseif ($this->file["error"] > 0) {
            $response = new ApiErrorResponse("There was an error uploading the file. Details: " . $this->file["error"]);
            $response->send();
            return false;
        }

        foreach ($this->conditions as $condition) {
            if (!$condition->validate()) {
                return false;
            }
        }

        return true;
    }

    public function execute() {
        if ($this->beforeUploadCallback) {
            $temp = $this->beforeUploadCallback;
            $newName = $temp($this->file);
        }

        $extension = null;
        foreach ($this->allowedExtensions as $key => $value) {
            if ($key == "SAVE_AS")
                $extension = $value;
        }

        $result = move_uploaded_file($this->file["tmp_name"], "../../uploads/" . $this->savePath . ($newName ?? pathinfo($this->file["name"], PATHINFO_FILENAME)) . '.' . ($extension ?? pathinfo($this->file["name"], PATHINFO_EXTENSION)));

        if ($result) {
            if ($this->afterUploadCallback) {
                $temp = $this->afterUploadCallback;
                $temp($this->file);
            }

            $response = new ApiSuccessResponse("File uploaded successfully");
        } else {
            $response = new ApiErrorResponse("File upload failed");
        }

        $response->send();
    }
}
