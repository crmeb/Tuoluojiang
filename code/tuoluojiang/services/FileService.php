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

/**
 * 系统配置
 * Class FileService.
 */
class FileService
{
    /**
     * 替换相应的字符.
     * @param string $path 路径
     */
    public function dirReplace(string $path): string
    {
        return str_replace('//', '/', str_replace('\\', '/', $path));
    }

    /**
     * 文件保存路径处理.
     * @param mixed $path
     */
    public function checkPath($path): string
    {
        return (preg_match('/\/$/', $path)) ? $path : $path . '/';
    }

    /**
     * 删除文件.
     */
    public function unlinkFile(string $path): bool
    {
        $path = $this->dirReplace($path);
        if (file_exists($path)) {
            return unlink($path);
        }
        return true;
    }

    /**
     * 创建多级目录.
     */
    public function createDir(string $dir, int $mode = 0777): bool
    {
        return is_dir($dir) or ($this->createDir(dirname($dir)) and mkdir($dir, $mode));
    }

    /**
     * 文件操作(复制/移动).
     * @param string $old_path 指定要操作文件路径(需要含有文件名和后缀名)
     * @param string $new_path 指定新文件路径（需要新的文件名和后缀名）
     * @param string $type 文件操作类型
     * @param bool $overWrite 是否覆盖已存在文件
     * @param array $ignore 按后缀名过滤
     * @return bool
     */
    public function handleFile(string $old_path, string $new_path, string $type = 'copy', bool $overWrite = false, array $ignore = [])
    {
        $old_path = $this->dirReplace($old_path);
        $new_path = $this->dirReplace($new_path);
        if (file_exists($new_path) && $overWrite = false) {
            return false;
        }
        if (file_exists($new_path) && $overWrite = true) {
            $this->unlinkFile($new_path);
        }

        $extension = pathinfo($old_path, PATHINFO_EXTENSION);
        if ($ignore && $extension && in_array($extension, $ignore)) {
            return true;
        }

        $aimDir = dirname($new_path);
        $this->createDir($aimDir);
        switch ($type) {
            case 'copy':
                return copy($old_path, $new_path);
            case 'move':
                return @rename($old_path, $new_path);
        }
    }

    /**
     * 文件夹操作(复制/移动).
     * @param string $old_path 指定要操作文件夹路径
     * @param string $type 操作类型
     * @param bool $overWrite 是否覆盖文件和文件夹
     * @param array $ignore 按目录名过滤
     */
    public function handleDir(string $old_path, string $new_path, string $type = 'copy', bool $overWrite = false, array $ignore = []): bool
    {
        $new_path = $this->checkPath($new_path);
        $old_path = $this->checkPath($old_path);
        if (! is_dir($old_path)) {
            return false;
        }

        if (! file_exists($new_path)) {
            $this->createDir($new_path);
        }

        $dirHandle = opendir($old_path);

        if (! $dirHandle) {
            return false;
        }

        $boolean = true;

        while (false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            if (! is_dir($old_path . $file)) {
                $boolean = $this->handleFile($old_path . $file, $new_path . $file, $type, $overWrite);
            } else {
                if ($ignore && in_array($file, $ignore)) {
                    break;
                }
                $this->handleDir($old_path . $file, $new_path . $file, $type, $overWrite);
            }
        }
        switch ($type) {
            case 'copy':
                closedir($dirHandle);
                return $boolean;
            case 'move':
                closedir($dirHandle);
                return @rmdir($old_path);
        }
        return $boolean;
    }

    /**
     * 列出目录.
     * @param string $dir 目录名
     * @return array 列出文件夹下内容，返回数组 $dirArray['dir']:存文件夹；$dirArray['file']：存文件
     */
    public function getDirs(string $dir): array
    {
        $dir          = rtrim($dir, '/') . '/';
        $dirArray[][] = null;
        if (false != ($handle = opendir($dir))) {
            $i = 0;
            $j = 0;
            while (false !== ($file = readdir($handle))) {
                if (is_dir($dir . $file)) { // 判断是否文件夹
                    $dirArray['dir'][$i] = $file;
                    ++$i;
                } else {
                    $dirArray['file'][$j] = $file;
                    ++$j;
                }
            }
            closedir($handle);
        }
        return $dirArray;
    }

    /**
     * 压缩文件夹及文件.
     * @param string $source 需要压缩的文件夹/文件路径
     * @param string $destination 压缩后的保存地址
     * @param string $folder 文件夹前缀，保存时需要去掉的父级文件夹
     */
    public function addZip(string $source, string $destination, string $folder = ''): bool
    {
        if (! extension_loaded('zip') || ! file_exists($source)) {
            return false;
        }

        $zip = new \ZipArchive();
        if (! $zip->open($destination, $zip::CREATE)) {
            return false;
        }
        $source = str_replace('\\', '/', $source);
        $folder = str_replace('\\', '/', $folder);
        if (is_dir($source) === true) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $file) {
                $file = str_replace('\\', '/', $file);
                if (in_array(substr($file, strrpos($file, '/') + 1), ['.', '..'])) {
                    continue;
                }
                if (is_dir($file) === true) {
                    $zip->addEmptyDir(str_replace($folder . '/', '', $file . '/'));
                } elseif (is_file($file) === true) {
                    $zip->addFromString(str_replace($folder . '/', '', $file), file_get_contents($file));
                }
            }
        } elseif (is_file($source) === true) {
            $zip->addFromString(basename($source), file_get_contents($source));
        }
        return $zip->close();
    }

    /**
     * 解压缩文件夹及文件.
     * @param string $source 需要解压缩的文件路径
     * @param string $folder 文件夹前缀，保存时需要去掉的父级文件夹
     */
    public function extractFile(string $source, string $folder): bool
    {
        if (! extension_loaded('zip') || ! file_exists($source)) {
            return false;
        }

        $zip = new \ZipArchive();
        $zip->open($source);
        return $zip->extractTo($folder);
    }

    /**
     * 删除目录.
     * @param mixed $dirName
     */
    public function delDir($dirName): bool
    {
        if (! file_exists($dirName)) {
            return false;
        }

        $dir = opendir($dirName);
        while ($fileName = readdir($dir)) {
            $file = $dirName . '/' . $fileName;
            if ($fileName != '.' && $fileName != '..') {
                if (is_dir($file)) {
                    $this->delDir($file);
                } else {
                    if (! @unlink($file)) {
                        return false;
                    }
                }
            }
        }
        closedir($dir);
        return rmdir($dirName);
    }
}
