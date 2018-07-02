<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    友情链接控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/27
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class FriendLink extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个友情链接
            'add.friendlink.item'   => ['addFriendLinkItem'],
            // 编辑一个友情链接
            'set.friendlink.item'   => ['setFriendLinkItem'],
            // 获取一个友情链接
            'get.friendlink.item'   => ['getFriendLinkItem'],
            // 获取友情链接列表
            'get.friendlink.list'   => ['getFriendLinkList'],
            // 批量删除友情链接
            'del.friendlink.list'   => ['delFriendLinkList'],
            // 批量设置友情链接状态
            'set.friendlink.status' => ['setFriendLinkStatus'],
            // 设置友情链接排序
            'set.friendlink.sort'   => ['setFriendLinkSort'],
        ];
    }
}