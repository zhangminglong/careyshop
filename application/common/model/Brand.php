<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    品牌模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/1
 */

namespace app\common\model;

use util\Phonetic;
use think\helper\Str;
use think\Cache;

class Brand extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'brand_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'brand_id'          => 'integer',
        'goods_category_id' => 'integer',
        'sort'              => 'integer',
        'status'            => 'integer',
    ];

    /**
     * 添加一个品牌
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addBrandItem($data)
    {
        if (!$this->validateData($data, 'Brand')) {
            return false;
        }

        // 避免无关字段
        unset($data['brand_id']);

        // 确认用户自定义或系统转换
        if (!isset($data['phonetic'])) {
            $data['phonetic'] = Phonetic::encode(Str::substr($data['name'], 0, 1));
            $data['phonetic'] = Str::lower($data['phonetic']);
        }

        if (false !== $this->allowField(true)->save($data)) {
            Cache::clear('Brand');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个品牌
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setBrandItem($data)
    {
        if (!$this->validateSetData($data, 'Brand.set')) {
            return false;
        }

        if (isset($data['name'])) {
            $map['brand_id'] = ['neq', $data['brand_id']];
            $map['name'] = ['eq', $data['name']];

            if (self::checkUnique($map)) {
                return $this->setError('品牌名称已存在');
            }

            // 确认用户自定义或系统转换
            if (!isset($data['phonetic'])) {
                $data['phonetic'] = Phonetic::encode(Str::substr($data['name'], 0, 1));
                $data['phonetic'] = Str::lower($data['phonetic']);
            }
        }

        $map = ['brand_id' => ['eq', $data['brand_id']]];
        if (false !== $this->allowField(true)->save($data, $map)) {
            Cache::clear('Brand');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除品牌
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delBrandList($data)
    {
        if (!$this->validateData($data, 'Brand.del')) {
            return false;
        }

        self::destroy($data['brand_id']);
        Cache::clear('Brand');

        return true;
    }

    /**
     * 批量设置品牌是否显示
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setBrandStatus($data)
    {
        if (!$this->validateData($data, 'Brand.status')) {
            return false;
        }

        $map['brand_id'] = ['in', $data['brand_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            Cache::clear('Brand');
            return true;
        }

        return false;
    }

    /**
     * 验证品牌名称是否唯一
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function uniqueBrandName($data)
    {
        if (!$this->validateData($data, 'Brand.unique')) {
            return false;
        }

        $map['name'] = ['eq', $data['name']];
        !isset($data['exclude_id']) ?: $map['ads_id'] = ['neq', $data['exclude_id']];

        if (self::checkUnique($map)) {
            return $this->setError('品牌名称已存在');
        }

        return true;
    }

    /**
     * 获取一个品牌
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getBrandItem($data)
    {
        if (!$this->validateData($data, 'Brand.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['brand_id'] = ['eq', $data['brand_id']];
            is_client_admin() ?: $map['status'] = ['eq', 1];

            $query->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取品牌列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getBrandList($data)
    {
        if (!$this->validateData($data, 'Brand.list')) {
            return false;
        }

        // 获取商品分类Id,包括子分类
        $catIdList = [];
        if (isset($data['goods_category_id'])) {
            if ($data['goods_category_id'] == 0) {
                $catIdList[] = 0;
            } else {
                $goodsCat = GoodsCategory::getCategoryList($data['goods_category_id'], false, true);
                $catIdList = array_column((array)$goodsCat, 'goods_category_id');
            }
        }

        // 搜索条件
        $map['b.status'] = ['eq', is_client_admin() && isset($data['status']) ? $data['status'] : 1];
        empty($data['name']) ?: $map['b.name'] = ['like', '%' . $data['name'] . '%'];
        empty($catIdList) ?: $map['b.goods_category_id'] = ['in', $catIdList];

        $totalResult = $this->cache(true, null, 'Brand')->alias('b')->where($map)->count();
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
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'brand_id';

            // 排序处理
            $order['b.sort'] = 'asc';
            $order['b.' . $orderField] = $orderType;

            $query
                ->cache(true, null, 'Brand')
                ->alias('b')
                ->field('b.*,ifnull(c.name, \'\') category_name,ifnull(c.alias, \'\') category_alias')
                ->join('goods_category c', 'c.status = 1 AND c.goods_category_id = b.goods_category_id', 'left')
                ->where($map)
                ->order($order)
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }

    /**
     * 获取品牌选择列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getBrandSelect($data)
    {
        if (!$this->validateData($data, 'Brand.select')) {
            return false;
        }

        $result = self::all(function ($query) use ($data) {
            $map['status'] = ['eq', 1];
            !isset($data['goods_category_id']) ?: $map['goods_category_id'] = ['in', $data['goods_category_id']];

            $query
                ->cache(true, null, 'Brand')
                ->field('goods_category_id,brand_id,name,phonetic,logo')
                ->where($map);
        });

        if (false !== $result) {
            return $result->toArray();
        }

        return false;
    }

    /**
     * 设置品牌排序
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setBrandSort($data)
    {
        if (!$this->validateData($data, 'Brand.sort')) {
            return false;
        }

        $map['brand_id'] = ['eq', $data['brand_id']];
        if (false !== $this->save(['sort' => $data['sort']], $map)) {
            return true;
        }

        return false;
    }
}