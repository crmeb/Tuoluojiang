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

namespace Tuoluojiang\Open\utils;

/**
 * 签名计算
 * Class fileVerification.
 */
class fileVerification
{
    public string $path = '';

    public string $fileValue = '';

    /**
     * 项目路径.
     * @throws \Exception
     */
    public function getSignature(string $path): string
    {
        if (! is_dir($path) && ! is_file($path)) {
            throw new \Exception($path . ' 不是有效的文件或目录!');
        }

        $appPath = $path . DIRECTORY_SEPARATOR . 'app';
        if (! is_dir($appPath)) {
            throw new \Exception($appPath . ' 不是有效的目录!');
        }

        $frameworkPath = $path . DIRECTORY_SEPARATOR . 'tuoluojiang';
        if (! is_dir($frameworkPath)) {
            throw new \Exception($frameworkPath . ' 不是有效的目录!');
        }

        $this->path = $appPath;
        $this->getFileSignature($appPath);
        $this->path = $frameworkPath;
        $this->getFileSignature($frameworkPath);
        return md5($this->fileValue);
    }

    /**
     * 计算签名.
     * @throws \Exception
     */
    public function getFileSignature(string $path)
    {
        if (! is_dir($path)) {
            $this->fileValue .= @md5_file($path);
        } else {
            if (! $dh = opendir($path)) {
                throw new \Exception($path . ' File open failed!');
            }
            while (($file = readdir($dh)) != false) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $this->getFileSignature($path . DIRECTORY_SEPARATOR . $file);
            }
            closedir($dh);
        }
    }
}
