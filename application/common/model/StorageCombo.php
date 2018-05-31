<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    资源样式组合模型
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2018/5/31
 */

namespace app\common\model;

class StorageCombo extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'storage_combo_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'storage_combo_id' => 'integer',
        'platform'         => 'integer',
        'size'             => 'array',
        'crop'             => 'array',
        'quality'          => 'integer',
        'status'           => 'integer',
    ];
}