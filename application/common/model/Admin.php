<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    管理组账号模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/12/29
 */

namespace app\common\model;

use think\Cache;
use think\Request;

class Admin extends CareyShop
{
    /**
     * 是否需要自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'password',
        'is_delete',
    ];

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'admin_id',
        'username',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'admin_id'   => 'integer',
        'group_id'   => 'integer',
        'last_login' => 'timestamp',
        'status'     => 'integer',
        'is_delete'  => 'integer',
    ];

    /**
     * 密码修改器
     * @access protected
     * @param  string $value 值
     * @return string
     */
    protected function setPasswordAttr($value)
    {
        return user_md5($value);
    }

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
     * hasOne db_token
     * @access public
     * @return mixed
     */
    public function hasToken()
    {
        return $this->hasOne('Token', 'admin_id', 'client_id');
    }

    /**
     * hasOne cs_auth_group
     * @access public
     * @return mixed
     */
    public function getAuthGroup()
    {
        return $this
            ->hasOne('AuthGroup', 'group_id', 'group_id', [], 'left')
            ->field('name,status')
            ->setEagerlyType(0);
    }

    /**
     * 添加一个账号
     * @access public
     * @param  array $data 外部数据
     * @return array|bool
     * @throws
     */
    public function addAdminItem($data)
    {
        if (!$this->validateData($data, 'Admin')) {
            return false;
        }

        $field = ['username', 'password', 'group_id', 'nickname', 'head_pic'];
        if (false !== $this->allowField($field)->save($data)) {
            return $this->hidden(['password_confirm'])->toArray();
        }

        return false;
    }

    /**
     * 编辑一个账号
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setAdminItem($data)
    {
        if (!$this->validateSetData($data, 'Admin.set')) {
            return false;
        }

        // 数据类型修改
        $data['client_id'] = (int)$data['client_id'];

        if (isset($data['nickname'])) {
            $nickMap['admin_id'] = ['neq', $data['client_id']];
            $nickMap['nickname'] = ['eq', $data['nickname']];

            if (self::checkUnique($nickMap)) {
                return $this->setError('昵称已存在');
            }
        }

        if (isset($data['group_id'])) {
            Cache::clear('token:admin_' . $data['client_id']);
            $this->hasToken()->where(['client_id' => $data['client_id'], 'client_type' => 1])->delete();
        }

        $map['admin_id'] = ['eq', $data['client_id']];
        if (false !== $this->allowField(['group_id', 'nickname', 'head_pic'])->save($data, $map)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量设置账号状态
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function setAdminStatus($data)
    {
        if (!$this->validateData($data, 'Admin.status')) {
            return false;
        }

        $map = ['admin_id' => ['in', $data['client_id']]];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            foreach ($data['client_id'] as $value) {
                Cache::clear('token:admin_' . $value);
            }

            $map = ['client_id' => ['in', $data['client_id']], 'client_type' => 1];
            $this->hasToken()->where($map)->delete();
            return true;
        }

        return false;
    }

    /**
     * 修改一个账号密码
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function setAdminPassword($data)
    {
        if (!$this->validateData($data, 'Admin.change')) {
            return false;
        }

        $result = self::get($data['client_id']);
        if (!$result) {
            return is_null($result) ? $this->setError('账号不存在') : false;
        }

        if (!hash_equals($result->getAttr('password'), user_md5($data['password_old']))) {
            return $this->setError('原始密码错误');
        }

        if (false !== $result->setAttr('password', $data['password'])->save()) {
            Cache::clear('token:admin_' . $data['client_id']);
            $result->hasToken()->where(['client_id' => $data['client_id'], 'client_type' => 1])->delete();
            return true;
        }

        return false;
    }

    /**
     * 重置一个账号密码
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     */
    public function resetAdminItem($data)
    {
        if (!$this->validateData($data, 'Admin.reset')) {
            return false;
        }

        // 初始化部分数据
        $data['password'] = mb_strtolower(get_randstr(8), 'utf-8');
        $map['admin_id'] = ['eq', $data['client_id']];

        if (false !== $this->save(['password' => $data['password']], $map)) {
            Cache::clear('token:admin_' . $data['client_id']);
            return ['password' => $data['password']];
        }

        return false;
    }

