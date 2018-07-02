<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    配送方式控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/27
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Delivery extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个配送方式
            'add.delivery.item'    => ['addDeliveryItem'],
            // 编辑一个配送方式
            'set.delivery.item'    => ['setDeliveryItem'],
            // 批量删除配送方式
            'del.delivery.list'    => ['delDeliveryList'],
            // 获取一个配送方式
            'get.delivery.item'    => ['getDeliveryItems'],
            // 获取配送方式列表
            'get.delivery.list'    => ['getDeliveryList'],
            // 获取配送方式选择列表
            'get.delivery.select'  => ['getDeliverySelect'],
            // 根据配送方式获取运费
            'get.delivery.freight' => ['getDeliveryFreight'],
            // 批量设置配送方式状态
            'set.delivery.status'  => ['setDeliveryStatus'],
            // 验证快递公司编号是否已存在
            'unique.delivery.item' => ['uniqueDeliveryItem'],
            // 设置配送方式排序
            'set.delivery.sort'    => ['setDeliverySort'],
        ];
    }
}