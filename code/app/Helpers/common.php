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
use App\Http\Requests\ApiValidate;
use App\Http\Service\Notice\NoticeRecordService;
use Tuoluojiang\Open\exceptions\ApiException;
use Tuoluojiang\Open\exceptions\ApiRequestException;
use Tuoluojiang\Open\services\ConfigService;
use Tuoluojiang\Open\services\GroupDataService;
use Tuoluojiang\Open\services\SmsService;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Fastknife\Service\BlockPuzzleCaptchaService;
use Fastknife\Service\ClickWordCaptchaService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

if (! function_exists('sys_config')) {
    /**
     * 获取或设置单个系统配置.
     *
     * @param null|string $key 配置名称；为 null 时返回 ConfigService 实例
     * @param null|mixed $default 默认值；当 $isSet 为 true 时作为写入值
     * @param bool $isSet 是否写入配置；为 true 时写入后仅清除当前 key 缓存
     * @param bool $clearCache 是否先清除当前 key 缓存再读取；默认 false 走缓存读取
     * @return null|Application|(Application&ConfigService)|ConfigService|mixed|string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    function sys_config(?string $key = null, $default = null, bool $isSet = false, bool $clearCache = false)
    {
        if ($key === null) {
            return app('config_crmeb');
        }

        return app('config_crmeb')->get($key, $default, $isSet, $clearCache);
    }
}
if (! function_exists('sys_more')) {
    /**
     * 获取多个系统配置.
     * @return Application|(Application&ConfigService)|array|ConfigService|mixed
     * @throws BindingResolutionException
     */
    function sys_more(array $keys = [])
    {
        if ($keys === []) {
            return app('config_crmeb');
        }

        return app('config_crmeb')->more($keys);
    }
}

if (! function_exists('sys_data')) {
    /**
     * 获取组合数据配置.
     * @return Application|(Application&GroupDataService)|array|GroupDataService|mixed
     * @throws BindingResolutionException
     */
    function sys_data(?string $key = null, int $limit = 0)
    {
        if ($key === null) {
            return app('group_config');
        }

        return app('group_config')->getData($key, $limit);
    }
}

if (! function_exists('ent_data')) {
    /**
     * 获取组合数据配置.
     * @return Application|(Application&GroupDataService)|array|GroupDataService|mixed
     * @throws BindingResolutionException
     */
    function ent_data(int $entid = 0, ?string $key = null, int $limit = 0, int $page = 0)
    {
        if ($key === null) {
            return app('group_config');
        }

        return app('group_config')->getData($key, $limit, $entid, $page);
    }
}
if (! function_exists('uuid_to_uid')) {
    /**
     * 根据用户uuid获取企业用户ID.
     * @return array|int|string
     * @throws BindingResolutionException
     */
    function uuid_to_uid(string $uuid, int $entId = 1)
    {
        return app('enterprise_user')->uuidToUid($uuid, $entId);
    }
}
if (! function_exists('uuid_to_card_id')) {
    /**
     * 根据用户uuid获取企业用户名片ID.
     *
     * @return Application|array|GroupDataService|mixed
     * @throws BindingResolutionException
     */
    function uuid_to_card_id(string $uuid, int $entid = 1)
    {
        return app('enterprise_user')->uuidToCardid($uuid, $entid);
    }
}

if (! function_exists('uuid_to_card')) {
    /**
     * 根据用户uuid获取企业用户名片.
     *
     * @return Application|array|GroupDataService|mixed
     * @throws BindingResolutionException
     */
    function uuid_to_card(string $uuid, int $entid = 1, array|string $field = ['*'])
    {
        return app('enterprise_user')->uuidToCard($uuid, $entid, $field);
    }
}
if (! function_exists('card_to_uid')) {
    /**
     * 根据企业用户名片获取用户uid.
     *
     * @return Application|array|GroupDataService|mixed
     * @throws BindingResolutionException
     */
    function card_to_uid(int $cardId, int $entid = 1)
    {
        return app('enterprise_user')->cardToUid($cardId, $entid);
    }
}

if (! function_exists('uid_to_uuid')) {
    /**
     * 根据用户uuid获取企业用户ID.
     *
     * @return Application|array|GroupDataService|mixed
     * @throws BindingResolutionException
     */
    function uid_to_uuid(int $uid)
    {
        return app('enterprise_user')->uidToUuid($uid);
    }
}

