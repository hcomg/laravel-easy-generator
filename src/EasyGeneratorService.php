<?php

namespace EasyGenerator;

use Illuminate\Support\Facades\DB;

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
        $this->routePath = strtolower(str_plural($this->controllerName));
        $this->output->info('');
        $this->output->info('Creating catalogue for table: ' . ($this->tableName ? : strtolower(str_plural($this->modelName))));
        $this->output->info('Model Name: ' . $modelName);

        $options = [
            'model_uc' => $modelName,
            'model_uc_plural' => str_plural($modelName),
            'model_singular' => strtolower($modelName),
            'model_plural' => strtolower(str_plural($modelName)),
            'tableName' => $this->tableName ?: strtolower(str_plural($this->modelName)),
            'prefix' => $this->prefix,
            'controller_name' => $this->controllerName,
            'route_path' => $this->routePath,
            'appns' => $this->appNamespace,
        ];

        if(!$this->force) {
            if (file_exists(app_path() . '/Models/' . $modelName . '.php')) {
                $this->output->info('Model already exists, use --force to overwrite');
                return;
            }
            if (file_exists(app_path() . '/Http/Controllers/' . $this->controllerName . 'Controller.php')) {
                $this->output->info('Controller already exists, use --force to overwrite');
                return;
            }
            if (file_exists(app_path() . '/Transformers/' . $this->modelName . 'Transformer.php')) {
                $this->output->info('Transformers already exists, use --force to overwrite');
                return;
            }
        }

        $columns = $this->getColumns($this->prefix . ($this->tableName ?: strtolower(str_plural($modelName))));

        $options['columns'] = $columns;
        $options['first_column_nonId'] = count($columns) > 1 ? $columns[1]['name'] : '';
        $options['num_columns'] = count($columns);

        if(!is_dir(app_path().'/Transformers')) {
            $this->output->info('Creating directory: '.app_path().'/Transformers');
            mkdir( app_path().'/Transformers');
        }

        $fileGenerator = new EasyGeneratorFileCreator();
        $fileGenerator->options = $options;
        $fileGenerator->output = $this->output;

        $fileGenerator->templateName = 'controller';
        $fileGenerator->path = app_path().'/Http/Controllers/'.$this->controllerName.'Controller.php';
        $fileGenerator->Generate();

        $fileGenerator->templateName = 'model';
        $fileGenerator->path = app_path().'/Models/'.$this->modelName.'.php';
        $fileGenerator->Generate();

        $fileGenerator->templateName = 'transformer';
        $fileGenerator->path = app_path().'/Transformers/'.$this->modelName.'Transformer.php';
        $fileGenerator->Generate();

        $addRoute = '$api->resource(\'' . $this->routePath . '\', \'\App\Http\Controllers\\' . $this->controllerName . 'Controller\');';
        $this->appendToEndOfFile(base_path().'/routes/api.php', "\n".$addRoute, 0, true);
        $this->output->info('Adding Route: '.$addRoute);
    }

    protected function appendToEndOfFile($path, $text, $remove_last_chars = 0, $dont_add_if_exist = false) {
        $content = file_get_contents($path);
        if(!str_contains($content, $text) || !$dont_add_if_exist) {
            $newContent = substr($content, 0, strlen($content) - $remove_last_chars) . $text;
            file_put_contents($path, $newContent);
        }
    }

    protected function getColumns($tableName) {
        $dbType = DB::getDriverName();
        switch ($dbType) {
            case "pgsql":
                $cols = DB::select("select column_name as Field, "
                    . "data_type as Type, "
                    . "is_nullable as Null "
                    . "from INFORMATION_SCHEMA.COLUMNS "
                    . "where table_name = '" . $tableName . "'");
                break;
            default:
                $cols = DB::select("show columns from " . $tableName);
                break;
        }
        $ret = [];
        foreach ($cols as $c) {
            $field = isset($c->Field) ? $c->Field : $c->field;
            $type = isset($c->Type) ? $c->Type : $c->type;
            $cAdd = [];
            $cAdd['name'] = $field;
            $cAdd['type'] = $field == 'id' ? 'id' : $this->getTypeFromDBType($type);
            $cAdd['display'] = ucwords(str_replace('_', ' ', $field));
            $ret[] = $cAdd;
        }
        return $ret;
    }

    protected function getTypeFromDBType($dbType) {
        if (str_contains($dbType, 'varchar')) {
            return 'text';
        }
        if (str_contains($dbType, 'int') || str_contains($dbType, 'float')) {
            return 'number';
        }
        if (str_contains($dbType, 'date')) {
            return 'date';
        }
        return 'unknown';
    }
}