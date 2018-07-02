<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    配送区域控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/28
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class DeliveryArea extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个配送区域
            'add.delivery.area.item' => ['addAreaItem'],
            // 编辑一个配送区域
            'set.delivery.area.item' => ['setAreaItem'],
            // 批量删除配送区域
            'del.delivery.area.list' => ['delAreaList'],
            // 获取一个配送区域
            'get.delivery.area.item' => ['getAreaItem'],
            // 获取配送区域列表
            'get.delivery.area.list' => ['getAreaList'],
        ];
    }
}