<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    二维码控制器
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/7/27
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Qrcode extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 生成一个二维码
            'get.qrcode.item'    => ['getQrcodeItem', 'app\common\service\Qrcode'],
            // 获取二维码调用地址
            'get.qrcode.callurl' => ['getQrcodeCallurl', 'app\common\service\Qrcode'],
        ];
    }
}