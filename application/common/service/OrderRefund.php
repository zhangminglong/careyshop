<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    订单退款服务层
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/1/26
 */

namespace app\common\service;

use think\helper\Str;

class OrderRefund extends CareyShop
{
    /**
     * 创建退款模块
     * @access private
     * @param  string $file  支付目录
     * @param  string $model 退款模块
     * @return object|false
     */
    private function createRefundModel($file, $model)
    {
        // 转换模块的名称
        $file = Str::lower($file);
        $model = Str::studly($model);

        if (empty($file) || empty($model)) {
            return $this->setError('原路退款目录或模块不存在');
        }

        $refund = '\\payment\\' . $file . '\\' . $model;
        if (class_exists($refund)) {
            return new $refund;
        }

        return $this->setError($refund . '原路退款模块不存在');
    }

    /**
     * 创建订单原路退款请求
     * @access public
     * @param  array  &$data    订单数据
     * @param  array  &$setting 支付配置
     * @param  float  $amount   退款金额
     * @param  string $refundNo 退款流水号
     * @return object|false
     */
    public function createRefundRequest(&$data, &$setting, $amount, $refundNo = '')
    {
        if (empty($data) || !is_array($setting)) {
            return $this->setError('数据错误');
        }

        // 创建原路退款总控件
        $refund = $this->createRefundModel($setting['model'], 'refund');
        if (false === $refund) {
            return false;
        }

        // 设置退款配置
        if (!$refund->setConfig($setting['setting'])) {
            return $this->setError($refund->getError());
        }

        // 设置支付流水号
        $refund->setOutTradeNo($data['payment_no']);

        // 设置订单金额
        $refund->setTotalAmount($data['total_amount']);

        // 设置退款金额
        $refund->setRefundAmount($amount);

        // 设置退款流水号
        $refund->setRefundNo($refundNo);

        // 返回请求结果
        $result = $refund->refundRequest();

        return false === $result ? $this->setError($refund->getError()) : $refund;
    }

    /**
     * 创建退款查询请求
     * @access public
     * @param  array $refundLog 退款记录数据结构
     * @param  array &$setting  支付配置
     * @return array|false
     */
    public function createFastpayRefundQueryRequest($refundLog, &$setting)
    {
        if (empty($refundLog) || !is_array($setting)) {
            return $this->setError('数据错误');
        }

        // 创建原路退款总控件
        $refund = $this->createRefundModel($setting['model'], 'refund');
        if (false === $refund) {
            return false;
        }

        // 设置退款配置
        if (!$refund->setConfig($setting['setting'])) {
            return $this->setError($refund->getError());
        }

        // 设置支付流水号
        $refund->setOutTradeNo($refundLog['payment_no']);

        // 设置退款流水号
        $refund->setRefundNo($refundLog['refund_no']);

        // 返回请求结果
        $result = $refund->refundFastpayQueryRequest();

        return false === $result ? $this->setError($refund->getError()) : $result;
    }
}