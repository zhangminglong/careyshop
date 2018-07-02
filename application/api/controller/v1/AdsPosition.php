<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    广告位控制器
 *
 * @author      zxm <252404501@qq.com>
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
            // 添加一个广告位
            'add.ads.position.item'    => ['addPositionItem'],
            // 编辑一个广告位
            'set.ads.position.item'    => ['setPositionItem'],
            // 批量删除广告位
            'del.ads.position.list'    => ['delPositionList'],
            // 验证广告位编号是否唯一
            'unique.ads.position.code' => ['uniquePositionCode'],
            // 批量设置广告位状态
            'set.ads.position.status'  => ['setPositionStatus'],
            // 获取一个广告位
            'get.ads.position.item'    => ['getPositionItem'],
            // 获取广告位列表
            'get.ads.position.list'    => ['getPositionList'],
            // 获取广告位选择列表
            'get.ads.position.select'  => ['getPositionSelect'],
            // 根据广告位编码获取广告列表
            'get.ads.position.code'    => ['getPositionCode'],
        ];
    }
}