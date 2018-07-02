<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    区域验证器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/27
 */

namespace app\common\model;

use think\Cache;
use think\Config;

class Region extends CareyShop
{
    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'is_delete',
    ];

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'region_id',
        'parent_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'region_id' => 'integer',
        'parent_id' => 'integer',
        'sort'      => 'integer',
        'is_delete' => 'integer',
    ];

    /**
     * 全局查询条件
     * @access protected
     * @param  object $query 模型
     * @return void
     */
    protected function base($query)
    {
        $query->where(['is_delete' => ['eq', 0]]);
    }

    /**
     * 获取区域缓存列表
     * @access public
     * @return array|false
     */
    public static function getRegionCacheList()
    {
        return self::useGlobalScope(false)
            ->cache('DeliveryArea')
            ->order(['sort', 'region_id'])
            ->column('region_id,parent_id,region_name,sort,is_delete', 'region_id');
    }

    /**
     * 添加一个区域
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addRegionItem($data)
    {
        if (!$this->validateData($data, 'Region')) {
            return false;
        }

        if (false !== $this->allowField(['parent_id', 'region_name', 'sort'])->save($data)) {
            Cache::rm('DeliveryArea');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个区域
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setRegionItem($data)
    {
        if (!$this->validateSetData($data, 'Region.set')) {
            return false;
        }

        $map['region_id'] = ['eq', $data['region_id']];
        if (false !== $this->allowField(true)->save($data, $map)) {
            Cache::rm('DeliveryArea');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除区域
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delRegionList($data)
    {
        if (!$this->validateData($data, 'Region.del')) {
            return false;
        }

        $map['region_id'] = ['in', $data['region_id']];
        if (false !== $this->save(['is_delete' => 1], $map)) {
            Cache::rm('DeliveryArea');
            return true;
        }

        return false;
    }

    /**
     * 获取指定区域
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getRegionItem($data)
    {
        if (!$this->validateData($data, 'Region.item')) {
            return false;
        }

        // 是否提取已删除区域
        $scope = isset($data['region_all']) ? !$data['region_all'] : true;
        $result = self::useGlobalScope($scope)->where(['region_id' => ['eq', $data['region_id']]])->find();

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取指定Id下的子节点(不包含本身)
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getRegionList($data)
    {
        if (!$this->validateData($data, 'Region.list')) {
            return false;
        }

        // 是否提取已删除区域
        $scope = isset($data['region_all']) ? !$data['region_all'] : true;
        $map['parent_id'] = isset($data['region_id']) ? ['eq', $data['region_id']] : ['eq', 0];
        $result = self::useGlobalScope($scope)->where($map)->order(['sort', 'region_id'])->select();

        if (false !== $result) {
            return $result->toArray();
        }

        return false;
    }

    /**
     * 获取指定Id下的所有子节点(包含本身)
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     */
    public function getRegionSonList($data)
    {
        if (!$this->validateData($data, 'Region.son')) {
            return false;
        }

        // 是否提取已删除区域
        $isDelete = isset($data['region_all']) ? (bool)$data['region_all'] : false;
        $regionList = self::getRegionCacheList();

        static $result = [];
        $data['region_id'] = array_unique($data['region_id']);

        foreach ($data['region_id'] as $value) {
            if (!isset($regionList[$value])) {
                continue;
            }

            if (!$isDelete && $regionList[$value]['is_delete'] == 1) {
                continue;
            }

            unset($regionList[$value]['is_delete']);
            $result[] = $regionList[$value];

            self::getRegionChildrenList($value, $result, $regionList, $isDelete);
        }

        return $result;
    }

    /**
     * 过滤和排序所有区域
     * @access private
     * @param  int   $id    上级区域Id
     * @param  array &$tree 树结构
     * @param  array &$list 原始数据结构
     * @param  bool  $isAll 是否提取已删除区域
     * @return void
     */
    private static function getRegionChildrenList($id, &$tree, &$list, $isAll)
    {
        static $keyList = null;
        if (is_null($keyList)) {
            $keyList = array_column($list, 'parent_id', 'parent_id');
        }

        foreach ($list as $value) {
            if ($value['parent_id'] != $id) {
                continue;
            }

            if (!$isAll && $value['is_delete'] == 1) {
                continue;
            }

            unset($value['is_delete']);
            $tree[] = $value;

            if ($value['region_id'] != 0 && isset($keyList[$value['region_id']])) {
                self::getRegionChildrenList($value['region_id'], $tree, $list, $isAll);
            }
        }
    }

    /**
     * 设置区域排序
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setRegionSort($data)
    {
        if (!$this->validateData($data, 'Region.sort')) {
            return false;
        }

        $map['region_id'] = ['eq', $data['region_id']];
        if (false !== $this->save(['sort' => $data['sort']], $map)) {
            Cache::rm('DeliveryArea');
            return true;
        }

        return false;
    }

    /**
     * 根据区域编号获取区域名称
     * @access public
     * @param  array $data 外部数据
     * @return string
     */
    public function getRegionName($data)
    {
        if (!$this->validateData($data, 'Region.name')) {
            return false;
        }

        $map['region_id'] = ['in', $data['region_id']];
        $result = $this->where($map)->column('region_name', 'region_id');

        // 根据用户输入的顺序返回
        $name = [];
        foreach ($data['region_id'] as $value) {
            !isset($result[$value]) ?: $name[] = $result[$value];
        }

        return implode(Config::get('spacer.value', 'system_shopping'), $name);
    }
}