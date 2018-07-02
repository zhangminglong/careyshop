<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    订单日志模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/8/12
 */

namespace app\common\model;

class OrderLog extends CareyShop
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
        'order_log_id',
        'order_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'order_log_id'    => 'integer',
        'order_id'        => 'integer',
        'trade_status'    => 'integer',
        'delivery_status' => 'integer',
        'payment_status'  => 'integer',
        'client_type'     => 'integer',
    ];

    /**
     * 添加订单操作日志
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     * @throws
     */
    public function addOrderItem($data)
    {
        if (!$this->validateData($data, 'OrderLog')) {
            return false;
        }

        // 避免无关字段
        unset($data['order_log_id']);
        $data['action'] = get_client_name();
        $data['client_type'] = get_client_type();

        if (false !== $this->isUpdate(false)->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }
}