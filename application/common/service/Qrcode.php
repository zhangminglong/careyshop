<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    二维码服务层
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/1/26
 */

namespace app\common\service;

use think\Url;

class Qrcode extends CareyShop
{
    /**
     * 获取二维码调用地址
     * @access public
     * @return array
     */
    public function getQrcodeCallurl()
    {
        $vars = ['method' => 'get.qrcode.item'];
        $data['call_url'] = Url::bUild('/api/v1/qrcode', $vars, true, true);

        return $data;
    }

    /**
     * 判断本地资源或网络资源,最终将返回实际需要的路径
     * @access public
     * @param  string $path 路径
     * @return string
     */
    private static function getQrcodeLogoPath($path)
    {
        // 如果是网络文件直接返回
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return urldecode($path);
        }

        $path = ROOT_PATH . 'public' . DS . $path;
        $path = str_replace(IS_WIN ? '/' : '\\', DS, $path);

        if (is_file($path)) {
            return $path;
        }

        $path = ROOT_PATH . 'public' . DS . 'static' . DS . 'api' . DS . 'images' . DS . 'qrcode_logo.png';
        return $path;
    }

    /**
     * 动态生成一个二维码
     * @access public
     * @param  array $data 外部数据
     * @return mixed
     */
    public static function getQrcodeItem($data)
    {
        // 参数值处理
        isset($data['text']) ?: $data['text'] = base64_decode('5Z+65LqOQ2FyZXlTaG9w5ZWG5Z+O5qGG5p6257O757uf');
        $size = !empty($data['size']) ? (int)$data['size'] : 3;
        $logo = isset($data['logo']) ? $data['logo'] : config('qrcode_logo.value', null, 'system_info');
        $logo = self::getQrcodeLogoPath($logo);

        ob_start();
        \PHPQRCode\QRcode::png(urldecode($data['text']), false, 'M', $size, 1);
        $imageData = ob_get_contents();
        ob_end_clean();

        $qr = imagecreatefromstring($imageData);
        $logo = imagecreatefromstring(file_get_contents(urldecode($logo)));

        $qrWidth = imagesx($qr);
        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);
        $logoQrWidth = $qrWidth / 5;
        $scale = $logoWidth / $logoQrWidth;
        $logoQrHeight = $logoHeight / $scale;
        $fromWidth = ($qrWidth - $logoQrWidth) / 2;
        imagecopyresampled($qr, $logo, $fromWidth, $fromWidth, 0, 0, $logoQrWidth, $logoQrHeight, $logoWidth, $logoHeight);

        header('Content-type: image/png');
        imagepng($qr);
        imagedestroy($qr);
        exit;
    }
}