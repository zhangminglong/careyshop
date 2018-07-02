<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    支付日志控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/6/28
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class PaymentLog extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 关闭一笔充值记录
            'close.payment.log.item' => ['closePaymentLogItem'],
            // 获取一笔充值记录
            'get.payment.log.item'   => ['getPaymentLogItem'],
            // 获取充值记录列表
            'get.payment.log.list'   => ['getPaymentLogList'],
        ];
    }
}