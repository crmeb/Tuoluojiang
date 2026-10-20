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

use Tuoluojiang\Open\utils\Arr;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * 表格操作
 * Class SheetService.
 */
class SheetService
{
    protected static $instance;

    /**
     * PHPSpreadsheet实例化对象
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /**
     * sheet实例化对象
     * @var Worksheet
     */
    protected $sheet;

    /**
     * @var int
     */
    protected $cells = 0;

    /**
     * 表头计数.
     * @var int
     */
    protected $count = 0;

    /**
     * @var int
     */
    protected $topNumber = 0;

    /**
     * 文件名.
     * @var string
     */
    protected $fileName;

    /**
     * 文件后缀类型.
     * @var string
     */
    protected $suffix;

    /**
     * 行宽.
     * @var int
     */
    protected $width = 20;

    /**
     * 行高.
     * @var int
     */
    protected $height = 50;

    /**
     * 保存文件目录.
     * @var string
     */
    protected $path = 'phpExcel/';

    /**
     * 设置style.
     * @var array
     */
    protected $style = [
        'font' => [
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical'   => Alignment::VERTICAL_CENTER,
        ],
    ];

    /**
     * 默认style.
     * @var array
     */
    protected $defaultStyle = [];

    /**
     * SheetService constructor.
     */
    public function __construct(?string $fileName = null, ?string $path = null)
    {
        if ($fileName) {
            $this->fileName = $fileName;
        }
        if ($path) {
            $this->path = $path;
        }
        $this->defaultStyle = $this->style;
    }

    /**
     * 初始化.
     * @return SheetService
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance              = new self();
            self::$instance->spreadsheet = new Spreadsheet();
        }
        return self::$instance;
    }

    /**
     * @return $this
     */
    public function createOrActive(bool $i = false)
    {
        if ($i) {
            $this->sheet = $this->spreadsheet->createSheet();
        } else {
            $this->sheet = $this->spreadsheet->getActiveSheet();
        }
        return $this;
    }

    /**
     * 设置字体格式.
     * @return false|string
     */
    public function setUtf8(string $title)
    {
        return iconv('utf-8', 'gb2312', $title);
    }

    /**
     * 设置标题.
     * @param null $fileName
     * @return $this
     * @throws Exception
     */
    public function setTitle($fileName = null, string $name = '', array $info = [])
    {
        // 设置参数
        if (is_array($fileName)) {
            $name     = $title['name'] ?? '';
            $fileName = $title['title'] ?? '';
            $info     = $title['info'] ?? '';
        }
        if (empty($fileName)) {
            $fileName = $this->fileName;
        } else {
            $this->fileName = $fileName;
        }
        if (empty($name)) {
            $name = time();
        }
        // 设置Excel属性
        $this->spreadsheet->getProperties()
            ->setTitle($this->setUtf8($fileName))
            ->setSubject($name)
            ->setDescription('')
            ->setKeywords($name)
            ->setCategory('');
        $this->sheet->setTitle($name)
            ->setCellValue('A1', $fileName)
            ->setCellValue('A2', $this->setCellInfo($info));
        // 文字居中
        $this->sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 合并表头单元格
        $this->sheet->mergeCells('A1:' . $this->cells . '1');
        $this->sheet->mergeCells('A2:' . $this->cells . '2');
        $this->sheet->getRowDimension(1)->setRowHeight(40);
        $this->sheet->getRowDimension(2)->setRowHeight(20);

        // 设置表头字体
        $this->sheet->getStyle('A1')->getFont()->setName('黑体');
        $this->sheet->getStyle('A1')->getFont()->setSize(20);
        $this->sheet->getStyle('A1')->getFont()->setBold(true);
        $this->sheet->getStyle('A2')->getFont()->setName('宋体');
        $this->sheet->getStyle('A2')->getFont()->setSize(14);
        $this->sheet->getStyle('A2')->getFont()->setBold(true);
        $this->sheet->getStyle('A3:' . $this->cells . '3')->getFont()->setBold(true);

        return $this;
    }

    /**
     * 设置头部信息.
     * @return $this
     */
    public function setHeader(array $data)
    {
        $span = 'A';
        foreach ($data as $key => $value) {
            $this->sheet->getColumnDimension($span)->setWidth($this->width);
            $this->sheet->setCellValue($span . $this->topNumber, $value);
            ++$span;
        }
        $this->sheet->getRowDimension(3)->setRowHeight($this->height);
        $this->cells = $span;
        return $this;
    }

    /**
     * 设置表格导出内容.
     * @param array $data 需要导出的数据
     * @return $this
     * @throws Exception
     */
    public function setContent(array $data = [])
    {
        if (! empty($data) && is_array($data)) {
            $column = $this->topNumber + 1;
            // 行写入
            foreach ($data as $key => $rows) {
                $span = 'A';
                // 列写入
                foreach ($rows as $keyName => $value) {
                    $this->sheet->setCellValue($span . $column, $value);
                    ++$span;
                }
                ++$column;
            }
            $this->sheet->getDefaultRowDimension()->setRowHeight($this->height);
            // 设置内容字体样式
            $this->sheet->getStyle('A1:' . $span . $column)->applyFromArray($this->style);
            // 设置边框
            $this->sheet->getStyle('A1:' . $span . $column)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            // 设置自动换行
            $this->sheet->getStyle('A4:' . $span . $column)->getAlignment()->setWrapText(true);
        }
        return $this;
    }

