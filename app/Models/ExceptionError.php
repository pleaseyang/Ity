<?php

namespace App\Models;

use Carbon\Carbon;
use GuzzleHttp\Utils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * App\Models\ExceptionError
 *
 * @property string $id
 * @property string|null $message
 * @property string $code
 * @property string $file
 * @property int $line
 * @property mixed $trace
 * @property string $trace_as_string
 * @property bool $is_solve 是否解决 0为解决 1已解决
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExceptionError newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExceptionError newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExceptionError query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExceptionError whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExceptionError whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExceptionError whereFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExceptionError whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExceptionError whereIsSolve($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExceptionError whereLine($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExceptionError whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExceptionError whereTrace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExceptionError whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExceptionError whereTraceAsString($value)
 * @mixin \Eloquent
 */
class ExceptionError extends Model
{
    /**
     * 指示模型主键是否递增
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * 自动递增ID的“类型”。
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'message', 'code', 'file', 'line', 'trace', 'trace_as_string', 'is_solve'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'line' => 'integer',
        'is_solve' => 'boolean',
    ];

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        ExceptionError::creating(function ($model) {
            $model->setId();
        });
    }

    /**
     * 设置ID
     */
    public function setId()
    {
        $this->attributes['id'] = Str::orderedUuid();
    }

    public function getId()
    {
        return $this->attributes['id'];
    }

    /**
     * Json转数组
     * @param $value
     * @return mixed
     */
    public function getTraceAttribute($value)
    {
        return Utils::jsonDecode($value, true);
    }

    /**
     * 数字转Json
     * @param $value
     */
    public function setTraceAttribute($value): void
    {
        $this->attributes['trace'] = Utils::jsonEncode($value);
    }

    /**
     * 自定义异常字符串
     *
     * @param $value
     */
    public function setTraceAsStringAttribute($value): void
    {
        $this->attributes['trace_as_string'] =
            '[' . Carbon::now()->format('Y-m-d H:i:s') . '] ' . App::environment() . '.ERROR: '
            . $this->attributes['message']
            . ' at ' . $this->attributes['file'] . ':' . $this->attributes['line']
            . "\n"
            . $value;
    }

    /**
     * 获取列表
     *
     * @param array $validated
     * @return array
     */
    public static function getList(array $validated): array
    {
        $where = [];
        if (isset($validated['is_solve'])) {
            $where[] = ['is_solve', '=', $validated['is_solve']];
        }

        $model = ExceptionError::where($where)
            ->when($validated['id'] ?? null, function ($query) use ($validated) {
                return $query->where('id', 'like', '%' . $validated['id'] . '%');
            })
            ->when($validated['message'] ?? null, function ($query) use ($validated) {
                return $query->where('message', 'like', '%' . $validated['message'] . '%');
            })
            ->when($validated['start_at'] ?? null, function ($query) use ($validated) {
                return $query->whereBetween('created_at', [$validated['start_at'], $validated['end_at']]);
            });


        $total = $model->count('id');

        $logs = $model->select(
            [
            'id',
            'message',
            'code',
            'file',
            'line',
            'trace',
            'trace_as_string',
            'is_solve',
            'created_at',
            'updated_at'
            ]
        )
            ->orderBy($validated['sort'] ?? 'updated_at', $validated['order'] === 'ascending' ? 'asc' : 'desc')
            ->offset(($validated['offset'] - 1) * $validated['limit'])
            ->limit($validated['limit'])
            ->get();


        return [
            'logs' => $logs,
            'total' => $total
        ];
    }

    /**
     * 修正错误信息
     */
    public function solve(): void
    {
        $this->is_solve = 1;
        $this->save();
        activity()
            ->useLog('exception')
            ->performedOn($this)
            ->causedBy(Auth::guard('admin')->user())
            ->log('The :subject.id exception amended by :causer.name');
    }
}
