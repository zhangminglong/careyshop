<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    订单退款控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/9/25
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class OrderRefund extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 查询一笔退款信息
            'query.refund.item' => ['queryRefundItem'],
            // 获取退款记录列表
            'get.refund.list'   => ['getRefundList'],
        ];
    }
}