<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    应用管理模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/24
 */

namespace app\common\model;

use think\Cache;

class App extends CareyShop
{
    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'is_delete',
    ];

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'app_id',
        'app_key',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'app_id'	=> 'integer',
        'app_key'	=> 'integer',
        'status'	=> 'integer',
        'is_delete'	=> 'integer',
    ];

    /**
     * 全局查询条件
     * @access protected
     * @param  object $query 模型
     * @return void
     */
    protected function base($query)
    {
        $query->where(['is_delete' => ['eq', 0]]);
    }

    /**
     * 生成唯一应用Key
     * @access private
     * @return string
     */
    private function getAppKey()
    {
        do {
            $appKey = rand_number(8);
        } while (self::checkUnique(['app_key' => ['eq', $appKey]]));

        return $appKey;
    }

    /**
     * 添加一个应用
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addAppItem($data)
    {
        if (!$this->validateData($data, 'App')) {
            return false;
        }

        // 初始化部分数据
        $data['app_key'] = $this->getAppKey();
        $data['app_secret'] = rand_string();
        unset($data['app_id']);

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个应用
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setAppItem($data)
    {
        if (!$this->validateSetData($data, 'App.set')) {
            return false;
        }

        if (isset($data['app_name'])) {
            $map['app_id'] = ['neq', $data['app_id']];
            $map['app_name'] = ['eq', $data['app_name']];

            if (self::checkUnique($map)) {
                return $this->setError('应用名称已存在');
            }
        }

        $field = ['app_name', 'status'];
        $map = ['app_id' => ['eq', $data['app_id']]];

        if (false !== $this->allowField($field)->save($data, $map)) {
            Cache::clear('app');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 获取一个应用
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getAppItem($data)
    {
        if (!$this->validateData($data, 'App.item')) {
            return false;
        }

        $result = self::get($data['app_id']);
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取应用列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getAppList($data)
    {
        if (!$this->validateData($data, 'App.list')) {
            return false;
        }

        $result = self::all(function ($query) use ($data) {
            // 搜索条件
            $map = [];
            !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];
            empty($data['app_name']) ?: $map['app_name'] = ['like', '%' . $data['app_name'] . '%'];

            $query->where($map);
        });

        if (false !== $result) {
            return $result->toArray();
        }

        return false;
    }

    /**
     * 批量删除应用
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delAppList($data)
    {
        if (!$this->validateData($data, 'App.del')) {
            return false;
        }

        $map['app_id'] = ['in', $data['app_id']];
        if (false !== $this->save(['is_delete' => 1], $map)) {
            Cache::clear('app');
            return true;
        }

        return false;
    }

    /**
     * 查询应用名称是否已存在
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function uniqueAppName($data)
    {
        if (!$this->validateData($data, 'App.unique')) {
            return false;
        }

        $map['app_name'] = ['eq', $data['app_name']];
        !isset($data['exclude_id']) ?: $map['app_id'] = ['neq', $data['exclude_id']];

        if (self::checkUnique($map)) {
            return $this->setError('应用名称已存在');
        }

        return true;
    }

    /**
     * 更换应用Secret
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function replaceAppSecret($data)
    {
        if (!$this->validateData($data, 'App.replace')) {
            return false;
        }

        $map['app_id'] = ['eq', $data['app_id']];
        $result = $this->save(['app_secret' => rand_string()], $map);

        if (false !== $result) {
            Cache::clear('app');
            return $result === 0 ? false : $this->toArray();
        }

        return false;
    }

    /**
     * 批量设置应用状态
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setAppStatus($data)
    {
        if (!$this->validateData($data, 'App.status')) {
            return false;
        }

        $map['app_id'] = ['in', $data['app_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            Cache::clear('app');
            return true;
        }

        return false;
    }
}