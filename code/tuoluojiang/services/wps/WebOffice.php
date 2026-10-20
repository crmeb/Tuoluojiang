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

namespace Tuoluojiang\Open\services\wps;

use Tuoluojiang\Open\exceptions\WebOfficeException;

/**
 * WPS office
 * Class WebOffice.
 */
class WebOffice
{
    // office默认地址
    public const WPS_OPEN_OFFICE = 'https://wwo.wps.cn/office/';

    // 文件预览支持格式
    public const WPS_OFFICE_WORD = 'w';

    public const WPS_OFFICE_PPT = 'p';

    public const WPS_OFFICE_SHEET = 's';

    public const WPS_OFFICE_PDF = 'f';

    // 文字文件支持类型
    public const WPS_OFFICE_WORD_TYPE = ['doc', 'dot', 'wps', 'wpt', 'docx', 'dotx', 'docm', 'dotm', 'rtf', 'txt', 'md'];

    // 演示文件支持类型
    public const WPS_OFFICE_PPT_TYPE = ['ppt', 'pptx', 'pptm', 'pdf', 'ppsx', 'ppsm', 'pps', 'potx', 'potm', 'dpt', 'dps'];

    // 表格文件支持类型
    public const WPS_OFFICE_SHEET_TYPE = ['xls', 'xlt', 'et', 'xlsx', 'xltx', 'csv', 'xlsm', 'xltm'];

    // PDF文件支持类型
    public const WPS_OFFICE_PDF_TYPE = ['pdf'];

    /**
     * @var Config
     */
    protected $config;

    /**
     * WebOffice constructor.
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * 获取签名.
     * @return string
     */
    public function getSignature(array $param = [])
    {
        $contents = '';
        $appKey   = $this->config->get('appKey');
        $data     = ['appid' => $this->config->get('appid')] + $param;
        ksort($data);
        $data += ['secretkey' => $appKey];
        foreach ($data as $key => $value) {
            $contents .= '_w_' . $key . '=' . $value;
        }
        return urlencode(base64_encode(hash_hmac('sha1', $contents, $appKey, true)));
    }

    /**
     * 文件新建.
     * @return string
     */
    public function newOffic(string $type = self::WPS_OFFICE_SHEET, array $param = [])
    {
        if (! in_array($type, [self::WPS_OFFICE_SHEET, self::WPS_OFFICE_PPT, self::WPS_OFFICE_WORD])) {
            throw new WebOfficeException(__('创建连接类型不正确'));
        }
        return $this->getCreateFileLink($type, 'new/0', $param);
    }

    /**
     * 文件编辑.
     * @return string
     */
    public function editOffic(string $fileId, string $type = self::WPS_OFFICE_SHEET, array $param = [])
    {
        return $this->getCreateFileLink($type, $fileId, $param);
    }

    /**
     * 文件预览.
     * @return string
     */
    public function viewOffic(string $fileId, string $type = self::WPS_OFFICE_SHEET, array $param = [])
    {
        return $this->getCreateFileLink($type, $fileId, $param);
    }

    /**
     * 创建访问路径.
     * @return string
     */
    public function getCreateFileLink(string $type = self::WPS_OFFICE_SHEET, string $query = '', array $param = [])
    {
        if (! in_array($type, [self::WPS_OFFICE_SHEET, self::WPS_OFFICE_PPT, self::WPS_OFFICE_WORD, self::WPS_OFFICE_PDF])) {
            throw new WebOfficeException(__('创建连接类型不正确'));
        }
        return $this->getWpsOffiesUrl(self::WPS_OPEN_OFFICE . $type . '/' . $query, $param);
    }

    /**
     * 创建offies访问地址
     * @return string
     */
    public function getWpsOffiesUrl(string $link, array $param = [])
    {
        $query = '_w_appid=' . $this->config->get('appid') . '&_w_signature=' . $this->getSignature($param);
        foreach ($param as $key => $value) {
            $query .= '&_w_' . $key . '=' . $value;
        }
        return $link . '?' . $query;
    }

    /**
     * 获取offies类型.
     * @return string
     */
    public function getOffiesType(string $mine)
    {
        foreach ([
            self::WPS_OFFICE_WORD  => self::WPS_OFFICE_WORD_TYPE,
            self::WPS_OFFICE_PPT   => self::WPS_OFFICE_PPT_TYPE,
            self::WPS_OFFICE_SHEET => self::WPS_OFFICE_SHEET_TYPE,
            self::WPS_OFFICE_PDF   => self::WPS_OFFICE_PDF_TYPE,
        ] as $key => $types) {
            if (in_array($mine, $types)) {
                return $key;
            }
        }
        return '';
    }
}
