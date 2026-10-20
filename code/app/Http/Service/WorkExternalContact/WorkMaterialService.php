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

namespace App\Http\Service\WorkExternalContact;

use App\Http\Dao\WorkExternalContact\WorkMediaDao;
use App\Http\Service\BaseService;

/**
 * 素材库服务
 */
class WorkMaterialService extends BaseService
{
    public function __construct(WorkMediaDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 搜索素材（支持关键词搜索和分页）
     * @param string $keyword 搜索关键词
     * @param string $type 素材类型 (text/image/file/video/link/mini_program)
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function search(string $keyword, string $type = '', int $page = 1, int $limit = 20): array
    {
        $where = [];
        if ($keyword) {
            $where['real_name_like'] = $keyword;
        }
        if ($type) {
            $where['types'] = $type;
        }

        $list = $this->dao->getList($where, ['*'], $page, $limit, 'id', []);
        $count = $this->dao->count($where);

        return $this->listData($list, $count);
    }

    /**
     * 获取素材详情
     * @param int $id
     * @return array
     */
    public function getDetail(int $id): array
    {
        return $this->dao->get($id)?->toArray() ?: [];
    }
}
