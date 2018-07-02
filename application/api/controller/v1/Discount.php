<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品折扣控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/5/31
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Discount extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个商品折扣
            'add.discount.item'       => ['addDiscountItem'],
            // 编辑一个商品折扣
            'set.discount.item'       => ['setDiscountItem'],
            // 获取一个商品折扣
            'get.discount.item'       => ['getDiscountItem'],
            // 批量删除商品折扣
            'del.discount.list'       => ['delDiscountList'],
            // 批量设置商品折扣状态
            'set.discount.status'     => ['setDiscountStatus'],
            // 获取商品折扣列表
            'get.discount.list'       => ['getDiscountList'],
            // 根据商品编号获取折扣信息
            'get.discount.goods.info' => ['getDiscountGoodsInfo', 'app\common\model\DiscountGoods'],
        ];
    }
}