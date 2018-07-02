<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    收藏夹控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/7/15
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Collect extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个商品收藏
            'add.collect.item'   => ['addCollectItem'],
            // 批量删除商品收藏
            'del.collect.list'   => ['delCollectList'],
            // 清空商品收藏夹
            'clear.collect.list' => ['clearCollectList'],
            // 设置收藏商品是否置顶
            'set.collect.top'    => ['setCollectTop'],
            // 获取商品收藏列表
            'get.collect.list'   => ['getCollectList'],
            // 获取商品收藏数量
            'get.collect.count'  => ['getCollectCount'],
        ];
    }
}