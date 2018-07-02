<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    支付宝原路退回
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/9/25
 */

namespace payment\alipay;

use payment\Payment;

require_once __DIR__ . '/lib/AopClient.php';
require_once __DIR__ . '/lib/request/AlipayTradeRefundRequest.php';
require_once __DIR__ . '/lib/request/AlipayTradeFastpayRefundQueryRequest.php';

class Refund extends Payment
{
    /**
     * 应用ID
     * @var string
     */
    private $appId;

    /**
     * 商户私钥
     * @var string
     */
    private $merchantPrivateKey;

    /**
     * 签名方式
     * @var string
     */
    private $signType;

    /**
     * 支付宝公钥
     * @var string
     */
    private $alipayPublicKey;

    /**
     * 退款流水号
     * @var string
     */
    private $refundNo;

    /**
     * 退款金额
     * @var float
     */
    private $refundAmount;

    /**
     * 交易号
     * @var string
     */
    protected $tradeNo;

    /**
     * 设置支付配置
     * @access public
     * @param  array $setting 配置信息
     * @return bool
     */
    public function setConfig($setting)
    {
        foreach ($setting as $key => $value) {
            $this->$key = $value['value'];
        }

        if (empty($this->appId) || trim($this->appId) == '') {
            $this->error = '应用ID不能为空';
            return false;
        }

        if (empty($this->merchantPrivateKey) || trim($this->merchantPrivateKey) == '') {
            $this->error = '商户私钥不能为空';
            return false;
        }

        if (empty($this->signType) || trim($this->signType) == '') {
            $this->error = '签名方式不能为空';
            return false;
        }

        if (empty($this->alipayPublicKey) || trim($this->alipayPublicKey) == '') {
            $this->error = '支付宝公钥不能为空';
            return false;
        }

        return true;
    }

    /**
     * 设置退款流水号
     * @access public
     * @param  string $refundNo 退款流水号
     */
    public function setRefundNo($refundNo)
    {
        $this->refundNo = $refundNo;
    }

    /**
     * 设置退款金额
     * @access public
     * @param  string $amount 退款金额
     */
    public function setRefundAmount($amount)
    {
        $this->refundAmount = $amount;
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
     * 返回退款请求结果
     * @access public
     * @return array|false
     * @throws
     */
    public function refundRequest()
    {
        // 业务参数
        $bizContent = [
            'out_trade_no'   => $this->outTradeNo,
            'refund_amount'  => $this->refundAmount,
            'out_request_no' => $this->refundNo,
        ];

        $request = new \AlipayTradeRefundRequest();
        $request->setBizContent(json_encode($bizContent, JSON_UNESCAPED_UNICODE));

        // 请求客户端
        $aop = new \AopClient();
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->merchantPrivateKey;
        $aop->alipayrsaPublicKey = $this->alipayPublicKey;
        $aop->signType = $this->signType;
        $aop->debugInfo = false; // 开启页面信息输出

        $result = $aop->Execute($request);
        if ($result->alipay_trade_refund_response->code != 10000) {
            $this->error = $result->alipay_trade_refund_response->sub_msg;
            return false;
        }

        $this->tradeNo = $result->alipay_trade_refund_response->trade_no;
        return true;
    }

    /**
     * 返回退款查询请求结果
     * @access public
     * @return array|false
     * @throws
     */
    public function refundFastpayQueryRequest()
    {
        // 业务参数
        $bizContent = [
            'out_trade_no'   => $this->outTradeNo,
            'out_request_no' => $this->refundNo,
        ];

        $request = new \AlipayTradeFastpayRefundQueryRequest();
        $request->setBizContent(json_encode($bizContent, JSON_UNESCAPED_UNICODE));

        // 请求客户端
        $aop = new \AopClient();
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->merchantPrivateKey;
        $aop->alipayrsaPublicKey = $this->alipayPublicKey;
        $aop->signType = $this->signType;
        $aop->debugInfo = false; // 开启页面信息输出

        $result = $aop->Execute($request);
        if ($result->alipay_trade_fastpay_refund_query_response->code != 10000) {
            $this->error = $result->alipay_trade_fastpay_refund_query_response->sub_msg;
            return false;
        }

        if (isset($result->alipay_trade_fastpay_refund_query_response->trade_no)) {
            $data = [
                'refund_amount'      => (float)$result->alipay_trade_fastpay_refund_query_response->refund_amount,
                'refund_status'      => '退款成功',
                'refund_recv_accout' => '支付宝原路退回',
                'refund_no'          => $result->alipay_trade_fastpay_refund_query_response->out_request_no,
                'payment_no'         => $result->alipay_trade_fastpay_refund_query_response->out_trade_no,
                'out_trade_no'       => $result->alipay_trade_fastpay_refund_query_response->trade_no,
            ];
        } else {
            $data = [
                'refund_amount'      => 0.00,
                'refund_status'      => '退款处理中',
                'refund_recv_accout' => '支付宝原路退回',
                'refund_no'          => '',
                'payment_no'         => '',
                'out_trade_no'       => '',
            ];
        }

        return $data;
    }
}