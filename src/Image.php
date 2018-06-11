<?php

namespace Org\Snje\Minifw;

class Image {

    /**
     * 将图片缩放到指定大小，并保存到指定路径，如果图片类型不支持则直接复制文件
     *
     * @param string $src 源文件的绝对路径
     * @param string $dest 目标文件的绝对路径
     * @param int $w 目标宽度
     * @param int $h 目标高度
     */
    public static function image_resize($src, $dest, $w, $h) {
        $img = getimagesize($src);
        switch ($img[2]) {
            case 1:
                $im_in = imagecreatefromgif($src);
                $im_out = imagecreate($w, $h);
                $bgcolor = imagecolorallocate($im_out, 0, 0, 0);
                $bgcolortrans = ImageColorTransparent($im_out, $bgcolor);
                break;
            case 2:
                $im_in = imagecreatefromjpeg($src);
                $im_out = imagecreatetruecolor($w, $h);
                break;
            case 3:
                $im_in = imagecreatefrompng($src);
                $im_out = imagecreatetruecolor($w, $h);
                imagealphablending($im_out, true);
                imagesavealpha($im_out, true);
                $trans_colour = imagecolorallocatealpha($im_out, 0, 0, 0, 127);
                imagefill($im_out, 0, 0, $trans_colour);
                break;
            default:
                copy($src, $dest);
        }
        if (!$im_in || !$im_out) {
            return false;
        }
        imagecopyresampled($im_out, $im_in, 0, 0, 0, 0, $w, $h, $img[0], $img[1]);
        switch ($img[2]) {
            case 1:
                imagegif($im_out, $dest);
                break;
            case 2:
                imagejpeg($im_out, $dest);
                break;
            case 3:
                imagepng($im_out, $dest);
                break;
            default:
                return -5;  //保存失败
        }
        imagedestroy($im_out);
        imagedestroy($im_in);
    }

    /**
     * 将图片缩放到指定大小之内，并保持长宽比，如果已经满足则直接复制
     *
     * @param string $src 源文件的相对路径
     * @param string $dest 目标文件的相对路径
     * @param int $w 目标宽度，0为不限制
     * @param int $h 目标高度，0为不限制
     * @return string 缩放结果的文件的相对路径
     */
    public static function image_widthin($src, $dest, $w, $h) {
        $img = getimagesize(WEB_ROOT . $src);
        if (!$img) {
            return false;
        }
        $nw = $img[0];
        $nh = $img[1];
        if ($nw > $w && $w > 0) {
            $nh = (($w / $nw) * $nh);
            $nw = $w;
        }
        if ($nh > $h && $h > 0) {
            $nw = (($h / $nh) * $nw);
            $nh = $h;
        }
        if ($nw != $img[0] || $nh != $img[1]) {
            self::image_resize(WEB_ROOT . $src, WEB_ROOT . $dest, $nw, $nh);
        } else {
            copy(WEB_ROOT . $src, WEB_ROOT . $dest);
        }
        return $dest;
    }

    /**
     * 生成一个缩放到指定大小之内，并且具有指定后缀的文件
     *
     * @param string $src 源文件的相对路径
     * @param string $tail 目标文件的后缀
     * @param int $w 目标宽度，0为不限制
     * @param int $h 目标高度，0为不限制
     * @return string 缩放结果的文件的相对路径
     */
    public static function path($src, $tail = '', $w = 0, $h = 0) {
        if ($src == '') {
            return '';
        }
        if ($tail == '') {
            return $src;
        }
        $temp = pathinfo($src);
        $name = $temp['filename'];
        $path = $temp['dirname'];
        $exte = $temp['extension'];
        $dest = $path . '/' . $name . '_' . $tail . '.' . $exte;
        if (!file_exists(WEB_ROOT . $dest)) {
            $dest = self::image_widthin($src, $dest, $w, $h);
        }
        return $dest;
    }

}
