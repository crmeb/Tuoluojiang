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

namespace App\Http\Dao\WorkExternalContact;

use App\Http\Model\WorkExternalContact\WorkMedia;
use App\Http\Dao\BaseDao;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 临时素材数据处理.
 */
class WorkMediaDao extends BaseDao
{
    protected $table = 'work_media';

    public function getModel(bool $need = true)
    {
        if (! Schema::hasTable($this->table)) {
            method_exists($this, 'createTable') && $this->createTable();
        }
        return parent::getModel($need);
    }

    protected function setModel()
    {
        return WorkMedia::class;
    }

    /**
     * 创建表.
     */
    protected function createTable()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->unsignedInteger('uid')->default(0)->index()->comment('业务员用户ID');
            $table->string('real_name', 255)->default('')->comment('原始名称');
            $table->string('file_name', 255)->default('')->comment('附件名称');
            $table->string('file_url', 255)->default('')->comment('附件地址');
            $table->string('file_size', 255)->default('')->comment('附件大小');
            $table->string('file_type', 255)->default('')->comment('附件类型');
            $table->string('file_ext', 255)->default('')->comment('扩展名');
            $table->string('file_md5', 255)->default('')->comment('文件md5值');
            $table->unsignedTinyInteger('up_type')->default(1)->comment('上传方式：1、本地；2、七牛云；3、OSS；4、COS。');
            $table->unsignedInteger('link_id')->default(0)->index()->comment('关联数据ID');
            $table->string('link_type', 32)->default('')->index()->comment('关联数据类型');
            $table->string('media_id', 255)->nullable()->index()->comment('临时素材ID');
            $table->timestamp('fail_time')->nullable()->comment('临时素材失效时间');
            $table->string('attach_id', 255)->nullable()->index()->comment('临时附件ID');
            $table->timestamp('attach_fail')->nullable()->comment('临时附件失效时间');
            $table->string('job_id', 255)->nullable()->index()->comment('分片上传素材任务ID');
            $table->string('media_type', 32)->default('')->comment('素材类型: image、voice、video、file');
            $table->string('media_msg', 255)->default('')->comment('临时素材上传失败信息');
            $table->string('attach_msg', 255)->default('')->comment('临时附件上传失败信息');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('临时素材表');
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });
    }
}
