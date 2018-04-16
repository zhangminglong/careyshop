<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    广告位置控制器
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/3/29
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class AdsPosition extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个广告位置
            'add.ads.position.item'    => ['addPositionItem'],
            // 编辑一个广告位置
            'set.ads.position.item'    => ['setPositionItem'],
            // 批量删除广告位置
            'del.ads.position.list'    => ['delPositionList'],
            // 验证广告位置名称是否唯一
            'unique.ads.position.name' => ['uniquePositionName'],
            // 获取一个广告位置
            'get.ads.position.item'    => ['getPositionItem'],
            // 获取广告位置列表
            'get.ads.position.list'    => ['getPositionList'],
            // 批量设置广告位置状态
            'set.ads.position.status'  => ['setPositionStatus'],
        ];
    }
}