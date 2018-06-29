<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商城后台控制器
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/03/23
 */

namespace app\admin\controller;

use think\Db;

class Index
{
    public $menuList = [];

    public function index()
    {
        // return '欢迎使用CareyShop商城框架系统 - Admin';
        $menu = [
            'admin'   => [
            ],
            'user'    => [
            ],
            'visitor' => [
                'check.user.username' => 0,
                'check.user.mobile'   => 0,
            ],
        ];

        foreach ($menu as $key => $value) {
            $list = $this->getMenuList($menu[$key]);
            echo $key . '：' . implode(',', $list['list']);
            echo '</br>';
            echo $key . 'log：' . implode(',', $list['log']);
            echo '</br>';
        }
    }

    private function getMenuList($data)
    {
        if (empty($this->menuList)) {
            $list = Db::name('menu')->column('url', 'menu_id');
            $this->menuList = array_unique($list);
        }

        $idList = [];
        $logList = [];

        foreach ($data as $key => $value) {
            foreach ($this->menuList as $k => $v) {
                if (stripos($v, $key) === false)
                    continue;

                $idList[] = $k;

                if ($value !== 0) {
                    $logList[] = $k;
                }

                break;
            }
        }

        return ['list' => $idList, 'log' => $logList];
    }
}