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

use Fastknife\Exception\ParamException;
use Fastknife\Service\BlockPuzzleCaptchaService;
use Fastknife\Service\ClickWordCaptchaService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

class CaptchaService
{
    protected string $backgroundImg;

    protected string $coverImg;

    protected float $widthInit;

    protected float $heightInit;

    protected float $widthLimit;

    protected float $heightLimit;

    /**
     * @return $this
     */
    public function setImageUrl(string $bgImg = '', string $coverImg = ''): static
    {
        $this->backgroundImg = $bgImg ?: public_path('static/captcha/bgimg0' . rand(1, 4) . '.jpeg');
        $this->coverImg      = $coverImg ?: public_path('static/captcha/cover.png');
        $this->getCoordinate();

        return $this;
    }

    /**
     * 获取图片信息.
     */
    public function getImageInfo(): array
    {
        // 遮盖层
        [$width_z, $height_z] = getimagesize($this->coverImg);
        $cover                = imagecreatefrompng($this->coverImg);
        // 创建一个和遮盖层同样大小的图片
        $img = imagecreatetruecolor($width_z, $height_z);
        imagesavealpha($img, true);
        $bg = imagecolorallocatealpha($img, 255, 0, 0, 127);
        imagefill($img, 0, 0, $bg);
        $background = imagecreatefromstring(file_get_contents($this->backgroundImg));
        for ($i = $this->widthInit; $i < $this->widthLimit; ++$i) {
            for ($j = $this->heightInit; $j < $this->heightLimit; ++$j) {
                $color2 = imagecolorat($background, $i, $j);
                // 判断索引值区分具体的遮盖区域
                if (imagecolorat($cover, $i - $this->widthInit, $j - $this->heightInit) == 0) {
                    imagesetpixel($img, $i - $this->widthInit, $j - $this->heightInit, $color2);
                }
                $color1 = imagecolorat($cover, $i - $this->widthInit, $j - $this->heightInit);
                $s      = imagecolorallocatealpha($background, 192, 192, 192, 45);
                if ($color1 == 0) {
                    imagesetpixel($background, $i, $j, $s);
                }
            }
        }
        ob_start();
        // 生成背景图
        imagepng($background);
        $bgImg = ob_get_contents();
        ob_end_clean();
        ob_start();
        // 生成滑块图
        imagepng($img);
        $coverImg = ob_get_contents();
        ob_end_clean();
        $key = password_hash(uniqid(more_entropy: true), PASSWORD_BCRYPT);
        Cache::tags(['slider_captcha'])->add($key, json_encode([$this->widthLimit, $this->heightLimit]), 180);

        return [
            'background' => $this->getImage($bgImg),
            'cover'      => $this->getImage($coverImg),
            'height'     => $this->heightLimit,
            'key'        => $key,
        ];
    }

    /**
     * @return string
     */
    public function getImage($img)
    {
        $mime = 'image/png';

        return sprintf(
            'data:%s;base64,%s',
            $mime,
            base64_encode($img)
        );
    }

    public function getCaptchaService()
    {
        $captchaType = request()->post('captchaType', 'blockPuzzle');
        $config      = config('captcha');
        return match ($captchaType) {
            'clickWord'   => new ClickWordCaptchaService($config),
            'blockPuzzle' => new BlockPuzzleCaptchaService($config),
            default       => throw new ParamException('captchaType参数不正确！'),
        };
    }

    public function validate(): array
    {
        return Request::instance()->validate([
            'token'     => 'bail|required',
            'pointJson' => 'required',
        ]);
    }

    protected function getCoordinate(): void
    {
        // 遮盖层
        [$width_z, $height_z] = getimagesize($this->coverImg);
        // 背景层
        [$width_t, $height_t] = getimagesize($this->backgroundImg);
        $width_max            = $width_t - $width_z - 10;
        $height_max           = $height_t - $height_z - 10;
        $this->widthInit      = $width_ini = rand($width_z + 10, $width_max);
        $this->heightInit     = $height_ini = rand(10, $height_max);
        $this->widthLimit     = $width_ini + $width_z;
        $this->heightLimit    = $height_ini + $height_z;
    }
}
