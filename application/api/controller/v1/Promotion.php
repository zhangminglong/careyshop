<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    订单促销控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/5/31
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Promotion extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个订单促销
            'add.promotion.item'   => ['addPromotionItem'],
            // 编辑一个订单促销
            'set.promotion.item'   => ['setPromotionItem'],
            // 获取一个订单促销
            'get.promotion.item'   => ['getPromotionItem'],
            // 批量设置订单促销状态
            'set.promotion.status' => ['setPromotionStatus'],
            // 批量删除订单促销
            'del.promotion.list'   => ['delPromotionList'],
            // 获取订单促销列表
            'get.promotion.list'   => ['getPromotionList'],
            // 获取正在进行的促销列表
            'get.promotion.active' => ['getPromotionActive'],
        ];
    }
}