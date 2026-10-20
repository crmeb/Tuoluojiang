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

namespace App\Http\Service\WorkExternalContact;

use App\Constants\Work\MediaEnum;
use App\Http\Dao\WorkExternalContact\WorkMassMessagingTempAttachDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 快捷回复模板
 */
class WorkMassMessagingTempAttachService extends BaseService
{
    use ResourceServiceTrait;

    public function __construct(WorkMassMessagingTempAttachDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @param null|mixed $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'sort', array $with = ['file']): array
    {
        $list = $this->dao->getList($where, $field, 0, 0, $sort, $with);
        return collect($list)->map(function ($item) {
            $item['file_url'] = $item['file'] ? link_file($item['file']['file_url']) : '';
            return $item;
        })->all();
    }

    /**
     * 保存.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceSave(array $data)
    {
        $this->validateType($data);
        $data   = $this->filterDataByRules($data);
        $fileId = $data['file_id'];
        unset($data['file_id']);
        return $this->transaction(function () use ($data, $fileId) {
            $result = $this->dao->create($data);
            $fileId && app()->get(WorkMediaService::class)->setLink($fileId, $result->id, MediaEnum::LINK_TYPE_MASS);
            return $result;
        });
    }

    /**
     * 获取修改数据.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id)
    {
        $data = $this->dao->get($id, with: ['file']);
        return collect($data)->map(function ($item) {
            $item->file_url = $item->file ? link_file($item->file->file_url) : '';
        })->all();
    }

    /**
     * 修改.
     * @param mixed $id
     * @return int|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceUpdate($id, array $data)
    {
        $this->validateType($data);
        $data   = $this->filterDataByRules($data);
        $fileId = $data['file_id'];
        unset($data['file_id']);
        return $this->transaction(function () use ($id, $data, $fileId) {
            $result = $this->dao->update($id, $data);
            app()->get(WorkMediaService::class)->setLink($fileId, $id, MediaEnum::LINK_TYPE_MASS);
            return $result;
        });
    }

    /**
     * 删除.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        return $this->transaction(function () use ($id) {
            $this->dao->delete($id);
            app()->get(WorkMediaService::class)->deleteLink((int) $id, MediaEnum::LINK_TYPE_REPLY);
            return true;
        });
    }

    /**
     * 删除关联.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteLink(int $linkId): bool
    {
        $fileIds = $this->dao->column(['temp_id' => $linkId], 'id');
        $fileIds && $this->setLink($fileIds, 0);
        return true;
    }

    /**
     * 设置关联.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function setLink(array|int $attachId, int $tempId): int
    {
        $attachId   = is_array($attachId) ? $attachId : [$attachId];
        $oldFileIds = $this->dao->column(['temp_id' => $tempId], 'id');
        $diff       = collect(array_diff($oldFileIds, $attachId) ?? [])->values()->all();
        if ($diff) {
            app()->get(WorkMediaService::class)->deleteMedia($diff);
            $this->dao->delete(['id' => $diff]);
        }
        return $this->dao->update(['id' => $attachId], ['temp_id' => $tempId]);
    }

    /**
     * 验证类型.
     * @param mixed $data
     */
    private function validateType($data): bool
    {
        switch ($data['types']) {
            case MediaEnum::TEMP_TEXT:
                if (! $data['content']) {
                    throw $this->exception('请输入回复内容');
                }
                break;
            case MediaEnum::TEMP_IMAGE:
                if (! $data['file_id']) {
                    throw $this->exception('请上传图片');
                }
                break;
            case MediaEnum::TEMP_FILE:
                if (! $data['file_id']) {
                    throw $this->exception('请上传文件');
                }
                break;
            case MediaEnum::TEMP_VIDEO:
                if (! $data['file_id']) {
                    throw $this->exception('请上传视频');
                }
                break;
            case MediaEnum::TEMP_LINK:
                if (! $data['link']) {
                    throw $this->exception('请填写网页链接');
                }
                break;
            case MediaEnum::TEMP_MINI_PROGRAM:
                if (! $data['file_id']) {
                    throw $this->exception('请上传小程序封面');
                }
                if (! $data['link']) {
                    throw $this->exception('请填写小程序页面路径');
                }
                if (! $data['app_id']) {
                    throw $this->exception('请填写小程序appid');
                }
                if (! $data['title']) {
                    throw $this->exception('请填写小程序标题');
                }
                break;
            default:
                throw $this->exception('请选择类型');
        }
        return true;
    }

    /**
     * 根据规则数组过滤目标数据.
     */
    private function filterDataByRules(array $data): array
    {
        $rules = [
            ['types', MediaEnum::TEMP_TEXT],
            ['title', ''],
            ['info', ''],
            ['link', ''],
            ['app_id', ''],
            ['sort', 0],
            ['file_id', 0],
            ['uid', auth('admin')->id()],
        ];
        $filtered = [];
        foreach ($rules as $rule) {
            $filtered[$rule[0]] = $data[$rule[0]] ?? $rule[1];
        }
        return $filtered;
    }
}
