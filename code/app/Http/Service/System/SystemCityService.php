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

namespace App\Http\Service\System;

use App\Constants\CacheEnum;
use App\Http\Dao\Config\CityDao;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 省市区
 * Class SystemCityService.
 */
class SystemCityService extends BaseService
{
    public function __construct(CityDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取城市数据tree型结构.
     * @return mixed
     */
    public function cityTree()
    {
        if (! $this->dao->exists([])) {
            $this->setData();
        }
        $list = Cache::tags([CacheEnum::TAG_CONFIG])->remember(md5('cityTree'), (int) sys_config('system_cache_ttl', 3600), function () {
            $list = $this->dao->select([], ['id', 'city_id as value', 'name as label', 'parent_id as pid'])?->toArray();
            return json_encode(get_tree_children($list), JSON_UNESCAPED_UNICODE);
        });
        return json_decode($list, true);
    }

    /**
     * 重置城市数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function setData(): void
    {
        try {
            $jsonPath = resource_path('district/data1106.json');
            if (! file_exists($jsonPath)) {
                throw $this->exception("JSON 文件不存在：{$jsonPath}");
            }
            $jsonContent = file_get_contents($jsonPath);
            if ($jsonContent === false) {
                throw $this->exception("读取 JSON 文件失败：{$jsonPath}");
            }
            $data = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw $this->exception('JSON 解析失败：' . json_last_error_msg());
            }
            DB::table($this->dao->getTable())->truncate();
            $cityData = $this->transaction(function () use ($data) {
                $this->saveDistricts(collect($data), 0);
                return true;
            });
            $cityData && Cache::tags([CacheEnum::TAG_CONFIG])->flush();
            $dictData = app()->get(DictDataService::class);
            $dataSave = $dictData->transaction(function () use ($dictData) {
                $dictData->delete(['type_id' => 2]);
                $save = collect($this->dao->select(['is_show' => 1], ['id as value', 'name', 'level', 'parent_id as pid'])?->toArray() ?? [])->map(function ($item) {
                    $item['status']     = 1;
                    $item['type_id']    = 2;
                    $item['type_name']  = 'area_cascade';
                    $item['is_default'] = 1;
                    $item['level']      = $item['level'] + 1;
                    return $item;
                })->all();
                return $dictData->insert($save);
            });
            $dataSave && Cache::tags([CacheEnum::TAG_DICT])->flush();
        } catch (\Exception $e) {
            Log::error('重置城市数据失败:' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }

    /**
     * 递归保存所有层级的行政区划.
     * @param Collection $districts 当前层级的行政区划集合
     * @param int $parentId 父级 ID（顶级为 0）
     */
    private function saveDistricts(Collection $districts, int $parentId): void
    {
        // 批量收集当前层级数据
        $currentLevelData = [];
        $childDistricts   = []; // 临时存储子级数据，key 为当前层级记录的 city_id
        foreach ($districts as $item) {
            // 收集当前层级数据（准备批量插入）
            $currentLevelData[] = [
                'city_id'     => $item['id'],
                'name'        => $item['fullname'],
                'merger_name' => $item['name'] ?? $item['fullname'],
                'level'       => $item['level'] - 1,
                'parent_id'   => $parentId,
                'lat'         => $item['location']['lat'] ?? 0, // 处理可能的 null
                'lng'         => $item['location']['lng'] ?? 0,
                'is_show'     => 1,
            ];
            // 收集子级数据（如果存在），关联当前 item 的 city_id
            if (! empty($item['districts'])) {
                $childDistricts[$item['id']] = $item['districts'];
            }
        }
        // 批量插入当前层级数据
        if (! empty($currentLevelData)) {
            $this->dao->insert($currentLevelData);
        }
        // 递归处理子级数据
        if (! empty($childDistricts)) {
            // 批量查询当前层级插入的记录 ID（通过 city_id 关联）
            $currentLevelRecords = collect($this->dao->select(['city_id' => array_keys($childDistricts), 'parent_id' => $parentId], ['id', 'city_id', 'parent_id']))->pluck('id', 'city_id')->all();
            // 遍历子级数据，递归保存
            foreach ($childDistricts as $cityId => $children) {
                $currentParentId = $currentLevelRecords[$cityId] ?? 0;
                if ($currentParentId > 0) {
                    $this->saveDistricts(collect($children), $currentParentId);
                }
            }
        }
    }
}
