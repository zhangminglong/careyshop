<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    售后服务控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/10/10
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class OrderService extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 获取订单商品可申请的售后服务
            'get.order.service.goods'    => ['getOrderServiceGoods'],
            // 客服对售后服务单添加备注(顾客不可见)
            'set.order.service.remark'   => ['setOrderServiceRemark'],
            // 获取一个售后服务单
            'get.order.service.item'     => ['getOrderServiceItem'],
            // 获取售后服务单列表
            'get.order.service.list'     => ['getOrderServiceList'],
            // 添加一个维修售后服务单
            'add.order.service.maintain' => ['addOrderServiceMaintain'],
            // 添加一个换货售后服务单
            'add.order.service.exchange' => ['addOrderServiceExchange'],
            // 添加一个仅退款售后服务单
            'add.order.service.refund'   => ['addOrderServiceRefund'],
            // 添加一个退款退货售后服务单
            'add.order.service.refunds'  => ['addOrderServiceRefunds'],
            // 添加一条售后服务单留言
            'add.order.service.message'  => ['addOrderServiceMessage'],
            // 同意(接收)一个售后服务单
            'set.order.service.agree'    => ['setOrderServiceAgree'],
            // 拒绝一个售后服务单
            'set.order.service.refused'  => ['setOrderServiceRefused'],
            // 设置退换货、维修商品是否寄还商家
            'set.order.service.sendback' => ['setOrderServiceSendback'],
            // 买家上报换货、维修后的快递单号,并填写商家寄回时需要的信息
            'set.order.service.buyer'    => ['setOrderServiceBuyer'],
            // 买家上报退款退货后的快递单号
            'set.order.service.logistic' => ['setOrderServiceLogistic'],
            // 设置一个售后服务单状态为"售后中"
            'set.order.service.after'    => ['setOrderServiceAfter'],
            // 撤销一个售后服务单
            'set.order.service.cancel'   => ['setOrderServiceCancel'],
            // 完成一个售后服务单
            'set.order.service.complete' => ['setOrderServiceComplete'],
        ];
    }
}