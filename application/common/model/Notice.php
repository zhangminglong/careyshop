<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    通知系统模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/7/17
 */

namespace app\common\model;

use think\Cache;
use think\Config;
use think\Loader;

class Notice extends CareyShop
{
    /**
     * 通用验证
     * @var int
     */
    const CAPTCHA = 0;

    /**
     * 注册成功
     * @var int
     */
    const REGISTER = 1;

    /**
     * 充值成功
     * @var int
     */
    const RECHARGE = 2;

    /**
     * 确认订单
     * @var int
     */
    const CONFIRM_ORDER = 3;

    /**
     * 付款成功
     * @var int
     */
    const PAY_ORDER = 4;

    /**
     * 下单成功
     * @var int
     */
    const PICKING_ORDER = 5;

    /**
     * 订单发货
     * @var int
     */
    const DELIVERY_ORDER = 6;

    /**
     * 当前模型名称
     * @var string
     */
    protected $name = 'setting';

    /**
     * 获取一个通知系统
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     */
    public function getNoticeItem($data)
    {
        if (!$this->validateData($data, 'Notice.item')) {
            return false;
        }

        $result = Config::get($data['code'], 'notice');
        if (!empty($result['value'])) {
            $result['value'] = json_decode($result['value'], true);
        }

        return $result;
    }

    /**
     * 批量设置通知系统是否启用
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function setNoticeStatus($data)
    {
        if (!$this->validateData($data, 'Notice.status')) {
            return false;
        }

        // 获取原始数据
        $map['module'] = ['eq', 'notice'];
        $map['code'] = ['in', $data['code']];
        $result = $this->field('setting_id,code,value')->where($map)->select()->toArray();

        foreach ($result as $key => $value) {
            if (!empty($value['value'])) {
                $value['value'] = json_decode($value['value'], true);
                $value['value']['status']['value'] = $data['status'];
                $value['value'] = json_encode($value['value'], JSON_UNESCAPED_UNICODE);

                $result[$key] = $value;
                Config::set($value['code'] . '.value', $value['value'], 'notice');
            }
        }

        if (empty($result) || false === $this->saveAll($result)) {
            return false;
        }

        Cache::rm('setting');
        return true;
    }

    /**
     * 设置一个通知系统
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setNoticeItem($data)
    {
        $code = isset($data['code']) ? $data['code'] : '';
        $validate = Loader::validate('Notice');

        if (!$validate->hasScene('set_' . $code)) {
            return $this->setError('通知系统编码错误');
        }

        if (!$validate->scene('set_' . $code)->check($data)) {
            return $this->setError($validate->getError());
        }

        $settingData = [];
        if ('sms' == $data['code']) {
            $settingData = [
                'key_id'     => [
                    'name'  => 'Access Key ID',
                    'value' => $data['key_id'],
                ],
                'key_secret' => [
                    'name'  => 'Access Key Secret',
                    'value' => $data['key_secret'],
                ],
                'status'     => [
                    'name'  => '启用状态',
                    'value' => $data['status'],
                ],
            ];
        } else if ('email' == $data['code']) {
            $settingData = [
                'email_host' => [
                    'name'  => 'SMTP服务器',
                    'value' => $data['email_host'],
                ],
                'email_port' => [
                    'name'  => 'SMTP端口',
                    'value' => $data['email_port'],
                ],
                'email_addr' => [
                    'name'  => '发信人邮箱地址',
                    'value' => $data['email_addr'],
                ],
                'email_id'   => [
                    'name'  => 'SMTP身份验证用户名',
                    'value' => $data['email_id'],
                ],
                'email_pass' => [
                    'name'  => 'SMTP身份验证码',
                    'value' => $data['email_pass'],
                ],
                'email_ssl'  => [
                    'name'  => '是否使用安全链接',
                    'value' => $data['email_ssl'],
                ],
                'status'     => [
                    'name'  => '启用状态',
                    'value' => $data['status'],
                ],
            ];
        }

        if (!empty($settingData)) {
            $map['code'] = ['eq', $code];
            $map['module'] = ['eq', 'notice'];

            $settingData = json_encode($settingData, JSON_UNESCAPED_UNICODE);
            if (false !== $this->save(['value' => $settingData], $map)) {
                Config::set($code . '.value', $settingData, 'notice');
                Cache::rm('setting');
                return true;
            }
        }

        return false;
    }
}