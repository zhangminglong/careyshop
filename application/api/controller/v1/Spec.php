<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品规格控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/10
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Spec extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个商品规格
            'add.goods.spec.item'  => ['addSpecItem'],
            // 编辑一个商品规格
            'set.goods.spec.item'  => ['setSpecItem'],
            // 获取一条商品规格
            'get.goods.spec.item'  => ['getSpecItem'],
            // 获取商品规格列表
            'get.goods.spec.list'  => ['getSpecList'],
            // 批量删除商品规格
            'del.goods.spec.list'  => ['delSpecList'],
            // 批量设置商品规格检索
            'set.goods.spec.index' => ['setSpecIndex'],
            // 设置商品规格排序
            'set.goods.spec.sort'  => ['setSpecSort'],
        ];
    }
}