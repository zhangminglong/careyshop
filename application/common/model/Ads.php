<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    广告模型
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/3/29
 */

namespace app\common\model;

class Ads extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'ads_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'ads_id'          => 'integer',
        'ads_position_id' => 'integer',
        'type'            => 'integer',
        'begin_time'      => 'timestamp',
        'end_time'        => 'timestamp',
        'sort'            => 'integer',
        'status'          => 'integer',
    ];

    /**
     * hasOne cs_ads_position
     * @access public
     * @return $this
     */
    public function getAdsPosition()
    {
        return $this
            ->hasOne('AdsPosition', 'ads_position_id', 'ads_position_id')
            ->field('ads_position_id,position_name')
            ->setEagerlyType(0);
    }

    /**
     * 添加一个广告
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function addAdsItem($data)
    {
        if (!$this->validateData($data, 'Ads')) {
            return false;
        }

        // 避免无关字段
        unset($data['ads_id']);

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个广告
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function setAdsItem($data)
    {
        if (!$this->validateSetData($data, 'Ads.set')) {
            return false;
        }

        $map['ads_id'] = ['eq', $data['ads_id']];
        if (false !== $this->allowField(true)->save($data, $map)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除广告
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delAdsList($data)
    {
        if (!$this->validateData($data, 'Ads.del')) {
            return false;
        }

        self::destroy($data['ads_id']);

        return true;
    }

    /**
     * 设置广告排序
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setAdsSort($data)
    {
        if (!$this->validateData($data, 'Ads.sort')) {
            return false;
        }

        $map['ads_id'] = ['eq', $data['ads_id']];
        if (false !== $this->save(['sort' => $data['sort']], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 批量设置是否显示
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setAdsStatus($data)
    {
        if (!$this->validateData($data, 'Ads.status')) {
            return false;
        }

        $map['ads_id'] = ['in', $data['ads_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 获取一个广告
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function getAdsItem($data)
    {
        if (!$this->validateData($data, 'Ads.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['ads_id'] = ['eq', $data['ads_id']];
            is_client_admin() ?: $map['status'] = ['eq', 1];

            $query->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取广告列表
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function getAdsList($data)
    {
        if (!$this->validateData($data, 'Ads.list')) {
            return false;
        }

        // 搜索条件
        $map = [];
        !isset($data['type']) ?: $map['ads.type'] = ['eq', $data['type']];
        !isset($data['ads_position_id']) ?: $map['ads.ads_position_id'] = ['eq', $data['ads_position_id']];

        // 区分前后台需求
        if (!is_client_admin()) {
            $map['ads.status'] = ['eq', 1];
            $map['getAdsPosition.status'] = ['eq', 1];
            $map['ads.begin_time'] = ['<= time', time()];
            $map['ads.end_time'] = ['>= time', time()];
        } else {
            !isset($data['status']) ?: $map['ads.status'] = ['eq', $data['status']];
            !isset($data['status']) ?: $map['getAdsPosition.status'] = ['eq', $data['status']];
            empty($data['name']) ?: $map['ads.name'] = ['like', '%' . $data['name'] . '%'];
            empty($data['begin_time']) ?: $map['ads.begin_time'] = ['< time', $data['end_time']];
            empty($data['end_time']) ?: $map['ads.end_time'] = ['> time', $data['begin_time']];
        }

        $totalResult = $this->with('getAdsPosition')->where($map)->count();
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
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'ads_id';

            // 排序处理
            $order['ads.sort'] = 'asc';
            $order['ads.' . $orderField] = $orderType;

            $query
                ->field('ads_position_id,code', true)
                ->with('getAdsPosition')
                ->where($map)
                ->order($order)
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }
}