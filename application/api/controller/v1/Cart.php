<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    购物车控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/7/12
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Cart extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 验证是否允许添加或编辑购物车
            'check.cart.goods'   => ['checkCartGoods'],
            // 添加或编辑购物车商品
            'set.cart.item'      => ['setCartItem'],
            // 批量添加商品到购物车
            'add.cart.list'      => ['addCartList'],
            // 获取购物车列表
            'get.cart.list'      => ['getCartList'],
            // 获取购物车商品数量
            'get.cart.count'     => ['getCartCount'],
            // 设置购物车商品是否选中
            'set.cart.select'    => ['setCartSelect'],
            // 批量删除购物车商品
            'del.cart.list'      => ['delCartList'],
            // 清空购物车
            'clear.cart.list'    => ['clearCartList'],
            // 请求商品立即购买
            'create.cart.buynow' => ['createCartBuynow'],
        ];
    }
}