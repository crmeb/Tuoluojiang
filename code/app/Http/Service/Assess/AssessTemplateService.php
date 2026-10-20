<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Assess;

use App\Http\Dao\Access\EnterpriseTemplateDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\synchro\Company;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;

/**
 * 考核模板service.
 */
class AssessTemplateService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public function __construct(EnterpriseTemplateDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @param array|string[] $field
     * @param null $sort
     * @throws BindingResolutionException
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        $where['types']     = (int) sys_config('assess_compute_mode', 1);
        $tempCollectService = app()->get(AssessTemplateCollectService::class);
        [$page, $limit]     = $this->getPageValue();
        $uid                = uuid_to_uid($where['uid'], $where['entid']);
        switch ($where['cate_id']) {
            case 'collect':
                $ids = $tempCollectService->column(['user_id' => $uid], 'temp_id');
                unset($where['cate_id'], $where['uid'], $where['entid']);
                return app()->get(Company::class)->assessTemplate('', $page, $limit, ['*'], $ids, $where['name']);
            case 'template':
                $where['user_id'] = $uid;
                unset($where['cate_id'], $where['uid']);
                $list = $this->dao->getList($where, $field, $page, $limit, $sort, [
                    'cate' => function ($query) {
                        $query->select(['id', 'name']);
                    },
                    'user',
                ])?->toArray();
                $count = $this->dao->count($where);
                return $this->listData($list, $count);
            default:
                $ids = $tempCollectService->column(['user_id' => $uid], 'temp_id');
                unset($where['uid']);
                return array_merge(app()->get(Company::class)->assessTemplate($where['cate_id'], $page, $limit, nameLike: $where['name']), ['collects' => $ids]);
        }
    }

    /**
     * 修改考核模板（修改）.
     * @param int $id
     * @return mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        if ($data['entid']) {
            $data['user_id'] = uuid_to_uid($data['user_id'], $data['entid']);
        }
        $info = $data['data'];
        unset($data['data']);
        $tempService = app()->get(AssessTemplateService::class);
        if (! $tempService->get($id)) {
            throw $this->exception('修改的模板不存在！');
        }
        if (! $info || ! count($info) || ! count($info[0]['target'])) {
            throw $this->exception('内容不能为空');
        }
        $spaceService  = app()->get(AssessSpaceService::class);
        $targetService = app()->get(AssessTargetService::class);
        $spaceIds      = $spaceService->column(['targetid' => $id], 'id') ?? [];
        $targetIds     = $targetService->column(['spaceid' => $spaceIds], 'id') ?? [];
        return $this->transaction(function () use ($spaceService, $targetService, $info, $spaceIds, $targetIds, $tempService, $id, $data) {
            $tempService->update($id, $data);
            if ($spaceIds) {
                $spaceService->delete($spaceIds, 'id');
            }
            if ($targetIds) {
                $targetService->delete($targetIds, 'id');
            }
            foreach ($info as $item) {
                $space['entid']    = $data['entid'];
                $space['targetid'] = $id;
                $space['name']     = $item['name'];
                $space['ratio']    = $item['ratio'];
                $res               = $spaceService->create($space);
                foreach ($item['target'] as $value) {
                    $target['spaceid'] = $res->id;
                    $target['ratio']   = $value['ratio'];
                    $target['name']    = $value['name'];
                    $target['content'] = $value['content'];
                    $target['info']    = $value['info'];
                    $targetService->create($target);
                }
                unset($res);
            }
            return true;
        });
    }

    /**
     * 保存考核模板（创建）.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        if ($data['entid']) {
            $data['user_id'] = uuid_to_uid($data['user_id'], $data['entid']);
        }
        $info = $data['data'];
        unset($data['data']);
        if ($this->exists($data)) {
            throw $this->exception('模板已存在，请勿重复添加！');
        }
        if (! $info || ! count($info) || ! count($info[0]['target'])) {
            throw $this->exception('内容不能为空');
        }
        $spaceService  = app()->get(AssessSpaceService::class);
        $targetService = app()->get(AssessTargetService::class);
        return $this->transaction(function () use ($spaceService, $targetService, $info, $data) {
            $temp = $this->create($data);
            foreach ($info as $item) {
                $space['entid']    = $data['entid'];
                $space['targetid'] = $temp->id;
                $space['name']     = $item['name'];
                $space['ratio']    = $item['ratio'];
                $res               = $spaceService->create($space);
                foreach ($item['target'] as $value) {
                    $target['spaceid'] = $res->id;
                    $target['ratio']   = $value['ratio'];
                    $target['name']    = $value['name'];
                    $target['content'] = $value['content'];
                    $targetService->create($target);
                }
                unset($res);
            }
            return true;
        });
    }

    /**
     * 删除数据.
     * @param int $id
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        $spaceService  = app()->get(AssessSpaceService::class);
        $targetService = app()->get(AssessTargetService::class);
        $spaceIds      = $spaceService->column(['targetid' => $id], 'id') ?? [];
        $targetIds     = $targetService->column(['spaceid' => $spaceIds], 'id') ?? [];
        return $this->transaction(function () use ($id, $key, $spaceService, $targetService, $spaceIds, $targetIds) {
            $res = $this->dao->delete($id, $key);
            if ($spaceIds) {
                $spaceService->delete($spaceIds, 'id');
            }
            if ($targetIds) {
                $targetService->delete($targetIds, 'id');
            }
            return $res;
        });
    }

    /**
     * 收藏模板
     * @return BaseModel|int|Model
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function collect($id, $uid, $entid)
    {
        $uid = uuid_to_uid($uid, $entid);
        return app()->get(AssessTemplateCollectService::class)->collectTemp($id, $uid, $entid);
    }

    /**
     * 修改模板封面.
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function cover($id, $uid, $entid, $cover, $color)
    {
        if (! $cover) {
            throw $this->exception('请选择封面图');
        }
        if ($entid) {
            $uid  = uuid_to_uid($uid, $entid);
            $info = $this->dao->get($id);
            if ($info['user_id'] != $uid || $info['entid'] != $entid) {
                throw $this->exception('操作失败，您无权修改该模板');
            }
        }
        return $this->dao->update($id, ['cover' => $cover, 'color' => $color]);
    }

    /**
     * 创建表单.
     */
    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    /**
     * 修改表单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        if ($other['way']) {
            return app()->get(Company::class)->assessInfo($id);
        }
        $temp = $this->dao->get($id);
        if (! $temp) {
            throw $this->exception('考核模板不存在');
        }
        $temp = $temp->toArray();
        $info = app()->get(AssessSpaceService::class)->getAssessTemp($id);
        if (! $info) {
            throw $this->exception('考核内容不存在');
        }
        $info = $info->toArray();
        return compact('temp', 'info');
    }

    /**
     * 修改状态
     * @return mixed
     */
    public function resourceShowUpdate($id, array $data)
    {
        return $this->showUpdate($id, $data);
    }

    /**
     * 保存模板信息.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     */
    public function saveInfo($name, $info, $uid, $entid)
    {
        if (! $name) {
            throw $this->exception('请填写模板名称');
        }
        return $this->dao->create([
            'name'    => $name,
            'info'    => $info,
            'user_id' => $uid,
            'entid'   => $entid,
        ]);
    }
}
