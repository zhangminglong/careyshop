<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    公共模型基类
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/22
 */

namespace app\common\model;

use think\Model;

class CareyShop extends Model
{
    /**
     * 检测是否存在相同值
     * @access public
     * @param  array $map 查询条件
     * @return bool false:不存在
     */
    public static function checkUnique($map)
    {
        if (empty($map)) {
            return true;
        }

        $count = self::where($map)->count();
        if (is_numeric($count) && $count <= 0) {
            return false;
        }

        return true;
    }

    /**
     * 设置模型错误信息
     * @access public
     * @param  string $value 错误信息
     * @return false
     */
    public function setError($value)
    {
        $this->error = $value;
        return false;
    }

    /**
     * 根据传入参数进行验证
     * @access public
     * @param  array  $data  待验证数据
     * @param  string $name  验证器
     * @param  string $scene 场景
     * @return bool
     */
    public function validateSetData($data, $name, $scene = '')
    {
        !mb_strpos($name, '.', null, 'utf-8') ?: list($name, $scene) = explode('.', $name);
        $validate = validate($name);

        if (!$validate->hasScene($scene)) {
            return $this->setError($name . '场景不存在');
        }

        $rule = $validate->getSetScene($scene);
        foreach ($data as $key => $item) {
            if (!in_array($key, $rule) && !array_key_exists($key, $rule)) {
                return $this->setError('不接收' . $key . '字段参数');
            }
        }
        unset($key, $item);

        $pk = $this->getPk();
        foreach ($rule as $key => $value) {
            $field = is_string($key) ? $key : $value;
            if ($field == $pk) {
                continue;
            }

            if (!array_key_exists($field, $data)) {
                unset($rule[$key]);
            }
        }
        unset($key, $value);

        if (!$validate->scene($scene, $rule)->check($data, [], $scene)) {
            return $this->setError((string)$validate->getError());
        }

        return true;
    }
}