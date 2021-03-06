<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品规格图片验证器
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/4/21
 */

namespace app\common\validate;

class SpecImage extends CareyShop
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'goods_id'     => 'require|integer|gt:0',
        'spec_item_id' => 'require|integer|gt:0',
        'image'        => 'require|max:255',
    ];

    /**
     * 字段描述
     * @var array
     */
    protected $field = [
        'goods_id'     => '商品规格图片中的商品编号',
        'spec_item_id' => '商品规格图片中的商品规格项编号',
        'image'        => '商品规格图片中的商品规格图片',
    ];
}