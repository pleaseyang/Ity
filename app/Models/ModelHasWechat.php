<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ModelHasWechat
 *
 * @property string $model_type
 * @property int $model_id
 * @property string $unionid unionid
 * @property string $nickname 微信名称
 * @property string|null $headimgurl 微信头像
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasWechat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasWechat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasWechat query()
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasWechat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasWechat whereHeadimgurl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasWechat whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasWechat whereModelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasWechat whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasWechat whereUnionid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasWechat whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ModelHasWechat extends Model
{
    use HasFactory;

    /**
     * 主键是否主动递增
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'model_type', 'model_id', 'userid', 'name', 'avatar', 'admin', 'email', 'mobile', 'unionid'
    ];
}
