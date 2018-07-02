<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品规格项模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/18
 */

namespace app\common\model;

class SpecItem extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'spec_item_id',
        'spec_id',
    ];

    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'spec_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'spec_item_id' => 'integer',
        'spec_id'      => 'integer',
    ];

    /**
     * 删除或插入商品规格项
     * @access public static
     * @param  int   $specId 商品规格Id
     * @param  array $item   外部提交数据
     * @return bool
     */
    public static function updataItem($specId, $item)
    {
        // 获取商品规格项
        $item = array_unique($item);
        $result = self::where(['spec_id' => ['eq', $specId]])->column('spec_item_id,item_name');

        // 提交数据在数据库中不存在则插入
        $insert = [];
        foreach ($item as $value) {
            if (!in_array($value, $result)) {
                $insert[] = ['spec_id' => $specId, 'item_name' => $value];
            }
        }

        if (!empty($insert)) {
            self::insertAll($insert);
        }

        // 数据中存在而提交不存在则删除
        $destroy = [];
        foreach ($result as $key => $value) {
            if (!in_array($value, $item)) {
                $destroy[] = $key;
            }
        }

        if (!empty($destroy)) {
            foreach ($destroy as $value) {
                $sql = "`key_name` REGEXP '^{$value}_' OR ";
                $sql .= "`key_name` REGEXP '_{$value}_' OR ";
                $sql .= "`key_name` REGEXP '_{$value}$' OR ";
                $sql .= "`key_name` = '{$value}'";

                SpecGoods::where($sql)->delete();
            }

            self::destroy($destroy);
        }

        return true;
    }
}