if (! function_exists('is_win')) {
    /**
     * 是否是windows操作系统
     *
     * @return bool
     */
    function is_win(): bool
    {
        return strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN';
    }
}

if (! function_exists('get_tree_children')) {
    /**
     * 获取tree型数据.
     *
     * @param array $data 数据
     * @param string $children 子数据名
     * @param string $keyName 数据key名
     * @param string $pidName 数据上级key名
     */
    function get_tree_children(array $data, string $children = 'children', string $keyName = 'id', string $pidName = 'pid'): array
    {
        $list = [];
        foreach ($data as $value) {
            $list[$value[$keyName]] = $value;
        }
        $tree = []; // 格式化好的树
        foreach ($list as $item) {
            if (isset($list[$item[$pidName]]) && $item[$pidName]) {
                $list[$item[$pidName]][$children][] = &$list[$item[$keyName]];
            } else {
                $tree[] = &$list[$item[$keyName]];
            }
        }

        return $tree;
    }
}

if (! function_exists('validate')) {
    /**
     * 生成验证对象
     *
     * @param mixed $validate
     * @return ApiValidate|MessageBag
     */
    function validate($validate, array $message = [], bool $failException = true)
    {
        if (is_array($validate)) {
            $validator = Validator::make(request()->all(), $validate, $message);

            if ($validator->fails() && $failException) {
                throw new ApiRequestException($validator->errors()->first(), 400, null, 200);
            }

            return $validator->errors();
        }
        if (str_contains($validate, '.')) {
            // 支持场景
            [$validate, $scene] = explode('.', $validate);
        }

        /** @var ApiValidate $v */
        $v = new $validate();

        if (! empty($scene)) {
            $v->scene($scene);
        }

        return $v->setMessage($message)->failException($failException);
    }
}

if (! function_exists('get_roule_mobu')) {
    /**
     * 获取配置中的路由模块名.
     *
     * @return string
     */
    function get_roule_mobu(string $value = '', int $type = 0): string
    {
        $typeName = $type ? 'ent' : 'admin';
        return config('app.' . $typeName . '.path', '/admin') . $value;
    }
}

if (! function_exists('get_image_frame_url')) {
    /**
     * 获取总后台图片frame URL.
     */
    function get_image_frame_url(array $query = [], int $type = 0, string $value = '/setting/uploadPicture'): string
    {
        $queryData = [];
        foreach ($query as $k => $v) {
            $queryData[] = $k . '=' . $v;
        }
        $roule    = get_roule_mobu($value, $type);
        $queryStr = implode('&', $queryData);

        return $roule . (! str_contains($roule, '?') ? '?' : '&') . $queryStr;
    }
}

if (! function_exists('verification_api_check')) {
    /**
     * 验证手机号验证码
     *
     * @return bool
     */
    function verification_api_check(string $verificationCode, string $phone): bool
    {
        if (! config('sms.verification')) {
            return true;
        }
        return app()->get(SmsService::class)->captchaVerify($phone, $verificationCode);
    }
}

if (! function_exists('link_file')) {
    /**
     * 文件路径.
     */
    function link_file(string $path): string
    {
        if (! str_contains($path, 'http')) {
            $path = sys_config('site_url', config('app.url')) . $path;
        }

        return $path;
    }
}

if (! function_exists('get_os')) {
    /**
     * 获取操作系统
     *
     * @return string
     */
    function get_os(): string
    {
        $user_agent = request()->header('user-agent') ?? '';
        // 操作系统关键词映射表（顺序敏感，优先匹配更具体的关键词）
        $osMap = [
            'windows nt 5.0' => 'Windows 2000',
            'windows nt 9'   => 'Windows 9X',
            'windows nt 5.1' => 'Windows XP',
            'windows nt 5.2' => 'Windows 2003',
            'windows nt 6.0' => 'Windows Vista',
            'windows nt 6.1' => 'Windows 7',
            'windows nt 6.2' => 'Windows 8',
            'windows nt 6.3' => 'Windows 8.1',
            'windows nt 10'  => 'Windows 10',
            'windows phone'  => 'Windows Phone',
            'android'        => 'Android',
            'iphone'         => 'iPhone',
            'ipad'           => 'iPad',
            'mac'            => 'Mac',
            'sunos'          => 'Sun OS',
            'bsd'            => 'BSD',
            'ubuntu'         => 'Ubuntu',
            'linux'          => 'Linux',
            'unix'           => 'Unix',
        ];
        foreach ($osMap as $keyword => $osName) {
            if (stripos($user_agent, $keyword) !== false) {
                return $osName;
            }
        }
        return 'Other';
    }
}

