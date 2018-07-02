<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品模型控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/7
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class GoodsType extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个商品模型
            'add.goods.type.item'    => ['addTypeItem'],
            // 编辑一个商品模型
            'set.goods.type.item'    => ['setTypeItem'],
            // 批量删除商品模型
            'del.goods.type.list'    => ['delTypeList'],
            // 查询商品模型名称是否已存在
            'unique.goods.type.name' => ['uniqueTypeName'],
            // 获取一个商品模型
            'get.goods.type.item'    => ['getTypeItem'],
            // 获取商品模型列表
            'get.goods.type.list'    => ['getTypeList'],
            // 获取商品模型选择列表
            'get.goods.type.select'  => ['getTypeSelect'],
        ];
    }
}