<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品分类模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/1
 */

namespace app\common\model;

use think\Cache;
use think\helper\Str;
use util\Phonetic;

class GoodsCategory extends CareyShop
{
    /**
     * 分类树
     * @var int
     */
    private static $tree = [];

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'goods_category_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'goods_category_id' => 'integer',
        'parent_id'         => 'integer',
        'category_type'     => 'integer',
        'sort'              => 'integer',
        'is_navi'           => 'integer',
        'status'            => 'integer',
    ];

    /**
     * 添加一个商品分类
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addCategoryItem($data)
    {
        if (!$this->validateData($data, 'GoodsCategory')) {
            return false;
        }

        // 避免无关字段
        unset($data['goods_category_id']);

        // 识别并转换分类名称首拼
        if (!isset($data['name_phonetic'])) {
            $data['name_phonetic'] = Phonetic::encode(Str::substr($data['name'], 0, 1));
            $data['name_phonetic'] = Str::lower($data['name_phonetic']);
        }

        // 识别并转换分类别名首拼
        if (!empty($data['alias']) && !isset($data['alias_phonetic'])) {
            $data['alias_phonetic'] = Phonetic::encode(Str::substr($data['alias'], 0, 1));
            $data['alias_phonetic'] = Str::lower($data['alias_phonetic']);
        }

        if (false !== $this->allowField(true)->save($data)) {
            Cache::clear('GoodsCategory');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个商品分类
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setCategoryItem($data)
    {
        if (!$this->validateSetData($data, 'GoodsCategory.set')) {
            return false;
        }

        // 父分类不能设置成本身或本身的子分类
        if (isset($data['parent_id'])) {
            if ($data['parent_id'] == $data['goods_category_id']) {
                return $this->setError('上级分类不能设为自身');
            }

            if (($result = self::getCategoryList($data['goods_category_id'])) === false) {
                return false;
            }

            foreach ($result as $value) {
                if ($data['parent_id'] == $value['goods_category_id']) {
                    return $this->setError('上级分类不能设为自身的子分类');
                }
            }
        }

        if (!empty($data['name']) && !isset($data['name_phonetic'])) {
            $data['name_phonetic'] = Phonetic::encode(Str::substr($data['name'], 0, 1));
            $data['name_phonetic'] = Str::lower($data['name_phonetic']);
        }

        if (!empty($data['alias']) && !isset($data['alias_phonetic'])) {
            $data['alias_phonetic'] = Phonetic::encode(Str::substr($data['alias'], 0, 1));
            $data['alias_phonetic'] = Str::lower($data['alias_phonetic']);
        }

        $map['goods_category_id'] = ['eq', $data['goods_category_id']];
        if (false !== $this->allowField(true)->save($data, $map)) {
            Cache::clear('GoodsCategory');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除商品分类(支持检测是否存在子节点与关联商品)
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delCategoryList($data)
    {
        if (!$this->validateData($data, 'GoodsCategory.del')) {
            return false;
        }

        $idList = $result = [];
        isset($data['not_empty']) ?: $data['not_empty'] = 0;

        if (1 == $data['not_empty']) {
            $idList = $data['goods_category_id'];
            if (($result = self::getCategoryList(0, true)) === false) {
                return false;
            }
        }

        // 过滤不需要的分类
        $catFilter = [];
        foreach ($result as $value) {
            if ($value['children_total'] > 0 || $value['goods_total'] > 0) {
                $catFilter[$value['goods_category_id']] = $value;
            }
        }

        foreach ($idList as $catId) {
            if (array_key_exists($catId, $catFilter)) {
                if ($catFilter[$catId]['children_total'] > 0) {
                    return $this->setError('Id:' . $catId . ' 分类名称"' . $catFilter[$catId]['name'] . '"存在子分类');
                }

                if ($catFilter[$catId]['goods_total'] > 0) {
                    return $this->setError('Id:' . $catId . ' 分类名称"' . $catFilter[$catId]['name'] . '"存在关联商品');
                }
            }
        }

        self::destroy($data['goods_category_id']);
        Cache::clear('GoodsCategory');

        return true;
    }

    /**
     * 获取一个商品分类
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getCategoryItem($data)
    {
        if (!$this->validateData($data, 'GoodsCategory.item')) {
            return false;
        }

        $result = self::get($data['goods_category_id']);
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取分类导航数据
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     */
    public function getCategoryNavi($data)
    {
        if (!$this->validateData($data, 'GoodsCategory.navi')) {
            return false;
        }

        if (empty($data['goods_category_id'])) {
            return [];
        }

        $catList = $this
            ->cache('GoodsCategoryNavi', null, 'GoodsCategory')
            ->order('sort,goods_category_id')
            ->column('goods_category_id,parent_id,name,alias');

        if (false === $catList) {
            Cache::clear('GoodsCategory');
            return false;
        }

        $isLayer = isset($data['is_layer']) ? (bool)$data['is_layer'] : true;
        if (!$isLayer && isset($catList[$data['goods_category_id']])) {
            $data['goods_category_id'] = $catList[$data['goods_category_id']]['parent_id'];
        }

        $result = [];
        for ($i = 0; true; $i++) {
            if (!isset($catList[$data['goods_category_id']])) {
                break;
            }

            $result[$i] = $catList[$data['goods_category_id']];
            if (!empty($data['is_same_level'])) {
                foreach ($catList as $key => $value) {
                    if ($result[$i]['goods_category_id'] == $key) {
                        continue;
                    }

                    if ($value['parent_id'] == $result[$i]['parent_id']) {
                        // 既然是同级,那么就没必要再返回父级Id
                        unset($value['parent_id']);
                        $result[$i]['same_level'][] = $value;
                    }
                }
            }

            if ($catList[$data['goods_category_id']]['parent_id'] <= 0) {
                break;
            }

            $data['goods_category_id'] = $catList[$data['goods_category_id']]['parent_id'];
        }

        // 导航需要反转的顺序返回
        return array_reverse($result);
    }

    /**
     * 批量设置是否显示
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setCategoryStatus($data)
    {
        if (!$this->validateData($data, 'GoodsCategory.status')) {
            return false;
        }

        $map['goods_category_id'] = ['in', $data['goods_category_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            Cache::clear('GoodsCategory');
            return true;
        }

        return false;
    }

    /**
     * 过滤和排序所有商品分类
     * @access private
     * @param  int    $parentId   上级分类Id
     * @param  object &$list      原始模型对象
     * @param  int    $limitLevel 显示多少级深度 null:全部
     * @param  bool   $isLayer    是否返回本级分类
     * @param  int    $level      分类深度
     * @return array
     */
    private static function setCategoryTree($parentId, &$list, $limitLevel = null, $isLayer = false, $level = 0)
    {
        $parentId != 0 ?: $isLayer = false; // 返回全部分类不需要本级
        foreach ($list as $key => $value) {
            // 获取分类主Id
            $goodsCategoryId = $value->getAttr('goods_category_id');
            if ($value->getAttr('parent_id') !== $parentId && $goodsCategoryId !== $parentId) {
                continue;
            }

            // 是否返回本级分类
            if ($goodsCategoryId === $parentId && false == $isLayer) {
                continue;
            }

            // 限制分类显示深度
            if (!is_null($limitLevel) && $level > $limitLevel) {
                break;
            }

            $value->setAttr('level', $level);
            self::$tree[] = $value->toArray();

            // 需要返回本级分类时保留列表数据,否则引起树的重复,并且需要自增层级
            if (true == $isLayer) {
                $isLayer = false;
                $level++;
                continue;
            }

            // 删除已使用数据,减少查询次数
            unset($list[$key]);

            if ($value->getAttr('children_total') > 0) {
                self::setCategoryTree($goodsCategoryId, $list, $limitLevel, $isLayer, $level + 1);
            }
        }

        return self::$tree;
    }

    /**
     * 获取所有商品分类
     * @access public
     * @param  int  $catId        分类Id
     * @param  bool $isGoodsTotal 是否获取关联商品数
     * @param  bool $isLayer      是否返回本级分类
     * @param  int  $level        分类深度
     * @return false|array
     * @throws
     */
    public static function getCategoryList($catId = 0, $isGoodsTotal = false, $isLayer = false, $level = null)
    {
        // 获取商品全部分类
        $result = self::all(function ($query) use ($isGoodsTotal) {
            // 搜索条件
            $map = [];
            $joinMap = '';

            if (!is_client_admin()) {
                $map['c.status'] = ['eq', 1];
                $joinMap = ' AND s.status = ' . 1;
            }

            $goodsSql = $goodsTotal = '';
            if ($isGoodsTotal) {
                $goodsTotal = ',ifnull(g.num, 0) goods_total';
                $goodsSql = Goods::field('goods_category_id,count(*) num')
                    ->where('is_delete', 0)
                    ->group('goods_category_id')
                    ->buildSql();
            }

            $query
                ->alias('c')
                ->field('c.*,count(s.goods_category_id) children_total' . $goodsTotal)
                ->join('goods_category s', 's.parent_id = c.goods_category_id' . $joinMap, 'left');

            if ($isGoodsTotal) {
                $query->join([$goodsSql => 'g'], 'g.goods_category_id = c.goods_category_id', 'left');
            }

            $query
                ->where($map)
                ->group('c.goods_category_id')
                ->order('c.parent_id,c.sort,c.goods_category_id')
                ->cache(true, null, 'GoodsCategory');
        });

        if (false === $result) {
            Cache::clear('GoodsCategory');
            return false;
        }

        // 缓存名称
        $treeCache = sprintf('GoodsCat:admin%dtotal%d', is_client_admin(), $isGoodsTotal);
        $treeCache .= sprintf('id%dis_layer%dlevel%d', $catId, $isLayer, is_null($level) ? -1 : $level);

        if (Cache::has($treeCache)) {
            return Cache::get($treeCache);
        }

        self::$tree = [];
        $tree = self::setCategoryTree((int)$catId, $result, $level, $isLayer);
        Cache::tag('GoodsCategory')->set($treeCache, $tree);

        return $tree;
    }

    /**
     * 根据主Id集合获取所有子级
     * @access public
     * @param  array $data 外部数据
     * @return array
     */
    public static function getCategorySon($data)
    {
        if (empty($data['goods_category_id'])) {
            return [];
        }

        $level = isset($data['level']) ? $data['level'] : null;
        $isGoodsTotal = isset($data['goods_total']) ? $data['goods_total'] : false;
        $isLayer = isset($data['is_layer']) ? $data['is_layer'] : true;

        $result = [];
        foreach ($data['goods_category_id'] as $value) {
            self::$tree = [];
            $list = self::getCategoryList($value, $isGoodsTotal, $isLayer, $level);

            if ($list) {
                $result = array_merge($result, $list);
            }
        }

        return $result;
    }

    /**
     * 设置商品分类排序
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setCategorySort($data)
    {
        if (!$this->validateData($data, 'GoodsCategory.sort')) {
            return false;
        }

        $map['goods_category_id'] = ['eq', $data['goods_category_id']];
        if (false !== $this->save(['sort' => $data['sort']], $map)) {
            Cache::clear('GoodsCategory');
            return true;
        }

        return false;
    }

    /**
     * 批量设置是否导航
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setCategoryNavi($data)
    {
        if (!$this->validateData($data, 'GoodsCategory.nac')) {
            return false;
        }

        $map['goods_category_id'] = ['in', $data['goods_category_id']];
        if (false !== $this->save(['is_navi' => $data['is_navi']], $map)) {
            Cache::clear('GoodsCategory');
            return true;
        }

        return false;
    }
}