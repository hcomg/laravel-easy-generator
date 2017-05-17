<?php

namespace EasyGenerator;

use Illuminate\Support\ServiceProvider;

class EasyGeneratorServiceProvider extends ServiceProvider
{
    public function register() {
        $this->commands(['EasyGenerator\Console\Commands\EasyGeneratorCommand']);
    }

    public function boot() {
        //
    }
}