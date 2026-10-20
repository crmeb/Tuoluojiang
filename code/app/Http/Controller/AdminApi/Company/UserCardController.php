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

namespace App\Http\Controller\AdminApi\Company;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\user\EnterpriseUserCardRequest;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Company\CompanyUserChangeService;
use App\Http\Service\Frame\FrameService;
use FormBuilder\Exception\FormBuilderException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 企业用户名片
 * Class CardController.
 */
#[Prefix('ent/company/card')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class UserCardController extends AuthController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 人员列表.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Post('/', '企业成员列表')]
    public function index(AdminService $service): mixed
    {
        $where = $this->request->postMore([
            ['time', ''],
            ['sex', ''],
            ['type', ''],
            ['types', []],
            ['field', ''],
            ['status', ''],
            ['search', ''],
            ['frame_id', ''],
            ['is_part', ''],
            ['education', ''],
        ]);
        [$page,$limit] = $this->request->postMore([
            ['page', 1],
            ['limit', 10],
        ], true);
        $where['entid'] = $this->entId;
        if (! $where['types']) {
            $where['uid'] = true;
        }
        return $this->success($service->adminList($where, (int) $page, (int) $limit, ['admin_info.sort', 'admin_info.id']));
    }

    /**
     * 获取导入模板
     */
    #[Get('import/temp', '获取导入模板')]
    public function importTemplate(): mixed
    {
        $url = '/static/temp/card_import_temp.xlsx';
        return $this->success(compact('url'));
    }

    /**
     * 组织架构tree型数据.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('tree', '组织架构tree型数据')]
    public function frameTree(FrameService $services): mixed
    {
        return $this->success($services->getList([]));
    }

    /**
     * 创建保存员工档案.
     * @throws BindingResolutionException
     * @throws ValidationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('save/{type}', '创建保存员工档案')]
    public function save(EnterpriseUserCardRequest $request, int $type = 0): mixed
    {
        $request->scene(match ($type) {
            1       => 'create_induction',
            2       => 'create_departure',
            default => 'create_interview',
        })->check();
        $data = $this->request->postMore([
            // 基本信息
            ['interview_date', ''],
            ['interview_position', ''],
            // 基本信息
            ['name', ''],
            ['phone', ''],
            ['position', ''],
            ['photo', ''],
            // 职工信息
            ['is_part', ''],
            ['type', ''],
            ['status', ''],
            ['work_time', ''],
            ['trial_time', ''],
            ['formal_time', ''],
            ['treaty_time', ''],
            ['quit_time', ''],
            // 个人信息
            ['card_id', ''],
            ['sex', ''],
            ['birthday', ''],
            ['age', 18],
            ['nation', ''],
            ['politic', ''],
            ['work_years', ''],
            ['native', ''],
            ['address', ''],
            ['marriage', ''],
            ['email', ''],
            // 学历信息
            ['education', ''],
            ['acad', ''],
            ['graduate_date', ''],
            ['graduate_name', ''],
            // 银行卡信息
            ['bank_num', ''],
            ['bank_name', ''],
            // 社保信息
            ['social_num', ''],
            ['fund_num', ''],
            // 紧急联系人
            ['spare_name', ''],
            ['spare_tel', ''],
            // 个人材料
            ['card_front', ''],
            ['card_both', ''],
            ['education_image', ''],
            ['acad_image', ''],
            // 系统信息
            ['sort', 0],

            ['works', []],
            ['educations', []],
        ]);
        $frameInfo = $this->request->postMore([
            ['frame_id', []],
            ['is_admin', 0],
            ['main_id', 0],
            ['position', 0],
            ['superior_uid', 0],
            ['frames', []],
            ['manage_frame', [], 'manage_frames'],
        ]);
        $frameInfo['main_id'] = $frameInfo['main_id'] ?: collect($frameInfo['frame_id'] ?? [])->first();
        if ($frameInfo['frame_id'] && ! $frameInfo['main_id']) {
            return $this->fail('必须选择一个主部门');
        }
        $res = app()->get(AdminService::class)->createAdmin($data, $type, $frameInfo);
        return $res ? $this->success('common.insert.succ') : $this->fail('common.insert.fail');
    }

    /**
     * 导入用户档案.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('import', '导入用户档案')]
    public function import(AdminService $service): mixed
    {
        [$type, $data] = $this->request->postMore([
            ['type', 0],
            ['data', []],
        ], true);
        if (empty($data)) {
            return $this->fail('未获取到导入数据');
        }
        $succ = $err = 0;
        foreach ($data as $val) {
            try {
                if (isset($val['frames'])) {
                    unset($val['frames']);
                }

                $service->createAdmin(array_merge($val, ['photo' => '', 'works' => [], 'educations' => [], 'work_time' => '']), type: (int) $type, import: true);
                ++$succ;
            } catch (\Exception $e) {
                ++$err;
                Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
            }
        }
        return $this->success('员工导入结果，成功：' . $succ . '条,失败：' . $err . '条.');
    }

    /**
     * 获取企业用户名片.
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Get('info/{id}', '获取企业用户名片')]
    public function edit(AdminService $service, $id)
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        return $this->success($service->adminInfo((int) $id));
    }

    /**
     * 修改保存员工档案.
     *
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Put('{id}', '修改保存员工档案')]
    public function update(AdminService $service, $id): mixed
    {
        [$type] = $this->request->postMore([
            ['edit_type', 'all'],
        ], true);
        $data = $this->request->postMore([
            // 基本信息
            ['name', ''],
            ['phone', ''],
            ['position', ''],
            ['photo', ''],
            // 职工信息
            ['is_part', ''],
            ['status', ''],
            ['work_time', ''],
            ['trial_time', ''],
            ['formal_time', ''],
            ['treaty_time', ''],
            ['interview_date', ''],
            ['interview_position', ''],
            ['quit_time', ''],
            ['type', ''],
            // 个人信息
            ['card_id', ''],
            ['sex', ''],
            ['birthday', ''],
            ['age', 18],
            ['nation', ''],
            ['politic', ''],
            ['work_years', ''],
            ['native', ''],
            ['address', ''],
            ['marriage', ''],
            ['email', ''],
            // 学历信息
            ['education', ''],
            ['acad', ''],
            ['graduate_date', ''],
            ['graduate_name', ''],
            // 银行卡信息
            ['bank_num', ''],
            ['bank_name', ''],
            // 社保信息
            ['social_num', ''],
            ['fund_num', ''],
            // 紧急联系人
            ['spare_name', ''],
            ['spare_tel', ''],
            // 个人材料
            ['card_front', ''],
            ['card_both', ''],
            ['education_image', ''],
            ['acad_image', ''],
            // 系统信息
            ['sort', 0],
        ]);
        $other = $this->request->postMore([
            ['frame_id', []],
            ['is_admin', 0],
            ['main_id', 0],
            ['position', 0],
            ['superior_uid', 0],
            ['frames', []],
            ['manage_frame', [], 'manage_frames'],
        ]);
        $other['main_id'] = $other['main_id'] ?: collect($other['frame_id'] ?? [])->first();
        if ($other['frame_id'] && ! $other['main_id']) {
            return $this->fail('必须选择一个主部门');
        }
        if ($other['is_admin'] && ! $other['superior_uid']) {
            return $this->fail('必须选择一个上级主管');
        }
        $service->updateAdmin((int) $id, $type, $data, $other);
        return $this->success('common.update.succ');
    }

    /**
     * 员工入职.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('entry/{id}', '员工入职')]
    public function entry(AdminService $service, $id): mixed
    {
        $service->adminEntry((int) $id);
        return $this->success('common.update.succ');
    }

    /**
     * 员工转正表单.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws FormBuilderException
     * @throws NotFoundExceptionInterface
     */
    #[Get('formal/{id}', '员工转正表单')]
    public function formal(AdminService $service, $id): mixed
    {
        return $this->success($service->getFormalForm((int) $id));
    }

    /**
     * 员工转正.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Put('be_formal/{id}', '员工转正')]
    public function beFormal(AdminService $service, $id): mixed
    {
        $data = $this->request->postMore([
            ['formal_time', ''],
            ['mark', ''],
        ]);
        $service->adminBeFormal($id, $data);
        return $this->success('common.update.succ');
    }

    /**
     * 员工离职.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('quit/{id}', '员工离职')]
    public function quit(AdminService $service, $id)
    {
        $data = $this->request->postMore([
            ['mark', ''],
            ['info', ''],
            ['quit_time', ''],
            ['user_id', 0],
        ]);
        $service->adminQuit($id, $data);
        return $this->success('common.update.succ');
    }

    /**
     * 人事异动列表.
     */
    #[Get('change', '人事异动列表')]
    public function cardChange(CompanyUserChangeService $services): mixed
    {
        $where = $this->request->getMore([
            ['card_id', '', 'uid'],
        ]);
        return $this->success($services->getList($where));
    }

    /**
     * 修改员状态
     *
     * @param mixed $id
     * @param mixed $status
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function show(AdminService $service, $id, $status)
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $service->updateUserStatus((int) $id, (int) $status, $this->entId);

        return $this->success('common.update.succ');
    }

    /**
     * 删除.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Delete('{id}', '删除档案')]
    public function destroy(AdminService $service, $id)
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $service->deleteAdmin((int) $id);
        return $this->success('删除成功');
    }

    /**
     * 删除.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Delete('batch', '批量删除档案')]
    public function batchDestroy(AdminService $service): mixed
    {
        [$id] = $this->request->postMore([
            ['id', []],
        ], true);
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $service->deleteBatchUserCard((array) $id, $this->entId);

        return $this->success('删除成功');
    }

    /**
     * 批量设置成员进入部门.
     *
     * @throws BindingResolutionException
     */
    #[Post('batch', '批量设置成员进入部门')]
    public function batchSetFrame(AdminService $service): mixed
    {
        [$frameId, $userId, $mastartId] = $this->request->postMore([
            ['frame_id', []],
            ['user_id', []],
            ['mastart_id', 0],
        ], true);
        if (! $frameId) {
            return $this->fail('至少选择一个部门进行设置');
        }
        if (! $userId) {
            return $this->fail('至少选择一个用户进行设置');
        }
        $service->batchSetFrame($frameId, $userId, (int) $mastartId, $this->entId);

        return $this->success('批量加入成功');
    }

    /**
     * 邀请完善信息.
     *
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('perfect/{id}', '邀请完善信息')]
    public function sendPerfect(AdminService $service, int $id = 0): mixed
    {
        $url = $service->sendPerfect(auth('admin')->id(), $id);

        return $this->success('操作成功', compact('url'));
    }
}
