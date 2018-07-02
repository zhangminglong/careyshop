<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    阿里云OSS
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/1/23
 */

namespace oss\aliyun;

use app\common\model\Storage;
use oss\Upload as UploadBase;
use OSS\OssClient;
use OSS\Core\OssException;
use think\Cache;
use think\Config;
use think\Url;
use aliyun\AssumeRoleRequest;
use aliyun\core\Config as AliyunConfig;
use aliyun\core\profile\DefaultProfile;
use aliyun\core\DefaultAcsClient;

class Upload extends UploadBase
{
    /**
     * 模块名称
     * @var string
     */
    const NAME = '阿里云 OSS';

    /**
     * 模块
     * @var string
     */
    const MODULE = 'aliyun';

    /**
     * 主机区域后缀
     * @var string
     */
    const HOST = '.aliyuncs.com';

    /**
     * 获取回调推送地址
     * @access private
     * @return string
     */
    private function getCallbackUrl()
    {
        $vars = ['method' => 'put.upload.data', 'module' => self::MODULE];
        $callbackUrl = Url::bUild('/api/v1/upload', $vars, true, true);

        return $callbackUrl;
    }

    /**
     * 获取上传地址
     * @access public
     * @return array
     */
    public function getUploadUrl()
    {
        // 请求获取bucket所在数据中心位置信息
        $location = Cache::remember('aliyunLocation', function () {
            $accessKeyId = Config::get('aliyun_access_key.value', 'upload');
            $accessKeySecret = Config::get('aliyun_secret_key.value', 'upload');
            $endPoint = Config::get('aliyun_endpoint.value', 'upload');
            $bucket = Config::get('aliyun_bucket.value', 'upload');

            try {
                $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endPoint);
                $result = $ossClient->getBucketLocation($bucket);

                if (false === $result = xml_to_array($result)) {
                    throw new OssException('解析数据失败');
                }
            } catch (OssException $e) {
                return $this->setError($e->getErrorMessage());
            }

            $random = array_rand($result, 1);
            return $bucket . '.' . $result[$random] . self::HOST;
        }, 7200);

        if (false === $location) {
            Cache::rm('aliyunLocation');
            return [];
        }

        $uploadUrl = Url::bUild('/', '', false, $location);
        $param = [
            ['name' => 'x:replace', 'type' => 'hidden', 'default' => $this->replace],
            ['name' => 'x:parent_id', 'type' => 'hidden', 'default' => 0],
            ['name' => 'x:filename', 'type' => 'hidden', 'default' => ''],
            ['name' => 'OSSAccessKeyId', 'type' => 'hidden', 'default' => ''],
            ['name' => 'policy', 'type' => 'hidden', 'default' => ''],
            ['name' => 'Signature', 'type' => 'hidden', 'default' => ''],
            ['name' => 'callback', 'type' => 'hidden', 'default' => ''],
            ['name' => 'key', 'type' => 'hidden', 'default' => $this->replace],
            ['name' => 'success_action_status', 'type' => 'hidden', 'default' => 200],
            ['name' => 'file', 'type' => 'file', 'default' => ''],
        ];

