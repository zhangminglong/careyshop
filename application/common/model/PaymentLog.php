<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    交易日志模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/6/28
 */

namespace app\common\model;

class PaymentLog extends CareyShop
{
    /**
     * 是否需要自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'payment_log_id',
    ];

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'payment_log_id',
        'payment_no',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'payment_log_id' => 'integer',
        'user_id'        => 'integer',
        'amount'         => 'float',
        'to_payment'     => 'integer',
        'type'           => 'integer',
        'status'         => 'integer',
    ];

    /**
     * hasOne cs_user
     * @access public
     * @return mixed
     */
    public function getUser()
    {
        return $this
            ->hasOne('User', 'user_id', 'user_id', [], 'left')
            ->field('username,nickname,head_pic')
            ->setEagerlyType(0);
    }

    /**
     * 生成唯一交易流水号
     * @access private
     * @return string
     */
    private function getPaymentNo()
    {
        do {
            $paymentNo = get_order_no('ZF_');
        } while (self::checkUnique(['payment_no' => ['eq', $paymentNo]]));

        return $paymentNo;
    }

    /**
     * 添加一笔交易日志
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addPaymentLogItem($data)
    {
        if (!$this->validateData($data, 'PaymentLog')) {
            return false;
        }

        // 初始化部分数据
        unset($data['payment_log_id'], $data['payment_time'], $data['to_payment']);
        $this->setAttr('payment_log_id', null);
        $data['payment_no'] = $this->getPaymentNo();
        $data['user_id'] = get_client_id();
        isset($data['status']) ?: $data['status'] = 0;

        if (false !== $this->isUpdate(false)->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 关闭一笔充值记录
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function closePaymentLogItem($data)
    {
        if (!$this->validateData($data, 'PaymentLog.close')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['payment_no'] = ['eq', $data['payment_no']];
            $map['user_id'] = ['eq', get_client_id()];

            $query->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        if ($result->getAttr('status') !== 0) {
            return $this->setError('状态不可变更');
        }

        $result->setAttr('status', 2);
        if (false !== $result->save()) {
            return true;
        }

        return false;
    }

    /**
     * 获取一笔充值记录
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getPaymentLogItem($data)
    {
        if (!$this->validateData($data, 'PaymentLog.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['payment_no'] = ['eq', $data['payment_no']];
            is_client_admin() ?: $map['user_id'] = ['eq', get_client_id()];
            !isset($data['type']) ?: $map['type'] = ['eq', $data['type']];
            !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];

            $query->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取充值记录列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getPaymentLogList($data)
    {
        if (!$this->validateData($data, 'PaymentLog.list')) {
            return false;
        }

        // 搜索条件
        $map['payment_log.user_id'] = ['eq', get_client_id()];
        empty($data['payment_no']) ?: $map['payment_log.payment_no'] = ['eq', $data['payment_no']];
        empty($data['order_no']) ?: $map['payment_log.order_no'] = ['eq', $data['order_no']];
        empty($data['out_trade_no']) ?: $map['payment_log.out_trade_no'] = ['eq', $data['out_trade_no']];
        !isset($data['status']) ?: $map['payment_log.status'] = ['eq', $data['status']];

        if (!empty($data['begin_time']) && !empty($data['end_time'])) {
            $map['payment_log.create_time'] = ['between time', [$data['begin_time'], $data['end_time']]];
        }

        // 关联查询
        $with = [];
        if (is_client_admin()) {
            $with = ['getUser'];
            unset($map['payment_log.user_id']);
            !isset($data['to_payment']) ?: $map['payment_log.to_payment'] = ['eq', $data['to_payment']];
            empty($data['account']) ?: $map['getUser.username|getUser.nickname'] = ['eq', $data['account']];
        }

        // 获取总数量,为空直接返回
        $totalResult = $this->alias('payment_log')->with($with)->where($map)->count();
        if ($totalResult <= 0) {
            return ['total_result' => 0];
        }

        $result = self::all(function ($query) use ($data, $map, $with) {
            // 翻页页数
            $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

            // 每页条数
            $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'desc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'payment_log_id';

            $query
                ->alias('payment_log')
                ->with($with)
                ->where($map)
                ->order(['payment_log.' . $orderField => $orderType])
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }

    /**
     * 获取一笔订单成功付款的具体金额
     * @access public
     * @param  string $paymentNo 交易流水号
     * @return float|int
     */
    public static function getPaymentLogValue($paymentNo)
    {
        if (empty($paymentNo)) {
            return 0;
        }

        $map['payment_no'] = ['eq', $paymentNo];
        $map['type'] = ['eq', 1];
        $map['status'] = ['eq', 1];

        return self::where($map)->value('amount', 0, true);
    }
}