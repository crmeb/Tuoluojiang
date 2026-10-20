<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\WorkExternalContact;

use App\Constants\Work\MediaEnum;
use App\Http\Dao\WorkExternalContact\WorkReplyTempDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 快捷回复模板
 */
class WorkReplyTempService extends BaseService
{
    use ResourceServiceTrait;

    public function __construct(WorkReplyTempDao $dao)
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
    public function getList(array $where, array $field = ['*'], $sort = 'sort', array $with = ['file', 'group', 'creator']): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $list           = collect($list)->map(function ($item) {
            $item['file_url'] = isset($item['file']) && $item['file'] ? link_file($item['file']['file_url']) : '';
            $item['media_id'] = isset($item['file']) && $item['file'] ? $item['file']['media_id'] : '';
            return $item;
        })->all();
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 获取个人库列表.
     * @param array $where
     * @param array $field 查询字段
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getPersonalList(array $where = [], array $field = ['*']): array
    {
        return $this->getList($where, $field);
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
        return $this->transaction(function () use ($data) {
            $fileId = $data['file_id'];
            unset($data['file_id']);
            $result = $this->dao->create($data);
            $fileId && app()->get(WorkMediaService::class)->setLink($fileId, $result->id, MediaEnum::LINK_TYPE_REPLY);
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
        return $this->dao->get($id, with: ['file' => fn ($query) => $query->select(['id', 'file_url as url', 'real_name as name', 'file_size as size', 'link_id'])])?->toArray();
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
        return $this->transaction(function () use ($id, $data) {
            $fileId = $data['file_id'];
            unset($data['file_id'], $data['uid']);
            $result = $this->dao->update($id, $data);
            $fileId && app()->get(WorkMediaService::class)->setLink($fileId, (int) $id, MediaEnum::LINK_TYPE_REPLY);
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
     * 导入.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function import(array $data, int $uid)
    {
        $groupService = app()->get(WorkReplyTempGroupService::class);
        return collect($data)->reduce(function ($stats, $val) use ($uid, $groupService) {
            try {
                if (! $val['group_id']) {
                    ++$stats['error'];
                    throw $this->exception('缺少快捷回复分组');
                }
                if (! $val['content']) {
                    ++$stats['error'];
                    throw $this->exception('缺少快捷回复内容');
                }
                $groupId = $groupService->value(['name' => $val['group_id']], 'id') ?: $groupService->create(['name' => $val['group_id']])->id;
                $this->dao->create([
                    'types'    => MediaEnum::TEMP_TEXT,
                    'uid'      => $uid,
                    'content'  => $val['content'],
                    'group_id' => $groupId,
                ]);
                ++$stats['success'];
            } catch (\Exception $e) {
                ++$stats['error'];
                Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
            }
            return $stats;
        }, ['success' => 0, 'error' => 0]);
    }

    /**
     * 验证个人库访问权限.
     * @param int $id 内容ID
     * @param int $uid 用户ID
     */
    public function checkPersonalAccess(int $id, int $uid): bool
    {
        $item = $this->dao->get($id);
        if (! $item) {
            throw $this->exception('内容不存在');
        }

        // 公共库所有人都可以访问
        if ($item->is_personal == 0) {
            return true;
        }

        // 个人库仅所有者可以访问
        if ($item->uid != $uid) {
            throw $this->exception('无权访问此内容');
        }

        return true;
    }

    /**
     * 获取个人库详情（带权限验证）.
     */
    public function getPersonalDetail(int $id, int $uid): array
    {
        $this->checkPersonalAccess($id, $uid);
        return $this->dao->get($id)?->toArray() ?: [];
    }

    /**
     * 更新个人库内容（带权限验证）.
     * @return mixed
     */
    public function updatePersonal(int $id, array $data, int $uid)
    {
        $this->checkPersonalAccess($id, $uid);

        // 个人库仅允许文本类型
        if (isset($data['types']) && $data['types'] !== MediaEnum::TEMP_TEXT) {
            throw $this->exception('个人库仅支持文本类型');
        }
        if (! isset($data['content']) || ! $data['content']) {
            throw $this->exception('请输入回复内容');
        }

        unset($data['uid'], $data['is_personal'], $data['source_id']);

        return $this->dao->update($id, $data);
    }

    /**
     * 删除个人库内容（带权限验证）.
     * @return mixed
     */
    public function deletePersonal(int $id, int $uid)
    {
        $this->checkPersonalAccess($id, $uid);
        return $this->transaction(function () use ($id) {
            $this->dao->delete($id);
            return true;
        });
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
                throw $this->exception('请选择回复内容类型');
        }
        return true;
    }
}
