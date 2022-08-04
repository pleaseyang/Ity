<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;
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

    public static function list(array $validated): array
    {
        $model = DictData::whereDictTypeId($validated['dict_type_id'])
            ->when($validated['label'] ?? null, function (Builder $builder) use ($validated): Builder {
                return $builder->where('label', 'like', '%' . $validated['label'] . '%');
            })->when($validated['value'] ?? null, function (Builder $builder) use ($validated): Builder {
                return $builder->where('value', 'like', '%' . $validated['value'] . '%');
            })->when(isset($validated['default']) && is_numeric($validated['default']), function (Builder $builder) use ($validated): Builder {
                return $builder->where('default', '=', $validated['default']);
            })->when(isset($validated['status']) && is_numeric($validated['status']), function (Builder $builder) use ($validated): Builder {
                return $builder->where('status', '=', $validated['status']);
            })->when($validated['start_at'] ?? null, function (Builder $builder) use ($validated): Builder {
                return $builder->whereBetween('created_at', [$validated['start_at'], $validated['end_at']]);
            });

        $total = $model->count('id');

        $data = $model->select([
            'id', 'dict_type_id', 'sort', 'label', 'value', 'list_class', 'default', 'status', 'remark',
            'created_at', 'updated_at'
        ])
            ->orderByDesc('default')
            ->orderByDesc('sort')
            ->offset(($validated['offset'] - 1) * $validated['limit'])
            ->limit($validated['limit'])
            ->get();

        return [
            'data' => $data,
            'total' => $total
        ];
    }

    public static function setDefault(DictData $data): void
    {
        DictData::whereDictTypeId($data->dict_type_id)
            ->where('id', '!=', $data->id)
            ->update([
                'default' => 0,
                'updated_at' => now()
            ]);
    }

    public static function selectAll(): Collection
    {
        try {
            $data = Cache::store('redis')->get('DictData', collect([]));
        } catch (InvalidArgumentException) {
            $data = collect([]);
        }
        if ($data->count() === 0) {
            $data = DictData::leftJoin(
                'dict_types',
                'dict_types.id',
                '=',
                'dict_data.dict_type_id'
            )->where(
                'dict_types.status', '=', 1
            )->where(
                'dict_data.status', '=', 1
            )->select([
                'dict_data.dict_type_id',
                'dict_data.sort',
                'dict_data.label',
                'dict_data.value',
                'dict_data.list_class',
                'dict_data.default'
            ])->get();
            Cache::store('redis')->put('DictData', $data);
        }

        return $data;
    }

    public static function forgetRedis(): void
    {
        Cache::store('redis')->forget('DictData');
    }
}
