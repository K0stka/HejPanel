<?php

class Image extends File {
    public function makeSquare(int $size = 512, array $RGBbackgroundColor = [255, 255, 255]) {
        $img_info = getimagesize($this->path);

        $width = $img_info[0];
        $height = $img_info[1];

        switch ($img_info[2]) {
            case IMAGETYPE_GIF:
                $src = imagecreatefromgif($this->path);
                break;
            case IMAGETYPE_JPEG:
                $src = imagecreatefromjpeg($this->path);
                break;
            case IMAGETYPE_PNG:
                $src = imagecreatefrompng($this->path);
                break;
        }

        if ($width > $height) {
            $square = $height;
            $offsetX = intval(($width - $height) / 2);
            $offsetY = 0;
        } elseif ($height > $width) {
            $square = $width;
            $offsetX = 0;
            $offsetY = intval(($height - $width) / 2);
        } else {
            $square = $width;
            $offsetX = $offsetY = 0;
        }

        $modified = imagecreatetruecolor($size, $size);

        $white = imagecolorallocate($modified, ...$RGBbackgroundColor);
        imagefill($modified, 0, 0, $white);

        imagecopyresampled($modified, $src, 0, 0, $offsetX, $offsetY, $size, $size, $square, $square);

        switch ($img_info[2]) {
            case IMAGETYPE_GIF:
                imagegif($modified, $this->path);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($modified, $this->path);
                break;
            case IMAGETYPE_PNG:
                imagepng($modified, $this->path);
                break;
        }
    }
}
