<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    广告位置验证器
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/3/29
 */

namespace app\common\validate;

class AdsPosition extends CareyShop
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'ads_position_id' => 'integer|gt:0',
        'position_name'   => 'require|max:100|unique:ads_position,position_name,0,ads_position_id',
        'description'     => 'max:255',
        'width'           => 'integer|egt:0',
        'height'          => 'integer|egt:0',
        'status'          => 'in:0,1',
        'not_empty'       => 'in:0,1',
        'exclude_id'      => 'integer|gt:0',
        'page_no'         => 'integer|gt:0',
        'page_size'       => 'integer|between:1,40',
        'order_type'      => 'in:asc,desc',
        'order_field'     => 'in:ads_position_id,position_name,description,width,height,status',
    ];

    /**
     * 字段描述
     * @var array
     */
    protected $field = [
        'ads_position_id' => '广告位置编号',
        'position_name'   => '广告位置名称',
        'description'     => '广告位置描述',
        'width'           => '广告位置宽度',
        'height'          => '广告位置高度',
        'status'          => '广告位置状态',
        'not_empty'       => '是否存在关联广告',
        'exclude_id'      => '广告位置排除Id',
        'page_no'         => '页码',
        'page_size'       => '每页数量',
        'order_type'      => '排序方式',
        'order_field'     => '排序字段',
    ];

    /**
     * 场景规则
     * @var array
     */
    protected $scene = [
        'set'    => [
            'ads_position_id' => 'require|integer|gt:0',
            'position_name'   => 'require|max:100',
            'description',
            'width',
            'height',
            'status',
        ],
        'del'    => [
            'ads_position_id' => 'require|arrayHasOnlyInts',
            'not_empty',
        ],
        'unique' => [
            'position_name' => 'require|max:100',
            'exclude_id',
        ],
        'item'   => [
            'ads_position_id' => 'require|integer|gt:0',
        ],
        'list'   => [
            'position_name' => 'max:100',
            'status',
            'page_no',
            'page_size',
            'order_type',
            'order_field',
        ],
        'status' => [
            'ads_position_id' => 'require|arrayHasOnlyInts',
            'status'          => 'require|in:0,1',
        ],
    ];
}