<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    专题模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/28
 */

namespace app\common\model;

class Topic extends CareyShop
{
    /**
     * 是否需要自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'topic_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'topic_id'    => 'integer',
        'status'      => 'integer',
    ];

    /**
     * 添加一个专题
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addTopicItem($data)
    {
        if (!$this->validateData($data, 'Topic')) {
            return false;
        }

        // 避免无关字段
        unset($data['topic_id']);

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个专题
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setTopicItem($data)
    {
        if (!$this->validateSetData($data, 'Topic.set')) {
            return false;
        }

        $map['topic_id'] = ['eq', $data['topic_id']];
        if (false !== $this->allowField(true)->save($data, $map)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除专题
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delTopicList($data)
    {
        if (!$this->validateData($data, 'Topic.del')) {
            return false;
        }

        self::destroy($data['topic_id']);
        
        return true;
    }

    /**
     * 获取一个专题
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getTopicItem($data)
    {
        if (!$this->validateData($data, 'Topic.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['topic_id'] = ['eq', $data['topic_id']];
            is_client_admin() ?: $map['status'] = ['eq', 1];

            $query->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取专题列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getTopicList($data)
    {
        if (!$this->validateData($data, 'Topic.list')) {
            return false;
        }

        // 搜索条件
        $map['status'] = ['eq', 1];

        // 后台管理搜索
        if (is_client_admin()) {
            unset($map['status']);
            !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];
            empty($data['title']) ?: $map['title'] = ['like', '%' . $data['title'] . '%'];
            empty($data['alias']) ?: $map['alias'] = ['like', '%' . $data['alias'] . '%'];
            empty($data['keywords']) ?: $map['keywords'] = ['like', '%' . $data['keywords'] . '%'];
        }

        // 获取总数量,为空直接返回
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
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'topic_id';

            $query
                ->field('content', true)
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
     * 批量设置专题是否显示
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setTopicStatus($data)
    {
        if (!$this->validateData($data, 'Topic.status')) {
            return false;
        }

        $map['topic_id'] = ['in', $data['topic_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            return true;
        }

        return false;
    }
}