if (! function_exists('get_download_url')) {
    /**
     * 获取下载地址
     *
     * @return string
     */
    function get_download_url(string $fileId, array $param = [], string $path = 'api/ent'): string
    {
        $param['fileId'] = $fileId;
        $signature       = Crypt::encryptString(json_encode($param));

        return sys_config('site_url', config('app.url')) . ($path ? '/' . $path : '') . '/common/download?signature=' . $signature;
    }
}

if (! function_exists('birthday_to_age')) {
    /**
     * 年份获取年龄.
     *
     * @return false|mixed|string
     */
    function birthday_to_age(string $birthday): int
    {
        [$year, $month, $day] = explode('-', $birthday);
        $year_diff  = (int) date('Y') - (int) $year;
        $month_diff = (int) date('m') - (int) $month;
        $day_diff   = (int) date('d') - (int) $day;
        if ($day_diff < 0 || $month_diff < 0) {
            --$year_diff;
        }

        return $year_diff;
    }
}

if (! function_exists('datetime_timestamp')) {
    /**
     * 获取时间戳，兼容 Eloquent datetime cast 返回的 Carbon/DateTime 对象.
     */
    function datetime_timestamp(mixed $datetime): int|false
    {
        if ($datetime instanceof \DateTimeInterface) {
            return $datetime->getTimestamp();
        }

        if ($datetime === null || $datetime === '') {
            return false;
        }

        if (is_int($datetime)) {
            return $datetime;
        }

        if (is_float($datetime)) {
            return (int) $datetime;
        }

        return strtotime((string) $datetime);
    }
}

if (! function_exists('time_contrast_api_check')) {
    /**
     * 时间对比.
     *
     * @param string $entTime 结束时间
     * @param string $startTime 开始时间
     * @param bool $just 正比或反比
     */
    function time_contrast_api_check(string $entTime, string $startTime, bool $just = false): bool
    {
        if ($just) {
            return datetime_timestamp($startTime) < datetime_timestamp($entTime);
        }
        return datetime_timestamp($entTime) > datetime_timestamp($startTime);
    }
}

if (! function_exists('password_confirm_api_check')) {
    /**
     * 密码确认.
     *
     * @return bool
     */
    function password_confirm_api_check(string $password, string $passwordConfirm): bool
    {
        return $password === $passwordConfirm;
    }
}

if (! function_exists('message')) {
    /**
     * 消息发送
     *
     * @return NoticeRecordService
     * @throws BindingResolutionException
     */
    function message(): NoticeRecordService
    {
        return app()->get(NoticeRecordService::class);
    }
}

if (! function_exists('get_week_num')) {
    /**
     * 根据类型获取星期几.
     *
     * @return int
     */
    function get_week_num(string $type): int
    {
        return match (strtolower($type)) {
            'monday'    => 1,
            'tuesday'   => 2,
            'wednesday' => 3,
            'thursday'  => 4,
            'friday'    => 5,
            'saturday'  => 6,
            'sunday'    => 7,
            default     => 0,
        };
    }
}

if (! function_exists('get_password_message')) {
    /**
     * 获取密码错误提示.
     *
     * @return mixed
     */
    function get_password_message(array $type = []): string
    {
        $type = $type ?: sys_config('login_password_type');
        $type = is_array($type) ? $type : [0, 2];
        sort($type);
        $typeStr = implode('', $type);

        $message = [
            '0'    => '数字',
            '01'   => '数字+字母',
            '02'   => '数字+小写字母',
            '03'   => '数字+特殊字符',
            '12'   => '大写字母+小写字母',
            '13'   => '大写字母+特殊字符',
            '23'   => '小写字母+特殊字符',
            '012'  => '数字+小写字母+大写字母',
            '0123' => '数字+小写字母+大写字母+特殊字符',
            '013'  => '数字+大写字母+特殊字符',
            '023'  => '数字+小写字母+特殊字符',
            '123'  => '小写字母+大写字母+特殊字符',
        ];

        return $message[$typeStr] ?? '未知';
    }
}
if (! function_exists('assoc_unique')) {
    /**
     * @param mixed $arr
     * @param mixed $key
     * @return mixed
     */
    function assoc_unique(&$arr, $key)
    {
        $tmpArr = [];
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $tmpArr, true)) {
                unset($arr[$k]);
            } else {
                $tmpArr[] = $v[$key];
            }
        }
        sort($arr); // sort函数对数组进行排序

        return $arr;
    }
}

