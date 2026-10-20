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

namespace App\Http\Service\Customer;

use App\Constants\CustomEnum\CustomEnum;
use App\Constants\CustomEnum\LiaisonEnum;
use App\Constants\ModuleEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Contract\Customer\CustomerLiaisonInterface;
use App\Http\Dao\Customer\LiaisonDao;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Config\FormService;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 联系人.
 */
class LiaisonService extends BaseService implements CustomerLiaisonInterface
{
    use CustomerTrait;
    use ResourceServiceTrait;

    public $dao;

    public function __construct(LiaisonDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 字段过滤.
     * @return string[]
     */
    public function dictFilterField(): array
    {
        return [];
    }

    public function followUpField(): string
    {
        return '';
    }

    public function followUpService(): string
    {
        return '';
    }

    /**
     * 关注状态
     */
    public function getSubscribeStatus(int $uid, array $ids): array
    {
        return [];
    }

    /**
     * 保存联系人.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveLiaison(array $data, int $eid, int $uid): mixed
    {
        $formService = app(FormService::class);
        $list        = $formService->getFormDataList(CustomEnum::LIAISON);
        $formService->fieldValueCheck($data, CustomEnum::LIAISON, 0, $list);

        $attaches = [];

        foreach ($list as $item) {
            $type      = strtolower($item['type']);
            $inputType = strtolower($item['input_type']);
            foreach ($data as $key => &$datum) {
                if ($item['key'] == $key) {
                    if (in_array($inputType, ['file', 'images'])) {
                        $attaches = array_merge($attaches, (array) $datum);
                    }
                    $datum = $formService->getFormValue($type, $inputType, $datum, $key);
                }
            }
        }

        $data['eid'] = $eid;
        $data['uid'] = $data['creator_uid'] = $uid;
        $attaches    = array_filter($attaches);
        return $this->transaction(function () use ($data, $attaches) {
            $res = $this->dao->create($data);
            if (! $res) {
                throw $this->exception('保存失败');
            }

            // save relation attach
            if ($attaches) {
                app(AttachService::class)->update(['id' => $attaches], ['relation_id' => $res->id, 'relation_type' => 8]);
            }

            app(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_LIAISON, [
                'eid'            => $res->id,
                'type'           => LiaisonEnum::OPERATE_CREATE,
                'uid'            => $data['uid'],
                'creator_uid'    => $data['creator_uid'],
                'record_version' => 0,
                'reason'         => '新添加联系人“' . ($data['liaison_name'] ?? '') . '”',
            ]);

            return $res;
        });
    }

    public function getSearchField(): array
    {
        $field[] = ['statistics_type', ''];
        $field[] = ['types', ''];
        $field[] = ['uid', ''];

        $fieldSet = app(FormService::class)->getCustomDataByTypes(CustomEnum::LIAISON, ['key as field', 'input_type']);
        $fieldSet = array_merge($fieldSet, LiaisonEnum::LIAISON_SEARCH_FIELD);
        foreach ($fieldSet as $value) {
            $field[] = [$value['field'], ''];
        }
        $field[] = ['scope_frame', ''];
        return $field;
    }

    /**
     * 获取用户设置的搜索列表.
     */
    public function searchField(mixed $customType = null): array
    {
        return $this->getSearchField();
    }

    /**
     * 更新过滤字段.
     */
    public function getUpdateFilterField(): array
    {
        return ['creator_uid', 'uid', 'eid'];
    }

    /**
     * 修改联系人.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateLiaison(array $data, int $id): mixed
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('数据获取异常');
        }

        return $this->updateData($data, $id, (int) ($info->creator_uid ?: $info->uid), ViewSearchEnum::VIEW_LIAISON);
    }

    /**
     * 删除.
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function deleteLiaison(int $id, int $uid): int
    {
        $infoUid = $this->dao->value($id, 'uid');
        if ($uid && ! in_array($infoUid, app(ModulePermissionService::class)->getAccessibleUserIds($uid, ModuleEnum::CUSTOMER))) {
            throw $this->exception('common.operation.noPermission');
        }
        return (int) $this->dao->delete($id);
    }

    /**
     * 联系人详情.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getInfo(int $id): mixed
    {
        $info = $this->dao->get($id)?->toArray();
        if (! $info) {
            throw $this->exception('数据获取异常');
        }

        $attachField     = $this->getAttachField();
        $attachService   = app(AttachService::class);
        $dictDataService = app(DictDataService::class);

        $list = app(FormService::class)->getFormDataWithType(CustomEnum::LIAISON, false);
        foreach ($list as &$item) {
            foreach ($item['data'] as &$datum) {
                if (array_key_exists($datum['key'], $info)) {
                    $type           = strtolower($datum['type']);
                    $inputType      = strtolower($datum['input_type']);
                    $datum['value'] = $this->handleFieldValue($inputType, $type, $info[$datum['key']], CustomEnum::SCENE_INFO);
                    if ($datum['dict_ident']) {
                        if (is_dimensional_data($datum['value'])) {
                            $datum['value'] = $this->handleDictValue($datum['dict_ident'], $datum['value'], $type);
                        } else {
                            $datum['value'] = $dictDataService->getNamesByValue($datum['dict_ident'], $datum['value']);
                        }
                    }
                    if (in_array($inputType, ['file', 'images'])) {
                        $datum['files'] = empty($datum['value']) ? []
                            : $attachService->getListByRelationType(AttachService::RELATION_TYPE_CLIENT, $datum['value'], $attachField);
                    }
                }
            }
        }

        return $list;
    }

    /**
     * 联系人编辑表单数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getEditInfo(int $id): mixed
    {
        $info = $this->dao->get($id)?->toArray();
        if (! $info) {
            throw $this->exception('数据获取异常');
        }

        $attachField   = $this->getAttachField();
        $attachService = app(AttachService::class);
        $list          = app(FormService::class)->getFormDataWithType(CustomEnum::LIAISON, platform: $this->getPlatform());
        foreach ($list as &$item) {
            foreach ($item['data'] as &$datum) {
                if (array_key_exists($datum['key'], $info)) {
                    $type           = strtolower($datum['type']);
                    $inputType      = strtolower($datum['input_type']);
                    $datum['value'] = $this->handleFieldValue($inputType, $type, $info[$datum['key']], CustomEnum::SCENE_EDIT);
                    if ($inputType == 'member') {
                        $datum['options'] = $this->handleFieldValue($inputType, $type, $info[$datum['key']]);
                    }
                    is_numeric($datum['value']) && $datum['value'] = (float) $datum['value'];

                    if (in_array($inputType, ['file', 'images'])) {
                        $datum['files'] = empty($datum['value']) ? []
                            : $attachService->getListByRelationType(AttachService::RELATION_TYPE_CLIENT, $datum['value'], $attachField);
                    }
                }
            }
        }

        return $list;
    }

    /**
     * 无需同步字段.
     * @return string[]
     */
    public function getOutOfSyncField(): array
    {
        return [];
    }

    /**
     * 获取列表搜索条件.
     */
    private function viewSearchWhere(array $where, int $uid): array
    {
        if (! isset($where['view_search'])) {
            unset($where['scope_frame']);
            return $where;
        }
        switch ((int) $where['view_search']) {
            case 1:// 我负责的
                $where['uid'] = $uid;
                break;
            case 2:// 我查看的
                $where = $this->applyScopeWhere($where, $uid, 'all');
                break;
            case 10:// 我创建的
                $where['creator_uid'] = $uid;
                break;
        }
        unset($where['view_search'], $where['scope_frame']);
        return $where;
    }
}
