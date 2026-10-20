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

namespace Tuoluojiang\Open\services\phpoffice;

use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;

/**
 * 创建ppt
 * Class PptService.
 */
class PptService
{
    /**
     * @var self
     */
    protected static $instance;

    /**
     * 文件保存路径.
     * @var string
     */
    protected $path;

    /**
     * 文件后缀
     * @var string
     */
    protected $suffix;

    /**
     * 文件名称.
     * @var string
     */
    protected $fileName;

    /**
     * @var PhpPresentation
     */
    protected $presentation;

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * 初始化.
     * @return PptService
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @return $this
     */
    public function setPath(string $path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return $this
     */
    public function setSuffix(string $suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * @return $this
     */
    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function save()
    {
        if (mb_detect_encoding($this->fileName) != 'UTF-8') {
            $this->fileName = iconv('utf-8', 'gbk//IGNORE', $this->fileName);
        }
        if (! pathinfo($this->fileName, PATHINFO_EXTENSION)) {
            $this->fileName = $this->fileName . '.' . $this->suffix;
        }
        $dir        = $this->path . '/' . $this->fileName;
        $rootPath   = public_path() . '/' . $dir;
        $oWriterPpt = IOFactory::createWriter($this->presentation);
        $oWriterPpt->save($rootPath);
        return $dir;
    }

    /**
     * 初始化.
     */
    protected function initialize()
    {
        $this->presentation = new PhpPresentation();
        $this->presentation->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_B5ISO);
    }
}
