<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\DictType
 *
 * @property int $id
 * @property string $name 字典名称
 * @property string $type 字典类型
 * @property int $status 状态 1:正常 0:禁止
 * @property string|null $remark 备注
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Activitylog\Models\Activity[] $activities
 * @property-read int|null $activities_count
 * @method static \Illuminate\Database\Eloquent\Builder|DictType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DictType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DictType query()
 * @method static \Illuminate\Database\Eloquent\Builder|DictType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictType whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictType whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictType whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DictType extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'type', 'status', 'remark'
    ];

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('dict_type')
            ->logFillable()
            ->logUnguarded();
    }
}