    /**
     * 批量删除账号
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function delAdminList($data)
    {
        if (!$this->validateData($data, 'Admin.del')) {
            return false;
        }

        $map = ['admin_id' => ['in', $data['client_id']]];
        if (false !== $this->save(['is_delete' => 1], $map)) {
            foreach ($data['client_id'] as $value) {
                Cache::clear('token:admin_' . $value);
            }

            $map = ['client_id' => ['in', $data['client_id']], 'client_type' => 1];
            $this->hasToken()->where($map)->delete();

            return true;
        }

        return false;
    }

    /**
     * 获取一个账号
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getAdminItem($data)
    {
        if (!$this->validateData($data, 'Admin.item')) {
            return false;
        }

        $result = self::get($data['client_id'], 'getAuthGroup');
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取账号列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getAdminList($data)
    {
        if (!$this->validateData($data, 'Admin.list')) {
            return false;
        }

        // 搜索条件
        $map = [];
        empty($data['account']) ?: $map['admin.username|admin.nickname'] = ['eq', $data['account']];
        !isset($data['group_id']) ?: $map['admin.group_id'] = ['eq', $data['group_id']];
        !isset($data['status']) ?: $map['admin.status'] = ['eq', $data['status']];

        $totalResult = $this->alias('admin')->where($map)->count();
        if ($totalResult <= 0) {
            return ['total_result' => 0];
        }

        $result = self::all(function ($query) use ($data, $map) {
            // 翻页页数
            $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

            // 每页条数
            $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'desc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'admin_id';

            $query
                ->with('getAuthGroup')
                ->where($map)
                ->order(['admin.' . $orderField => $orderType])
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }

    /**
     * 注销账号
     * @access public
     * @return bool
     * @throws
     */
    public function logoutAdmin()
    {
        $map['client_id'] = ['eq', get_client_id()];
        $map['client_type'] = ['eq', 1];

        $token = Request::instance()->param('token');
        if (!empty($token)) {
            $map['token'] = ['eq', $token];
            Cache::rm('token:' . $token);
        }

        $this->hasToken()->where($map)->delete();
        $GLOBALS['client'] = [];

        return true;
    }

    /**
     * 登录账号
     * @access public
     * @param  array $data       外部数据
     * @param  bool  $isGetToken 是否需要返回Token
     * @return array|false
     * @throws
     */
    public function loginAdmin($data, $isGetToken = true)
    {
        if (!$this->validateData($data, 'Admin.login')) {
            return false;
        }

        // 根据账号获取
        $result = self::get(['username' => $data['username']]);
        if (!$result) {
            return is_null($result) ? $this->setError('账号不存在') : false;
        }

        if ($result->getAttr('status') !== 1) {
            return $this->setError('账号已禁用');
        }

        if (!hash_equals($result->getAttr('password'), user_md5($data['password']))) {
            return $this->setError('账号或密码错误');
        }

        $data['last_login'] = time();
        $data['last_ip'] = Request::instance()->ip();
        unset($data['admin_id']);
        $this->allowField(['last_login', 'last_ip'])->save($data, ['username' => $data['username']]);

        if (!$isGetToken) {
            return ['admin' => $result->toArray()];
        }

        $adminId = $result->getAttr('admin_id');
        $groupId = $result->getAttr('group_id');

        $tokenDb = new Token();
        $tokenResult = $tokenDb->setToken($adminId, $groupId, 1, $data['username'], $data['platform']);

        if (false === $tokenResult) {
            return $this->setError($tokenDb->getError());
        }

        Cache::clear('token:admin_' . $result->getAttr('admin_id'));
        return ['admin' => $result->toArray(), 'token' => $tokenResult];
    }

    /**
     * 刷新Token
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     */
    public function refreshToken($data)
    {
        if (!$this->validateData($data, 'Admin.refresh')) {
            return false;
        }

        // 获取原始Token
        $oldToken = Request::instance()->param('token', '');

        $tokenDb = new Token();
        $result = $tokenDb->refreshUser(1, $data['refresh'], $oldToken);

        if (false !== $result) {
            Cache::rm('token:' . $oldToken);
            return ['token' => $result];
        }

        return $this->setError($tokenDb->getError());
    }
}