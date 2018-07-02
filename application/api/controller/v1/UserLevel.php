<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    账号等级控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/30
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class UserLevel extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 获取一个账号等级
            'get.user.level.item' => ['getLevelItem'],
            // 获取账号等级列表
            'get.user.level.list' => ['getLevelList'],
            // 添加一个账号等级
            'add.user.level.item' => ['addLevelItem'],
            // 编辑一个账号等级
            'set.user.level.item' => ['setLevelItem'],
            // 批量删除账号等级
            'del.user.level.list' => ['delLevelList'],
        ];
    }
}