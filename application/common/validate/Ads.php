<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    广告验证器
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/3/29
 */

namespace app\common\validate;

class Ads extends CareyShop
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'ads_id'          => 'integer|gt:0',
        'ads_position_id' => 'require|integer|gt:0',
        'type'            => 'integer|gt:0',
        'name'            => 'require|max:100',
        'url'             => 'require|max:255',
        'target'          => 'in:_self,_blank',
        'begin_time'      => 'require|date|betweenTime|beforeTime:end_time',
        'end_time'        => 'require|date|betweenTime|afterTime:begin_time',
        'sort'            => 'integer|between:0,255',
        'status'          => 'in:0,1',
        'page_no'         => 'integer|gt:0',
        'page_size'       => 'integer|between:1,40',
        'order_type'      => 'in:asc,desc',
        'order_field'     => 'in:ads_id,ads_position_id,type,name,url,begin_time,end_time,sort,status',
    ];

    /**
     * 字段描述
     * @var array
     */
    protected $field = [
        'ads_id'          => '广告编号',
        'ads_position_id' => '广告位置编号',
        'type'            => '广告类型',
        'name'            => '广告名称',
        'url'             => '链接地址',
        'target'          => '打开方式',
        'begin_time'      => '开始投放时间',
        'end_time'        => '投放结束时间',
        'sort'            => '广告排序值',
        'status'          => '广告是否可见',
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
            'ads_id' => 'require|integer|gt:0',
            'ads_position_id',
            'type',
            'name',
            'url',
            'target',
            'begin_time',
            'end_time',
            'sort',
            'status',
        ],
        'del'    => [
            'ads_id' => 'require|arrayHasOnlyInts',
        ],
        'status' => [
            'ads_id' => 'require|arrayHasOnlyInts',
            'status' => 'require|in:0,1',
        ],
        'sort'   => [
            'ads_id' => 'require|integer|gt:0',
            'sort'   => 'require|integer|between:0,255',
        ],
        'item'   => [
            'ads_id' => 'require|integer|gt:0',
        ],
        'list'   => [
            'ads_position_id' => 'integer|gt:0',
            'type',
            'name'            => 'max:100',
            'status',
            'begin_time'      => 'date|betweenTime|beforeTime:end_time',
            'end_time'        => 'date|betweenTime|afterTime:begin_time',
            'page_no',
            'page_size',
            'order_type',
            'order_field',
        ],
    ];
}