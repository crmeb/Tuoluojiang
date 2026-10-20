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

namespace App\Http\Service;

use App\Constants\CacheEnum;
use App\Http\Contract\Common\CommonInterface;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\System\SystemCityService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\exceptions\UploadException;
use Tuoluojiang\Open\services\SmsService;
use Tuoluojiang\Open\services\UploadService;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Mews\Captcha\Captcha;

class CommonService extends BaseService implements CommonInterface
{
    public function captcha(): array
    {
        /** @var Captcha $captcha */
        $captcha = app()->get(Captcha::class);
        return $captcha->create();
    }

    public function smsVerifyKey(): array
    {
        $key = password_hash(uniqid(more_entropy: true), PASSWORD_BCRYPT);
        Cache::tags([CacheEnum::TAG_OTHER])->add('sms.key.' . $key, 0, 300);
        $expire_time = sys_config('verify_expire_time', 1);
        return compact('key', 'expire_time');
    }

    public function smsVerifyCode($phone, $key, $types): bool
    {
        if (! $key) {
            throw $this->exception('请先获取短信发送KEY');
        }
        if (! Cache::tags([CacheEnum::TAG_OTHER])->has('sms.key.' . $key)) {
            throw $this->exception('短信发送KEY已失效请重新获取');
        }
        Cache::tags([CacheEnum::TAG_OTHER])->forget('sms.key.' . $key);
        $letter = app()->get(SmsService::class);
        if ($letter->hasCode($phone)) {
            throw $this->exception('当前手机号验证码已发送,验证码在有效期内可重复使用!');
        }
        $sendNum    = Cache::tags([CacheEnum::TAG_OTHER])->get('sms.send.' . $phone, 0);
        $sendMinNum = Cache::tags([CacheEnum::TAG_OTHER])->get('sms.send.min.' . $phone, 0);
        $config     = Config::get('sms.setting', [
            'maxPhoneCount'  => 20,
            'maxMinuteCount' => 2,
        ]);
        if ($sendNum > $config['maxPhoneCount']) {
            throw $this->exception('当前手机号今日发送验证码已达上限!');
        }
        if ($sendMinNum > $config['maxMinuteCount']) {
            throw $this->exception('当前手机每分钟发送验证码已达上限!');
        }
        $res = $letter->captcha($phone);
        if ($res) {
            if (Cache::tags([CacheEnum::TAG_OTHER])->has('sms.send.' . $phone)) {
                Cache::increment('sms.send.' . $phone);
            } else {
                $ttl = Carbon::today()->endOfDay()->timestamp - Carbon::today()->timestamp;
                Cache::add('sms.send.' . $phone, 1, $ttl);
            }
            if (Cache::tags([CacheEnum::TAG_OTHER])->has('sms.send.min.' . $phone)) {
                Cache::increment('sms.send.min.' . $phone);
            } else {
                Cache::add('sms.send.min.' . $phone, 1, 60);
            }
            return true;
        }
        return false;
    }

    public function uploadFromFile($file, $option): array
    {
        if ($option['upload_type'] == 1) {
            $option['upload_type'] = sys_config('upload_type', 1) ?? 1;
        }
        try {
            $path   = $this->make_path('attach', 2, true, $option['entId'] ?: 0);
            $upload = UploadService::init($option['upload_type']);
            $res    = $upload->to($path)->validate()->move($file);
            if ($res === false) {
                throw new UploadException($upload->getError());
            }
            $fileInfo = $upload->getUploadInfo();
            $fileType = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
            if ($fileInfo) {
                app()->get(AttachService::class)->create([
                    'name'          => $fileInfo['name'],
                    'real_name'     => $fileInfo['real_name'],
                    'att_dir'       => $fileInfo['att_dir'],
                    'thumb_dir'     => $fileInfo['thumb_dir'],
                    'att_size'      => $fileInfo['att_size'],
                    'att_type'      => $fileInfo['att_type'],
                    'file_ext'      => $fileType,
                    'up_type'       => $fileInfo['file_ext'],
                    'cid'           => $option['pid'] ?: 0,
                    'uid'           => $option['uuid'] ?: '',
                    'way'           => $option['way'] ?: 0,
                    'entid'         => $option['entId'] ?: 0,
                    'relation_type' => $option['link_type'] ?: '',
                    'relation_id'   => $option['link_id'] ?: 0,
                ]);
            }
            return $res->filePath;
        } catch (\Exception $e) {
            throw new UploadException($e->getMessage());
        }
    }

