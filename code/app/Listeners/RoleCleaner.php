<?php

namespace App\Listeners;

use Hhxsv5\LaravelS\Illuminate\Cleaners\BaseCleaner;
use Hhxsv5\LaravelS\Illuminate\Cleaners\CleanerInterface;
use Illuminate\Support\Facades\Facade;

class RoleCleaner extends BaseCleaner implements CleanerInterface
{
    protected $instances = [
        'enforcer'
    ];

    public function clean()
    {
        foreach ($this->instances as $instance) {
            $this->currentApp->forgetInstance($instance);
            Facade::clearResolvedInstance($instance);
        }
    }
}
