<?php


namespace App\Models;


/**
 * App\Models\Activity
 *
 * @property int $id
 * @property string|null $log_name
 * @property string $description
 * @property string|null $subject_id
 * @property string|null $subject_type
 * @property string|null $causer_id
 * @property string|null $causer_type
 * @property \Illuminate\Support\Collection|null $properties
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $causer
 * @property-read \Illuminate\Support\Collection $changes
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $subject
 * @method static \Illuminate\Database\Eloquent\Builder|\Spatie\Activitylog\Models\Activity causedBy(\Illuminate\Database\Eloquent\Model $causer)
 * @method static \Illuminate\Database\Eloquent\Builder|\Spatie\Activitylog\Models\Activity forSubject(\Illuminate\Database\Eloquent\Model $subject)
 * @method static \Illuminate\Database\Eloquent\Builder|\Spatie\Activitylog\Models\Activity inLog($logNames)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Activity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Activity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Activity query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Activity whereCauserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Activity whereCauserType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Activity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Activity whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Activity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Activity whereLogName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Activity whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Activity whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Activity whereSubjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Activity whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Activity extends \Spatie\Activitylog\Models\Activity
{

    protected $hidden = [
        'id', 'updated_at',
    ];

    /**
     * 获取列表
     *
     * @param array $validated
     * @return array
     */
    public static function getList(array $validated): array
    {
        $where = [];

        $model = Activity::where($where)
            ->when($validated['log_name'] ?? null, function ($query) use ($validated) {
                return $query->where('log_name', 'like', '%' . $validated['log_name'] . '%');
            })
            ->when($validated['description'] ?? null, function ($query) use ($validated) {
                return $query->where('description', 'like', '%' . $validated['description'] . '%');
            })
            ->when($validated['subject_id'] ?? null, function ($query) use ($validated) {
                return $query->where('subject_id', '=', $validated['subject_id']);
            })
            ->when($validated['subject_type'] ?? null, function ($query) use ($validated) {
                return $query->where('subject_type', '=', $validated['subject_type']);
            })
            ->when($validated['causer_id'] ?? null, function ($query) use ($validated) {
                return $query->where('causer_id', '=', $validated['causer_id']);
            })
            ->when($validated['causer_type'] ?? null, function ($query) use ($validated) {
                return $query->where('causer_type', '=', $validated['causer_type']);
            })
            ->when($validated['properties'] ?? null, function ($query) use ($validated) {
                return $query->where('properties', 'like', '%' . $validated['properties'] . '%');
            })
            ->when($validated['start_at'] ?? null, function ($query) use ($validated) {
                return $query->whereBetween('created_at', [$validated['start_at'], $validated['end_at']]);
            });

        $total = $model->count('id');

        $logs = $model->select([
            'id', 'log_name', 'description', 'subject_id', 'subject_type', 'causer_id', 'causer_type',
            'properties', 'created_at'])
            ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] === 'ascending' ? 'asc' : 'desc')
            ->offset(($validated['offset'] - 1) * $validated['limit'])
            ->limit($validated['limit'])
            ->get();

        $logNames = Activity::whereNotNull('log_name')->groupBy('log_name')->pluck('log_name')->toArray();
        $subjectType = Activity::whereNotNull('subject_type')
            ->groupBy('subject_type')->pluck('subject_type')->toArray();
        $causerType = Activity::whereNotNull('causer_type')->groupBy('causer_type')->pluck('causer_type')->toArray();

        return [
            'logs' => $logs,
            'total' => $total,
            'log_name' => $logNames,
            'subject_type' => $subjectType,
            'causer_type' => $causerType,
        ];
    }
}
