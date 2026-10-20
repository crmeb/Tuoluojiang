<?php

namespace App\Http\Service\Crud;

use App\Http\Dao\Crud\SystemCrudCommentDao;
use App\Http\Model\Crud\SystemCrud;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 评论
 */
class SystemCrudCommentService extends BaseService
{

    /**
     * @param SystemCrudCommentDao $dao
     */
    public function __construct(SystemCrudCommentDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 创建评论
     * @param SystemCrud $systemCrud
     * @param array $data
     * @param int $id
     * @param int $uid
     * @return BaseModel|Model
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function createComment(SystemCrud $systemCrud, array $data, int $id, int $uid)
    {
        $data['data_id'] = $id;
        $data['uid'] = $uid;
        $data['crud_id'] = $systemCrud->id;
        return $this->dao->create($data);
    }

    /**
     * 获取评论列表
     * @param SystemCrud $systemCrud
     * @param int $id
     * @return array|mixed
     */
    public function getCommentList(SystemCrud $systemCrud, int $id)
    {
        return $this->getList(where: ['crud_id' => $systemCrud->id, 'data_id' => $id, 'pid' => 0], sort: 'id', with: [
            'user'  => fn($q) => $q->select(['name', 'id', 'avatar']),
            'reply' => fn($q) => $q->with(['user' => fn($q) => $q->select(['name', 'id', 'avatar'])])
        ]);
    }
}