if (! function_exists('str_content')) {
    /**
     * 获取纯文本摘要.
     *
     * @param string $content HTML内容
     * @param int $length 截取长度
     * @return string
     */
    function str_content(string $content, int $length = 320): string
    {
        $decoded  = htmlspecialchars_decode($content);
        $cleaned  = str_replace('&nbsp;', '', $decoded);
        $plainText = strip_tags($cleaned);

        return mb_substr($plainText, 0, $length, 'utf-8') . '...';
    }
}

if (! function_exists('get_start_and_end_time')) {
    /**
     * 获取当前时间的开始时间和结束时间.
     *
     * @return string[]
     */
    function get_start_and_end_time(int $period): array
    {
        $now       = now();
        $startTime = $endTime = '';
        switch ($period) {
            case 1:
                $startTime = $now->startOfWeek()->format('y/m/d') . ' 00:00:00';
                $endTime   = $now->endOfWeek()->format('y/m/d') . ' 23:59:59';
                break;
            case 2:
                $startTime = $now->startOfMonth()->format('y/m/d') . ' 00:00:00';
                $endTime   = $now->endOfMonth()->format('y/m/d') . ' 23:59:59';
                break;
            case 3:
                $startTime = $now->startOfYear()->format('y/m/d') . ' 00:00:00';
                $endTime   = $now->endOfYear()->format('y/m/d') . ' 23:59:59';
                break;
            case 5:
                $startTime = $now->startOfQuarter()->format('y/m/d') . ' 00:00:00';
                $endTime   = $now->endOfQuarter()->format('y/m/d') . ' 23:59:59';
                break;
            case 4:
                if ($now->month >= 7) {
                    $startTime = $now->year . '/7/01 00:00:00';
                    $day       = $now->endOfYear()->endOfDay()->day;
                    $endTime   = $now->year . '/12/' . $day . ' 00:00:00';
                } else {
                    $startTime = $now->year . '/1/01 00:00:00';
                    $day       = $now->startOfYear()->endOfDay()->day;
                    $endTime   = $now->year . '/6/' . $day . ' 23:59:59';
                }
                break;
        }

        return [$startTime, $endTime];
    }
}

if (! function_exists('get_period_type_str')) {
    /**
     * 获取绩效类型.
     *
     * @return string
     */
    function get_period_type_str(int $period)
    {
        return match ($period) {
            1       => '周',
            2       => '月',
            3       => '年',
            4       => '季度',
            5       => '半年',
            default => ''
        };
    }
}

if (! function_exists('toArray')) {
    /**
     * 数据库资源转数组.
     * @param mixed $result
     */
    function toArray($result): array
    {
        if ($result instanceof Model || $result instanceof Collection) {
            return $result->toArray();
        }
        return is_array($result) ? $result : [];
    }
}

if (! function_exists('micro_time')) {
    /**
     * 获取时间.
     */
    function micro_time(): string
    {
        [$usec, $sec] = explode(' ', microtime());
        return $sec . substr($usec, 2, 3);
    }
}

if (! function_exists('crypto_encode')) {
    /**
     * 加密.
     * @param mixed $data
     */
    function crypto_encode($data): string
    {
        $key  = Key::loadFromAsciiSafeString(env('CRYPTO_KEY'));
        $data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return Crypto::encrypt($data, $key);
    }
}

if (! function_exists('crypto_decode')) {
    /**
     * 解密.
     * @param mixed $data
     */
    function crypto_decode($data): mixed
    {
        $key = Key::loadFromAsciiSafeString(env('CRYPTO_KEY'));
        $str = Crypto::decrypt($data, $key);
        return json_decode($str, true);
    }
}

if (! function_exists('format_val')) {
    function format_val(string $value): string
    {
        if (str_contains($value, ' ') || str_contains($value, '$')) {
            $value = "\"{$value}\"";
        }

        return $value;
    }
}

