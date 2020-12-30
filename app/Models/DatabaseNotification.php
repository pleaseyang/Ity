<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\DatabaseNotification as DatabaseNotificationModel;
use Illuminate\Support\Str;

/**
 * App\Models\DatabaseNotification
 *
 * @property string $id
 * @property string $type
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property array $data
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $notifiable
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection|static[] all($columns = ['*'])
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection|static[] get($columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseNotification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseNotification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseNotification query()
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseNotification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseNotification whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseNotification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseNotification whereNotifiableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseNotification whereNotifiableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseNotification whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseNotification whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatabaseNotification whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static Builder|DatabaseNotification read()
 * @method static Builder|DatabaseNotification unread()
 */
class DatabaseNotification extends DatabaseNotificationModel
{
    use HasFactory;

    /**
     * 获取列表
     *
     * @param $guard
     * @param array $validated
     * @return array
     */
    public static function getList($guard, array $validated): array
    {
        $model = DatabaseNotification::whereNotifiableType(get_class($guard))
            ->whereNotifiableId($guard->id)
            ->when($validated['message'] ?? null, function ($query) use ($validated) {
                return $query->where('data', 'like', '%' . $validated['message'] . '%');
            })
            ->when(isset($validated['is_read']), function ($query) use ($validated) {
                if ($validated['is_read'] === 0) {
                    return $query->whereNull('read_at');
                } else {
                    return $query->whereNotNull('read_at');
                }
            })
            ->when($validated['start_at'] ?? null, function ($query) use ($validated) {
                return $query->whereBetween('created_at', [$validated['start_at'], $validated['end_at']]);
            });

        $total = $model->count('id');

        $notifications = $model->select([
                'id',
                'data',
                'read_at',
                'created_at',
                'updated_at'
            ])
            ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] === 'ascending' ? 'asc' : 'desc')
            ->offset(($validated['offset'] - 1) * $validated['limit'])
            ->limit($validated['limit'])
            ->get()
            ->map(function ($notification) {
                // 只保留KEY值 去除HTML标签 最多100个字符 拼接...
                $data = preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/", " ", strip_tags(implode(' ', $notification->data)));
                $notification->data = Str::limit($data, 100, '...');
                return $notification;
            });

        return [
            'notifications' => $notifications,
            'total' => $total
        ];
    }
}
