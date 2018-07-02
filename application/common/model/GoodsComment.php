<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品评价模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/11
 */

namespace app\common\model;

use think\Request;

class GoodsComment extends CareyShop
{
    /**
     * 主评论
     * @var int
     */
    const COMMENT_TYPE_MAIN = 0;

    /**
     * 主评论回复
     * @var int
     */
    const COMMENT_TYPE_MAIN_REPLY = 1;

    /**
     * 追加评论
     * @var int
     */
    const COMMENT_TYPE_ADDITION = 2;

    /**
     * 追加评论回复
     * @var int
     */
    const COMMENT_TYPE_ADDITION_REPLY = 3;

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
     * 新增自动完成列表
     * @var array
     */
    protected $insert = [
        'ip_address',
    ];

    /**
     * IP地址自动完成
     * @access protected
     * @return string
     */
    protected function setIpAddressAttr()
    {
        return Request::instance()->ip();
    }

    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'parent_id',
        'order_no',
        'user_id',
        'is_anon',
        'is_image',
        'is_delete',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'goods_comment_id' => 'integer',
        'parent_id'        => 'integer',
        'goods_id'         => 'integer',
        'order_goods_id'   => 'integer',
        'user_id'          => 'integer',
        'is_anon'          => 'integer',
        'type'             => 'integer',
        'is_image'         => 'integer',
        'score'            => 'integer',
        'praise'           => 'integer',
        'reply_count'      => 'integer',
        'is_show'          => 'integer',
        'is_top'           => 'integer',
        'status'           => 'integer',
        'is_delete'        => 'integer',
        'image'            => 'array',
    ];

    /**
     * hasMany cs_goods_comment
     * @access public
     * @return mixed
     */
    public function getAddition()
    {
        return $this->hasMany('GoodsComment', 'parent_id');
    }

    /**
     * hasMany cs_goods_comment
     * @access public
     * @return mixed
     */
    public function getMainReply()
    {
        return $this->hasMany('GoodsComment', 'parent_id');
    }

    /**
     * hasMany cs_goods_comment
     * @access public
     * @return mixed
     */
    public function getAdditionReply()
    {
        return $this->hasMany('GoodsComment', 'parent_id');
    }

    /**
     * hasOne cs_order_goods
     * @access public
     * @return mixed
     */
    public function getGoods()
    {
        return $this
            ->hasOne('OrderGoods', 'order_goods_id', 'order_goods_id', [], 'left')
            ->field('goods_id,goods_name,goods_image,key_value')
            ->setEagerlyType(0);
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
            ->field('username,nickname,user_level_id,head_pic')
            ->setEagerlyType(0);
    }

    /**
     * 添加一条新的商品评价
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addCommentItem($data)
    {
        if (!$this->validateData($data, 'GoodsComment')) {
            return false;
        }

        $map['order_goods_id'] = ['eq', $data['order_goods_id']];
        $map['order_no'] = ['eq', $data['order_no']];
        $map['user_id'] = ['eq', get_client_id()];
        $map['type'] = ['eq', self::COMMENT_TYPE_MAIN];

        if (self::checkUnique($map)) {
            return $this->setError('订单商品评价已存在');
        }

        // 检测订单商品是否允许评价
        $orderGoodsDb = new OrderGoods();
        if (!$orderGoodsDb->isComment($data['order_no'], $data['order_goods_id'])) {
            return $this->setError($orderGoodsDb->getError());
        }

        // 允许外部写入字段
        $field = [
            'order_no', 'goods_id', 'order_goods_id', 'user_id', 'is_anon', 'type',
            'content', 'image', 'is_image', 'score', 'ip_address', 'is_show',
        ];

        // 系统数据内容
        $data['user_id'] = get_client_id();
        $data['type'] = self::COMMENT_TYPE_MAIN;
        empty($data['image']) ? $data['image'] = [] : $data['is_image'] = 1;

        // 开启事务
        self::startTrans();

        try {
            // 添加订单商品评价
            if (false === $this->allowField($field)->save($data)) {
                throw new \Exception($this->getError());
            }

            // 修改订单商品数据
            unset($map['type']);
            if (false === $orderGoodsDb->isUpdate(true)->save(['is_comment' => 1], $map)) {
                throw new \Exception($orderGoodsDb->getError());
            }

            // 累计增加商品评价数
            Goods::where(['goods_id' => ['eq', $data['goods_id']]])->setInc('comment_sum');

            self::commit();
            return $this->toArray();
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 追加一条商品评价
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addAdditionItem($data)
    {
        if (!$this->validateData($data, 'GoodsComment.addition')) {
            return false;
        }

        $map['parent_id'] = ['neq', 0];
        $map['order_goods_id'] = ['eq', $data['order_goods_id']];
        $map['order_no'] = ['eq', $data['order_no']];
        $map['user_id'] = ['eq', get_client_id()];
        $map['type'] = ['eq', self::COMMENT_TYPE_ADDITION];

        if (self::checkUnique($map)) {
            return $this->setError('订单商品追加评价已存在');
        }

        // 获取主评价
        $result = self::get(function ($query) use ($map) {
            $map['parent_id'] = ['eq', 0];
            $map['type'] = ['eq', self::COMMENT_TYPE_MAIN];
            $map['is_delete'] = ['eq', 0];

            $query->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('主评价不存在') : false;
        }

        // 开启事务
        self::startTrans();

        try {
            // 设置主评价为未读,并且判断追评是否存在图片
            $updata['status'] = 0;
            empty($data['image']) ?: $updata['is_image'] = 1;

            if (false === $result->save($updata)) {
                throw new \Exception($this->getError());
            }

            // 准备插入数据
            $result->setAttr('score', 0);
            $result->setAttr('praise', 0);
            $result->setAttr('reply_count', 0);
            $result->setAttr('is_show', 0);
            $result->setAttr('is_top', 0);
            $result->setAttr('status', 0);
            $result->setAttr('ip_address', '');
            $result->setAttr('parent_id', $result->getAttr('goods_comment_id'));
            $result->setAttr('goods_comment_id', null);
            $result->setAttr('type', self::COMMENT_TYPE_ADDITION);
            $result->setAttr('content', $data['content']);
            $result->setAttr('is_image', !empty($data['image']) ? 1 : 0);
            $result->setAttr('image', !empty($data['image']) ? $data['image'] : []);

            if (false === $result->isUpdate(false)->save()) {
                throw new \Exception($this->getError());
            }

            // 修改订单商品数据
            $mapGoods['order_goods_id'] = ['eq', $result->getAttr('order_goods_id')];
            $mapGoods['order_no'] = ['eq', $data['order_no']];
            $mapGoods['user_id'] = ['eq', get_client_id()];

            $orderGoodsDb = new OrderGoods();
            if (false === $orderGoodsDb->isUpdate(true)->save(['is_comment' => 2], $mapGoods)) {
                throw new \Exception($orderGoodsDb->getError());
            }

            // 隐藏不返回的字段
            $hidden = ['is_show', 'is_top', 'status', 'praise', 'reply_count'];

            self::commit();
            return $result->hidden($hidden)->toArray();
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 回复或追评一条商品评价
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function replyCommentItem($data)
    {
        if (!$this->validateData($data, 'GoodsComment.reply')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['goods_comment_id'] = ['eq', $data['goods_comment_id']];
            $map['type'] = ['in', [self::COMMENT_TYPE_MAIN, self::COMMENT_TYPE_ADDITION]];
            $map['is_delete'] = ['eq', 0];

            $query->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        // 准备插入数据
        $result->setAttr('goods_comment_id', null);
        $result->setAttr('score', 0);
        $result->setAttr('praise', 0);
        $result->setAttr('reply_count', 0);
        $result->setAttr('ip_address', '');
        $result->setAttr('is_show', 0);
        $result->setAttr('is_top', 0);
        $result->setAttr('status', 0);
        $result->setAttr('is_image', 0);
        $result->setAttr('content', $data['content']);
        $result->setAttr('is_image', !empty($data['image']) ? 1 : 0);
        $result->setAttr('image', !empty($data['image']) ? $data['image'] : []);

        // 回复和追加共用数据结构
        if ($result->getAttr('type') === self::COMMENT_TYPE_MAIN) {
            $result->setAttr('type', self::COMMENT_TYPE_MAIN_REPLY);
            $result->setAttr('parent_id', $data['goods_comment_id']);
        } else {
            $result->setAttr('type', self::COMMENT_TYPE_ADDITION_REPLY);
            $result->setAttr('parent_id', $result->getAttr('parent_id'));
        }

        if (false !== $result->isUpdate(false)->save()) {
            // 隐藏不返回的字段
            $hidden = ['is_show', 'is_top', 'status', 'praise', 'reply_count'];
            return $result->hidden($hidden)->toArray();
        }

        return false;
    }

    /**
     * 删除任意一条商品评价(主评,主回,追评,追回)
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function delCommentItem($data)
    {
        if (!$this->validateData($data, 'GoodsComment.del')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['goods_comment_id'] = ['eq', $data['goods_comment_id']];
            $map['is_delete'] = ['eq', 0];

            if (!is_client_admin()) {
                $map['user_id'] = ['eq', get_client_id()];
                $map['type'] = ['in', [self::COMMENT_TYPE_MAIN, self::COMMENT_TYPE_ADDITION]];
            }

            $query->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        // 软删除评价
        $result->save(['is_delete' => 1]);

        // 如果是追评需要处理主评是否有图
        if ($result->getAttr('type') === self::COMMENT_TYPE_ADDITION && $result->getAttr('is_image') === 1) {
            $map['goods_comment_id'] = ['eq', $result->getAttr('parent_id')];
            $map['is_delete'] = ['eq', 0];

            $mainResult = $this->where($map)->find();
            if ($mainResult && empty($mainResult->getAttr('image'))) {
                $mainResult->save(['is_image' => 0]);
            }
        }

        return true;
    }

    /**
     * 点赞任意一条商品评价(主评,主回,追评,追回)
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function addPraiseItem($data)
    {
        if (!$this->validateData($data, 'GoodsComment.praise')) {
            return false;
        }

        $count = $this
            ->alias('c')
            ->join('praise p', 'p.goods_comment_id = c.goods_comment_id')
            ->where(['p.user_id' => ['eq', get_client_id()], 'c.goods_comment_id' => ['eq', $data['goods_comment_id']]])
            ->count();

        if ($count > 0) {
            return $this->setError('您已经点过赞了');
        }

        $map['goods_comment_id'] = ['eq', $data['goods_comment_id']];
        $map['is_delete'] = ['eq', 0];

        // 更新成功则添加记录到praise表
        if ($this->where($map)->setInc('praise') > 0) {
            Praise::insert(['user_id' => get_client_id(), 'goods_comment_id' => $data['goods_comment_id']]);
        }

        return true;
    }

    /**
     * 获取一个商品评价得分
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     */
    public function getCommentScore($data)
    {
        if (!$this->validateData($data, 'GoodsComment.score')) {
            return false;
        }

        // 初始化数据
        $result = [
            'count'         => 0,
            'good_count'    => 0,
            'good_rate'     => 0,
            'general_count' => 0,
            'general_rate'  => 0,
            'poor_count'    => 0,
            'poor_rate'     => 0,
        ];

        // 公共查询条件
        $map['parent_id'] = ['eq', 0];
        $map['goods_id'] = ['eq', $data['goods_id']];
        $map['type'] = ['eq', self::COMMENT_TYPE_MAIN];
        $map['is_show'] = ['eq', 1];
        $map['is_delete'] = ['eq', 0];

        // 1~2=差评 3~4=中评 5=好评
        $result['poor_count'] = $this->where($map)->where(['score' => ['elt', 2]])->count();
        $result['general_count'] = $this->where($map)->where(['score' => ['between', '3,4']])->count();
        $result['good_count'] = $this->where($map)->where(['score' => ['eq', 5]])->count();
        $result['count'] = $result['poor_count'] + $result['general_count'] + $result['good_count'];

        if ($result['count'] > 0) {
            $result['good_rate'] = round(($result['good_count'] / $result['count']) * 100, 2);
            $result['general_rate'] = round(($result['general_count'] / $result['count']) * 100, 2);
            $result['poor_rate'] = round(($result['poor_count'] / $result['count']) * 100, 2);
        }

        return $result;
    }

    /**
     * 批量设置某个字段值
     * @access private
     * @param  array $field 修改的字段
     * @param  array $data  原始数据
     * @return bool
     */
    private function batchSetting($field, $data)
    {
        $map['goods_comment_id'] = ['in', $data['goods_comment_id']];
        $map['parent_id'] = ['eq', 0];
        $map['type'] = ['eq', self::COMMENT_TYPE_MAIN];
        $map['is_delete'] = ['eq', 0];

        unset($data['goods_comment_id']);
        if (false !== $this->allowField($field)->save($data, $map)) {
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
    public function setCommentShow($data)
    {
        if (!$this->validateData($data, 'GoodsComment.show')) {
            return false;
        }

        return $this->batchSetting(['is_show'], $data);
    }

    /**
     * 批量设置评价是否置顶
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setCommentTop($data)
    {
        if (!$this->validateData($data, 'GoodsComment.top')) {
            return false;
        }

        return $this->batchSetting(['is_top'], $data);
    }

    /**
     * 批量设置评价是否已读
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setCommentStatus($data)
    {
        if (!$this->validateData($data, 'GoodsComment.status')) {
            return false;
        }

        return $this->batchSetting(['status'], $data);
    }

    /**
     * 获取一个商品"全部"、"晒图"、"追评"、"好评"、"中评"、差评"的数量
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     */
    public function getCommentCount($data)
    {
        if (!$this->validateData($data, 'GoodsComment.count')) {
            return false;
        }

        // 初始化基础数据
        $result = [
            'all_count'      => 0,
            'image_count'    => 0,
            'addition_count' => 0,
            'good_count'     => 0,
            'general_count'  => 0,
            'poor_count'     => 0,
        ];

        // 公共筛选条件
        $map['goods_id'] = ['eq', $data['goods_id']];
        $map['type'] = ['eq', self::COMMENT_TYPE_MAIN];
        $map['is_show'] = ['eq', 1];
        $map['is_delete'] = ['eq', 0];

        $result['all_count'] = $this->where($map)->count();
        $result['image_count'] = $this->where($map)->where(['is_image' => ['eq', 1]])->count();
        $result['poor_count'] = $this->where($map)->where(['score' => ['elt', 2]])->count();
        $result['general_count'] = $this->where($map)->where(['score' => ['between', '3,4']])->count();
        $result['good_count'] = $this->where($map)->where(['score' => ['eq', 5]])->count();

        // 带有追加评论的
        $map['type'] = ['eq', self::COMMENT_TYPE_ADDITION];
        $result['addition_count'] = $this->where($map)->count();

        return $result;
    }

    /**
     * 获取某个评价的明细("是否已读"不关联,关联不代表看完,所以需手动设置)
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getCommentItem($data)
    {
        if (!$this->validateData($data, 'GoodsComment.item')) {
            return false;
        }

        // 获取条件
        $map['goods_comment.goods_comment_id'] = ['eq', $data['goods_comment_id']];
        $map['goods_comment.type'] = ['eq', self::COMMENT_TYPE_MAIN];
        is_client_admin() ?: $map['goods_comment.is_show'] = ['eq', 1];
        $map['goods_comment.is_delete'] = ['eq', 0];

        $result = self::get(function ($query) use ($map) {
            // 关联数据
            $with = ['getUser', 'getGoods'];

            // 关联表不返回的字段
            $replyField = 'goods_id,order_goods_id,score,is_top,status,is_show,reply_count';

            // 关联表搜索条件
            $replyMap['is_delete'] = ['eq', 0];

            // 返回主评回复
            $with['getMainReply'] = function ($query) use ($replyField, $replyMap) {
                $replyMap['type'] = ['eq', self::COMMENT_TYPE_MAIN_REPLY];
                $query->field($replyField . ',ip_address', true)->where($replyMap);
            };

            // 返回追加评价
            $with['getAddition'] = function ($query) use ($replyField, $replyMap) {
                $replyMap['type'] = ['eq', self::COMMENT_TYPE_ADDITION];
                $query->field($replyField, true)->where($replyMap);
            };

            // 返回追评回复
            $with['getAdditionReply'] = function ($query) use ($replyField, $replyMap) {
                $replyMap['type'] = ['eq', self::COMMENT_TYPE_ADDITION_REPLY];
                $query->field($replyField . ',ip_address', true)->where($replyMap);
            };

            $query->field('goods_id,order_goods_id,is_show,is_top,status', true)->with($with)->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        // 处理客户信息是否匿名
        if ($result->getAttr('is_anon') !== 0 && !is_client_admin()) {
            $result['get_user']->setAttr('username', auto_hid_substr($result['get_user']->getAttr('username')));
            $result['get_user']->setAttr('nickname', auto_hid_substr($result['get_user']->getAttr('nickname')));
        }

        return $result->toArray();
    }

    /**
     * 获取商品评价列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getCommentList($data)
    {
        if (!$this->validateData($data, 'GoodsComment.list')) {
            return false;
        }

        // 搜索条件
        $map['goods_comment.type'] = ['eq', self::COMMENT_TYPE_MAIN];
        $map['goods_comment.is_delete'] = ['eq', 0];

        empty($data['is_image']) ?: $map['goods_comment.is_image'] = ['eq', $data['is_image']];
        empty($data['goods_id']) ?: $map['goods_comment.goods_id'] = ['eq', $data['goods_id']];
        is_client_admin() ?: $map['goods_comment.is_show'] = ['eq', 1];

        // 处理查看的评价类型是"追加评价"
        if (isset($data['type']) && $data['type'] == self::COMMENT_TYPE_ADDITION) {
            $map['goods_comment.type'] = ['eq', self::COMMENT_TYPE_ADDITION];
        }

        // 处理"好中差"评价搜索(0=好评 1=中评 其他=差评)
        if (isset($data['score'])) {
            switch ($data['score']) {
                case 0:
                    $map['goods_comment.score'] = ['eq', 5];
                    break;
                case 1:
                    $map['goods_comment.score'] = ['between', '3,4'];
                    break;
                default:
                    $map['goods_comment.score'] = ['elt', 2];
            }
        }

        // 后台搜索条件
        if (is_client_admin()) {
            !isset($data['is_show']) ?: $map['goods_comment.is_show'] = ['eq', $data['is_show']];
            !isset($data['is_top']) ?: $map['goods_comment.is_top'] = ['eq', $data['is_top']];
            !isset($data['status']) ?: $map['goods_comment.status'] = ['eq', $data['status']];
            empty($data['order_no']) ?: $map['goods_comment.order_no'] = ['eq', $data['order_no']];
            empty($data['content']) ?: $map['goods_comment.content'] = ['like', '%' . $data['content'] . '%'];
            empty($data['account']) ?: $map['getUser.username|getUser.nickname'] = ['eq', $data['account']];
        }

        // 查看指定商品规格评价
        if (!empty($data['goods_id']) && !empty($data['goods_spec'])) {
            $with[] = 'getGoods';
            sort($data['goods_spec']);
            $data['goods_spec'] = implode('_', $data['goods_spec']);
            $map['getGoods.key_name'] = ['eq', $data['goods_spec']];
        }

        $with[] = 'getUser';
        $totalResult = $this->with($with)->where($map)->count();

        if ($totalResult <= 0) {
            return ['total_result' => 0];
        }

        $result = self::all(function ($query) use ($data, $map) {
            // 翻页页数
            $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

            // 每页条数
            $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

            // 关联数据
            $with = ['getUser', 'getGoods'];

            // 关联表不返回的字段
            $replyField = 'goods_id,order_goods_id,score,is_top,status,is_show,reply_count';

            // 关联表搜索条件
            $replyMap['is_delete'] = ['eq', 0];

            // 列表模式的区分(当"goods_id"为空表示简洁列表,否则为明细列表)
            if (!empty($data['goods_id'])) {
                // 设置主评回复
                $with['getMainReply'] = function ($query) use ($replyField, $replyMap) {
                    $replyMap['type'] = ['eq', self::COMMENT_TYPE_MAIN_REPLY];
                    $query->field($replyField . ',ip_address', true)->where($replyMap);
                };

                // 设置追加评价
                $with['getAddition'] = function ($query) use ($replyField, $replyMap) {
                    $replyMap['type'] = ['eq', self::COMMENT_TYPE_ADDITION];
                    $query->field($replyField, true)->where($replyMap);
                };

                // 设置追评回复
                $with['getAdditionReply'] = function ($query) use ($replyField, $replyMap) {
                    $replyMap['type'] = ['eq', self::COMMENT_TYPE_ADDITION_REPLY];
                    $query->field($replyField . ',ip_address', true)->where($replyMap);
                };
            }

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'desc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'goods_comment_id';

            // 排序处理
            $order['goods_comment.' . $orderField] = $orderType;
            $order['goods_comment.goods_comment_id'] = $orderType;

            // 过滤不需要返回的字段
            $field = 'goods_id,order_goods_id,is_show,is_top';
            is_client_admin() ?: $field .= ',status';

            $query
                ->field($field, true)
                ->with($with)
                ->where($map)
                ->order($order)
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