<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    支付配置模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/6/26
 */

namespace app\common\model;

use think\Cache;

class Payment extends CareyShop
{
    /**
     * 账号资金
     * @var int
     */
    const PAYMENT_CODE_USER = 0;

    /**
     * 货到付款
     * @var int
     */
    const PAYMENT_CODE_COD = 1;

    /**
     * 支付宝
     * @var int
     */
    const PAYMENT_CODE_ALIPAY = 2;

    /**
     * 微信支付
     * @var int
     */
    const PAYMENT_CODE_WECHAT = 3;

    /**
     * 银行转账
     * @var int
     */
    const PAYMENT_CODE_BANK = 4;

    /**
     * 购物卡
     * @var int
     */
    const PAYMENT_CODE_CARD = 5;

    /**
     * 其他
     * @var int
     */
    const PAYMENT_CODE_OTHER = 6;

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'payment_id',
        'name',
        'code',
        'model',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'payment_id' => 'integer',
        'code'       => 'integer',
        'is_deposit' => 'integer',
        'is_inpour'  => 'integer',
        'is_payment' => 'integer',
        'is_refund'  => 'integer',
        'setting'    => 'array',
        'sort'       => 'integer',
        'status'     => 'integer',
    ];

    /**
     * 添加一个支付配置
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addPaymentItem($data)
    {
        if (!$this->validateData($data, 'Payment')) {
            return false;
        }

        // 避免无关字段及数据初始化
        unset($data['payment_id']);
        !empty($data['setting']) ?: $data['setting'] = [];

        if (false !== $this->allowField(true)->save($data)) {
            Cache::clear('Payment');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个支付配置
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setPaymentItem($data)
    {
        if (!$this->validateSetData($data, 'Payment.set')) {
            return false;
        }

        if (isset($data['setting']) && '' == $data['setting']) {
            $data['setting'] = [];
        }

        $map['payment_id'] = ['eq', $data['payment_id']];
        if (false !== $this->allowField(true)->save($data, $map)) {
            Cache::clear('Payment');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除支付配置
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delPaymentList($data)
    {
        if (!$this->validateData($data, 'Payment.del')) {
            return false;
        }

        self::destroy($data['payment_id']);
        Cache::clear('Payment');

        return true;
    }

    /**
     * 获取一个支付配置
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getPaymentItem($data)
    {
        if (!$this->validateData($data, 'Payment.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $query->cache(true, null, 'Payment')->where(['payment_id' => ['eq', $data['payment_id']]]);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 根据Code获取支付配置详情(不对外开放)
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getPaymentInfo($data)
    {
        if (!$this->validateData($data, 'Payment.info')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['code'] = ['eq', $data['code']];
            $map['status'] = ['eq', $data['status']];

            $query->cache(true, null, 'Payment')->field('image,sort,status', true)->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取支付配置列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getPaymentList($data)
    {
        if (!$this->validateData($data, 'Payment.list')) {
            return false;
        }

        // 查询条件
        $map = [];
        is_client_admin() ?: $map['status'] = ['eq', 1];
        empty($data['exclude_code']) ?: $map['code'] = ['not in', $data['exclude_code']];

        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'deposit':
                    $map['is_deposit'] = ['eq', 1];
                    break;
                case 'inpour':
                    $map['is_inpour'] = ['eq', 1];
                    break;
                case 'payment':
                    $map['is_payment'] = ['eq', 1];
                    break;
                case 'refund':
                    $map['is_refund'] = ['eq', 1];
                    break;
            }
        }

        $field = 'payment_id,name,code,image,is_deposit,is_inpour,is_payment,is_refund,model,sort,status';
        if (!empty($data['is_select'])) {
            $field = 'name,code,image';
            $map['status'] = ['eq', 1];
        }

        $result = self::all(function ($query) use ($map, $field) {
            // 排序处理
            $order['sort'] = 'asc';
            $order['payment_id'] = 'asc';

            $query->cache(true, null, 'Payment')->field($field)->where($map)->order($order);
        });

        if (false !== $result) {
            return $result->toArray();
        }

        return false;
    }

    /**
     * 设置支付配置排序
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setPaymentSort($data)
    {
        if (!$this->validateData($data, 'Payment.sort')) {
            return false;
        }

        $map['payment_id'] = ['eq', $data['payment_id']];
        if (false !== $this->save(['sort' => $data['sort']], $map)) {
            Cache::clear('Payment');
            return true;
        }

        return false;
    }

    /**
     * 批量设置支付配置状态
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setPaymentStatus($data)
    {
        if (!$this->validateData($data, 'Payment.status')) {
            return false;
        }

        $map['payment_id'] = ['in', $data['payment_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            Cache::clear('Payment');
            return true;
        }

        return false;
    }

    /**
     * 财务对账号进行资金调整
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function setPaymentFinance($data)
    {
        if (!$this->validateData($data, 'Recharge.finance')) {
            return false;
        }

        if (!isset($data['money']) && !isset($data['points'])) {
            return $this->setError('资金或积分调整数量必须填写');
        }

        $paymentResult = $this->getPaymentInfo(['code' => $data['to_payment'], 'status' => 1]);
        if (!$paymentResult || $paymentResult['is_deposit'] != 1) {
            return $this->setError('支付方式不可用');
        }

        $userResult = User::where(['username' => ['eq', $data['username']], 'status' => ['eq', 1]])->value('user_id');
        if (!$userResult) {
            return $this->setError('账号不存在');
        }

        // 开启事务
        self::startTrans();

        try {
            $userMoneyDb = new UserMoney();
            $transactionDb = new Transaction();

            // 调整可用余额
            if (isset($data['money'])) {
                if (!$userMoneyDb->setBalance($data['money'], $userResult)) {
                    throw new \Exception($userMoneyDb->getError());
                }

                $txMoneyData = [
                    'user_id'    => $userResult,
                    'type'       => $data['money'] > 0 ? 0 : 1,
                    'amount'     => sprintf('%.2f', $data['money'] > 0 ? $data['money'] : -$data['money']),
                    'balance'    => $userMoneyDb->where(['user_id' => ['eq', $userResult]])->value('balance'),
                    'source_no'  => !empty($data['source_no']) ? $data['source_no'] : '',
                    'remark'     => '财务调整',
                    'cause'      => $data['cause'],
                    'module'     => 'money',
                    'to_payment' => $data['to_payment'],
                ];

                if (!$transactionDb->addTransactionItem($txMoneyData)) {
                    throw new \Exception($transactionDb->getError());
                }
            }

            if (isset($data['points'])) {
                // 调整账号积分
                if (!$userMoneyDb->setPoints($data['points'], $userResult)) {
                    throw new \Exception($userMoneyDb->getError());
                }

                $txPointsData = [
                    'user_id'    => $userResult,
                    'type'       => $data['points'] > 0 ? 0 : 1,
                    'amount'     => $data['points'] > 0 ? $data['points'] : -$data['points'],
                    'balance'    => $userMoneyDb->where(['user_id' => ['eq', $userResult]])->value('points'),
                    'source_no'  => !empty($data['source_no']) ? $data['source_no'] : '',
                    'remark'     => '财务调整',
                    'cause'      => $data['cause'],
                    'module'     => 'points',
                    'to_payment' => $data['to_payment'],
                ];

                if (!$transactionDb->addTransactionItem($txPointsData)) {
                    throw new \Exception($transactionDb->getError());
                }
            }

            self::commit();
            return true;
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 账号在线充值余额
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     */
    public function userPaymentPay($data)
    {
        if (!$this->validateData($data, 'Recharge.user')) {
            return false;
        }

        // 获取支付配置信息
        $paymentResult = $this->getPaymentInfo(['code' => $data['to_payment'], 'status' => 1]);
        if (!$paymentResult || $paymentResult['is_inpour'] != 1) {
            return $this->setError('支付方式不可用');
        }

        // 当支付流水号存在,则恢复支付
        $paymentLogResult = null;
        $paymentLogDb = new PaymentLog();

        // 获取已存在未支付的支付日志
        $logData['type'] = 0;
        if (!empty($data['payment_no'])) {
            $logData['payment_no'] = $data['payment_no'];
            $logData['status'] = 0;
            $paymentLogResult = $paymentLogDb->getPaymentLogItem($logData);

            if (false === $paymentLogResult) {
                return $this->setError($paymentLogDb->getError());
            }

            // 支付金额不匹配则需要更新
            if (bccomp($paymentLogResult->getAttr('amount'), $data['money'], 2) !== 0) {
                $paymentLogResult->save(['amount' => $data['money']]);
            }
        }

        // 创建新的支付日志
        if (!$paymentLogResult) {
            $logData['amount'] = $data['money'];
            $paymentLogResult = $paymentLogDb->addPaymentLogItem($logData);

            if (false === $paymentLogResult) {
                return $this->setError($paymentLogDb->getError());
            }
        }

        $paymentSer = new \app\common\service\Payment();
        $result = $paymentSer->createPaymentPay($paymentLogResult, $paymentResult, $data['request_type'], '账号充值');

        if (false === $result) {
            return $this->setError($paymentSer->getError());
        }

        return $result;
    }

    /**
     * 订单付款在线支付
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function orderPaymentPay($data)
    {
        if (!$this->validateData($data, 'Recharge.order')) {
            return false;
        }

        // 获取订单信息
        $map['order_no'] = ['eq', $data['order_no']];
        $map['user_id'] = ['eq', get_client_id()];
        $map['is_delete'] = ['eq', 0];

        $orderDb = new Order();
        $result = $orderDb->where($map)->find();

        if (!$result) {
            return $this->setError(is_null($result) ? '订单不存在' : $orderDb->getError());
        }

        if ($result->getAttr('trade_status') !== 0) {
            return $this->setError('订单不可支付');
        }

        if ($result->getAttr('payment_status') === 1) {
            return $this->setError('订单已完成支付');
        }

        // 创建新的支付日志
        $logData = [
            'order_no' => $result->getAttr('order_no'),
            'amount'   => $data['to_payment'] != self::PAYMENT_CODE_COD ? $result->getAttr('total_amount') : 0,
            'type'     => 1,
            'status'   => 0,
        ];

        $paymentLogDb = new PaymentLog();
        $paymentLogResult = $paymentLogDb->addPaymentLogItem($logData);

        if (false === $paymentLogResult) {
            return $this->setError($paymentLogDb->getError());
        }

        // 应付金额为0时直接内部处理
        $paymentSer = new \app\common\service\Payment();
        if (round($paymentLogResult['amount'], 2) <= 0) {
            $model = $paymentSer->createPaymentModel('cod', 'return_url');
            if (false === $model) {
                return $this->setError($paymentSer->getError());
            }

            $model->paymentNo = $paymentLogDb->getAttr('payment_no');
            return $this->settleOrder($model, $paymentLogDb, $data['to_payment']);
        }

        // 获取支付配置信息
        $paymentResult = $this->getPaymentInfo(['code' => $data['to_payment'], 'status' => 1]);
        if (!$paymentResult || $paymentResult['is_payment'] != 1) {
            return $this->setError('支付方式不可用');
        }

        $createResult = $paymentSer->createPaymentPay($paymentLogResult, $paymentResult, $data['request_type'], '订单付款');
        if (false === $createResult) {
            return $this->setError($paymentSer->getError());
        }

        return $createResult;
    }

    /**
     * 接收支付返回内容
     * @access public
     * @param  array $data 外部数据
     * @return bool|string
     * @throws
     */
    public function putPaymentData($data)
    {
        if (!$this->validateData($data, 'Recharge.put')) {
            return false;
        }

        // 获取支付配置信息
        $paymentResult = $this->getPaymentInfo(['code' => $data['to_payment'], 'status' => 1]);
        if (!$paymentResult) {
            return $this->setError('支付方式不可用');
        }

        // 创建支付总控件
        $model = $data['type'] == 'return' ? 'return_url' : 'notify_url';
        $paymentSer = new \app\common\service\Payment();
        $payment = $paymentSer->createPaymentModel($paymentResult['model'], $model);

        if (false === $payment) {
            return $this->setError($paymentSer->getError());
        }

        // 初始化配置,并且进行验签
        if (!$payment->checkReturn($paymentResult['setting'])) {
            return $payment->getError('非法访问');
        }

        // 获取支付日志信息
        $paymentLogResult = PaymentLog::get(['payment_no' => $payment->getPaymentNo()]);
        if (!$paymentLogResult) {
            return $payment->getError('数据不存在');
        }

        // 已完成支付
        if ($paymentLogResult->getAttr('status') !== 0) {
            return $payment->getSuccess();
        }

        // 结算实际业务
        $result = false;
        switch ($paymentLogResult->getAttr('type')) {
            case 0:
                $result = $this->settlePay($payment, $paymentLogResult, $paymentResult['code']);
                break;

            case 1:
                $result = $this->settleOrder($payment, $paymentLogResult, $paymentResult['code']);
                break;
        }

        return false === $result ? $payment->getError() : $payment->getSuccess();
    }

    /**
     * 结算订单付款
     * @access private
     * @param  object &$model        支付模块
     * @param  object &$paymentLogDb 支付日志
     * @param  int    $toPayment     支付方式
     * @return bool
     * @throws
     */
    private function settleOrder(&$model, &$paymentLogDb, $toPayment)
    {
        // 共用参数提取
        $userId = $paymentLogDb->getAttr('user_id');
        $amount = $model->getTotalAmount();

        // 实付金额不得小于应付金额
        if (bccomp($amount, $paymentLogDb->getAttr('amount'), 2) === -1) {
            return false;
        }

        if (bccomp($amount, 0, 2) === 0 && $toPayment != self::PAYMENT_CODE_COD) {
            $toPayment = self::PAYMENT_CODE_USER;
        }

        // 开启事务
        self::startTrans();

        try {
            // 保存支付日志
            $logData = [
                'out_trade_no' => $model->getTradeNo(),
                'payment_time' => $model->getTimestamp(),
                'to_payment'   => $toPayment,
                'status'       => 1,
            ];

            if (false === $paymentLogDb->isUpdate(true)->save($logData)) {
                throw new \Exception();
            }

            // 调整订单状态
            $orderDb = new Order();
            $orderResult = $orderDb->isPaymentStatus(['order_no' => $paymentLogDb->getAttr('order_no')]);

            if (!$orderResult || $orderResult->getAttr('user_id') != $userId) {
                throw new \Exception();
            }

            $orderData = [
                'payment_no'     => $model->getPaymentNo(),
                'payment_code'   => $toPayment,
                'payment_status' => 1,
                'payment_time'   => strtotime($model->getTimestamp()),
            ];

            if (false === $orderResult->isUpdate(true)->save($orderData)) {
                throw new \Exception();
            }

            // 保存订单操作日志
            $orderLogData = [
                'order_id'        => $orderResult->getAttr('order_id'),
                'order_no'        => $orderResult->getAttr('order_no'),
                'trade_status'    => $orderResult->getAttr('trade_status'),
                'delivery_status' => $orderResult->getAttr('delivery_status'),
                'payment_status'  => $orderResult->getAttr('payment_status'),
            ];

            if (!$orderDb->addOrderLog($orderLogData, '订单付款成功', '订单付款')) {
                throw new \Exception();
            }

            self::commit();
            return true;
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 结算账号充值
     * @access private
     * @param  object &$model        支付模块
     * @param  object &$paymentLogDb 支付日志
     * @param  int    $toPayment     支付方式
     * @return bool
     * @throws
     */
    private function settlePay(&$model, &$paymentLogDb, $toPayment)
    {
        // 共用参数提取
        $userId = $paymentLogDb->getAttr('user_id');
        $amount = $model->getTotalAmount();

        // 开启事务
        self::startTrans();

        try {
            // 保存支付日志
            $logData = [
                'out_trade_no' => $model->getTradeNo(),
                'amount'       => $amount,
                'payment_time' => $model->getTimestamp(),
                'to_payment'   => $toPayment,
                'status'       => 1,
            ];

            if (!$paymentLogDb->save($logData)) {
                throw new \Exception();
            }

            // 调整账号充值金额
            if (!(new UserMoney())->setBalance($amount, $userId)) {
                throw new \Exception();
            }

            // 保存交易结算日志
            $txData = [
                'user_id'    => $userId,
                'type'       => Transaction::TRANSACTION_INCOME,
                'amount'     => $amount,
                'balance'    => UserMoney::where(['user_id' => ['eq', $userId]])->value('balance'),
                'source_no'  => $model->getPaymentNo(),
                'remark'     => '账号充值',
                'module'     => 'money',
                'to_payment' => $toPayment,
            ];

            if (!(new Transaction())->addTransactionItem($txData)) {
                throw new \Exception();
            }

            self::commit();
            return true;
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }
}