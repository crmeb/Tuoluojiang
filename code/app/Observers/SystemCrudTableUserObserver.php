<?php

namespace App\Observers;



use App\Http\Model\Crud\SystemCrudTableUser;

class SystemCrudTableUserObserver
{
    /**
     * Handle the SystemCrudTableUser "created" event.
     *
     * @param  SystemCrudTableUser  $systemCrudTableUser
     * @return void
     */
    public function created(SystemCrudTableUser $systemCrudTableUser)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrudTableUser "updated" event.
     *
     * @param  SystemCrudTableUser  $systemCrudTableUser
     * @return void
     */
    public function updated(SystemCrudTableUser $systemCrudTableUser)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrudTableUser "deleted" event.
     *
     * @param  SystemCrudTableUser  $systemCrudTableUser
     * @return void
     */
    public function deleted(SystemCrudTableUser $systemCrudTableUser)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrudTableUser "restored" event.
     *
     * @param  SystemCrudTableUser  $systemCrudTableUser
     * @return void
     */
    public function restored(SystemCrudTableUser $systemCrudTableUser)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrudTableUser "force deleted" event.
     *
     * @param  SystemCrudTableUser  $systemCrudTableUser
     * @return void
     */
    public function forceDeleted(SystemCrudTableUser $systemCrudTableUser)
    {
        event('system.crud');
    }
}
