<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    账号资金控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/6/22
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class UserMoney extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 获取指定账号资金信息
            'get.user.money.info' => ['getUserMoneyInfo'],
        ];
    }
}