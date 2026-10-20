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

namespace Tuoluojiang\Open\services\wechat\client\work;

use Tuoluojiang\Open\services\wechat\client\BaseClient;
use EasyWeChat\Kernel\HttpClient\Response;
use Illuminate\Support\Carbon;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CheckInClient extends BaseClient
{
    /**
     * 获取员工打卡规则
     * 自建应用、第三方应用和代开发应用可使用此接口，获取可见范围内指定员工指定日期的打卡规则。
     * 最后更新：2025/08/29.
     * @param string $date 日期当天0点的Unix时间戳
     * @param array|string $userId 员工ID
     * @throws TransportExceptionInterface
     */
    public function checkInRules(string $date, array|string $userId): Response|ResponseInterface
    {
        $data = [
            'datetime'   => Carbon::parse($date, 'Asia/Shanghai')->startOfDay()->timestamp,
            'useridlist' => is_string($userId) ? [$userId] : $userId,
        ];
        return $this->api->postJson('cgi-bin/checkin/getcheckinoption', $data);
    }

    /**
     * 获取打卡记录数据
     * 应用可通过本接口，获取可见范围内员工指定时间段内的打卡记录数据。
     * 最后更新：2024/12/16.
     * @param string $start 开始时间
     * @param string $end 结束时间
     * @param array|string $userId 员工ID
     * @param int $type 获取打卡记录数据类型:1：上下班打卡；2：外出打卡；3：全部打卡
     * @throws TransportExceptionInterface
     */
    public function checkInData(string $start, string $end, array|string $userId, int $type = 3): Response|ResponseInterface
    {
        $data = [
            'opencheckindatatype' => $type,
            'starttime'           => Carbon::parse($start, 'Asia/Shanghai')->timestamp,
            'endtime'             => Carbon::parse($end, 'Asia/Shanghai')->timestamp,
            'useridlist'          => is_string($userId) ? [$userId] : $userId,
        ];
        return $this->api->postJson('cgi-bin/checkin/getcheckindata', $data);
    }

    /**
     * 获取打卡日报数据
     * 企业可通过具有调用权限的应用，获取应用可见范围内指定员工指定日期内的打卡日报统计数据。
     * 最后更新：2024/09/11.
     * @param string $start 开始时间
     * @param string $end 结束时间
     * @param array|string $userId 员工ID
     * @throws TransportExceptionInterface
     */
    public function checkInDayData(string $start, string $end, array|string $userId): Response|ResponseInterface
    {
        $data = [
            'starttime'  => Carbon::parse($start, 'Asia/Shanghai')->timestamp,
            'endtime'    => Carbon::parse($end, 'Asia/Shanghai')->timestamp,
            'useridlist' => is_string($userId) ? [$userId] : $userId,
        ];
        return $this->api->postJson('cgi-bin/checkin/getcheckin_daydata', $data);
    }

    /**
     * 获取打卡月报数据.
     * 企业可通过具有调用权限的应用，获取应用可见范围内指定员工指定日期内的打卡月报统计数据。
     * 最后更新：2025/03/24.
     * @param string $start 开始时间
     * @param string $end 结束时间
     * @param array|string $userId 员工ID
     * @throws TransportExceptionInterface
     */
    public function checkInMonthData(string $start, string $end, array|string $userId): Response|ResponseInterface
    {
        $data = [
            'starttime'  => Carbon::parse($start, 'Asia/Shanghai')->timestamp,
            'endtime'    => Carbon::parse($end, 'Asia/Shanghai')->timestamp,
            'useridlist' => is_string($userId) ? [$userId] : $userId,
        ];
        return $this->api->postJson('cgi-bin/checkin/getcheckin_monthdata', $data);
    }

    /**
     * 获取打卡人员排班信息.
     * 应用可通过此接口，获取应用可见范围内、打卡规则为“按班次上下班”规则的指定员工指定时间段内的排班信息。
     * 最后更新：2023/12/18.
     * @param string $start 开始时间
     * @param string $end 结束时间
     * @param array|string $userId 员工ID
     * @throws TransportExceptionInterface
     */
    public function getCheckInSchedule(string $start, string $end, array|string $userId): Response|ResponseInterface
    {
        $data = [
            'starttime'  => Carbon::parse($start, 'Asia/Shanghai')->timestamp,
            'endtime'    => Carbon::parse($end, 'Asia/Shanghai')->timestamp,
            'useridlist' => is_string($userId) ? [$userId] : $userId,
        ];
        return $this->api->postJson('cgi-bin/checkin/getcheckinschedulist', $data);
    }

    /**
     * 为打卡人员排班.
     * 企业可通过具有调用权限的应用，为打卡规则为“按班次上下班”规则的指定员工排班。
     * 最后更新：2023/12/18.
     * @param int $groupId 排班组ID
     * @param array $items 排班数据
     * @param string $yearMonth 排班年月
     * @throws TransportExceptionInterface
     */
    public function setCheckInSchedule(int $groupId, array $items, string $yearMonth): Response|ResponseInterface
    {
        $data = [
            'groupid'   => $groupId,
            'items'     => $items,
            'yearmonth' => $yearMonth,
        ];
        return $this->api->postJson('cgi-bin/checkin/setcheckinschedulist', $data);
    }

    /**
     * 获取设备打卡数据.
     * 应用可通过此接口，获取可见范围内成员在考勤设备上产生的原始打卡记录，包括未被打卡应用记录的不符合打卡规则的记录。
     * 最后更新：2024/10/31.
     * @param string $start 获取打卡记录数据开始时间
     * @param string $end 获取打卡记录数据结束时间
     * @param array|string $userId 员工ID
     * @param int $filterType 获取打卡记录过滤类型:1、表示按打卡时间过滤，2、表示按设备上传打卡记录的时间过滤
     * @throws TransportExceptionInterface
     */
    public function hardwareCheckInData(string $start, string $end, array|string $userId, int $filterType = 1): Response|ResponseInterface
    {
        $data = [
            'filter_type' => $filterType,
            'starttime'   => Carbon::parse($start, 'Asia/Shanghai')->timestamp,
            'endtime'     => Carbon::parse($end, 'Asia/Shanghai')->timestamp,
            'useridlist'  => is_string($userId) ? [$userId] : $userId,
        ];
        return $this->api->postJson('cgi-bin/hardware/get_hardware_checkin_data', $data);
    }
}
