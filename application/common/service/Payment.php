<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    支付配置服务层
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/1/27
 */

namespace app\common\service;

use think\Url;
use think\helper\Str;
use think\Loader;

class Payment extends CareyShop
{
    /**
     * 获取支付异步URL接口
     * @access public
     * @param  array $data     外部数据
     * @param  bool  $isString 是否直接返回URL地址
     * @return mixed
     */
    public function getPaymentNotify($data, $isString = false)
    {
        $validate = Loader::validate('Recharge');
        if (!$validate->scene('return')->check($data)) {
            return $this->setError($validate->getError());
        }

        $vars = ['method' => 'put.payment.data', 'to_payment' => $data['to_payment'], 'type' => 'notify'];
        $notifyUrl = Url::bUild('/api/v1/payment', $vars, true, true);

        return $isString ? $notifyUrl : ['notify_url' => $notifyUrl];
    }

    /**
     * 获取支付同步URL接口
     * @access public
     * @param  array $data     外部数据
     * @param  bool  $isString 是否直接返回URL地址
     * @return mixed
     */
    public function getPaymentReturn($data, $isString = false)
    {
        $validate = Loader::validate('Recharge');
        if (!$validate->scene('return')->check($data)) {
            return $this->setError($validate->getError());
        }

        $vars = ['method' => 'put.payment.data', 'to_payment' => $data['to_payment'], 'type' => 'return'];
        $notifyUrl = Url::bUild('/api/v1/payment', $vars, true, true);

        return $isString ? $notifyUrl : ['return_url' => $notifyUrl];
    }

    /**
     * 创建支付模块
     * @access public
     * @param  string $file  支付目录
     * @param  string $model 支付模块
     * @return object|false
     */
    public function createPaymentModel($file, $model)
    {
        // 转换模块的名称
        $file = Str::lower($file);
        $model = Str::studly($model);

        if (empty($file) || empty($model)) {
            return $this->setError('支付目录或模块不存在');
        }

        $payment = '\\payment\\' . $file . '\\' . $model;
        if (class_exists($payment)) {
            return new $payment;
        }

        return $this->setError($payment . '支付模块不存在');
    }

    /**
     * 创建支付请求
     * @access public
     * @param  array  &$data    支付日志
     * @param  array  &$setting 支付配置
     * @param  string $request  请求来源
     * @param  string $subject  订单名称
     * @param  string $body     订单描述
     * @return array|false
     */
    public function createPaymentPay(&$data, &$setting, $request, $subject, $body = '')
    {
        if (empty($data) || !is_array($setting)) {
            return $this->setError('数据错误');
        }

        // 创建支付总控件
        $payment = $this->createPaymentModel($setting['model'], $setting['model']);
        if (false === $payment) {
            return false;
        }

        // 设置支付配置
        if (!$payment->setQequest($request)->setConfig($setting['setting'])) {
            return $this->setError($payment->getError());
        }

        // 设置支付同步返回URL
        if (!$payment->setReturnUrl($this->getPaymentReturn(['to_payment' => $setting['code']], true))) {
            return false;
        }

        // 设置支付异步返回URL
        if (!$payment->setNotifyUrl($this->getPaymentNotify(['to_payment' => $setting['code']], true))) {
            return false;
        }

        // 设置支付流水号
        $payment->setOutTradeNo($data['payment_no']);

        // 设置支付订单名称
        $payment->setSubject($subject);

        // 设置支付金额
        $payment->setTotalAmount($data['amount']);

        // 设置支付描述
        $payment->setBody($body);

        // 返回支付模块请求结果
        $result = $payment->payRequest();

        return false === $result ? $this->setError($payment->getError()) : $result;
    }
}