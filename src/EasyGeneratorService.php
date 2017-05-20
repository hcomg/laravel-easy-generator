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
            'appns' => $this->appNamespace
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

        $rulesMessages = [];
        foreach ($columns as $column) {
            $rules = explode('|', $column['rules_create']);
            foreach ($rules as $rule) {
                if (str_contains($rule, ':')) {
                    $rule = explode(':', $rule);
                    $rule = $rule[0];
                }
                if (strlen($rule)) {
                    $rulesMessage = [
                        'key' => $column['name'] . '.' . $rule,
                        'value' => $column['name'] . '_' . $rule
                    ];
                    $rulesMessages[] = $rulesMessage;
                }
            }
        }
        $options['rules_messages'] = $rulesMessages;

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

        if ($this->modelName && strlen($this->modelName) > 0) {
            $fileGenerator->templateName = 'model';
            $fileGenerator->path = app_path().'/Models/'.$this->modelName.'.php';
            $fileGenerator->Generate();
        }

        if ($this->controllerName && strlen($this->controllerName) > 0) {
            $fileGenerator->templateName = 'controller';
            $fileGenerator->path = app_path() . '/Http/Controllers/' . $this->controllerName . 'Controller.php';
            $fileGenerator->Generate();

            $fileGenerator->templateName = 'transformer';
            $fileGenerator->path = app_path().'/Transformers/'.$this->controllerName.'Transformer.php';
            $fileGenerator->Generate();

            $addRoute = '    $api->resource(\'' . $this->routePath . '\', \'\App\Http\Controllers\\' . $this->controllerName . 'Controller\');';
            $this->appendToAfterVersion(base_path().'/routes/api.php', $addRoute);
            $this->output->info('Adding Route: '.$addRoute);
        }
    }

    protected function appendToAfterVersion($path, $text) {
        $content = file_get_contents($path);
        if(!str_contains($content, trim($text, "\",\n, "))) {
            $content = explode("\n", $content);
            $apiDefined = false;
            $hasPhpOpenTag = false;
            foreach ($content as $index => $item) {
                if (str_contains($item, '->version(')) {
                    $apiDefined = true;
                    array_splice($content, $index + 1, 0, $text);
                }
                if (str_contains($index, '<?php')) {
                    $hasPhpOpenTag = true;
                }
            }
            if (!$apiDefined) {
                if (!$hasPhpOpenTag) {
                    $content[] = "<?php\n";
                }
                $content[] = '$api = app(\'Dingo\Api\Routing\Router\');';
                $content[] = '$api->version(\'v1\', function ($api) {';
                $content[] = $text;
                $content[] = '});';
            }
            $content = implode($content, "\n");
            $content = trim($content, " ,\n");
            file_put_contents($path, $content);
        }
        return $content;
    }

    protected function getColumns($tableName) {
        $dbType = DB::getDriverName();
        switch ($dbType) {
            case "mysql":
                $cols = DB::select("SELECT cols.TABLE_NAME, cols.COLUMN_NAME, cols.ORDINAL_POSITION,
                    cols.COLUMN_DEFAULT, cols.IS_NULLABLE, cols.DATA_TYPE,
                        cols.CHARACTER_MAXIMUM_LENGTH, cols.CHARACTER_OCTET_LENGTH,
                        cols.NUMERIC_PRECISION, cols.NUMERIC_SCALE,
                        cols.COLUMN_TYPE, cols.COLUMN_KEY, cols.EXTRA,
                        cols.COLUMN_COMMENT, refs.REFERENCED_TABLE_NAME, refs.REFERENCED_COLUMN_NAME,
                        cRefs.UPDATE_RULE, cRefs.DELETE_RULE,
                        links.TABLE_NAME AS LINKS_TABLE, links.COLUMN_NAME AS LINKS_COLUMN,
                        cLinks.UPDATE_RULE, cLinks.DELETE_RULE
                    FROM INFORMATION_SCHEMA.`COLUMNS` as cols
                    LEFT JOIN INFORMATION_SCHEMA.`KEY_COLUMN_USAGE` AS refs
                    ON refs.TABLE_SCHEMA=cols.TABLE_SCHEMA
                        AND refs.REFERENCED_TABLE_SCHEMA=cols.TABLE_SCHEMA
                        AND refs.TABLE_NAME=cols.TABLE_NAME
                        AND refs.COLUMN_NAME=cols.COLUMN_NAME
                    LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS cRefs
                    ON cRefs.CONSTRAINT_SCHEMA=cols.TABLE_SCHEMA
                        AND cRefs.CONSTRAINT_NAME=refs.CONSTRAINT_NAME
                    LEFT JOIN INFORMATION_SCHEMA.`KEY_COLUMN_USAGE` AS links
                    ON links.TABLE_SCHEMA=cols.TABLE_SCHEMA
                        AND links.REFERENCED_TABLE_SCHEMA=cols.TABLE_SCHEMA
                        AND links.REFERENCED_TABLE_NAME=cols.TABLE_NAME
                        AND links.REFERENCED_COLUMN_NAME=cols.COLUMN_NAME
                    LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS cLinks
                    ON cLinks.CONSTRAINT_SCHEMA=cols.TABLE_SCHEMA
                        AND cLinks.CONSTRAINT_NAME=links.CONSTRAINT_NAME
                    WHERE cols.TABLE_SCHEMA=DATABASE()
                        AND cols.TABLE_NAME='{$tableName}'
                    GROUP BY cols.COLUMN_NAME
                    ORDER BY cols.COLUMN_NAME");
                break;
            default:
                $cols = [];
                break;
        }
//        return dd($cols);
        $ret = [];
        foreach ($cols as $c) {
            $cAdd = [];
            $cAdd['name'] = $c->COLUMN_NAME;
            $cAdd['doc_type'] = $this->getDocTypeFromDBType($c->DATA_TYPE);
            $cAdd['rules_create'] = $this->getRolesFromDB($c);
            $cAdd['rules_update'] = $this->getRolesFromDB($c, 'update');
            $ret[] = $cAdd;
        }
        return $ret;
    }

    protected function getDocTypeFromDBType($dbType) {
        if (
            str_contains($dbType, 'string') ||
            str_contains($dbType, 'varchar') ||
            str_contains($dbType, 'text')
        ) {
            $type = 'string';
        } elseif (
            str_contains($dbType, 'date') ||
            str_contains($dbType, 'time') ||
            str_contains($dbType, 'datetimetz') ||
            str_contains($dbType, 'datetime') ||
            str_contains($dbType, 'timestamp')
        ) {
            $type = '\Carbon\Carbon';
        } elseif (
            str_contains($dbType, 'guid') ||
            str_contains($dbType, 'integer') ||
            str_contains($dbType, 'bigint') ||
            str_contains($dbType, 'smallint') ||
            str_contains($dbType, 'tinyint') ||
            str_contains($dbType, 'int')
        ) {
            $type = 'int';
        } elseif (
            str_contains($dbType, 'decimal') ||
            str_contains($dbType, 'float')
        ) {
            $type = 'float';
        } elseif (
        str_contains($dbType, 'boolean')
        ) {
            $type = 'boolean';
        } else {
            $type = 'mixed';
        }
        return $type;
    }

    protected function getRuleTypeFromDBType($dbType) {
        if (
            str_contains($dbType, 'string') ||
            str_contains($dbType, 'varchar') ||
            str_contains($dbType, 'text')
        ) {
            $type = 'string';
        } elseif (
            str_contains($dbType, 'date') ||
            str_contains($dbType, 'time') ||
            str_contains($dbType, 'datetimetz') ||
            str_contains($dbType, 'datetime') ||
            str_contains($dbType, 'timestamp')
        ) {
            $type = 'date';
        } elseif (
            str_contains($dbType, 'guid') ||
            str_contains($dbType, 'integer') ||
            str_contains($dbType, 'bigint') ||
            str_contains($dbType, 'smallint') ||
            str_contains($dbType, 'tinyint') ||
            str_contains($dbType, 'int')
        ) {
            $type = 'integer';
        } elseif (
            str_contains($dbType, 'decimal') ||
            str_contains($dbType, 'float')
        ) {
            $type = 'float';
        } elseif (
        str_contains($dbType, 'boolean')
        ) {
            $type = 'boolean';
        } else {
            $type = '';
        }
        return $type;
    }

    protected function getRolesFromDB($column, $method = 'create') {
        $rules = '';
        if ($column->COLUMN_KEY === 'PRI') {
            return $rules;
        }
        if ($column->IS_NULLABLE === 'NO' && is_null($column->COLUMN_DEFAULT)) {
            $rules .= 'required';
        }
        $rules .= '|' . $this->getRuleTypeFromDBType($column->COLUMN_TYPE);
        if ($column->CHARACTER_MAXIMUM_LENGTH) {
            $rules .= '|max:' . $column->CHARACTER_MAXIMUM_LENGTH;
        }
        if ($column->COLUMN_KEY === 'UNI') {
            if ($method === 'create') {
                $rules .= '|unique:' . $column->TABLE_NAME;
            } else {
                $rules .= '|unique:' . $column->TABLE_NAME . ',{$id}';
            }
        }
        if ($column->COLUMN_KEY === 'MUL') {
            $rules .= '|exists:' . $column->REFERENCED_TABLE_NAME . ',' . $column->REFERENCED_COLUMN_NAME;
        }
        $rules = trim($rules, '|');
        return $rules;
    }
}