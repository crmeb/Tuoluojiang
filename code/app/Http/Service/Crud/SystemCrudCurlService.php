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

namespace App\Http\Service\Crud;

use App\Http\Dao\Crud\SystemCrudCurlDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\exceptions\ApiException;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\HttpService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * 接口管理.
 */
class SystemCrudCurlService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    /**
     * 缓存前缀
     * @var string
     */
    private $cacheTagName = 'system_crud_curl';

    public function __construct(SystemCrudCurlDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 前置请求
     * @return array|mixed|mixed[]
     */
    public function preRequest(string $url, string $method, array $data = [], array $header = [], int $cacheTime = 0)
    {
        $key = md5($url . $method . json_encode($data) . json_encode($header));
        if ($cacheTime && Cache::tags($this->cacheTagName)->has($key)) {
            return Cache::tags($this->cacheTagName)->get($key);
        }

        $formData = [];
        foreach ($data as $item) {
            if (! isset($item['name'])) {
                continue;
            }
            if ($item['value']) {
                $formData[$item['name']] = $item['value'];
            }
        }

        $formHeader = [];
        foreach ($header as $item) {
            if (! isset($item['name'])) {
                continue;
            }
            if ($item['value']) {
                $formHeader[] = $item['name'] . ':' . $item['value'];
            }
        }
        $formHeader[] = 'Content-Type:application/json; charset=utf-8';

        if (strtoupper($method) === 'GET') {
            $url .= '?' . http_build_query($formData);
            $formData = [];
        } else {
            $formData = json_encode($formData);
        }

        $res = (new HttpService())->setHeader($formHeader)->parseJsonCurl($url, $formData, $method, true, 5);

        if ($cacheTime) {
            Cache::tags($this->cacheTagName)->forever($key, $res->toArray());
        }

        return $res->toArray();
    }

    /**
     * 发送请求
     * @return Collection
     */
    public function request(string $url, string $method, array $data = [], array $header = [])
    {
        $header[] = 'Content-Type:application/json; charset=utf-8';
        if (strtoupper($method) === 'GET') {
            $url .= '?' . http_build_query($data);
            $data = [];
        } else {
            $data = json_encode($data);
        }
        return (new HttpService())->setHeader($header)->parseJsonCurl($url, $data, $method, true, 5);
    }

    /**
     * 获取接口发送请求
     * @return array|array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function send(int $id, array $data = [], int $page = -1)
    {
        $pageData = [
            'key_name' => '',
            'page'     => -1,
            'is_page'  => false,
            'page_end' => true,
        ];

        $curlInfo = $this->dao->get($id)?->toArray();
        if (! $curlInfo) {
            throw new ApiException('没有查询到接口数据');
        }
        $preRes = [];
        if ($curlInfo['is_pre']) {
            $preRes = $this->preRequest(
                url: $curlInfo['pre_url'],
                method: $curlInfo['pre_method'],
                data: $curlInfo['pre_data'],
                header: $curlInfo['pre_headers'],
                cacheTime: $curlInfo['pre_cache_time']
            );
        }

        $headers = [];
        foreach ($curlInfo['headers'] as $header) {
            if (! isset($header['name'])) {
                continue;
            }
            if ($header['type']) {
                if (Arr::has($preRes, $header['value'])) {
                    $headers[] = $header['name'] . ':' . ($header['prefix'] ?? '') . Arr::get($preRes, $header['value']);
                }
            } elseif ($header['value']) {
                $headers[] = $header['name'] . ':' . ($header['prefix'] ?? '') . $header['value'];
            }
        }

        $formData = [];
        foreach ($curlInfo['data'] as $item) {
            if (! isset($item['name'])) {
                continue;
            }
            if ((int) $item['type'] == 1) {
                if (Arr::has($preRes, $item['value'])) {
                    $formData[$item['name']] = Arr::get($preRes, $item['value']);
                }
            } elseif ((int) $item['type'] == 0) {
                $formData[$item['name']] = $item['value'];
            } elseif ((int) $item['type'] == 2) {
                $pageData['key_name'] = $item['name'];
                $pageData['page']     = $item['value'];
                $pageData['is_page']  = true;
                if ($page === -1) {
                    $formData[$item['name']] = $item['value'];
                } else {
                    $formData[$item['name']] = $page;
                    $pageData['page']        = $page;
                }
            }
        }

        $formData = array_merge($formData, $data);

        $response = $this->request(
            url: $curlInfo['url'],
            method: $curlInfo['method'],
            data: $formData,
            header: $headers
        )->toArray();

        return ['response' => $response, 'pageData' => $pageData];
    }

    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    public function resourceEdit(int $id, array $other = []): array
    {
        return [];
    }

    /**
     * 把数组转换成带有点的数组.
     * @param mixed $array
     * @param mixed $prefix
     * @return array
     */
    public function flattenArrayWithDots($array, $prefix = '')
    {
        $result = [];
        foreach ($array as $key => $value) {
            $keyStr = $key;
            if (is_int($key)) {
                $keyStr = '*';
            }
            $newKey = $prefix ? $prefix . '.' . $keyStr : $keyStr;
            if (is_array($value)) {
                // 如果是数组，递归调用
                $result = array_merge($result, $this->flattenArrayWithDots($value, $newKey));
            } else {
                // 如果是标量值，直接添加到结果数组中
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    /**
     * 新增.
     * @return BaseModel|mixed|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        if ($data['is_pre']) {
            if (! $data['pre_url']) {
                throw $this->exception('缺少前置请求地址');
            }
        }
        return $this->dao->create($data);
    }

    /**
     * 修改.
     * @param mixed $id
     * @return int|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        if ($data['is_pre']) {
            if (! $data['pre_url']) {
                throw $this->exception('缺少前置请求地址');
            }
        }
        return $this->dao->update($id, $data);
    }

    public function resourceDelete($id, ?string $key = null)
    {
        if (app()->get(SystemCrudEventService::class)->count(['curl_id' => $id])) {
            throw $this->exception('已经关联触发器，暂时无法删除，请先解除触发器关联的接口');
        }
        return $this->dao->delete($id, $key);
    }
}
