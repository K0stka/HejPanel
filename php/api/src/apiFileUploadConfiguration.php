<?php
enum FileType: string {
        # Images
    case jpeg = "image/jpeg"; #JPEG images 
    case png = "image/png"; #Portable Network Graphics 
    case webp = "image/webp"; #WEBP image
    case svg = "image/svg+xml"; #Scalable Vector Graphics (SVG)
    case tiff = "image/tiff"; #Tagged Image File Format (TIFF) 
    case bmp = "image/bmp"; #Windows OS/2 Bitmap Graphics
    case gif = "image/gif"; #Graphics Interchange Format (GIF) 
    case ico = "image/vnd.microsoft.icon"; #Icon format 
    case avif = "image/avif"; #AVIF image

        # Sound
    case mp3 = "audio/mpeg"; #MP3 audio 
    case wav = "audio/wav"; #Waveform Audio Format 
    case aac = "audio/aac"; #AAC audio 
    case weba = "audio/webm"; #WEBM audio

        # Video
    case mp4 = "video/mp4"; #MP4 video 
    case avi = "video/x-msvideo"; #AVI: Audio Video Interleave 
    case webm = "video/webm"; #WEBM video
    case mpeg = "video/mpeg"; #MPEG Video

        # Text
    case txt = "text/plain"; #Text, (generally {{Glossary("ASCII")}} or ISO 8859-_n_) 
    case pdf = "application/pdf"; #Adobe [Portable Document Format](https://www.adobe.com/acrobat/about-adobe-pdf.html) (PDF)
    case epub = "application/epub+zip"; #Electronic publication (EPUB) 

        # Data
    case json = "application/json"; #JSON format 
    case csv = "text/csv"; #Comma-separated values (CSV)
    case bin = "application/octet-stream"; #Any kind of binary data 

        # MS Office
    case doc = "application/msword"; #Microsoft Word
    case docx = "application/vnd.openxmlformats-officedocument.wordprocessingml.document"; #Microsoft Word (OpenXML)
    case ppt = "application/vnd.ms-powerpoint"; #Microsoft PowerPoint
    case pptx = "application/vnd.openxmlformats-officedocument.presentationml.presentation"; #Microsoft PowerPoint (OpenXML)
    case xls = "application/vnd.ms-excel"; #Microsoft Excel 
    case xlsx = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"; #Microsoft Excel (OpenXML) 

        # Open Office
    case odp = "application/vnd.oasis.opendocument.presentation"; #OpenDocument presentation document
    case ods = "application/vnd.oasis.opendocument.spreadsheet"; #OpenDocument spreadsheet document 
    case odt = "application/vnd.oasis.opendocument.text"; #OpenDocument text document

        # Misc
    case zip = "application/zip"; #ZIP archive
}

define("IMAGES", [FileType::jpeg, FileType::png, FileType::webp, FileType::svg, FileType::tiff, FileType::bmp, FileType::gif, FileType::ico, FileType::avif]);
define("AUTOCONVERTIBLE_IMAGES", [FileType::jpeg, FileType::png, FileType::webp, FileType::bmp, FileType::gif, FileType::avif]);

class ApiFileUploadConfiguration {
    public string $fileInputName;
    public $saveName;
    public ?FileType $imageSaveAs;
    public string $savePath;
    public int $minFiles;
    public int $maxFiles;
    public int $maxFileSizeMB;
    /** @var FileType[] */
    public array $allowedFileTypes;
    public $onBeforeUpload;
    public $onAfterUpload;

    public function __construct(
        callable $saveName,
        int $maxFileSizeMB,
        array $allowedFileTypes,
        ?FileType $imageSaveAs = null,
        string $savePath = "/uploads",
        string $fileInputName = "file",
        int $minFiles = 1,
        int $maxFiles = 1,
        callable $onBeforeUpload = null,
        callable $onAfterUpload = null
    ) {
        $this->fileInputName = $fileInputName;
        $this->saveName = $saveName;
        $this->imageSaveAs = $imageSaveAs;
        $this->savePath = rtrim(ltrim($savePath, "/"), "/") . "/";
        $this->minFiles = $minFiles;
        $this->maxFiles = $maxFiles;
        $this->maxFileSizeMB = $maxFileSizeMB;
        $this->allowedFileTypes = $allowedFileTypes;
        $this->onBeforeUpload = $onBeforeUpload ?? fn () => null;
        $this->onAfterUpload = $onAfterUpload ?? fn () => null;
    }
}
