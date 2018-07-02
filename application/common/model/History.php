<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    我的足迹模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/7/15
 */

namespace app\common\model;

class History extends CareyShop
{
    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'user_id',
    ];

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'history_id',
        'user_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'history_id'  => 'integer',
        'user_id'     => 'integer',
        'goods_id'    => 'integer',
        'update_time' => 'timestamp',
    ];

    /**
     * hasOne cs_goods
     * @access public
     * @return mixed
     */
    public function getGoods()
    {
        $field = [
            'goods_category_id', 'name', 'short_name', 'brand_id', 'store_qty',
            'comment_sum', 'sales_sum', 'attachment', 'status', 'is_delete',
        ];

        return $this
            ->hasOne('Goods', 'goods_id', 'goods_id')
            ->field($field)
            ->setEagerlyType(0);
    }

    /**
     * 添加一个我的足迹
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function addHistoryItem($data)
    {
        if (get_client_id() == 0) {
            return true;
        }

        if (!$this->validateData($data, 'History')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['user_id'] = ['eq', get_client_id()];
            $map['goods_id'] = ['eq', $data['goods_id']];

            $query->where($map);
        });

        if ($result) {
            $result->setAttr('update_time', time())->save();
            return true;
        }

        // 避免无关字段
        unset($data['history_id']);
        $data['user_id'] = get_client_id();
        $data['update_time'] = time();

        if (false !== $this->allowField(true)->isUpdate(false)->save($data)) {
            return true;
        }

        return false;
    }

    /**
     * 批量删除我的足迹
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delHistoryList($data)
    {
        if (!$this->validateData($data, 'History.del')) {
            return false;
        }

        $map['history_id'] = ['in', $data['history_id']];
        $map['user_id'] = ['eq', get_client_id()];
        $this->where($map)->delete();

        return true;
    }

    /**
     * 清空我的足迹
     * @access public
     * @return bool
     */
    public function clearHistoryList()
    {
        self::destroy(['user_id' => get_client_id()]);
        return true;
    }

    /**
     * 获取我的足迹数量
     * @access public
     * @return array
     */
    public function getHistoryCount()
    {
        // 搜索条件
        $map['history.user_id'] = ['eq', get_client_id()];
        $totalResult = $this->with('getGoods')->where($map)->count();

        return ['total_result' => $totalResult];
    }

    /**
     * 获取我的足迹列表
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     * @throws
     */
    public function getHistoryList($data)
    {
        if (!$this->validateData($data, 'History.list')) {
            return false;
        }

        // 搜索条件
        $map['history.user_id'] = ['eq', get_client_id()];
        $totalResult = $this->with('getGoods')->where($map)->count();

        if ($totalResult <= 0) {
            return ['total_result' => 0];
        }

        $result = self::all(function ($query) use ($map, $data) {
            // 翻页页数
            $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

            // 每页条数
            $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'desc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'update_time';

            $query
                ->with('getGoods')
                ->where($map)
                ->order(['history.' . $orderField => $orderType])
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }
}