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

namespace App\Http\Service\Other;

use App\Http\Dao\Other\ViewSearchDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;

/**
 * 视图管理 服务
 */
class ViewSearchService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public function __construct(ViewSearchDao $dao)
    {
        $this->dao = $dao;
    }

    public function resourceSave(array $data)
    {
        $save = [
            'uid'       => auth('admin')->id(),
            'content'   => $data['content'] ? json_encode($data['content']) : '',
            'title'     => $data['title'],
            'category'  => $data['category'],
            'types'     => 1,
            'sort'      => $data['sort'],
            'is_public' => $data['is_public'],
        ];
        return $this->dao->create($save);
    }

    public function resourceUpdate($id, array $data)
    {
        $info = $this->dao->get($id, ['id', 'sort', 'title', 'uid', 'types', 'is_public'])?->toArray();
        if (! $info) {
            throw $this->exception('未找到相关视图');
        }
        if ($info['uid'] != auth('admin')->id() || $info['types'] != 1) {
            throw $this->exception('无权限操作');
        }
        return $this->dao->update($id, [
            'sort'      => $data['sort'],
            'title'     => $data['title'],
            'is_public' => $data['is_public'],
            'content'   => $data['content'] ? json_encode($data['content']) : '',
        ]);
    }

    public function getList(array $where, array $field = ['id', 'title', 'uid', 'types', 'is_public', 'sort', 'content'], $sort = ['sort', 'id'], array $with = ['admin']): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, sort: $sort, with: $with);
        $count          = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    public function resourceEdit(int $id, array $other = []): array
    {
        $info = $this->dao->get($id, ['id', 'sort', 'title', 'uid', 'types', 'is_public'])?->toArray();
        if (! $info) {
            throw $this->exception('未找到相关视图');
        }
        if ($info['uid'] != auth('admin')->id() || $info['types'] != 1) {
            throw $this->exception('无权限操作');
        }
        return $info;
    }

    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    public function resourceSort(mixed $data)
    {
        foreach (array_reverse($data) as $key => $vo) {
            $this->dao->update($vo, ['sort' => $key]);
        }
        return true;
    }
}
