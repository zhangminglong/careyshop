<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品咨询模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/10
 */

namespace app\common\model;

class GoodsConsult extends CareyShop
{
    /**
     * 是否需要自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 更新时间字段
     * @var bool/string
     */
    protected $updateTime = false;

    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'parent_id',
        'user_id',
        'is_anon',
        'goods_id',
        'is_delete',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'goods_consult_id' => 'integer',
        'goods_id'         => 'integer',
        'parent_id'        => 'integer',
        'user_id'          => 'integer',
        'type'             => 'integer',
        'is_show'          => 'integer',
        'is_anon'          => 'integer',
        'status'           => 'integer',
        'is_delete'        => 'integer',
    ];

    /**
     * hasMany cs_goods_consult
     * @access public
     * @return mixed
     */
    public function getAnswer()
    {
        return $this->hasMany('GoodsConsult', 'parent_id');
    }

    /**
     * hasOne cs_user
     * @access public
     * @return mixed
     */
    public function getUser()
    {
        return $this
            ->hasOne('User', 'user_id', 'user_id', [], 'left')
            ->field('username,nickname,head_pic')
            ->setEagerlyType(0);
    }

    /**
     * hasOne cs_goods
     * @access public
     * @return mixed
     */
    public function getGoods()
    {
        return $this
            ->hasOne('Goods', 'goods_id', 'goods_id', [], 'left')
            ->field('goods_id,name,attachment')
            ->setEagerlyType(0);
    }

    /**
     * 添加一个新的商品咨询
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addConsultItem($data)
    {
        if (!$this->validateData($data, 'GoodsConsult')) {
            return false;
        }

        // 获取用户当前登录Id,并定义写入字段
        $data['user_id'] = get_client_id();
        $field = ['goods_id', 'user_id', 'type', 'content', 'is_show', 'is_anon'];

        if (false !== $this->allowField($field)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除商品咨询
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delConsultList($data)
    {
        if (!$this->validateData($data, 'GoodsConsult.del')) {
            return false;
        }

        // 允许删除所有Id,包括咨询与回答
        $map['goods_consult_id'] = ['in', $data['goods_consult_id']];
        $map['is_delete'] = ['eq', 0];

        if (false !== $this->save(['is_delete' => 1], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 批量设置是否前台显示
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setConsultShow($data)
    {
        if (!$this->validateData($data, 'GoodsConsult.show')) {
            return false;
        }

        // 只允许将咨询主题设置是否显示,设置回答毫无意义
        $map['goods_consult_id'] = ['in', $data['goods_consult_id']];
        $map['parent_id'] = ['eq', 0];
        $map['is_delete'] = ['eq', 0];

        if (false !== $this->save(['is_show' => $data['is_show']], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 回复一个商品咨询
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function replyConsultItem($data)
    {
        if (!$this->validateData($data, 'GoodsConsult.reply')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['goods_consult_id'] = ['eq', $data['goods_consult_id']];
            $map['parent_id'] = ['eq', 0];
            $map['is_delete'] = ['eq', 0];

            $query->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        // 开启事务
        self::startTrans();

        try {
            if (false === $result->save(['status' => 1])) {
                throw new \Exception($this->getError());
            }

            // 准备回复的内容
            unset($result['goods_consult_id']);
            $result->setAttr('parent_id', $data['goods_consult_id']);
            $result->setAttr('content', $data['content']);

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
     * 获取一个商品咨询问答明细
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getConsultItem($data)
    {
        if (!$this->validateData($data, 'GoodsConsult.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['goods_consult_id'] = ['eq', $data['goods_consult_id']];
            $map['parent_id'] = ['eq', 0];
            $map['is_delete'] = ['eq', 0];
            is_client_admin() ?: $map['user_id'] = ['eq', get_client_id()];

            $query->with(['getAnswer' => function ($query) {
                $query->field('is_show,status,type', true)->where(['is_delete' => 0]);
            }])->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取商品咨询列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getConsultList($data)
    {
        if (!$this->validateData($data, 'GoodsConsult.list')) {
            return false;
        }

        // 搜索条件
        $map['goods_consult.parent_id'] = ['eq', 0];
        $map['goods_consult.is_delete'] = ['eq', 0];

        // 管理组:为空表示获取所有的咨询列表
        // 客户组:为空表示获取当前客户自己的咨询列表
        empty($data['goods_id']) ?: $map['goods_consult.goods_id'] = ['eq', $data['goods_id']];

        // 区分前后台
        if (is_client_admin()) {
            // 允许管理组根据用户搜索商品咨询
            if (!empty($data['account'])) {
                $map['getUser.username|getUser.nickname'] = ['eq', $data['account']];
            }
        } else {
            // 当goods_id为空,表示顾客组想要获取属于他自己的咨询列表
            if (empty($data['goods_id'])) {
                $map['getUser.user_id'] = ['eq', get_client_id()];
                unset($data['is_show']);
            } else {
                // 否则表示获取指定商品下的咨询列表,所以需要加上is_show为条件
                $data['is_show'] = 1;
            }
        }

        !isset($data['type']) ?: $map['goods_consult.type'] = ['eq', $data['type']];
        !isset($data['status']) ?: $map['goods_consult.status'] = ['eq', $data['status']];
        !isset($data['is_show']) ?: $map['goods_consult.is_show'] = ['eq', $data['is_show']];
        empty($data['content']) ?: $map['goods_consult.content'] = ['like', '%' . $data['content'] . '%'];

        $totalResult = $this->with('getUser')->where($map)->count();
        if ($totalResult <= 0) {
            return ['total_result' => 0];
        }

        $result = $this->all(function ($query) use ($data, $map) {
            // 翻页页数
            $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

            // 每页条数
            $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'desc';

            // 排序的字段
            $orderField = 'goods_consult.goods_consult_id';
            if (isset($data['order_field'])) {
                switch ($data['order_field']) {
                    case 'goods_consult_id':
                    case 'type':
                    case 'content':
                    case 'is_show':
                    case 'is_anon':
                    case 'status':
                    case 'create_time':
                        $orderField = 'goods_consult.' . $data['order_field'];
                        break;

                    case 'username':
                    case 'nickname':
                        $orderField = 'getUser.' . $data['order_field'];
                        break;
                }
            }

            // 判断是否需要关联Goods(为空表示获取列表,列表中需要商品信息,方便查看)
            $with = ['getUser'];
            !empty($data['goods_id']) ?: $with[] = 'getGoods';

            // 是否需要提取答复列表
            if (isset($data['is_answer']) && $data['is_answer'] == 1) {
                $with['getAnswer'] = function ($query) {
                    $query->field('is_show,status', true)->where(['is_delete' => 0]);
                };
            }

            $query
                ->with($with)
                ->where($map)
                ->order([$orderField => $orderType])
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            // 账号资料匿名处理
            if (!is_client_admin()) {
                foreach ($result as $value) {
                    if ($value->getAttr('is_anon') !== 0) {
                        $value['get_user']->setAttr('username', auto_hid_substr($value['get_user']->getAttr('username')));
                        $value['get_user']->setAttr('nickname', auto_hid_substr($value['get_user']->getAttr('nickname')));
                    }
                }
            }

            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }
}