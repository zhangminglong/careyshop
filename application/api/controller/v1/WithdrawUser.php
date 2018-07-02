<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    提现账号控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/6/20
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class WithdrawUser extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个提现账号
            'add.withdraw.user.item'   => ['addWithdrawUserItem'],
            // 编辑一个提现账号
            'set.withdraw.user.item'   => ['setWithdrawUserItem'],
            // 批量删除提现账号
            'del.withdraw.user.list'   => ['delWithdrawUserList'],
            // 获取指定账号的一个提现账号
            'get.withdraw.user.item'   => ['getWithdrawUserItem'],
            // 获取指定账号的提现账号列表
            'get.withdraw.user.list'   => ['getWithdrawUserList'],
            // 检测是否超出最大添加数量
            'is.withdraw.user.maximum' => ['isWithdrawUserMaximum'],
        ];
    }
}