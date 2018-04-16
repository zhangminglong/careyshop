<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    问答控制器
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/3/30
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Ask extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个新的咨询
            'add.ask.item'      => ['addAskItem'],
            // 删除一条记录
            'del.ask.item'      => ['delAskItem'],
            // 回复一个咨询
            'reply.ask.item'    => ['replyAskItem'],
            // 在主题上继续提交咨询
            'continue.ask.item' => ['continueAskItem'],
            // 根据主题获取一个问答明细
            'get.ask.item'      => ['getAskItem'],
            // 获取咨询主题列表
            'get.ask.list'      => ['getAskList'],
        ];
    }
}