if (! function_exists('modify_env')) {
    function modify_env(array $data): void
    {
        $contentArray = collect(file(base_path('.env'), FILE_IGNORE_NEW_LINES));

        $contentArray->transform(function ($item) use ($data) {
            foreach ($data as $key => $value) {
                $itemArr = explode('=', $item);
                if ($itemArr[0] == $key) {
                    return $key . '=' . format_val((string) $value);
                }
            }

            return $item;
        });

        File::put(base_path('.env'), implode(PHP_EOL, $contentArray->toArray()));

        // 同步更新当前进程的环境变量（Swoole常驻内存下避免缓存问题）
        foreach ($data as $key => $value) {
            $val = (string) $value;
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

if (! function_exists('sql_split')) {
    function sql_split(string $sql, string $tablepre): array
    {
        if ($tablepre != 'eb_') {
            $sql = str_replace('eb_', $tablepre, $sql);
        }

        $sql = preg_replace('/TYPE=(InnoDB|MyISAM|MEMORY)( DEFAULT CHARSET=[^; ]+)?/', 'ENGINE=\1 DEFAULT CHARSET=utf8', $sql);

        $sql          = str_replace("\r", "\n", $sql);
        $ret          = [];
        $num          = 0;
        $queriesarray = explode(";\n", trim($sql));
        unset($sql);
        foreach ($queriesarray as $query) {
            $ret[$num] = '';
            $queries   = explode("\n", trim($query));
            $queries   = array_filter($queries);
            foreach ($queries as $query) {
                $str1 = substr($query, 0, 1);
                if ($str1 != '#' && $str1 != '-') {
                    $ret[$num] .= $query;
                }
            }
            ++$num;
        }
        return $ret;
    }
}
if (! function_exists('getVersion')) {
    /**
     * 获取版本号.
     */
    function getVersion(string $key = ''): array|string
    {
        $version_arr     = [];
        $current_version = @file(base_path('.version'));
        if ($current_version !== false) {
            foreach ($current_version as $val) {
                $parts = explode('=', $val, 2);
                if (count($parts) === 2) {
                    $version_arr[trim($parts[0])] = trim($parts[1]);
                }
            }
        }
        return $key ? ($version_arr[$key] ?? '') : $version_arr;
    }
}
if (! function_exists('getFieldData')) {
    /**
     * 按照键名过滤数据.
     */
    function getFieldData(array $data, array $field): array
    {
        return array_intersect_key($data, array_flip($field));
    }
}

if (! function_exists('getMimetype')) {
    /**
     * 获取文件类型.
     * @param mixed $name
     */
    function getMimetype(string $name): ?string
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext === '') {
            return null;
        }

        return config('upload.mime_types')[$ext] ?? null;
    }
}

if (! function_exists('text_strlen_confirm_api_check')) {
    /**
     * 文本长度确认.
     *
     * @return bool
     */
    function text_strlen_confirm_api_check(string $value, string $len): bool
    {
        return mb_strlen(strip_tags(stripslashes(htmlspecialchars_decode($value)))) <= $len;
    }
}

if (! function_exists('get_env')) {
    /**
     * 根据key获取env的值.
     * @param mixed $key
     */
    function get_env($key): array|string
    {
        if ($key === '') {
            return $key;
        }
        $arr  = [];
        $str  = '';
        $data = collect(file(base_path('.env'), FILE_IGNORE_NEW_LINES))->toArray();
        foreach ($data as $item) {
            if (! $item) {
                continue;
            }
            $itemArr = explode('=', $item);
            if (! isset($itemArr[0]) || ! isset($itemArr[1])) {
                continue;
            }
            if (is_string($key)) {
                if ($key == $itemArr[0]) {
                    $str = trim(format_val($itemArr[1]), '"');
                    break;
                }
            }
            if (is_array($key)) {
                if ($key) {
                    if (in_array($itemArr[0], $key, true)) {
                        $arr[$itemArr[0]] = trim(format_val($itemArr[1]), '"');
                    }
                } else {
                    $arr[$itemArr[0]] = trim(format_val($itemArr[1]), '"');
                }
            }
        }
        return is_array($key) ? $arr : $str;
    }
}

if (! function_exists('sort_mode')) {
    /**
     * 获取排序.
     * @return mixed[]
     */
    function sort_mode(null|array|string $sort = null)
    {
        $orderField = request()->input('sort_field', '');
        $orderValue = request()->input('sort_value', '');
        if (empty($orderField) || empty($orderValue)) {
            return $sort;
        }
        $baseSort = collect([$orderField => $orderValue]);
        if (is_null($sort)) {
            return $baseSort->all();
        }
        if (! is_array($sort)) {
            return $baseSort->put($sort, 'asc')->all();
        }
        return $baseSort->merge($sort)->all();
    }
}

if (! function_exists('is_dimensional_data')) {
    /**
     * 是否为多维数组.
     * @return bool
     */
    function is_dimensional_data(mixed $data = null): bool
    {
        if (! is_array($data)) {
            return false;
        }
        return count(array_filter($data, 'is_array')) > 0;
    }
}

if (! function_exists('format_size')) {
    /**
     * 格式化大小.
     * @param mixed $b
     * @param mixed $times
     * @return bool
     */
    function format_size(float|int $b, int $times = 0): string
    {
        if ($b > 1024) {
            $temp = $b / 1024;
            ++$times;
            return format_size($temp, $times);
        }
        $unit = match ($times) {
            1       => 'KB',
            2       => 'MB',
            3       => 'GB',
            4       => 'TB',
            5       => 'PB',
            6       => 'EB',
            7       => 'ZB',
            default => 'B',
        };
        return sprintf('%.2f', $b) . $unit;
    }
}

if (! function_exists('prefix_correction')) {
    /**
     * 表前缀处理.
     * @return string
     */
    function prefix_correction(string $content, string $correct = '')
    {
        if (! $content) {
            return $content;
        }
        $defaultPrefix = 'eb_';
        $correct = $correct ?: (get_env('DB_PREFIX') ?: $defaultPrefix);
        if ($correct !== $defaultPrefix) {
            $content = str_replace('`' . $defaultPrefix, '`' . $correct, $content);
            $content = preg_replace('/\b(FROM|JOIN|UPDATE|INTO|TABLE)\s+`?' . preg_quote($defaultPrefix, '/') . '([A-Za-z0-9_]+)`?/i', '$1 `' . $correct . '$2`', $content);
        }
        return $content;
    }
}

if (! function_exists('aj_captcha_create')) {
    /**
     * 创建验证码
     * @return array
     */
    function aj_captcha_create(string $captchaType): array
    {
        return aj_get_serevice($captchaType)->get();
    }
}

if (! function_exists('aj_get_serevice')) {
    /**
     * @return BlockPuzzleCaptchaService|ClickWordCaptchaService
     */
    function aj_get_serevice(string $captchaType): BlockPuzzleCaptchaService|ClickWordCaptchaService
    {
        $config                         = Config::get('ajcaptcha');
        $config['cache']['constructor'] = app('cache.store');

        return match ($captchaType) {
            'clickWord'   => new ClickWordCaptchaService($config),
            'blockPuzzle' => new BlockPuzzleCaptchaService($config),
            default       => throw new ApiException('captchaType参数不正确！'),
        };
    }
}

if (! function_exists('aj_captcha_check_one')) {
    /**
     * 验证滑块1次验证
     * @return bool
     */
    function aj_captcha_check_one(string $captchaType, string $token, string $pointJson): bool
    {
        aj_get_serevice($captchaType)->check($token, $pointJson);
        return true;
    }
}

if (! function_exists('aj_captcha_check_two')) {
    /**
     * 验证滑块2次验证
     * @return bool
     */
    function aj_captcha_check_two(string $captchaType, string $captchaVerification): bool
    {
        aj_get_serevice($captchaType)->verificationByEncryptCode($captchaVerification);
        return true;
    }
}

if (! function_exists('array_find')) {
    /**
     * 获取数组中指定key的值
     * @param null|mixed $key
     * @return null|mixed
     */
    function array_find(array $data, mixed $key = null): mixed
    {
        if (! $key) {
            return $data;
        }

        foreach ($data as $id => $item) {
            if (is_callable($key)) {
                if ($key($item)) {
                    return $item;
                }
            } elseif ($key === $id) {
                return $item;
            }
        }

        return null;
    }
}

if (! function_exists('is_nested_array')) {
    /**
     * 是不是嵌套数组.
     * @param mixed $var
     * @return bool
     */
    function is_nested_array(mixed $var): bool
    {
        if (! is_array($var)) {
            return false;
        }
        foreach ($var as $value) {
            if (is_array($value)) {
                return true;
            }
            if (is_nested_array($value)) {
                return true;
            }
        }
        return false;
    }
}

if (! function_exists('format_url')) {
    /**
     * 移除URL中的http/https协议，可选拼接指定前缀
     * @param string $url 原始URL
     * @param null|string $prefix 可选前缀缀（如 'http://'、'https://'，默认null不拼接）
     * @return string 处理后的URL
     */
    function format_url(string $url, ?string $prefix = null): string
    {
        $parsed = parse_url($url);
        // 无法解析URL时，直接处理前缀后返回原URL
        if (empty($parsed['host'])) {
            return $prefix ? rtrim($prefix, '/') . '/' . ltrim($url, '/') : $url;
        }
        // 拼接主机名（含端口）
        $result = $parsed['host'];
        if (! empty($parsed['port'])) {
            $result .= ':' . $parsed['port'];
        }
        // 拼接路径、查询参数、锚点
        if (! empty($parsed['path'])) {
            $result .= $parsed['path'];
        }
        if (! empty($parsed['query'])) {
            $result .= '?' . $parsed['query'];
        }
        if (! empty($parsed['fragment'])) {
            $result .= '#' . $parsed['fragment'];
        }
        // 若指定前缀，拼接前缀（处理斜杠冗余）
        if ($prefix) {
            $result = rtrim($prefix, '/') . '/' . ltrim($result, '/');
        }
        return $result;
    }
}
if (! function_exists('is_empty')) {
    /**
     * 判断字符串是否为无意义值（空字符串、空数组/对象格式字符串等）.
     * @return bool true=无意义，false=有有效内容
     */
    function is_empty(?string $string): bool
    {
        // 先处理null/非字符串类型（直接判定为无意义）
        if (! is_string($string)) {
            return true;
        }
        // 移除首尾空白字符（包括全角空格、换行、制表符等）
        $trimmedStr = trim($string, " \t\n\r\0\x0B\xA0"); // \xA0是全角空格
        // 基础判断：纯空字符串
        if ($trimmedStr === '') {
            return true;
        }
        // 判断空数组/空对象/空引号格式字符串
        if (preg_match('/^\s*(\[\s*\]|\{\s*\}|[\'"]\s*[\'"])\s*$/', $trimmedStr)) {
            return true;
        }

        return false;
    }
}

if (! function_exists('get_name_letter')) {
    /**
     * 根据姓名获取字母索引.
     * - 汉字姓名：取姓氏拼音首字母（大写）
     * - 纯字母姓名：取首字母（大写）
     * - 非汉字非字母（符号等）：返回 #
     *
     * @param string $name 姓名
     * @return string 字母索引
     */
    function get_name_letter(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '#';
        }

        // 检测是否包含汉字
        $hasChinese = preg_match('/[\x{4e00}-\x{9fa5}]/u', $name);

        if ($hasChinese) {
            // 汉字姓名：使用拼音库获取姓氏首字母
            $pinyin = \Overtrue\Pinyin\Pinyin::nameAbbr($name);
            $letter = $pinyin[0] ?? '';
            if ($letter !== '') {
                return strtoupper($letter);
            }
        }

        // 非汉字姓名：取首字符
        $firstChar = mb_substr($name, 0, 1, 'utf-8');

        // 检测首字符是否为字母（a-zA-Z）
        if (preg_match('/^[a-zA-Z]$/', $firstChar)) {
            return strtoupper($firstChar);
        }

        // 其他情况（符号等）返回 #
        return '#';
    }
}

if (! function_exists('mask_phone')) {
    /**
     * 联系电话脱敏展示.
     * 长度 ≥ 7：保留前 3、后 4，中间以 **** 替换；更短则按原长度全部替换为 *；空值返回空串.
     *
     * @param null|string $phone 原始电话
     * @return string 脱敏后的电话
     */
    function mask_phone(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '';
        }

        $length = strlen($phone);
        if ($length < 7) {
            return str_repeat('*', $length);
        }

        return substr($phone, 0, 3) . '****' . substr($phone, -4);
    }
}
