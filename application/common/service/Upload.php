<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    资源上传服务层
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/1/26
 */

namespace app\common\service;

use app\common\model\Storage;
use app\common\model\StorageStyle;
use think\Config;
use think\helper\Str;
use think\Loader;
use think\Request;

class Upload extends CareyShop
{
    /**
     * 获取上传模块列表
     * @access public
     * @return array
     */
    public function getUploadModule()
    {
        return [
            [
                'name'   => \oss\careyshop\Upload::NAME,
                'module' => \oss\careyshop\Upload::MODULE,
            ],
            [
                'name'   => \oss\qiniu\Upload::NAME,
                'module' => \oss\qiniu\Upload::MODULE,
            ],
            [
                'name'   => \oss\aliyun\Upload::NAME,
                'module' => \oss\aliyun\Upload::MODULE,
            ],
        ];
    }

    /**
     * 创建资源上传对象
     * @access public
     * @param  string $file  目录
     * @param  string $model 模块
     * @return object|false
     */
    public function createOssObject($file, $model = 'Upload')
    {
        // 转换模块的名称
        $file = Str::lower($file);
        $model = Str::studly($model);

        if (empty($file) || empty($model)) {
            return $this->setError('资源目录或模块不存在');
        }

        $ossObject = '\\oss\\' . $file . '\\' . $model;
        if (class_exists($ossObject)) {
            return new $ossObject;
        }

        return $this->setError($ossObject . '模块不存在');
    }

    /**
     * 获取上传地址
     * @access public
     * @return mixed
     */
    public function getUploadUrl()
    {
        $file = $this->getModuleName();
        if (false === $file) {
            return false;
        }

        $ossObject = $this->createOssObject($file);
        if (false === $ossObject) {
            return false;
        }

        $result = $ossObject->getUploadUrl();
        if (false === $result) {
            return $this->setError($ossObject->getError());
        }

        return $result;
    }

    /**
     * 获取上传Token
     * @access public
     * @return mixed
     */
    public function getUploadToken()
    {
        $file = $this->getModuleName();
        if (false === $file) {
            return false;
        }

        $ossObject = $this->createOssObject($file);
        if (false === $ossObject) {
            return false;
        }

        $result = $ossObject->getToken();
        if (false === $result) {
            return $this->setError($ossObject->getError());
        }

        return $result;
    }

    /**
     * 替换上传资源
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function replaceUploadItem($data)
    {
        $validate = Loader::validate('Storage');
        if (!$validate->scene('replace')->check($data)) {
            return $this->setError($validate->getError());
        }

        // 获取已存在资源数据
        $map['storage_id'] = ['eq', $data['storage_id']];
        $map['type'] = ['neq', 2];

        $storageDB = new Storage();
        $storageData = $storageDB->field('path,protocol')->where($map)->find();

        if (!$storageData) {
            return $this->setError(is_null($storageData) ? '资源不存在' : $storageDB->getError());
        }

        $ossObject = $this->createOssObject($storageData->getAttr('protocol'));
        if (false === $ossObject) {
            return false;
        }

        $result = $ossObject->getToken($storageData->getAttr('path'));
        if (false === $result) {
            return $this->setError($ossObject->getError());
        }

        return $result;
    }

    /**
     * 当参数为空时获取默认上传模块名,否则验证指定模块名并返回
     * @access public
     * @return string|false
     */
    private function getModuleName()
    {
        $request = Request::instance();
        $module = $request->param('module');

        if (empty($module)) {
            return Config::get('default.value', 'upload');
        }

        $moduleList = array_column($this->getUploadModule(), 'module');
        if (!in_array($module, $moduleList)) {
            return $this->setError('上传模块名 ' . $module . ' 不存在');
        }

        return $module;
    }

    /**
     * 资源上传请求(第三方OSS只能单文件直传方式上传)
     * @access public
     * @return mixed
     */
    public function addUploadList()
    {
        $ossObject = $this->createOssObject('careyshop');
        if (false === $ossObject) {
            return false;
        }

        $result = $ossObject->uploadFiles();
        if (false === $result) {
            return $this->setError($ossObject->getError());
        }

        return $result;
    }

    /**
     * 接收第三方推送数据
     * @access public
     * @return mixed
     */
    public function putUploadData()
    {
        $ossObject = $this->createOssObject(Request::instance()->param('module', ''));
        if (false === $ossObject) {
            return false;
        }

        $result = $ossObject->putUploadData();
        if (false === $result) {
            return $this->setError($ossObject->getError());
        }

        return $result;
    }

    /**
     * 获取资源缩略图
     * @access public
     * @return void
     */
    public function getThumb()
    {
        $url = $this->getThumbUrl();
        if (false === $url || empty($url['url_prefix'])) {
            header('status: 404 Not Found', true, 404);
            exit;
        }

        header('Location:' . $url['url_prefix'], true, 301);
        exit;
    }

    /**
     * 获取资源缩略图实际路径
     * @access public
     * @return mixed
     */
    public function getThumbUrl()
    {
        // 补齐协议地址
        $request = Request::instance();
        $url = $request->param('url');

        $pattern = '/^((http|https)?:\/\/)/i';
        if (!preg_match($pattern, $url)) {
            $url = ($request->isSsl() ? 'https://' : 'http://') . $url;
        }

        // 从URL分析获取对应模型
        $urlArray = parse_url($url);
        if (!isset($urlArray['query'])) {
            return $this->setError('请填写合法的url或缺少type参数');
        }

        list(, $module) = explode('=', $urlArray['query']);
        if (empty($module)) {
            return $this->setError('type参数值不能为空');
        }

        $pact = array_column($this->getUploadModule(), 'module');
        if (!in_array($module, $pact)) {
            return $this->setError('type协议错误');
        }

        // 是否定义资源样式
        if ($request->has('code', 'param', true)) {
            $styleResult = (new StorageStyle())->getStorageStyleCode(['code' => $request->param('code')]);
            foreach ($styleResult as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $request->get([$k => $v]);
                    }
                } else {
                    $request->get([$key => $value]);
                }
            }
        }

        $ossObject = $this->createOssObject($module);
        if (false === $ossObject) {
            return false;
        }

        $url = $ossObject->getThumbUrl($urlArray);
        $notPrefix = preg_replace($pattern, '', $url);

        return [
            'url'        => $notPrefix,
            'url_prefix' => strval($url),
        ];
    }
}