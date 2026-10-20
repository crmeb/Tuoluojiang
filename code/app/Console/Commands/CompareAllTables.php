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

namespace App\Console\Commands;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;

/**
 * 表结构对比.
 */
class CompareAllTables extends Command
{
    /**
     * 命令名称和签名.
     *
     * @var string
     */
    protected $signature = 'tables:compare-all
                            {--source=source : 源数据库连接名}
                            {--target=target : 目标数据库连接名}
                            {--exclude= : 要排除的表，用逗号分隔}';

    /**
     * 命令描述.
     *
     * @var string
     */
    protected $description = '对比两个数据库中所有表的结构并生成迁移文件';

    /**
     * 源数据库连接.
     *
     * @var Connection
     */
    protected $sourceConnection;

    /**
     * 目标数据库连接.
     *
     * @var Connection
     */
    protected $targetConnection;

    /**
     * 要排除的表.
     *
     * @var array
     */
    protected $excludedTables = [];

    /**
     * @var Application|mixed|Repository
     */
    private mixed $sourcePrefix;

    /**
     * @var Application|mixed|Repository
     */
    private mixed $targetPrefix;

    /**
     * 执行命令.
     *
     * @return int
     */
    public function handle()
    {
        $this->initializeConnections();

        // 获取所有表名
        $sourceTables = $this->getSourceTables();
        $targetTables = $this->getTargetTables();

        // 确定需要处理的表
        $allTables            = array_unique(array_merge($sourceTables, $targetTables));
        $this->excludedTables = $this->getExcludedTables();
        $tablesToProcess      = array_filter($allTables, function ($table) {
            return ! in_array($table, $this->excludedTables);
        });

        $this->info('开始对比 ' . count($tablesToProcess) . ' 个表...');
        $bar = $this->output->createProgressBar(count($tablesToProcess));
        $bar->start();

        foreach ($tablesToProcess as $table) {
            try {
                // 检查两个库中表是否存在
                $sourceExists = $this->tableExists($this->sourceConnection, $table, $this->sourcePrefix);
                $targetExists = $this->tableExists($this->targetConnection, $table, $this->targetPrefix);

                // 两个库都不存在的表，直接跳过
                if (! $sourceExists && ! $targetExists) {
                    $this->line("\n表 {$table} 在两个数据库中都不存在，已跳过");
                    $bar->advance();
                    continue;
                }
                // 源库存在，目标库不存在 - 生成创建表迁移
                if ($sourceExists && ! $targetExists) {
                    $this->generateCreateTableMigration($table);
                }
                // 源库不存在，目标库存在 - 生成删除表迁移
                elseif (! $sourceExists && $targetExists) {
                    //                    $this->generateDropTableMigration($table);
                    continue;
                }
                // 两个库都存在 - 对比结构差异
                else {
                    $sourceSchema = $this->getTableSchema($this->sourceConnection, $table, $this->sourcePrefix);
                    $targetSchema = $this->getTableSchema($this->targetConnection, $table, $this->targetPrefix);

                    $differences = $this->compareSchemas($sourceSchema, $targetSchema);

                    if ($this->hasDifferences($differences)) {
                        $this->generateAlterTableMigration($table, $differences);
                    }
                }
            } catch (\Exception $e) {
                $this->error("\n处理表 {$table} 时出错: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n表结构对比完成！");
        return 0;
    }

    /**
     * 检查指定数据库连接中表是否存在.
     */
    protected function tableExists(Connection $connection, string $table, string $prefix): bool
    {
        // 检查时需要使用带前缀的原始表名
        $tableWithPrefix = $prefix . $table;
        return $connection->createSchemaManager()->tablesExist($tableWithPrefix);
    }

    /**
     * 初始化数据库连接.
     */
    protected function initializeConnections()
    {
        $sourceConnectionName = $this->option('source');
        $targetConnectionName = $this->option('target');
        // 获取源数据库和目标数据库的表前缀
        $this->sourcePrefix = config("database.connections.{$sourceConnectionName}.prefix", '');
        $this->targetPrefix = config("database.connections.{$targetConnectionName}.prefix", '');

        $this->sourceConnection = DB::connection($sourceConnectionName)->getDoctrineConnection();
        $this->targetConnection = DB::connection($targetConnectionName)->getDoctrineConnection();
        // 注册 enum 类型映射，将其视为 string 处理
        $this->sourceConnection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        $this->targetConnection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * 获取源数据库中的所有表.
     *
     * @return array
     */
    protected function getSourceTables()
    {
        $tables = $this->sourceConnection->createSchemaManager()->listTableNames();
        return $this->removePrefixes($tables, $this->sourcePrefix);
    }

    /**
     * 获取目标数据库中的所有表.
     *
     * @return array
     */
    protected function getTargetTables()
    {
        $tables = $this->targetConnection->createSchemaManager()->listTableNames();
        return $this->removePrefixes($tables, $this->targetPrefix);
    }

    /**
     * 获取要排除的表.
     *
     * @return array
     */
    protected function getExcludedTables()
    {
        $exclude = $this->option('exclude');
        if (empty($exclude)) {
            return [];
        }

        return explode(',', $exclude);
    }

    /**
     * 获取指定表的结构信息（已确保表存在）.
     */
    protected function getTableSchema(Connection $connection, string $table, string $prefix): array
    {
        $schemaManager = $connection->createSchemaManager();
        // 获取结构时需要使用带前缀的原始表名
        $tableWithPrefix = $prefix . $table;
        $tableDetails    = $schemaManager->introspectTable($tableWithPrefix);

        // 提取字段信息
        $columns = [];
        foreach ($tableDetails->getColumns() as $column) {
            $columnType = $column->getType()->getName();
            $props      = [
                'type'           => $columnType,
                'length'         => $column->getLength(),
                'precision'      => $column->getPrecision(),
                'scale'          => $column->getScale(),
                'nullable'       => ! $column->getNotnull(),
                'default'        => $column->getDefault(),
                'auto_increment' => $column->getAutoincrement(),
                'comment'        => $column->getComment() ?: '',
            ];

            // 处理 enum 类型的可选值
            if ($columnType === 'enum') {
                $options                 = $column->toArray();
                $props['allowed_values'] = $options['length']['fixed'] ?? [];
            }

            $columns[$column->getName()] = $props;
        }

        // 提取索引信息
        $indexes = [];
        foreach ($schemaManager->listTableIndexes($table) as $index) {
            $indexes[$index->getName()] = [
                'columns' => $index->getColumns(),
                'unique'  => $index->isUnique(),
                'primary' => $index->isPrimary(),
            ];
        }

        // 提取外键信息
        $foreignKeys = [];
        foreach ($schemaManager->listTableForeignKeys($table) as $foreignKey) {
            /* @var ForeignKeyConstraint $foreignKey */
            $foreignKeys[$foreignKey->getName()] = [
                'local_columns'   => $foreignKey->getLocalColumns(),
                'foreign_table'   => $foreignKey->getForeignTableName(),
                'foreign_columns' => $foreignKey->getForeignColumns(),
                'on_delete'       => $foreignKey->getOnDelete(),
                'on_update'       => $foreignKey->getOnUpdate(),
            ];
        }

        $tableOptions = $tableDetails->getOptions();

        return [
            'columns'      => $columns,
            'indexes'      => $indexes,
            'foreign_keys' => $foreignKeys,
            'engine'       => $tableOptions['engine'] ?? null,
            'comment'      => $tableDetails->getComment() ?: '',
        ];
    }

    /**
     * 对比两个表结构的差异
     */
    protected function compareSchemas(array $source, array $target): array
    {
        $diff = [
            'add_columns'       => [],
            'drop_columns'      => [],
            'modify_columns'    => [],
            'add_indexes'       => [],
            'drop_indexes'      => [],
            'add_foreign_keys'  => [],
            'drop_foreign_keys' => [],
            'modify_table'      => [],
        ];

        // 对比字段
        $sourceColumns = $source['columns'];
        $targetColumns = $target['columns'];

        $diff['add_columns']  = array_diff_key($sourceColumns, $targetColumns);
        $diff['drop_columns'] = array_diff_key($targetColumns, $sourceColumns);

        // 修改字段比较逻辑
        foreach (array_intersect_key($sourceColumns, $targetColumns) as $col => $props) {
            // 对id主键进行特殊处理，忽略类型差异
            if ($col === 'id' && $props['auto_increment']) {
                // 复制属性进行比较，排除类型差异
                $sourceCopy = $props;
                $targetCopy = $targetColumns[$col];

                // 移除类型比较（因为我们强制使用id()方法，对应bigint）
                unset($sourceCopy['type'], $targetCopy['type'], $sourceCopy['length'], $targetCopy['length']);

                // 移除长度比较（id()方法不需要指定长度）

                if ($sourceCopy !== $targetCopy) {
                    $diff['modify_columns'][$col] = [
                        'from' => $targetColumns[$col],
                        'to'   => $props,
                    ];
                }
            } else {
                if ($props !== $targetColumns[$col]) {
                    $diff['modify_columns'][$col] = [
                        'from' => $targetColumns[$col],
                        'to'   => $props,
                    ];
                }
            }
        }

        // 对比索引（修改部分）
        $sourceIndexes = $source['indexes'];
        $targetIndexes = $target['indexes'];

        // 过滤掉id主键索引，避免不必要的差异检测
        $filteredSourceIndexes = array_filter($sourceIndexes, function ($index, $name) {
            return ! ($index['primary'] && $index['columns'] === ['id']);
        }, ARRAY_FILTER_USE_BOTH);

        $filteredTargetIndexes = array_filter($targetIndexes, function ($index, $name) {
            return ! ($index['primary'] && $index['columns'] === ['id']);
        }, ARRAY_FILTER_USE_BOTH);

        $diff['add_indexes']  = array_diff_key($filteredSourceIndexes, $filteredTargetIndexes);
        $diff['drop_indexes'] = array_diff_key($filteredTargetIndexes, $filteredSourceIndexes);
        // 对比外键
        $sourceForeignKeys = $source['foreign_keys'];
        $targetForeignKeys = $target['foreign_keys'];

        $diff['add_foreign_keys']  = array_diff_key($sourceForeignKeys, $targetForeignKeys);
        $diff['drop_foreign_keys'] = array_diff_key($targetForeignKeys, $sourceForeignKeys);

        // 对比表引擎
        if ($source['engine'] !== $target['engine']) {
            $diff['modify_table']['engine'] = [
                'from' => $target['engine'],
                'to'   => $source['engine'],
            ];
        }
        // 对比表注释
        //        if ($source['comment'] !== $target['comment']) {
        //            $diff['modify_table']['comment'] = [
        //                'from' => $target['comment'],
        //                'to'   => $source['comment'],
        //            ];
        //        }
        return $diff;
    }

    /**
     * 检查是否有差异
     */
    protected function hasDifferences(array $differences): bool
    {
        foreach ($differences as $key => $value) {
            if (! empty($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 生成创建新表的迁移文件.
     */
    protected function generateCreateTableMigration(string $table)
    {
        $schema        = $this->getTableSchema($this->sourceConnection, $table, $this->sourcePrefix);
        $migrationName = date('Y_m_d_His') . '_create_' . $table . '_table.php';
        $path          = database_path('migrations/' . $migrationName);

        // 构建up()方法代码
        $upCode = $this->buildCreateTableUpCode($table, $schema);
        // 构建down()方法代码
        $downCode = $this->buildCreateTableDownCode($table);

        $content = $this->getMigrationTemplate($upCode, $downCode);

        file_put_contents($path, $content);
        $this->line("\n已生成创建表迁移: {$migrationName}");
    }

    /**
     * 生成删除表的迁移文件.
     */
    protected function generateDropTableMigration(string $table)
    {
        $migrationName = date('Y_m_d_His') . '_drop_' . $table . '_table.php';
        $path          = database_path('migrations/' . $migrationName);

        // 构建up()方法代码 - 删除表
        $upCode = "Schema::dropIfExists('{$table}');";

        // 构建down()方法代码 - 恢复表（需要从源数据库获取表结构）
        $schema   = $this->getTableSchema($this->sourceConnection, $table, $this->sourcePrefix);
        $downCode = $this->buildCreateTableUpCode($table, $schema);

        $content = $this->getMigrationTemplate($upCode, $downCode);

        file_put_contents($path, $content);
        $this->line("\n已生成删除表迁移: {$migrationName}");
    }

    /**
     * 生成修改表结构的迁移文件.
     */
    protected function generateAlterTableMigration(string $table, array $differences)
    {
        $migrationName = date('Y_m_d_His') . '_alter_' . $table . '_table.php';
        $path          = database_path('migrations/' . $migrationName);

        // 构建up()方法代码
        $upCode = $this->buildAlterTableUpCode($table, $differences);
        // 构建down()方法代码（与up相反）
        $downCode = $this->buildAlterTableDownCode($table, $differences);

        $content = $this->getMigrationTemplate($upCode, $downCode);

        file_put_contents($path, $content);
        $this->line("\n已生成修改表迁移: {$migrationName}");
    }

    /**
     * 构建创建表的up()方法代码
     */
    protected function buildCreateTableUpCode(string $table, array $schema): string
    {
        $code = "Schema::create('{$table}', function (Blueprint \$table) {\n";

        // 标记是否已经处理主键
        $primaryKeyHandled = false;
        $primaryKeyName    = '';

        // 先找出主键索引信息
        foreach ($schema['indexes'] as $name => $props) {
            if ($props['primary'] && $props['columns'] === ['id']) {
                $primaryKeyName = $name;
                break;
            }
        }
        // 添加字段
        foreach ($schema['columns'] as $col => $props) {
            // 处理主键字段：只要是名为id的自增主键，强制使用$table->id()
            $hasPrimaryIndex = true;
            if ($col === 'id' && $props['auto_increment'] && ! empty($schema['indexes'])) {
                $hasPrimaryIndex = collect($schema['indexes'])->first(function ($index) use ($col) {
                    return $index['primary'] && $index['columns'] === [$col];
                });

                if ($hasPrimaryIndex) {
                    $columnCode = '    $table->id()';

                    if (! empty($props['comment'])) {
                        $columnCode .= "->comment('{$props['comment']}')";
                    } else {
                        $columnCode .= "->comment('主键ID')";
                    }
                    $columnCode .= ";\n";
                    $code .= $columnCode;
                    $primaryKeyHandled = true;
                    continue;
                }
            }
            $type = $this->mapDbTypeToLaravel($props['type']);
            // 处理需要长度参数的类型
            if ($type === 'decimal' && $props['precision'] && $props['scale']) {
                // decimal类型: $table->decimal('col', precision, scale)
                $columnCode = "    \$table->{$type}('{$col}', {$props['precision']}, {$props['scale']})";
            }
            // 处理string和char类型的长度参数
            elseif (in_array($type, ['string', 'char']) && $props['length']) {
                // string/char类型: $table->string('col', length)
                $columnCode = "    \$table->{$type}('{$col}', {$props['length']})";
            }
            // 其他普通类型
            else {
                $columnCode = "    \$table->{$type}('{$col}')";
            }

            // 添加可空
            if ($props['nullable']) {
                $columnCode .= '->nullable()';
            }

            // 添加默认值
            if ($props['default'] !== null) {
                $default = is_string($props['default']) ? "'{$props['default']}'" : $props['default'];
                $columnCode .= "->default({$default})";
            }
            // 避免为主键重复添加autoIncrement
            if ($props['auto_increment'] && ! ($col === 'id' && $hasPrimaryIndex)) {
                $columnCode .= '->autoIncrement()';
            }

            // 添加注释
            if (! empty($props['comment'])) {
                $columnCode .= "->comment('{$props['comment']}')";
            }

            $columnCode .= ";\n";
            $code .= $columnCode;
        }
        // 添加索引
        foreach ($schema['indexes'] as $name => $props) {
            // 跳过主键索引：
            // 1. 如果是id字段的主键索引，且已通过$table->id()处理
            // 2. 或者是复合主键但已处理
            if ($props['primary']) {
                // 单字段主键且是id，并且已处理
                if ($props['columns'] === ['id'] && $primaryKeyHandled) {
                    continue;
                }
                // 复合主键但包含id且已处理（特殊情况）
                if (in_array('id', $props['columns']) && $primaryKeyHandled) {
                    continue;
                }
            }
            // 生成其他索引
            if ($props['primary']) {
                $code .= "    \$table->primary(['" . implode("', '", $props['columns']) . "'], '{$name}');\n";
            } elseif ($props['unique']) {
                $code .= "    \$table->unique(['" . implode("', '", $props['columns']) . "'], '{$name}');\n";
            } else {
                $code .= "    \$table->index(['" . implode("', '", $props['columns']) . "'], '{$name}');\n";
            }
        }
        // 添加外键
        foreach ($schema['foreign_keys'] as $name => $props) {
            $code .= "    \$table->foreign(['" . implode("', '", $props['local_columns']) . "'], '{$name}')\n";
            $code .= "        ->references(['" . implode("', '", $props['foreign_columns']) . "'])->on('{$props['foreign_table']}')\n";
            if ($props['on_delete']) {
                $code .= "        ->onDelete('{$props['on_delete']}')\n";
            }
            if ($props['on_update']) {
                $code .= "        ->onUpdate('{$props['on_update']}')\n";
            }
            $code .= "    ;\n";
        }
        // 设置表引擎
        if (! empty($schema['engine'])) {
            $code .= "    \$table->engine = '{$schema['engine']}';\n";
        }
        // 设置表注释
        if (! empty($schema['comment'])) {
            $code .= "    \$table->comment('{$schema['comment']}');\n";
        }
        $code .= '});';
        return $code;
    }

    /**
     * 构建创建表的down()方法代码
     */
    protected function buildCreateTableDownCode(string $table): string
    {
        return "Schema::dropIfExists('{$table}');";
    }

    /**
     * 构建修改表的up()方法代码
     */
    protected function buildAlterTableUpCode(string $table, array $differences): string
    {
        $code = "Schema::table('{$table}', function (Blueprint \$table) {\n";

        // 先删除外键，避免修改字段时冲突
        foreach ($differences['drop_foreign_keys'] as $name => $props) {
            $code .= "    \$table->dropForeign('{$name}');\n";
        }
        // 删除索引
        foreach ($differences['drop_indexes'] as $name => $props) {
            // 跳过主键索引删除（id()方法自动处理）
            if ($props['primary'] && $props['columns'] === ['id']) {
                continue;
            }

            if ($props['primary']) {
                $code .= "    \$table->dropPrimary('{$name}');\n";
            } else {
                $code .= "    \$table->dropIndex('{$name}');\n";
            }
        }

        // 删除字段
        if (! empty($differences['drop_columns'])) {
            $cols = implode("', '", array_keys($differences['drop_columns']));
            $code .= "    \$table->dropColumn(['{$cols}']);\n";
        }

        // 新增字段
        foreach ($differences['add_columns'] as $col => $props) {
            // 主键字段特殊处理
            if ($col === 'id' && $props['auto_increment']) {
                $columnCode = '    $table->id()';
                if (! empty($props['comment'])) {
                    $columnCode .= "->comment('{$props['comment']}')";
                } else {
                    $columnCode .= "->comment('主键ID')";
                }
                $columnCode .= ";\n";
                $code .= $columnCode;
                continue;
            }
            $type       = $this->mapDbTypeToLaravel($props['type']);
            $columnCode = '';

            // 处理需要长度参数的类型
            if ($type === 'decimal' && $props['precision'] && $props['scale']) {
                $columnCode = "    \$table->{$type}('{$col}', {$props['precision']}, {$props['scale']})";
            } elseif (in_array($type, ['string', 'char']) && $props['length']) {
                $columnCode = "    \$table->{$type}('{$col}', {$props['length']})";
            } else {
                $columnCode = "    \$table->{$type}('{$col}')";
            }

            if ($props['nullable']) {
                $columnCode .= '->nullable()';
            }

            if ($props['default'] !== null) {
                $default = is_string($props['default']) ? "'{$props['default']}'" : $props['default'];
                $columnCode .= "->default({$default})";
            }

            if ($props['auto_increment']) {
                $columnCode .= '->autoIncrement()';
            }

            if (! empty($props['comment'])) {
                $columnCode .= "->comment('{$props['comment']}')";
            }

            $columnCode .= ";\n";
            $code .= $columnCode;
        }
        // 修改字段
        foreach ($differences['modify_columns'] as $col => $change) {
            // 主键字段特殊处理，不修改类型
            if ($col === 'id' && $change['to']['auto_increment']) {
                $columnCode = '    $table->id()';

                if ($change['to']['nullable']) {
                    $columnCode .= '->nullable()';
                }

                if ($change['to']['default'] !== null) {
                    $default = is_string($change['to']['default']) ? "'{$change['to']['default']}'" : $change['to']['default'];
                    $columnCode .= "->default({$default})";
                }

                if (! empty($change['to']['comment'])) {
                    $columnCode .= "->comment('{$change['to']['comment']}')";
                } else {
                    $columnCode .= "->comment('主键ID')";
                }

                $columnCode .= "->change();\n";
                $code .= $columnCode;
                continue;
            }
            $type       = $this->mapDbTypeToLaravel($change['to']['type']);
            $columnCode = '';

            // 处理需要参数的类型修改
            if ($type === 'decimal' && $props['precision'] && $props['scale']) {
                $columnCode = "    \$table->{$type}('{$col}', {$props['precision']}, {$props['scale']})";
            } elseif (in_array($type, ['string', 'char']) && $change['to']['length']) {
                $columnCode = "    \$table->{$type}('{$col}', {$change['to']['length']})";
            } else {
                $columnCode = "    \$table->{$type}('{$col}')";
            }

            if ($change['to']['nullable']) {
                $columnCode .= '->nullable()';
            }

            if ($change['to']['default'] !== null) {
                $default = is_string($change['to']['default']) ? "'{$change['to']['default']}'" : $change['to']['default'];
                $columnCode .= "->default({$default})";
            }

            if (! empty($change['to']['comment'])) {
                $columnCode .= "->comment('{$change['to']['comment']}')";
            }

            $columnCode .= "->change();\n";
            $code .= $columnCode;
        }

        // 新增索引
        foreach ($differences['add_indexes'] as $name => $props) {
            if ($props['primary']) {
                $code .= "    \$table->primary(['" . implode("', '", $props['columns']) . "'], '{$name}');\n";
            } elseif ($props['unique']) {
                $code .= "    \$table->unique(['" . implode("', '", $props['columns']) . "'], '{$name}');\n";
            } else {
                $code .= "    \$table->index(['" . implode("', '", $props['columns']) . "'], '{$name}');\n";
            }
        }

        // 新增外键
        foreach ($differences['add_foreign_keys'] as $name => $props) {
            $code .= "    \$table->foreign(['" . implode("', '", $props['local_columns']) . "'], '{$name}')\n";
            $code .= "        ->references(['" . implode("', '", $props['foreign_columns']) . "'])->on('{$props['foreign_table']}')\n";

            if ($props['on_delete']) {
                $code .= "        ->onDelete('{$props['on_delete']}')\n";
            }

            if ($props['on_update']) {
                $code .= "        ->onUpdate('{$props['on_update']}')\n";
            }

            $code .= "    ;\n";
        }

        $code .= "});\n";

        // 表级别修改
        if (! empty($differences['modify_table'])) {
            $code .= "\n";
            $code .= "if (Schema::hasTable('{$table}')) {\n";

            if (isset($differences['modify_table']['engine'])) {
                $code .= "    DB::statement('ALTER TABLE {$table} ENGINE = {$differences['modify_table']['to']['engine']}');\n";
            }
            // 正确的访问方式
            //            if (isset($differences['modify_table']['comment'])) {
            //                $comment = addslashes($differences['modify_table']['comment']['to']);
            //                $code .= "    DB::statement(\"ALTER TABLE {$table} COMMENT = '{$comment}'\");\n";
            //            }

            $code .= '}';
        }

        return $code;
    }

    /**
     * 构建修改表的down()方法代码（与up相反）.
     */
    protected function buildAlterTableDownCode(string $table, array $differences): string
    {
        // 这里实现与up方法相反的逻辑，用于回滚
        // 代码结构与buildAlterTableUpCode类似，但操作相反
        // 例如：删除的字段要恢复，新增的要删除等

        $code = "Schema::table('{$table}', function (Blueprint \$table) {\n";

        // 先删除新增的外键
        foreach ($differences['add_foreign_keys'] as $name => $props) {
            $code .= "    \$table->dropForeign('{$name}');\n";
        }

        // 删除新增的索引
        foreach ($differences['add_indexes'] as $name => $props) {
            if ($props['primary']) {
                $code .= "    \$table->dropPrimary('{$name}');\n";
            } else {
                $code .= "    \$table->dropIndex('{$name}');\n";
            }
        }

        // 删除新增的字段
        if (! empty($differences['add_columns'])) {
            $cols = implode("', '", array_keys($differences['add_columns']));
            $code .= "    \$table->dropColumn(['{$cols}']);\n";
        }

        // 恢复被修改的字段
        foreach ($differences['modify_columns'] as $col => $change) {
            $type       = $this->mapDbTypeToLaravel($change['from']['type']);
            $columnCode = "    \$table->{$type}('{$col}')";

            if (in_array($change['from']['type'], ['varchar', 'string']) && $change['from']['length']) {
                $columnCode .= "->length({$change['from']['length']})";
            }

            if ($change['from']['nullable']) {
                $columnCode .= '->nullable()';
            }

            if ($change['from']['default'] !== null) {
                $default = is_string($change['from']['default']) ? "'{$change['from']['default']}'" : $change['from']['default'];
                $columnCode .= "->default({$default})";
            }

            if (! empty($change['from']['comment'])) {
                $columnCode .= "->comment('{$change['from']['comment']}')";
            }

            $columnCode .= "->change();\n";
            $code .= $columnCode;
        }

        // 恢复被删除的字段
        foreach ($differences['drop_columns'] as $col => $props) {
            $type       = $this->mapDbTypeToLaravel($props['type']);
            $columnCode = "    \$table->{$type}('{$col}')";

            if (in_array($props['type'], ['varchar', 'string']) && $props['length']) {
                $columnCode .= "->length({$props['length']})";
            }

            if ($props['nullable']) {
                $columnCode .= '->nullable()';
            }

            if ($props['default'] !== null) {
                $default = is_string($props['default']) ? "'{$props['default']}'" : $props['default'];
                $columnCode .= "->default({$default})";
            }

            if ($props['auto_increment']) {
                $columnCode .= '->autoIncrement()';
            }

            if (! empty($props['comment'])) {
                $columnCode .= "->comment('{$props['comment']}')";
            }

            $columnCode .= ";\n";
            $code .= $columnCode;
        }

        // 恢复被删除的索引
        foreach ($differences['drop_indexes'] as $name => $props) {
            if ($props['primary']) {
                $code .= "    \$table->primary(['" . implode("', '", $props['columns']) . "'], '{$name}');\n";
            } elseif ($props['unique']) {
                $code .= "    \$table->unique(['" . implode("', '", $props['columns']) . "'], '{$name}');\n";
            } else {
                $code .= "    \$table->index(['" . implode("', '", $props['columns']) . "'], '{$name}');\n";
            }
        }

        // 恢复被删除的外键
        foreach ($differences['drop_foreign_keys'] as $name => $props) {
            $code .= "    \$table->foreign(['" . implode("', '", $props['local_columns']) . "'], '{$name}')\n";
            $code .= "        ->references(['" . implode("', '", $props['foreign_columns']) . "'])->on('{$props['foreign_table']}')\n";

            if ($props['on_delete']) {
                $code .= "        ->onDelete('{$props['on_delete']}')\n";
            }

            if ($props['on_update']) {
                $code .= "        ->onUpdate('{$props['on_update']}')\n";
            }

            $code .= "    ;\n";
        }

        $code .= "});\n";

        // 恢复表级别修改
        if (! empty($differences['modify_table'])) {
            $code .= "\n";
            $code .= "if (Schema::hasTable('{$table}')) {\n";

            if (isset($differences['modify_table']['engine'])) {
                $code .= "    DB::statement('ALTER TABLE {$table} ENGINE = {$differences['modify_table']['from']['engine']}');\n";
            }

            //            if (isset($differences['modify_table']['comment'])) {
            //                $comment = addslashes($differences['modify_table']['comment']['from']);
            //                $code .= "    DB::statement(\"ALTER TABLE {$table} COMMENT = '{$comment}'\");\n";
            //            }

            $code .= '}';
        }

        return $code;
    }

    /**
     * 获取迁移文件模板
     */
    protected function getMigrationTemplate(string $upCode, string $downCode): string
    {
        return <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    /**
     * 运行迁移
     *
     * @return void
     */
    public function up()
    {
        {$upCode}
    }

    /**
     * 回滚迁移
     *
     * @return void
     */
    public function down()
    {
        {$downCode}
    }
};
PHP;
    }

    /**
     * 映射数据库类型到Laravel迁移方法.
     */
    protected function mapDbTypeToLaravel(string $dbType): string
    {
        // 处理 enum 类型
        if ($dbType === 'enum') {
            return 'enum';
        }
        $map = [
            'int'         => 'integer',
            'varchar'     => 'string',
            'char'        => 'char',
            'text'        => 'text',
            'mediumtext'  => 'mediumText',
            'longtext'    => 'longText',
            'datetime'    => 'dateTime',
            'datetimetz'  => 'dateTimeTz',
            'date'        => 'date',
            'time'        => 'time',
            'timetz'      => 'timeTz',
            'timestamp'   => 'timestamp',
            'timestamptz' => 'timestampTz',
            'float'       => 'float',
            'double'      => 'double',
            'decimal'     => 'decimal',  // 仅返回类型名称
            'boolean'     => 'boolean',
            'binary'      => 'binary',
            'blob'        => 'blob',
            'json'        => 'json',
            'jsonb'       => 'jsonb',
            'uuid'        => 'uuid',
            'ipaddress'   => 'ipAddress',
            'macaddress'  => 'macAddress',
        ];

        return $map[$dbType] ?? $dbType;
    }

    /**
     * 从表名数组中移除前缀（新增方法）.
     */
    protected function removePrefixes(array $tables, string $prefix): array
    {
        if (empty($prefix)) {
            return $tables;
        }

        return array_map(function ($table) use ($prefix) {
            // 移除表名中的前缀
            if (str_starts_with($table, $prefix)) {
                return substr($table, strlen($prefix));
            }
            return $table;
        }, $tables);
    }
}
