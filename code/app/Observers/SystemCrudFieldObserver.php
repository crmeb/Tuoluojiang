<?php

namespace App\Observers;



use App\Http\Model\Crud\SystemCrudField;

/**
 * Class SystemCrudFieldObserver
 */
class SystemCrudFieldObserver
{
    /**
     * Handle the SystemCrudField "created" event.
     *
     * @param  SystemCrudField  $systemCrudField
     * @return void
     */
    public function created(SystemCrudField $systemCrudField)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrudField "updated" event.
     *
     * @param  SystemCrudField  $systemCrudField
     * @return void
     */
    public function updated(SystemCrudField $systemCrudField)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrudField "deleted" event.
     *
     * @param  SystemCrudField  $systemCrudField
     * @return void
     */
    public function deleted(SystemCrudField $systemCrudField)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrudField "restored" event.
     *
     * @param  SystemCrudField  $systemCrudField
     * @return void
     */
    public function restored(SystemCrudField $systemCrudField)
    {
        event('system.crud');
    }

    /**
     * Handle the SystemCrudField "force deleted" event.
     *
     * @param  SystemCrudField  $systemCrudField
     * @return void
     */
    public function forceDeleted(SystemCrudField $systemCrudField)
    {
        event('system.crud');
    }
}
