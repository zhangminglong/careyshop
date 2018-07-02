<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    广告位模型
 *
 * @author      zxm <252404501@qq.com>
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
        'platform'        => 'integer',
        'width'           => 'integer',
        'height'          => 'integer',
        'type'            => 'integer',
        'display'         => 'integer',
        'status'          => 'integer',
    ];

    /**
     * 添加一个广告位
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addPositionItem($data)
    {
        if (!$this->validateData($data, 'AdsPosition')) {
            return false;
        }

        // 避免无关字段
        unset($data['ads_position_id']);
        isset($data['content']) ?: $data['content'] = '';

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个广告位
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setPositionItem($data)
    {
        if (!$this->validateSetData($data, 'AdsPosition.set')) {
            return false;
        }

        if (!empty($data['code'])) {
            $map['ads_position_id'] = ['neq', $data['ads_position_id']];
            $map['code'] = ['eq', $data['code']];

            if (self::checkUnique($map)) {
                return $this->setError('广告位编码已存在');
            }
        }

        $result = self::get($data['ads_position_id']);
        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        if (isset($data['platform']) && $result->getAttr('platform') != $data['platform']) {
            $adsData['platform'] = $data['platform'];
        }

        if (isset($data['type']) && $result->getAttr('type') != $data['type']) {
            $adsData['type'] = $data['type'];
        }

        if (false !== $result->allowField(true)->save($data)) {
            if (!empty($adsData)) {
                Ads::update($adsData, ['ads_position_id' => ['eq', $data['ads_position_id']]]);
            }

            return $result->toArray();
        }

        return false;
    }

    /**
     * 批量删除广告位(支持检测是否存在关联广告)
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
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
                    ->field('p.ads_position_id,p.name')
                    ->join('ads a', 'a.ads_position_id = p.ads_position_id')
                    ->where(['p.ads_position_id' => ['in', $data['ads_position_id']]])
                    ->group('p.ads_position_id');
            });

            if ($result) {
                $error = 'Id:' . $result->getAttr('ads_position_id') . ' 广告位"';
                $error .= $result->getAttr('name') . '"存在关联广告';
                return $this->setError($error);
            }
        }

        self::destroy($data['ads_position_id']);

        return true;
    }

    /**
     * 验证广告位编号是否唯一
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function uniquePositionCode($data)
    {
        if (!$this->validateData($data, 'AdsPosition.unique')) {
            return false;
        }

        $map['code'] = ['eq', $data['code']];
        !isset($data['exclude_id']) ?: $map['ads_position_id'] = ['neq', $data['exclude_id']];

        if (self::checkUnique($map)) {
            return $this->setError('广告位编码已存在');
        }

        return true;
    }

    /**
     * 批量设置广告位状态
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

    /**
     * 获取一个广告位
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
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
     * @return array|false
     * @throws
     */
    public function getPositionList($data)
    {
        if (!$this->validateData($data, 'AdsPosition.list')) {
            return false;
        }

        // 搜索条件
        $map = [];
        empty($data['name']) ?: $map['name'] = ['like', '%' . $data['name'] . '%'];
        empty($data['code']) ?: $map['code'] = ['eq', $data['code']];
        !isset($data['platform']) ?: $map['platform'] = ['eq', $data['platform']];
        !isset($data['type']) ?: $map['type'] = ['eq', $data['type']];
        !isset($data['display']) ?: $map['display'] = ['eq', $data['display']];
        !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];

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
     * 获取广告位选择列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getPositionSelect($data)
    {
        if (!$this->validateData($data, 'AdsPosition.select')) {
            return false;
        }

        $result = self::all(function ($query) use ($data) {
            // 搜索条件
            $map = [];
            !isset($data['platform']) ?: $map['platform'] = ['eq', $data['platform']];
            !isset($data['type']) ?: $map['type'] = ['eq', $data['type']];
            !isset($data['display']) ?: $map['display'] = ['eq', $data['display']];
            !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];

            $query->field('description,width,height,content,color', true)->where($map);
        });

        if (false !== $result) {
            return $result->toArray();
        }

        return false;
    }

    /**
     * 根据广告位编码获取广告
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getPositionCode($data)
    {
        if (!$this->validateData($data, 'AdsPosition.code')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['code'] = ['eq', $data['code']];
            $map['status'] = ['eq', 1];

            $query
                ->field('code,description,status', true)
                ->where($map);
        });

        if (!$result) {
            return is_null($result) ? null : false;
        }

        $adsDb = new Ads();
        $adsResult = $adsDb::all(function ($query) use ($result) {
            $map['ads_position_id'] = ['eq', $result->getAttr('ads_position_id')];
            $map['begin_time'] = ['<= time', time()];
            $map['end_time'] = ['>= time', time()];
            $map['status'] = ['eq', 1];

            // 随机展示的广告没必要排序
            if (in_array($result->getAttr('display'), [0, 1])) {
                $query->order(['sort' => 'asc', 'ads_id' => 'desc']);
            }

            $query->field('ads_id,name,url,target,content,color')->where($map);
        });

        if (false === $adsResult) {
            return $this->setError($adsDb->getError());
        }

        // 从Ads数据集获取 0=多个 1=单个 2=随机多个 3=随机单个
        $adsData = $adsResult->toArray();
        if (!empty($adsData)) {
            switch ($result->getAttr('display')) {
                case 1:
                    $adsData = [array_shift($adsData)];
                    break;

                case 2:
                    shuffle($adsData);
                    break;

                case 3:
                    shuffle($adsData);
                    $adsData = [array_shift($adsData)];
                    break;
            }
        }

        $result->setAttr('ads_items', $adsData);
        return $result->toArray();
    }
}