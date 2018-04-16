<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品属性控制器
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/4/7
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class GoodsAttribute extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个商品属性主体
            'add.goods.attrbute.body.item' => ['addAttributeBodyItem'],
            // 编辑一个商品属性主体
            'set.goods.attrbute.body.item' => ['setAttributeBodyItem'],
            // 获取一个商品属性主体
            'get.goods.attrbute.body.item' => ['getAttributeBodyItem'],
            // 获取商品属性主体列表
            'get.goods.attrbute.body.list' => ['getAttributeBodyList'],
            // 设置商品属性主体排序
            'set.goods.attrbute.body.sort' => ['setAttributeSort'],
            // 添加一个商品属性
            'add.goods.attrbute.item'      => ['addAttributeItem'],
            // 编辑一个商品属性
            'set.goods.attrbute.item'      => ['setAttributeItem'],
            // 批量删除商品属性
            'del.goods.attrbute.list'      => ['delAttributeList'],
            // 获取一个商品属性
            'get.goods.attrbute.item'      => ['getAttributeItem'],
            // 获取商品属性列表
            'get.goods.attrbute.list'      => ['getAttributeList'],
            // 批量设置商品属性检索
            'set.goods.attrbute.index'     => ['setAttributeIndex'],
            // 批量设置商品属性是否核心
            'set.goods.attrbute.important' => ['setAttributeImportant'],
            // 设置商品属性排序
            'set.goods.attrbute.sort'      => ['setAttributeSort'],
        ];
    }
}