    /**
     * 设置文件名称.
     * @return $this
     */
    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * 设置文件保存位置.
     * @return $this
     */
    public function setPath(string $path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * 设置文件后缀
     * @return $this
     */
    public function setSuffix(string $suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * 保存表格数据.
     * @param null|string $fileName 文件名称
     * @param string $suffix 文件后缀名
     * @param string $path 文件保存路径
     * @return string
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function save(?string $fileName = null, string $suffix = 'xlsx', string $path = '')
    {
        if (empty($fileName)) {
            $fileName = $this->fileName;
        }
        if (empty($fileName)) {
            $fileName = date('YmdHis') . time();
        }
        if (empty($suffix)) {
            $suffix = $this->suffix;
        }
        if (empty($suffix)) {
            $suffix = 'xlsx';
        }
        // 重命名表（UTF8编码不需要这一步）
        if (mb_detect_encoding($fileName) != 'UTF-8') {
            $fileName = iconv('utf-8', 'gbk//IGNORE', $fileName);
        }
        $savePath = $this->path . $path;
        $rootPath = public_path() . '/' . $savePath;
        if (! is_dir($rootPath)) {
            mkdir($rootPath, 0700, true);
        }
        if (! pathinfo($fileName, PATHINFO_EXTENSION)) {
            $fileName = $fileName . '.' . $suffix;
        }

        $filepath = $rootPath . '/' . $fileName;

        if (! $this->spreadsheet) {
            $this->spreadsheet = new Spreadsheet();
        }

        $writer = new Xlsx($this->spreadsheet);
        $writer->save($rootPath . '/' . $fileName);
        $this->reset();
        return $filepath;
    }

    /**
     * 读取表格内的文件数据.
     * @return array
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function getExcelData(string $filePath, array $cellsData = [], ?callable $closure = null, int $startLine = 2)
    {
        if (! file_exists($filePath)) {
            throw new \RuntimeException('文件不存在');
        }
        $extension   = ucwords(pathinfo($filePath, PATHINFO_EXTENSION));
        $io          = IOFactory::createreader($extension);
        $spreadsheet = $io->load($filePath);
        $worksheet   = $spreadsheet->getActiveSheet();
        $highestRow  = $worksheet->getHighestRow();
        $data        = [];

        if ($closure) {
            $closure($worksheet);
        }

        for ($j = $startLine; $j <= (int) $highestRow; ++$j) {
            $value = [];
            foreach ($cellsData as $key => $val) {
                if ($v = $worksheet->getCell($val . $j)->getValue()) {
                    $value[$key] = $v;
                }
            }
            if ($value) {
                $data[] = $value;
            }
        }
        return Arr::filterValue($data);
    }

    /**
     *  创建保存excel目录
     *  return string.
     */
    protected function mkdirPath()
    {
        if (! is_dir($this->path)) {
            if (mkdir($this->path, 0700) == false) {
                throw new \RuntimeException('创建目录失败');
            }
        }
        // 年月一级目录
        $montPath = $this->path . date('Ym');
        if (! is_dir($montPath)) {
            if (mkdir($montPath, 0700) == false) {
                throw new \RuntimeException('创建目录失败');
            }
        }
        // 日二级目录
        $dayPath = $montPath . '/' . date('d');
        if (! is_dir($dayPath)) {
            if (mkdir($dayPath, 0700) == false) {
                throw new \RuntimeException('创建目录失败');
            }
        }
        return $this;
    }

    /**
     * 重置.
     */
    protected function reset()
    {
        $this->spreadsheet = null;
        $this->fileName    = null;
        $this->cells       = 0;
        $this->height      = 50;
        $this->width       = 20;
        $this->sheet       = null;
        $this->count       = 0;
        $this->topNumber   = 0;
        $this->style       = $this->defaultStyle;
    }

    /**
     * 设置第二行标题内容.
     * @param $info array (['name'=>'','site'=>'','phone'=>123] || ['我是表名','我是地址','我是手机号码'] ) || string 自定义
     * @return string
     */
    private function setCellInfo($info)
    {
        $content = ['操作者：', '导出日期：' . date('Y-m-d', time()), '地址：', '电话：'];
        if (is_array($info) && ! empty($info)) {
            if (isset($info['name'])) {
                $content[0] .= $info['name'];
            } else {
                $content[0] .= isset($info[0]) ? $info[0] : '';
            }
            if (isset($info['site'])) {
                $content[2] .= $info['site'];
            } else {
                $content[2] .= isset($info[1]) ? $info[1] : '';
            }
            if (isset($info['phone'])) {
                $content[3] .= $info['phone'];
            } else {
                $content[3] .= isset($info[2]) ? $info[2] : '';
            }
            return implode(' ', $content);
        }
        if (is_string($info)) {
            return empty($info) ? implode(' ', $content) : $info;
        }
    }
}
