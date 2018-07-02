<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品模型模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/7
 */

namespace app\common\model;

class GoodsType extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'goods_type_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'goods_type_id' => 'integer',
    ];

    /**
     * 添加一个商品模型
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addTypeItem($data)
    {
        if (!$this->validateData($data, 'GoodsType')) {
            return false;
        }

        // 避免无关字段
        unset($data['goods_type_id']);

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个商品模型
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setTypeItem($data)
    {
        if (!$this->validateSetData($data, 'GoodsType.set')) {
            return false;
        }

        if (isset($data['type_name'])) {
            $map['goods_type_id'] = ['neq', $data['goods_type_id']];
            $map['type_name'] = ['eq', $data['type_name']];

            if (self::checkUnique($map)) {
                return $this->setError('商品模型名称已存在');
            }
        }

        $map = ['goods_type_id' => ['eq', $data['goods_type_id']]];
        if (false !== $this->allowField(true)->save($data, $map)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 查询商品模型名称是否已存在
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function uniqueTypeName($data)
    {
        if (!$this->validateData($data, 'GoodsType.unique')) {
            return false;
        }

        $map['type_name'] = ['eq', $data['type_name']];
        !isset($data['exclude_id']) ?: $map['goods_type_id'] = ['neq', $data['exclude_id']];

        if (self::checkUnique($map)) {
            return $this->setError('商品模型名称已存在');
        }

        return true;
    }

    /**
     * 获取一个商品模型
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getTypeItem($data)
    {
        if (!$this->validateData($data, 'GoodsType.item')) {
            return false;
        }

        $result = self::get($data['goods_type_id']);
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取商品模型选择列表
     * @access public
     * @return array|false
     * @throws
     */
    public function getTypeSelect()
    {
        // 获取商品模型列表
        $result = self::all();
        if (false !== $result) {
            return $result->toArray();
        }

        return false;
    }

    /**
     * 获取商品模型列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getTypeList($data)
    {
        if (!$this->validateData($data, 'GoodsType.list')) {
            return false;
        }

        // 搜索条件
        $map = [];
        empty($data['type_name']) ?: $map['type_name'] = ['like', '%' . $data['type_name'] . '%'];

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
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'goods_type_id';

            $query->where($map)->order([$orderField => $orderType])->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }

    /**
     * 批量删除商品模型
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function delTypeList($data)
    {
        if (!$this->validateData($data, 'GoodsType.del')) {
            return false;
        }

        // 检测商品模型是否存在关联,存在则不允许删除
        $result = self::all(function ($query) use ($data) {
            $attribute = GoodsAttribute::field('goods_type_id, count(*) num')
                ->group('goods_type_id')
                ->buildSql();

            $spec = Spec::field('goods_type_id, count(*) num')
                ->group('goods_type_id')
                ->buildSql();

            $query
                ->alias('t')
                ->field('t.*, ifnull(a.num, 0) attribute_total, ifnull(s.num, 0) spec_total')
                ->join([$attribute => 'a'], 'a.goods_type_id = t.goods_type_id', 'left')
                ->join([$spec => 's'], 's.goods_type_id = t.goods_type_id', 'left')
                ->whereIn('t.goods_type_id', $data['goods_type_id']);
        });

        if (!$result) {
            return true;
        }

        foreach ($result as $value) {
            $typeId = $value->getAttr('goods_type_id');
            $typeName = $value->getAttr('type_name');

            if ($value->getAttr('attribute_total') > 0) {
                return $this->setError('Id:' . $typeId . ' 模型名称"' . $typeName . '"存在商品属性');
            }

            if ($value->getAttr('spec_total') > 0) {
                return $this->setError('Id:' . $typeId . ' 模型名称"' . $typeName . '"存在商品规格');
            }
        }

        self::destroy($data['goods_type_id']);

        return true;
    }
}