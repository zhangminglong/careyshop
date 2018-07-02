<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    文章管理控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/30
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Article extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一篇文章
            'add.article.item'   => ['addArticleItem'],
            // 编辑一篇文章
            'set.article.item'   => ['setArticleItem'],
            // 批量删除文章
            'del.article.list'   => ['delArticleList'],
            // 获取一篇文章
            'get.article.item'   => ['getArticleItem'],
            // 获取文章列表
            'get.article.list'   => ['getArticleList'],
            // 批量设置文章置顶
            'set.article.top'    => ['setArticleTop'],
            // 批量设置文章是否显示
            'set.article.status' => ['setArticleStatus'],
        ];
    }
}