<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    区域控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/27
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Region extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个区域
            'add.region.item'     => ['addRegionItem'],
            // 编辑一个区域
            'set.region.item'     => ['setRegionItem'],
            // 批量删除区域
            'del.region.list'     => ['delRegionList'],
            // 获取指定区域
            'get.region.item'     => ['getRegionItem'],
            // 获取指定Id下的子节点(不包含本身)
            'get.region.list'     => ['getRegionList'],
            // 获取指定Id下的所有子节点(包含本身)
            'get.region.son.list' => ['getRegionSonList'],
            // 设置区域排序
            'set.region.sort'     => ['setRegionSort'],
            // 根据区域编号获取区域名称
            'get.region.name'     => ['getRegionName'],
        ];
    }
}