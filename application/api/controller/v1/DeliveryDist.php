<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    配送轨迹控制器
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/4/27
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class DeliveryDist extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 获取配送回调URL接口
            'get.delivery.dist.callback'   => ['getDistCallback', 'app\common\service\DeliveryDist'],
            // 订阅一条配送轨迹
            'subscribe.delivery.dist.item' => ['subscribeDistItem'],
            // 接收推送过来的配送数据
            'put.delivery.dist.data'       => ['putDistData'],
            // 获取配送信息
            'get.delivery.dist.item'       => ['getDistItem'],
            // 获取配送信息列表
            'get.delivery.dist.list'       => ['getDistList'],
        ];
    }
}