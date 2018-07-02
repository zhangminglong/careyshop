<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    用户组控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/3/29
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class AuthGroup extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个用户组
            'add.auth.group.item'   => ['addAuthGroupItem'],
            // 编辑一个用户组
            'set.auth.group.item'   => ['setAuthGroupItem'],
            // 获取一个用户组
            'get.auth.group.item'   => ['getAuthGroupItem'],
            // 删除一个用户组
            'del.auth.group.item'   => ['delAuthGroupItem'],
            // 获取用户组列表
            'get.auth.group.list'   => ['getAuthGroupList'],
            // 批量设置用户组状态
            'set.auth.group.status' => ['setAuthGroupStatus'],
            // 设置用户组排序
            'set.auth.group.sort'   => ['setAuthGroupSort'],
        ];
    }
}