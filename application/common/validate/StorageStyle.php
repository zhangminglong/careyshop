<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    资源样式验证器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/5/31
 */

namespace app\common\validate;

class StorageStyle extends CareyShop
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'storage_style_id' => 'integer|gt:0',
        'name'             => 'require|max:64',
        'code'             => 'require|max:32|alphaDash|unique:storage_style,code,0,storage_style_id',
        'platform'         => 'require|integer|between:-128,127',
        'size'             => 'max:2|arrayHasOnlyInts:zero',
        'crop'             => 'max:2|arrayHasOnlyInts:zero',
        'quality'          => 'integer|between:0,100',
        'type'             => 'in:jpg,png,svg,gif,bmp,tiff,webp',
        'style'            => 'max:64|alphaDash',
        'status'           => 'in:0,1',
        'exclude_id'       => 'integer|gt:0',
        'page_no'          => 'integer|gt:0',
        'page_size'        => 'integer|between:1,40',
        'order_type'       => 'in:asc,desc',
        'order_field'      => 'in:storage_style_id,name,code,platform,status',
    ];

    /**
     * 字段描述
     * @var array
     */
    protected $field = [
        'storage_style_id' => '资源样式编号',
        'name'             => '资源样式名称',
        'code'             => '资源样式编码',
        'platform'         => '资源样式平台',
        'size'             => '资源缩略尺寸',
        'crop'             => '资源裁剪尺寸',
        'quality'          => '资源图片质量',
        'type'             => '资源输出格式',
        'style'            => '第三方OSS样式',
        'status'           => '资源样式状态',
        'exclude_id'       => '资源样式排除Id',
        'page_no'          => '页码',
        'page_size'        => '每页数量',
        'order_type'       => '排序方式',
        'order_field'      => '排序字段',
    ];

    /**
     * 场景规则
     * @var array
     */
    protected $scene = [
        'unique' => [
            'code' => 'require|max:32|alphaDash',
            'exclude_id',
        ],
        'set'    => [
            'storage_style_id' => 'require|integer|gt:0',
            'name',
            'code'             => 'max:32|alphaDash',
            'platform',
            'size',
            'crop',
            'quality',
            'type',
            'style',
            'status',
        ],
        'item'   => [
            'storage_style_id' => 'require|integer|gt:0',
        ],
        'code'   => [
            'code'     => 'require|max:32|alphaDash',
            'platform' => 'integer|between:-128,127',
        ],
        'list'   => [
            'name'     => 'max:64',
            'code'     => 'max:32',
            'platform' => 'integer|between:-128,127',
            'status',
            'page_no',
            'page_size',
            'order_type',
            'order_field',
        ],
        'del'    => [
            'storage_style_id' => 'require|arrayHasOnlyInts',
        ],
        'status' => [
            'storage_style_id' => 'require|arrayHasOnlyInts',
            'status'           => 'require|in:0,1',
        ],
    ];
}