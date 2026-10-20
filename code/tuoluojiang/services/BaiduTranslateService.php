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

namespace Tuoluojiang\Open\services;

use Tuoluojiang\Open\exceptions\HttpServiceExceptions;
use Illuminate\Support\Collection;

class BaiduTranslateService extends HttpService
{
    protected $appid = '20210323000738726';

    protected $apiHttps = 'http://api.fanyi.baidu.com/api/trans/vip/translate';

    protected $secretKey = 'NvGHzhhq5lFI80KT55po';

    /**
     * 查询翻译.
     * @return Collection
     */
    public function query(string $query, string $to, string $from = 'auto')
    {
        mt_srand();
        $salt = rand(1000, 99999);
        $res  = $this->getJSON($this->apiHttps, [
            'q'     => $query,
            'appid' => $this->appid,
            'from'  => $from,
            'to'    => $to,
            'salt'  => $salt,
            'sign'  => $this->getSign($query, $salt),
        ]);

        if ($res->get('error_code', '0')) {
            throw new HttpServiceExceptions($res->get('error_msg'));
        }

        return $res;
    }

    /**
     * 获取签名.
     * @return string
     */
    protected function getSign(string $q, int $salt)
    {
        return md5($this->appid . $q . $salt . $this->secretKey);
    }
}
