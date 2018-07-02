<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    规则验证服务层
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/3/30
 */

namespace app\common\service;

use app\common\model\ActionLog;
use app\common\model\AuthRule;
use app\common\model\Menu;

class Auth extends CareyShop
{
    /**
     * 菜单权限
     * @var array
     */
    private $menuAuth = [];

    /**
     * 日志权限
     * @var array
     */
    private $logAuth = [];

    /**
     * 菜单数据
     * @var array
     */
    private $menuList = [];

    /**
     * 构造函数
     * @access public
     * @param  string $module  所属模块
     * @param  int    $groupId 用户组编号
     */
    public function __construct($module, $groupId)
    {
        // 获取权限数据
        $rule = AuthRule::getMenuAuthRule($module, $groupId);
        if ($rule && is_array($rule)) {
            $this->menuAuth = $rule['menu_auth'];
            $this->logAuth = $rule['log_auth'];
        }

        // 获取菜单数据
        $menu = Menu::getUrlMenuList($module);
        if ($menu && is_array($menu)) {
            $this->menuList = $menu;
        }
    }

    /**
     * 验证权限
     * @access public
     * @param  string $url Url(模块/控制器/操作名)
     * @return bool
     */
    public function check($url)
    {
        // 超级管理员直接返回
        if (AUTH_SUPER_ADMINISTRATOR == get_client_group()) {
            return true;
        }

        // 转为小写
        $url = mb_strtolower($url, 'utf-8');

        // 核心数据是否存在
        if (empty($this->menuAuth) || empty($this->menuList)) {
            return false;
        }

        if (!isset($this->menuList[$url])) {
            return false;
        }

        $menuId = $this->menuList[$url]['menu_id'];
        if (in_array($menuId, (array)$this->menuAuth)) {
            return true;
        }

        return false;
    }

    /**
     * 记录日志
     * @access public
     * @param  string $url     Url(模块/控制器/操作名)
     * @param  object $request 请求对象
     * @param  array  $result  处理结果
     * @param  string $class   手动输入当前类
     * @param  string  $error   错误信息
     * @return void
     */
    public function saveLog($url, &$request, $result, $class, $error = '')
    {
        // 转为小写
        $url = mb_strtolower($url, 'utf-8');

        if (!isset($this->menuList[$url])) {
            return;
        }

        $menuId = $this->menuList[$url]['menu_id'];
        if (!in_array($menuId, (array)$this->logAuth)) {
            return;
        }

        $data = [
            'client_type' => get_client_type(),
            'user_id'     => get_client_id(),
            'username'    => get_client_name(),
            'path'        => $request->path(),
            'module'      => $class,
            'params'      => $request->param(),
            'result'      => false === $result ? $error : $result,
            'ip'          => $request->ip(),
            'status'      => false === $result ? 1 : 0,
        ];

        ActionLog::create($data);
    }
}