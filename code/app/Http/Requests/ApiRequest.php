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

namespace App\Http\Requests;

use App\Http\Model\Crud\SystemCrud;
use App\Http\Requests\BaseRequest;
use Tuoluojiang\Open\interfaces\ApiRequestInterface;

/**
 * Class ApiRequest.
 * @method int adminId() 获取总后台ID
 * @method array adminInfo(string $key = null) 获取总后台登录账号信息
 * @method int entId() 获取企业后台登录账号ID
 * @method bool isEnt() 是否登录企业
 * @method array entInfo(string $key = null) 获取企业后台登录账号信息
 * @method string uuId() 获取用户登录企业后台账号ID
 * @method array userInfo(string $key = null) 获取用户登录企业后台账号信息
 * @property SystemCrud $crudInfo
 */
class ApiRequest extends BaseRequest implements ApiRequestInterface
{
    /**
     * 验证规则.
     * @var array
     */
    protected $rules = [];

    /**
     * 返回是否一维数组.
     * @var bool
     */
    protected $suffix = false;

    /**
     * @return $this
     */
    public function setSuffix(bool $suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * 获取GET请求的数据.
     */
    public function getMore(array $params = [], ?bool $suffix = null): array
    {
        $queryData = parent::getMore($this->getParamKeys($params), false);
        return $this->mergeData($queryData, $suffix);
    }

    /**
     * 获取POST请求的数据.
     */
    public function postMore(array $params = [], ?bool $suffix = null): array
    {
        $postData = parent::postMore($this->getParamKeys($params), false);
        return $this->mergeData($postData, $suffix);
    }

    /**
     * 处理规则参数.
     * @return array
     */
    protected function getParamKeys(array $params = [])
    {
        $paramsKey = [];
        foreach ($this->rules() as $rule => $value) {
            $paramsKey[] = [$rule, ''];
        }
        if ($params) {
            $paramsKey = array_merge($paramsKey, $params);
        }
        return $paramsKey;
    }

    /**
     * 合并请求参数.
     * @return array
     */
    protected function mergeData(array $data, ?bool $suffix = null)
    {
        if ($suffix || $this->suffix) {
            $nowData = [];
            foreach ($data as $item) {
                $nowData[] = $item;
            }
            $this->suffix = false;
            return $nowData;
        }
        return $data;
    }
}
