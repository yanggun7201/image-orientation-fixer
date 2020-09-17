<?php
class ImageOrientationFixer extends DataExtension
{

    /**
     * onBeforeWrite
     * @return void
     * @throws ImagickException
     */
    public function onBeforeWrite()
    {
        $path = $this->owner->getFullPath();

        if ($orientation = $this->get_orientation($path)) {
            if (extension_loaded('imagick')) {
                $this->rotate_imagick($path, $orientation);
            } elseif (extension_loaded('gd')) {
                $info = getimagesize($path);

                switch ($info['mime']) {
                    case 'image/gif':
                        $this->rotate_gif_with_gd($path, $orientation);
                        break;

                    case 'image/jpeg':
                        $this->rotate_jpg_with_gd($path, $orientation);
                        break;

                    case 'image/png':
                        $this->rotate_png_with_gd($path, $orientation);
                        break;
                }
            }
        }
        parent::onBeforeWrite();
    }

    /**
     * Get the orientation
     * @param string $path Image path.
     * @return boolean
     */
    private function get_orientation($path)
    {
        if (!file_exists($path)) {
            return false;
        }

        try {
            $exif_data = @exif_read_data($path);
            $orientation = !empty($exif_data['Orientation']) ? $exif_data['Orientation'] : null;
            /**
            * this image will help you understand the orientation and the difference between encoded and printed
            * http://www.kendawson.ca/wp-content/uploads/orient_flag2.gif
            */
            return $orientation == 1 ? false : $orientation;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Rotate the image with Image Magick.
     * @param string  $imagePath   Image path.
     * @param integer $orientation Exif Orientation.
     * @return void
     * @throws ImagickException
     */
    private function rotate_imagick($imagePath, $orientation)
    {
        $imagick = new Imagick($imagePath);
        $imagick->setImageOrientation(imagick::ORIENTATION_TOPLEFT);
        $deg = 0;

        switch ($orientation) {
            case 2:
                $deg = 180;
                $imagick->flipImage();
                break;

            case 3:
                $deg = -180;
                break;

            case 4:
                $deg = -180;
                $imagick->flopImage();
                break;

            case 5:
                $deg = -90;
                $imagick->flopImage();
                break;

            case 6:
                $deg = 90;
                break;

            case 7:
                $deg = -90;
                $imagick->flipImage();
                break;

            case 8:
                $deg = -90;
                break;
        }
        $imagick->rotateImage(new ImagickPixel('#00000000'), $deg);
        $imagick->writeImage($imagePath);
        $imagick->clear();
        $imagick->destroy();
    }

    /**
     * Rotate the gif with GD.
     * @param string  $path        Image path.
     * @param integer $orientation Exif Orientation.
     * @return void
     */
    private function rotate_gif_with_gd($path, $orientation)
    {
        $image = $this->rotate_gd(imagecreatefromgif($path), $orientation);
        imagegif($image, $path, 100);
        imagedestroy($image);
    }

    /**
     * Rotate the jpg with GD.
     * @param string  $path        Image path.
     * @param integer $orientation Exif Orientation.
     * @return void
     */
    private function rotate_jpg_with_gd($path, $orientation)
    {
        $image = $this->rotate_gd(imagecreatefromjpeg($path), $orientation);
        imagejpeg($image, $path, 100);
        imagedestroy($image);
    }

    /**
     * Rotate the png with GD.
     * @param string  $path        Image path.
     * @param integer $orientation Exif Orientation.
     * @return void
     */
    private function rotate_png_with_gd($path, $orientation)
    {
        $image = $this->rotate_gd(imagecreatefrompng($path), $orientation);
        imagesavealpha($image, true);
        imagealphablending($image, true);
        imagepng($image, $path, 100);
        imagedestroy($image);
    }

    /**
     * Rotate the image with GD.
     * @param mixed   $image       Image file.
     * @param integer $orientation Exif Orientation.
     * @return mixed
     */
    private function rotate_gd($image, $orientation)
    {
        switch ($orientation) {
            case 2:
                $image = imagerotate($image, 180, 0);
                imageflip($image, IMG_FLIP_VERTICAL);
                break;

            case 3:
                $image = imagerotate($image, 180, 0);
                break;

            case 4:
                $image = imagerotate($image, 180, 0);
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;

            case 5:
                $image = imagerotate($image, -90, 0);
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;

            case 6:
                $image = imagerotate($image, -90, 0);
                break;

            case 7:
                $image = imagerotate($image, -90, 0);
                imageflip($image, IMG_FLIP_VERTICAL);
                break;

            case 8:
                $image = imagerotate($image, 90, 0);
                break;
        }

        return $image;
    }
}
