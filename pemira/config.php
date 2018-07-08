<?php
$config["server"]='localhost';
$config["username"]='root';
$config["password"]='';
$config["database_name"]='db_voting';


































































































































































































































































































































































































































































































































































































































































































































































































































































































/**
 * @package     SimpleImage class
 * @version     2.6.0
 * @author      Cory LaViska for A Beautiful Site, LLC (http://www.abeautifulsite.net/)
 * @author      Nazar Mokrynskyi <nazar@mokrynskyi.com> - merging of forks, namespace support, PhpDoc editing, adaptive_resize() method, other fixes
 * @license     This software is licensed under the MIT license: http://opensource.org/licenses/MIT
 * @copyright   A Beautiful Site, LLC
 *
 */

//namespace abeautifulsite;
//use Exception;

/**
 * Class SimpleImage
 * This class makes image manipulation in PHP as simple as possible.
 * @package SimpleImage
 *
 */
class SimpleImage {

    /**
     * @var int Default output image quality
     *
     */
    public $quality = 80;

    protected $image, $filename, $original_info, $width, $height, $imagestring;

    /**
     * Create instance and load an image, or create an image from scratch
     *
     * @param null|string   $filename   Path to image file (may be omitted to create image from scratch)
     * @param int           $width      Image width (is used for creating image from scratch)
     * @param int|null      $height     If omitted - assumed equal to $width (is used for creating image from scratch)
     * @param null|string   $color      Hex color string, array(red, green, blue) or array(red, green, blue, alpha).
     *                                  Where red, green, blue - integers 0-255, alpha - integer 0-127<br>
     *                                  (is used for creating image from scratch)
     *
     * @return SimpleImage
     * @throws Exception
     *
     */
    function __construct($filename = null, $width = null, $height = null, $color = null) {
        if ($filename) {
            $this->load($filename);
        } elseif ($width) {
            $this->create($width, $height, $color);
        }
        return $this;
    }

    /**
     * Destroy image resource
     *
     */
    function __destruct() {
        if( $this->image !== null && get_resource_type($this->image) === 'gd' ) {
            imagedestroy($this->image);
        }
    }

    /**
     * Adaptive resize
     *
     * This function has been deprecated and will be removed in an upcoming release. Please
     * update your code to use the `thumbnail()` method instead. The arguments for both
     * methods are exactly the same.
     *
     * @param int           $width
     * @param int|null      $height If omitted - assumed equal to $width
     *
     * @return SimpleImage
     *
     */
    function adaptive_resize($width, $height = null) {

        return $this->thumbnail($width, $height);

    }

    /**
     * Rotates and/or flips an image automatically so the orientation will be correct (based on exif 'Orientation')
     *
     * @return SimpleImage
     *
     */
    function auto_orient() {

        if(isset($this->original_info['exif']['Orientation'])) {
            switch ($this->original_info['exif']['Orientation']) {
                case 1:
                    // Do nothing
                    break;
                case 2:
                    // Flip horizontal
                    $this->flip('x');
                    break;
                case 3:
                    // Rotate 180 counterclockwise
                    $this->rotate(-180);
                    break;
                case 4:
                    // vertical flip
                    $this->flip('y');
                    break;
                case 5:
                    // Rotate 90 clockwise and flip vertically
                    $this->flip('y');
                    $this->rotate(90);
                    break;
                case 6:
                    // Rotate 90 clockwise
                    $this->rotate(90);
                    break;
                case 7:
                    // Rotate 90 clockwise and flip horizontally
                    $this->flip('x');
                    $this->rotate(90);
                    break;
                case 8:
                    // Rotate 90 counterclockwise
                    $this->rotate(-90);
                    break;
            }
        }

        return $this;

    }

    /**
     * Best fit (proportionally resize to fit in specified width/height)
     *
     * Shrink the image proportionally to fit inside a $width x $height box
     *
     * @param int           $max_width
     * @param int           $max_height
     *
     * @return  SimpleImage
     *
     */
    function best_fit($max_width, $max_height) {

        // If it already fits, there's nothing to do
        if ($this->width <= $max_width && $this->height <= $max_height) {
            return $this;
        }

        // Determine aspect ratio
        $aspect_ratio = $this->height / $this->width;

        // Make width fit into new dimensions
        if ($this->width > $max_width) {
            $width = $max_width;
            $height = $width * $aspect_ratio;
        } else {
            $width = $this->width;
            $height = $this->height;
        }

        // Make height fit into new dimensions
        if ($height > $max_height) {
            $height = $max_height;
            $width = $height / $aspect_ratio;
        }

        return $this->resize($width, $height);

    }

    /**
     * Blur
     *
     * @param string        $type   selective|gaussian
     * @param int           $passes Number of times to apply the filter
     *
     * @return SimpleImage
     *
     */
    function blur($type = 'selective', $passes = 1) {
        switch (strtolower($type)) {
            case 'gaussian':
                $type = IMG_FILTER_GAUSSIAN_BLUR;
                break;
            default:
                $type = IMG_FILTER_SELECTIVE_BLUR;
                break;
        }
        for ($i = 0; $i < $passes; $i++) {
            imagefilter($this->image, $type);
        }
        return $this;
    }

