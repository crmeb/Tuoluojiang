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

namespace App\Http\Contract\Company;

use PhpOffice\PhpSpreadsheet\Exception;

interface CompanyUserInterface
{
    /**
     * 获取组织人员列表(分页).
     */
    public function getPageFrameUsers(array $where, array $field = ['*'], null|array|string $sort = null, array $with = []): array;

    /**
     * 获取组织人员信息.
     */
    public function getFrameUserInfo(int $id, int $entId): array;

    /**
     * 修改组织人员信息.
     */
    public function updateFrameUserInfo(int $id, int $entId, array $data): bool;

    /**
     * 获取企业用户信息.
     */
    public function userInfo(int $id, array|string $field = ['*']): array|string;

    /**
     * 企业批量导入用户.
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function addMoreMember(int $entID, string $filePath, string $uid, int $frameId, int $limit = 1000): void;
}
