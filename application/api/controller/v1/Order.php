<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    订单管理控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/7/29
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Order extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 获取订单确认或提交订单
            'confirm.order.list'      => ['confirmOrderList'],
            // 调整订单应付金额
            'change.price.order.item' => ['changePriceOrderItem'],
            // 添加或编辑卖家备注
            'remark.order.item'       => ['remarkOrderItem'],
            // 编辑一个订单
            'set.order.item'          => ['setOrderItem'],
            // 将订单放入回收站、还原或删除
            'recycle.order.item'      => ['recycleOrderItem'],
            // 获取一个订单
            'get.order.item'          => ['getOrderItem'],
            // 获取订单列表
            'get.order.list'          => ['getOrderList'],
            // 获取订单各个状态合计数
            'get.order.status.total'  => ['getOrderStatusTotal'],
            // 再次购买与订单相同的商品
            'buyagain.order.goods'    => ['buyagainOrderGoods'],
            // 获取可评价或可追评的订单商品列表
            'get.order.goods.comment' => ['getOrderGoodsComment'],
            // 未付款订单超时自动取消
            'timeout.order.cancel'    => ['timeoutOrderCancel'],
            // 未确认收货订单超时自动完成
            'timeout.order.complete'  => ['timeoutOrderComplete'],
            // 取消一个订单
            'cancel.order.item'       => ['cancelOrderItem'],
            // 订单设为配货状态
            'picking.order.item'      => ['pickingOrderItem'],
            // 订单设为发货状态
            'delivery.order.item'     => ['deliveryOrderItem'],
            // 订单确认收货
            'complete.order.item'     => ['completeOrderItem'],
            // 获取一个订单商品明细
            'get.order.goods.item'    => ['getOrderGoodsItem', 'app\common\model\OrderGoods'],
        ];
    }
}