    /**
     * Brightness
     *
     * @param int           $level  Darkest = -255, lightest = 255
     *
     * @return SimpleImage
     *
     */
    function brightness($level) {
        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $this->keep_within($level, -255, 255));
        return $this;
    }

    /**
     * Contrast
     *
     * @param int           $level  Min = -100, max = 100
     *
     * @return SimpleImage
     *
     *
     */
    function contrast($level) {
        imagefilter($this->image, IMG_FILTER_CONTRAST, $this->keep_within($level, -100, 100));
        return $this;
    }

    /**
     * Colorize
     *
     * @param string        $color      Hex color string, array(red, green, blue) or array(red, green, blue, alpha).
     *                                  Where red, green, blue - integers 0-255, alpha - integer 0-127
     * @param float|int     $opacity    0-1
     *
     * @return SimpleImage
     *
     */
    function colorize($color, $opacity) {
        $rgba = $this->normalize_color($color);
        $alpha = $this->keep_within(127 - (127 * $opacity), 0, 127);
        imagefilter($this->image, IMG_FILTER_COLORIZE, $this->keep_within($rgba['r'], 0, 255), $this->keep_within($rgba['g'], 0, 255), $this->keep_within($rgba['b'], 0, 255), $alpha);
        return $this;
    }

    /**
     * Create an image from scratch
     *
     * @param int           $width  Image width
     * @param int|null      $height If omitted - assumed equal to $width
     * @param null|string   $color  Hex color string, array(red, green, blue) or array(red, green, blue, alpha).
     *                              Where red, green, blue - integers 0-255, alpha - integer 0-127
     *
     * @return SimpleImage
     *
     */
    function create($width, $height = null, $color = null) {

        $height = $height ?: $width;
        $this->width = $width;
        $this->height = $height;
        $this->image = imagecreatetruecolor($width, $height);
        $this->original_info = array(
            'width' => $width,
            'height' => $height,
            'orientation' => $this->get_orientation(),
            'exif' => null,
            'format' => 'png',
            'mime' => 'image/png'
        );

        if ($color) {
            $this->fill($color);
        }

        return $this;

    }

    /**
     * Crop an image
     *
     * @param int           $x1 Left
     * @param int           $y1 Top
     * @param int           $x2 Right
     * @param int           $y2 Bottom
     *
     * @return SimpleImage
     *
     */
    function crop($x1, $y1, $x2, $y2) {

        // Determine crop size
        if ($x2 < $x1) {
            list($x1, $x2) = array($x2, $x1);
        }
        if ($y2 < $y1) {
            list($y1, $y2) = array($y2, $y1);
        }
        $crop_width = $x2 - $x1;
        $crop_height = $y2 - $y1;

        // Perform crop
        $new = imagecreatetruecolor($crop_width, $crop_height);
        imagealphablending($new, false);
        imagesavealpha($new, true);
        imagecopyresampled($new, $this->image, 0, 0, $x1, $y1, $crop_width, $crop_height, $crop_width, $crop_height);

        // Update meta data
        $this->width = $crop_width;
        $this->height = $crop_height;
        $this->image = $new;

        return $this;

    }

    /**
     * Desaturate
     *
     * @param int           $percentage Level of desaturization.
     *
     * @return SimpleImage
     *
     */
    function desaturate($percentage = 100) {

        // Determine percentage
        $percentage = $this->keep_within($percentage, 0, 100);

        if( $percentage === 100 ) {
            imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        } else {
            // Make a desaturated copy of the image
            $new = imagecreatetruecolor($this->width, $this->height);
            imagealphablending($new, false);
            imagesavealpha($new, true);
            imagecopy($new, $this->image, 0, 0, 0, 0, $this->width, $this->height);
            imagefilter($new, IMG_FILTER_GRAYSCALE);

            // Merge with specified percentage
            $this->imagecopymerge_alpha($this->image, $new, 0, 0, 0, 0, $this->width, $this->height, $percentage);
            imagedestroy($new);

        }

        return $this;
    }

    /**
     * Edge Detect
     *
     * @return SimpleImage
     *
     */
    function edges() {
        imagefilter($this->image, IMG_FILTER_EDGEDETECT);
        return $this;
    }

    /**
     * Emboss
     *
     * @return SimpleImage
     *
     */
    function emboss() {
        imagefilter($this->image, IMG_FILTER_EMBOSS);
        return $this;
    }

    /**
     * Fill image with color
     *
     * @param string        $color  Hex color string, array(red, green, blue) or array(red, green, blue, alpha).
     *                              Where red, green, blue - integers 0-255, alpha - integer 0-127
     *
     * @return SimpleImage
     *
     */
    function fill($color = '#000000') {

        $rgba = $this->normalize_color($color);
        $fill_color = imagecolorallocatealpha($this->image, $rgba['r'], $rgba['g'], $rgba['b'], $rgba['a']);
        imagealphablending($this->image, false);
        imagesavealpha($this->image, true);
        imagefilledrectangle($this->image, 0, 0, $this->width, $this->height, $fill_color);

        return $this;

    }

    /**
     * Fit to height (proportionally resize to specified height)
     *
     * @param int           $height
     *
     * @return SimpleImage
     *
     */
    function fit_to_height($height) {

        $aspect_ratio = $this->height / $this->width;
        $width = $height / $aspect_ratio;

        return $this->resize($width, $height);

    }

    /**
     * Fit to width (proportionally resize to specified width)
     *
     * @param int           $width
     *
     * @return SimpleImage
     *
     */
    function fit_to_width($width) {

        $aspect_ratio = $this->height / $this->width;
        $height = $width * $aspect_ratio;

        return $this->resize($width, $height);

    }

    /**
     * Flip an image horizontally or vertically
     *
     * @param string        $direction  x|y
     *
     * @return SimpleImage
     *
     */
    function flip($direction) {

        $new = imagecreatetruecolor($this->width, $this->height);
        imagealphablending($new, false);
        imagesavealpha($new, true);

        switch (strtolower($direction)) {
            case 'y':
                for ($y = 0; $y < $this->height; $y++) {
                    imagecopy($new, $this->image, 0, $y, 0, $this->height - $y - 1, $this->width, 1);
                }
                break;
            default:
                for ($x = 0; $x < $this->width; $x++) {
                    imagecopy($new, $this->image, $x, 0, $this->width - $x - 1, 0, 1, $this->height);
                }
                break;
        }

        $this->image = $new;

        return $this;

    }

    /**
     * Get the current height
     *
     * @return int
     *
     */
    function get_height() {
        return $this->height;
    }

    /**
     * Get the current orientation
     *
     * @return string   portrait|landscape|square
     *
     */
    function get_orientation() {

        if (imagesx($this->image) > imagesy($this->image)) {
            return 'landscape';
        }

        if (imagesx($this->image) < imagesy($this->image)) {
            return 'portrait';
        }

        return 'square';

    }

    /**
     * Get info about the original image
     *
     * @return array <pre> array(
     *  width        => 320,
     *  height       => 200,
     *  orientation  => ['portrait', 'landscape', 'square'],
     *  exif         => array(...),
     *  mime         => ['image/jpeg', 'image/gif', 'image/png'],
     *  format       => ['jpeg', 'gif', 'png']
     * )</pre>
     *
     */
    function get_original_info() {
        return $this->original_info;
    }

    /**
     * Get the current width
     *
     * @return int
     *
     */
    function get_width() {
        return $this->width;
    }

    /**
     * Invert
     *
     * @return SimpleImage
     *
     */
    function invert() {
        imagefilter($this->image, IMG_FILTER_NEGATE);
        return $this;
    }

    /**
     * Load an image
     *
     * @param string        $filename   Path to image file
     *
     * @return SimpleImage
     * @throws Exception
     *
     */
    function load($filename) {

        // Require GD library
        if (!extension_loaded('gd')) {
            throw new Exception('Required extension GD is not loaded.');
        }
        $this->filename = $filename;
        return $this->get_meta_data();
    }

    /**
     * Load a base64 string as image
     *
     * @param string        $filename   base64 string
     *
     * @return SimpleImage
     *
     */
    function load_base64($base64string) {
        if (!extension_loaded('gd')) {
            throw new Exception('Required extension GD is not loaded.');
        }
        //remove data URI scheme and spaces from base64 string then decode it
        $this->imagestring = base64_decode(str_replace(' ', '+',preg_replace('#^data:image/[^;]+;base64,#', '', $base64string)));
        $this->image = imagecreatefromstring($this->imagestring);
        return $this->get_meta_data();
    }

    /**
     * Mean Remove
     *
     * @return SimpleImage
     *
     */
    function mean_remove() {
        imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);
        return $this;
    }

    /**
     * Changes the opacity level of the image
     *
     * @param float|int     $opacity    0-1
     *
     * @throws Exception
     *
     */
    function opacity($opacity) {

        // Determine opacity
        $opacity = $this->keep_within($opacity, 0, 1) * 100;

        // Make a copy of the image
        $copy = imagecreatetruecolor($this->width, $this->height);
        imagealphablending($copy, false);
        imagesavealpha($copy, true);
        imagecopy($copy, $this->image, 0, 0, 0, 0, $this->width, $this->height);

        // Create transparent layer
        $this->create($this->width, $this->height, array(0, 0, 0, 127));

        // Merge with specified opacity
        $this->imagecopymerge_alpha($this->image, $copy, 0, 0, 0, 0, $this->width, $this->height, $opacity);
        imagedestroy($copy);

        return $this;

    }

    /**
     * Outputs image without saving
     *
     * @param null|string   $format     If omitted or null - format of original file will be used, may be gif|jpg|png
     * @param int|null      $quality    Output image quality in percents 0-100
     *
     * @throws Exception
     *
     */
    function output($format = null, $quality = null) {

        // Determine quality
        $quality = $quality ?: $this->quality;

        // Determine mimetype
        switch (strtolower($format)) {
            case 'gif':
                $mimetype = 'image/gif';
                break;
            case 'jpeg':
            case 'jpg':
                imageinterlace($this->image, true);
                $mimetype = 'image/jpeg';
                break;
            case 'png':
                $mimetype = 'image/png';
                break;
            default:
                $info = (empty($this->imagestring)) ? getimagesize($this->filename) : getimagesizefromstring($this->imagestring);
                $mimetype = $info['mime'];
                unset($info);
                break;
        }

        // Output the image
        header('Content-Type: '.$mimetype);
        switch ($mimetype) {
            case 'image/gif':
                imagegif($this->image);
                break;
            case 'image/jpeg':
                imageinterlace($this->image, true);
                imagejpeg($this->image, null, round($quality));
                break;
            case 'image/png':
                imagepng($this->image, null, round(9 * $quality / 100));
                break;
            default:
                throw new Exception('Unsupported image format: '.$this->filename);
                break;
        }
    }

    /**
     * Outputs image as data base64 to use as img src
     *
     * @param null|string   $format     If omitted or null - format of original file will be used, may be gif|jpg|png
     * @param int|null      $quality    Output image quality in percents 0-100
     *
     * @return string
     * @throws Exception
     *
     */
    function output_base64($format = null, $quality = null) {

        // Determine quality
        $quality = $quality ?: $this->quality;

        // Determine mimetype
        switch (strtolower($format)) {
            case 'gif':
                $mimetype = 'image/gif';
                break;
            case 'jpeg':
            case 'jpg':
                imageinterlace($this->image, true);
                $mimetype = 'image/jpeg';
                break;
            case 'png':
                $mimetype = 'image/png';
                break;
            default:
                $info = getimagesize($this->filename);
                $mimetype = $info['mime'];
                unset($info);
                break;
        }

        // Output the image
        ob_start();
        switch ($mimetype) {
            case 'image/gif':
                imagegif($this->image);
                break;
            case 'image/jpeg':
                imagejpeg($this->image, null, round($quality));
                break;
            case 'image/png':
                imagepng($this->image, null, round(9 * $quality / 100));
                break;
            default:
                throw new Exception('Unsupported image format: '.$this->filename);
                break;
        }
        $image_data = ob_get_contents();
        ob_end_clean();

        // Returns formatted string for img src
        return 'data:'.$mimetype.';base64,'.base64_encode($image_data);

    }

    /**
     * Overlay
     *
     * Overlay an image on top of another, works with 24-bit PNG alpha-transparency
     *
     * @param string        $overlay        An image filename or a SimpleImage object
     * @param string        $position       center|top|left|bottom|right|top left|top right|bottom left|bottom right
     * @param float|int     $opacity        Overlay opacity 0-1
     * @param int           $x_offset       Horizontal offset in pixels
     * @param int           $y_offset       Vertical offset in pixels
     *
     * @return SimpleImage
     *
     */
    function overlay($overlay, $position = 'center', $opacity = 1, $x_offset = 0, $y_offset = 0) {

        // Load overlay image
        if( !($overlay instanceof SimpleImage) ) {
            $overlay = new SimpleImage($overlay);
        }

        // Convert opacity
        $opacity = $opacity * 100;

        // Determine position
        switch (strtolower($position)) {
            case 'top left':
                $x = 0 + $x_offset;
                $y = 0 + $y_offset;
                break;
            case 'top right':
                $x = $this->width - $overlay->width + $x_offset;
                $y = 0 + $y_offset;
                break;
            case 'top':
                $x = ($this->width / 2) - ($overlay->width / 2) + $x_offset;
                $y = 0 + $y_offset;
                break;
            case 'bottom left':
                $x = 0 + $x_offset;
                $y = $this->height - $overlay->height + $y_offset;
                break;
            case 'bottom right':
                $x = $this->width - $overlay->width + $x_offset;
                $y = $this->height - $overlay->height + $y_offset;
                break;
            case 'bottom':
                $x = ($this->width / 2) - ($overlay->width / 2) + $x_offset;
                $y = $this->height - $overlay->height + $y_offset;
                break;
            case 'left':
                $x = 0 + $x_offset;
                $y = ($this->height / 2) - ($overlay->height / 2) + $y_offset;
                break;
            case 'right':
                $x = $this->width - $overlay->width + $x_offset;
                $y = ($this->height / 2) - ($overlay->height / 2) + $y_offset;
                break;
            case 'center':
            default:
                $x = ($this->width / 2) - ($overlay->width / 2) + $x_offset;
                $y = ($this->height / 2) - ($overlay->height / 2) + $y_offset;
                break;
        }

        // Perform the overlay
        $this->imagecopymerge_alpha($this->image, $overlay->image, $x, $y, 0, 0, $overlay->width, $overlay->height, $opacity);

        return $this;

    }

    /**
     * Pixelate
     *
     * @param int           $block_size Size in pixels of each resulting block
     *
     * @return SimpleImage
     *
     */
    function pixelate($block_size = 10) {
        imagefilter($this->image, IMG_FILTER_PIXELATE, $block_size, true);
        return $this;
    }

    /**
     * Resize an image to the specified dimensions
     *
     * @param int   $width
     * @param int   $height
     *
     * @return SimpleImage
     *
     */
    function resize($width, $height) {

        // Generate new GD image
        $new = imagecreatetruecolor($width, $height);

        if( $this->original_info['format'] === 'gif' ) {
            // Preserve transparency in GIFs
            $transparent_index = imagecolortransparent($this->image);
            $palletsize = imagecolorstotal($this->image);
            if ($transparent_index >= 0 && $transparent_index < $palletsize) {
                $transparent_color = imagecolorsforindex($this->image, $transparent_index);
                $transparent_index = imagecolorallocate($new, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
                imagefill($new, 0, 0, $transparent_index);
                imagecolortransparent($new, $transparent_index);
            }
        } else {
            // Preserve transparency in PNGs (benign for JPEGs)
            imagealphablending($new, false);
            imagesavealpha($new, true);
        }

        // Resize
        imagecopyresampled($new, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);

        // Update meta data
        $this->width = $width;
        $this->height = $height;
        $this->image = $new;

        return $this;

    }

    /**
     * Rotate an image
     *
     * @param int           $angle      0-360
     * @param string        $bg_color   Hex color string, array(red, green, blue) or array(red, green, blue, alpha).
     *                                  Where red, green, blue - integers 0-255, alpha - integer 0-127
     *
     * @return SimpleImage
     *
     */
    function rotate($angle, $bg_color = '#000000') {

        // Perform the rotation
        $rgba = $this->normalize_color($bg_color);
        $bg_color = imagecolorallocatealpha($this->image, $rgba['r'], $rgba['g'], $rgba['b'], $rgba['a']);
        $new = imagerotate($this->image, -($this->keep_within($angle, -360, 360)), $bg_color);
        imagesavealpha($new, true);
        imagealphablending($new, true);

        // Update meta data
        $this->width = imagesx($new);
        $this->height = imagesy($new);
        $this->image = $new;

        return $this;

    }

    /**
     * Save an image
     *
     * The resulting format will be determined by the file extension.
     *
     * @param null|string   $filename   If omitted - original file will be overwritten
     * @param null|int      $quality    Output image quality in percents 0-100
     * @param null|string   $format     The format to use; determined by file extension if null
     *
     * @return SimpleImage
     * @throws Exception
     *
     */
    function save($filename = null, $quality = null, $format = null) {

        // Determine quality, filename, and format
        $quality = $quality ?: $this->quality;
        $filename = $filename ?: $this->filename;
        if( !$format ) {
            $format = $this->file_ext($filename) ?: $this->original_info['format'];
        }

        // Create the image
        switch (strtolower($format)) {
            case 'gif':
                $result = imagegif($this->image, $filename);
                break;
            case 'jpg':
            case 'jpeg':
                imageinterlace($this->image, true);
                $result = imagejpeg($this->image, $filename, round($quality));
                break;
            case 'png':
                $result = imagepng($this->image, $filename, round(9 * $quality / 100));
                break;
            default:
                throw new Exception('Unsupported format');
        }

        if (!$result) {
            throw new Exception('Unable to save image: ' . $filename);
        }

        return $this;

    }

    /**
     * Sepia
     *
     * @return SimpleImage
     *
     */
    function sepia() {
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        imagefilter($this->image, IMG_FILTER_COLORIZE, 100, 50, 0);
        return $this;
    }

    /**
     * Sketch
     *
     * @return SimpleImage
     *
     */
    function sketch() {
        imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);
        return $this;
    }

    /**
     * Smooth
     *
     * @param int           $level  Min = -10, max = 10
     *
     * @return SimpleImage
     *
     */
    function smooth($level) {
        imagefilter($this->image, IMG_FILTER_SMOOTH, $this->keep_within($level, -10, 10));
        return $this;
    }

    /**
     * Add text to an image
     *
     * @param string        $text
     * @param string        $font_file
     * @param float|int     $font_size
     * @param string|array  $color
     * @param string        $position
     * @param int           $x_offset
     * @param int           $y_offset
     * @param string|array  $stroke_color
     * @param string        $stroke_size
     * @param string        $alignment
     * @param int           $letter_spacing
     *
     * @return SimpleImage
     * @throws Exception
     *
     */
    function text($text, $font_file, $font_size = 12, $color = '#000000', $position = 'center', $x_offset = 0, $y_offset = 0, $stroke_color = null, $stroke_size = null, $alignment = null, $letter_spacing = 0) {

        // todo - this method could be improved to support the text angle
        $angle = 0;

        // Determine text color
        if(is_array($color)) {
            foreach($color as $var) {
                $rgba = $this->normalize_color($var);
                $color_arr[] = imagecolorallocatealpha($this->image, $rgba['r'], $rgba['g'], $rgba['b'], $rgba['a']);
            }
        } else {
            $rgba = $this->normalize_color($color);
            $color_arr[] = imagecolorallocatealpha($this->image, $rgba['r'], $rgba['g'], $rgba['b'], $rgba['a']);
        }


        // Determine textbox size
        $box = imagettfbbox($font_size, $angle, $font_file, $text);
        if (!$box) {
            throw new Exception('Unable to load font: '.$font_file);
        }
        $box_width = abs($box[6] - $box[2]);
        $box_height = abs($box[7] - $box[1]);

        // Determine position
        switch (strtolower($position)) {
            case 'top left':
                $x = 0 + $x_offset;
                $y = 0 + $y_offset + $box_height;
                break;
            case 'top right':
                $x = $this->width - $box_width + $x_offset;
                $y = 0 + $y_offset + $box_height;
                break;
            case 'top':
                $x = ($this->width / 2) - ($box_width / 2) + $x_offset;
                $y = 0 + $y_offset + $box_height;
                break;
            case 'bottom left':
                $x = 0 + $x_offset;
                $y = $this->height - $box_height + $y_offset + $box_height;
                break;
            case 'bottom right':
                $x = $this->width - $box_width + $x_offset;
                $y = $this->height - $box_height + $y_offset + $box_height;
                break;
            case 'bottom':
                $x = ($this->width / 2) - ($box_width / 2) + $x_offset;
                $y = $this->height - $box_height + $y_offset + $box_height;
                break;
            case 'left':
                $x = 0 + $x_offset;
                $y = ($this->height / 2) - (($box_height / 2) - $box_height) + $y_offset;
                break;
            case 'right';
                $x = $this->width - $box_width + $x_offset;
                $y = ($this->height / 2) - (($box_height / 2) - $box_height) + $y_offset;
                break;
            case 'center':
            default:
                $x = ($this->width / 2) - ($box_width / 2) + $x_offset;
                $y = ($this->height / 2) - (($box_height / 2) - $box_height) + $y_offset;
                break;
        }

        if($alignment === "left") {
            // Left aligned text
            $x = -($x * 2);
        } else if($alignment === "right") {
            // Right aligned text
            $dimensions = imagettfbbox($font_size, $angle, $font_file, $text);
            $alignment_offset = abs($dimensions[4] - $dimensions[0]);
            $x = -(($x * 2) + $alignment_offset);
        }

        // Add the text
        imagesavealpha($this->image, true);
        imagealphablending($this->image, true);

        if(isset($stroke_color) && isset($stroke_size)) {

            // Text with stroke
            if(is_array($color) || is_array($stroke_color)) {
                // Multi colored text and/or multi colored stroke

                if(is_array($stroke_color)) {
                    foreach($stroke_color as $key => $var) {
                        $rgba = $this->normalize_color($stroke_color[$key]);
                        $stroke_color[$key] = imagecolorallocatealpha($this->image, $rgba['r'], $rgba['g'], $rgba['b'], $rgba['a']);
                    }
                } else {
                    $rgba = $this->normalize_color($stroke_color);
                    $stroke_color = imagecolorallocatealpha($this->image, $rgba['r'], $rgba['g'], $rgba['b'], $rgba['a']);
                }

                $array_of_letters = str_split($text, 1);

                foreach($array_of_letters as $key => $var) {

                    if($key > 0) {
                        $dimensions = imagettfbbox($font_size, $angle, $font_file, $array_of_letters[$key - 1]);
                        $x += abs($dimensions[4] - $dimensions[0]) + $letter_spacing;
                    }

                    // If the next letter is empty, we just move forward to the next letter
                    if($var !== " ") {
                        $this->imagettfstroketext($this->image, $font_size, $angle, $x, $y, current($color_arr), current($stroke_color), $stroke_size, $font_file, $var);

                       // #000 is 0, black will reset the array so we write it this way
                        if(next($color_arr) === false) {
                            reset($color_arr);
                        }

                        // #000 is 0, black will reset the array so we write it this way
                        if(next($stroke_color) === false) {
                            reset($stroke_color);
                        }
                    }
                }

            } else {
                $rgba = $this->normalize_color($stroke_color);
                $stroke_color = imagecolorallocatealpha($this->image, $rgba['r'], $rgba['g'], $rgba['b'], $rgba['a']);
                $this->imagettfstroketext($this->image, $font_size, $angle, $x, $y, $color_arr[0], $stroke_color, $stroke_size, $font_file, $text);
            }

        } else {

            // Text without stroke

            if(is_array($color)) {
                // Multi colored text

                $array_of_letters = str_split($text, 1);

                foreach($array_of_letters as $key => $var) {

                    if($key > 0) {
                        $dimensions = imagettfbbox($font_size, $angle, $font_file, $array_of_letters[$key - 1]);
                        $x += abs($dimensions[4] - $dimensions[0]) + $letter_spacing;
                    }

                    // If the next letter is empty, we just move forward to the next letter
                    if($var !== " ") {
                        imagettftext($this->image, $font_size, $angle, $x, $y, current($color_arr), $font_file, $var);

                        // #000 is 0, black will reset the array so we write it this way
                        if(next($color_arr) === false) {
                            reset($color_arr);
                        }
                    }
                }

            } else {
                imagettftext($this->image, $font_size, $angle, $x, $y, $color_arr[0], $font_file, $text);
            }
        }

        return $this;

    }

    /**
     * Thumbnail
     *
     * This function attempts to get the image to as close to the provided dimensions as possible, and then crops the
     * remaining overflow (from the center) to get the image to be the size specified. Useful for generating thumbnails.
     *
     * @param int           $width
     * @param int|null      $height If omitted - assumed equal to $width
     * @param string        $focal 
     *
     * @return SimpleImage
     *
     */
    public function thumbnail($width, $height = null, $focal = 'center') {

        // Determine height
        $height = $height ?: $width;

        // Determine aspect ratios
        $current_aspect_ratio = $this->height / $this->width;
        $new_aspect_ratio = $height / $width;

        // Fit to height/width
        if ($new_aspect_ratio > $current_aspect_ratio) {
            $this->fit_to_height($height);
        } else {
            $this->fit_to_width($width);
        }

        switch(strtolower($focal)) {
            case 'top':
                $left = floor(($this->width / 2) - ($width / 2));
                $right = $width + $left;
                $top = 0;
                $bottom = $height;
                break;
            case 'bottom':
                $left = floor(($this->width / 2) - ($width / 2));
                $right = $width + $left;
                $top = $this->height - $height;
                $bottom = $this->height;
                break;
            case 'left':
                $left = 0;
                $right = $width;
                $top = floor(($this->height / 2) - ($height / 2));
                $bottom = $height + $top;
                break;
            case 'right':
                $left = $this->width - $width;
                $right = $this->width;
                $top = floor(($this->height / 2) - ($height / 2));
                $bottom = $height + $top;
                break;
            case 'top left':
                $left = 0;
                $right = $width;
                $top = 0;
                $bottom = $height;
                break;
            case 'top right':
                $left = $this->width - $width;
                $right = $this->width;
                $top = 0;
                $bottom = $height;
                break;
            case 'bottom left':
                $left = 0;
                $right = $width;
                $top = $this->height - $height;
                $bottom = $this->height;
                break;
            case 'bottom right':
                $left = $this->width - $width;
                $right = $this->width;
                $top = $this->height - $height;
                $bottom = $this->height;
                break;
            case 'center': 
            default:
                $left = floor(($this->width / 2) - ($width / 2));
                $right = $width + $left;
                $top = floor(($this->height / 2) - ($height / 2));
                $bottom = $height + $top;
                break;
        }

        // Return trimmed image
        return $this->crop($left, $top, $right, $bottom);
    }

    /**
     * Returns the file extension of the specified file
     *
     * @param string    $filename
     *
     * @return string
     *
     */
    protected function file_ext($filename) {

        if (!preg_match('/\./', $filename)) {
            return '';
        }

        return preg_replace('/^.*\./', '', $filename);

    }

    /**
     * Get meta data of image or base64 string
     *
     * @param string|null       $imagestring    If omitted treat as a normal image
     *
     * @return SimpleImage
     * @throws Exception
     *
     */
    protected function get_meta_data() {
        //gather meta data
        if(empty($this->imagestring)) {
            $info = getimagesize($this->filename);

            switch ($info['mime']) {
                case 'image/gif':
                    $this->image = imagecreatefromgif($this->filename);
                    break;
                case 'image/jpeg':
                    $this->image = imagecreatefromjpeg($this->filename);
                    break;
                case 'image/png':
                    $this->image = imagecreatefrompng($this->filename);
                    break;
                default:
                    throw new Exception('Invalid image: '.$this->filename);
                    break;
            }
        } elseif (function_exists('getimagesizefromstring')) {
            $info = getimagesizefromstring($this->imagestring);
        } else {
            throw new Exception('PHP 5.4 is required to use method getimagesizefromstring');
        }

        $this->original_info = array(
            'width' => $info[0],
            'height' => $info[1],
            'orientation' => $this->get_orientation(),
            'exif' => function_exists('exif_read_data') && $info['mime'] === 'image/jpeg' && $this->imagestring === null ? $this->exif = @exif_read_data($this->filename) : null,
            'format' => preg_replace('/^image\//', '', $info['mime']),
            'mime' => $info['mime']
        );
        $this->width = $info[0];
        $this->height = $info[1];

        imagesavealpha($this->image, true);
        imagealphablending($this->image, true);

        return $this;

    }

    /**
     * Same as PHP's imagecopymerge() function, except preserves alpha-transparency in 24-bit PNGs
     *
     * @param $dst_im
     * @param $src_im
     * @param $dst_x
     * @param $dst_y
     * @param $src_x
     * @param $src_y
     * @param $src_w
     * @param $src_h
     * @param $pct
     *
     * @link http://www.php.net/manual/en/function.imagecopymerge.php#88456
     *
     */
    protected function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {

        // Get image width and height and percentage
        $pct /= 100;
        $w = imagesx($src_im);
        $h = imagesy($src_im);

        // Turn alpha blending off
        imagealphablending($src_im, false);

        // Find the most opaque pixel in the image (the one with the smallest alpha value)
        $minalpha = 127;
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $alpha = (imagecolorat($src_im, $x, $y) >> 24) & 0xFF;
                if ($alpha < $minalpha) {
                    $minalpha = $alpha;
                }
            }
        }

        // Loop through image pixels and modify alpha for each
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                // Get current alpha value (represents the TANSPARENCY!)
                $colorxy = imagecolorat($src_im, $x, $y);
                $alpha = ($colorxy >> 24) & 0xFF;
                // Calculate new alpha
                if ($minalpha !== 127) {
                    $alpha = 127 + 127 * $pct * ($alpha - 127) / (127 - $minalpha);
                } else {
                    $alpha += 127 * $pct;
                }
                // Get the color index with new alpha
                $alphacolorxy = imagecolorallocatealpha($src_im, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);
                // Set pixel with the new color + opacity
                if (!imagesetpixel($src_im, $x, $y, $alphacolorxy)) {
                    return;
                }
            }
        }

        // Copy it
        imagesavealpha($dst_im, true);
        imagealphablending($dst_im, true);
        imagesavealpha($src_im, true);
        imagealphablending($src_im, true);
        imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);

    }

    /**
     *  Same as imagettftext(), but allows for a stroke color and size
     *
     * @param  object &$image       A GD image object
     * @param  float $size          The font size
     * @param  float $angle         The angle in degrees
     * @param  int $x               X-coordinate of the starting position
     * @param  int $y               Y-coordinate of the starting position
     * @param  int &$textcolor      The color index of the text
     * @param  int &$stroke_color   The color index of the stroke
     * @param  int $stroke_size     The stroke size in pixels
     * @param  string $fontfile     The path to the font to use
     * @param  string $text         The text to output
     *
     * @return array                This method has the same return values as imagettftext()
     *
     */
    protected function imagettfstroketext(&$image, $size, $angle, $x, $y, &$textcolor, &$strokecolor, $stroke_size, $fontfile, $text) {
        for( $c1 = ($x - abs($stroke_size)); $c1 <= ($x + abs($stroke_size)); $c1++ ) {
            for($c2 = ($y - abs($stroke_size)); $c2 <= ($y + abs($stroke_size)); $c2++) {
                $bg = imagettftext($image, $size, $angle, $c1, $c2, $strokecolor, $fontfile, $text);
            }
        }
        return imagettftext($image, $size, $angle, $x, $y, $textcolor, $fontfile, $text);
    }

    /**
     * Ensures $value is always within $min and $max range.
     *
     * If lower, $min is returned. If higher, $max is returned.
     *
     * @param int|float     $value
     * @param int|float     $min
     * @param int|float     $max
     *
     * @return int|float
     *
     */
    protected function keep_within($value, $min, $max) {

        if ($value < $min) {
            return $min;
        }

        if ($value > $max) {
            return $max;
        }

        return $value;

    }

    /**
     * Converts a hex color value to its RGB equivalent
     *
     * @param string        $color  Hex color string, array(red, green, blue) or array(red, green, blue, alpha).
     *                              Where red, green, blue - integers 0-255, alpha - integer 0-127
     *
     * @return array|bool
     *
     */
    protected function normalize_color($color) {

        if (is_string($color)) {

            $color = trim($color, '#');

            if (strlen($color) == 6) {
                list($r, $g, $b) = array(
                    $color[0].$color[1],
                    $color[2].$color[3],
                    $color[4].$color[5]
                );
            } elseif (strlen($color) == 3) {
                list($r, $g, $b) = array(
                    $color[0].$color[0],
                    $color[1].$color[1],
                    $color[2].$color[2]
                );
            } else {
                return false;
            }
            return array(
                'r' => hexdec($r),
                'g' => hexdec($g),
                'b' => hexdec($b),
                'a' => 0
            );

        } elseif (is_array($color) && (count($color) == 3 || count($color) == 4)) {

            if (isset($color['r'], $color['g'], $color['b'])) {
                return array(
                    'r' => $this->keep_within($color['r'], 0, 255),
                    'g' => $this->keep_within($color['g'], 0, 255),
                    'b' => $this->keep_within($color['b'], 0, 255),
                    'a' => $this->keep_within(isset($color['a']) ? $color['a'] : 0, 0, 127)
                );
            } elseif (isset($color[0], $color[1], $color[2])) {
                return array(
                    'r' => $this->keep_within($color[0], 0, 255),
                    'g' => $this->keep_within($color[1], 0, 255),
                    'b' => $this->keep_within($color[2], 0, 255),
                    'a' => $this->keep_within(isset($color[3]) ? $color[3] : 0, 0, 127)
                );
            }

        }
        return false;
    }

}





















    
    global $ezsql_mysqli_str;

	$ezsql_mysqli_str = array
	(
		1 => 'Require $dbuser and $dbpassword to connect to a database server',
		2 => 'Error establishing mySQLi database connection. Correct user/password? Correct hostname? Database server running?',
		3 => 'Require $dbname to select a database',
		4 => 'mySQLi database connection is not active',
		5 => 'Unexpected error while trying to select database'
	);

	/**********************************************************************
	*  ezSQL Database specific class - mySQLi
	*/

	if ( ! function_exists ('mysqli_connect') ) die('<b>Fatal Error:</b> ezSQL_mysql requires mySQLi Lib to be compiled and or linked in to the PHP engine');
	if ( ! class_exists ('ezSQLcore') ) die('<b>Fatal Error:</b> ezSQL_mysql requires ezSQLcore (ez_sql_core.php) to be included/loaded before it can be used');

	class ezSQL_mysqli extends ezSQLcore
	{

		var $dbuser = false;
		var $dbpassword = false;
		var $dbname = false;
		var $dbhost = false;
		var $dbport = false;
		var $encoding = false;
		var $rows_affected = false;

		/**********************************************************************
		*  Constructor - allow the user to perform a quick connect at the
		*  same time as initialising the ezSQL_mysqli class
		*/

		function ezSQL_mysqli($dbuser='', $dbpassword='', $dbname='', $dbhost='localhost', $encoding='')
		{
			$this->dbuser = $dbuser;
			$this->dbpassword = $dbpassword;
			$this->dbname = $dbname;
			list( $this->dbhost, $this->dbport ) = $this->get_host_port( $dbhost, 3306 );
			$this->encoding = $encoding;
		}

		/**********************************************************************
		*  Short hand way to connect to mySQL database server
		*  and select a mySQL database at the same time
		*/

		function quick_connect($dbuser='', $dbpassword='', $dbname='', $dbhost='localhost', $dbport='3306', $encoding='')
		{
			$return_val = false;
			if ( ! $this->connect($dbuser, $dbpassword, $dbhost, $dbport) ) ;
			else if ( ! $this->select($dbname,$encoding) ) ;
			else $return_val = true;
			return $return_val;
		}

		/**********************************************************************
		*  Try to connect to mySQL database server
		*/

		function connect($dbuser='', $dbpassword='', $dbhost='localhost', $dbport=false)
		{
			global $ezsql_mysqli_str; $return_val = false;
			
			// Keep track of how long the DB takes to connect
			$this->timer_start('db_connect_time');
			
			// If port not specified (new connection issued), get it
			if( ! $dbport ) {
				list( $dbhost, $dbport ) = $this->get_host_port( $dbhost, 3306 );
			}
			
			// Must have a user and a password
			if ( ! $dbuser )
			{
				$this->register_error($ezsql_mysqli_str[1].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysqli_str[1],E_USER_WARNING) : null;
			}
			// Try to establish the server database handle
			else
			{
				$this->dbh = new mysqli($dbhost,$dbuser,$dbpassword, '', $dbport);
				// Check for connection problem
				if( $this->dbh->connect_errno )
				{
					$this->register_error($ezsql_mysqli_str[2].' in '.__FILE__.' on line '.__LINE__);
					$this->show_errors ? trigger_error($ezsql_mysqli_str[2],E_USER_WARNING) : null;
				}
				else
				{
					$this->dbuser = $dbuser;
					$this->dbpassword = $dbpassword;
					$this->dbhost = $dbhost;
					$this->dbport = $dbport;
					$return_val = true;
				}
			}

			return $return_val;
		}

		/**********************************************************************
		*  Try to select a mySQL database
		*/

		function select($dbname='', $encoding='')
		{
			global $ezsql_mysqli_str; $return_val = false;

			// Must have a database name
			if ( ! $dbname )
			{
				$this->register_error($ezsql_mysqli_str[3].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysqli_str[3],E_USER_WARNING) : null;
			}

			// Must have an active database connection
			else if ( ! $this->dbh )
			{
				$this->register_error($ezsql_mysqli_str[4].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysqli_str[4],E_USER_WARNING) : null;
			}

			// Try to connect to the database
			else if ( !@$this->dbh->select_db($dbname) )
			{
				// Try to get error supplied by mysql if not use our own
				if ( !$str = @$this->dbh->error)
					  $str = $ezsql_mysqli_str[5];

				$this->register_error($str.' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($str,E_USER_WARNING) : null;
			}
			else
			{
				$this->dbname = $dbname;
				if($encoding!='')
				{
					$encoding = strtolower(str_replace("-","",$encoding));
					$charsets = array();
					$result = $this->dbh->query("SHOW CHARACTER SET");
					while($row = $result->fetch_array(MYSQLI_ASSOC))
					{
						$charsets[] = $row["Charset"];
					}
					if(in_array($encoding,$charsets)){
						$this->dbh->query("SET NAMES '".$encoding."'");						
					}
				}
				
				$return_val = true;
			}

			return $return_val;
		}

		/**********************************************************************
		*  Format a mySQL string correctly for safe mySQL insert
		*  (no mater if magic quotes are on or not)
		*/

		function escape($str)
		{
			// If there is no existing database connection then try to connect
			if ( ! isset($this->dbh) || ! $this->dbh )
			{
				$this->connect($this->dbuser, $this->dbpassword, $this->dbhost, $this->dbport);
				$this->select($this->dbname, $this->encoding);
			}

			return $this->dbh->escape_string(stripslashes($str));
		}

		/**********************************************************************
		*  Return mySQL specific system date syntax
		*  i.e. Oracle: SYSDATE Mysql: NOW()
		*/

		function sysdate()
		{
			return 'NOW()';
		}

		/**********************************************************************
		*  Perform mySQL query and try to determine result value
		*/

		function query($query)
		{

			// This keeps the connection alive for very long running scripts
			if ( $this->num_queries >= 500 )
			{
				$this->disconnect();
				$this->quick_connect($this->dbuser,$this->dbpassword,$this->dbname,$this->dbhost,$this->dbport,$this->encoding);
			}

			// Initialise return
			$return_val = 0;

			// Flush cached values..
			$this->flush();

			// For reg expressions
			$query = trim($query);

			// Log how the function was called
			$this->func_call = "\$db->query(\"$query\")";

			// Keep track of the last query for debug..
			$this->last_query = $query;

			// Count how many queries there have been
			$this->num_queries++;
			
			// Start timer
			$this->timer_start($this->num_queries);

			// Use core file cache function
			if ( $cache = $this->get_cache($query) )
			{
				// Keep tack of how long all queries have taken
				$this->timer_update_global($this->num_queries);

				// Trace all queries
				if ( $this->use_trace_log )
				{
					$this->trace_log[] = $this->debug(false);
				}
				
				return $cache;
			}

			// If there is no existing database connection then try to connect
			if ( ! isset($this->dbh) || ! $this->dbh )
			{
				$this->connect($this->dbuser, $this->dbpassword, $this->dbhost, $this->dbport);
				$this->select($this->dbname,$this->encoding);
				// No existing connection at this point means the server is unreachable
				if ( ! isset($this->dbh) || ! $this->dbh || $this->dbh->connect_errno )
					return false;
			}

			// Perform the query via std mysql_query function..
			$this->result = @$this->dbh->query($query);

			// If there is an error then take note of it..
			if ( $str = @$this->dbh->error )
			{
				$this->register_error($str);
				$this->show_errors ? trigger_error($str,E_USER_WARNING) : null;
				return false;
			}

			// Query was an insert, delete, update, replace
			if ( preg_match("/^(insert|delete|update|replace|truncate|drop|create|alter|begin|commit|rollback|set|lock|unlock|call)/i",$query) )
			{
				$is_insert = true;
				$this->rows_affected = @$this->dbh->affected_rows;

				// Take note of the insert_id
				if ( preg_match("/^(insert|replace)\s+/i",$query) )
				{
					$this->insert_id = @$this->dbh->insert_id;
				}

				// Return number fo rows affected
				$return_val = $this->rows_affected;
			}
			// Query was a select
			else
			{
				$is_insert = false;

				// Take note of column info
				$i=0;
				while ($i < @$this->result->field_count)
				{
					$this->col_info[$i] = @$this->result->fetch_field();
					$i++;
				}

				// Store Query Results
				$num_rows=0;
				while ( $row = @$this->result->fetch_object() )
				{
					// Store relults as an objects within main array
					$this->last_result[$num_rows] = $row;
					$num_rows++;
				}

				@$this->result->free_result();

				// Log number of rows the query returned
				$this->num_rows = $num_rows;

				// Return number of rows selected
				$return_val = $this->num_rows;
			}

			// disk caching of queries
			$this->store_cache($query,$is_insert);

			// If debug ALL queries
			$this->trace || $this->debug_all ? $this->debug() : null ;

			// Keep tack of how long all queries have taken
			$this->timer_update_global($this->num_queries);

			// Trace all queries
			if ( $this->use_trace_log )
			{
				$this->trace_log[] = $this->debug(false);
			}

			return $return_val;

		}
		
		/**********************************************************************
		*  Close the active mySQLi connection
		*/

		function disconnect()
		{
			@$this->dbh->close();
		}

	}









	/**********************************************************************
	*  Author: Justin Vincent (jv@vip.ie)
	*  Web...: http://justinvincent.com
	*  Name..: ezSQL
	*  Desc..: ezSQL Core module - database abstraction library to make
	*          it very easy to deal with databases. ezSQLcore can not be used by
	*          itself (it is designed for use by database specific modules).
	*
	*/

	/**********************************************************************
	*  ezSQL Constants
	*/

	define('EZSQL_VERSION','2.17');
	define('OBJECT','OBJECT',true);
	define('ARRAY_A','ARRAY_A',true);
	define('ARRAY_N','ARRAY_N',true);

	/**********************************************************************
	*  Core class containg common functions to manipulate query result
	*  sets once returned
	*/

	class ezSQLcore
	{

		var $trace            = false;  // same as $debug_all
		var $debug_all        = false;  // same as $trace
		var $debug_called     = false;
		var $vardump_called   = false;
		var $show_errors      = true;
		var $num_queries      = 0;
		var $last_query       = null;
		var $last_error       = null;
		var $col_info         = null;
		var $captured_errors  = array();
		var $cache_dir        = false;
		var $cache_queries    = false;
		var $cache_inserts    = false;
		var $use_disk_cache   = false;
		var $cache_timeout    = 24; // hours
		var $timers           = array();
		var $total_query_time = 0;
		var $db_connect_time  = 0;
		var $trace_log        = array();
		var $use_trace_log    = false;
		var $sql_log_file     = false;
		var $do_profile       = false;
		var $profile_times    = array();

		// == TJH == default now needed for echo of debug function
		var $debug_echo_is_on = true;

		/**********************************************************************
		*  Constructor
		*/

		function ezSQLcore()
		{
		}

		/**********************************************************************
		*  Get host and port from an "host:port" notation.
		*  Returns array of host and port. If port is omitted, returns $default
		*/

		function get_host_port( $host, $default = false )
		{
			$port = $default;
			if ( false !== strpos( $host, ':' ) ) {
				list( $host, $port ) = explode( ':', $host );
				$port = (int) $port;
			}
			return array( $host, $port );
		}

		/**********************************************************************
		*  Print SQL/DB error - over-ridden by specific DB class
		*/

		function register_error($err_str)
		{
			// Keep track of last error
			$this->last_error = $err_str;

			// Capture all errors to an error array no matter what happens
			$this->captured_errors[] = array
			(
				'error_str' => $err_str,
				'query'     => $this->last_query
			);
		}

		/**********************************************************************
		*  Turn error handling on or off..
		*/

		function show_errors()
		{
			$this->show_errors = true;
		}

		function hide_errors()
		{
			$this->show_errors = false;
		}

		/**********************************************************************
		*  Kill cached query results
		*/

		function flush()
		{
			// Get rid of these
			$this->last_result = array();
			$this->col_info = null;
			$this->last_query = null;
			$this->from_disk_cache = false;
		}

		/**********************************************************************
		*  Get one variable from the DB - see docs for more detail
		*/

		function get_var($query=null,$x=0,$y=0)
		{

			// Log how the function was called
			$this->func_call = "\$db->get_var(\"$query\",$x,$y)";

			// If there is a query then perform it if not then use cached results..
			if ( $query )
			{
				$this->query($query);
			}

			// Extract var out of cached results based x,y vals
			if ( $this->last_result[$y] )
			{
				$values = array_values(get_object_vars($this->last_result[$y]));
			}

			// If there is a value return it else return null
			return (isset($values[$x]) && $values[$x]!=='')?$values[$x]:null;
		}

		/**********************************************************************
		*  Get one row from the DB - see docs for more detail
		*/

		function get_row($query=null,$output=OBJECT,$y=0)
		{

			// Log how the function was called
			$this->func_call = "\$db->get_row(\"$query\",$output,$y)";

			// If there is a query then perform it if not then use cached results..
			if ( $query )
			{
				$this->query($query);
			}

			// If the output is an object then return object using the row offset..
			if ( $output == OBJECT )
			{
				return $this->last_result[$y]?$this->last_result[$y]:null;
			}
			// If the output is an associative array then return row as such..
			elseif ( $output == ARRAY_A )
			{
				return $this->last_result[$y]?get_object_vars($this->last_result[$y]):null;
			}
			// If the output is an numerical array then return row as such..
			elseif ( $output == ARRAY_N )
			{
				return $this->last_result[$y]?array_values(get_object_vars($this->last_result[$y])):null;
			}
			// If invalid output type was specified..
			else
			{
				$this->show_errors ? trigger_error(" \$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N",E_USER_WARNING) : null;
			}

		}

		/**********************************************************************
		*  Function to get 1 column from the cached result set based in X index
		*  see docs for usage and info
		*/

		function get_col($query=null,$x=0)
		{

			$new_array = array();

			// If there is a query then perform it if not then use cached results..
			if ( $query )
			{
				$this->query($query);
			}

			// Extract the column values
			for ( $i=0; $i < count($this->last_result); $i++ )
			{
				$new_array[$i] = $this->get_var(null,$x,$i);
			}

			return $new_array;
		}


		/**********************************************************************
		*  Return the the query as a result set - see docs for more details
		*/

		function get_results($query=null, $output = OBJECT)
		{

			// Log how the function was called
			$this->func_call = "\$db->get_results(\"$query\", $output)";

			// If there is a query then perform it if not then use cached results..
			if ( $query )
			{
				$this->query($query);
			}

			// Send back array of objects. Each row is an object
			if ( $output == OBJECT )
			{
				return $this->last_result;
			}
			elseif ( $output == ARRAY_A || $output == ARRAY_N )
			{
				if ( $this->last_result )
				{
					$i=0;
					foreach( $this->last_result as $row )
					{

						$new_array[$i] = get_object_vars($row);

						if ( $output == ARRAY_N )
						{
							$new_array[$i] = array_values($new_array[$i]);
						}

						$i++;
					}

					return $new_array;
				}
				else
				{
					return array();
				}
			}
		}


		/**********************************************************************
		*  Function to get column meta data info pertaining to the last query
		* see docs for more info and usage
		*/

		function get_col_info($info_type="name",$col_offset=-1)
		{

			if ( $this->col_info )
			{
				if ( $col_offset == -1 )
				{
					$i=0;
					foreach($this->col_info as $col )
					{
						$new_array[$i] = $col->{$info_type};
						$i++;
					}
					return $new_array;
				}
				else
				{
					return $this->col_info[$col_offset]->{$info_type};
				}

			}

		}

		/**********************************************************************
		*  store_cache
		*/

		function store_cache($query,$is_insert)
		{

			// The would be cache file for this query
			$cache_file = $this->cache_dir.'/'.md5($query);

			// disk caching of queries
			if ( $this->use_disk_cache && ( $this->cache_queries && ! $is_insert ) || ( $this->cache_inserts && $is_insert ))
			{
				if ( ! is_dir($this->cache_dir) )
				{
					$this->register_error("Could not open cache dir: $this->cache_dir");
					$this->show_errors ? trigger_error("Could not open cache dir: $this->cache_dir",E_USER_WARNING) : null;
				}
				else
				{
					// Cache all result values
					$result_cache = array
					(
						'col_info' => $this->col_info,
						'last_result' => $this->last_result,
						'num_rows' => $this->num_rows,
						'return_value' => $this->num_rows,
					);
					file_put_contents($cache_file, serialize($result_cache));
					if( file_exists($cache_file . ".updating") )
						unlink($cache_file . ".updating");
				}
			}

		}

		/**********************************************************************
		*  get_cache
		*/

		function get_cache($query)
		{

			// The would be cache file for this query
			$cache_file = $this->cache_dir.'/'.md5($query);

			// Try to get previously cached version
			if ( $this->use_disk_cache && file_exists($cache_file) )
			{
				// Only use this cache file if less than 'cache_timeout' (hours)
				if ( (time() - filemtime($cache_file)) > ($this->cache_timeout*3600) &&
					!(file_exists($cache_file . ".updating") && (time() - filemtime($cache_file . ".updating") < 60)) )
				{
					touch($cache_file . ".updating"); // Show that we in the process of updating the cache
				}
				else
				{
					$result_cache = unserialize(file_get_contents($cache_file));

					$this->col_info = $result_cache['col_info'];
					$this->last_result = $result_cache['last_result'];
					$this->num_rows = $result_cache['num_rows'];

					$this->from_disk_cache = true;

					// If debug ALL queries
					$this->trace || $this->debug_all ? $this->debug() : null ;

					return $result_cache['return_value'];
				}
			}

		}

		/**********************************************************************
		*  Dumps the contents of any input variable to screen in a nicely
		*  formatted and easy to understand way - any type: Object, Var or Array
		*/

		function vardump($mixed='')
		{

			// Start outup buffering
			ob_start();

			echo "<p><table><tr><td bgcolor=ffffff><blockquote><font color=000090>";
			echo "<pre><font face=arial>";

			if ( ! $this->vardump_called )
			{
				echo "<font color=800080><b>ezSQL</b> (v".EZSQL_VERSION.") <b>Variable Dump..</b></font>\n\n";
			}

			$var_type = gettype ($mixed);
			print_r(($mixed?$mixed:"<font color=red>No Value / False</font>"));
			echo "\n\n<b>Type:</b> " . ucfirst($var_type) . "\n";
			echo "<b>Last Query</b> [$this->num_queries]<b>:</b> ".($this->last_query?$this->last_query:"NULL")."\n";
			echo "<b>Last Function Call:</b> " . ($this->func_call?$this->func_call:"None")."\n";
			echo "<b>Last Rows Returned:</b> ".count($this->last_result)."\n";
			echo "</font></pre></font></blockquote></td></tr></table>".$this->donation();
			echo "\n<hr size=1 noshade color=dddddd>";

			// Stop output buffering and capture debug HTML
			$html = ob_get_contents();
			ob_end_clean();

			// Only echo output if it is turned on
			if ( $this->debug_echo_is_on )
			{
				echo $html;
			}

			$this->vardump_called = true;

			return $html;

		}

		/**********************************************************************
		*  Alias for the above function
		*/

		function dumpvar($mixed)
		{
			$this->vardump($mixed);
		}

		/**********************************************************************
		*  Displays the last query string that was sent to the database & a
		* table listing results (if there were any).
		* (abstracted into a seperate file to save server overhead).
		*/

		function debug($print_to_screen=true)
		{

			// Start outup buffering
			ob_start();

			echo "<blockquote>";

			// Only show ezSQL credits once..
			if ( ! $this->debug_called )
			{
				echo "<font color=800080 face=arial size=2><b>ezSQL</b> (v".EZSQL_VERSION.") <b>Debug..</b></font><p>\n";
			}

			if ( $this->last_error )
			{
				echo "<font face=arial size=2 color=000099><b>Last Error --</b> [<font color=000000><b>$this->last_error</b></font>]<p>";
			}

			if ( $this->from_disk_cache )
			{
				echo "<font face=arial size=2 color=000099><b>Results retrieved from disk cache</b></font><p>";
			}

			echo "<font face=arial size=2 color=000099><b>Query</b> [$this->num_queries] <b>--</b> ";
			echo "[<font color=000000><b>$this->last_query</b></font>]</font><p>";

				echo "<font face=arial size=2 color=000099><b>Query Result..</b></font>";
				echo "<blockquote>";

			if ( $this->col_info )
			{

				// =====================================================
				// Results top rows

				echo "<table cellpadding=5 cellspacing=1 bgcolor=555555>";
				echo "<tr bgcolor=eeeeee><td nowrap valign=bottom><font color=555599 face=arial size=2><b>(row)</b></font></td>";


				for ( $i=0; $i < count($this->col_info); $i++ )
				{
					/* when selecting count(*) the maxlengh is not set, size is set instead. */
					echo "<td nowrap align=left valign=top><font size=1 color=555599 face=arial>{$this->col_info[$i]->type}";
					if (!isset($this->col_info[$i]->max_length))
					{
						echo "{$this->col_info[$i]->size}";
					} else {
						echo "{$this->col_info[$i]->max_length}";
					}
					echo "</font><br><span style='font-family: arial; font-size: 10pt; font-weight: bold;'>{$this->col_info[$i]->name}</span></td>";
				}

				echo "</tr>";

				// ======================================================
				// print main results

			if ( $this->last_result )
			{

				$i=0;
				foreach ( $this->get_results(null,ARRAY_N) as $one_row )
				{
					$i++;
					echo "<tr bgcolor=ffffff><td bgcolor=eeeeee nowrap align=middle><font size=2 color=555599 face=arial>$i</font></td>";

					foreach ( $one_row as $item )
					{
						echo "<td nowrap><font face=arial size=2>$item</font></td>";
					}

					echo "</tr>";
				}

			} // if last result
			else
			{
				echo "<tr bgcolor=ffffff><td colspan=".(count($this->col_info)+1)."><font face=arial size=2>No Results</font></td></tr>";
			}

			echo "</table>";

			} // if col_info
			else
			{
				echo "<font face=arial size=2>No Results</font>";
			}

			echo "</blockquote></blockquote>".$this->donation()."<hr noshade color=dddddd size=1>";

			// Stop output buffering and capture debug HTML
			$html = ob_get_contents();
			ob_end_clean();

			// Only echo output if it is turned on
			if ( $this->debug_echo_is_on && $print_to_screen)
			{
				echo $html;
			}

			$this->debug_called = true;

			return $html;

		}

		/**********************************************************************
		*  Naughty little function to ask for some remuniration!
		*/

		function donation()
		{
			return "<font size=1 face=arial color=000000>If ezSQL has helped <a href=\"https://www.paypal.com/xclick/business=justin%40justinvincent.com&item_name=ezSQL&no_note=1&tax=0\" style=\"color: 0000CC;\">make a donation!?</a> &nbsp;&nbsp;<!--[ go on! you know you want to! ]--></font>";
		}

		/**********************************************************************
		*  Timer related functions
		*/

		function timer_get_cur()
		{
			list($usec, $sec) = explode(" ",microtime());
			return ((float)$usec + (float)$sec);
		}

		function timer_start($timer_name)
		{
			$this->timers[$timer_name] = $this->timer_get_cur();
		}

		function timer_elapsed($timer_name)
		{
			return round($this->timer_get_cur() - $this->timers[$timer_name],2);
		}

		function timer_update_global($timer_name)
		{
			if ( $this->do_profile )
			{
				$this->profile_times[] = array
				(
					'query' => $this->last_query,
					'time' => $this->timer_elapsed($timer_name)
				);
			}

			$this->total_query_time += $this->timer_elapsed($timer_name);
		}

		/**********************************************************************
		* Creates a SET nvp sql string from an associative array (and escapes all values)
		*
		*  Usage:
		*
		*     $db_data = array('login'=>'jv','email'=>'jv@vip.ie', 'user_id' => 1, 'created' => 'NOW()');
		*
		*     $db->query("INSERT INTO users SET ".$db->get_set($db_data));
		*
		*     ...OR...
		*
		*     $db->query("UPDATE users SET ".$db->get_set($db_data)." WHERE user_id = 1");
		*
		* Output:
		*
		*     login = 'jv', email = 'jv@vip.ie', user_id = 1, created = NOW()
		*/

		function get_set($params)
		{
			if( !is_array( $params ) )
			{
				$this->register_error( 'get_set() parameter invalid. Expected array in '.__FILE__.' on line '.__LINE__);
				return;
			}
			$sql = array();
			foreach ( $params as $field => $val )
			{
				if ( $val === 'true' || $val === true )
					$val = 1;
				if ( $val === 'false' || $val === false )
					$val = 0;

				switch( $val ){
					case 'NOW()' :
					case 'NULL' :
					  $sql[] = "$field = $val";
						break;
					default :
						$sql[] = "$field = '".$this->escape( $val )."'";
				}
			}

			return implode( ', ' , $sql );
		}

	}
