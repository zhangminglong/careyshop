<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    导航控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/5/7
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Navigation extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个导航
            'add.navigation.item'   => ['addNavigationItem'],
            // 编辑一个导航
            'set.navigation.item'   => ['setNavigationItem'],
            // 批量删除导航
            'del.navigation.list'   => ['delNavigationList'],
            // 获取一个导航
            'get.navigation.item'   => ['getNavigationItem'],
            // 获取导航列表
            'get.navigation.list'   => ['getNavigationList'],
            // 批量设置是否新开窗口
            'set.navigation.target' => ['setNavigationTarget'],
            // 批量设置是否启用
            'set.navigation.status' => ['setNavigationStatus'],
            // 设置导航排序
            'set.navigation.sort'   => ['setNavigationSort'],
        ];
    }
}