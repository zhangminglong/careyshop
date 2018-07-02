<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    应用安装包模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/3/9
 */

namespace app\common\model;

use think\Cache;
use think\Config;

class AppInstall extends CareyShop
{
    /**
     * 是否需要自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'app_install_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'app_install_id' => 'integer',
        'count'          => 'integer',
    ];

    /**
     * 系统标识修改器
     * @access protected
     * @param  string $value 值
     * @return string
     */
    protected function setUserAgentAttr($value)
    {
        return mb_strtolower($value, 'utf-8');
    }

    /**
     * 添加一个应用安装包
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addAppInstallItem($data)
    {
        if (!$this->validateData($data, 'AppInstall')) {
            return false;
        }

        // 避免无关数据
        unset($data['app_install_id'], $data['count']);

        if (false !== $this->allowField(true)->save($data)) {
            Cache::clear('AppInstall');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个应用安装包
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setAppInstallItem($data)
    {
        if (!$this->validateSetData($data, 'AppInstall.set')) {
            return false;
        }

        $field = ['user_agent', 'name', 'ver', 'url'];
        $map['app_install_id'] = ['eq', $data['app_install_id']];

        if (false !== $this->allowField($field)->save($data, $map)) {
            Cache::clear('AppInstall');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 获取一个应用安装包
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getAppInstallItem($data)
    {
        if (!$this->validateData($data, 'AppInstall.item')) {
            return false;
        }

        $result = self::get($data['app_install_id']);
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 批量删除应用安装包
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delAppInstallList($data)
    {
        if (!$this->validateData($data, 'AppInstall.del')) {
            return false;
        }

        self::destroy($data['app_install_id']);
        Cache::clear('AppInstall');

        return true;
    }

    /**
     * 获取应用安装包列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getAppInstallList($data)
    {
        if (!$this->validateData($data, 'AppInstall.list')) {
            return false;
        }

        // 搜索条件
        $map = [];
        empty($data['user_agent']) ?: $map['user_agent'] = ['eq', $data['user_agent']];
        empty($data['name']) ?: $map['name'] = ['like', '%' . $data['name'] . '%'];

        $totalResult = $this->where($map)->count();
        if ($totalResult <= 0) {
            return ['total_result' => 0];
        }

        $result = self::all(function ($query) use ($data, $map) {
            // 翻页页数
            $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

            // 每页条数
            $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'desc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'app_install_id';

            $query
                ->where($map)
                ->order([$orderField => $orderType])
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }

    /**
     * 根据条件查询是否有更新
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function queryAppInstallUpdated($data)
    {
        if (!$this->validateData($data, 'AppInstall.updated')) {
            return false;
        }

        $result = self::all(function ($query) use ($data) {
            $query
                ->cache(true, null, 'AppInstall')
                ->field('ver')
                ->where(['user_agent' => ['eq', $data['user_agent']]]);
        });

        if ($result->isEmpty()) {
            return $this->setError('不存在标识为 ' . $data['user_agent'] . ' 的应用安装包');
        }

        foreach ($result as $value) {
            if (version_compare($value->getAttr('ver'), $data['ver'], '>')) {
                return true;
            }
        }

        return $this->setError('当前应用版本已是最新');
    }

    /**
     * 根据请求获取一个应用安装包
     * @access public
     * @return array|false
     * @throws
     */
    public function requestAppInstallItem()
    {
        // 获取所有安装包列表
        $result = self::cache(true, null, 'AppInstall')->column('user_agent,ver,url', 'app_install_id');
        if (false === $result) {
            Cache::clear('AppInstall');
            return false;
        }

        $data = [];
        $maxVersion = '';
        $agent = request()->server('HTTP_USER_AGENT');

        foreach ($result as $value) {
            if (false !== mb_stripos($agent, $value['user_agent'], null, 'utf-8')) {
                // 获取最新版本号的安装包
                if (version_compare($value['ver'], $maxVersion, '>=')) {
                    $maxVersion = $value['ver'];
                    $data = $value;
                }
            }
        }

        // 后续处理数据
        if (!empty($data)) {
            // 如果是安卓微信,则返回自定义中间页
            if (mb_stripos($agent, 'Android', null, 'utf-8') && mb_stripos($agent, 'MicroMessenger', null, 'utf-8')) {
                $data['url'] = Config::get('weixin_url.value', 'system_info');
            }

            // 自增访问次数
            $this->where(['app_install_id' => ['eq', $data['app_install_id']]])->setInc('count');
        }

        return $data;
    }
}