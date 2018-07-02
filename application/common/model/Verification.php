<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    验证码模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/7/20
 */

namespace app\common\model;

class Verification extends CareyShop
{
    /**
     * 是否需要自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 更新时间字段
     * @var bool/string
     */
    protected $updateTime = false;

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'verification_id' => 'integer',
        'status'          => 'integer',
    ];

    /**
     * 发送验证码
     * @access public
     * @param  string $code   通知编码 sms或email
     * @param  string $number 手机号或邮箱地址
     * @return bool
     * @throws
     */
    private function sendNotice($code, $number)
    {
        $result = self::get(function ($query) use ($number) {
            $query->where(['number' => ['eq', $number]])->order(['verification_id' => 'desc']);
        });

        if ($result) {
            // 现在时间与创建时间
            $nowTime = time();
            $createTime = $result->getData('create_time');

            if (($nowTime - $createTime) < 60) {
                return $this->setError(sprintf('操作过于频繁，请%d秒后重试', 60 - ($nowTime - $createTime)));
            }
        }

        $notice = new NoticeTpl();
        $data['number'] = rand_number(6);

        if (!$notice->sendNotice($number, $number, Notice::CAPTCHA, $code, $data)) {
            return $this->setError($notice->getError());
        }

        // 添加新的验证码
        if (false === $this->isUpdate(false)->save(['number' => $number, 'code' => $data['number']])) {
            return false;
        }

        return true;
    }

    /**
     * 使用验证码
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function useVerificationItem($data)
    {
        if (!$this->validateData($data, 'Verification.use')) {
            return false;
        }

        return $this->isUpdate()->save(['status' => 0], ['number' => ['eq', $data['number']]]) !== false;
    }

    /**
     * 发送短信验证码
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function sendVerificationSms($data)
    {
        if (!$this->validateData($data, 'Verification.sms')) {
            return false;
        }

        return $this->sendNotice('sms', $data['mobile']);
    }

    /**
     * 发送邮件验证码
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function sendVerificationEmail($data)
    {
        if (!$this->validateData($data, 'Verification.email')) {
            return false;
        }

        return $this->sendNotice('email', $data['email']);
    }

    /**
     * 验证验证码
     * @access public
     * @param  string $number 手机号或邮箱地址
     * @param  string $code   通知编码 sms或email
     * @return bool
     * @throws
     */
    public function verVerification($number, $code)
    {
        $map['number'] = ['eq', $number];
        $map['code'] = ['eq', $code];

        $result = self::get(function ($query) use ($map) {
            $query->where($map)->order(['verification_id' => 'desc']);
        });

        if (is_null($result)) {
            return $this->setError('验证码错误');
        }

        if ($result->getAttr('status') !== 1) {
            return $this->setError('验证码已失效');
        }

        if (time() - $result->getData('create_time') > 60 * 5) {
            return $this->setError('验证码已失效');
        }

        return true;
    }

    /**
     * 验证短信验证码
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function verVerificationSms($data)
    {
        if (!$this->validateData($data, 'Verification.ver_sms')) {
            return false;
        }

        return $this->verVerification($data['mobile'], $data['code']);
    }

    /**
     * 验证邮件验证码
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function verVerificationEmail($data)
    {
        if (!$this->validateData($data, 'Verification.ver_email')) {
            return false;
        }

        return $this->verVerification($data['email'], $data['code']);
    }
}