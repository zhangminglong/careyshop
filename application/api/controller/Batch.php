<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    API批量调用
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/12/1
 */

namespace app\api\controller;

use think\helper\Str;

class Batch extends CareyShop
{
    /**
     * API批量调用首页
     * @access public
     * @return array
     */
    public function index()
    {
        // 删除多余数据,避免影响其他模块
        unset($this->params['appkey']);
        unset($this->params['token']);
        unset($this->params['timestamp']);
        unset($this->params['format']);
        unset($this->params['method']);

        $result = [];
        foreach ($this->params as $key => $value) {
            // 为生成控制器与模型对象准备数据
            $version = Str::lower($value['version']);
            $module = Str::studly($value['module']);
            $method = $value['method'];

            $oldData['version'] = $value['version'];
            $oldData['module'] = $value['module'];
            $oldData['method'] = $value['method'];
            $oldData['class'] = sprintf('app\\api\\controller\\%s\\%s', $version, $module);

            $callback = null;
            static::$model = null;
            $authUrl = sprintf('%s/%s/%s/%s', $this->request->module(), $version, $module, $method);

            try {
                // 验证数据
                $validate = $this->validate($value, 'CareyShop.batch');
                if (true !== $validate) {
                    throw new \Exception($validate);
                }

                // 权限验证
                if (!$this->apiDebug) {
                    if (!static::$auth->check($authUrl)) {
                        throw new \Exception('权限不足');
                    }
                }

                $route = $oldData['class']::initMethod();
                if (!array_key_exists($method, $route)) {
                    throw new \Exception('method路由方法不存在');
                }

                $method = $route[$method];
                if (!isset($method[1])) {
                    $method[1] = 'app\\common\\model\\' . $module;
                }

                if (class_exists($method[1])) {
                    static::$model = new $method[1];
                } else {
                    throw new \Exception('method不支持批量调用');
                }

                if (!method_exists(static::$model, $method[0])) {
                    throw new \Exception('method成员方法不存在');
                }

                unset($value['version'], $value['module'], $value['method']);
                $callback = call_user_func([static::$model, $method[0]], $value);
            } catch (\Exception $e) {
                $callback = false;
                $this->setError($e->getMessage());
            }

            // 确定调用结果
            if (false === $callback) {
                !empty($this->error) ?: $this->error = static::$model->getError();
            }

            $result[$key] = [
                'status'  => false !== $callback ? 200 : 500,
                'message' => false !== $callback ? 'success' : $this->getError(),
                'version' => $oldData['version'],
                'module'  => $oldData['module'],
                'method'  => $oldData['method'],
                'data'    => $callback,
            ];

            // 日志记录
            static::$auth->saveLog(
                $authUrl,
                $this->request,
                false !== $callback ? $result[$key] : false,
                $oldData['class'],
                $this->getError()
            );
        }

        return $this->outputResult($result);
    }
}