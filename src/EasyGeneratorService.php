<?php

namespace EasyGenerator;

class EasyGeneratorService
{

    public $modelName = '';
    public $tableName = '';
    public $prefix = '';
    public $force = false;
    public $existingModel = '';
    public $controllerName = '';
    public $routePath = '';
    public $output = null;
    public $appNamespace = 'App';

    public function __construct()
    {
    }

    public function Generate()
    {
        $modelName = ucfirst(str_singular($this->modelName));
        $this->routePath = strtolower($this->controllerName);
        $this->output->info('');
        $this->output->info('Creating catalogue for table: ' . ($this->tableName ? : strtolower(str_plural($this->modelName))));
        $this->output->info('Model Name: ' . $modelName);
    }
}