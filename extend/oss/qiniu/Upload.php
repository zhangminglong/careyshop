<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    七牛云OSS
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/1/23
 */

namespace oss\qiniu;

use app\common\model\Storage;
use oss\Upload as UploadBase;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Zone;
use think\Cache;
use think\Config;
use think\Url;

class Upload extends UploadBase
{
    /**
     * 模块名称
     * @var string
     */
    const NAME = '七牛云 KODO';

    /**
     * 模块
     * @var string
     */
    const MODULE = 'qiniu';

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
     * @return array|false
     */
    public function getUploadUrl()
    {
        $zone = Cache::remember('qiniuZone', function () {
            $accessKey = Config::get('qiniu_access_key.value', 'upload');
            $bucket = Config::get('qiniu_bucket.value', 'upload');

            return Zone::queryZone($accessKey, $bucket);
        }, 7200);

        if (!$zone instanceof Zone) {
            Cache::rm('qiniuZone');
            return $this->setError($zone[1]->message());
        }

        $random = array_rand($zone->cdnUpHosts, 1);
        $uploadUrl = Url::bUild('/', '', false, $zone->cdnUpHosts[$random]);
        $param = [
            ['name' => 'x:replace', 'type' => 'hidden', 'default' => $this->replace],
            ['name' => 'x:parent_id', 'type' => 'hidden', 'default' => 0],
            ['name' => 'x:filename', 'type' => 'hidden', 'default' => ''],
            ['name' => 'key', 'type' => 'hidden', 'default' => $this->replace],
            ['name' => 'token', 'type' => 'hidden', 'default' => ''],
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
        // 初始化Auth状态
        $accessKey = Config::get('qiniu_access_key.value', 'upload');
        $secretKey = Config::get('qiniu_secret_key.value', 'upload');
        $bucket = Config::get('qiniu_bucket.value', 'upload');
        empty($replace) ?: $this->replace = $replace;

        // 回调参数(别用JSON,处理很麻烦)
        $callbackBody = 'replace=$(x:replace)&parent_id=$(x:parent_id)&filename=$(x:filename)&mime=$(mimeType)&path=$(key)&';
        $callbackBody .= 'size=$(fsize)&name=$(fname)&width=$(imageInfo.width)&height=$(imageInfo.height)&hash=$(etag)';

        // 资源文件前缀
        $key = '';
        $dir = 'uploads/files/' . date('Ymd/', time());

        if (!empty($this->replace)) {
            $pathInfo = pathinfo($this->replace);
            empty($pathInfo['dirname']) ?: $dir = $pathInfo['dirname'] . '/';
            empty($pathInfo['basename']) ?: $key = $pathInfo['basename'];
        }

        // 组建上传策略
        $policy = [
            // 限定上传附件大小最大值
            'fsizeLimit'       => string_to_byte(Config::get('file_size.value', 'upload')),
            // 是否以"keyPrefix"为前缀的文件
            'isPrefixalScope'  => empty($this->replace) ? 1 : 0,
            // 回调地址
            'callbackUrl'      => $this->getCallbackUrl(),
            // 回调body信息
            'callbackBody'     => $callbackBody,
            // 回调contentType
            'callbackBodyType' => 'application/x-www-form-urlencoded',
        ];

        $auth = new Auth($accessKey, $secretKey);
        $upToken = $auth->uploadToken($bucket, $dir . $key, 3600, $policy, true);

        $response['upload_url'] = $this->getUploadUrl();
        $response['token'] = $upToken;
        $response['dir'] = $dir;

        return ['token' => $response, 'expires' => time() + 3600];
    }

    /**
     * 接收第三方推送数据
     * @access public
     * @return array|false
     * @throws
     */
    public function putUploadData()
    {
        // 获取回调body信息
        $callbackBody = file_get_contents('php://input');

        // 回调contentType
        $contentType = 'application/x-www-form-urlencoded';

        // 回调的签名信息,验证该回调是否来自七牛
        $authorization = $this->request->server('HTTP_AUTHORIZATION', '');

        // 回调地址
        $callbackUrl = $this->getCallbackUrl();

        // 初始化Auth状态
        $accessKey = Config::get('qiniu_access_key.value', 'upload');
        $secretKey = Config::get('qiniu_secret_key.value', 'upload');

        $auth = new Auth($accessKey, $secretKey);
        $isQiniuCallback = $auth->verifyCallback($contentType, $authorization, $callbackUrl, $callbackBody);

        if (true !== $isQiniuCallback) {
            return $this->setError(self::NAME . '模块异常访问!');
        }

        // 获取参数
        $params = $this->request->param();

        // 判断是否为图片
        $isImage = (int)$params['width'] > 0 && (int)$params['height'] > 0;

        // 准备写入数据库
        $data = [
            'parent_id' => (int)$params['parent_id'],
            'name'      => !empty($params['filename']) ? $params['filename'] : $params['name'],
            'mime'      => $params['mime'],
            'ext'       => mb_strtolower(pathinfo($params['name'], PATHINFO_EXTENSION), 'utf-8'),
            'size'      => $params['size'],
            'pixel'     => $isImage ? ['width' => (int)$params['width'], 'height' => (int)$params['height']] : [],
            'hash'      => $params['hash'],
            'path'      => $params['path'],
            'url'       => Config::get('qiniu_url.value', 'upload') . '/' . $params['path'] . '?type=' . self::MODULE,
            'protocol'  => self::MODULE,
            'type'      => $isImage ? 0 : 1,
        ];

        if (!empty($params['replace'])) {
            unset($data['parent_id']);
        }

        !empty($params['replace']) ?: $map['hash'] = ['eq', $data['hash']];
        $map['path'] = ['eq', $data['path']];
        $map['protocol'] = ['eq', self::MODULE];
        $map['type'] = ['neq', 2];

        $storageDb = new Storage();
        $result = $storageDb->field('mime,path,cover,sort', true)->where($map)->find();

        if (false === $result) {
            return $this->setError($storageDb->getError());
        }

        if (!is_null($result)) {
            // 更新已有资源
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
        return $this->setError('"' . self::NAME . '"只支持直传附件,详见七牛云开发文档');
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
        $options = 'thumbnail/';
        $options .= $width != 0 ? (int)$width : '';
        $options .= 'x';
        $options .= $height != 0 ? (int)$height : '';
        $options .= '/';

        return $options;
    }

    /**
     * 获取图片整体大小请求参数
     * @access private
     * @param  int $width  宽度
     * @param  int $height 高度
     * @return string
     */
    private function getExtentParam($width, $height)
    {
        $options = '';
        if ($width != 0 && $height != 0) {
            $options = 'extent/';
            $options .= $width != 0 ? (int)$width : '';
            $options .= 'x';
            $options .= $height != 0 ? (int)$height : '';
            $options .= '/background/d2hpdGU=/';
        }

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
        $options = 'gravity/Center/crop/';
        $options .= $width != 0 ? (int)$width : '';
        $options .= 'x';
        $options .= $height != 0 ? (int)$height : '';
        $options .= '/';

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
        $options = '?imageMogr2/auto-orient/';
        $url = sprintf('%s://%s%s', $urlArray['scheme'], $urlArray['host'], $urlArray['path']);

        // 带样式则直接返回
        if (!empty($param['style'])) {
            return $url . $param['style'];
        }

        // 检测尺寸是否正确
        list($sWidth, $sHeight) = @array_pad(isset($param['size']) ? $param['size'] : [], 2, 0);
        list($cWidth, $cHeight) = @array_pad(isset($param['crop']) ? $param['crop'] : [], 2, 0);

//        if (!$sWidth && !$sHeight) {
//            return $url;
//        }

        // 画布最后的尺寸初始化
        $last = 'size';
        $extent = [0, 0];

        // 处理缩放尺寸、裁剪尺寸
        foreach ($param as $key => $value) {
            switch ($key) {
                case 'size':
                    $last = 'size';
                    empty($sWidth) && $sWidth = $sHeight;
                    empty($sHeight) && $sHeight = $sWidth;
                    $extent = [$sWidth, $sHeight];
                    $options .= $this->getSizeParam($sWidth, $sHeight);
                    break;

                case 'crop':
                    $last = 'crop';
                    $extent = [$cWidth, $cHeight];
                    $options .= $this->getCropParam($cWidth, $cHeight);
                    break;
            }
        }

        // 决定图片画布最后的尺寸
        if ($last === 'crop') {
            $extent[0] = $sWidth > $cWidth && $cWidth > 0 ? $cWidth : $sWidth;
            $extent[1] = $sHeight > $cHeight ? $cHeight : $sHeight;
        }

        // 处理画布尺寸
        $options .= $this->getExtentParam($extent[0], $extent[1]);

        // 处理图片质量
        if (empty($param['quality'])) {
            $options .= 'quality/90!/';
        } else {
            $options .= sprintf('quality/%d!/', (int)$param['quality'] > 100 ? 100 : $param['quality']);
        }

        // 处理输出格式
        if (!empty($param['type'])) {
            if (in_array($param['type'], ['jpg', 'png', 'svg', 'gif', 'bmp', 'tiff', 'webp'])) {
                $options .= 'format/' . $param['type'] . '/';
            }
        }

        // 其余参数添加
        $options .= 'interlace/1/';
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

        // 初始化Auth状态
        $accessKey = Config::get('qiniu_access_key.value', 'upload');
        $secretKey = Config::get('qiniu_secret_key.value', 'upload');
        $bucket = Config::get('qiniu_bucket.value', 'upload');

        $auth = new Auth($accessKey, $secretKey);
        $config = new \Qiniu\Config();
        $bucketManager = new BucketManager($auth, $config);

        $ops = $bucketManager->buildBatchDelete($bucket, $this->delFileList);
        list($ret, $err) = $bucketManager->batch($ops);

        if ($ret) {
            return true;
        }

        return $this->setError($err->message());
    }
}