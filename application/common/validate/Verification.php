<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    验证码验证器
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/7/20
 */

namespace app\common\validate;

class Verification extends CareyShop
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'mobile' => 'number|length:7,15',
        'email'  => 'email|max:60',
        'type'   => 'in:1,7,8',
        'code'   => 'integer|max:6',
        'number' => 'max:60',
    ];

    /**
     * 字段描述
     * @var array
     */
    protected $field = [
        'mobile' => '手机号',
        'email'  => '邮箱地址',
        'type'   => '通知类型',
        'code'   => '验证码',
        'number' => '验证号',
    ];

    /**
     * 场景规则
     * @var array
     */
    protected $scene = [
        'sms'       => [
            'mobile' => 'require|number|length:7,15',
            'type'   => 'require|in:1,7,8',
        ],
        'email'     => [
            'email' => 'require|email|max:60',
            'type'  => 'require|in:1,7,8',
        ],
        'ver_sms'   => [
            'mobile' => 'require|number|length:7,15',
            'code'   => 'require|integer|max:6',
        ],
        'ver_email' => [
            'email' => 'require|email|max:60',
            'code'  => 'require|integer|max:6',
        ],
        'use'       => [
            'number' => 'require|max:60',
        ],
    ];
}