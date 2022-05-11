<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\DictData
 *
 * @property int $id
 * @property int $dict_type_id
 * @property int $sort 字典排序
 * @property string $label 字典标签
 * @property string $value 字典键值
 * @property string|null $list_class 表格回显样式
 * @property int $default 是否默认 1:是 0:否
 * @property int $status 状态 1:正常 0:禁止
 * @property string|null $remark 备注
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Activitylog\Models\Activity[] $activities
 * @property-read int|null $activities_count
 * @method static \Illuminate\Database\Eloquent\Builder|DictData newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DictData newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DictData query()
 * @method static \Illuminate\Database\Eloquent\Builder|DictData whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictData whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictData whereDictTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictData whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictData whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictData whereListClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictData whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictData whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictData whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictData whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictData whereValue($value)
 * @mixin \Eloquent
 */
class DictData extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dict_type_id', 'sort', 'label', 'value', 'list_class', 'default', 'status', 'remark'
    ];

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('dict_data')
            ->logFillable()
            ->logUnguarded();
    }
}
