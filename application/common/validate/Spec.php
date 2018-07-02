<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品规格验证器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/10
 */

namespace app\common\validate;

class Spec extends CareyShop
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'spec_id'       => 'integer|gt:0',
        'goods_type_id' => 'require|integer|gt:0',
        'name'          => 'require|max:60',
        'spec_item'     => 'require|arrayHasOnlyStrings',
        'spec_index'    => 'in:0,1',
        'sort'          => 'integer|between:0,255',
    ];

    /**
     * 字段描述
     * @var array
     */
    protected $field = [
        'spec_id'       => '商品规格编号',
        'goods_type_id' => '所属商品模型编号',
        'name'          => '商品规格名称',
        'spec_item'     => '商品规格项',
        'spec_index'    => '商品规格检索',
        'sort'          => '商品规格排序值',
    ];

    /**
     * 场景规则
     * @var array
     */
    protected $scene = [
        'set'   => [
            'spec_id' => 'require|integer|gt:0',
            'goods_type_id',
            'name',
            'spec_item',
            'spec_index',
            'sort',
        ],
        'del'   => [
            'spec_id' => 'require|arrayHasOnlyInts',
        ],
        'item'  => [
            'spec_id' => 'require|integer|gt:0',
        ],
        'index' => [
            'spec_id'    => 'require|arrayHasOnlyInts',
            'spec_index' => 'require|in:0,1',
        ],
        'list'  => [
            'goods_type_id',
        ],
        'sort'  => [
            'spec_id' => 'require|integer|gt:0',
            'sort'    => 'require|integer|between:0,255',
        ],
    ];
}