<?php

namespace EasyGenerator\Console\Commands;

use EasyGenerator\EasyGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Config;

class EasyGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api {model-name} {--force} {--singular} {--table-name=} {--custom-controller=}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create fully functional api resource code based on a mysql table instantly';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $modelName = strtolower($this->argument('model-name'));
        $prefix = Config::get('database.connections.mysql.prefix');
        $custom_table_name = $this->option('table-name');
        $custom_controller = $this->option('custom-controller');
        $singular = $this->option('singular');
        $toCreate = [];
        if($modelName == 'all') {
            $preTables = json_decode(json_encode(DB::select("show tables")), true);
            $tables = [];
            foreach($preTables as $p) {
                list($key) = array_keys($p);
                $tables[] = $p[$key];
            }
            $this->info("List of tables: ".implode($tables, ","));

            foreach ($tables as $t) {
                // Ignore tables with different prefix
                if($prefix == '' || str_contains($t, $prefix)) {
                    $t = strtolower(substr($t, strlen($prefix)));
                    $toAdd = ['modelName'=> str_singular($t), 'tableName'=>''];
                    if(str_plural($toAdd['modelName']) != $t) {
                        $toAdd['tableName'] = $t;
                    }
                    $toCreate[] = $toAdd;
                }
            }
            // Remove options not applicable for multiples tables
            $custom_table_name = null;
            $custom_controller = null;
            $singular = null;
        }
        else {
            $toCreate = [
                'modelName' => $modelName,
                'tableName' => '',
            ];
            if($singular) {
                $toCreate['tableName'] = strtolower($modelName);
            }
            else if($custom_table_name) {
                $toCreate['tableName'] = $custom_table_name;
            }
            $toCreate = [$toCreate];
        }
        foreach ($toCreate as $c) {
            $generator = new EasyGeneratorService();
            $generator->output = $this;
            $generator->appNamespace = Container::getInstance()->getNamespace();
            $generator->modelName = ucfirst($c['modelName']);
            $generator->tableName = $c['tableName'];
            $generator->prefix = $prefix;
            $generator->force = $this->option('force');
            $generator->controllerName = ucfirst(strtolower($custom_controller)) ?: str_plural($generator->modelName);
            $generator->Generate();
        }
    }
}