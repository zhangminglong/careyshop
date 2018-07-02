<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    客服控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/28
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Support extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一名客服
            'add.support.item'   => ['addSupportItem'],
            // 编辑一名客服
            'set.support.item'   => ['setSupportItem'],
            // 批量删除客服
            'del.support.list'   => ['delSupportList'],
            // 获取一名客服
            'get.support.item'   => ['getSupportItem'],
            // 获取客服列表
            'get.support.list'   => ['getSupportList'],
            // 批量设置客服状态
            'set.support.status' => ['setSupportStatus'],
            // 设置客服排序
            'set.support.sort'   => ['setSupportSort'],
        ];
    }
}