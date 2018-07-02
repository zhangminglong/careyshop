<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    系统配置控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/2/12
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Setting extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 获取某个模块的设置
            'get.setting.list'       => ['getSettingList'],
            // 设置配送轨迹
            'set.delivery.dist.list' => ['setDeliveryDistList'],
            // 设置支付完成提示页
            'set.payment.list'       => ['setPaymentList'],
            // 设置配送优惠
            'set.delivery.list'      => ['setDeliveryList'],
            // 设置购物系统
            'set.shopping.list'      => ['setShoppingList'],
            // 设置售后服务
            'set.service.list'       => ['setServiceList'],
            // 设置系统配置
            'set.system.list'        => ['setSystemList'],
            // 设置上传配置
            'set.upload.list'        => ['setUploadList'],
        ];
    }
}