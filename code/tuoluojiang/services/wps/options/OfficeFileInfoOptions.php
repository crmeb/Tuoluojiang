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

namespace Tuoluojiang\Open\services\wps\options;

use Tuoluojiang\Open\interfaces\OptionsInterface;
use Tuoluojiang\Open\traits\OptionsTrait;
use Illuminate\Support\Str;

/**
 * 文件信息
 * Class OfficeFileInfoOptions.
 */
class OfficeFileInfoOptions implements OptionsInterface
{
    use OptionsTrait;

    /**
     * 文件id,字符串长度小于32.
     * @var string
     */
    public $id;

    /**
     * 文件名(必须带文件后缀).
     * @var string
     */
    public $name;

    /**
     * 当前版本号，位数小于11.
     * @var int
     */
    public $version;

    /**
     * 文件大小，单位为B(文件真实大小，否则会出现异常).
     * @var int
     */
    public $size;

    /**
     * 创建者ID字符串长度小于32.
     * @var string
     */
    public $creator;

    /**
     * 创建时间，时间戳，单位为秒.
     * @var int
     */
    public $createTime;

    /**
     * 修改者id，字符串长度小于32.
     * @var string
     */
    public $modifier;

    /**
     * 修改时间，时间戳，单位为秒.
     * @var int
     */
    public $modifyTime;

    /**
     * 文档下载地址
     * @var string
     */
    public $downloadUrl;

    /**
     * 限制预览页数.
     * @var int
     */
    public $previewPages;

    /**
     * @var array
     */
    public $userAcl = [
        'rename'  => 1, // 重命名权限，1为打开该权限，0为关闭该权限，默认为0
        'history' => 1, // 历史版本权限，1为打开该权限，0为关闭该权限,默认为1
        'copy'    => 1, // 复制
        'export'  => 1, // 导出PDF
        'print'   => 1, // 打印
    ];

    /**
     * 水印.
     * @var array
     */
    public $watermark = [
        'type'       => 0, // 水印类型， 0为无水印； 1为文字水印,
        'value'      => '', // 文字水印的文字，当type为1时此字段必选
        'fillstyle'  => '', // 水印的透明度，非必选，有默认值
        'font'       => '', // 水印的字体，非必选，有默认值
        'rotate'     => '', // 水印的旋转度，非必选，有默认值
        'horizontal' => '', // 水印水平间距，非必选，有默认值
        'vertical'   => '', // 水印垂直间距，非必选，有默认值
    ];

    /**
     * @var string[]
     */
    public $user = [
        'id'         => '', // 用户id，长度小于32
        'name'       => '', // 用户名称
        'permission' => '', // 用户操作权限，write：可编辑，read：预览
        'avatar_url' => '', // 用户头像地址
    ];

    /**
     * OfficeOptions constructor.
     */
    public function __construct(
        ?string $id = null,
        ?string $name = null,
        int $version = 0,
        int $size = 0,
        ?string $creator = null,
        int $createTime = 0,
        ?string $modifier = null,
        int $modifyTime = 0,
        ?string $downloadUrl = null,
        int $previewPages = 0,
        array $userAcl = [],
        array $watermark = [],
        array $user = []
    ) {
        $this->id           = $id;
        $this->name         = $name;
        $this->version      = $version;
        $this->size         = $size;
        $this->creator      = $creator;
        $this->createTime   = $createTime;
        $this->modifier     = $modifier;
        $this->modifyTime   = $modifyTime;
        $this->downloadUrl  = $downloadUrl;
        $this->previewPages = $previewPages;
        $this->userAcl      = $userAcl ?: $this->userAcl;
        $this->watermark    = $watermark ?: $this->watermark;
        $this->user         = $user ?: $this->user;
    }

    /**
     * 转换数据.
     * @return mixed|void
     */
    public function toArray(): array
    {
        $publicData = get_object_vars($this);
        $data       = [];
        foreach ($publicData as $key => $value) {
            $data[Str::snake($key)] = $value;
        }
        $user = $data['user'];
        unset($data['user']);
        return ['file' => $data, 'user' => $user];
    }
}
