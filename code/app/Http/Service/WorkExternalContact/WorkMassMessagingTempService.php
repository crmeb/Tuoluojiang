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

use App\Http\Dao\WorkExternalContact\WorkMassMessagingTempDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 企微群发消息模板.
 */
class WorkMassMessagingTempService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public function __construct(WorkMassMessagingTempDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @param mixed $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = ['sort', 'id'], array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $with           = ['creator', 'group'];
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $count          = $this->dao->count($where);
        return $this->listData($list, $count);
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
        $attach = $data['attach'];
        unset($data['attach']);
        $attachService = app()->get(WorkMassMessagingTempAttachService::class);
        $attachId      = [];
        foreach ($attach as $item) {
            $attachId[] = $attachService->resourceSave($item)->id;
        }
        $result = $this->dao->create($data);
        $attachId && app()->get(WorkMassMessagingTempAttachService::class)->setLink($attachId, $result->id);
        return $result;
    }

    /**
     * 修改.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceUpdate($id, array $data)
    {
        $attach = $data['attach'];
        unset($data['attach'],$data['uid']);
        $attachService = app()->get(WorkMassMessagingTempAttachService::class);
        $attachId      = [];
        foreach ($attach as $item) {
            $attachId[] = $attachService->resourceSave($item)->id;
        }
        $result = $this->dao->update($id, $data);
        app()->get(WorkMassMessagingTempAttachService::class)->setLink($attachId, (int) $id);
        return $result;
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
            app()->get(WorkMassMessagingTempAttachService::class)->deleteLink((int) $id);
            return true;
        });
    }

    public function resourceCreate(array $other = []): array
    {
        // TODO: Implement resourceCreate() method.
    }

    public function resourceEdit(int $id, array $other = []): array
    {
        $data = $this->dao->get($id, with: [
            'attach' => fn ($query) => $query->with([
                'file' => fn ($q) => $q->select(['id', 'file_url', 'real_name as file_name', 'file_size', 'link_id']),
            ]),
            'creator',
        ])?->toArray();
        $data['attach'] = collect($data['attach'])->map(function ($attachItem) {
            $cleanAttach = collect($attachItem)->forget(['created_at', 'updated_at'])->all();
            if (! empty($cleanAttach['file'])) {
                $cleanAttach['file'] = collect($cleanAttach['file'])
                    ->only(['id', 'file_name', 'file_url', 'file_size'])
                    ->mapWithKeys(function ($value, $key) {
                        return match ($key) {
                            'file_name' => ['name' => $value],
                            'file_url'  => ['url' => link_file($value)],
                            'file_size' => ['size' => $value],
                            default     => [$key => $value]
                        };
                    })->all();
            }
            return $cleanAttach;
        })->all();
        return $data;
    }
}
