<?php

namespace Org\Snje\Minifw;

class Image {

    const FORMAT_GIF = 1;
    const FORMAT_JPG = 2;
    const FORMAT_PNG = 3;
    const CUT_TOP_LEFT = 1;
    const CUT_TOP_RIGHT = 2;
    const CUT_BTM_LEFT = 3;
    const CUT_BTM_RIGHT = 4;
    const CUT_CENTER = 5;

    public static $default_quality = 90;
    public static $default_level = 2;
    protected $image_obj = null;
    protected $width = 0;
    protected $height = 0;
    protected $format = 0;

    public function __construct() {

    }

    public function load_image($full) {
        $this->destroy();

        $info = self::get_image_info($full);
        $this->format = $info['format'];
        $this->width = $info['width'];
        $this->height = $info['height'];

        $this->image_obj = self::load_image_obj($full, $this->format);
        return $this;
    }

    public function init_image($format, $width, $height, $bgcolor = null) {
        $this->destroy();
        $this->image_obj = self::get_new_image($format, $width, $height, $bgcolor);
        $this->format = $format;
        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    public function rotate($degree) {
        if ($degree % 90 !== 0) {
            throw new Exception('不支持该操作');
        }
        $degree = $degree % 360;
        if ($degree == 0) {
            return $this;
        }
        $new_obj = imagerotate($this->image_obj, $degree, 0);
        imagedestroy($this->image_obj);
        $this->image_obj = $new_obj;

        if ($degree % 180 !== 0) {
            $tmp = $this->height;
            $this->height = $this->width;
            $this->width = $tmp;
        }

        return $this;
    }

    public function merge($full, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
        $info = self::get_image_info($full);
        $src_obj = self::load_image_obj($full, $info['format']);
        imagecopyresampled($this->image_obj, $src_obj, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
        imagedestroy($src_obj);
        return $this;
    }

    public function transform($new_w, $new_h, $bgcolor, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
        $src_obj = $this->image_obj;
        $this->image_obj = self::get_new_image($this->format, $new_w, $new_h, $bgcolor);
        $this->width = $new_w;
        $this->height = $new_h;

        imagecopyresampled($this->image_obj, $src_obj, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
        imagedestroy($src_obj);
        return $this;
    }

    public function round_corner($r, $level = -1, $bgcolor = null) {
        if ($level < 0) {
            $level = self::$default_level;
        }
        if ($r <= 0) {
            return $this;
        }
        $w = ceil($this->width / 2);
        $h = ceil($this->height / 2);

        if ($w > $r) {
            $w = $r;
        }
        if ($h > $r) {
            $h = $r;
        }

        if ($this->format == self::FORMAT_GIF) {
            $level = 0; //gif图片不支持抗锯齿
        }

        if ($bgcolor == null) {
            switch ($this->format) {
                case self::FORMAT_GIF:
                    $bgcolor = imagecolortransparent($this->image_obj);
                    break;
                case self::FORMAT_JPG:
                    $bgcolor = imagecolorallocate($this->image_obj, 255, 255, 255);
                    break;
                case self::FORMAT_PNG:
                    $bgcolor = imagecolorallocatealpha($this->image_obj, 0, 0, 0, 127);
                    break;
            }
        }

        $this->round_one_corner($r, $r, $r, 0, 0, $w, $h, $level, $bgcolor);
        $this->round_one_corner($r, $this->width - $r, $r, $this->width - $w, 0, $w, $h, $level, $bgcolor);
        $this->round_one_corner($r, $r, $this->height - $r, 0, $this->height - $h, $w, $h, $level, $bgcolor);
        $this->round_one_corner($r, $this->width - $r, $this->height - $r, $this->width - $w, $this->height - $h, $w, $h, $level, $bgcolor);

        return $this;
    }

    public function round_one_corner($r, $cx, $cy, $x, $y, $w, $h, $level, $bgcolor) {
        $br = (($bgcolor >> 16) & 0xFF);
        $bg = (($bgcolor >> 8) & 0xFF);
        $bb = ($bgcolor & 0xFF);
        $ba = (($bgcolor >> 24) & 0xFF);

        for ($i = 0; $i < $w; $i++) {
            for ($j = 0; $j < $h; $j++) {
                $px = $x + $i;
                $py = $y + $j;

                $alpha = self::calc_alpha($r, $px - $cx, $py - $cy, $level);
                if ($alpha <= 0) {
                    continue;
                }
                elseif ($alpha >= 127) {
                    imagesetpixel($this->image_obj, $px, $py, $bgcolor);
                }
                else {
                    $color = imagecolorat($this->image_obj, $px, $py);
                    $alpah_c = 127 - $alpha;

                    $cr = (($color >> 16) & 0xFF) * $alpah_c + $br * $alpha;
                    $cg = (($color >> 8) & 0xFF) * $alpah_c + $bg * $alpha;
                    $cb = ($color & 0xFF) * $alpah_c + $bg + $bb * $alpha;
                    $ca = (($color >> 24) & 0xFF) * $alpah_c + $ba * $alpha;

                    $color = ($ca / 127) << 24 | ($cr / 127) << 16 | ($cg / 127) << 8 | ($cb / 127);
                    imagesetpixel($this->image_obj, $px, $py, $color);
                }
            }
        }
        return $this;
    }

    public function save($dest, $quality = -1) {
        if ($quality < 0) {
            $quality = self::$default_quality;
        }
        switch ($this->format) {
            case self::FORMAT_GIF:
                imagegif($this->image_obj, $dest);
                break;
            case self::FORMAT_JPG:
                imagejpeg($this->image_obj, $dest, $quality);
                break;
            case self::FORMAT_PNG:
                //imagealphablending($image_obj, true);
                //imagesavealpha($image_obj, true);
                imagepng($this->image_obj, $dest);
                break;
        }
        return $this;
    }

    public function destroy() {
        if ($this->image_obj === null) {
            return;
        }
        imagedestroy($this->image_obj);
        $this->image_obj = null;
        $this->width = 0;
        $this->height = 0;
        $this->format = 0;
    }

    public function get_format() {
        return $this->format;
    }

    public function get_size() {
        return [$this->width, $this->height];
    }

    public function get_obj() {
        return $this->image_obj;
    }

    public static function calc_alpha($r, $x, $y, $level) {
        $r2 = $r * $r;
        $offset = $x * $x + $y * $y - $r2;
        if ($level <= 0) {
            if ($offset > 0) {
                return 127;
            }
            else {
                return 0;
            }
        }

        $offset -= 0.5;
        $limit = 1.42 * $r;
        if ($offset >= $limit) {
            return 127;
        }
        elseif ($offset < -1 * $limit) {
            return 0;
        }

        if ($level > 4) {
            $level = 4;
        }

        $len = 1 << $level;
        $step = 1 / $len;
        $bx = $x - 0.5 + $step / 2;
        $by = $y - 0.5 + $step / 2;
        $count = 0;

        for ($i = 0; $i < $len; $i++) {
            for ($j = 0; $j < $len; $j++) {
                $sx = $bx + $step * $i;
                $sy = $by + $step * $j;
                if ($sx * $sx + $sy * $sy - $r2 > 0) {
                    $count ++;
                }
            }
        }

        return intval(($count / $len / $len) * 127);
    }

    public static function load_image_obj($full, $format) {
        switch ($format) {
            case self::FORMAT_GIF:
                $image_obj = imagecreatefromgif($full);
                break;
            case self::FORMAT_JPG:
                $image_obj = imagecreatefromjpeg($full);
                break;
            case self::FORMAT_PNG:
                $image_obj = imagecreatefrompng($full);
                imagealphablending($image_obj, false);
                imagesavealpha($image_obj, true);
                break;
        }
        return $image_obj;
    }

    public static function get_image_info($full) {
        if (!file_exists($full)) {
            throw new Exception('文件不存在');
        }
        $info = getimagesize($full);

        $hash = [
            1 => self::FORMAT_GIF,
            2 => self::FORMAT_JPG,
            3 => self::FORMAT_PNG,
        ];

        if (!isset($hash[$info[2]])) {
            throw new Exception('不支持的文件格式');
        }

        $ret = [
            'width' => $info[0],
            'height' => $info[1],
            'format' => $hash[$info[2]],
        ];
        return $ret;
    }

    public static function get_new_image($format, $width, $height, $bgcolor = null) {
        switch ($format) {
            case self::FORMAT_GIF:
                $image_obj = imagecreate($width, $height);
                if ($bgcolor == null) {
                    $bgcolor = imagecolorallocate($image_obj, 0, 0, 0);
                }
                imagecolortransparent($image_obj, $bgcolor);
                break;
            case self::FORMAT_JPG:
                $image_obj = imagecreatetruecolor($width, $height);
                if ($bgcolor == null) {
                    $bgcolor = imagecolorallocate($image_obj, 255, 255, 255);
                }
                imagefill($image_obj, 0, 0, $bgcolor);
                break;
            case self::FORMAT_PNG:
                $image_obj = imagecreatetruecolor($width, $height);
                imagealphablending($image_obj, false);
                imagesavealpha($image_obj, true);
                if ($bgcolor == null) {
                    $bgcolor = imagecolorallocatealpha($image_obj, 0, 0, 0, 127);
                }
                imagefill($image_obj, 0, 0, $bgcolor);
                break;
            default :
                throw new Exception('不支持的图片格式');
        }
        return $image_obj;
    }

    public static function calc_dst_size($width, $height, $max_width, $max_height) {
        if ($max_width <= 0 && $max_height <= 0) {
            return [$width, $height];
        }
        elseif ($max_width <= 0) {
            $width = intval($max_height * $width / $height);
            $height = $max_height;
        }
        elseif ($max_height <= 0) {
            $height = intval($max_width * $height / $width);
            $width = $max_width;
        }
        else {
            if ($width * $max_height >= $height * $max_width) {
                $height = intval($max_width * $height / $width);
                $width = $max_width;
            }
            else {
                $width = intval($max_height * $width / $height);
                $height = $max_height;
            }
        }

        return [$width, $height];
    }

    public static function calc_src_size($width, $height, $max_width, $max_height) {
        if ($max_width <= 0 || $max_height <= 0) {
            return [$width, $height];
        }
        if ($width * $max_height <= $height * $max_width) {
            $height = intval($max_height * $width / $max_width);
        }
        else {
            $width = intval($max_width * $height / $max_height);
        }
        return [$width, $height];
    }

    public static function image_scale($src, $tail = '', $max_width = 0, $max_height = 0) {
        $dest = File::appent_tail($src, $tail);

        if (!file_exists(WEB_ROOT . $dest)) {
            try {
                $info = self::get_image_info(WEB_ROOT . $src);

                list($dst_w, $dst_h) = self::calc_dst_size($info['width'], $info['height'], $max_width, $max_height);

                $obj = new static();
                $obj->load_image(WEB_ROOT . $src)
                        ->transform($dst_w, $dst_h, NULL, 0, 0, 0, 0, $dst_w, $dst_h, $info['width'], $info['height'])
                        ->save(WEB_ROOT . $dest)
                        ->destroy();
            }
            catch (\Exception $ex) {
                copy(WEB_ROOT . $src, WEB_ROOT . $dest);
            }
        }

        return $dest;
    }

    public static function image_scale_padding($src, $tail, $max_width, $max_height, $bgcolor = null) {
        $dest = File::appent_tail($src, $tail);

        if (!file_exists(WEB_ROOT . $dest)) {
            try {
                $info = self::get_image_info(WEB_ROOT . $src);

                list($dst_w, $dst_h) = self::calc_dst_size($info['width'], $info['height'], $max_width, $max_height);

                if ($dst_w < $max_width) {
                    $dst_x = intval(($max_width - $dst_w) / 2);
                }
                else {
                    $dst_x = 0;
                }

                if ($dst_h < $max_height) {
                    $dst_y = intval(($max_height - $dst_h) / 2);
                }
                else {
                    $dst_y = 0;
                }

                $obj = new static();
                $obj->load_image(WEB_ROOT . $src)
                        ->transform($max_width, $max_height, $bgcolor, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $info['width'], $info['height'])
                        ->save(WEB_ROOT . $dest)
                        ->destroy();
            }
            catch (\Exception $ex) {
                copy(WEB_ROOT . $src, WEB_ROOT . $dest);
            }
        }

        return $dest;
    }

    public static function image_scale_cut($src, $tail, $max_width, $max_height, $cut = self::CUT_CENTER) {
        $dest = File::appent_tail($src, $tail);

        if (!file_exists(WEB_ROOT . $dest)) {
            try {
                $info = self::get_image_info(WEB_ROOT . $src);

                list($src_w, $src_h) = self::calc_src_size($info['width'], $info['height'], $max_width, $max_height, false);

                switch ($cut) {
                    case self::CUT_TOP_LEFT:
                        $src_x = 0;
                        $src_y = 0;
                        break;
                    case self::CUT_TOP_RIGHT:
                        $src_x = $info['width'] - $src_w;
                        $src_y = 0;
                        break;
                    case self::CUT_BTM_LEFT:
                        $src_x = 0;
                        $src_y = $info['height'] - $src_h;
                        break;
                    case self::CUT_BTM_RIGHT:
                        $src_x = $info['width'] - $src_w;
                        $src_y = $info['height'] - $src_h;
                        break;
                    case self::CUT_CENTER:
                        $src_x = intval(($info['width'] - $src_w) / 2);
                        $src_y = intval(($info['height'] - $src_h) / 2);
                        break;
                }

                $obj = new static();
                $obj->load_image(WEB_ROOT . $src)
                        ->transform($max_width, $max_height, NULL, 0, 0, $src_x, $src_y, $max_width, $max_height, $src_w, $src_h)
                        ->save(WEB_ROOT . $dest)
                        ->destroy();
            }
            catch (\Exception $ex) {
                copy(WEB_ROOT . $src, WEB_ROOT . $dest);
            }
        }

        return $dest;
    }

    public static function image_round_corner($src, $tail, $r, $level = 2, $bgcolor = null) {
        $dest = File::appent_tail($src, $tail);

        if (!file_exists(WEB_ROOT . $dest)) {
            try {
                $info = self::get_image_info(WEB_ROOT . $src);

                $obj = new static();
                $obj->load_image(WEB_ROOT . $src)
                        ->round_corner($r, $level, $bgcolor)
                        ->save(WEB_ROOT . $dest)
                        ->destroy();
            }
            catch (\Exception $ex) {
                copy(WEB_ROOT . $src, WEB_ROOT . $dest);
            }
        }

        return $dest;
    }

}
