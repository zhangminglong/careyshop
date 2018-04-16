<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    菜单管理模型
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2018/3/9
 */

namespace app\common\model;

use think\Cache;
use think\helper\Str;

class Menu extends CareyShop
{
    /**
     * 菜单权限
     * @var array
     */
    private static $menuAuth = [];

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'menu_id',
        'module',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'menu_id'   => 'integer',
        'parent_id' => 'integer',
        'type'      => 'integer',
        'is_navi'   => 'integer',
        'sort'      => 'integer',
        'status'    => 'integer',
    ];

    /**
     * URL驼峰转下划线修改器
     * @access protected
     * @param  string $value 值
     * @return string
     */
    private function strToSnake($value)
    {
        if (empty($value) || !is_string($value)) {
            return $value;
        }

        $word = explode('/', $value);
        $word = array_map(['think\\helper\\Str', 'snake'], $word);

        return implode('/', $word);
    }

    /**
     * 添加一个菜单
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function addMenuItem($data)
    {
        if (!$this->validateData($data, 'Menu')) {
            return false;
        }

        // 避免无关字段,并且转换格式
        unset($data['menu_id']);
        empty($data['url']) ?: $data['url'] = $this->strToSnake($data['url']);

        if (!empty($data['url']) && 0 == $data['type']) {
            $map['module'] = ['eq', $data['module']];
            $map['type'] = ['eq', 0];
            $map['url'] = ['eq', $data['url']];

            if (self::checkUnique($map)) {
                return $this->setError('Url已存在');
            }
        }

        if (false !== $this->allowField(true)->save($data)) {
            Cache::clear('CommonAuth');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 获取一个菜单
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function getMenuItem($data)
    {
        if (!$this->validateData($data, 'Menu.item')) {
            return false;
        }

        $result = self::get($data['menu_id']);
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 编辑一个菜单
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function setMenuItem($data)
    {
        if (!$this->validateSetData($data, 'Menu.set')) {
            return false;
        }

        $result = self::get($data['menu_id']);
        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        // 检测编辑后是否存在重复URL
        empty($data['url']) ?: $data['url'] = $this->strToSnake($data['url']);
        isset($data['type']) ?: $data['type'] = $result->getAttr('type');
        isset($data['url']) ?: $data['url'] = $result->getAttr('url');

        if (!empty($data['url']) && 0 == $data['type']) {
            $map['menu_id'] = ['neq', $data['menu_id']];
            $map['module'] = ['eq', $result->getAttr('module')];
            $map['type'] = ['eq', 0];
            $map['url'] = ['eq', $data['url']];

            if (self::checkUnique($map)) {
                return $this->setError('Url已存在');
            }
        }

        // 父菜单不能设置成自身或所属的子菜单
        if (isset($data['parent_id'])) {
            if ($data['parent_id'] == $data['menu_id']) {
                return $this->setError('上级菜单不能设为自身');
            }

            $menuList = self::getMenuListData($result->getAttr('module'), $data['menu_id']);
            if (false === $menuList) {
                return false;
            }

            foreach ($menuList as $value) {
                if ($data['parent_id'] == $value['menu_id']) {
                    return $this->setError('上级菜单不能设为自身的子菜单');
                }
            }
        }

        if (false !== $result->allowField(true)->save($data)) {
            Cache::clear('CommonAuth');
            return $result->toArray();
        }

        return false;
    }

    /**
     * 根据条件获取菜单列表数据
     * @access public static
     * @param  string $module  所属模块
     * @param  int    $menuId  菜单Id
     * @param  bool   $isLayer 是否返回本级菜单
     * @param  int    $level   菜单深度
     * @param  array  $filter  过滤'is_navi'与'status'
     * @return array/false
     */
    public static function getMenuListData($module, $menuId = 0, $isLayer = false, $level = null, $filter = null)
    {
        // 缓存名称
        $treeCache = 'MenuTree:' . $module;

        // 搜索条件
        $map['m.module'] = ['eq', $module];

        // 过滤'is_navi'与'status'
        foreach ((array)$filter as $key => $value) {
            if ($key != 'is_navi' && $key != 'status') {
                continue;
            }

            $map['m.' . $key] = $value;
            $treeCache .= $key . $value;
        }

        $result = self::all(function ($query) use ($map) {
            $query
                ->cache(true, null, 'CommonAuth')
                ->alias('m')
                ->field('m.*,count(s.menu_id) children_total')
                ->join('menu s', 's.parent_id = m.menu_id', 'left')
                ->where($map)
                ->group('m.menu_id')
                ->order('m.parent_id,m.sort,m.menu_id');
        });

        if (false === $result) {
            Cache::clear('CommonAuth');
            return false;
        }

        $treeCache .= sprintf('id%dlevel%dis_layer%d', $menuId, is_null($level) ? -1 : $level, $isLayer);
        empty(self::$menuAuth) ?: $treeCache .= 'auth' . implode(',', self::$menuAuth);

        if (Cache::has($treeCache)) {
            return Cache::get($treeCache);
        }

        $tree = self::setMenuTree((int)$menuId, $result, $level, $isLayer);
        Cache::tag('CommonAuth')->set($treeCache, $tree);

        return $tree;
    }

    /**
     * 过滤和排序所有菜单
     * @access private
     * @param  int    $parentId   上级菜单Id
     * @param  object $list       原始模型对象
     * @param  int    $limitLevel 显示多少级深度 null:全部
     * @param  bool   $isLayer    是否返回本级菜单
     * @return array
     */
    private static function setMenuTree($parentId, &$list, $limitLevel = null, $isLayer = false, $level = 0)
    {
        static $tree = [];
        $parentId != 0 ?: $isLayer = false; // 返回全部菜单不需要本级

        foreach ($list as $key => $value) {
            // 获取菜单主Id
            $menuId = $value->getAttr('menu_id');
            if ($value->getAttr('parent_id') !== $parentId && $menuId !== $parentId) {
                continue;
            }

            // 是否返回本级菜单
            if ($menuId === $parentId && !$isLayer) {
                continue;
            }

            // 存在权限列表则需要检测
            if (!empty(self::$menuAuth) && !in_array($menuId, self::$menuAuth)) {
                continue;
            }

            // 限制菜单显示深度
            if (!is_null($limitLevel) && $level > $limitLevel) {
                break;
            }

            $value->setAttr('level', $level);
            $tree[] = $value->toArray();

            // 需要返回本级菜单时保留列表数据,否则引起树的重复
            if (true == $isLayer) {
                $isLayer = false;
                continue;
            }

            // 删除已使用数据,减少查询次数
            unset($list[$key]);

            if ($value->getAttr('children_total') > 0) {
                self::setMenuTree($menuId, $list, $limitLevel, $isLayer, $level + 1);
            }
        }

        return $tree;
    }

    /**
     * 删除一个菜单(影响下级子菜单)
     * @access public
     * @param  array $data 外部数据
     * @return false/array
     */
    public function delMenuItem($data)
    {
        if (!$this->validateData($data, 'Menu.del')) {
            return false;
        }

        $result = self::get($data['menu_id']);
        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        $menuList = self::getMenuListData($result->getAttr('module'), $data['menu_id'], true);
        if (false === $menuList) {
            return false;
        }

        $delList = array_column($menuList, 'menu_id');
        self::destroy($delList);
        Cache::clear('CommonAuth');

        return ['children' => $delList];
    }

    /**
     * 根据菜单Id生成导航数据
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function getMenuIdNavi($data)
    {
        if (!$this->validateData($data, 'Menu.navi')) {
            return false;
        }

        $isLayer = isset($data['is_layer']) ? (bool)$data['is_layer'] : true;
        $filter['is_navi'] = 1;
        $filter['status'] = 1;

        return self::getParentList(request()->module(), $data['menu_id'], $isLayer, $filter);
    }

    /**
     * 根据菜单url生成导航数据
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function getMenuUrlNavi($data)
    {
        if (!$this->validateData($data, 'Menu.url')) {
            return false;
        }

        $isLayer = isset($data['is_layer']) ? (bool)$data['is_layer'] : true;
        $filter['is_navi'] = 1;
        $filter['status'] = 1;
        $filter['url'] = $data['url'];

        return self::getParentList(request()->module(), 0, $isLayer, $filter);
    }

    /**
     * 批量设置是否属于导航菜单
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setMenuNavi($data)
    {
        if (!$this->validateData($data, 'Menu.nac')) {
            return false;
        }

        $map['menu_id'] = ['in', $data['menu_id']];
        if (false !== $this->save(['is_navi' => $data['is_navi']], $map)) {
            Cache::clear('CommonAuth');
            return true;
        }

        return false;
    }

    /**
     * 设置菜单排序
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setMenuSort($data)
    {
        if (!$this->validateData($data, 'Menu.sort')) {
            return false;
        }

        $map['menu_id'] = ['eq', $data['menu_id']];
        if (false !== $this->save(['sort' => $data['sort']], $map)) {
            Cache::clear('CommonAuth');
            return true;
        }

        return false;
    }

    /**
     * 根据编号获取上级菜单列表
     * @access public
     * @param  string $module  所属模块
     * @param  int    $menuId  菜单编号
     * @param  bool   $isLayer 是否返回本级
     * @param  array  $filter  过滤'is_navi'与'status'
     * @return array/false
     */
    public static function getParentList($module, $menuId, $isLayer = false, $filter = null)
    {
        // 搜索条件
        $map['module'] = ['eq', $module];

        // 过滤'is_navi'与'status'
        foreach ((array)$filter as $key => $value) {
            if ($key != 'is_navi' && $key != 'status') {
                continue;
            }

            $map[$key] = $value;
        }

        $list = self::cache(true, null, 'CommonAuth')->where($map)->column(null, 'menu_id');
        if ($list === false) {
            Cache::clear('CommonAuth');
            return false;
        }

        // 判断是否根据url获取
        if (isset($filter['url'])) {
            $url = array_column($list, 'menu_id', 'url');
            if (isset($url[$filter['url']])) {
                $menuId = $url[$filter['url']];
                unset($url);
            }
        }

        // 是否返回本级
        if (!$isLayer && isset($list[$menuId])) {
            $menuId = $list[$menuId]['parent_id'];
        }

        $result = [];
        while (true) {
            if (!isset($list[$menuId])) {
                break;
            }

            $result[] = $list[$menuId];

            if ($list[$menuId]['parent_id'] <= 0) {
                break;
            }

            $menuId = $list[$menuId]['parent_id'];
        }

        return array_reverse($result);
    }

    /**
     * 设置菜单状态(影响上下级菜单)
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function setMenuStatus($data)
    {
        if (!$this->validateData($data, 'Menu.status')) {
            return false;
        }

        $result = self::get($data['menu_id']);
        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        if ($result->getAttr('status') == $data['status']) {
            return $this->setError('状态未改变');
        }

        // 获取当前菜单模块名
        $module = $result->getAttr('module');

        // 如果是启用,则父菜单也需要启用
        $parent = [];
        if ($data['status'] == 1) {
            $parent = self::getParentList($module, $data['menu_id'], false);
            if (false === $parent) {
                return false;
            }
        }

        // 子菜单则无条件继承
        $children = self::getMenuListData($module, $data['menu_id'], true);
        if (false === $children) {
            return false;
        }

        $parent = array_column($parent, 'menu_id');
        $children = array_column($children, 'menu_id');

        $map['menu_id'] = ['in', array_merge($parent, $children)];
        $map['status'] = ['eq', $result->getAttr('status')];

        if (false !== $this->save(['status' => $data['status']], $map)) {
            Cache::clear('CommonAuth');
            return ['parent' => $parent, 'children' => $children, 'status' => (int)$data['status']];
        }

        return false;
    }

    /**
     * 获取菜单列表
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function getMenuList($data)
    {
        if (!$this->validateData($data, 'Menu.list')) {
            return false;
        }

        $menuId = isset($data['menu_id']) ? $data['menu_id'] : 0;
        $isLayer = isset($data['is_layer']) ? (bool)$data['is_layer'] : true;
        $level = isset($data['level']) ? $data['level'] : null;

        return self::getMenuListData($data['module'], $menuId, $isLayer, $level);
    }

    /**
     * 根据权限获取菜单列表
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function getMenuAuthList($data)
    {
        if (!$this->validateData($data, 'Menu.auth')) {
            return false;
        }

        // 获取对应的权限
        $ruleResult = AuthRule::getMenuAuthRule($data['module'], get_client_group());
        if (!$ruleResult) {
            return [];
        }

        // 生成菜单的条件
        $menuId = isset($data['menu_id']) ? $data['menu_id'] : 0;
        $isLayer = isset($data['is_layer']) ? (bool)$data['is_layer'] : true;
        $level = isset($data['level']) ? $data['level'] : null;

        $filter['is_navi'] = isset($data['is_navi']) ? $data['is_navi'] : 1;
        $filter['status'] = isset($data['status']) ? $data['status'] : 1;

        // 当规则表中存在菜单权限时进行赋值,让获取的函数进行过滤
        self::$menuAuth = $ruleResult['menu_auth'];
        $result = self::getMenuListData($data['module'], $menuId, $isLayer, $level, $filter);
        self::$menuAuth = [];

        return $result;
    }

    /**
     * 获取以URL为索引的菜单列表
     * @access public
     * @param  string $module 所属模块
     * @param  int    $status 菜单状态
     * @return array
     */
    public static function getUrlMenuList($module, $status = 1)
    {
        // 缓存名称
        $key = 'urlMenuList' . $module . $status;

        $map['module'] = ['eq', $module];
        $map['status'] = ['eq', $status];

        $result = self::cache($key, null, 'CommonAuth')->where($map)->column(null, 'url');
        if (!$result) {
            Cache::rm($key);
            return false;
        }

        return $result;
    }
}