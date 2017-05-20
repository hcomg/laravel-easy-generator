<?php

namespace EasyGenerator\Console\Commands;

use EasyGenerator\EasyGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;

class EasyGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hcomg:gen
                            {--model= : The name of the model.}
                            {--table= : The name of the table.}
                            {--controller= : The name of the controller.}
                            {--force : Override exist files}';
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
        $modelName = strtolower($this->option('model'));
        $prefix = Config::get('database.connections.mysql.prefix');
        $table_name = $this->option('table');
        $controller = $this->option('controller');
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
            $table_name = null;
            $controller = null;
            $singular = null;
        }
        else {
            $toCreate = [
                'modelName' => $modelName,
                'tableName' => $table_name,
            ];
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
            $generator->controllerName = ucfirst(strtolower($controller)) ?: str_singular($generator->modelName);
            $generator->Generate();
        }
    }
}