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

namespace App\Http\Service\Work;

use App\Constants\CacheEnum;
use App\Http\Dao\Work\WorkMemberDao;
use App\Http\Service\Admin\AdminService;
use App\Jobs\Work\WorkMemberJob;
use App\Jobs\Work\WorkMemberSaveJob;
use App\Jobs\Work\WorkWithClueJob;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\wechat\config\WorkConfig;
use Tuoluojiang\Open\services\wechat\Work;
use Tuoluojiang\Open\utils\Str;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 员工数据服务.
 * Class WorkMemberService.
 */
class WorkMemberService extends BaseService
{
    public const TABLE_FIELD = ['Name', 'MainDepartment', 'DirectLeader',
        'Mobile', 'Position', 'Gender', 'Email', 'BizMail', 'Status', 'Avatar', 'Alias',
        'Telephone', 'Address'];

    public function __construct(WorkMemberDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表.
     * @param null|mixed $sort
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getDataList(array $where = [], array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = [])
    {
        return $this->dao->getList($where, $field, $page, $limit, $sort, $with);
    }

    /**
     * 自动更新企业成员.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function authUpdataMember(int $departmentId)
    {
        $res      = app()->get(Work::class)->getDetailedDepartmentUsers($departmentId);
        $members  = $res['userlist'] ?? [];
        $maxCount = 500;
        $sumCount = count($members);
        if ($sumCount > $maxCount) {
            $page = ceil($maxCount / $sumCount);
            for ($i = 1; $i < $page; ++$i) {
                $res = collect($members)->slice($maxCount * $i, $maxCount)->toArray();
                WorkMemberSaveJob::dispatch($res);
            }
        } else {
            $this->saveMember($members);
        }
    }

    public function authUpdataMemberV1(string $next_cursor = '')
    {
        $res        = app()->get(Work::class)->getUserListId($next_cursor);
        $members    = $res['dept_user'] ?? [];
        $nextCursor = $res['next_cursor'] ?? '';
        /** @var WorkConfig $config */
        $config = app()->get(WorkConfig::class);
        $corpId = $config->getCorpId();
        if (! $corpId) {
            return true;
        }

        $relation = [];
        $userids  = array_column($members, 'userid');
        foreach ($members as $member) {
            // 写入默认部门
            $department = $member['department'];
            unset($member['department']);
            if ($member_id = $this->dao->value(['userid' => $member['userid'], 'corp_id' => $corpId], 'id')) {
                $this->dao->update(['userid' => $member['userid'], 'corp_id' => $corpId], $member);
            } else {
                $member['created_at'] = date('Y-m-d H:i:s');
                $member['corp_id']    = $corpId;
                $res                  = $this->dao->create($member);
                $member_id            = $res->id;
            }
            $relation[$member['userid']][] = ['department' => $department, 'srot' => 0, 'is_leader_in_dept' => 0, 'member_id' => $member_id];
        }

        $userList     = $this->dao->column(['userid' => $userids, 'corp_id' => $corpId], 'id', 'userid');
        $userValueAll = array_values($userList);
        // 写入关联数据
        if (count($relation)) {
            $relationService = app()->get(WorkMemberRelationService::class);
            $relationService->delete(['member_id' => $userValueAll]);
            $saveRelation = [];
            foreach ($relation as $userid => $item) {
                if (! isset($userList[$userid])) {
                    continue;
                }
                foreach ($item as $value) {
                    $saveRelation[] = [
                        'member_id'         => $value['member_id'],
                        'created_at'        => date('Y-m-d H:i:s'),
                        'department'        => $value['department'],
                        'srot'              => $value['srot'],
                        'is_leader_in_dept' => $value['is_leader_in_dept'],
                    ];
                }
            }
            $relationService->insert($saveRelation);
        }

        if ($nextCursor) {
            WorkMemberJob::dispatch($nextCursor);
        }
    }

