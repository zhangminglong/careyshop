<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    广告位模型
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/3/29
 */

namespace app\common\model;

class AdsPosition extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'ads_position_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'ads_position_id' => 'integer',
        'width'           => 'integer',
        'height'          => 'integer',
        'status'          => 'integer',
    ];

    /**
     * 添加一个广告位置
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function addPositionItem($data)
    {
        if (!$this->validateData($data, 'AdsPosition')) {
            return false;
        }

        // 避免无关字段
        unset($data['ads_position_id']);

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个广告位置
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function setPositionItem($data)
    {
        if (!$this->validateSetData($data, 'AdsPosition.set')) {
            return false;
        }

        if (isset($data['position_name'])) {
            $map['ads_position_id'] = ['neq', $data['ads_position_id']];
            $map['position_name'] = ['eq', $data['position_name']];

            if (self::checkUnique($map)) {
                return $this->setError('广告位置名称已存在');
            }
        }

        $map = ['ads_position_id' => ['eq', $data['ads_position_id']]];
        if (false !== $this->allowField(true)->save($data, $map)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除广告位置(支持检测是否存在关联广告)
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delPositionList($data)
    {
        if (!$this->validateData($data, 'AdsPosition.del')) {
            return false;
        }

        // 检测是否存在关联广告
        if (isset($data['not_empty']) && $data['not_empty'] == 1) {
            $result = self::get(function ($query) use ($data) {
                $query
                    ->alias('p')
                    ->field('p.ads_position_id,p.position_name')
                    ->join('ads a', 'a.ads_position_id = p.ads_position_id')
                    ->where(['p.ads_position_id' => ['in', $data['ads_position_id']]])
                    ->group('p.ads_position_id');
            });

            if ($result) {
                $error = 'Id:' . $result->getAttr('ads_position_id') . ' 广告位置"';
                $error .= $result->getAttr('position_name') . '"存在关联广告';
                return $this->setError($error);
            }
        }

        self::destroy($data['ads_position_id']);

        return true;
    }

    /**
     * 验证广告位置名称是否唯一
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function uniquePositionName($data)
    {
        if (!$this->validateData($data, 'AdsPosition.unique')) {
            return false;
        }

        $map['position_name'] = ['eq', $data['position_name']];
        !isset($data['exclude_id']) ?: $map['ads_position_id'] = ['neq', $data['exclude_id']];

        if (self::checkUnique($map)) {
            return $this->setError('广告位置名称已存在');
        }

        return true;
    }

    /**
     * 获取一个广告位置
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function getPositionItem($data)
    {
        if (!$this->validateData($data, 'AdsPosition.item')) {
            return false;
        }

        $result = self::get($data['ads_position_id']);
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取广告位置列表
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function getPositionList($data)
    {
        if (!$this->validateData($data, 'AdsPosition.list')) {
            return false;
        }

        // 搜索条件
        $map['status'] = ['eq', 1];

        // 后台管理搜索
        if (is_client_admin()) {
            unset($map['status']);
            !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];
            empty($data['position_name']) ?: $map['position_name'] = ['like', '%' . $data['position_name'] . '%'];
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
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'ads_position_id';

            $query->where($map)->order([$orderField => $orderType])->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }

    /**
     * 批量设置广告位置状态
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setPositionStatus($data)
    {
        if (!$this->validateData($data, 'AdsPosition.status')) {
            return false;
        }

        $map['ads_position_id'] = ['in', $data['ads_position_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            return true;
        }

        return false;
    }
}