<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    通知系统控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/7/17
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Notice extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 获取一个通知系统
            'get.notice.item'   => ['getNoticeItem'],
            // 获取通知系统列表
            'get.notice.list'   => ['getNoticeList', 'app\common\service\Notice'],
            // 批量设置通知系统是否启用
            'set.notice.status' => ['setNoticeStatus'],
            // 设置一个通知系统
            'set.notice.item'   => ['setNoticeItem'],
        ];
    }
}