<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    问答验证器
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/3/30
 */

namespace app\common\validate;

class Ask extends CareyShop
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'answer'      => 'max:200',
        'ask_id'      => 'integer|gt:0',
        'ask_type'    => 'require|between:0,3',
        'title'       => 'require|max:120',
        'ask'         => 'require|max:200',
        'account'     => 'max:80',
        'status'      => 'in:0,1',
        'page_no'     => 'integer|gt:0',
        'page_size'   => 'integer|between:1,40',
        'order_type'  => 'in:asc,desc',
        'order_field' => 'in:ask_id,ask_type,title,status,ask_time,answer_time,username,nickname',
    ];

    /**
     * 字段描述
     * @var array
     */
    protected $field = [
        'answer'      => '回复内容',
        'ask_id'      => '咨询编号',
        'ask_type'    => '咨询类型',
        'status'      => '是否回复',
        'title'       => '咨询标题',
        'ask'         => '咨询内容',
        'account'     => '账号或昵称',
        'page_no'     => '页码',
        'page_size'   => '每页数量',
        'order_type'  => '排序方式',
        'order_field' => '排序字段',
    ];

    /**
     * 场景规则
     * @var array
     */
    protected $scene = [
        'del'      => [
            'ask_id' => 'require|integer|gt:0',
        ],
        'reply'    => [
            'ask_id' => 'require|integer|gt:0',
            'answer' => 'require|max:200',
        ],
        'continue' => [
            'ask_id' => 'require|integer|gt:0',
            'ask',
        ],
        'item'     => [
            'ask_id' => 'require|integer|gt:0',
        ],
        'list'     => [
            'ask_type' => 'between:0,3',
            'status',
            'account',
            'page_no',
            'page_size',
            'order_type',
            'order_field',
        ],
    ];
}