    /**
     * 保存成员数据.
     * @return bool
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveMember(array $members)
    {
        /** @var WorkConfig $config */
        $config = app()->get(WorkConfig::class);
        $corpId = $config->getCorpId();
        if (! $corpId) {
            return true;
        }

        $departmentService = app()->get(WorkDepartmentService::class);
        $defaultDepartment = $departmentService->value(['corp_id' => $corpId, 'parentid' => 0], 'department_id');

        $this->transaction(function () use ($members, $corpId, $defaultDepartment) {
            $data     = [];
            $relation = [];
            $other    = [];
            $userids  = array_column($members, 'userid');
            foreach ($members as $member) {
                if (isset($member['english_name'])) {
                    unset($member['english_name']);
                }
                $address = $bizMail = '';
                if (isset($member['address'])) {
                    $address = $member['address'];
                    unset($member['address']);
                }
                if (isset($member['biz_mail'])) {
                    $bizMail = $member['biz_mail'];
                    unset($member['biz_mail']);
                }
                $member['address']  = $address;
                $member['biz_mail'] = $bizMail;
                if (isset($member['extattr']) && $member['extattr']) {
                    $other[$member['userid']] = [
                        'extattr'          => json_encode($member['extattr']),
                        'external_profile' => json_encode($member['external_profile'] ?? []),
                    ];
                }
                if (! empty($member['department'])) {
                    foreach ($member['department'] as $i => $department) {
                        $relation[$member['userid']][] = [
                            'department'        => $member['department'][$i] ?? 0,
                            'srot'              => $member['order'][$i] ?? 0,
                            'is_leader_in_dept' => $member['is_leader_in_dept'][$i] ?? 0,
                        ];
                    }
                } else {
                    // 写入默认部门
                    $relation[$member['userid']][] = ['department' => $defaultDepartment, 'srot' => 0, 'is_leader_in_dept' => 0];
                }
                $externalPosition = '';
                if (isset($member['external_position'])) {
                    $externalPosition = $member['external_position'];
                    unset($member['external_position']);
                }
                $member['external_position'] = $externalPosition;
                $member['direct_leader']     = json_encode($member['direct_leader']);
                $member['is_leader']         = $member['isleader'];
                $member['corp_id']           = $corpId;
                if (isset($member['external_profile'])) {
                    unset($member['external_profile']);
                }
                unset($member['isleader'], $member['is_leader_in_dept'], $member['order'], $member['department'], $member['extattr']);
                if ($this->dao->count(['userid' => $member['userid'], 'corp_id' => $corpId])) {
                    $this->dao->update(['userid' => $member['userid'], 'corp_id' => $corpId], $member);
                } else {
                    $member['created_at'] = date('Y-m-d H:i:s');
                    $data[]               = $member;
                }
            }
            // 写入成员数据
            if ($data) {
                $this->dao->insert($data);
            }
            $userList     = $this->dao->column(['userid' => $userids, 'corp_id' => $corpId], 'id', 'userid');
            $userValueAll = array_values($userList);
            // 写入关联数据
            if (count($relation)) {
                $relationService = app()->get(WorkMemberRelationService::class);
                $relationService->delete(['member_id' => $userValueAll]);
                $saveRelation = [];
                foreach ($relation as $userid => $item) {
                    $memberId = $userList[$userid];
                    foreach ($item as $value) {
                        $saveRelation[] = [
                            'member_id'         => $memberId,
                            'created_at'        => date('Y-m-d H:i:s'),
                            'department'        => $value['department'],
                            'srot'              => $value['srot'],
                            'is_leader_in_dept' => $value['is_leader_in_dept'],
                        ];
                    }
                }
                $relationService->insert($saveRelation);
            }
            // 写入其他数据
            if (count($other)) {
                $otherService = app()->get(WorkMemberOtherService::class);
                $otherService->delete(['member_id' => $userValueAll]);
                foreach ($other as $userid => &$item) {
                    $memberId          = $userList[$userid];
                    $item['member_id'] = $memberId;
                }
                $otherService->insert($other);
            }
        });
        return true;
    }

    /**
     * 更新企业成员.
     * @return mixed
     */
    public function updateMember(array $payload)
    {
        $corpId     = $payload['ToUserName'] ?? '';
        $userId     = $payload['UserID'] ?? '';
        $updateData = $this->getTableField($payload);
        if (! empty($payload['NewUserID'])) {
            $updateData['userid'] = $payload['NewUserID'];
        }

        $memberInfo = app()->get(Work::class)->getMemberInfo($userId);
        if ($memberInfo['errcode'] !== 0) {
            throw $this->exception($memberInfo['errmsg']);
        }
        $extattr         = $memberInfo['extattr'] ?? [];
        $externalProfile = $memberInfo['external_profile'] ?? [];
        unset($memberInfo['errcode'], $memberInfo['errmsg'], $memberInfo['department'],
            $memberInfo['order'], $memberInfo['is_leader_in_dept'], $memberInfo['extattr'],
            $memberInfo['external_profile']);
        $updateData = array_merge($updateData, $memberInfo);

        $memberId = $this->dao->value(['userid' => $userId, 'corp_id' => $corpId], 'id');
        if ($memberId) {
            if ($updateData) {
                $this->dao->update(['corp_id' => $corpId, 'userid' => $userId], $updateData);
            }
        } else {
            if (! empty($payload['NewUserID'])) {
                $updateData['userid'] = $payload['NewUserID'];
            }
            $updateData['corp_id'] = $corpId;
            $res                   = $this->dao->create($updateData);
            $memberId              = $res->id;
        }

        $relationServices = app()->get(WorkMemberRelationService::class);
        $relationServices->saveMemberDepartment($memberId, $payload['IsLeaderInDept'] ?? '', $payload['Department'] ?? '');

        // 写入其他数据
        if (! empty($extattr['attrs']) || ! empty($externalProfile)) {
            $otherService = app()->get(WorkMemberOtherService::class);
            $otherInfo    = $otherService->get(['member_id' => $memberId]);
            if ($otherInfo) {
                $otherInfo->extattr          = json_encode($extattr);
                $otherInfo->external_profile = json_encode($externalProfile);
                $otherInfo->save();
            } else {
                $otherService->create([
                    'member_id'        => $memberId,
                    'extattr'          => json_encode($extattr),
                    'external_profile' => json_encode($externalProfile),
                ]);
            }
        }

        return $memberId;
    }

    /**
     * 创建企业微信成员.
     * @return mixed
     */
    public function createMember(array $payload)
    {
        $corpId = $payload['ToUserName'] ?? '';
        $userId = $payload['UserID'] ?? '';
        $data   = $this->getTableField($payload);
        if (! $corpId) {
            /** @var WorkConfig $config */
            $config = app()->get(WorkConfig::class);
            $corpId = $config->getCorpId();
        }
        $memberInfo = app()->get(Work::class)->getMemberInfo($userId);
        if ($memberInfo['errcode'] !== 0) {
            throw $this->exception($memberInfo['errmsg']);
        }
        $extattr         = $memberInfo['extattr'] ?? [];
        $externalProfile = $memberInfo['external_profile'] ?? [];
        unset($memberInfo['errcode'], $memberInfo['errmsg'], $memberInfo['department'],
            $memberInfo['order'], $memberInfo['is_leader_in_dept'], $memberInfo['extattr'],
            $memberInfo['external_profile']);
        $data     = array_merge($data, $memberInfo);
        $memberId = $this->dao->value(['userid' => $userId, 'corp_id' => $corpId], 'id');
        if ($memberId) {
            if ($data) {
                $this->dao->update($memberId, $data);
            }
        } else {
            $data['corp_id'] = $corpId;
            $res             = $this->dao->create($data);
            $memberId        = $res->id;
        }

        // 记录
        $isLeaderInDept = $payload['IsLeaderInDept'] ?? '';
        $department     = $payload['Department'] ?? '';
        if (! $department && ! $isLeaderInDept) {
            // 写入主部门

            $departmentService = app()->get(WorkDepartmentService::class);
            $id                = $departmentService->value(['corp_id' => $corpId, 'parentid' => 0], 'department_id');
            if ($id) {
                $department     = (string) $id;
                $isLeaderInDept = '0';
            }
        }

        $relationServices = app()->get(WorkMemberRelationService::class);
        $relationServices->saveMemberDepartment($memberId, $isLeaderInDept, $department);

        // 写入其他数据
        if (! empty($extattr['attrs']) || ! empty($externalProfile)) {
            $otherService = app()->get(WorkMemberOtherService::class);
            $otherInfo    = $otherService->get(['member_id' => $memberId]);
            if ($otherInfo) {
                $otherInfo->extattr          = json_encode($extattr);
                $otherInfo->external_profile = json_encode($externalProfile);
                $otherInfo->save();
            } else {
                $otherService->create([
                    'member_id'        => $memberId,
                    'extattr'          => json_encode($extattr),
                    'external_profile' => json_encode($externalProfile),
                ]);
            }
        }

        return $memberId;
    }

    /**
     * 删除企业微信成员.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteMember(string $corpId, string $userid)
    {
        $memberId = $this->dao->value(['corp_id' => $corpId, 'userid' => $userid], 'id');
        if ($memberId) {
            $this->transaction(function () use ($memberId) {
                $relationServices = app()->get(WorkMemberRelationService::class);
                $relationServices->delete(['member_id' => $memberId]);
                $otherServices = app()->get(WorkMemberOtherService::class);
                $otherServices->delete(['member_id' => $memberId]);
                $this->dao->delete($memberId);
            });
        }
    }

    /**
     * 绑定管理员.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function bindingAdmin(string $code, int $uid, int $replace = 0)
    {
        $adminService = app()->get(AdminService::class);

        $adminInfo = $adminService->get($uid);
        if (! $adminInfo) {
            throw $this->exception('用户不存在');
        }
        if ($adminInfo->work_member_id && ! $replace) {
            throw $this->exception('用户已经绑定企业微信');
        }
        try {
            $userInfo = app()->get(Work::class)->auth()->getUserInfo($code);
        } catch (\Throwable $e) {
            throw $this->exception($e->getMessage());
        }

        if (empty($userInfo['userid'])) {
            throw $this->exception('当前扫码非企业内部成员，无法绑定');
        }

        $id = $this->dao->value(['userid' => $userInfo['userid']], 'id');
        if (! $id) {
            throw $this->exception('当前扫码的内部成员不存在');
        }

        $adminInfo->work_member_id = $id;
        $adminInfo->save();
        Cache::tags([CacheEnum::TAG_FRAME])->flush() && WorkWithClueJob::dispatch($uid, $userInfo['userid']);
    }

    /**
     * 解除绑定管理员.
     * @throws BindingResolutionException
     */
    public function unbindAdmin(int $uid)
    {
        $adminService = app()->get(AdminService::class);

        $adminInfo = $adminService->get($uid);
        if (! $adminInfo) {
            throw $this->exception('用户不存在');
        }
        if (! $adminInfo->work_member_id) {
            throw $this->exception('没有绑定企业微信无法解除');
        }

        $adminInfo->work_member_id = 0;
        $adminInfo->save();
    }

    /**
     * 获取提交字段.
     * @return array
     */
    protected function getTableField(array $payload)
    {
        $data = [];
        foreach (self::TABLE_FIELD as $key) {
            $strKey = Str::snake($key);
            if (isset($payload[$strKey])) {
                $data[$strKey] = $payload[$strKey];
            }
        }
        return $data;
    }
}
