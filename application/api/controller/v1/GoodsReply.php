<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品评价回复控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/11
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class GoodsReply extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 对商品评价添加一个回复(管理组不参与评价回复)
            'add.goods.reply.item' => ['addReplyItem'],
            // 批量删除商品评价的回复
            'del.goods.reply.list' => ['delReplyList'],
            // 获取商品评价回复列表
            'get.goods.reply.list' => ['getReplyList'],
        ];
    }
}