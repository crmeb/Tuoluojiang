<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Config;

use App\Constants\CacheEnum;
use App\Http\Dao\Config\SystemStorageDao;
use App\Http\Service\Other\UploadService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\exceptions\AdminException;
use Tuoluojiang\Open\services\FormService as FormBuilder;
use FormBuilder\Exception\FormBuilderException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Class SystemStorageService.
 */
class SystemStorageService extends BaseService
{
    public function __construct(SystemStorageDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param null|mixed $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        [$page, $limit]      = $this->getPageValue();
        $config              = $this->getStorageConfig((int) $where['type']);
        $where['access_key'] = $config['accessKey'];
        $list                = $this->dao->getList($where, ['*'], $page, $limit, 'id');
        foreach ($list as &$item) {
            $item['cname']        = str_replace('https://', '', $item['domain']);
            $item['_add_time']    = $item['created_at'];
            $item['_update_time'] = $item['updated_at'];
            $service              = UploadService::init($item['type']);
            $region               = $service->getRegion();
            foreach ($region as $value) {
                if (strstr($item['region'], $value['value'])) {
                    $item['_region'] = $value['label'];
                }
            }
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * @return array
     * @throws FormBuilderException
     */
    public function getFormStorage(int $type)
    {
        $upload = UploadService::init($type);

        $config     = $this->getStorageConfig($type);
        $ruleConfig = [];
        if (! $config['accessKey']) {
            $ruleConfig = [
                FormBuilder::input('accessKey', 'AccessKeyId', $config['accessKey'] ?? '')->required(),
                FormBuilder::input('secretKey', 'AccessKeySecret', $config['secretKey'] ?? '')->required(),
            ];
        }

        if ($type === 4 && isset($config['appid']) && ! $config['appid']) {
            $ruleConfig[] = FormBuilder::input('appid', 'APPID', $config['appid'] ?? '')->required();
        }

        $rule = [
            FormBuilder::input('name', '空间名称')->required(),
            FormBuilder::select('region', '空间区域')->options($upload->getRegion())->required(),
            FormBuilder::radio('acl', '读写权限', 'public-read')->options([
                ['label' => '公共读(推荐)', 'value' => 'public-read'],
                ['label' => '公共读写', 'value' => 'public-read-write'],
            ])->required(),
        ];

        $rule = array_merge($ruleConfig, $rule);
        return $this->createElementForm('添加云空间', $rule, '/ent/config/storage/' . $type);
    }

    /**
     * @return array
     */
    public function getStorageConfig(int $type)
    {
        $config = [
            'accessKey' => '',
            'secretKey' => '',
        ];
        switch ($type) {
            case 2:// 七牛
                $config = [
                    'accessKey' => sys_config('qiniu_accessKey', ''),
                    'secretKey' => sys_config('qiniu_secretKey', ''),
                ];
                break;
            case 3:// oss 阿里云
                $config = [
                    'accessKey' => sys_config('accessKey', ''),
                    'secretKey' => sys_config('secretKey', ''),
                ];
                break;
            case 4:// cos 腾讯云
                $config = [
                    'accessKey' => sys_config('tengxun_accessKey', ''),
                    'secretKey' => sys_config('tengxun_secretKey', ''),
                    'appid'     => sys_config('tengxun_appid', ''),
                ];
                break;
            case 5:// cos 京东云
                $config = [
                    'accessKey'     => sys_config('jd_accessKey', ''),
                    'secretKey'     => sys_config('jd_secretKey', ''),
                    'storageRegion' => sys_config('jd_storage_region', ''),
                ];
                break;
            case 6:// cos 华为云
                $config = [
                    'accessKey' => sys_config('hw_accessKey', ''),
                    'secretKey' => sys_config('hw_secretKey', ''),
                ];
                break;
            case 7:// cos 天翼云
                $config = [
                    'accessKey' => sys_config('ty_accessKey', ''),
                    'secretKey' => sys_config('ty_secretKey', ''),
                ];
                break;
        }
        return $config;
    }

    /**
     * @return array
     * @throws FormBuilderException
     * @throws BindingResolutionException
     */
    public function getFormStorageConfig(int $type)
    {
        $config = $this->getStorageConfig($type);
        $rule   = [
            FormBuilder::hidden('type', $type),
            FormBuilder::input('accessKey', 'AccessKey', $config['accessKey'] ?? '')->required(),
            FormBuilder::input('secretKey', 'SecretKey', $config['secretKey'] ?? '')->required(),
        ];

        if ($type === 4) {
            $rule[] = FormBuilder::input('appid', 'AppId', $config['appid'] ?? '')->required();
        }

        if ($type == 5 || $type == 6) {
            $rule[] = FormBuilder::input('storageRegion', 'Endpoint', $config['storageRegion'] ?? '')->required();
        }

        return $this->createElementForm('配置信息', $rule, '/ent/config/storage/config');
    }

    /**
     * 删除空间.
     * @return true
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteStorage(int $id)
    {
        $storageInfo = $this->dao->get(['is_delete' => 0, 'id' => $id]);
        if (! $storageInfo) {
            throw new AdminException('删除的云存储空间不存在');
        }
        if ($storageInfo->status) {
            throw new AdminException('云存储正在使用中,需要启动其他空间才能删除');
        }

        try {
            $upload = UploadService::init($storageInfo->type);
            $res    = $upload->deleteBucket($storageInfo->name, $storageInfo->region);
            if ($res === false) {
                throw new AdminException($upload->getError());
            }
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
        $storageInfo->is_delete = 1;
        $storageInfo->save();
        Cache::tags([CacheEnum::TAG_CONFIG])->flush();

        return true;
    }

    public function saveConfig(int $type, array $data)
    {
        // 保存配置信息
        if ($type !== 1) {
            $accessKey = $secretKey = $appid = $storageRegion = '';
            if (isset($data['accessKey'], $data['secretKey']) && $data['accessKey'] && $data['secretKey']) {
                $accessKey = $data['accessKey'];
                $secretKey = $data['secretKey'];
                unset($data['accessKey'], $data['secretKey']);
            }
            if (isset($data['appid']) && $data['appid']) {
                $appid = $data['appid'];
                unset($data['appid']);
            }
            if (isset($data['storageRegion']) && $data['storageRegion']) {
                $storageRegion = $data['storageRegion'];
                unset($data['storageRegion']);
            }
            if (! $accessKey || ! $secretKey) {
                return true;
            }
            $make = app()->get(SystemConfigService::class);
            switch ($type) {
                case 2:// 七牛
                    $make->update(['key' => 'qiniu_accessKey'], ['value' => $accessKey]);
                    $make->update(['key' => 'qiniu_secretKey'], ['value' => $secretKey]);
                    break;
                case 3:// oss 阿里云
                    $make->update(['key' => 'accessKey'], ['value' => $accessKey]);
                    $make->update(['key' => 'secretKey'], ['value' => $secretKey]);
                    break;
                case 4:// cos 腾讯云
                    $make->update(['key' => 'tengxun_accessKey'], ['value' => $accessKey]);
                    $make->update(['key' => 'tengxun_secretKey'], ['value' => $secretKey]);
                    $make->update(['key' => 'tengxun_appid'], ['value' => $appid]);
                    break;
                case 5:// oss 京东云
                    $make->update(['key' => 'jd_accessKey'], ['value' => $accessKey]);
                    $make->update(['key' => 'jd_secretKey'], ['value' => $secretKey]);
                    $make->update(['key' => 'jd_storage_region'], ['value' => $storageRegion]);
                    break;
                case 6:// oss 华为云
                    $make->update(['key' => 'hw_accessKey'], ['value' => $accessKey]);
                    $make->update(['key' => 'hw_secretKey'], ['value' => $secretKey]);
                    $make->update(['key' => 'hw_storage_region'], ['value' => $storageRegion]);
                    break;
                case 7:// oss 天翼云
                    $make->update(['key' => 'ty_accessKey'], ['value' => $accessKey]);
                    $make->update(['key' => 'ty_secretKey'], ['value' => $secretKey]);
                    break;
            }
            Cache::tags([CacheEnum::TAG_CONFIG])->flush();
        }
    }

    /**
     * 保存云存储.
     * @return mixed
     */
    public function saveStorage(int $type, array $data)
    {
        // 保存配置信息
        $this->saveConfig($type, $data);
        // 保存云存储
        $data['type'] = $type;
        $upload       = UploadService::init($type);
        $res          = $upload->createBucket($data['name'], $data['region'], $data['acl']);
        if ($res === false) {
            throw new AdminException($upload->getError());
        }
        $data['region'] = $this->getReagionHost($type, $data['region']);
        switch ($type) {
            case 2:
                $data['access_key'] = sys_config('qiniu_accessKey');
                break;
            case 3:
                $data['access_key'] = sys_config('accessKey');
                break;
            case 4:
                if ($this->dao->count(['name' => $data['name'] . '-' . sys_config('tengxun_appid')])) {
                    throw new AdminException('云空间名称不能重复');
                }
                $data['access_key'] = sys_config('tengxun_accessKey');
                $data['name']       = $data['name'] . '-' . sys_config('tengxun_appid');
                $data['domain']     = $this->getDomain($type, $data['name'], $data['region'], sys_config('tengxun_appid') ?: '');
                break;
            case 5:
                $data['access_key'] = sys_config('jd_accessKey');
                break;
            case 6:
                $data['access_key'] = sys_config('hw_accessKey');
                break;
            case 7:
                $data['access_key'] = sys_config('ty_accessKey');
                break;
        }
        Cache::tags([CacheEnum::TAG_CONFIG])->flush();
        return $this->dao->create($data);
    }

    /**
     * 同步云储存桶.
     * @return bool
     */
    public function synchronization(int $type)
    {
        try {
            switch ($type) {
                case 2:// 七牛
                    $config = $this->getStorageConfig($type);
                    $upload = UploadService::init($type);
                    $list   = $upload->listbuckets();
                    foreach ($list as $item) {
                        if (! $this->dao->count(['name' => $item['id'], 'access_key' => $config['accessKey']])) {
                            $this->dao->create([
                                'type'       => $type,
                                'access_key' => $config['accessKey'],
                                'name'       => $item['id'],
                                'region'     => $item['region'],
                                'acl'        => $item['private'] == 0 ? 'public-read' : 'private',
                                'status'     => 0,
                                'is_delete'  => 0,
                            ]);
                        }
                    }
                    break;
                case 3:// oss 阿里云
                    $upload = UploadService::init($type);
                    $list   = $upload->listbuckets();
                    $config = $this->getStorageConfig($type);
                    foreach ($list as $item) {
                        if (! $this->dao->exists(['name' => $item['name'], 'access_key' => $config['accessKey']])) {
                            $region = $this->getReagionHost($type, $item['location']);
                            $this->dao->create([
                                'type'       => $type,
                                'access_key' => $config['accessKey'],
                                'name'       => $item['name'],
                                'region'     => $region,
                                'acl'        => 'public-read',
                                'domain'     => $this->getDomain($type, $item['name'], $region),
                                'status'     => 0,
                                'is_delete'  => 0,
                                'created_at' => Carbon::parse($item['createTime'])->toDateTimeString(),
                            ]);
                        }
                    }
                    break;
                case 4:// cos 腾讯云
                    $upload = UploadService::init($type);
                    $list   = $upload->listbuckets();
                    if (! empty($list['Name'])) {
                        $newList = $list;
                        $list    = [];
                        $list[]  = $newList;
                    }
                    $config = $this->getStorageConfig($type);
                    foreach ($list as $item) {
                        if (! $this->dao->exists(['name' => $item['Name'], 'access_key' => $config['accessKey']])) {
                            $this->dao->create([
                                'type'       => $type,
                                'access_key' => $config['accessKey'],
                                'name'       => $item['Name'],
                                'region'     => $item['Location'],
                                'acl'        => 'public-read',
                                'status'     => 0,
                                'domain'     => sys_config('tengxun_appid') ? $this->getDomain($type, $item['Name'], $item['Location']) : '',
                                'is_delete'  => 0,
                                'created_at' => Carbon::parse($item['CreationDate'])->toDateTimeString(),
                            ]);
                        }
                    }
                    break;
                case 5:// cos 京东云
                    $upload   = UploadService::init($type);
                    $res      = $upload->listbuckets(sys_config('jd_storage_region'));
                    $list     = $res['Buckets'];
                    $location = explode('.', $res['@metadata']['effectiveUri'])[1] ?? 'cn-north-1';
                    $config   = $this->getStorageConfig($type);
                    foreach ($list as $item) {
                        if (! $this->dao->exists(['name' => $item['Name'], 'access_key' => $config['accessKey']])) {
                            $this->dao->create([
                                'type'       => $type,
                                'access_key' => $config['accessKey'],
                                'name'       => $item['Name'],
                                'region'     => $location,
                                'acl'        => 'public-read',
                                'status'     => 0,
                                'domain'     => $this->getDomain($type, $item['Name'], $location),
                                'is_delete'  => 0,
                            ]);
                        }
                    }
                    break;
                case 6:// cos 华为云
                case 7:// cos 天翼云
                    $upload = UploadService::init($type);
                    $list   = $upload->listbuckets();
                    if (! empty($list['Name'])) {
                        $newList = $list;
                        $list    = [];
                        $list[]  = $newList;
                    }
                    $config = $this->getStorageConfig($type);
                    foreach ($list as $item) {
                        $this->dao->firstOrCreate(['name' => $item['Name'], 'access_key' => $config['accessKey']], [
                            'type'       => $type,
                            'access_key' => $config['accessKey'],
                            'name'       => $item['Name'],
                            'region'     => $item['Location'],
                            'acl'        => 'public-read',
                            'status'     => 0,
                            'domain'     => $this->getDomain($type, $item['Name'], $item['Location']) ?: "obs.{$item['name']}.{$item['Location']}.myhuaweicloud.com",
                            'is_delete'  => 0,
                            'created_at' => Carbon::parse($item['CreationDate'])->toDateTimeString(),
                        ]);
                    }
                    break;
            }
        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }
        return Cache::tags([CacheEnum::TAG_CONFIG])->flush();
    }

    /**
     * @return mixed|string
     */
    public function getReagionHost(int $type, string $region)
    {
        $upload     = UploadService::init($type);
        $regionList = $upload->getRegion();
        foreach ($regionList as $item) {
            if (str_contains($item['value'], $region)) {
                return $item['value'];
            }
        }
        return '';
    }

    /**
     * 获取域名.
     * @return string
     */
    public function getDomain(int $type, string $name, string $reagion, string $appid = '')
    {
        $domainName = '';
        switch ($type) {
            case 3:// oss 阿里云
                $domainName = 'https://' . $name . '.' . $reagion;
                break;
            case 4:// cos 腾讯云
                $domainName = 'https://' . $name . ($appid ? '-' . $appid : '') . '.cos.' . $reagion . '.myqcloud.com';
                break;
            case 5:// cos 京东云
                $domainName = 'https://' . $name . '.s3.' . $reagion . '.jdcloud-oss.com';
                break;
            case 6:// cos 华为云
                $domainName = 'https://' . $name . '.obs.' . $reagion . '.myhuaweicloud.com';
                break;
            case 7:// cos 天翼云
                $domainName = 'https://' . $name . '.obs.' . $reagion . '.ctyun.cn';
                break;
        }
        return $domainName;
    }

    /**
     * 获取云存储配置.
     * @return array|string[]
     */
    public function getConfig(int $type)
    {
        $res = ['name' => '', 'region' => '', 'domain' => '', 'cdn' => ''];
        try {
            $config = $this->dao->get(['type' => $type, 'status' => 1, 'is_delete' => 0]);
            if ($config) {
                return ['name' => $config->name, 'region' => $config->region, 'domain' => $config->domain, 'cdn' => $config->cdn];
            }
        } catch (\Throwable $e) {
        }
        return $res;
    }

    /**
     * 获取修改域名表单.
     * @return array
     * @throws FormBuilderException
     */
    public function getUpdateDomainForm(int $id)
    {
        $storage = $this->dao->get(['id' => $id], ['domain', 'cdn']);
        $rule    = [
            FormBuilder::input('domain', '空间域名', $storage['domain']),
            FormBuilder::input('cdn', 'cdn域名', $storage['cdn']),
        ];
        return $this->createElementForm('修改空间域名', $rule, '/ent/config/storage/domain/' . $id);
    }

    /**
     * 修改域名并绑定.
     * @return true
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateDomain(int $id, string $domain, array $data = [])
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw new AdminException('数据不存在');
        }
        if ($info->domain != $domain) {
            $info->domain = $domain;
            $upload       = UploadService::init($info->type);
            // 是否添加过域名不存在需要绑定域名
            $domainList  = $upload->getDomian($info->name, $info->region);
            $domainParse = parse_url($domain);
            if ($domainParse === false) {
                throw new AdminException('域名输入有误');
            }
            if (! in_array($domainParse['host'], $domainList)) {
                // 绑定域名到云储存桶
                $res = $upload->bindDomian($info->name, $domain, $info->region);
                if ($res === false) {
                    throw new AdminException($upload->getError());
                }
            }
            // 七牛云需要通过接口获取cname
            if (2 === ((int) $info->type)) {
                $resDomain   = $upload->getDomianInfo($domain);
                $info->cname = $resDomain['cname'] ?? '';
            }
            $info->save();
        }
        if ($info->cdn != $data['cdn']) {
            $info->cdn = $data['cdn'];
            $info->save();
        }

        Cache::tags([CacheEnum::TAG_CONFIG])->flush();

        return true;
    }
}
