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

namespace App\Http\Controller\AdminApi\Attach;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Attach\AttachCateService;
use App\Http\Service\Attach\AttachService;
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
 * 附件管理
 * Class AttachController.
 */
#[Prefix('ent/system/attach')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class AttachController extends AuthController
{
    public function __construct(AttachService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 显示列表.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Get('list', '获取附件列表')]
    public function index()
    {
        $where = $this->request->getMore([
            ['cid', ''],
            ['way', 2],
            ['up_type', ''],
            ['cate_name', '', 'name'],
            ['entid', 1],
            ['file_ext', ['jpg', 'png', 'gif', 'jpeg', 'webp']],
        ]);
        return $this->success($this->service->getImageList($where));
    }

    /**
     * 新建附件记录.
     * @throws BindingResolutionException
     */
    #[Post('save', '新建附件记录')]
    public function save()
    {
        [$pid, $file, $way, $relationType, $relationId, $eid] = $this->request->postMore([
            ['cid', 0], // 分类ID
            ['file', []], // 文件内容(含地址url、名称name、大小size、类型type)
            ['way', 2], // 来源：1、总后台；2、分后台；3、用户
            ['relation_type', ''], // 关联类型
            ['relation_id', 0], // 关联ID
            ['eid', ''],
        ], true);
        $res = $this->service->setRelationType($relationType)->setRelationId((int) $relationId, (int) $eid)
            ->saveFile((int) $pid, $file, (int) $way, $this->entId, $this->uuid);
        return $this->success($res);
    }

    /**
     * 删除指定资源.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Delete('delete', '删除指定资源')]
    public function delete()
    {
        [$ids] = $this->request->postMore([
            ['ids', []],
        ], true);
        $this->service->delImg($ids, $this->entId);
        return $this->success('删除成功');
    }

    /**
     * 图片上传.
     * @param mixed $attach_type
     * @param int $type
     * @return mixed
     */
    #[Post('upload/{attach_type?}/{type?}', '图片上传')]
    public function upload($attach_type = 1, $type = 0)
    {
        [$pid, $file, $way, $relationType, $relationId, $eid, $md5, $chunkIndex, $chunkTotal] = $this->request->postMore([
            ['cid', 0],
            ['file', 'file'],
            ['way', 2],
            ['relation_type', ''],
            ['relation_id', 0],
            ['eid', ''],
            ['md5', ''],        // 文件md5
            ['chunk_index', 0], // 分片索引
            ['chunk_total', 0], // 总分片数
        ], true);
        // 路由参数默认是 string，AttachService::upload 要求 int，必须强转.
        $res = $this->service->setRelationType($relationType)
            ->setRelationId((int) $relationId, (int) $eid)
            ->setOption(['chunk_index' => (int) $chunkIndex, 'chunk_total' => (int) $chunkTotal, 'md5' => $md5])
            ->upload((int) $pid, (string) $file, (int) $attach_type, (int) $type, (int) $way, $this->entId, $this->uuid);
        return $this->success($res === true ? 'ok' : '上传成功', $res === true ? [] : $res);
    }

    /**
     * 移动图片.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Put('move', '修改附件分类')]
    public function moveImageCate()
    {
        $data = $this->request->postMore([
            ['pid', 0],
            ['images', ''],
        ]);
        $this->service->move($data);
        return $this->success('移动成功');
    }

    /**
     * 修改附件名称.
     * @param mixed $attach_id
     * @return mixed
     */
    #[Put('update/{attach_id}', '修改附件名称')]
    public function update($attach_id)
    {
        $realName = $this->request->post('real_name', '');
        if (! $realName) {
            return $this->fail('文件名称不能为空');
        }
        $this->service->update($attach_id, ['real_name' => $realName]);
        return $this->success('common.update.succ');
    }

    /**
     * 文件上传.
     * @param mixed $attach_type
     * @return mixed
     */
    #[Post('file/{attach_type?}', '附件文件上传')]
    public function fileUpload($attach_type = 1)
    {
        [$file] = $this->request->postMore([
            ['file', 'file'],
        ], true);
        $res = $this->service->fileUpload($file, $attach_type);
        return $this->success('上传成功', ['src' => $res]);
    }

    /**
     * 获取考核封面图.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Get('cover', '获取考核封面图')]
    public function getAssessCover()
    {
        return $this->success($this->service->getAssessCover(['entids' => $this->entId]));
    }

    /**
     * 考核封面上传.
     * @param int $attach_type
     * @param int $type
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('cover/{attach_type?}/{type?}', '考核封面上传')]
    public function uploadCover($attach_type = 1, $type = 0)
    {
        $cateService                                    = app()->get(AttachCateService::class);
        $pid                                            = $cateService->value(['keyword' => 'assessCover', 'is_show' => 1], 'id');
        [$file, $entid, $md5, $chunkIndex, $chunkTotal] = $this->request->postMore([
            ['file', 'file'],
            ['entid', 1],
            ['md5', ''],        // 文件md5
            ['chunk_index', 0], // 分片索引
            ['chunk_total', 0], // 总分片数
        ], true);
        $res = $this->service->setOption(['chunk_index' => (int) $chunkIndex, 'chunk_total' => (int) $chunkTotal, 'md5' => $md5])
            ->upload((int) $pid, $file, $attach_type, $type, 2, $entid, $this->uuid);
        return $this->success($res === true ? 'ok' : '上传成功', $res === true ? [] : ['src' => link_file($res)]);
    }

    /**
     * 删除考核封面图.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Delete('cover', '删除考核封面图')]
    public function deleteCover()
    {
        [$ids, $entid] = $this->request->postMore([
            ['ids', []],
            ['entid', 1],
        ], true);
        $this->service->delCover($ids, $entid, $this->uuid);
        return $this->success('删除成功');
    }

    /**
     * 附件重命名.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Put('real_name/{id}', '附件重命名')]
    public function realName($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }
        [$realName] = $this->request->postMore([
            ['real_name', ''],
        ], true);
        $this->service->setRealName((int) $id, $this->entId, $realName);
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 附件详情.
     * @param mixed $id
     * @return mixed
     */
    #[Get('info/{id}', '附件详情')]
    public function info($id)
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }
        $field = ['id', 'file_ext', 'name as file_name', 'real_name', 'att_size as file_size', 'att_type as file_type', 'att_dir as file_url'];
        return $this->success($this->service->getInfo($id, $field));
    }

    /**
     * 附件详情.
     * @param mixed $id
     * @return mixed
     */
    #[Put('info/{id}', '附件详情修改')]
    public function updateContent($id)
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }
        [$file, $isDoc] = $this->request->postMore([
            ['content', 'content'],
            ['is_file', 0],
        ], true);
        $this->service->updateFile(auth('admin')->id(), $this->fileId, $file, (bool) $isDoc);
        return $this->success(__('common.operation.succ'));
    }
}
