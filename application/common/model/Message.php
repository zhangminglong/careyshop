<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    消息模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/11/27
 */

namespace app\common\model;

class Message extends CareyShop
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
        'is_delete',
    ];

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'message_id',
        'member',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'message_id' => 'integer',
        'type'       => 'integer',
        'member'     => 'integer',
        'page_views' => 'integer',
        'is_top'     => 'integer',
        'status'     => 'integer',
        'is_delete'  => 'integer',
    ];

    /**
     * hasOne cs_message_user
     * @access public
     * @return mixed
     */
    public function getMessageUser()
    {
        return $this->hasOne('MessageUser', 'message_id');
    }

    /**
     * 添加一条消息
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addMessageItem($data)
    {
        if (!$this->validateData($data, 'Message')) {
            return false;
        }

        // 避免无关参数及初始化部分数据
        unset($data['message_id'], $data['page_views']);

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 添加一条私有函(内部调用)
     * @access public
     * @param  array $data       消息结构数据
     * @param  array $clientId   账号编号
     * @param  int   $clientType 消息成员组 0=顾客组 1=管理组
     * @return bool
     * @throws
     */
    public function inAddMessageItem($data, $clientId, $clientType)
    {
        if (!$this->validateData($data, 'Message')) {
            return false;
        }

        // 避免无关参数及初始化部分数据
        unset($data['message_id'], $data['page_views']);
        $data['member'] = 0;

        // 开启事务
        self::startTrans();

        try {
            if (false === $this->allowField(true)->save($data)) {
                throw new \Exception($this->getError());
            }

            $messageUserData = [];
            $clientType = $clientType == 0 ? 'user_id' : 'admin_id';

            foreach ($clientId as $value) {
                $messageUserData[] = [
                    'message_id'  => $this->getAttr('message_id'),
                    $clientType   => $value,
                    'is_read'     => 0,
                    'create_time' => time(),
                ];
            }

            $messageUserDb = new MessageUser();
            if (false === $messageUserDb->insertAll($messageUserData)) {
                throw new \Exception($messageUserDb->getError());
            }

            self::commit();
            return true;
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 编辑一条消息
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setMessageItem($data)
    {
        if (!$this->validateSetData($data, 'Message.set')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['message_id'] = ['eq', $data['message_id']];
            $map['member'] = ['neq', 0];
            $map['is_delete'] = ['eq', 0];

            $query->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('消息不存在') : false;
        }

        if ($result->getAttr('status') === 1) {
            return $this->setError('消息已发布，不允许编辑！');
        }

        if (false !== $result->allowField(true)->isUpdate(true)->save($data)) {
            return $result->toArray();
        }

        return false;
    }

    /**
     * 批量删除消息
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delMessageList($data)
    {
        if (!$this->validateData($data, 'Message.del')) {
            return false;
        }

        $map['message_id'] = ['in', $data['message_id']];
        $map['member'] = ['neq', 0];
        $map['is_delete'] = ['eq', 0];

        if (false !== $this->save(['is_delete' => 1], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 批量正式发布消息
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setMessageStatus($data)
    {
        if (!$this->validateData($data, 'Message.status')) {
            return false;
        }

        $map['message_id'] = ['in', $data['message_id']];
        $map['member'] = ['neq', 0];
        $map['status'] = ['eq', 0];
        $map['is_delete'] = ['eq', 0];

        if (false !== $this->save(['status' => 1], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 获取一条消息(后台)
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getMessageItem($data)
    {
        if (!$this->validateData($data, 'Message.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['message_id'] = ['eq', $data['message_id']];
            $map['member'] = ['neq', 0];
            $map['is_delete'] = ['eq', 0];

            $query->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 用户获取一条消息
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getMessageUserItem($data)
    {
        if (!$this->validateData($data, 'Message.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['message_id'] = ['eq', $data['message_id']];
            $map['status'] = ['eq', 1];
            $map['is_delete'] = ['eq', 0];

            $query->where($map);
        });

        if (!$result) {
            return is_null($result) ? null : false;
        }

        // 验证是否有阅读权限
        $map['message_id'] = ['eq', $result->getAttr('message_id')];
        $map[is_client_admin() ? 'admin_id' : 'user_id'] = ['eq', get_client_id()];

        $userDb = new MessageUser();
        $userResult = $userDb->where($map)->value('is_delete', 1, true);

        switch ($result->getAttr('member')) {
            case 0:
                $notReadable = $userResult === 1;
                break;
            case 1:
                $notReadable = is_client_admin() || $userResult === 1;
                break;
            case 2:
                $notReadable = !is_client_admin() || $userResult === 1;
                break;
            default:
                $notReadable = true;
        }

        if ($notReadable) {
            return null;
        }

        // 存在权限则需要插入记录与更新
        $result->setInc('page_views');
        $userDb->updateMessageUserItem($data['message_id']);

        return $result->toArray();
    }

    /**
     * 获取消息列表(后台)
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getMessageList($data)
    {
        if (!$this->validateData($data, 'Message.list')) {
            return false;
        }

        // 搜索条件
        !isset($data['type']) ?: $map['type'] = ['eq', $data['type']];
        empty($data['title']) ?: $map['title'] = ['like', '%' . $data['title'] . '%'];
        !isset($data['is_top']) ?: $map['is_top'] = ['eq', $data['is_top']];
        !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];
        $map['member'] = isset($data['member']) ? ['eq', $data['member']] : ['neq', 0];
        $map['is_delete'] = ['eq', 0];

        $totalResult = $this->where($map)->count();
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
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'message_id';

            $query
                ->where($map)
                ->order([$orderField => $orderType])
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }

    /**
     * 用户获取未读消息数
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     */
    public function getMessageUserUnread($data)
    {
        if (!$this->validateData($data, 'Message.unread')) {
            return false;
        }

        return $this->getMessageUserList($data, true);
    }

    /**
     * 用户获取消息列表
     * @access public
     * @param  array $data       外部数据
     * @param  bool  $isGetTotal 是否只获取数量
     * @return array|false
     * @throws
     */
    public function getMessageUserList($data, $isGetTotal = false)
    {
        if (!$this->validateData($data, 'Message.list')) {
            return false;
        }

        !isset($data['type']) ?: $map['m.type'] = ['eq', $data['type']];
        $map['m.status'] = ['eq', 1];
        $map['m.is_delete'] = ['eq', 0];

        $mapRead = null;
        !$isGetTotal ?: $data['is_read'] = 0;
        $clientType = is_client_admin() ? 'admin_id' : 'user_id';

        // 是否已读需要特殊对待
        if (isset($data['is_read'])) {
            switch ($data['is_read']) {
                case 0:
                    $mapRead = '`u`.' . $clientType . ' IS NULL OR `u`.is_read = 0';
                    break;

                case 1:
                    $mapRead = ['u.is_read' => ['eq', 1]];
                    break;
            }
        }

        // 构建子语句
        $userSQL = MessageUser::where([$clientType => ['eq', get_client_id()]])->buildSql();

        // 联合查询语句
        $userWhere_1 = '`u`.' . $clientType . ' IS NULL OR `u`.' . $clientType . ' = :' . $clientType . '';
        $userWhere_2 = '`u`.' . $clientType . ' IS NULL OR `u`.is_delete = 0';
        $userWhere_3 = '`u`.' . $clientType . ' IS NOT NULL OR `m`.member > 0';

        $totalResult = $this
            ->alias('m')
            ->join([$userSQL => 'u'], 'u.message_id = m.message_id', 'left')
            ->where($userWhere_1, [$clientType => [get_client_id(), \PDO::PARAM_INT]])
            ->where($userWhere_2)
            ->where($userWhere_3)
            ->where($mapRead)
            ->where($map)
            ->count();

        if ($totalResult <= 0 || $isGetTotal) {
            return ['total_result' => $totalResult];
        }

        // 翻页页数
        $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

        // 每页条数
        $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

        // 排序方式
        $orderType = !empty($data['order_type']) ? $data['order_type'] : 'desc';

        // 排序的字段
        $orderField = !empty($data['order_field']) ? $data['order_field'] : 'message_id';

        // 排序处理
        $order['m.is_top'] = 'desc';
        $order['m.' . $orderField] = $orderType;

        $result = $this
            ->alias('m')
            ->field('m.message_id,m.title,m.url,m.target,m.page_views,ifnull(`u`.is_read, 0) is_read,m.create_time')
            ->join([$userSQL => 'u'], 'u.message_id = m.message_id', 'left')
            ->where($userWhere_1, [$clientType => [get_client_id(), \PDO::PARAM_INT]])
            ->where($userWhere_2)
            ->where($userWhere_3)
            ->where($mapRead)
            ->where($map)
            ->order($order)
            ->page($pageNo, $pageSize)
            ->select();

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }
}