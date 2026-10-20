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

namespace Tuoluojiang\Open\services\synchro;

use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class Company.
 */
class Company extends TokenService
{
    protected string $account = '';

    protected string $secret = '';

    protected string $tcpHost = '';

    protected string $tcpPort = '';

    protected string $accessToken = '';

    protected string $cacheTokenPrefix = '';

    protected string $salt = '';

    protected string $serviceName = '';

    protected string $createApi = '/api/v2/company/create';

    protected string $ordersApi = '/api/v2/company/orders';

    protected string $payStatusApi = '/api/v2/company/pay_status';

    protected string $invoiceApplyApi = '/api/v2/company/invoice/apply';

    protected string $invoiceEditApi = '/api/v2/company/invoice/edit';

    protected string $invoiceListApi = '/api/v2/company/invoice/list';

    protected string $payTypeApi = '/api/v2/company/payType';

    protected string $folderPayApi = '/api/v2/folder/pay';

    protected string $folderListApi = '/api/v2/folder/list';

    protected string $folderTemplateListApi = '/api/v2/folder/folder';

    protected string $templateInfoApi = '/api/v2/folder/detail';

    protected string $folderTemplateExportApi = '/api/v2/folder/template_export';

    protected string $accessClassifyApi = '/api/v2/assess/classify';

    protected string $accessListApi = '/api/v2/assess/list';

    protected string $accessInfoApi = '/api/v2/assess/info';

    protected string $assessTarget = '/api/v2/assess/target';

    /**
     * 申请企业.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function create($data)
    {
        return $this->httpRequest($this->createApi, $data, 'POST', false);
    }

    /**
     * 可用支付方式.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function payType()
    {
        return $this->httpRequest($this->payTypeApi);
    }

    /**
     * 企业订单信息.
     * @param mixed $page
     * @param mixed $limit
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function orders($data, $page, $limit)
    {
        $params = [
            'where' => $data,
            'page'  => $page,
            'limit' => $limit,
        ];
        return $this->httpRequest($this->ordersApi, $params);
    }

    /**
     * 订单支付结果.
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function orderResult($orderId)
    {
        $params = [
            'order_id' => $orderId,
        ];
        return $this->httpRequest($this->payStatusApi, $params);
    }

    /**
     * 企业申请发票.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function applyInvoice($data)
    {
        return $this->httpRequest($this->invoiceApplyApi, $data);
    }

    /**
     * 企业申请发票.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function editInvoice($data)
    {
        return $this->httpRequest($this->invoiceEditApi, $data);
    }

    /**
     * 企业发票列表.
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function invoiceList($where, $page, $limit)
    {
        $params = [
            'where' => $where,
            'page'  => $page,
            'limit' => $limit,
        ];
        return $this->httpRequest($this->invoiceListApi, $params);
    }

    /**
     * 获取模板文件列表.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function templateList($data)
    {
        return $this->httpRequest($this->folderListApi, $data);
    }

    /**
     * 获取模板文件目录.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function templateFolder($data)
    {
        return $this->httpRequest($this->folderTemplateListApi, $data);
    }

    /**
     * 导出模板文件.
     * @throws InvalidArgumentException
     */
    public function templateExport($data): mixed
    {
        return $this->httpRequest($this->folderTemplateExportApi, $data);
    }

    /**
     * 获取模板文件购买二维码
     * @throws InvalidArgumentException
     */
    public function templatePayCode($data): mixed
    {
        return $this->httpRequest($this->folderPayApi, $data);
    }

    /**
     * 获取模板文件列表.
     * @param string[] $field
     */
    public function assessCate($types, $field = ['*']): mixed
    {
        $data = [
            'types' => $types,
            'field' => $field,
        ];
        return $this->httpRequest($this->accessClassifyApi, $data, isHeader: false);
    }

    /**
     * 获取模板文件列表.
     * @param string[] $field
     * @param array $ids
     * @param mixed $nameLike
     */
    public function assessTemplate($cateId, $page, $limit, $field = ['*'], $ids = [], $nameLike = ''): mixed
    {
        $data = [
            'cate_id' => $cateId,
            'name'    => $nameLike,
            'page'    => $page,
            'limit'   => $limit,
            'field'   => $field,
            'ids'     => $ids,
        ];
        return $this->httpRequest($this->accessListApi, $data, isHeader: false);
    }

    /**
     * 获取模板文件详情.
     * @param string[] $field
     */
    public function assessInfo($id, $field = ['*']): mixed
    {
        $data = [
            'id'    => $id,
            'field' => $field,
        ];
        return $this->httpRequest($this->accessInfoApi, $data, isHeader: false);
    }

    /**
     * 获取指标模板列表.
     * @param string[] $field
     * @param array $with
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function assessTarget($where, $page, $limit, $field = ['*'], $with = [])
    {
        $data = [
            'where' => $where,
            'page'  => $page,
            'limit' => $limit,
            'field' => $field,
            'with'  => $with,
        ];
        return $this->httpRequest($this->assessTarget, $data, isHeader: false);
    }

    /**
     * 获取模板文件详情无需登录.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function templateInfoById($data)
    {
        return $this->httpRequest($this->templateInfoApi, $data, isHeader: false);
    }
}
