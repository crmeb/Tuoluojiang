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

namespace App\Http\Controller\AdminApi\Config;

use App\Constants\CacheEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Middleware\AuthAdmin;
use App\Http\Middleware\AuthEnterprise;
use App\Http\Middleware\CheckRuleCompany;
use App\Http\Requests\system\SystemStorageRequest;
use App\Http\Service\Config\SystemConfigService;
use App\Http\Service\Config\SystemStorageService;
use App\Http\Service\Other\UploadService;
use FormBuilder\Exception\FormBuilderException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 存储配置.
 */
#[Prefix('ent/config/storage')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class SystemStorageController extends AuthController
{
    public function __construct(SystemStorageService $services)
    {
        $this->service = $services;
        parent::__construct();
        $this->middleware([AuthAdmin::class, AuthEnterprise::class, CheckRuleCompany::class]);
    }

    /**
     * @return mixed
     */
    #[Get('index', '云存储列表')]
    public function index()
    {
        return $this->success($this->service->getList(['type' => $this->request->get('type')]));
    }

    /**
     * 获取创建数据表单.
     * @return mixed
     * @throws FormBuilderException
     */
    #[Get('create/{type}', '获取云存储创建表单')]
    public function create($type)
    {
        if (! $type) {
            return $this->fail('参数错误');
        }
        return $this->success($this->service->getFormStorage((int) $type));
    }

    /**
     * 获取配置表单.
     * @return mixed
     * @throws FormBuilderException
     * @throws BindingResolutionException
     */
    #[Get('form/{type}', '获取云存储配置表单')]
    public function getConfigForm($type)
    {
        return $this->success($this->service->getFormStorageConfig((int) $type));
    }

    /**
     * 获取配置类型.
     * @return mixed
     */
    #[Get('config', '获取云存储配置')]
    public function getConfig()
    {
        return $this->success(['type' => (int) sys_config('upload_type', 1)]);
    }

    /**
     * @return mixed
     */
    #[Post('config', '保存云存储配置')]
    public function saveConfig()
    {
        $type = (int) $this->request->post('type', 0);

        $data = $this->request->postMore([
            ['accessKey', ''],
            ['secretKey', ''],
            ['appid', ''],
            ['storageRegion', ''],
        ]);

        $this->service->saveConfig($type, $data);

        return $this->success('保存成功');
    }

    /**
     * @return mixed
     */
    #[Get('sync/{type}', '同步云存储')]
    public function sync($type)
    {
        $this->service->synchronization((int) $type);
        return $this->success('同步成功');
    }

    /**
     * 保存类型.
     * @return mixed
     */
    #[Post('{type}', '保存云存储数据')]
    public function save($type)
    {
        $data = $this->request->postMore([
            ['accessKey', ''],
            ['secretKey', ''],
            ['appid', ''],
            ['name', ''],
            ['region', ''],
            ['acl', ''],
        ]);
        $type = (int) $type;
        if ($type === 4) {
            if (! $data['appid'] && ! sys_config('tengxun_appid')) {
                return $this->fail('缺少APPID');
            }
        }
        if (! $data['accessKey']) {
            unset($data['accessKey'], $data['secretKey'], $data['appid']);
        }
        $this->service->saveStorage((int) $type, $data);

        return $this->success('添加成功');
    }

    /**
     * 修改状态
     * @return mixed
     */
    #[Put('status/{id}', '修改云存储状态')]
    public function status($id)
    {
        if (! $id) {
            return $this->fail('参数错误');
        }

        $info         = $this->service->get($id);
        $info->status = 1;
        if (! $info->domain) {
            return $this->fail('请先设置空间域名');
        }
        // 设置跨域规则
        try {
            $upload = UploadService::init($info->type);
            $res    = $upload->setBucketCors($info->name, $info->region);
            if ($res === false) {
                return $this->fail($upload->getError());
            }
        } catch (\Throwable $e) {
        }

        // 修改状态
        $this->service->transaction(function () use ($info) {
            //            $this->service->update(['status' => 1, 'is_delete' => 0], ['status' => 0]);
            $this->service->update(['type' => $info->type], ['status' => 0]);
            $info->save();
        });
        Cache::tags([CacheEnum::TAG_CONFIG])->flush();
        return $this->success('修改成功');
    }

    /**
     * @return mixed
     * @throws FormBuilderException
     */
    #[Get('domain/{id}', '获取云存储域名表单')]
    public function getUpdateDomainForm($id)
    {
        return $this->success($this->service->getUpdateDomainForm((int) $id));
    }

    /**
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Get('method', '获取修改云存储域名表单')]
    public function getStorageConfig()
    {
        return $this->success(sys_more([
            'upload_type',
            'thumb_big_height',
            'thumb_big_width',
            'thumb_mid_height',
            'thumb_mid_width',
            'thumb_small_height',
            'thumb_small_width',
            'image_watermark_status',
            'watermark_type',
            'watermark_image',
            'watermark_opacity',
            'watermark_position',
            'watermark_rotate',
            'watermark_text',
            'watermark_text_angle',
            'watermark_text_color',
            'watermark_text_size',
            'watermark_x',
            'watermark_y',
        ]));
    }

    /**
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Post('domain/{id}', '修改云存储域名')]
    public function updateDomain($id)
    {
        $domain = $this->request->post('domain', '');
        $cdn    = $this->request->post('cdn', '');
        $data   = $this->request->postMore([
            ['pri', ''],
            ['ca', ''],
        ]);
        if (! $domain) {
            return $this->fail('参数错误');
        }
        if (! str_contains($domain, 'https://') && ! str_contains($domain, 'http://')) {
            return $this->fail('格式错误，请输入格式为：http://域名');
        }

        $this->service->updateDomain((int) $id, $domain, ['cdn' => $cdn]);

        return $this->success('修改成功');
    }

    /**
     * 删除.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Delete('{id}', '删除云存储')]
    public function delete($id)
    {
        if (! $id) {
            return $this->fail('参数错误');
        }

        if ($this->service->deleteStorage((int) $id)) {
            return $this->success('删除成功');
        }
        return $this->fail('删除失败');
    }

    /**
     * 切换存储类型.
     * @return mixed
     */
    #[Put('save_type/{type}', '选择存储方式')]
    public function uploadType(SystemConfigService $services, $type)
    {
        $status = $this->service->count(['type' => $type, 'status' => 1]);
        if (! $status && $type != 1) {
            return $this->fail('没有正在使用的存储空间');
        }
        $services->update(['key' => 'upload_type'], ['value' => $type]);
        Cache::tags([CacheEnum::TAG_CONFIG])->flush();
        if ($type != 1) {
            $msg = '切换云存储成功,请检查是否开启使用了存储空间';
        } else {
            $msg = '切换本地存储成功';
        }
        return $this->success($msg);
    }

    /**
     * 保存云存储详细配置.
     * @return mixed
     */
    #[Put('save_basic', '保存云存储详细配置')]
    public function updateConfig(SystemStorageRequest $request, SystemConfigService $service)
    {
        $data = $request->postMore([
            ['upload_type', 4],
            ['image_watermark_status', 1],
            ['thumb_big_height', 800],
            ['thumb_big_width', 800],
            ['thumb_mid_height', 300],
            ['thumb_mid_width', 300],
            ['thumb_small_height', 150],
            ['thumb_small_width', 150],
            ['upload_type', 4],
            ['watermark_image', ''],
            ['watermark_opacity', ''],
            ['watermark_position', ''],
            ['watermark_rotate', ''],
            ['watermark_text', ''],
            ['watermark_text_angle', ''],
            ['watermark_text_color', ''],
            ['watermark_text_size', ''],
            ['watermark_type', 1],
            ['watermark_x', ''],
            ['watermark_y', ''],
        ]);
        $service->saveLoginConfig($data);
        return $this->success('保存成功');
    }
}
