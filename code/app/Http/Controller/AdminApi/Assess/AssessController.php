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

namespace App\Http\Controller\AdminApi\Assess;

use App\Constants\ModuleEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Assess\AssessService;
use App\Http\Service\Assess\AssessTargetService;
use App\Http\Service\Assess\UserAssessScoreService;
use Tuoluojiang\Open\traits\SearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 考核记录
 * Class AssessController.
 */
#[Prefix('ent/assess')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class AssessController extends AuthController
{
    use SearchTrait;

    public function __construct(AssessService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取绩效列表.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('index', '获取绩效考核列表')]
    public function index()
    {
        // 保留用户选择的被考核人，独立于数据权限与考核职责的可见范围。
        $filterTestUids = $this->request->get('test_uid', []);
        $filterTestUids = $filterTestUids === '' ? [] : array_values(array_unique(array_map('intval', (array) $filterTestUids)));
        $this->withScopeFrame('test_uid', module: ModuleEnum::ASSESS);
        $where = $this->request->getMore([
            ['period', ''],
            ['frame', '', 'frame_id'],
            ['status', ''],
            ['test_uid', []],
            ['check_uid', ''],
            ['time', ''],
            ['date', ''],
            ['number', ''],
            ['handle', ''],
        ]);
        $where['filter_test_uid'] = $filterTestUids;
        $list = $this->service->getAssessList(auth('admin')->id(), $where, (int) $this->request->get('type', 0));
        return $this->success($list);
    }

    /**
     * 绩效考核列表(人事).
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('list', '人事绩效考核列表')]
    public function list()
    {
        $where = $this->request->getMore([
            ['type', ''],
            ['period', ''],
            ['frame', '', 'frame_id'],
            ['status', ''],
            ['test_uid', ''],
            ['check_uid', ''],
            ['time', ''],
            ['date', ''],
            ['number', ''],
            ['entid', 1],
        ]);
        return $this->success($this->service->assessList($where));
    }

    /**
     * 获取绩效详情.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('info/{id}', '获取绩效考核详情')]
    public function info($id)
    {
        if (! $id) {
            return $this->fail('缺少必要参数：考核ID');
        }
        return $this->success($this->service->getAssessInfo((int) $id));
    }

    /**
     * 创建绩效考核.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('create', '创建绩效考核')]
    #[Post('target', '创建绩效考核模板')]
    public function create()
    {
        $data = $this->request->postMore([
            ['data', []],
            ['is_temp', 0],
            ['is_draft', 0],
            ['period', 0],
            ['test_uid', []],
            ['time', ''],
            ['name', ''],
            ['info', ''],
            ['types', 0],
        ]);
        $this->service->createAssess(auth('admin')->id(), $data);
        return $this->success('保存成功');
    }

    /**
     * 修改绩效考核(未启用时).
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('update/{id}', '修改绩效考核')]
    public function update($id)
    {
        [$data,$isSubmit,$time] = $this->request->postMore([
            ['data', []],
            ['is_submit', 0],
            ['time', ''],
        ], true);
        $this->service->setAssess((int) $id, $data, $this->entId, (bool) $isSubmit, $time);
        return $this->success('保存成功');
    }

    /**
     * 绩效自评.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Put('self_eval/{id}', '绩效考核自评')]
    public function selfEval($id): mixed
    {
        [$data,$isSubmit,$selfReply] = $this->request->postMore([
            ['data', []],
            ['is_submit', 0],
            ['mark', '', 'self_reply'],
        ], true);
        $this->service->setSelfAssess((int) $id, $data, (bool) $isSubmit, $selfReply);
        return $this->success('保存成功');
    }

    /**
     * 绩效跳过自评.
     */
    #[Post('to_eval/{id}', '绩效跳过自评')]
    public function jumpToEval($id): mixed
    {
        $this->service->toEval((int) $id);
        return $this->success('保存成功');
    }

    /**
     * 绩效上级评价.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Put('superior_eval/{id}', '绩效考核上级评价')]
    public function superiorEval($id): mixed
    {
        [$data,$isSubmit,$reply,$hideReply] = $this->request->postMore([
            ['data', []],
            ['is_submit', 0],
            ['mark', '', 'reply'],
            ['hide_mark', '', 'hide_reply'],
        ], true);
        $this->service->setSuperiorAssess((int) $id, auth('admin')->id(), $data, $this->entId, (bool) $isSubmit, $reply, $hideReply);
        return $this->success('保存成功');
    }

    /**
     * 绩效上上级审核.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Put('examine_eval/{id}', '绩效考核上上级审核')]
    public function examineEval($id): mixed
    {
        [$data,$isSubmit] = $this->request->postMore([
            ['data', []],
            ['is_submit', 0],
        ], true);
        $this->service->setExamineAssess((int) $id, auth('admin')->id(), $data, $this->entId, (bool) $isSubmit);
        return $this->success('保存成功');
    }

    /**
     * 启用绩效考核.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('show/{id}', '启用绩效考核')]
    public function show($id)
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->showAssess((int) $id, $this->request->get('status', 1));
        return $this->success('操作成功');
    }

    /**
     * 获取绩效其他信息.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('explain/{id}', '获取绩效其他信息')]
    public function explain($id)
    {
        return $this->success($this->service->getAssessExplain((int) $id, auth('admin')->id()));
    }

    /**
     * 考核统计图.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('census', '考核统计图')]
    public function census()
    {
        $this->withScopeFrame(module: ModuleEnum::ASSESS);
        $where = $this->request->postMore([
            ['period', ''],
            ['start', ''],
            ['end', ''],
            ['types', 0],
            ['frame_id', ''],
            ['uid', []],
            ['time', ''],
        ]);
        return $this->success($this->service->getAssessCensusLine($where, auth('admin')->id()));
    }

    /**
     * 考核统计图.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('census_bar', '人事考核统计图')]
    public function censusBar()
    {
        $where = $this->request->getMore([
            ['period', ''],
            ['time', ''],
            ['frame_id', ''],
            ['types', 0],
            ['number', ''],
            ['test_uid', ''],
            ['entid', 1],
        ]);

        return $this->success($this->service->getAssessStatistics($where, auth('admin')->id()));
    }

    /**
     * 指标自评.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Put('eval', '绩效指标自评')]
    public function evalTarget(AssessTargetService $services): mixed
    {
        [$assessId, $targetId, $spaceId, $finish_info, $finish_ratio] = $this->request->postMore([
            ['assess_id', 0],
            ['target_id', 0],
            ['space_id', 0],
            ['finish_info', ''],
            ['finish_ratio', 0],
        ], true);
        $services->selfEvalTarget($assessId, $targetId, $spaceId, compact('finish_info', 'finish_ratio'), auth('admin')->id());
        return $this->success('common.update.succ');
    }

    /**
     * 评分记录.
     * @return mixed
     */
    #[Get('score/{id}', '绩效评分记录')]
    public function record(UserAssessScoreService $services, $id = 0)
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $data = $services->getScoreRecord($id);

        return $this->success($data, tips: 0);
    }

    /**
     * 制定考核目标.
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['data', []],
            ['is_temp', 0],
            ['period', 0],
            ['test_uid', []],
            ['time', ''],
            ['name', ''],
            ['info', ''],
            ['types', 0],
        ]);
        $data['is_draft'] = 0;
        $this->service->saveAssess($this->uuid, $data, $this->entId);

        return $this->success('common.insert.succ');
    }

    /**
     * 修改考核目标.
     *
     * @param int $id
     *
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function edit($id = 0)
    {
        $data = $this->request->postMore([
            ['data', []],
            ['is_draft', 0],
            ['is_temp', 0],
            ['name', ''],
            ['info', ''],
            ['types', 0],
        ]);
        $data['is_draft'] = 0;
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->editAssess($this->uuid, $id, $data, $this->entId);

        return $this->success('common.update.succ');
    }

    /**
     * 删除表单.
     */
    #[Get('del_form/{id}', '绩效删除表单')]
    public function deleteForm(int $id = 0): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        if (! $this->service->exists(['id' => $id, 'entid' => $this->entId])) {
            return $this->fail('未找到考核记录');
        }

        return $this->success($this->service->deleteForm($id));
    }

    /**
     * 删除绩效.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Delete('delete/{id}', '绩效删除')]
    public function delete($id = 0): mixed
    {
        [$mark] = $this->request->postMore([
            ['mark', ''],
        ], true);
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->deleteAssess($id, $mark, auth('admin')->id());

        return $this->success('common.delete.succ');
    }

    /**
     * 绩效删除记录.
     */
    #[Get('del_record', '绩效删除记录')]
    public function deleteRecord(UserAssessScoreService $services): mixed
    {
        $where['entid'] = $this->entId;
        $where['types'] = 1;

        return $this->success($services->getDeleteList($where));
    }

    /**
     * 申诉/驳回.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Post('appeal/{id}', '绩效申诉/驳回')]
    public function appeal($id)
    {
        $data = $this->request->postMore([
            ['content', ''],
            ['types', 0],
        ]);
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->assessAppeal((int) $id, auth('admin')->id(), $data);

        return $data['types'] ? $this->success('common.operation.succ') : $this->success('提交申诉成功');
    }

    /**
     * 提醒操作人.
     *
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function remind($id)
    {
        if (! $id) {
            return $this->fail(__('common.empty.attr', ['attr' => 'id']));
        }
        $this->service->createNotice($id, $this->entId);

        return $this->success('common.operation.succ');
    }

    /**
     * 绩效未创建列表.
     * @return mixed
     */
    #[Get('abnormal', '绩效未创建列表')]
    public function abnormal()
    {
        [$period,$time] = $this->request->getMore([
            ['period', ''],
            ['time', ''],
        ], true);
        $data = $this->service->abnormalList($period, $time, $this->entId);
        return $this->success($data);
    }

    /**
     * 绩效是否存在未创建.
     * @return mixed
     */
    #[Get('is_abnormal', '绩效是否存在未创建')]
    public function isAbnormal()
    {
        [$period,$time] = $this->request->getMore([
            ['period', ''],
            ['time', ''],
        ], true);
        $count = $this->service->abnormalCount($period, $time, $this->entId);
        return $this->success(compact('count'));
    }
}
