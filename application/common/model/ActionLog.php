<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    操作日志模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/6/24
 */

namespace app\common\model;

class ActionLog extends CareyShop
{
    /**
     * 是否需要自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 更新时间字段
     * @var bool/string
     */
    protected $updateTime = false;

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'action_log_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'action_log_id' => 'integer',
        'client_type'   => 'integer',
        'user_id'       => 'integer',
        'params'        => 'json',
        'result'        => 'json',
        'status'        => 'integer',
    ];
}