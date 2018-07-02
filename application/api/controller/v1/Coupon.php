<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    优惠劵控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/5/18
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Coupon extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一张优惠劵
            'add.coupon.item'    => ['addCouponItem'],
            // 编辑一张优惠劵
            'set.coupon.item'    => ['setCouponItem'],
            // 获取一张优惠劵
            'get.coupon.item'    => ['getCouponItem'],
            // 获取优惠劵列表
            'get.coupon.list'    => ['getCouponList'],
            // 批量删除优惠劵
            'del.coupon.list'    => ['delCouponList'],
            // 批量设置优惠劵状态
            'set.coupon.status'  => ['setCouponStatus'],
            // 批量设置优惠劵是否失效
            'set.coupon.invalid' => ['setCouponInvalid'],
            // 获取当前可领取的优惠劵列表
            'get.coupon.active'  => ['getCouponActive'],
        ];
    }
}