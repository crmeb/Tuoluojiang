<?php

namespace App\Observers;



use App\Http\Model\Crud\SystemCrudTable;

class SystemCrudTableObserver
{
    /**
     * Handle the SystemCrudTable "created" event.
     *
     * @param  SystemCrudTable  $systemCrudTable
     * @return void
     */
    public function created(SystemCrudTable $systemCrudTable)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrudTable "updated" event.
     *
     * @param  SystemCrudTable  $systemCrudTable
     * @return void
     */
    public function updated(SystemCrudTable $systemCrudTable)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrudTable "deleted" event.
     *
     * @param  SystemCrudTable  $systemCrudTable
     * @return void
     */
    public function deleted(SystemCrudTable $systemCrudTable)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrudTable "restored" event.
     *
     * @param  SystemCrudTable  $systemCrudTable
     * @return void
     */
    public function restored(SystemCrudTable $systemCrudTable)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrudTable "force deleted" event.
     *
     * @param  SystemCrudTable  $systemCrudTable
     * @return void
     */
    public function forceDeleted(SystemCrudTable $systemCrudTable)
    {
        event('system.crud');
    }
}
