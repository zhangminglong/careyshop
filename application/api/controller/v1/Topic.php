<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    专题控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/28
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Topic extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个专题
            'add.topic.item'   => ['addTopicItem'],
            // 编辑一个专题
            'set.topic.item'   => ['setTopicItem'],
            // 批量删除专题
            'del.topic.list'   => ['delTopicList'],
            // 获取一个专题
            'get.topic.item'   => ['getTopicItem'],
            // 获取专题列表
            'get.topic.list'   => ['getTopicList'],
            // 批量设置专题是否显示
            'set.topic.status' => ['setTopicStatus'],
        ];
    }
}