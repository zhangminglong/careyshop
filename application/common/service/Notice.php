<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    通知系统服务层
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/1/26
 */

namespace app\common\service;

use think\Config;

class Notice extends CareyShop
{
    /**
     * 获取通知系统列表
     * @access public
     * @return array|false
     */
    public static function getNoticeList()
    {
        $result = Config::get(null, 'notice');
        foreach ($result as $key => $value) {
            if (!empty($value['value'])) {
                $result[$key]['value'] = json_decode($value['value'], true);
            }
        }

        return $result;
    }
}