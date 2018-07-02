<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    支付宝同步返回
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/7/2
 */

namespace payment\alipay;

use think\Config;

require_once __DIR__ . '/lib/AopClient.php';

class ReturnUrl
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
     * 返回支付成功页面
     * @access public
     * @param  string $msg 消息内容
     * @return array
     */
    public function getSuccess($msg = '支付结算完成')
    {
        $data['callback_return_type'] = 'view';
        $data['is_callback'] = sprintf(
            '<head><meta http-equiv="refresh" content="0; url=%s?info=%s&payment_no=%s"></head>',
            Config::get('success.value', 'payment'),
            $msg,
            $this->paymentNo
        );

        return $data;
    }

    /**
     * 返回支付失败页面
     * @access public
     * @param  string $msg 消息内容
     * @return array
     */
    public function getError($msg = '支付结算失败')
    {
        $data['callback_return_type'] = 'view';
        $data['is_callback'] = sprintf(
            '<head><meta http-equiv="refresh" content="0; url=%s?info=%s&payment_no=%s"></head>',
            Config::get('error.value', 'payment'),
            $msg,
            $this->paymentNo
        );

        return $data;
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

        $arr = $_GET;
        $this->paymentNo = isset($arr['out_trade_no']) ? $arr['out_trade_no'] : 0;
        $this->totalAmount = isset($arr['total_amount']) ? $arr['total_amount'] : 0;
        $this->tradeNo = isset($arr['trade_no']) ? $arr['trade_no'] : '';
        $this->timestamp = isset($arr['timestamp']) ? $arr['timestamp'] : '';

        if (!isset($arr['sign_type'])) {
            return false;
        }

        $aop = new \AopClient();
        $aop->alipayrsaPublicKey = $setting['alipayPublicKey']['value'];

        if (!$aop->rsaCheckV1($arr, $aop->alipayrsaPublicKey, $arr['sign_type'])) {
            return false;
        }

        if ($arr['app_id'] != $setting['appId']['value']) {
            return false;
        }

        return true;
    }
}