<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    微信支付原路退回
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/9/25
 */

namespace payment\weixin;

use payment\Payment;

require_once __DIR__ . '/lib/WxPay.Api.php';

class Refund extends Payment
{
    /**
     * 绑定支付的APPID
     * @var string
     */
    private $appid;

    /**
     * 商户号
     * @var string
     */
    private $mchid;

    /**
     * 商户支付密钥
     * @var string
     */
    private $key;

    /**
     * 公众帐号Secert
     * @var string
     */
    private $appsecret = '';

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
     * apiclient_cert
     * @var string
     */
    private $sslcert = '';

    /**
     * apiclient_key
     * @var string
     */
    private $sslkey = '';

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

        if (empty($this->appid) || trim($this->appid) == '') {
            $this->error = '绑定支付的APPID不能为空';
            return false;
        }

        if (empty($this->mchid) || trim($this->mchid) == '') {
            $this->error = '商户号不能为空';
            return false;
        }

        if (empty($this->key) || trim($this->key) == '') {
            $this->error = '商户支付密钥不能为空';
            return false;
        }

        if (empty($this->sslcert) || trim($this->sslcert) == '') {
            $this->error = 'apiclient_cert证书不能为空';
            return false;
        }

        if (empty($this->sslkey) || trim($this->sslkey) == '') {
            $this->error = 'apiclient_key证书不能为空';
            return false;
        }

        \WxPayConfig::$appid = $this->appid;
        \WxPayConfig::$mchid = $this->mchid;
        \WxPayConfig::$key = $this->key;
        \WxPayConfig::$appsecret = $this->appsecret;
        \WxPayConfig::$sslcert = $this->sslcert;
        \WxPayConfig::$sslkey = $this->sslkey;

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
        $input = new \WxPayRefund();
        $input->SetOut_trade_no($this->outTradeNo);
        $input->SetTotal_fee($this->totalAmount * 100);
        $input->SetRefund_fee($this->refundAmount * 100);
        $input->SetOut_refund_no($this->refundNo);
        $input->SetOp_user_id($this->mchid);

        $result = \WxPayApi::refund($input);
        if ($result['return_code'] != 'SUCCESS') {
            $this->error = isset($result['return_msg']) ? $result['return_msg'] : '未知错误';
            return false;
        }

        if ($result['result_code'] != 'SUCCESS') {
            $this->error = isset($result['err_code_des']) ? $result['err_code_des'] : '未知错误';
            return false;
        }

        $this->tradeNo = $result['transaction_id'];
        return true;
    }

    /**
     * 交易退款查询
     * @access public
     * @return array|false
     * @throws
     */
    public function refundFastpayQueryRequest()
    {
        $input = new \WxPayRefundQuery();
        $input->SetOut_refund_no($this->refundNo);

        $result = \WxPayApi::refundQuery($input);
        if ($result['return_code'] != 'SUCCESS') {
            $this->error = isset($result['return_msg']) ? $result['return_msg'] : '未知错误';
            return false;
        }

        if ($result['result_code'] != 'SUCCESS') {
            $this->error = isset($result['err_code_des']) ? $result['err_code_des'] : '未知错误';
            return false;
        }

        $refund_status = [
            'SUCCESS'     => '退款成功',
            'REFUNDCLOSE' => '退款关闭',
            'PROCESSING'  => '退款处理中',
            'CHANGE'      => '退款异常',
        ];

        $data = [
            'refund_amount'      => $result['refund_fee'] / 100,
            'refund_status'      => $refund_status[$result['refund_status_0']],
            'refund_recv_accout' => $result['refund_recv_accout_0'],
            'refund_no'          => $result['out_refund_no_0'],
            'payment_no'         => $result['out_trade_no'],
            'out_trade_no'       => $result['transaction_id'],
        ];

        return $data;
    }
}