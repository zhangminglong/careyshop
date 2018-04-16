<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    二维码生成验证器
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/7/27
 */

namespace app\common\validate;

class Qrcode extends CareyShop
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'text' => 'max:255',
        'size' => 'integer|between:1,10',
        'logo' => 'max:255',
    ];

    /**
     * 字段描述
     * @var array
     */
    protected $field = [
        'text' => '二维码内容',
        'size' => '二维码图片大小',
        'logo' => '二维码LOGO',
    ];
}