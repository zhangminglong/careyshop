<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    通知系统模板控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/7/18
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class NoticeTpl extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 获取一个通知系统模板
            'get.notice.tpl.item'   => ['getNoticeTplItem'],
            // 获取通知系统模板列表
            'get.notice.tpl.list'   => ['getNoticeTplList'],
            // 编辑一个通知系统模板
            'set.notice.tpl.item'   => ['setNoticeTplItem'],
            // 批量设置通知系统模板是否启用
            'set.notice.tpl.status' => ['setNoticeTplStatus'],
        ];
    }
}