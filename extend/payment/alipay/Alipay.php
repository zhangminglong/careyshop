<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    支付宝
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/6/30
 */

namespace payment\alipay;

use think\Request;
use payment\Payment;

require_once __DIR__ . '/lib/AopClient.php';
require_once __DIR__ . '/lib/request/AlipayTradePagePayRequest.php';
require_once __DIR__ . '/lib/request/AlipayTradeWapPayRequest.php';
require_once __DIR__ . '/lib/request/AlipayTradeAppPayRequest.php';

class Alipay extends Payment
{
    /**
     * 应用ID
     * @var string
     */
    private $appId;

    /**
     * 商户私钥
     * @var string
     */
    private $merchantPrivateKey;

    /**
     * 签名方式
     * @var string
     */
    private $signType;

    /**
     * 支付宝公钥
     * @var string
     */
    private $alipayPublicKey;

    /**
     * 业务编码
     * @var string
     */
    private $productCode;

    /**
     * 页面接口方式
     * @var string
     */
    private $httpMethod = 'post';

    /**
     * 请求来源
     * @var array/bool
     */
    private $request;

    /**
     * 设置请求来源
     * @access public
     * @param  string $request 请求来源
     * @return object
     */
    public function setQequest($request)
    {
        $this->request = $request;
        $this->productCode = $request == 'app' ? 'QUICK_MSECURITY_PAY' : 'FAST_INSTANT_TRADE_PAY';

        return $this;
    }

    /**
     * 设置支付配置
     * @access public
     * @param  array $setting 配置信息
     * @return bool
     */
    public function setConfig($setting)
    {
        foreach ($setting as $key => $value) {
            $this->$key = $value['value'];
        }

        if (empty($this->appId) || trim($this->appId) == '') {
            $this->error = '应用ID不能为空';
            return false;
        }

        if (empty($this->merchantPrivateKey) || trim($this->merchantPrivateKey) == '') {
            $this->error = '商户私钥不能为空';
            return false;
        }

        if (empty($this->signType) || trim($this->signType) == '') {
            $this->error = '签名方式不能为空';
            return false;
        }

        if (empty($this->alipayPublicKey) || trim($this->alipayPublicKey) == '') {
            $this->error = '支付宝公钥不能为空';
            return false;
        }

        return true;
    }

    /**
     * 返回支付模块请求结果
     * @access public
     * @return array|false
     */
    public function payRequest()
    {
        $bizContent = [
            'product_code' => $this->productCode,
            'body'         => $this->body,
            'subject'      => $this->subject,
            'total_amount' => $this->totalAmount,
            'out_trade_no' => $this->outTradeNo,
        ];

        if ($this->request == 'web') {
            $request = Request::instance()->isMobile() ? new \AlipayTradeWapPayRequest() : new \AlipayTradePagePayRequest();
            $request->setReturnUrl($this->returnUrl);
        } else {
            $request = new \AlipayTradeAppPayRequest();
        }

        $request->setNotifyUrl($this->notifyUrl);
        $request->setBizContent(json_encode($bizContent, JSON_UNESCAPED_UNICODE));

        // 调用支付api
        $result['callback_return_type'] = 'view';
        $result['is_callback'] = $this->aopclientRequestExecute($request, true);

        return $this->request == 'web' ? $result : $result['is_callback'];
    }

    /**
     * sdkClient
     * @access public
     * @param  object $request 接口请求参数对象
     * @param  bool   $ispage  是否是页面接口,电脑网站支付是页面表单接口
     * @return mixed
     * @throws
     */
    private function aopclientRequestExecute($request, $ispage = false)
    {
        $aop = new \AopClient();
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->merchantPrivateKey;
        $aop->alipayrsaPublicKey = $this->alipayPublicKey;
        $aop->signType = $this->signType;
        $aop->debugInfo = false; // 开启页面信息输出

        if ($ispage && $this->request == 'web') {
            $result = $aop->pageExecute($request, $this->httpMethod);
        } else if (!$ispage && $this->request == 'web') {
            $result = $aop->Execute($request);
        } else {
            $result = $aop->sdkExecute($request);
        }

        return $result;
    }
}