<?php

namespace App\Models;


use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\Role
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Activitylog\Models\Activity[] $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Permission\Models\Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Spatie\Permission\Models\Role permission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Role extends \Spatie\Permission\Models\Role
{
    use LogsActivity;

    protected static $logName = 'role';

    protected static $logUnguarded = true;

    /**
     * 获取列表
     *
     * @param array $validated
     * @return array
     */
    public static function getList(array $validated): array
    {
        $where[] = ['guard_name', '=', $validated['guard_name']];

        $model = Role::where($where)
            ->when($validated['name'] ?? null, function ($query) use ($validated) {
                return $query->where('name', 'like', '%' . $validated['name'] . '%');
            })
            ->when($validated['start_at'] ?? null, function ($query) use ($validated) {
                return $query->whereBetween('created_at', [$validated['start_at'], $validated['end_at']]);
            });

        $total = $model->count('id');

        $roles = $model->select(['id', 'name', 'guard_name', 'created_at', 'updated_at'])
            ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] === 'ascending' ? 'asc' : 'desc')
            ->offset(($validated['offset'] - 1) * $validated['limit'])
            ->limit($validated['limit'])
            ->get();

        return [
            'roles' => $roles,
            'total' => $total
        ];
    }

    /**
     * 获取全部角色
     *
     * @param array $validated
     * @return array
     */
    public static function getAllRoles(array $validated) : array
    {
        $where[] = ['guard_name', '=', $validated['guard_name']];

        return Role::where($where)->select(['id', 'name'])->get()->toArray();
    }
}
