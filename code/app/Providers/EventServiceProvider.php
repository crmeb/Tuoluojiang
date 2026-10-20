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

namespace App\Providers;

use App\Events\BusinessDataChangeEvent;
use App\Events\Config\YihaotongConfigChangedEvent;
use App\Events\SystemMessageEvent;
use App\Http\Model\Approve\ApproveApply;
use App\Http\Model\Company\UserAssess;
use App\Http\Model\Customer\Contract;
use App\Http\Model\Customer\Customer;
use App\Http\Model\Customer\Invoice;
use App\Http\Model\News\News;
use App\Http\Model\Program\ProgramTask;
use App\Http\Model\Schedule\Schedule;
use App\Listeners\AutoCopy;
use App\Listeners\Config\YihaotongAiModelSyncListener;
use App\Listeners\Config\YihaotongESignatureSyncListener;
use App\Listeners\Todo\TodoChangeListener;
use App\Listeners\Notification\DingHookNotificationListener;
use App\Listeners\Notification\EmailNotificationListener;
use App\Listeners\Notification\SmsNotificationListener;
use App\Listeners\Notification\SocketNotificationListener;
use App\Listeners\Notification\UniPushNotificationListener;
use App\Listeners\Notification\WeWorkNotificationListener;
use App\Listeners\Notification\WorkHookNotificationListener;
use App\Listeners\socket\WebSocketAdmin;
use App\Listeners\socket\WebSocketEnt;
use App\Listeners\socket\WebSocketError;
use App\Listeners\socket\WebSocketUser;
use App\Listeners\swoole\SwooleShutDown;
use App\Listeners\swoole\SwooleStart;
use App\Listeners\swoole\SwooleTask;
use App\Listeners\SystemCrud;
use App\Listeners\SystemCrudRoleListener;
use App\Observers\Todo\ScheduleObserver;
use App\Observers\Todo\CustomerObserver;
use App\Observers\Todo\ContractObserver;
use App\Observers\Todo\InvoiceObserver;
use App\Observers\Todo\ProgramTaskObserver;
use App\Observers\Todo\NewsObserver;
use App\Observers\Todo\ApproveApplyObserver;
use App\Observers\Todo\AssessPlanObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        SystemMessageEvent::class => [
            SmsNotificationListener::class,
            SocketNotificationListener::class,
            WorkHookNotificationListener::class,
            DingHookNotificationListener::class,
            UniPushNotificationListener::class,
            EmailNotificationListener::class,
            WeWorkNotificationListener::class,
        ],
        'swoole.start' => [
            SwooleStart::class,
        ],
        'swoole.task' => [
            SwooleTask::class,
        ],
        'swoole.shutDown' => [
            SwooleShutDown::class,
        ],
        'swoole.workerError' => [
            WebSocketError::class,
        ],
        'swoole.websocket.user' => [
            WebSocketUser::class,
        ],
        'swoole.websocket.admin' => [
            WebSocketAdmin::class,
        ],
        'swoole.websocket.ent' => [
            WebSocketEnt::class,
        ],
        'approve.autoCopy' => [
            AutoCopy::class,
        ],
        'system.crud' => [
            SystemCrud::class,
        ],
        'system.crud_role' => [
            SystemCrudRoleListener::class,
        ],
        BusinessDataChangeEvent::class => [
            TodoChangeListener::class,
        ],
        YihaotongConfigChangedEvent::class => [
            YihaotongESignatureSyncListener::class,
            YihaotongAiModelSyncListener::class,
        ],
    ];

    /**
     * 模型观察者映射.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $observers = [
        Schedule::class => [
            ScheduleObserver::class,
        ],
        Customer::class => [
            CustomerObserver::class,
        ],
        Contract::class => [
            ContractObserver::class,
        ],
        Invoice::class => [
            InvoiceObserver::class,
        ],
        ProgramTask::class => [
            ProgramTaskObserver::class,
        ],
        News::class => [
            NewsObserver::class,
        ],
        ApproveApply::class => [
            ApproveApplyObserver::class,
        ],
        UserAssess::class => [
            AssessPlanObserver::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot() {}

    /**
     * Register the application's event listeners.
     */
    public function register()
    {
        if (file_exists(public_path('install/install.lock'))) {
            $this->booting(function () {
                $events = $this->getEvents();
                foreach ($events as $event => $listeners) {
                    foreach (array_unique($listeners, SORT_REGULAR) as $listener) {
                        Event::listen($event, $listener);
                    }
                }
                foreach ($this->subscribe as $subscriber) {
                    Event::subscribe($subscriber);
                }
                foreach ($this->observers as $model => $observers) {
                    $model::observe($observers);
                }
            });
        }
    }
}
