<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    Api基类验证器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/23
 */

namespace app\api\validate;

use think\Validate;

class CareyShop extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'appkey'     => 'integer|length:8',
        'token'      => 'length:32',
        'sign'       => 'length:32',
        'timestamp'  => 'integer|checkTimestamp',
        'format'     => 'in:json,jsonp,xml',
        'version'    => 'max:10',
        'controller' => 'max:20',
        'method'     => 'max:100',
        'callback'   => 'max:100', // jsonp的返回方法
    ];

    /**
     * 场景规则
     * @var array
     */
    protected $scene = [
        'batch' => [
            'version'    => 'require',
            'controller' => 'require',
            'method'     => 'require',
        ],
    ];

    /**
     * 验证时间戳是否在允许范围内
     * @access protected
     * @param  int $value 验证数据
     * @return string|true
     */
    protected function checkTimestamp($value)
    {
        if ($value > strtotime("+10 minute") || $value < strtotime("-10 minute")) {
            return 'timestamp已过期';
        }

        return true;
    }
}