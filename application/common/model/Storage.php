<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    资源管理模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/1/10
 */

namespace app\common\model;

use app\common\service\Upload;
use think\Cache;

class Storage extends CareyShop
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
        'storage_id',
        'hash'
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'storage_id' => 'integer',
        'parent_id'  => 'integer',
        'size'       => 'integer',
        'type'       => 'integer',
        'sort'       => 'integer',
        'pixel'      => 'array',
        'is_default' => 'integer',
    ];

    /**
     * 添加一个资源目录
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addStorageDirectoryItem($data)
    {
        if (!$this->validateData($data, 'Storage.add_directory')) {
            return false;
        }

        // 初始化数据
        $data['type'] = 2;
        $data['protocol'] = '';

        if (false !== $this->allowField(['parent_id', 'name', 'type', 'protocol', 'sort'])->save($data)) {
            Cache::clear('StorageDirectory');
            return $this->hidden(['protocol'])->toArray();
        }

        return false;
    }

    /**
     * 编辑一个资源目录
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setStorageDirectoryItem($data)
    {
        if (!$this->validateSetData($data, 'Storage.set_directory')) {
            return false;
        }

        $map['storage_id'] = ['eq', $data['storage_id']];
        $map['type'] = ['eq', 2];

        if (false !== $this->allowField(['name', 'sort'])->save($data, $map)) {
            Cache::clear('StorageDirectory');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 获取资源目录选择列表
     * @access public
     * @param  array $data  外部数据
     * @param  bool  $isKey 是否以键名为索引
     * @return array|false
     * @throws
     */
    public function getStorageDirectorySelect($data, $isKey = false)
    {
        if (!$this->validateData($data, 'Storage.list_directory')) {
            return false;
        }

        // 排序方式与排序字段
        $orderType = !empty($data['order_type']) ? $data['order_type'] : 'asc';
        $orderField = !empty($data['order_field']) ? $data['order_field'] : 'storage_id';
        $order['sort'] = 'asc';
        $order[$orderField] = $orderType;

        // 获取实际数据
        $result = $this
            ->cache(true, null, 'StorageDirectory')
            ->field(['storage_id', 'parent_id', 'name', 'cover', 'sort', 'is_default'])
            ->where(['type' => ['eq', 2]])
            ->order($order)
            ->select();

        if (false !== $result) {
            return $isKey ? $result->column(null, 'storage_id') : $result->toArray();
        }

        return false;
    }

    /**
     * 将资源目录标设为默认目录
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setStorageDirectoryDefault($data)
    {
        if (!$this->validateData($data, 'Storage.item')) {
            return false;
        }

        $map['type'] = ['eq', 2];
        if (false === $this->save(['is_default' => 0], $map)) {
            return false;
        }

        $map['storage_id'] = ['eq', $data['storage_id']];
        if (false === $this->save(['is_default' => 1], $map)) {
            return false;
        }

        Cache::clear('StorageDirectory');
        return true;
    }

    /**
     * 获取一个资源或资源目录
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getStorageItem($data)
    {
        if (!$this->validateData($data, 'Storage.item')) {
            return false;
        }

        $result = self::get($data['storage_id']);
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取资源列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getStorageList($data)
    {
        if (!$this->validateData($data, 'Storage.list')) {
            return false;
        }

        // 初始化数据
        $data['storage_id'] = isset($data['storage_id']) ? $data['storage_id'] : 0;

        // 搜索条件
        $map = [];
        $map['parent_id'] = ['eq', $data['storage_id']];

        if (!empty($data['name'])) {
            $map['name'] = ['like', '%' . $data['name'] . '%'];
            unset($map['parent_id']);
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
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'asc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'storage_id';

            // 排序处理
            $order['type'] = 'desc';
            $order[$orderField] = $orderType;

            $query
                ->field('is_default', true)
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
     * 获取导航数据
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     */
    public function getStorageNavi($data)
    {
        if (!$this->validateData($data, 'Storage.navi')) {
            return false;
        }

        if (empty($data['storage_id'])) {
            return [];
        }

        $map['type'] = ['eq', 2];
        $list = self::cache('StorageNavi', null, 'StorageDirectory')->where($map)->column('storage_id,parent_id,name');

        if ($list === false) {
            Cache::clear('StorageDirectory');
            return false;
        }

        $isLayer = isset($data['is_layer']) ? (bool)$data['is_layer'] : true;
        if (!$isLayer && isset($list[$data['storage_id']])) {
            $data['storage_id'] = $list[$data['storage_id']]['parent_id'];
        }

        $result = [];
        while (true) {
            if (!isset($list[$data['storage_id']])) {
                break;
            }

            $result[] = $list[$data['storage_id']];

            if ($list[$data['storage_id']]['parent_id'] <= 0) {
                break;
            }

            $data['storage_id'] = $list[$data['storage_id']]['parent_id'];
        }

        return array_reverse($result);
    }

    /**
     * 重命名一个资源
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function renameStorageItem($data)
    {
        if (!$this->validateData($data, 'Storage.rename')) {
            return false;
        }

        $map['storage_id'] = ['eq', $data['storage_id']];
        if (false !== $this->save(['name' => $data['name']], $map)) {
            Cache::clear('StorageDirectory');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 将图片资源设为目录封面
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function coverStorageItem($data)
    {
        if (!$this->validateData($data, 'Storage.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['storage_id'] = ['eq', $data['storage_id']];
            $map['type'] = ['eq', 0];

            $query->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('资源图片不存在') : false;
        }

        $coverMap['storage_id'] = ['eq', $result->getAttr('parent_id')];
        $coverMap['type'] = ['eq', 2];

        if (false !== $this->save(['cover' => $result->getAttr('url')], $coverMap)) {
            Cache::clear('StorageDirectory');
            return true;
        }

        return false;
    }

    /**
     * 根据指定资源编号获取不允许移动的目录树(返回自身)
     * @access private
     * @param  int   $parentId 上级资源目录Id
     * @param  array $list     原始数据结构
     * @return array
     */
    private static function getNotAllowedMoveTree($parentId, $list)
    {
        static $tree = [];
        foreach ($list as $key => $value) {
            if ($parentId != $value['parent_id'] && $parentId != $value['storage_id']) {
                continue;
            }

            // 返回的数据只能通过键名比较,键值无任何效果
            $tree[$value['storage_id']] = 0;
            $tree[$value['parent_id']] = 0;

            //返回自身时需要保留源数据,否则引起树的重复
            if ($parentId == $value['storage_id']) {
                continue;
            }

            unset($list[$key]);
            self::getNotAllowedMoveTree($value['storage_id'], $list);
        }

        return $tree;
    }

    /**
     * 验证资源是否允许移动到指定目录
     * @access public
     * @param  array $storageIdList 待移动资源编号集
     * @param  int   $parentId      上级资源编号
     * @return bool
     */
    public function isMoveStorage($storageIdList, $parentId)
    {
        $result = $this->getStorageDirectorySelect([], true);
        if (false === $result) {
            return false;
        }

        if ($parentId != 0 && !array_key_exists($parentId, $result)) {
            return $this->setError('上级资源目录不存在');
        }

        // 验证资源是否允许移动
        $unmovable = self::getNotAllowedMoveTree($parentId, $result);
        do {
            $key = key($unmovable);
            if (false === $key) {
                return false;
            }

            $findId = array_search($key, $storageIdList);
            if (false !== $findId) {
                return $this->setError($result[$storageIdList[$findId]]['name'] . '不允许移动到同源资源目录下');
            }
        } while (false !== next($unmovable));

        return true;
    }

    /**
     * 批量移动资源到指定目录
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function moveStorageList($data)
    {
        if (!$this->validateData($data, 'Storage.move')) {
            return false;
        }

        $data['storage_id'] = array_unique($data['storage_id']);
        $result = $this->isMoveStorage($data['storage_id'], $data['parent_id']);

        if (true !== $result) {
            return false;
        }

        $map['storage_id'] = ['in', $data['storage_id']];
        if (false !== $this->save(['parent_id' => $data['parent_id']], $map)) {
            Cache::clear('StorageDirectory');
            return true;
        }

        return false;
    }

    /**
     * 批量删除资源
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function delStorageList($data)
    {
        if (!$this->validateData($data, 'Storage.del')) {
            return false;
        }

        // 数组转为字符串格式,用于SQL查询条件,为空直接返回
        $data['storage_id'] = array_unique($data['storage_id']);
        $data['storage_id'] = implode(',', $data['storage_id']);

        if (empty($data['storage_id'])) {
            return true;
        }

        // 获取子节点资源
        $storageId = $this->query('SELECT `getStorageChildrenList`(:storage_id) AS `storage_id`', $data);
        if (false === $storageId) {
            return false;
        }

        // 获取所有资源数据(不可使用FIND_IN_SET查询,不走索引,效率极低)
        $result = $this->where(['storage_id' => ['in', $storageId[0]['storage_id']]])->select();
        if ($result->isEmpty()) {
            return true;
        }

        $delDirId = [];
        $result = $result->toArray();
        $ossObjectList = new \StdClass();

        foreach ($result as $value) {
            // 如果是资源目录则加入待删除列表
            if ($value['type'] == 2) {
                $delDirId[] = $value['storage_id'];
                continue;
            }

            if ($value['type'] != 2 && !empty($value['protocol'])) {
                if (!isset($ossObjectList->oss[$value['protocol']])) {
                    $ossObject = new Upload();
                    $ossObjectList->oss[$value['protocol']] = $ossObject->createOssObject($value['protocol']);

                    if (false === $ossObjectList->oss[$value['protocol']]) {
                        return $this->setError($ossObject->getError());
                    }
                }

                $ossObjectList->oss[$value['protocol']]->addDelFile($value['path']);
                $ossObjectList->oss[$value['protocol']]->addDelFileId($value['storage_id']);
            }
        }

        // 开启事务
        self::startTrans();

        try {
            if (isset($ossObjectList->oss)) {
                foreach ($ossObjectList->oss as $item) {
                    // 删除OSS物理资源
                    if (false === $item->delFileList()) {
                        throw new \Exception($item->getError());
                    }

                    // 删除资源记录
                    $this->where(['storage_id' => ['in', $item->getDelFileIdList()]])->delete();
                }
            }

            // 删除资源目录记录
            if (!empty($delDirId)) {
                $this->where(['storage_id' => ['in', $delDirId]])->delete();
            }

            self::commit();
            Cache::clear('StorageDirectory');
            return true;
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }
}