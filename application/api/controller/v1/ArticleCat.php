<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    文章分类控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/30
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class ArticleCat extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个文章分类
            'add.article.cat.item' => ['addArticleCatItem'],
            // 编辑一个文章分类
            'set.article.cat.item' => ['setArticleCatItem'],
            // 批量删除文章分类
            'del.article.cat.list' => ['delArticleCatList'],
            // 获取一个文章分类
            'get.article.cat.item' => ['getArticleCatItem'],
            // 获取文章分类列表
            'get.article.cat.list' => ['getArticleCatList'],
            // 获取分类导航数据
            'get.article.cat.navi' => ['getArticleCatNavi'],
            // 设置文章分类排序
            'set.article.cat.sort' => ['setArticleCatSort'],
            // 批量设置是否导航
            'set.article.cat.navi' => ['setArticleCatNavi'],
        ];
    }

    /**
     * 获取文章分类列表
     * @access protected
     * @param  int  $articleCatId 文章分类Id
     * @param  bool $isLayer      是否返回本级分类
     * @param  int  $level        分类深度
     * @return array
     */
    protected function getArticleCatList($articleCatId = 0, $isLayer = false, $level = null)
    {
        $catData = $this->getParams();
        $validate = $this->validate($catData, 'ArticleCat.list');

        if (true !== $validate) {
            return $this->setError($validate);
        }

        !isset($catData['level']) ?: $level = $catData['level'];
        !isset($catData['article_cat_id']) ?: $articleCatId = $catData['article_cat_id'];
        empty($catData['is_layer']) ?: $isLayer = true;

        return self::$model->getArticleCatList($articleCatId, $isLayer, $level);
    }
}