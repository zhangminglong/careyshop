<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    我的足迹控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/7/15
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class History extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一条我的足迹
            'add.history.item'   => ['addHistoryItem'],
            // 批量删除我的足迹
            'del.history.list'   => ['delHistoryList'],
            // 清空我的足迹
            'clear.history.list' => ['clearHistoryList'],
            // 获取我的足迹数量
            'get.history.count'  => ['getHistoryCount'],
            // 获取我的足迹列表
            'get.history.list'   => ['getHistoryList'],
        ];
    }
}