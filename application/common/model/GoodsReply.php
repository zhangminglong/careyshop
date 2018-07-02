<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品评价回复模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/11
 */

namespace app\common\model;

class GoodsReply extends CareyShop
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
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'goods_reply_id',
        'goods_comment_id',
        'user_id',
    ];

    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'user_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'goods_reply_id'   => 'integer',
        'goods_comment_id' => 'integer',
        'reply_type'       => 'integer',
        'user_id'          => 'integer',
    ];

    /**
     * 对商品评价添加一个回复(管理组不参与评价回复)
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addReplyItem($data)
    {
        if (!$this->validateData($data, 'GoodsReply')) {
            return false;
        }

        // 获取被回复者Id,如果"goods_reply_id"空则默认获取主评价者Id
        if (empty($data['goods_reply_id'])) {
            $userId = GoodsComment::where(['goods_comment_id' => ['eq', $data['goods_comment_id']]])->value('user_id');
        } else {
            $userId = $this->where(['goods_reply_id' => ['eq', $data['goods_reply_id']]])->value('user_id');
        }

        // 避免无关字段及初始化数据
        unset($data['goods_reply_id']);
        $data['user_id'] = get_client_id();
        $data['nick_name'] = get_client_nickname();

        // 是否进行匿名处理
        if (!empty($data['is_anon'])) {
            $data['nick_name'] = auto_hid_substr($data['nick_name']);
        }

        if (!empty($userId)) {
            $messageData = [
                'type'    => 0,
                'member'  => 1,
                'title'   => '您的商品评价收到了最新回复',
                'content' => $data['nick_name'] . ' 对您的评价进行了回复：' . $data['content'],
            ];

            (new Message())->inAddMessageItem($messageData, [$userId], 0);
        }

        if (false !== $this->allowField(true)->save($data)) {
            GoodsComment::where(['goods_comment_id' => ['eq', $data['goods_comment_id']]])->setInc('reply_count');
            return $this->hidden(['is_anon'])->toArray();
        }

        return false;
    }

    /**
     * 批量删除商品评价的回复
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function delReplyList($data)
    {
        if (!$this->validateData($data, 'GoodsReply.del')) {
            return false;
        }

        $result = self::all($data['goods_reply_id']);
        if (false !== $result) {
            foreach ($result as $value) {
                $map['goods_comment_id'] = ['eq', $value->getAttr('goods_comment_id')];
                GoodsComment::where($map)->setDec('reply_count');
                $value->delete();
            }

            return true;
        }

        return false;
    }

    /**
     * 获取商品评价回复列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getReplyList($data)
    {
        if (!$this->validateData($data, 'GoodsReply.list')) {
            return false;
        }

        // 判断商品评价是否存在
        $map['goods_comment_id'] = ['eq', $data['goods_comment_id']];
        $map['type'] = ['eq', GoodsComment::COMMENT_TYPE_MAIN];
        $map['is_delete'] = ['eq', 0];
        is_client_admin() ?: $map['is_show'] = ['eq', 1];

        if (false === GoodsComment::checkUnique($map)) {
            return ['total_result' => 0];
        }

        // 开始获取评价回复数据
        $map = ['goods_comment_id' => ['eq', $data['goods_comment_id']]];
        $totalResult = $this->where($map)->count();

        if ($totalResult <= 0) {
            return ['total_result' => 0];
        }

        $result = self::all(function ($query) use ($data, $map) {
            // 翻页页数
            $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

            // 每页条数
            $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

            $query
                ->where($map)
                ->order(['goods_reply_id' => 'asc'])
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }
}