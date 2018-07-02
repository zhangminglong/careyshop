<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    账号等级模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/30
 */

namespace app\common\model;

class UserLevel extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'user_level_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'user_level_id' => 'integer',
        'amount'        => 'float',
        'discount'      => 'integer',
    ];

    /**
     * 获取一个账号等级
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getLevelItem($data)
    {
        if (!$this->validateData($data, 'UserLevel.item')) {
            return false;
        }

        $result = self::get($data['user_level_id']);
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取账号等级列表
     * @access public
     * @return array|false
     * @throws
     */
    public function getLevelList()
    {
        $result = self::all();
        if (false !== $result) {
            return $result->toArray();
        }

        return false;
    }

    /**
     * 添加一个账号等级
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addLevelItem($data)
    {
        if (!$this->validateData($data, 'UserLevel')) {
            return false;
        }

        // 避免无关字段
        unset($data['user_level_id']);

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个账号等级
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setLevelItem($data)
    {
        if (!$this->validateSetData($data, 'UserLevel.set')) {
            return false;
        }

        $map['user_level_id'] = ['eq', $data['user_level_id']];
        if (false !== $this->allowField(true)->save($data, $map)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除账号等级
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delLevelList($data)
    {
        if (!$this->validateData($data, 'UserLevel.del')) {
            return false;
        }

        self::destroy($data['user_level_id']);

        return true;
    }
}