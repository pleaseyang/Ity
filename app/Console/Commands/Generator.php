<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class Generator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generator {model : Model名称} {module : 模块名称}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '代码生成器';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * 未做字典设置
     *
     * @return void
     */
    public function handle()
    {
        $disk = Storage::disk('codes');
        $disk->deleteDirectory('php');
        $disk->deleteDirectory('js');
        $disk->delete('generator.zip');
        $model = $this->argument('model');
        $module = $this->argument('module');
        $dbName = Str::snake(Str::pluralStudly($model));
        $singular = Str::singular($dbName);

        // Controller
        $controller = str_replace([
            '{{model}}', '{{module}}'
        ], [
            $model, $module
        ], $this->getStub('Controller'));
        $controllerPath = "php/app/Http/Controllers/$module/{$model}Controller.php";
        Storage::disk('codes')->put($controllerPath, $controller);

        $api = str_replace([
            '{{model}}', '{{module}}', '{{snake}}', '{{singular}}'
        ], [
            $model, $module, $dbName, $singular
        ], $this->getStub('api'));
        $apiPath = "php/routes/api.php";
        Storage::disk('codes')->put($apiPath, $api);

        // Model
        $dbColumns = DB::select(sprintf("select `COLUMN_NAME` as name ,DATA_TYPE as type,COLUMN_COMMENT as comment, IS_NULLABLE as is_null, COLUMN_KEY as `key` from information_schema.columns where TABLE_NAME='$dbName' and table_schema = '%s';", config('database.connections.mysql.database')));

        $fillable = [];
        $unGeneratorColumns = ['id', 'created_at', 'updated_at'];
        $where = [];
        foreach ($dbColumns as $dbColumn) {
            if (!in_array($dbColumn->name, $unGeneratorColumns)) {
                $fillable[] = "'$dbColumn->name'";
            }
            if (in_array($dbColumn->type, ['bigint', 'int', 'tinyint'])) {
                $text = 'when(isset($validated[\''.$dbColumn->name.'\']) && is_numeric($validated[\''.$dbColumn->name.'\']), function (Builder $query) use ($validated): Builder {
            return $query->where(\''.$dbName.'.'.$dbColumn->name.'\', $validated[\''.$dbColumn->name.'\']);
        })';
                $where[] = $text;
            }
            if (in_array($dbColumn->type, ['varchar'])) {
                $text = 'when($validated[\''.$dbColumn->name.'\'] ?? null, function (Builder $query) use ($validated): Builder {
            return $query->where(\''.$dbName.'.'.$dbColumn->name.'\', \'like\', \'%\' . $validated[\''.$dbColumn->name.'\'] . \'%\');
        })';
                $where[] = $text;
            }
        }
        $fillable = implode(', ', $fillable);
        $where = implode('->', $where);
        $modelStub = str_replace([
            '{{model}}', '{{dbName}}', '{{fillable}}', '{{where}}'
        ], [
            $model, $dbName, $fillable, $where
        ], $this->getStub('Model'));
        $modelPath = "php/app/Models/{$model}.php";
        Storage::disk('codes')->put($modelPath, $modelStub);

        // Request
        $get = [];
        $create = [];
        $update = [];
        foreach ($dbColumns as $dbColumn) {
            $type = 'string';
            if (in_array($dbColumn->type, ['bigint', 'int', 'tinyint'])) {
                $type = 'numeric';
            }
            if (in_array($dbColumn->type, ['timestamp', 'datetime'])) {
                $type = 'date_format:Y-m-d H:i:s';
            }
            $required = $dbColumn->is_null === 'NO' ? 'required': 'nullable';
            $unique = $dbColumn->key === 'UNI' ? "Rule::unique('$dbName')" : '';
            if (in_array($dbColumn->type, ['bigint', 'int', 'tinyint', 'varchar'])) {
                $get[] = "'{$dbColumn->name}' => ['nullable', 'string',]";
            }
            if (!in_array($dbColumn->name, $unGeneratorColumns)) {
                $create[] = "'{$dbColumn->name}' => ['$required', '$type', $unique]";
                if ($unique) {
                    $uniqueText = $unique . '->ignore($this->post(\'id\'))';
                } else {
                    $uniqueText = '';
                }
                $update[] = '\''.$dbColumn->name.'\' => [\'' . $required . '\', \'' . $type .'\', ' . $uniqueText . ']';
            }
        }
        $update[] = "'id' => ['required', 'numeric', 'exists:{$dbName}',]";

        $attributes = [];
        foreach ($dbColumns as $dbColumn) {
            $attributes[] = "'{$dbColumn->name}' => '{$dbColumn->comment}',";
        }

        $attributes = implode("\n            ", $attributes);
        $get = implode(",\n            ", $get);
        $create = implode(",\n            ", $create);
        $update = implode(",\n            ", $update);
        $id = "'id' => ['required', 'integer', Rule::exists('{$dbName}', 'id'),],";
        $getRequest = str_replace([
            '{{model}}', '{{module}}', '{{rules}}', '{{attributes}}'
        ], [
            $model, $module, $get, $attributes
        ], $this->getStub('GetListRequest'));
        $getRequestPath = "php/app/Http/Requests/$module/{$model}/GetListRequest.php";
        Storage::disk('codes')->put($getRequestPath, $getRequest);

        $createRequest = str_replace([
            '{{model}}', '{{module}}', '{{rules}}', '{{attributes}}'
        ], [
            $model, $module, $create, $attributes
        ], $this->getStub('CreateRequest'));
        $createRequestPath = "php/app/Http/Requests/$module/{$model}/CreateRequest.php";
        Storage::disk('codes')->put($createRequestPath, $createRequest);

        $updateRequest = str_replace([
            '{{model}}', '{{module}}', '{{rules}}', '{{attributes}}'
        ], [
            $model, $module, $update, $attributes
        ], $this->getStub('UpdateRequest'));
        $updateRequestPath = "php/app/Http/Requests/$module/{$model}/UpdateRequest.php";
        Storage::disk('codes')->put($updateRequestPath, $updateRequest);

        $idRequest = str_replace([
            '{{model}}', '{{module}}', '{{rules}}', '{{attributes}}'
        ], [
            $model, $module, $id, $attributes
        ], $this->getStub('IdRequest'));
        $idRequestPath = "php/app/Http/Requests/$module/{$model}/IdRequest.php";
        Storage::disk('codes')->put($idRequestPath, $idRequest);


        // js

        // api js
        $apiJs = str_replace([
            '{{snake}}', '{{singular}}'
        ], [
            $dbName, $singular
        ], $this->getStub('apijs'));
        $apiJsPath = "js/src/api/$singular.js";
        Storage::disk('codes')->put($apiJsPath, $apiJs);

        $search = [];
        $table = [];
        $create = [];
        $update = [];
        $form = [];
        foreach ($dbColumns as $dbColumn) {
            if (!in_array($dbColumn->name, $unGeneratorColumns)) {
                $comment = $dbColumn->comment === '' ? $dbColumn->name : $dbColumn->comment;
                if (in_array($dbColumn->type, ['varchar'])) {
                    $search[] = '<el-form-item label="'.$comment.'" prop="'.$dbColumn->name.'">
              <el-input v-model="formInline.'.$dbColumn->name.'" placeholder="'.$comment.'" />
          </el-form-item>';
                }

                $table[] = '<el-table-column prop="'.$dbColumn->name.'" label="'.$comment.'" sortable />';

                $create[] = '<el-form-item label="'.$comment.'" prop="'.$dbColumn->name.'" :error="createError.'.$dbColumn->name.' ? createError.'.$dbColumn->name.'[0] : \'\'">
          <el-input v-model="createForm.'.$dbColumn->name.'" />
        </el-form-item>';

                $update[] = '<el-form-item label="'.$comment.'" prop="'.$dbColumn->name.'" :error="updateError.'.$dbColumn->name.' ? updateError.'.$dbColumn->name.'[0] : \'\'">
          <el-input v-model="updateForm.'.$dbColumn->name.'" />
        </el-form-item>';

                $form[] = "$dbColumn->name: ''";
            }
        }
        $search = implode("\n          ", $search);
        $table = implode("\n            ", $table);
        $create = implode("\n        ", $create);
        $update = implode("\n        ", $update);
        $form = implode(",\n        ", $form);
        $vue = str_replace([
            '{{search}}', '{{table}}', '{{create}}', '{{update}}', '{{snake}}', '{{singular}}', '{{form}}'
        ], [
            $search, $table, $create, $update, $dbName, $singular, $form
        ], $this->getStub('vue'));
        $vuePath = "js/src/views/$singular/$dbName.vue";
        Storage::disk('codes')->put($vuePath, $vue);

        $zip = new ZipArchive();
        $zipPath = storage_path("codes/generator.zip");
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);   //打开压缩包
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(storage_path("codes")),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                if (basename($filePath) !== '.gitignore') {
                    $relativePath = substr($filePath, strlen(storage_path("codes")) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
        $zip->close();
        $disk->deleteDirectory('php');
        $disk->deleteDirectory('js');

        exec("explorer " . storage_path("codes"));
    }

    private function getStub(string $type): string
    {
        return file_get_contents(resource_path('stubs/' . $type . '.stub'));
    }
}
