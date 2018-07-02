<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    优惠劵发放服务层
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/1/26
 */

namespace app\common\service;

use app\common\model\GoodsCategory;
use app\common\model\User;

class CouponGive extends CareyShop
{
    /**
     * 验证优惠劵是否可用
     * @access public
     * @param  array $coupon        优惠劵数据
     * @param  array $goodsCategory 商品分类集合
     * @param  float $payAmount     订单支付金额
     * @return bool
     */
    public function checkCoupon($coupon, $goodsCategory, $payAmount)
    {
        if (bccomp($coupon['get_coupon']['quota'], $payAmount, 2) === 1) {
            return $this->setError('订单金额不足' . $coupon['get_coupon']['quota']);
        }

        if ($coupon['get_coupon']['is_invalid'] != 0) {
            return $this->setError('优惠劵已失效');
        }

        if (time() < strtotime($coupon['get_coupon']['use_begin_time'])) {
            return $this->setError('优惠劵使用时间未到');
        }

        if (time() > strtotime($coupon['get_coupon']['use_end_time'])) {
            return $this->setError('优惠劵使用时间已过期');
        }

        if (!empty($coupon['get_coupon']['level'])) {
            $userLevel = User::where(['user_id' => ['eq', get_client_id()]])->value('user_level_id');
            if (!in_array($userLevel, $coupon['get_coupon']['level'])) {
                return $this->setError('当前会员等级无法使用此优惠劵');
            }
        }

        // 达到条件可直接返回
        if (empty($coupon['get_coupon']['category']) && empty($coupon['get_coupon']['exclude_category'])) {
            return true;
        }

        if (!empty($coupon['get_coupon']['category'])) {
            $categoryList = GoodsCategory::getCategorySon(['goods_category_id' => $coupon['get_coupon']['category']]);
            $categoryList = array_column($categoryList, 'goods_category_id');
        }

        if (!empty($coupon['get_coupon']['exclude_category'])) {
            $excludeList = GoodsCategory::getCategorySon(['goods_category_id' => $coupon['get_coupon']['exclude_category']]);
            $excludeList = array_column($excludeList, 'goods_category_id');
        }

        foreach ($goodsCategory as $value) {
            if (isset($categoryList) && !in_array($value, $categoryList)) {
                return $this->setError('当前优惠劵只能在指定商品分类中使用');
            }

            if (isset($excludeList) && in_array($value, $excludeList)) {
                return $this->setError('当前优惠劵不能在限制商品分类中使用');
            }
        }

        return true;
    }
}