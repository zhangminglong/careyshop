<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    优惠劵发放控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/5/20
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class CouponGive extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 向指定用户发放优惠劵
            'give.coupon.user'       => ['giveCouponUser'],
            // 生成线下优惠劵
            'give.coupon.live'       => ['giveCouponLive'],
            // 领取码领取优惠劵
            'give.coupon.code'       => ['giveCouponCode'],
            // 获取已领取优惠劵列表
            'get.coupon.give.list'   => ['getCouponGiveList'],
            // 批量删除已领取优惠劵
            'del.coupon.give.list'   => ['delCouponGiveList'],
            // 批量恢复已删优惠劵
            'rec.coupon.give.list'   => ['recCouponGiveList'],
            // 导出线下生成的优惠劵
            'get.coupon.give.export' => ['getCouponGiveExport'],
            // 根据商品Id列出可使用的优惠劵
            'get.coupon.give.select' => ['getCouponGiveSelect'],
            // 验证优惠劵是否可使用
            'get.coupon.give.check'  => ['getCouponGiveCheck'],
        ];
    }
}