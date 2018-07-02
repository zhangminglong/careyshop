<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    问答模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/30
 */

namespace app\common\model;

class Ask extends CareyShop
{
    /**
     * 主题
     * @var int
     */
    const ASK_TYPT_THEME = 0;

    /**
     * 提问
     * @var int
     */
    const ASK_TYPT_ASK = 1;

    /**
     * 回答
     * @var int
     */
    const ASK_TYPT_ANSWER = 2;

    /**
     * 是否需要自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 创建时间字段
     * @var bool/string
     */
    protected $createTime = 'ask_time';

    /**
     * 更新时间字段
     * @var bool/string
     */
    protected $updateTime = 'answer_time';

    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'parent_id',
        'is_delete',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'ask_id'    => 'integer',
        'user_id'   => 'integer',
        'parent_id' => 'integer',
        'ask_type'  => 'integer',
        'type'      => 'integer',
        'status'    => 'integer',
        'is_delete' => 'integer',
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
     * hasOne cs_user
     * @access public
     * @return mixed
     */
    public function getUser()
    {
        return $this
            ->hasOne('User', 'user_id', 'user_id', 'left')
            ->field('username,nickname,head_pic')
            ->setEagerlyType(0);
    }

    /**
     * hasMany cs_ask
     * @access public
     * @return mixed
     */
    public function getItems()
    {
        return $this->hasMany('Ask', 'parent_id');
    }

    /**
     * 添加一个提问
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addAskItem($data)
    {
        if (!$this->validateData($data, 'Ask')) {
            return false;
        }

        // 开启事务
        self::startTrans();

        try {
            $data['user_id'] = get_client_id();
            $data['type'] = self::ASK_TYPT_THEME;

            if (false === $this->allowField(['user_id', 'ask_type', 'type', 'title'])->save($data)) {
                throw new \Exception($this->getError());
            }

            $data['type'] = self::ASK_TYPT_ASK;
            $data['parent_id'] = $this->getAttr('ask_id');
            $field = ['user_id', 'parent_id', 'ask_type', 'type', 'ask'];

            if (false === $this->isUpdate(false)->allowField($field)->save($data)) {
                throw new \Exception($this->getError());
            }

            self::commit();
            return $this->toArray();
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 删除一条记录
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function delAskItem($data)
    {
        if (!$this->validateData($data, 'Ask.del')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['ask_id'] = ['eq', $data['ask_id']];
            is_client_admin() ?: $map['user_id'] = ['eq', get_client_id()];

            $query->where($map);
        });

        if (!$result) {
            return true;
        }

        if ($result->getAttr('type') === self::ASK_TYPT_THEME) {
            $this->save(['is_delete' => 1], ['ask_id|parent_id' => ['eq', $data['ask_id']]]);
        } else {
            $result->save(['is_delete' => 1]);
        }

        return true;
    }

    /**
     * 在主题上提交一个提问或回答
     * @access private
     * @param  array $data 提交数据
     * @param  bool  $isQa true:咨询 false:回复
     * @return false|array
     * @throws
     */
    private function addAskOrAnswer($data, $isQa)
    {
        $result = self::get(function ($query) use ($data, $isQa) {
            $map['parent_id'] = ['eq', 0];
            $map['ask_id'] = ['eq', $data['ask_id']];
            !$isQa ?: $map['user_id'] = ['eq', get_client_id()];

            $query->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        // 开启事务
        self::startTrans();

        try {
            if (false === $result->save(['status' => !$isQa])) {
                throw new \Exception($this->getError());
            }

            // 准备回复的内容
            $result->setAttr('parent_id', $result->getAttr('ask_id'));
            $result->setAttr('type', $isQa ? self::ASK_TYPT_ASK : self::ASK_TYPT_ANSWER);
            $isQa ? $result->setAttr('ask', $data['ask']) : $result->setAttr('answer', $data['answer']);

            // 避免无关数据
            unset($result['ask_id']);
            unset($result['title']);
            unset($result['status']);

            if (false === $result->isUpdate(false)->save()) {
                throw new \Exception($this->getError());
            }

            self::commit();
            return $result->toArray();
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 回答一个提问
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     */
    public function replyAskItem($data)
    {
        if (!$this->validateData($data, 'Ask.reply')) {
            return false;
        }

        $result = $this->addAskOrAnswer($data, false);
        if (false !== $result) {
            return $result;
        }

        return false;
    }

    /**
     * 在提问上继续提问
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     */
    public function continueAskItem($data)
    {
        if (!$this->validateData($data, 'Ask.continue')) {
            return false;
        }

        $result = $this->addAskOrAnswer($data, true);
        if (false !== $result) {
            return $result;
        }

        return false;
    }

    /**
     * 获取一个问答明细
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getAskItem($data)
    {
        if (!$this->validateData($data, 'Ask.item')) {
            return false;
        }

        // 获取主题与账号信息
        $result = self::useGlobalScope(false)->find(function ($query) use ($data) {
            $map['ask.ask_id'] = ['eq', $data['ask_id']];
            $map['ask.is_delete'] = ['eq', 0];
            is_client_admin() ?: $map['ask.user_id'] = ['eq', get_client_id()];

            $with = ['getUser'];
            $with['getItems'] = function ($query) {
                $query->field('user_id,ask_type,title,status', true)->order('ask_id');
            };

            $query->where($map)->field('ask,answer', true)->with($with);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取问答主题列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getAskList($data)
    {
        if (!$this->validateData($data, 'Ask.list')) {
            return false;
        }

        // 搜索条件
        $map['ask.parent_id'] = ['eq', 0];
        $map['ask.user_id'] = ['eq', get_client_id()];
        $map['ask.type'] = ['eq', self::ASK_TYPT_THEME];
        $map['ask.is_delete'] = ['eq', 0];
        !isset($data['ask_type']) ?: $map['ask.ask_type'] = ['eq', $data['ask_type']];
        !isset($data['status']) ?: $map['ask.status'] = ['eq', $data['status']];

        // 关联查询
        $with = [];

        // 后台管理搜索
        if (is_client_admin()) {
            $with = ['getUser'];
            unset($map['ask.user_id']);
            empty($data['account']) ?: $map['getUser.username|getUser.nickname'] = ['eq', $data['account']];
        }

        $totalResult = self::useGlobalScope(false)->alias('ask')->with($with)->where($map)->count();
        if ($totalResult <= 0) {
            return ['total_result' => 0];
        }

        $result = self::useGlobalScope(false)
            ->field('ask,answer', true)
            ->select(function ($query) use ($data, $map, $with) {
                // 翻页页数
                $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

                // 每页条数
                $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

                // 排序方式
                $orderType = !empty($data['order_type']) ? $data['order_type'] : 'desc';

                // 排序的字段
                $orderField = 'ask.ask_id';
                if (isset($data['order_field'])) {
                    switch ($data['order_field']) {
                        case 'ask_id':
                        case 'ask_type':
                        case 'title':
                        case 'status':
                        case 'ask_time':
                        case 'answer_time':
                            $orderField = 'ask.' . $data['order_field'];
                            break;

                        case 'username':
                        case 'nickname':
                            $orderField = 'getUser.' . $data['order_field'];
                            break;
                    }
                }

                $query
                    ->alias('ask')
                    ->with($with)
                    ->where($map)
                    ->order([$orderField => $orderType])
                    ->page($pageNo, $pageSize);
            });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }
}