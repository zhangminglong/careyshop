<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    微信支付
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/7/4
 */

namespace payment\weixin;

use payment\Payment;
use think\Request;
use think\Url;

require_once __DIR__ . '/lib/WxPay.Api.php';
require_once __DIR__ . '/example/WxPay.NativePay.php';
require_once __DIR__ . '/example/WxPay.JsApiPay.php';

class Weixin extends Payment
{
    /**
     * 绑定支付的APPID
     * @var string
     */
    private $appid;

    /**
     * 商户号
     * @var string
     */
    private $mchid;

    /**
     * 商户支付密钥
     * @var string
     */
    private $key;

    /**
     * 公众帐号Secert
     * @var string
     */
    private $appsecret = '';

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

        if (empty($this->appid) || trim($this->appid) == '') {
            $this->error = '绑定支付的APPID不能为空';
            return false;
        }

        if (empty($this->mchid) || trim($this->mchid) == '') {
            $this->error = '商户号不能为空';
            return false;
        }

        if (empty($this->key) || trim($this->key) == '') {
            $this->error = '商户支付密钥不能为空';
            return false;
        }

        \WxPayConfig::$appid = $this->appid;
        \WxPayConfig::$mchid = $this->mchid;
        \WxPayConfig::$key = $this->key;
        \WxPayConfig::$appsecret = $this->appsecret;

        return true;
    }

    /**
     * 格式化参数格式化成url参数
     * @access private
     * @param  array $data 参数信息
     * @return string
     */
    private function toUrlParams($data)
    {
        $buff = '';
        foreach ($data as $k => $v) {
            if ($k != 'sign' && $v != '' && !is_array($v)) {
                $buff .= $k . '=' . $v . '&';
            }
        }

        $buff = trim($buff, '&');
        return $buff;
    }

    /**
     * 生成签名
     * @access private
     * @param  array $data 参数信息
     * @return string
     */
    private function makeSign($data)
    {
        // 签名步骤一：按字典序排序参数
        ksort($data);
        $string = $this->toUrlParams($data);

        // 签名步骤二：在string后加入KEY
        $string = $string . '&key=' . \WxPayConfig::$key;

        // 签名步骤三：MD5加密
        $string = md5($string);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($string);

        return $result;
    }

    /**
     * 返回支付模块请求结果
     * @access public
     * @return mixed
     */
    public function payRequest()
    {
        $result['callback_return_type'] = 'view';
        $result['is_callback'] = [];

        if ($this->request == 'web') {
            if (Request::instance()->isMobile() && strstr($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                $result['is_callback'] = $this->jsRequestExecute();
            } else {
                $result['is_callback'] = $this->pcRequestExecute();
            }
        } else {
            $result['is_callback'] = $this->appRequestExecute();
        }

        return $this->request == 'web' ? $result : $result['is_callback'];
    }

    /**
     * app查询结果
     * @access private
     * @return array|false
     * @throws
     */
    private function appRequestExecute()
    {
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($this->subject);
        $input->SetOut_trade_no($this->outTradeNo);
        $input->SetTotal_fee($this->totalAmount * 100);
        $input->SetNotify_url($this->notifyUrl);
        $input->SetTrade_type('APP');

        // 发送统一下单请求,生成预付款单
        $order = \WxPayApi::unifiedOrder($input);

        if ($order['return_code'] != 'SUCCESS') {
            $this->error = $order['return_msg'];
            return false;
        }

        if (!isset($order['prepay_id'])) {
            $this->error = '缺少参数prepay_id';
            return false;
        }

        $result = [
            'appid'     => $order['appid'],
            'partnerid' => $order['mch_id'],
            'prepayid'  => $order['prepay_id'],
            'noncestr'  => \WxPayApi::getNonceStr(),
            'timestamp' => time(),
            'package'   => 'Sign=WXPay',
        ];

        $result['sign'] = $this->makeSign($result);
        return $result;
    }

    /**
     * pc查询结果
     * @access private
     * @return string
     * @throws
     */
    private function pcRequestExecute()
    {
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($this->subject);
        $input->SetOut_trade_no($this->outTradeNo);
        $input->SetTotal_fee($this->totalAmount * 100);
        $input->SetNotify_url($this->notifyUrl);
        $input->SetTrade_type('NATIVE');
        $input->SetProduct_id($this->totalAmount * 100);

        $notify = new \NativePay();
        $result = $notify->GetPayUrl($input);

        $vars = [
            'method' => 'get.qrcode.item',
            'text'   => 'SUCCESS' !== $result['return_code'] ? $result['return_msg'] : urlencode($result['code_url']),
        ];

        return '<img src="' . Url::build('api/v1/qrcode', $vars, true, true) . '"/>';
    }

    /**
     * js查询结果
     * @access private
     * @return string
     * @throws
     */
    private function jsRequestExecute()
    {
        $tools = new \JsApiPay();
        $openId = $tools->GetOpenid();

        $input = new \WxPayUnifiedOrder();
        $input->SetBody($this->subject);
        $input->SetOut_trade_no($this->outTradeNo);
        $input->SetTotal_fee($this->totalAmount * 100);
        $input->SetNotify_url($this->notifyUrl);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);

        $order = \WxPayApi::unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);

        $html = <<<EOF
	<script type="text/javascript">
	//调用微信JS api 支付
	function jsApiCall()
	{
		WeixinJSBridge.invoke(
			'getBrandWCPayRequest',$jsApiParameters,
			function(res){
				//WeixinJSBridge.log(res.err_msg);
				 if(res.err_msg == "get_brand_wcpay_request:ok") {
				    location.href='/';
				 }else{
				 	//alert(res.err_code+res.err_desc+res.err_msg);
				    location.href='/';
				 }
			}
		);
	}

	function callpay()
	{
		if (typeof WeixinJSBridge == "undefined"){
		    if( document.addEventListener ){
		        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
		    }else if (document.attachEvent){
		        document.attachEvent('WeixinJSBridgeReady', jsApiCall);
		        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
		    }
		}else{
		    jsApiCall();
		}
	}
	callpay();
	</script>
EOF;

        return $html;
    }
}