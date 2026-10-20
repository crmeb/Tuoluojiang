<?php

namespace App\Listeners;

use App\Http\Service\Crud\SystemCrudService;

class SystemCrud
{

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle()
    {
        app(SystemCrudService::class)->clearCache();
    }
}
