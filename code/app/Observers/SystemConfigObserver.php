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

namespace App\Observers;

use App\Constants\CacheEnum;
use App\Events\Config\YihaotongConfigChangedEvent;
use App\Http\Model\Config\Config;
use Illuminate\Support\Facades\Cache;

/**
 * 系统配置模型观察者：清缓存，并在一号通凭证变更时派发同步事件.
 */
class SystemConfigObserver
{
    /**
     * 一号通凭证配置 key.
     *
     * @var array<int, string>
     */
    private array $yiHaoTong = ['yihaotong_appid', 'yihaotong_appsecret'];

    /**
     * 配置更新后：清配置缓存；一号通 key 变更时派发同步事件.
     *
     * @param Config $systemConfig 配置模型
     */
    public function updated(Config $systemConfig): void
    {
        $this->dispatchYihaotongChangedIfNeeded($systemConfig);
    }

    /**
     * 配置创建后：清配置缓存；一号通 key 首次写入时派发同步事件.
     *
     * @param Config $systemConfig 配置模型
     */
    public function created(Config $systemConfig): void
    {
        $this->dispatchYihaotongChangedIfNeeded($systemConfig);
    }

    /**
     * 仅一号通 AppId/AppSecret 变更时触发同步事件（唯一派发入口）.
     *
     * @param Config $systemConfig 配置模型
     */
    private function dispatchYihaotongChangedIfNeeded(Config $systemConfig): void
    {
        if (in_array($systemConfig->key, $this->yiHaoTong, true)) {
            event(new YihaotongConfigChangedEvent());
        }
    }
}
