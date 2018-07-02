<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    支付
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/7/1
 */

namespace payment;

class Payment
{
    /**
     * 错误信息
     * @var string
     */
    protected $error = '';

    /**
     * 同步返回URL
     * @var string
     */
    protected $returnUrl;

    /**
     * 异步返回URL
     * @var string
     */
    protected $notifyUrl;

    /**
     * 支付流水号
     * @var string
     */
    protected $outTradeNo;

    /**
     * 订单名称
     * @var string
     */
    protected $subject;

    /**
     * 支付金额
     * @var float
     */
    protected $totalAmount;

    /**
     * 支付描述
     * @var string
     */
    protected $body = '';

    /**
     * 返回错误信息
     * @access public
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 设置同步返回URL
     * @access public
     * @param  string $returnUrl 同步返回URL
     * @return bool
     */
    public function setReturnUrl($returnUrl)
    {
        if (is_string($returnUrl)) {
            $this->returnUrl = $returnUrl;
            return true;
        }

        return false;
    }

    /**
     * 设置异步返回URL
     * @access public
     * @param  string $notifyUrl 异步返回URL
     * @return bool
     */
    public function setNotifyUrl($notifyUrl)
    {
        if (is_string($notifyUrl)) {
            $this->notifyUrl = $notifyUrl;
            return true;
        }

        return false;
    }

    /**
     * 设置支付流水号
     * @access public
     * @param  string $paymentNo 流水号
     */
    public function setOutTradeNo($paymentNo)
    {
        $this->outTradeNo = $paymentNo;
    }

    /**
     * 设置支付订单名称
     * @access public
     * @param  string $subject 订单名称
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * 设置订单支付金额
     * @access public
     * @param  float $amount 支付金额
     */
    public function setTotalAmount($amount)
    {
        $this->totalAmount = $amount;
    }

    /**
     * 设置支付描述
     * @access public
     * @param  string $body 描述
     */
    public function setBody($body = '')
    {
        $this->body = $body;
    }
}