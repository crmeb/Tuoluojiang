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

namespace App\Http\Dao\Cloud;

use App\Http\Model\Cloud\CloudFile;
use App\Http\Model\Cloud\CloudViewHistory;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\services\wps\WebOffice;
use Tuoluojiang\Open\traits\dao\JoinSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;

class CloudFileDao extends BaseDao
{
    use TogetherSearchTrait;
    use JoinSearchTrait;

    public function hasDeleted(string $path)
    {
        if (! $path || $path === '/') {
            return false;
        }
        $id = explode('/', $path);
        return $this->getModel(false)->whereIn('id', $id)->where('is_del', 1)->count() > 0;
    }

    public function getPath($id)
    {
        return $this->search(['id' => $id])->value('path');
    }

    /**
     * 获取含删除的文件.
     * @param mixed $where
     * @param mixed $field
     * @return BaseModel
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getWithTrashed($where, $field = ['*'])
    {
        return $this->setTrashed()->search($where)->select($field);
    }

    /**
     * 最近浏览文件查询.
     * @param mixed $where
     * @throws BindingResolutionException
     */
    public function getViewHistorySearch($where): mixed
    {
        return $this->getJoinModel('id', 'folder_id', '=', 'right')
            ->when(isset($where['sort_by']) && $where['sort_by'] !== '', function ($query) use ($where) {
                $query->orderBy('type', 'DESC')->orderBy($where['sort_type'], $where['sort_by']);
            })
            ->when(isset($where['recycle']) && $where['recycle'] == 1, function ($query) {
                $query->whereNotNull('deleted_at');
            })
            ->when(isset($where['uid']) && $where['uid'] !== '', function ($query) use ($where) {
                $query->where($this->getFiled('uid', $this->aliasB), $where['uid']);
            })
            ->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use ($where) {
                $query->where($this->getFiled('user_id', $this->aliasB), $where['user_id']);
            })
            ->when(isset($where['file_type']) && $where['file_type'] !== '', function ($query) use ($where) {
                return match ($where['file_type']) {
                    'word'  => $query->whereIn($this->getFiled('file_ext'), WebOffice::WPS_OFFICE_WORD_TYPE),
                    'ppt'   => $query->whereIn($this->getFiled('file_ext'), WebOffice::WPS_OFFICE_PPT_TYPE),
                    'excel' => $query->whereIn($this->getFiled('file_ext'), WebOffice::WPS_OFFICE_SHEET_TYPE),
                    'image' => $query->whereIn($this->getFiled('file_ext'), ['jpg', 'jpeg', 'png', 'gif', 'pem']),
                    default => $query->where($this->getFiled('file_ext'), $where['file_type']),
                };
            }, function ($query) {
                return $query->whereIn($this->getFiled('file_ext'), array_merge(WebOffice::WPS_OFFICE_WORD_TYPE, WebOffice::WPS_OFFICE_PPT_TYPE, WebOffice::WPS_OFFICE_SHEET_TYPE, ['jpg', 'jpeg', 'png', 'gif', 'pem']));
            })
            ->when(isset($where['pid']) && $where['pid'] !== '', function ($query) use ($where) {
                $query->where($this->getFiled('pid'), $where['pid']);
            })
            ->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
                $query->where($this->getFiled('name'), 'LIKE', "%{$where['keyword']}%");
            })
            ->where($this->getFiled('is_temp'), 0)->where($this->getFiled('is_del'), 0)
            ->orderByDesc($this->getFiled('updated_at', $this->aliasB));
    }

    /**
     * 空间查询.
     * @param mixed $where
     * @throws BindingResolutionException
     */
    public function getSpaceSearch($where): Builder
    {
        return $this->getModel(false)->leftJoin('folder_share', function ($query) {
            $query->on('folder_share.folder_id', '=', 'folder.id');
        })
            ->when(isset($where['pid']), function ($query) use ($where) {
                $query->where('folder.pid', $where['pid']);
            })
            ->where('folder.type', 1)
            ->when(isset($where['uid']) && $where['uid'] !== '', function ($query) use ($where) {
                $query->where(function ($query) use ($where) {
                    $query->where('folder.user_id', $where['uid'])->orWhere('folder_share.user_id', $where['uid']);
                });
            })->with(['user'])->groupBy('folder.id');
    }

    protected function setModel()
    {
        return CloudFile::class;
    }

    protected function setModelB(): string
    {
        return CloudViewHistory::class;
    }
}
