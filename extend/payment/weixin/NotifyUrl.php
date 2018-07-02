<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    微信支付异步返回
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/7/4
 */

namespace payment\weixin;

require_once __DIR__ . '/lib/WxPay.Api.php';
require_once __DIR__ . '/lib/WxPay.Notify.php';

class NotifyUrl extends \WxPayNotify
{
    /**
     * 流水号
     * @var string
     */
    protected $paymentNo;

    /**
     * 总金额
     * @var float
     */
    protected $totalAmount;

    /**
     * 交易号
     * @var string
     */
    protected $tradeNo;

    /**
     * 交易时间
     * @var string
     */
    protected $timestamp;

    /**
     * 验签是否通过
     * @var bool
     */
    private $isCheck = false;

    /**
     * 返回流水号
     * @access public
     * @return string
     */
    public function getPaymentNo()
    {
        return $this->paymentNo;
    }

    /**
     * 返回总金额
     * @access public
     * @return string
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * 返回交易号
     * @access public
     * @return string
     */
    public function getTradeNo()
    {
        return $this->tradeNo;
    }

    /**
     * 返回交易时间
     * @access public
     * @return string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * 返回支付成功响应
     * @access public
     * @param  string $msg 消息内容
     * @return array
     */
    public function getSuccess($msg = '')
    {
        unset($msg);
        $data['callback_return_type'] = 'view';
        $data['is_callback'] = 'success';

        return $data;
    }

    /**
     * 返回支付失败响应
     * @access public
     * @param  string $msg 消息内容
     * @return array
     */
    public function getError($msg = '')
    {
        unset($msg);
        $data['callback_return_type'] = 'view';
        $data['is_callback'] = 'fail';

        return $data;
    }

    /**
     * 查询订单
     * @access public
     * @param  string $transactionId 微信订单号
     * @return bool
     * @throws
     */
    public function orderQuery($transactionId)
    {
        $input = new \WxPayOrderQuery();
        $input->SetTransaction_id($transactionId);
        $result = \WxPayApi::orderQuery($input);

        if (array_key_exists('return_code', $result)
            && array_key_exists('result_code', $result)
            && $result['return_code'] == 'SUCCESS'
            && $result['result_code'] == 'SUCCESS'
        ) {
            return true;
        }

        if ($result['trade_state'] != 'SUCCESS') {
            return false;
        }

        if ($result['appid'] != \WxPayConfig::$appid) {
            return false;
        }

        return false;
    }

    /**
     * 重写回调处理函数
     * @access public
     * @param  array  $data 数据
     * @param  string $msg  消息
     * @return bool
     */
    public function NotifyProcess($data, &$msg)
    {
        if (!array_key_exists('transaction_id', $data)) {
            return false;
        }

        // 查询订单，判断订单真实性
        if (!$this->orderQuery($data['transaction_id'])) {
            return false;
        }

        $this->paymentNo = $data['out_trade_no'];
        $this->totalAmount = $data['total_fee'] / 100;
        $this->tradeNo = $data['transaction_id'];
        $this->timestamp = date('Y-m-d H:i:s', strtotime($data['time_end']));

        $this->isCheck = true;
        return true;
    }

    /**
     * 验签方法
     * @access public
     * @param  array $setting 配置参数
     * @return bool
     */
    public function checkReturn($setting = null)
    {
        if (empty($setting)) {
            return false;
        }

        \WxPayConfig::$appid = $setting['appid']['value'];
        \WxPayConfig::$mchid = $setting['mchid']['value'];
        \WxPayConfig::$key = $setting['key']['value'];
        \WxPayConfig::$appsecret = $setting['appsecret']['value'];

        $this->Handle(false);
        return $this->isCheck;
    }
}