<?php

namespace App\Observers;


use App\Http\Model\Crud\SystemCrud;

class SystemCrudObserver
{
    /**
     * Handle the SystemCrud "created" event.
     *
     * @param SystemCrud $systemCrud
     * @return void
     */
    public function created(SystemCrud $systemCrud)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrud "updated" event.
     *
     * @param SystemCrud $systemCrud
     * @return void
     */
    public function updated(SystemCrud $systemCrud)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrud "deleted" event.
     *
     * @param SystemCrud $systemCrud
     * @return void
     */
    public function deleted(SystemCrud $systemCrud)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrud "restored" event.
     *
     * @param SystemCrud $systemCrud
     * @return void
     */
    public function restored(SystemCrud $systemCrud)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrud "force deleted" event.
     *
     * @param SystemCrud $systemCrud
     * @return void
     */
    public function forceDeleted(SystemCrud $systemCrud)
    {
        event('system.crud');
    }
}
