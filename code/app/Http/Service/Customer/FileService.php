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

namespace App\Http\Service\Customer;

use App\Http\Dao\Attach\SystemAttachDao;
use App\Http\Dao\Customer\FileDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\UploadService;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 客户文件
 * Class FileService.
 */
class FileService extends BaseService
{
    /**
     * @var SystemAttachDao
     */
    protected $dao;

    public function __construct(FileDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array|string[] $field
     * @param null $sort
     */
    public function getList(array $where, array $field = ['id', 'eid', 'cid', 'fid', 'uid', 'name', 'real_name', 'att_dir', 'att_type', 'att_size', 'created_at'], $sort = 'id', array $with = ['card']): array
    {
        return parent::getList($where, $field, $sort, $with);
    }

    /**
     * 删除.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function delImg(int $id)
    {
        if (! $id) {
            throw $this->exception(__('common.empty.attrs'));
        }
        $attinfo = $this->dao->get($id);
        if (! $attinfo) {
            throw $this->exception(__('common.operation.noExists'));
        }
        if ($attinfo->entid != 1) {
            throw $this->exception(__('common.operation.noPermission'));
        }
        return $this->transaction(function () use ($attinfo, $id) {
            $res = $this->dao->delete($id);
            try {
                $upload = UploadService::init($attinfo['up_type']);
                if ($attinfo['up_type'] == 1) {
                    if (strpos($attinfo['att_dir'], '/') == 0) {
                        $attinfo['att_dir'] = substr($attinfo['att_dir'], 1);
                    }
                    if ($attinfo['att_dir']) {
                        $upload->delete($attinfo['att_dir']);
                    }
                } else {
                    if ($attinfo['name']) {
                        $upload->delete($attinfo['name']);
                    }
                }
            } catch (\Throwable $e) {
            }
            return $res;
        });
    }

    /**
     * 修改附件关联.
     * @return bool
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function updateRelation(array $where, $value, string $key)
    {
        return $this->dao->updateRelation($where, $value, $key);
    }

    /**
     * 重命名.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function setRealName(int $id, string $realName): bool
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }
        if ($info->real_name === $realName) {
            return true;
        }
        $info->real_name = $realName;
        return $info->save();
    }

    /**
     * 上传路径转化,默认路径.
     * @param mixed $entid
     * @return string
     * @throws \Exception
     */
    protected function make_path($path, int $type = 2, bool $force = false, $entid = 0)
    {
        $path = DIRECTORY_SEPARATOR . ltrim(rtrim($path));
        if ($entid) {
            $path .= DIRECTORY_SEPARATOR . $entid;
        }
        switch ($type) {
            case 1:
                $path .= DIRECTORY_SEPARATOR . date('Y');
                break;
            case 2:
                $path .= DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');
                break;
            case 3:
                $path .= DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR . date('d');
                break;
        }
        try {
            if (is_dir(public_path('uploads') . $path) == true || mkdir(public_path('uploads') . $path, 0777, true) == true) {
                return trim(str_replace(DIRECTORY_SEPARATOR, '/', $path), '.');
            }
            return '';
        } catch (\Exception $e) {
            if ($force) {
                throw new \Exception($e->getMessage());
            }
            return '无法创建文件夹，请检查您的上传目录权限：' . public_path('uploads') . DIRECTORY_SEPARATOR . 'attach' . DIRECTORY_SEPARATOR;
        }
    }
}
