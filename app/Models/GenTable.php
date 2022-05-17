<?php

namespace App\Models;

use App\Util\Gen;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * App\Models\GenTable
 *
 * @property int $id
 * @property string $name 表名称
 * @property string|null $comment 表描述
 * @property string $engine 表引擎
 * @property string $charset 字符集
 * @property string $collation 排序规则
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Activitylog\Models\Activity[] $activities
 * @property-read int|null $activities_count
 * @method static \Illuminate\Database\Eloquent\Builder|GenTable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GenTable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GenTable query()
 * @method static \Illuminate\Database\Eloquent\Builder|GenTable whereCharset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTable whereCollation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTable whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTable whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTable whereEngine($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTable whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTable whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTable whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\GenTableColumn[] $genTableColumns
 * @property-read int|null $gen_table_columns_count
 * @mixin \Eloquent
 */
class GenTable extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'comment', 'engine', 'charset', 'collation'
    ];

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('gen_table')
            ->logFillable()
            ->logUnguarded();
    }

    public function genTableColumns(): HasMany
    {
        return $this->hasMany(GenTableColumn::class);
    }

    public static function getImportTableList(): array
    {
        $tables = Gen::getTableList();
        $alreadyImportTable = GenTable::pluck('name')->toArray();
        return array_values(array_filter($tables, function (array $table) use ($alreadyImportTable) {
            return !in_array($table['name'], $alreadyImportTable);
        }));
    }

    /**
     * @param string $tableName
     * @return bool
     * @throws Throwable
     */
    public static function importTable(string $tableName): bool
    {
        $config = Gen::getTableConfig($tableName);
        DB::beginTransaction();
        GenTable::whereName($tableName)->delete();
        $table = new GenTable();
        $table->name = $config['name'];
        $table->comment = $config['comment'];
        $table->engine = $config['engine'];
        $table->charset = $config['charset'];
        $table->collation = $config['collation'];
        $table->save();
        $columns = array_map(function (array $column) use ($table): array {
            unset($column['columnDefinition']);
            unset($column['fixed']);
            unset($column['length']);
            unset($column['charset']);
            unset($column['collation']);
            $column['gen_table_id'] = $table->id;
            $column['created_at'] = now();
            $column['updated_at'] = now();
            return $column;
        }, $config['columns']);
        GenTableColumn::insert($columns);
        DB::commit();
        return true;
    }

    public static function gen(string $tableName, int $permissionId = 0, string $permissionName = null): bool
    {
        $table = GenTable::whereName($tableName)->first();
        $columns = GenTableColumn::whereGenTableId($table->id)->get();
        $unCommentColumns = $columns->whereNull('comment')->values();
        if ($unCommentColumns->count() > 0) {
            $unCommentName = $unCommentColumns->pluck('name')->implode('|');
            throw new Exception("$unCommentName 备注不能为空");
        }

        $name = $table->name;
        $className = Str::of($name)->singular()->studly()->toString();
        $singular = Str::of($name)->singular()->camel()->toString();
        $snake = Str::of($name)->plural()->camel()->toString();
        if (!$permissionName) {
            $permissionName = $table->comment;
        }
        if (!$permissionName) {
            throw new Exception('权限名称不能为空');
        }

        Storage::disk('codes')->deleteDirectory('php');
        Storage::disk('codes')->deleteDirectory('vue');

        $permissionSeeder = str_replace([
            '{{className}}', '{{permissionId}}', '{{permissionName}}', '{{singular}}', '{{snake}}'
        ], [
            $className, $permissionId, $permissionName, $singular, $snake
        ], GenTable::getStub('PermissionSeeder'));
        $path = "php/database/seeders/{$className}PermissionSeeder.php";
        Storage::disk('codes')->put($path, $permissionSeeder);

        $api = str_replace([
            '{{className}}', '{{permissionName}}', '{{singular}}', '{{snake}}'
        ], [
            $className, $permissionName, $singular, $snake
        ], GenTable::getStub('Api'));
        $path = "php/routes/api.php";
        Storage::disk('codes')->put($path, $api);

        $controller = str_replace([
            '{{className}}'
        ], [
            $className
        ], GenTable::getStub('Controller'));
        $path = "php/app/Http/Controllers/Admin/{$className}Controller.php";
        Storage::disk('codes')->put($path, $controller);

        $primaries = $columns->where('primary', '=', true)
            ->where('autoincrement', '=', true);
        if ($primaries->count() !== 1) {
            throw new Exception('数据表[主键自增]字段大于2或不存在， 暂不支持生成');
        }

        /* @var GenTableColumn $primary */
        $primary = $primaries->first();
        $idRequest = str_replace([
            '{{className}}', '{{tableName}}', '{{id}}', '{{validate}}', '{{message}}'
        ], [
            $className, $tableName, $primary->name, $primary->_validate, $primary->comment
        ], GenTable::getStub('IdRequest'));
        $path = "php/app/Http/Requests/Admin/{$className}/IdRequest.php";
        Storage::disk('codes')->put($path, $idRequest);

        $selectColumns = $columns->where('_select', '=', true)->values();
        $selectDateColumns = $selectColumns->where('_validate', 'date')->values();
        $selectColumns = $selectColumns->reject(function (GenTableColumn $column) use ($selectDateColumns): bool {
            return in_array($column->name, $selectDateColumns->pluck('name')->toArray());
        });
        $selectDateColumns = $selectDateColumns->map(function (GenTableColumn $column) {
            $columnStart = clone $column;
            $columnEnd = clone $column;
            $columnStart->name = $column->name . '_start';
            $columnEnd->name = $column->name . '_end';
            $columnStart->comment = $column->comment . '开始';
            $columnEnd->comment = $column->comment . '结束';
            return [$columnStart, $columnEnd];
        })->collapse();
        $selectColumns = $selectColumns->mergeRecursive($selectDateColumns);
        $selectRules = $selectColumns->map(function (GenTableColumn $column): string {
            return sprintf("'%s' => ['nullable', '%s'],", $column->name, $column->_validate);
        })->implode("\n            ");
        $selectAttributes = $selectColumns->map(function (GenTableColumn $column): string {
            return sprintf("'%s' => '%s',", $column->name, $column->comment);
        })->implode("\n            ");
        $selectRequest = str_replace([
            '{{className}}', '{{rules}}', '{{attributes}}'
        ], [
            $className, $selectRules, $selectAttributes
        ], GenTable::getStub('GetListRequest'));
        $path = "php/app/Http/Requests/Admin/{$className}/GetListRequest.php";
        Storage::disk('codes')->put($path, $selectRequest);

        $insertColumns = $columns->where('primary', '=', false)
            ->where('autoincrement', '=', false)
            ->where('_insert', '=', true)
            ->values();
        $insertRules = $insertColumns->map(function (GenTableColumn $column) use ($tableName, $singular): string {
            $required = $column->_required ? 'required' : 'nullable';
            $rule = [
                "'$required'",
                "'$column->_validate'",
            ];
            if ($column->dict_type_id) {
                array_push($rule, "Rule::exists('dict_data', 'value')->where('dict_type_id', $column->dict_type_id)->where('status', 1)");
            }
            if ($column->_unique) {
                array_push($rule, "Rule::unique('$tableName', '$column->name')");
            }
            if ($column->_foreign) {
                $fkClassName = Str::of($column->_foreign_table)->singular()->studly()->toString();
                $exceptions = [];
                if (!class_exists('\App\Http\Controllers\Admin\\' . $fkClassName . 'Controller')) {
                    array_push($exceptions, '\App\Http\Controllers\Admin\\' . $fkClassName . 'Controller 类不存在');
                }
                if (!method_exists('\App\Http\Controllers\Admin\\' . $fkClassName . 'Controller', 'select')) {
                    array_push($exceptions, '\App\Http\Controllers\Admin\\' . $fkClassName . 'Controller ::select 方法不存在');
                }
                $fkSingular = Str::of($column->_foreign_table)->singular()->camel()->toString();
                $route = "api/admin/$fkSingular/select";

                try {
                    Route::getRoutes()->match(Request::create($route, 'POST'));
                } catch (NotFoundHttpException | MethodNotAllowedHttpException) {
                    array_push($exceptions, "$route POST 路由不存在");
                }

                if (count($exceptions) > 0) {
                    throw new Exception(implode(',', $exceptions));
                }
                array_push($rule, "Rule::exists('$column->_foreign_table', '$column->_foreign_column')");
            }
            return "'$column->name' => [" . implode(', ', $rule) . "],";
        })->implode("\n            ");
        $insertAttributes = $insertColumns->map(function (GenTableColumn $column): string {
            return sprintf("'%s' => '%s',", $column->name, $column->comment);
        })->implode("\n            ");
        $insertRequest = str_replace([
            '{{className}}', '{{rules}}', '{{attributes}}'
        ], [
            $className, $insertRules, $insertAttributes
        ], GenTable::getStub('CreateRequest'));
        $path = "php/app/Http/Requests/Admin/{$className}/CreateRequest.php";
        Storage::disk('codes')->put($path, $insertRequest);

        $updateColumns = $columns->where('_update', '=', true)->values();
        $updateRules = $updateColumns->map(function (GenTableColumn $column) use ($tableName, $primary): string {
            $required = $column->_required ? 'required' : 'nullable';
            $rule = [
                "'$required'",
                "'$column->_validate'",
            ];
            if ($column->dict_type_id) {
                array_push($rule, "Rule::exists('dict_data', 'value')->where('dict_type_id', $column->dict_type_id)->where('status', 1)");
            }
            if ($column->_unique) {
                array_push($rule, "Rule::unique('$tableName', '$column->name')->ignore(" . '$' . $primary->name . ")");
            }
            if ($column->_foreign) {
                array_push($rule, "Rule::exists('$column->_foreign_table', '$column->_foreign_column')");
            }
            if ($column->primary && $column->autoincrement) {
                array_push($rule, "Rule::exists('$tableName', '$column->name')");
            }
            return "'$column->name' => [" . implode(', ', $rule) . "],";
        })->implode("\n            ");
        $updateRequestColumn = '$' . $primary->name . ' = $this->post(\'' . $primary->name . '\', 0);';
        $updateAttributes = $updateColumns->map(function (GenTableColumn $column): string {
            return sprintf("'%s' => '%s',", $column->name, $column->comment);
        })->implode("\n            ");
        $updateRequest = str_replace([
            '{{className}}', '{{idRequest}}', '{{rules}}', '{{attributes}}'
        ], [
            $className, $updateRequestColumn, $updateRules, $updateAttributes
        ], GenTable::getStub('UpdateRequest'));
        $path = "php/app/Http/Requests/Admin/{$className}/UpdateRequest.php";
        Storage::disk('codes')->put($path, $updateRequest);

        $fillable = $columns->where('primary', '=', false)
            ->where('autoincrement', '=', false)
            ->pluck('name')
            ->map(fn(string $name): string => "'$name'")
            ->implode(', ');

        $where = $columns->where('_select', '=', true)->map(function (GenTableColumn $column) use ($tableName) {
            $when = '';
            if ($column->_validate === 'integer') {
                $when = ' && is_numeric($validated[\'' . $column->name . '\'])';
            }
            if ($column->_query === Gen::SELECT_LIKE) {
                $where = 'when(isset($validated[\'' . $column->name . '\'])' . $when . ', function (Builder $query) use ($validated): Builder {
            return $query->where(\'' . $tableName . '.' . $column->name . '\', \'LIKE\', \'%\' . $validated[\'' . $column->name . '\'] . \'%\');
        })';
            } else if ($column->_query === Gen::SELECT_BETWEEN) {
                $where = 'when(isset($validated[\'' . $column->name . '_start\']) && isset($validated[\'' . $column->name . '_end\']), function (Builder $query) use ($validated): Builder {
            return $query->whereBetween(\'' . $tableName . '.' . $column->name . '\', [$validated[\'' . $column->name . '_start' . '\'], $validated[\'' . $column->name . '_end' . '\']]);
        })->when(isset($validated[\'' . $column->name . '_start\']) && !isset($validated[\'' . $column->name . '_end\']), function (Builder $query) use ($validated): Builder {
            return $query->where(\'' . $tableName . '.' . $column->name . '\', \'>=\', $validated[\'' . $column->name . '_start' . '\']);
        })->when(!isset($validated[\'' . $column->name . '_start\']) && isset($validated[\'' . $column->name . '_end\']), function (Builder $query) use ($validated): Builder {
            return $query->where(\'' . $tableName . '.' . $column->name . '\', \'<=\', $validated[\'' . $column->name . '_end' . '\']);
        })';
            } else {
                $where = 'when(isset($validated[\'' . $column->name . '\'])' . $when . ', function (Builder $query) use ($validated): Builder {
            return $query->where(\'' . $tableName . '.' . $column->name . '\', \'' . $column->_query . '\', $validated[\'' . $column->name . '\']);
        })';
            }
            return $where;
        })->implode('->');
        $selectDbColumns = $columns->where('_list', '=', true)->pluck('name')
            ->add($primary->name)
            ->map(function (string $name) use ($tableName): string {
                return  "'{$tableName}.{$name}'";
            })
            ->implode(', ');
        $columnNameList = $columns->pluck('name')->toArray();
        $timestamps = in_array(Model::CREATED_AT, $columnNameList) && in_array(Model::UPDATED_AT, $columnNameList);
        $sort = $timestamps ? Model::CREATED_AT : $primary->name;

        $model = str_replace([
            '{{className}}', '{{fillable}}', '{{singular}}', '{{where}}', '{{select}}', '{{tableName}}', '{{primaryKey}}', '{{keyType}}', '{{timestamps}}', '{{sort}}', '{{count}}'
        ], [
            $className, $fillable, $singular, $where, $selectDbColumns, $tableName, $primary->name, $primary->type === 'integer' ? 'int' : 'string', $timestamps ? 'true' : 'false', $sort, $tableName . '.' . $primary->name
        ], GenTable::getStub('Model'));
        $path = "php/app/Models/{$className}.php";
        Storage::disk('codes')->put($path, $model);

        $apis = str_replace([
            '{{singular}}', '{{snake}}'
        ], [
            $singular, $snake
        ], GenTable::getStub('Apijs'));
        $path = "vue/src/api/{$singular}Api.js";
        Storage::disk('codes')->put($path, $apis);

        return true;
    }

    private static function getStub(string $type): string
    {
        return file_get_contents(resource_path('stubs/' . $type . '.stub'));
    }
}