    public function uploadFromResource($file, $option): array
    {
        if (! isset($option['upload_type']) || $option['upload_type'] == 1) {
            $option['upload_type'] = sys_config('upload_type', 1) ?? 1;
        }
        try {
            $mime = 'image/png';
            if (preg_match('/^data:([^;]+);base64,(.*)$/s', $file, $matches)) {
                $mime = $matches[1];
                $file = $matches[2];
            }
            $content = base64_decode($file, true);
            if ($content === false || $content === '') {
                throw new UploadException('无效的文件内容');
            }

            $ext = match ($mime) {
                'image/jpeg', 'image/jpg' => 'jpg',
                'image/gif'               => 'gif',
                'image/webp'              => 'webp',
                default                   => 'png',
            };
            $path   = $this->make_path('attach', 2, true, $option['entId'] ?: 0);
            $upload = UploadService::init($option['upload_type']);
            $res    = $upload->to($path)->stream($content, date('YmdHis') . rand(0, 9999) . '.' . $ext);
            if (! $res) {
                throw new UploadException($upload->getError());
            }
            $fileInfo = $upload->getUploadInfo();
            if ($fileInfo) {
                app()->get(AttachService::class)->create([
                    'name'          => $fileInfo['name'],
                    'real_name'     => $fileInfo['real_name'],
                    'att_dir'       => $fileInfo['dir'],
                    'thumb_dir'     => $fileInfo['dir'],
                    'att_size'      => (int) $fileInfo['size'],
                    'att_type'      => $fileInfo['type'],
                    'file_ext'      => $fileInfo['ext'],
                    'up_type'       => $option['upload_type'],
                    'cid'           => $option['pid'] ?? 0,
                    'uid'           => $option['uuid'] ?? '',
                    'way'           => $option['way'] ?? 0,
                    'entid'         => $option['entId'] ?? 0,
                    'relation_type' => $option['link_type'] ?? '',
                    'relation_id'   => $option['link_id'] ?? 0,
                ]);
            }
            return [
                'name'    => $fileInfo['name'],
                'old_url' => '',
                'new_url' => link_file($fileInfo['dir']),
            ];
        } catch (\Exception $e) {
            throw new UploadException($e->getMessage());
        }
    }

    public function uploadFromUrl(string $url, array $option = []): array
    {
        if (! isset($option['upload_type']) || $option['upload_type'] == 1) {
            $option['upload_type'] = sys_config('upload_type', 1);
        }
        try {
            $client   = new Client(['timeout' => 30]);
            $response = $client->get($url, [
                'allow_redirects' => true,
            ]);
            if ($response->getStatusCode() !== 200) {
                throw new UploadException('远程文件请求失败，链接无效或文件不存在');
            }
            $fileContentType = $response->getHeaderLine('Content-Type');
            $fileName        = basename(parse_url($url, PHP_URL_PATH));
            if (empty($fileName) || ! str_contains($fileName, '.')) {
                $fileExt  = $this->getExtensionFromContentType($fileContentType);
                $fileName = Str::uuid() . '.' . ($fileExt ?: 'bin');
            } else {
                $fileInfo = pathinfo($fileName);
                $fileName = $fileInfo['filename'] . '_' . Str::random(8) . '.' . $fileInfo['extension'];
            }
            $path   = $this->make_path('attach', 2, true, $option['entId'] ?: 0);
            $upload = UploadService::init($option['upload_type']);
            $res    = $upload->to($path)->validate()->stream($response->getBody()->getContents(), $fileName);
            if (! $res) {
                throw new UploadException($upload->getError());
            }
            $fileInfo = $upload->getUploadInfo();
            if ($fileInfo) {
                app()->get(AttachService::class)->create([
                    'name'          => $fileInfo['name'],
                    'real_name'     => $fileInfo['real_name'],
                    'att_dir'       => $fileInfo['dir'],
                    'thumb_dir'     => $fileInfo['dir'],
                    'att_size'      => (int) $fileInfo['size'],
                    'att_type'      => $fileInfo['type'],
                    'file_ext'      => $fileInfo['ext'],
                    'up_type'       => $option['upload_type'],
                    'cid'           => $option['pid'] ?? 0,
                    'uid'           => $option['uuid'] ?? '',
                    'way'           => $option['way'] ?? 0,
                    'entid'         => $option['entId'] ?? 1,
                    'relation_type' => $option['link_type'] ?? '',
                    'relation_id'   => $option['link_id'] ?? 0,
                ]);
            }
            return [
                'name'    => $fileInfo['name'],
                'old_url' => $url,
                'new_url' => link_file($res->filePath),
            ];
        } catch (\Exception $e) {
            throw new UploadException($e->getMessage());
        }
    }

    public function getCityTree(): array
    {
        return app()->get(SystemCityService::class)->cityTree();
    }

    /**
     * 上传路径转化,默认路径.
     * @param mixed $entid
     * @param mixed $path
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
            if (is_dir(public_path('uploads') . $path) || mkdir(public_path('uploads') . $path, 0777, true)) {
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
