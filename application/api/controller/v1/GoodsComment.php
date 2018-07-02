<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品评价控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/11
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class GoodsComment extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一条新的商品评价
            'add.goods.comment.item'   => ['addCommentItem'],
            // 追加一条商品评价
            'add.goods.addition.item'  => ['addAdditionItem'],
            // 回复或追评一条商品评价
            'reply.goods.comment.item' => ['replyCommentItem'],
            // 删除任意一条商品评价(主评,主回,追评,追回)
            'del.goods.comment.item'   => ['delCommentItem'],
            // 点赞任意一条商品评价(主评,主回,追评,追回)
            'add.goods.praise.item'    => ['addPraiseItem'],
            // 获取一个商品评价得分
            'get.goods.comment.score'  => ['getCommentScore'],
            // 批量设置是否前台显示
            'set.goods.comment.show'   => ['setCommentShow'],
            // 批量设置评价是否置顶
            'set.goods.comment.top'    => ['setCommentTop'],
            // 批量设置评价是否已读
            'set.goods.comment.status' => ['setCommentStatus'],
            // 获取一个商品"全部"、"晒图"、"追评"、"好评"、"中评"、差评"的数量
            'get.goods.comment.count'  => ['getCommentCount'],
            // 获取某个评价的明细
            'get.goods.comment.item'   => ['getCommentItem'],
            // 获取商品评价列表
            'get.goods.comment.list'   => ['getCommentList'],
        ];
    }
}