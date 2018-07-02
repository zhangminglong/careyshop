<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    货到付款同步返回
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/9/3
 */

namespace payment\cod;

use think\Config;
use think\Controller;

class ReturnUrl extends Controller
{
    /**
     * 流水号
     * @var mixed
     */
    public $paymentNo;

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
        return 0;
    }

    /**
     * 返回交易号
     * @access public
     * @return string
     */
    public function getTradeNo()
    {
        return rand_number(28);
    }

    /**
     * 返回交易时间
     * @access public
     * @return string
     */
    public function getTimestamp()
    {
        return date('Y-m-d H:i:s', time());
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
     * @return bool
     */
    public function checkReturn()
    {
        $this->paymentNo = $this->request->param('out_trade_no');
        return true;
    }
}