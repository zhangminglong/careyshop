<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    二维码服务层
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2018/1/26
 */

namespace app\common\service;

use think\Loader;
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
     * 生成一个二维码
     * @access public
     * @param  array $data 外部数据
     * @return mixed
     */
    public function getQrcodeItem($data)
    {
        $validate = Loader::validate('Qrcode');
        if (!$validate->check($data)) {
            return $this->setError($validate->getError());
        }

        // LOGO内部地址
        $logoPath = ROOT_PATH . 'public' . DS . 'static' . DS . 'api' . DS . 'images' . DS . 'qrcode_logo.png';

        $data['text'] = isset($data['text']) ? urldecode($data['text']) : base64_decode('Q2FyZXlTaG9w54mI5p2D5omA5pyJ');
        $size = isset($data['size']) ? $data['size'] : 4;
        $logo = isset($data['logo']) ? urldecode($data['logo']) : $logoPath;

        ob_start();
        \PHPQRCode\QRcode::png($data['text'], false, 'M', $size, 1);
        $imageData = ob_get_contents();
        ob_end_clean();

        $qr = imagecreatefromstring($imageData);
        $logo = imagecreatefromstring(file_get_contents($logo));

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