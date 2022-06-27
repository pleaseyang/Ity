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
        if (isset($config['exception']) && $config['exception']) {
            throw new Exception($config['exception']);
        }
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
                return "'{$tableName}.{$name}'";
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

        $form = $selectColumns->map(function (GenTableColumn $genTableColumn): string {
            if ($genTableColumn->_show === Gen::TYPE_INPUT_TEXT) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '">
          <el-input v-model="form.' . $genTableColumn->name . '" />
        </el-form-item>';
                if ($genTableColumn->_validate === 'boolean') {
                    $label = Str::of($genTableColumn->comment)->explode(' ')->first();
                    $form = '<el-form-item label="' . $label . '" prop="' . $genTableColumn->name . '">
          <el-select v-model="form.' . $genTableColumn->name . '" clearable>
            <el-option :key="1" :value="1" :label="$t(\'common.yes\')" />
            <el-option :key="0" :value="0" :label="$t(\'common.no\')" />
          </el-select>
        </el-form-item>';
                }
            } elseif ($genTableColumn->_foreign) {
                $vFor = Str::of($genTableColumn->_foreign_table)->singular()->camel()->append('SelectData')->toString();
                $label = Str::of($genTableColumn->_foreign_show)->explode(',')->first();
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '">
          <el-select v-model="form.' . $genTableColumn->name . '" clearable filterable>
            <el-option
              v-for="item in ' . $vFor . '"
              :key="item.' . $genTableColumn->_foreign_column . '"
              :value="item.' . $genTableColumn->_foreign_column . '"
              :label="item.' . $label . '"
            />
          </el-select>
        </el-form-item>';
            } elseif (is_int($genTableColumn->dict_type_id)) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '">
          <el-select v-model="form.' . $genTableColumn->name . '" clearable filterable>
            <el-option
              v-for="item in dict.filter((e) => e.dict_type_id === ' . $genTableColumn->dict_type_id . ')"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>';
            } elseif ($genTableColumn->_show === Gen::TYPE_DATE) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '">
          <el-date-picker
            v-model="form.' . $genTableColumn->name . '"
            type="datetime"
            format="yyyy-MM-dd HH:mm:ss"
            time-arrow-control
          />
        </el-form-item>';
            } else {
                throw new Exception('字段[' . $genTableColumn->name . ']未记录， 暂不支持生成搜索条件');
            }

            return $form;
        })->implode("\n        ");

        $listColumns = $columns->where('_list', '=', true)->values();
        $table = $listColumns->map(function (GenTableColumn $genTableColumn): string {
            if ($genTableColumn->_show === Gen::TYPE_INPUT_TEXT || $genTableColumn->_show === Gen::TYPE_DATE) {
                $table = '<el-table-column prop="' . $genTableColumn->name . '" label="' . $genTableColumn->comment . '" sortable />';
                if ($genTableColumn->_validate === 'boolean') {
                    $label = Str::of($genTableColumn->comment)->explode(' ')->first();
                    $table = '<el-table-column prop="' . $genTableColumn->name . '" label="' . $label . '" sortable>
          <template scope="scope">
            <el-tag v-if="scope.row.' . $genTableColumn->name . '" type="success">{{ $t(\'common.yes\') }}</el-tag>
            <el-tag v-else type="danger">{{ $t(\'common.no\') }}</el-tag>
          </template>
        </el-table-column>';
                }
            } elseif ($genTableColumn->_foreign) {
                $vFor = Str::of($genTableColumn->_foreign_table)->singular()->camel()->append('SelectData')->toString();
                $label = Str::of($genTableColumn->_foreign_show)->explode(',')->first();
                $table = '<el-table-column prop="' . $genTableColumn->name . '" label="' . $genTableColumn->comment . '" sortable>
          <template scope="scope">
            <ForeignString v-if="' . $vFor . '.length > 0" column="' . $genTableColumn->_foreign_column . '" show="' . $label . '" :data="' . $vFor . '" :value="scope.row.' . $genTableColumn->name . '" />
          </template>
        </el-table-column>';
            } elseif (is_int($genTableColumn->dict_type_id)) {
                $table = '<el-table-column prop="' . $genTableColumn->name . '" label="' . $genTableColumn->comment . '" sortable>
          <template scope="scope">
            <DictTag v-if="dict.length > 0" :dict-data="dict" :dict-type-id="' . $genTableColumn->dict_type_id . '" :value="scope.row.' . $genTableColumn->name . '" />
          </template>
        </el-table-column>';
            } elseif ($genTableColumn->_show === Gen::TYPE_IMAGE) {
                $table = '<el-table-column prop="' . $genTableColumn->name . '" label="' . $genTableColumn->comment . '" sortable>
          <template scope="scope">
            <el-image class="table-image table-image-50" :src="scope.row.' . $genTableColumn->name . '" :preview-src-list="[scope.row.' . $genTableColumn->name . ']">
              <div slot="error" class="image-error-slot">
                <i class="el-icon-picture-outline" />
              </div>
            </el-image>
          </template>
        </el-table-column>';
            } elseif ($genTableColumn->_show === Gen::TYPE_FILE) {
                $table = '<el-table-column prop="' . $genTableColumn->name . '" label="' . $genTableColumn->comment . '" sortable>
          <template scope="scope">
            <el-link v-if="scope.row.' . $genTableColumn->name . '" icon="el-icon-download" :underline="false" :href="scope.row.' . $genTableColumn->name . '" target="_blank">{{ $t(\'common.download\') }}</el-link>
          </template>
        </el-table-column>';
            } else {
                throw new Exception('字段[' . $genTableColumn->name . ']未记录， 暂不支持生成表格数据');
            }

            return $table;
        })->implode("\n        ");

        $dataForm = $selectColumns->map(function (GenTableColumn $genTableColumn): string {
            return "$genTableColumn->name: ''";
        })->implode(",\n        ");

        $components = [];
        $components[] = "create: () => import('@/views/testDb/create')";
        $components[] = "update: () => import('@/views/testDb/update')";
        $dataFormColumns = [];
        $mounted = [];
        $methods = [];
        $import = [];
        $import[] = "import { {$singular}Delete, {$singular}List } from '@/api/{$singular}Api'";
        if ($listColumns->where('dict_type_id', '=', true)->count() > 0) {
            $components[] = "DictTag: () => import('@/components/DictTag')";
            $dataFormColumns[] = 'dict: []';
            $mounted[] = "this.getDictData()";
            $methods[] = 'getDictData() {
      dictDataSelect().then(response => {
        const { select = [] } = response.data
        this.dict = select
      })
    }';
            $import[] = "import { dictDataSelect } from '@/api/dict'";
        }
        $foreignColumns = $listColumns->where('_foreign', '=', true)->values();
        if ($foreignColumns->count() > 0) {
            $components[] = "ForeignString: () => import('@/components/Foreign/string')";
            $dataFormColumns = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                return Str::of($genTableColumn->_foreign_table)->singular()->camel()->append('SelectData: []')->toString();
            })->merge($dataFormColumns);
            $mounted = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                return 'this.get' . Str::of($genTableColumn->_foreign_table)->singular()->studly()->append('Select()')->toString();
            })->merge($mounted);
            $methods = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                $name = Str::of($genTableColumn->_foreign_table)->singular()->studly()->toString();
                $method = Str::of($genTableColumn->_foreign_table)->singular()->camel()->toString();
                return 'get' . $name . 'Select() {
      ' . $method . 'Select().then(response => {
        const { select = [] } = response.data
        this.' . $method . 'SelectData = select
      })
    }';
            })->merge($methods);
            $import = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                $name = Str::of($genTableColumn->_foreign_table)->singular()->camel()->toString();
                return "import { {$name}Select } from '@/api/{$name}Api'";
            })->merge($import);
        }
        $dataFormColumns = $dataFormColumns->implode(",\n      ");
        $components = implode(",\n    ", $components);
        $mounted = $mounted->implode("\n    ");
        $methods = $methods->implode(",\n    ");
        $import = $import->implode("\n");

        $indexVue = str_replace([
            '{{form}}', '{{table}}', '{{name}}', '{{dataForm}}', '{{primaryId}}', '{{components}}', '{{dataFormColumns}}', '{{mounted}}', '{{methods}}', '{{import}}', '{{singular}}', '{{sort}}'
        ], [
            $form, $table, $singular . '.' . $snake, $dataForm, $primary->name, $components, $dataFormColumns, $mounted, $methods, $import, $singular, $sort
        ], GenTable::getStub('IndexVue'));
        $path = "vue/src/views/{$singular}/index.vue";
        Storage::disk('codes')->put($path, $indexVue);

        $createColumns = $columns->where('_insert', '=', true)->values();
        $createForm = $createColumns->map(function (GenTableColumn $genTableColumn): string {
            $required = $genTableColumn->_required ? 'class="form-item-required" ' : '';
            if ($genTableColumn->_show === Gen::TYPE_INPUT_TEXT) {
                if ($genTableColumn->_validate === 'string') {
                    $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-input v-model="form.' . $genTableColumn->name . '" clearable />
      </el-form-item>';
                } elseif ($genTableColumn->_validate === 'integer') {
                    $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-input-number v-model="form.' . $genTableColumn->name . '" clearable />
      </el-form-item>';
                } elseif ($genTableColumn->_validate === 'boolean') {
                    $label = Str::of($genTableColumn->comment)->explode(' ')->first();
                    $form = '<el-form-item label="' . $label . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-select v-model="form.' . $genTableColumn->name . '">
          <el-option :key="1" :value="1" :label="$t(\'common.yes\')" />
          <el-option :key="0" :value="0" :label="$t(\'common.no\')" />
        </el-select>
      </el-form-item>';
                } else {
                    throw new Exception('字段[' . $genTableColumn->name . ']未记录， 暂不支持生成新增TEXT表格');
                }
            } elseif ($genTableColumn->_foreign) {
                $vFor = Str::of($genTableColumn->_foreign_table)->singular()->camel()->append('SelectData')->toString();
                $label = Str::of($genTableColumn->_foreign_show)->explode(',')->first();
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-select v-if="' . $vFor . '.length > 0" v-model="form.' . $genTableColumn->name . '" clearable filterable>
          <el-option
            v-for="item in ' . $vFor . '"
            :key="item.' . $genTableColumn->_foreign_column . '"
            :value="item.' . $genTableColumn->_foreign_column . '"
            :label="item.' . $label . '"
          />
        </el-select>
      </el-form-item>';
            } elseif (is_int($genTableColumn->dict_type_id)) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-select v-if="dict.length > 0" v-model="form.' . $genTableColumn->name . '" clearable filterable>
          <el-option
            v-for="item in dict.filter((e) => e.dict_type_id === ' . $genTableColumn->dict_type_id . ')"
            :key="item.value"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
      </el-form-item>';
            } elseif ($genTableColumn->_show === Gen::TYPE_IMAGE) {
                $httpRequestName = Str::of($genTableColumn->name)->singular()->studly()->toString();
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-upload
          ref="' . $genTableColumn->name . '"
          class="avatar-uploader"
          action="#"
          accept="image/*"
          name="image"
          :limit="1"
          :show-file-list="false"
          :http-request="upload' . $httpRequestName . 'Image"
        >
          <el-image v-if="form.' . $genTableColumn->name . '" class="avatar" :src="form.' . $genTableColumn->name . '" />
          <i v-else class="el-icon-plus avatar-uploader-icon" />
        </el-upload>
      </el-form-item>';
            } elseif ($genTableColumn->_show === Gen::TYPE_FILE) {
                $httpRequestName = Str::of($genTableColumn->name)->singular()->studly()->toString();
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-upload
          ref="' . $genTableColumn->name . '"
          action="#"
          accept="application/msword,application/pdf,text/plain,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,video/*"
          name="file"
          :limit="1"
          :file-list="' . $genTableColumn->name . 'FileList"
          :http-request="upload' . $httpRequestName . 'File"
          :on-exceed="' . $genTableColumn->name . 'UploadExceed"
          :on-remove="' . $genTableColumn->name . 'UploadRemove"
        >
          <el-button type="primary">{{ $t(\'file.uploadFileText.uploadText2\') }}</el-button>
          <div slot="tip" class="el-upload__tip">{{ $t(\'common.uploadTip\') }}</div>
        </el-upload>
      </el-form-item>';
            } elseif ($genTableColumn->_show === Gen::TYPE_EDITOR) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <WangEditor ref="' . $genTableColumn->name . 'Editor" v-model="form.' . $genTableColumn->name . '" />
      </el-form-item>';
            } elseif ($genTableColumn->_show === Gen::TYPE_DATE) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-date-picker
          v-model="form.' . $genTableColumn->name . '"
          type="datetime"
          value-format="yyyy-MM-dd HH:mm:ss"
          time-arrow-control
        />
      </el-form-item>';
            } else {
                throw new Exception('字段[' . $genTableColumn->name . ']未记录， 暂不支持生成新增表格');
            }

            return $form;
        })->implode("\n      ");

        $import = [];
        $components = [];
        $dataFormColumns = [];
        $init = [];
        $methods = [];
        $resetForm = [];
        $import[] = "import { {$singular}Create } from '@/api/{$singular}Api'";
        if ($createColumns->where('dict_type_id', '=', true)->count() > 0) {
            $import[] = "import { dictDataSelect } from '@/api/dict'";
            $dataFormColumns[] = 'dict: []';
            $init[] = "this.getDictData()";
            $methods[] = 'getDictData() {
      dictDataSelect().then(response => {
        const { select = [] } = response.data
        this.dict = select
      })
    }';
        }
        $foreignColumns = $createColumns->where('_foreign', '=', true)->values();
        if ($foreignColumns->count() > 0) {
            $import = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                $name = Str::of($genTableColumn->_foreign_table)->singular()->camel()->toString();
                return "import { {$name}Select } from '@/api/{$name}Api'";
            })->merge($import);
            $dataFormColumns = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                return Str::of($genTableColumn->_foreign_table)->singular()->camel()->append('SelectData: []')->toString();
            })->merge($dataFormColumns);
            $init = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                return 'this.get' . Str::of($genTableColumn->_foreign_table)->singular()->studly()->append('Select()')->toString();
            })->merge($init);
            $methods = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                $name = Str::of($genTableColumn->_foreign_table)->singular()->studly()->toString();
                $method = Str::of($genTableColumn->_foreign_table)->singular()->camel()->toString();
                return 'get' . $name . 'Select() {
      ' . $method . 'Select().then(response => {
        const { select = [] } = response.data
        this.' . $method . 'SelectData = select
      })
    }';
            })->merge($methods);
        }
        $uploads = $createColumns->whereIn('_show', [Gen::TYPE_FILE, Gen::TYPE_IMAGE])->values();
        if ($uploads->count() > 0) {
            $fileImport[] = 'fileRemoveFile';
            $fileUploads = $uploads->where('_show', '=', Gen::TYPE_FILE);
            if ($fileUploads->count() > 0) {
                $fileImport[] = 'fileUploadFile';
                $dataFormColumns = $fileUploads->map(function (GenTableColumn $genTableColumn): string {
                    return Str::of($genTableColumn->name)->append('FileList: []')->toString();
                })->merge($dataFormColumns);
                $methods = $fileUploads->map(function (GenTableColumn $genTableColumn): string {
                    $name = Str::of($genTableColumn->name)->studly()->toString();
                    return 'upload' . $name . 'File(file) {
      const loading = this.$loading({
        lock: true,
        text: \'Loading\',
        spinner: \'el-icon-loading\',
        background: \'rgba(0, 0, 0, 0.7)\'
      })
      const data = new FormData()
      data.append(file.filename, file.file)
      fileUploadFile(data).then(response => {
        const { path = \'\' } = response.data
        this.form.' . $genTableColumn->name . ' = path
        this.$message({ type: \'success\', message: response.message })
      }).finally(_ => {
        loading.close()
      })
    },
    ' . $genTableColumn->name . 'UploadExceed(files, fileList) {
      this.$message({
        type: \'error\',
        message: fileList[0].name + \' \' + this.$t(\'common.alreadyUpload\')
      })
    },
    ' . $genTableColumn->name . 'UploadRemove(file, fileList) {
      fileRemoveFile({
        path: this.form.' . $genTableColumn->name . '
      })
    }';
                })->merge($methods);
                $resetForm = $fileUploads->map(function (GenTableColumn $genTableColumn): string {
                    return 'this.$refs.' . $genTableColumn->name . '.clearFiles()';
                })->merge($resetForm);
            }
            $imageUploads = $uploads->where('_show', '=', Gen::TYPE_IMAGE);
            if ($imageUploads->count() > 0) {
                $fileImport[] = 'fileUploadImage';
                $methods = $imageUploads->map(function (GenTableColumn $genTableColumn): string {
                    $name = Str::of($genTableColumn->name)->studly()->toString();
                    return 'upload' . $name . 'Image(file) {
      const deleteImage = this.form.' . $genTableColumn->name . '
      const loading = this.$loading({
        lock: true,
        text: \'Loading\',
        spinner: \'el-icon-loading\',
        background: \'rgba(0, 0, 0, 0.7)\'
      })
      const data = new FormData()
      data.append(file.filename, file.file)
      fileUploadImage(data).then(response => {
        const { path = \'\' } = response.data
        this.form.' . $genTableColumn->name . ' = path
        this.$message({ type: \'success\', message: response.message })
      }).finally(_ => {
        loading.close()
        this.$refs.' . $genTableColumn->name . '.clearFiles()
        if (deleteImage) {
          fileRemoveFile({
            path: deleteImage
          })
        }
      })
    }';
                })->merge($methods);
                $resetForm = $imageUploads->map(function (GenTableColumn $genTableColumn): string {
                    return 'this.$refs.' . $genTableColumn->name . '.clearFiles()';
                })->merge($resetForm);
            }
            $import[] = "import { " . implode(', ', $fileImport) . " } from '@/api/file'";
        }
        $editor = $createColumns->where('_show', '=', Gen::TYPE_EDITOR)->values();
        if ($editor->count() > 0) {
            $components[] = "WangEditor: () => import('@/components/WangEditor')";
            $resetForm = $editor->map(function (GenTableColumn $genTableColumn): string {
                return 'this.$refs.' . $genTableColumn->name . 'Editor.clear()';
            })->merge($resetForm);
        }

        $import = $import->implode("\n");
        $components = implode(",\n    ", $components);

        $dataForm = $createColumns->map(function (GenTableColumn $genTableColumn): string {
            $default = $genTableColumn->default;
            if (is_null($default)) {
                $default = "''";
            } else {
                if ($genTableColumn->type === 'integer') {
                    $default = "'$default'";
                }
            }
            return "$genTableColumn->name: $default";
        })->implode(",\n        ");

        $dataFormColumns = $dataFormColumns->implode(",\n      ");
        $init = $init->implode("\n      ");
        $methods = $methods->implode(",\n    ");
        $resetForm = $resetForm->implode("\n      ");

        $createVue = str_replace([
            '{{createForm}}', '{{import}}', '{{singular}}', '{{components}}', '{{dataForm}}', '{{dataFormColumns}}', '{{init}}', '{{methods}}', '{{resetForm}}'
        ], [
            $createForm, $import, $singular, $components, $dataForm, $dataFormColumns, $init, $methods, $resetForm
        ], GenTable::getStub('CreateVue'));
        $path = "vue/src/views/{$singular}/create.vue";
        Storage::disk('codes')->put($path, $createVue);
        return true;
    }

    private static function getStub(string $type): string
    {
        return file_get_contents(resource_path('stubs/' . $type . '.stub'));
    }
}