        return ['upload_url' => $uploadUrl, 'module' => self::MODULE, 'param' => $param];
    }

    /**
     * 获取上传Token
     * @access public
     * @param  string $replace 替换资源(path)
     * @return array
     */
    public function getToken($replace = '')
    {
        empty($replace) ?: $this->replace = $replace;

        if ($this->request->param('type') === 'app') {
            return $this->getAppToken();
        }

        return $this->getWebToken();
    }

    /**
     * 获取表单上传所需Token
     * @access private
     * @return array
     */
    private function getWebToken()
    {
        // 获取配置数据
        $accessKeyId = Config::get('aliyun_access_key.value', 'upload');
        $accessKeySecret = Config::get('aliyun_secret_key.value', 'upload');

        $timestamp = new \DateTime();
        $expires = time() + 3600;
        $dir = 'uploads/files/' . date('Ymd/', time());

        if (!empty($this->replace)) {
            $pathInfo = pathinfo($this->replace);
            empty($pathInfo['dirname']) ?: $dir = $pathInfo['dirname'] . '/';
        }

        $policyArray = [
            'expiration' => $timestamp->setTimestamp($expires)->format('Y-m-d\TH:i:s\Z'),
            'conditions' => [
                ['content-length-range', 0, string_to_byte(Config::get('file_size.value', 'upload'))],
                ['starts-with', '$key', $dir],
            ],
        ];

        $policy = json_encode($policyArray, JSON_UNESCAPED_UNICODE);
        $policyBase64 = base64_encode($policy);
        $stringToSign = $policyBase64;
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret, true));

        $response['upload_url'] = $this->getUploadUrl();
        $response['OSSAccessKeyId'] = $accessKeyId;
        $response['policy'] = $policyBase64;
        $response['Signature'] = $signature;
        $response['callback'] = base64_encode($this->getCallbackData());
        $response['dir'] = $dir;

        return ['token' => $response, 'expires' => $expires];
    }

    /**
     * 获取回调参数
     * @access private
     * @return string
     */
    private function getCallbackData()
    {
        // 回调参数(别用JSON,阿里云传过来的JSON格式能坑死你)
        $callbackBody = 'replace=${x:replace}&parent_id=${x:parent_id}&filename=${x:filename}&mime=${mimeType}&';
        $callbackBody .= 'size=${size}&width=${imageInfo.width}&height=${imageInfo.height}&path=${object}&hash=${etag}';

        // 创建回调数据
        $callbackParam = [
            'callbackUrl'      => $this->getCallbackUrl(),
            'callbackBody'     => $callbackBody,
            'callbackBodyType' => 'application/x-www-form-urlencoded',
        ];

        return json_encode($callbackParam, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取STS临时授权Token(SDK或APP使用)
     * @access private
     * @return array|false
     */
    private function getAppToken()
    {
        // 获取配置数据
        $accessKeyId = Config::get('aliyun_access_key.value', 'upload');
        $accessKeySecret = Config::get('aliyun_secret_key.value', 'upload');
        $roleArn = Config::get('aliyun_rolearn.value', 'upload');
        $bucket = Config::get('aliyun_bucket.value', 'upload');

        // 加载区域结点配置
        AliyunConfig::load();

        // 创建STS请求配置
        $iClientProfile = DefaultProfile::getProfile('cn-hangzhou', $accessKeyId, $accessKeySecret);
        $client = new DefaultAcsClient($iClientProfile);

        // 创建授权策略 工具 http://gosspublic.alicdn.com/ram-policy-editor/index.html
        $policy = [
            'Version'   => '1',
            'Statement' => [
                [
                    'Effect'   => 'Allow',
                    'Action'   => [
                        'oss:PutObject',
                    ],
                    'Resource' => [
                        'acs:oss:*:*:*',
                    ],
                ],
            ],
        ];

        // 向阿里云请求获取Token
        try {
            $request = new AssumeRoleRequest();
            $request->setRoleSessionName('temp_user');
            $request->setRoleArn($roleArn);
            $request->setPolicy(json_encode($policy, JSON_UNESCAPED_UNICODE));
            $request->setDurationSeconds(3600);
            $response = $client->getAcsResponse($request);
        } catch (\exception $e) {
            return $this->setError($e->getMessage());
        }

        $result = [
            'assumed_role_user' => [
                'assumed_role_id' => $response['AssumedRoleUser']['AssumedRoleId'],
                'arn'             => $response['AssumedRoleUser']['Arn'],
            ],
            'credentials'       => [
                'access_key_id'     => $response['Credentials']['AccessKeyId'],
                'access_key_secret' => $response['Credentials']['AccessKeySecret'],
                'security_token'    => $response['Credentials']['SecurityToken'],
                'expiration'        => $response['Credentials']['Expiration'],
            ],
            'policy'            => json_encode($policy, JSON_UNESCAPED_UNICODE),
            'bucket'            => $bucket,
            'callback'          => $this->getCallbackData(),
            'callback_url'      => $this->getCallbackUrl(),
            'expires'           => time() + 3600,
        ];

        return $result;
    }

    /**
     * 接收第三方推送数据
     * @access public
     * @return array|false
     * @throws
     */
    public function putUploadData()
    {
        // 获取OSS的签名header和公钥url header
        $authorizationBase64 = $this->request->server('HTTP_AUTHORIZATION', '');
        $pubKeyUrlBase64 = $this->request->server('HTTP_X_OSS_PUB_KEY_URL', '');

        if ($authorizationBase64 == '' || $pubKeyUrlBase64 == '') {
            return $this->setError(self::NAME . '模块异常访问!');
        }

        // 获取签名和公钥
        $authorization = base64_decode($authorizationBase64);
        $pubKeyUrl = base64_decode($pubKeyUrlBase64);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $pubKey = curl_exec($ch);

        if ($pubKey == '') {
            return $this->setError(self::NAME . '模块获取失败!');
        }

        // 获取回调body
        $body = file_get_contents('php://input');

        // 拼接待签名字符串
        $path = $this->request->server('REQUEST_URI');
        $pos = mb_strpos($path, '?', null, 'utf-8');
        if ($pos === false) {
            $authStr = urldecode($path) . "\n" . $body;
        } else {
            $authStr = urldecode(mb_substr($path, 0, $pos, 'utf-8')) . mb_substr($path, $pos, mb_strlen($path, 'utf-8') - $pos, 'utf-8');
            $authStr .= ("\n" . $body);
        }

        // 验证签名
        $isVerify = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);
        if ($isVerify != 1) {
            return $this->setError(self::NAME . '模块非法访问!');
        }

        // 获取参数
        $params = $this->request->param();

        // 判断是否为图片
        $isImage = (int)$params['width'] > 0 && (int)$params['height'] > 0;

        // 准备写入数据库
        $data = [
            'parent_id' => (int)$params['parent_id'],
            'name'      => !empty($params['filename']) ? $params['filename'] : basename($params['path']),
            'mime'      => $params['mime'],
            'ext'       => mb_strtolower(pathinfo($params['path'], PATHINFO_EXTENSION), 'utf-8'),
            'size'      => $params['size'],
            'pixel'     => $isImage ? ['width' => (int)$params['width'], 'height' => (int)$params['height']] : [],
            'hash'      => $params['hash'],
            'path'      => $params['path'],
            'url'       => Config::get('aliyun_url.value', 'upload') . '/' . $params['path'] . '?type=' . self::MODULE,
            'protocol'  => self::MODULE,
            'type'      => $isImage ? 0 : 1,
        ];

        if (!empty($params['replace'])) {
            unset($data['parent_id']);
        }

        $map['path'] = ['eq', $data['path']];
        $map['protocol'] = ['eq', self::MODULE];
        $map['type'] = ['neq', 2];

        $storageDb = new Storage();
        $result = $storageDb->field('mime,path,cover,sort', true)->where($map)->find();

        if (false === $result) {
            return $this->setError($storageDb->getError());
        }

        if (!is_null($result)) {
            // 替换资源进行更新
            if (false === $result->save($data)) {
                return $this->setError($storageDb->getError());
            }

            $ossResult = $result->hidden(['mime'])->setAttr('status', 200)->toArray();
        } else {
            // 插入新记录
            if (false === $storageDb->isUpdate(false)->save($data)) {
                return $this->setError($storageDb->getError());
            }

            $ossResult = $storageDb->hidden(['mime'])->setAttr('status', 200)->toArray();
        }

        $ossResult['oss'] = Config::get('oss.value', 'upload');
        return [$ossResult];
    }

    /**
     * 上传资源
     * @access public
     * @return false
     */
    public function uploadFiles()
    {
        // 直传的意思是客户端直接传附件给OSS,而不再需要应用服务端代为上传,少了转发,速度更快.
        return $this->setError('"' . self::NAME . '"只支持直传附件,详见阿里云开发文档');
    }

    /**
     * 获取缩略大小请求参数
     * @access private
     * @param  int $width  宽度
     * @param  int $height 高度
     * @return string
     */
    private function getSizeParam($width, $height)
    {
        $options = 'resize,';
        $options .= $width != 0 ? sprintf('w_%d,', $width) : '';
        $options .= $height != 0 ? sprintf('h_%d,', $height) : '';
        $options .= 'm_pad/';

        return $options;
    }

    /**
     * 获取裁剪区域请求参数
     * @access private
     * @param  int $width  宽度
     * @param  int $height 高度
     * @return string
     */
    private function getCropParam($width, $height)
    {
        $options = 'crop,';
        $options .= $width != 0 ? sprintf('w_%d,', $width) : '';
        $options .= $height != 0 ? sprintf('h_%d,', $height) : '';
        $options .= 'g_center/';

        return $options;
    }

    /**
     * 获取资源缩略图实际路径
     * @access public
     * @param  array $urlArray 路径结构
     * @return string
     */
    public function getThumbUrl($urlArray)
    {
        // 初始化数据并拼接不带查询条件的URL
        $param = $this->request->param();
        $options = '?x-oss-process=image/';
        $url = sprintf('%s://%s%s', $urlArray['scheme'], $urlArray['host'], $urlArray['path']);

        // 带样式则直接返回
        if (!empty($param['style'])) {
            $style = mb_substr($param['style'], 0, 1, 'utf-8');
            $url .= in_array($style, ['-', '_', '/', '!']) ? $param['style'] : '?x-oss-process=style/' . $param['style'];
            return $url;
        }

        // 检测尺寸是否正确
        list($sWidth, $sHeight) = @array_pad(isset($param['size']) ? $param['size'] : [], 2, 0);
//        if (!$sWidth && !$sHeight) {
//            return $url;
//        }

        // 处理缩放尺寸、裁剪尺寸
        foreach ($param as $key => $value) {
            switch ($key) {
                case 'size':
                    $options .= $this->getSizeParam($sWidth, $sHeight);
                    break;

                case 'crop':
                    list($cWidth, $cHeight) = @array_pad($value, 2, 0);
                    $options .= $this->getCropParam($cWidth, $cHeight);
                    break;
            }
        }

        // 处理图片质量
        if (empty($param['quality'])) {
            $options .= 'quality,Q_90/';
        } else {
            $options .= sprintf('quality,Q_%d/', (int)$param['quality'] > 100 ? 100 : $param['quality']);
        }

        // 处理输出格式
        if (!empty($param['type'])) {
            if (in_array($param['type'], ['jpg', 'png', 'bmp', 'webp', 'gif', 'tiff'])) {
                $options .= 'format,' . $param['type'] . '/';
            }
        }

        // 其余参数添加
        $options .= 'auto-orient,1/interlace,1/';
        return $url . $options;
    }

    /**
     * 批量删除资源
     * @access public
     * @return bool
     */
    public function delFileList()
    {
        if (count($this->delFileList) > 1000) {
            return $this->setError(self::NAME . '批量删除资源不可超过1000个');
        }

        $accessKeyId = Config::get('aliyun_access_key.value', 'upload');
        $accessKeySecret = Config::get('aliyun_secret_key.value', 'upload');
        $endPoint = Config::get('aliyun_endpoint.value', 'upload');
        $bucket = Config::get('aliyun_bucket.value', 'upload');

        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endPoint);
            $ossClient->deleteObjects($bucket, $this->delFileList);
        } catch (OssException $e) {
            return $this->setError($e->getErrorMessage());
        }

        return true;
    }
}