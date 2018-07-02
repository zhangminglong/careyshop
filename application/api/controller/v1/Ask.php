<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    问答控制器
 *
 * @author      zxm <252404501@qq.com>
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
            // 添加一个提问
            'add.ask.item'      => ['addAskItem'],
            // 删除一条记录
            'del.ask.item'      => ['delAskItem'],
            // 回答一个提问
            'reply.ask.item'    => ['replyAskItem'],
            // 在提问上继续提问
            'continue.ask.item' => ['continueAskItem'],
            // 获取一个问答明细
            'get.ask.item'      => ['getAskItem'],
            // 获取问答主题列表
            'get.ask.list'      => ['getAskList'],
        ];
    }
}