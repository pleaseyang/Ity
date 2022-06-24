<?php

namespace App\Models;

use App\Util\Gen;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\GenTableColumn
 *
 * @property int $id
 * @property int $gen_table_id
 * @property string $name 名
 * @property string $type 类型
 * @property int $precision 长度
 * @property int $scale 小数点
 * @property bool $notnull 不是NULL 1:是 0:否
 * @property bool $primary 主键 1:是 0:否
 * @property string|null $comment 注释
 * @property string|null $default 默认值
 * @property bool $autoincrement 自动递增 1:是 0:否
 * @property bool $unsigned 无符号 1:是 0:否
 * @property bool $_insert 新增 1:是 0:否
 * @property bool $_update 更新 1:是 0:否
 * @property bool $_list 列表 1:是 0:否
 * @property bool $_select 查询 1:是 0:否
 * @property string $_query 查询方式 1:是 0:否
 * @property bool $_required 必填 1:是 0:否
 * @property string $_show 新增类型
 * @property string $_validate 验证类型
 * @property int|null $dict_type_id 字典
 * @property bool $_unique 唯一 1:是 0:否
 * @property bool $_foreign 外键 1:是 0:否
 * @property string|null $_foreign_table 外键表
 * @property string|null $_foreign_column 外键字段
 * @property string|null $_foreign_show 外键显示字段
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Activitylog\Models\Activity[] $activities
 * @property-read int|null $activities_count
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn query()
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereAutoincrement($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereDictTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereForeign($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereForeignColumn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereForeignShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereForeignTable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereGenTableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereInsert($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereList($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereNotnull($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn wherePrecision($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn wherePrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereQuery($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereScale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereSelect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereUnique($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereUnsigned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereUpdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GenTableColumn whereValidate($value)
 * @mixin \Eloquent
 */
class GenTableColumn extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'gen_table_id', 'name', 'type', 'precision', 'scale', 'notnull', 'primary', 'comment',
        'default', 'autoincrement', 'unsigned', '_insert', '_update', '_list', '_select', '_query',
        '_required', '_show', 'dict_type_id', '_validate', '_unique', '_foreign', '_foreign_table', '_foreign_column',
        '_foreign_show'
    ];


    protected $casts = [
        'notnull' => 'boolean',
        'primary' => 'boolean',
        'autoincrement' => 'boolean',
        'unsigned' => 'boolean',
        '_insert' => 'boolean',
        '_update' => 'boolean',
        '_list' => 'boolean',
        '_select' => 'boolean',
        '_required' => 'boolean',
        '_unique' => 'boolean',
        '_foreign' => 'boolean',
    ];

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('gen_table_column')
            ->logFillable()
            ->logUnguarded();
    }

    /**
     * @param DictType $dictType
     * @return $this
     */
    public function setDict(DictType $dictType): GenTableColumn
    {
        $default = DictData::whereDictTypeId($dictType->id)
            ->where('status', 1)
            ->where('default', 1)
            ->value('value');
        $this->default = $default;
        $this->_query = Gen::SELECT_EQ;
        $this->_show = Gen::TYPE_SELECT;
        $this->_validate = 'string';
        $this->dict_type_id = $dictType->id;
        $this->_select = true;
        $this->_unique = false;
        $this->_foreign = false;
        $this->_foreign_table = null;
        $this->_foreign_column = null;
        $this->_foreign_show = null;
        $this->save();
        return $this;
    }

    /**
     * @return $this
     */
    public function setType(string $type): GenTableColumn
    {
        $this->_show = $type;
        if (in_array($type, [Gen::TYPE_FILE, Gen::TYPE_EDITOR, Gen::TYPE_IMAGE])) {
            $this->_select = false;
            $this->_validate = 'string';
            $this->dict_type_id = null;
            $this->_unique = false;
            if ($type === Gen::TYPE_EDITOR) {
                $this->_list = false;
            }
        }
        $this->save();
        return $this;
    }

    /**
     * 设置外键显示字段
     *
     * @param string[] $columns
     * @return $this
     * @throws Exception
     */
    public function setForeignShow(array $columns): GenTableColumn
    {
        if (!$this->_foreign) {
            throw new Exception("不存在对应的主键");
        }

        $foreignColumns = Gen::getTableInfo($this->_foreign_table);
        $foreignColumns = array_map(fn(array $column): string => $column['name'], $foreignColumns['columns']);
        $errorColumns = array_filter($columns, fn(string $column): bool => !in_array($column, $foreignColumns));
        if (count($errorColumns) > 0) {
            $errorColumnString = implode(', ', $errorColumns);
            throw new Exception("字段:$errorColumnString 不在 $this->_foreign_table 表内");
        }

        $this->_show = Gen::TYPE_SELECT;
        $this->_foreign_show = implode(',', $columns);
        $this->save();

        return $this;
    }
}
