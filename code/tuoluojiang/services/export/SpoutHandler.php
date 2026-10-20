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

namespace Tuoluojiang\Open\services\export;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Exception\WriterAlreadyOpenedException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Illuminate\Support\Facades\DB;

class SpoutHandler
{
    /**
     * 数据导出.
     * @param string $format 导出格式：csv/xlsx
     * @param string $fileName 文件名（不含后缀）
     * @param array $headings 表头
     * @param \Closure $dataCallback 数据回调（返回单条数据，流式读取）
     * @return string 导出文件路径
     * @throws IOException
     * @throws WriterAlreadyOpenedException
     * @throws WriterNotOpenedException
     */
    public function export(string $format, string $fileName, array $headings, \Closure $dataCallback): string
    {
        ini_set('zend.enable_gc', '1');
        gc_enable();
        gc_collect_cycles();
        $extension = strtolower($format) === 'xlsx' ? 'xlsx' : 'csv';
        $filePath  = public_path("exports/{$fileName}.{$extension}");
        if ($extension === 'xlsx') {
            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->setTempFolder(storage_path('exports')); // XLSX 临时文件目录
        } else {
            $writer = WriterEntityFactory::createCSVWriter();
            $writer->setFieldDelimiter(','); // CSV 分隔符
            $writer->setEnclosure('"');
        }
        $writer->openToFile($filePath);
        $writer->addRow(WriterEntityFactory::createRowFromArray($headings));
        $count    = 0;
        $callback = $dataCallback();
        while ($callback->valid()) {
            $rowData = $callback->current();
            if (! empty($rowData)) {
                $writer->addRow(WriterEntityFactory::createRowFromArray($rowData));
            }
            if ($count % 100 === 0) {
                gc_collect_cycles();
                gc_mem_caches();
                logger()->info("Spout导出：已处理 {$count} 行，内存占用：" . round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB');
            }

            $callback->next();
            ++$count;
            unset($rowData);
        }
        $writer->close();
        unset($writer, $callback);
        gc_collect_cycles();

        return "/exports/{$fileName}.{$extension}";
    }

    /**
     * 数据导入.
     * @param string $filePath 导入文件路径
     * @param \Closure $processCallback 处理单条数据的回调
     * @param int $batchSize 批量入库大小（减少DB写入次数）
     * @param null|\Closure $beforeInsertCallback 批量入库前回调，可过滤当前批次数据
     * @param null|\Closure $afterInsertCallback 批量入库后回调
     * @return array 导入统计：total(总条数)、success(成功条数)、fail(失败条数)
     * @throws ReaderNotOpenedException
     */
    public function import(string $filePath, string $tableName, \Closure $processCallback, int $batchSize = 1000, ?\Closure $beforeInsertCallback = null, ?\Closure $afterInsertCallback = null): array
    {
        gc_enable();
        $stats     = ['total' => 0, 'success' => 0, 'fail' => 0];
        $batchData = [];
        try {
            $reader = ReaderEntityFactory::createReaderFromFile($filePath);
            $reader->open($filePath);
            $isFirstRow = true;
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    if ($isFirstRow) {
                        $isFirstRow = false;
                        continue;
                    }
                    ++$stats['total'];
                    $rowData = $row->toArray();
                    try {
                        $processedData = $processCallback($rowData);
                        if ($processedData) {
                            $batchData[] = $processedData;
                        } else {
                            logger()->error("导入行{$stats['total']}失败：数据处理未通过");
                            ++$stats['fail'];
                            continue;
                        }
                    } catch (\Exception $e) {
                        logger()->error("导入行{$stats['total']}失败：{$e->getMessage()}", [$e->getTraceAsString()]);
                        ++$stats['fail'];
                        continue;
                    }
                    if (count($batchData) >= $batchSize) {
                        $beforeCount = count($batchData);
                        $batchData   = $beforeInsertCallback ? $beforeInsertCallback($batchData) : $batchData;
                        $stats['fail'] += $beforeCount - count($batchData);
                        $stats['success'] += count($batchData);
                        if ($batchData) {
                            DB::table($tableName)->insert($batchData);
                            $afterInsertCallback && $afterInsertCallback($batchData);
                        }
                        $batchData = [];
                        gc_collect_cycles();
                    }
                    unset($row, $rowData);
                }
            }
            if (! empty($batchData)) {
                $beforeCount = count($batchData);
                $batchData   = $beforeInsertCallback ? $beforeInsertCallback($batchData) : $batchData;
                $stats['fail'] += $beforeCount - count($batchData);
                $stats['success'] += count($batchData);
                if ($batchData) {
                    DB::table($tableName)->insert($batchData);
                    $afterInsertCallback && $afterInsertCallback($batchData);
                }
            }
            $reader->close();
            unset($reader, $batchData);
            gc_collect_cycles();
        } catch (IOException|UnsupportedTypeException $e) {
            logger()->error("导入失败：{$e->getMessage()}");
            throw new \RuntimeException("导入失败：{$e->getMessage()}");
        }
        return $stats;
    }
}
