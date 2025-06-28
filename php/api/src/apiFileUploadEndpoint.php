<?php

class ApiFileUploadEndpoint extends ApiEndpoint {
    public array $file;
    public $saveName;
    public ?FileType $imageSaveAs;
    public string $savePath;
    public int $minFiles;
    public int $maxFiles;
    public int $maxFileSizeMB;
    /** @var FileType[] */
    public array $allowedFileTypes;
    public $onBeforeUploadCallback;
    public $onAfterUploadCallback;

    public function __construct(array $requiredRequestStructure, array $conditions, ApiFileUploadConfiguration $config) {
        $this->requiredRequestStructure = array_merge($requiredRequestStructure, ["fileIndex" => DataType::int, "fileCount" => DataType::int]);
        $this->conditions = $conditions;

        $this->file = $_FILES[$config->fileInputName] ?? [];
        $this->saveName = $config->saveName;
        $this->imageSaveAs = $config->imageSaveAs;
        $this->savePath = $config->savePath;
        $this->minFiles = $config->minFiles;
        $this->maxFiles = $config->maxFiles;
        $this->maxFileSizeMB = $config->maxFileSizeMB;
        $this->allowedFileTypes = $config->allowedFileTypes;
        $this->onBeforeUploadCallback = $config->onBeforeUpload;
        $this->onAfterUploadCallback = $config->onAfterUpload;
    }

    public function validateConditions() {
        parent::validateConditions();

        $fileIndex = intval($_POST["fileIndex"]);
        $fileCount = intval($_POST["fileCount"]);

        if ($fileCount < $this->minFiles) (new ApiErrorResponse(ApiMessage::notEnoughFiles->value . $this->minFiles))->send();
        if ($fileCount > $this->maxFiles) (new ApiErrorResponse(ApiMessage::tooMuchFiles->value . $this->maxFiles))->send();
        if ($fileIndex > $fileCount) (new ApiErrorResponse(ApiMessage::moreFilesThanPromised))->send();

        if (empty($this->file)) (new ApiErrorResponse(ApiMessage::noFileUploaded))->send();

        $fileSize = filesize($this->file['tmp_name']);
        if ($fileSize == 0) (new ApiErrorResponse(ApiMessage::fileEmpty))->send();
        if ($fileSize > $this->maxFileSizeMB * 1024 * 1024) (new ApiErrorResponse(ApiMessage::fileTooBig->value . $this->maxFileSizeMB . "MB"))->send();

        if (!in_array(mime_content_type($this->file["tmp_name"]), array_map(fn ($e) => $e->value, $this->allowedFileTypes))) (
            new ApiErrorResponse(ApiMessage::illegalFileType->value . implode(", ", array_map(fn ($e) => "." . $e->name, $this->allowedFileTypes)) . "<br>" . ApiMessage::fileReceived->value . mime_content_type($this->file["tmp_name"]))
        )->send();

        if ($this->file["error"] > 0) (new ApiErrorResponse(ApiMessage::uploadingError->value . $this->file["error"]))->send();
    }

    public function execute() {
        $fileIndex = intval($_POST["fileIndex"]);
        $fileCount = intval($_POST["fileCount"]);

        $sourceFileType = FileType::tryFrom(mime_content_type($this->file["tmp_name"]));

        if ($this->imageSaveAs) {
            if ($sourceFileType != $this->imageSaveAs) {
                switch ($sourceFileType) {
                    case FileType::jpeg:
                        $image = imagecreatefromjpeg($this->file["tmp_name"]);
                        break;
                    case FileType::png:
                        $image = imagecreatefrompng($this->file["tmp_name"]);
                        break;
                    case FileType::webp:
                        $image = imagecreatefromwebp($this->file["tmp_name"]);
                        break;
                    case FileType::bmp:
                        $image = imagecreatefrombmp($this->file["tmp_name"]);
                        break;
                    case FileType::gif:
                        $image = imagecreatefromgif($this->file["tmp_name"]);
                        break;
                    case FileType::avif:
                        $image = imagecreatefromavif($this->file["tmp_name"]);
                        break;
                    default:
                        (new ApiErrorResponse(ApiMessage::uploadingError->value . "Can't automaticaly convert from FileType::" . $sourceFileType->name))->send();
                }
                switch ($this->imageSaveAs) {
                    case FileType::jpeg:
                        imagejpeg($image, $this->file["tmp_name"]);
                        break;
                    case FileType::png:
                        imagepng($image, $this->file["tmp_name"]);
                        break;
                    case FileType::webp:
                        imagepalettetotruecolor($image);
                        imagewebp($image, $this->file["tmp_name"]);
                        break;
                    case FileType::bmp:
                        imagebmp($image, $this->file["tmp_name"]);
                        break;
                    case FileType::gif:
                        imagegif($image, $this->file["tmp_name"]);
                        break;
                    case FileType::avif:
                        imageavif($image, $this->file["tmp_name"]);
                        break;
                    default:
                        (new ApiErrorResponse(ApiMessage::uploadingError->value . "Can't automaticaly convert to FileType::" . $this->imageSaveAs->name))->send();
                }
            }
        }


        $response = ($this->onBeforeUploadCallback)($this->file["tmp_name"], $fileIndex, $fileCount);

        if ($response instanceof ApiResponse) $response->send();

        $newPath = PREFIX . $this->savePath . ($this->saveName)($_POST["fileIndex"], $_POST["fileCount"]) . '.' . ($this->imageSaveAs->name ?? $sourceFileType->name);

        $result = move_uploaded_file($this->file["tmp_name"], $newPath);

        if ($result) {
            $response = ($this->onAfterUploadCallback)($newPath, $fileIndex, $fileCount);

            if ($response instanceof ApiResponse) $response->send();

            $response = new ApiSuccessResponse(ApiMessage::uploadSucceeded);
        } else {
            $response = new ApiErrorResponse(ApiMessage::uploadingError->value . "Invalid temporary file name (server-side error)");
        }

        $response->send();
    }
}
