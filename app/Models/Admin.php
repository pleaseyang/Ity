<?php

namespace App\Models;

use AlibabaCloud\SDK\Dingtalk\Vcontact_1_0\Dingtalk as DingTalkV1;
use AlibabaCloud\SDK\Dingtalk\Vcontact_1_0\Models\GetUserHeaders;
use AlibabaCloud\SDK\Dingtalk\Voauth2_1_0\Dingtalk;
use AlibabaCloud\SDK\Dingtalk\Voauth2_1_0\Models\GetUserTokenRequest;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use App\Util\FunctionReturn;
use Carbon\Carbon;
use Darabonba\OpenApi\Models\Config as DingTalkConfig;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as Query;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Psr\SimpleCache\InvalidArgumentException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use stdClass;

/**
 * App\Models\Admin
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Activitylog\Models\Activity[] $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Permission\Models\Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Permission\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin permission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin role($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Admin whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int $status 状态 1:正常 2:禁止
 * @method static Builder|Admin whereStatus($value)
 * @property string $theme 主题色
 * @property int $tags_view 开启 Tags-View 0关闭 1开启
 * @property int $fixed_header 固定 Header 0关闭 1开启
 * @property int $sidebar_logo 侧边栏 Logo 0关闭 1开启
 * @property int $support_pinyin_search 菜单支持拼音搜索 0关闭 1开启
 * @method static Builder|Admin whereFixedHeader($value)
 * @method static Builder|Admin whereSidebarLogo($value)
 * @method static Builder|Admin whereSupportPinyinSearch($value)
 * @method static Builder|Admin whereTagsView($value)
 * @method static Builder|Admin whereTheme($value)
 */
class Admin extends Authenticatable implements JWTSubject
{
    use Notifiable, HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'status',
        'theme', 'tags_view', 'fixed_header', 'sidebar_logo', 'support_pinyin_search'
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * 获取列表
     *
     * @param array $validated
     * @return array
     */
    public static function getList(array $validated): array
    {
        $model = DB::table(function (Query $query) use ($validated) {
            $query->from('admins')
                ->groupBy('admins.id')
                ->join('model_has_roles', function (JoinClause $join) {
                    $join->on('admins.id', '=', 'model_has_roles.model_id')
                        ->where('model_type', '=', 'App\\Models\\Admin');
                }, null, null, 'left')
                ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->when($validated['name'] ?? null, function (Query $query) use ($validated): Query {
                    return $query->where('admins.name', 'like', '%' . $validated['name'] . '%');
                })
                ->when($validated['email'] ?? null, function (Query $query) use ($validated): Query {
                    return $query->where('admins.email', 'like', '%' . $validated['email'] . '%');
                })
                ->when(isset($validated['status']) && is_numeric($validated['status']), function (Query $query) use ($validated): Query {
                    return $query->where('admins.status', '=', $validated['status']);
                })
                ->when($validated['start_at'] ?? null, function (Query $query) use ($validated): Query {
                    return $query->whereBetween('admins.created_at', [$validated['start_at'], $validated['end_at']]);
                })
                ->when(
                    isset($validated['role_ids']) && count($validated['role_ids']),
                    function (Query $query) use ($validated): Query {
                        $roleIds = implode('|', $validated['role_ids']);
                        return $query->havingRaw("CONCAT (',',role_ids,',') REGEXP ',({$roleIds}),'");
                    }
                )->select([
                    'admins.id',
                    'admins.name',
                    'admins.email',
                    DB::raw(' GROUP_CONCAT(roles.id) as role_ids'),
                    DB::raw(' GROUP_CONCAT(roles.name) as role_names'),
                    'admins.status',
                    'admins.created_at',
                    'admins.updated_at',
                ]);
        }, 'admins');

        $total = $model->count('id');

        $admins = $model
            ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] === 'ascending' ? 'asc' : 'desc')
            ->offset(($validated['offset'] - 1) * $validated['limit'])
            ->limit($validated['limit'])
            ->get()
            ->map(function (stdClass $admin): stdClass {
                $admin->role_ids = is_string($admin->role_ids) ? explode(',', $admin->role_ids) : [];
                $admin->role_names = is_string($admin->role_names) ? explode(',', $admin->role_names) : [];
                return $admin;
            });

        return [
            'admins' => $admins,
            'total' => $total
        ];
    }

    /**
     * 创建
     *
     * @param array $attributes
     * @return Admin
     */
    public static function create(array $attributes): Admin
    {
        $attributes['password'] = Hash::make($attributes['password']);
        return static::query()->create($attributes);
    }

    /**
     * 更新
     *
     * @param array $data
     * @return array
     */
    public static function updateSave(array $data): FunctionReturn
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $admin = Admin::find($data['id']);
        unset($data['id']);

        return new FunctionReturn($admin->update($data), '', [
            'admin' => $admin
        ]);
    }

    public static function selectAll(): Collection
    {
        return Admin::select(['id', 'name'])->get();
    }

    /**
     * @throws Exception
     */
    public static function getByDingTalk(array $user): Admin
    {
        $dingTalk = ModelHasDingtalk::whereUserid($user['result']['userid'])
            ->where('model_type', Admin::class)
            ->first();
        if ($dingTalk === null) {
            throw new Exception(__('auth.ding_talk.login_failed'));
        }
        ModelHasDingtalk::whereUserid($user['result']['userid'])
            ->where('model_type', Admin::class)
            ->update([
                'name' => $user['result']['name'],
                'avatar' => $user['result']['avatar'],
                'admin' => $user['result']['admin'] ? 1 : 0,
                'email' => $user['result']['email'] === '' ? null : $user['result']['email'],
                'mobile' => $user['result']['mobile'] === '' ? null : $user['result']['mobile'],
                'unionid' => $user['result']['unionid'],
                'updated_at' => Carbon::now(),
            ]);
        return Admin::find($dingTalk->model_id);
    }

    public static function bindDingTalkUrl(): string
    {
        $config = Config::getConfig('dingTalk');
        $redirectUri = $config->where('key', 'redirect_bind_uri')->value('value');
        $redirectUri = urlencode($redirectUri);
        $clientId = $config->where('key', 'client_id')->value('value');
        $corpId = $config->where('key', 'corp_id')->value('value');
        $state = Str::uuid()->toString();
        Cache::store('redis')->put('DingTalk:state:' . $state, '1', 300);
        return 'https://login.dingtalk.com/oauth2/auth?redirect_uri=' . $redirectUri
            . '&response_type=code&client_id=' . $clientId . '&scope=openid corpid&state=' . $state
            . '&org_type=management&corpId=' . $corpId . '&prompt=consent&exclusiveLogin=true';
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function bindDingTalk(string $code, string $state): void
    {
        $res = ModelHasDingtalk::checkState($state);
        if ($res === false) {
            throw new Exception(__('auth.state_not_exists'));
        }
        Cache::store('redis')->forget('DingTalk:state:' . $state);
        $config = Config::getConfig('dingTalk');
        $clientId = $config->where('key', 'client_id')->value('value');
        $clientSecret = $config->where('key', 'client_secret')->value('value');
        // 获取个人token
        $config = new DingTalkConfig([]);
        $config->protocol = "https";
        $config->regionId = "central";
        $client = new Dingtalk($config);
        $getAccessTokenRequest = new GetUserTokenRequest([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'code' => $code,
            'grantType' => 'authorization_code'
        ]);
        $response = $client->getUserToken($getAccessTokenRequest);
        // 获取unionId
        $getUserHeaders = new GetUserHeaders([]);
        $getUserHeaders->xAcsDingtalkAccessToken = $response->body->accessToken;
        $client1 = new DingTalkV1($config);
        $response = $client1->getUserWithOptions("me", $getUserHeaders, new RuntimeOptions([]));
        // 根据unionId获取userId
        $client2 = new Client([
            'base_uri' => 'https://oapi.dingtalk.com',
        ]);
        $response = $client2->request('POST', '/topapi/user/getbyunionid', [
            RequestOptions::QUERY => [
                'access_token' => ModelHasDingtalk::getToken($clientId, $clientSecret)
            ],
            RequestOptions::JSON => [
                'unionid' => $response->body->unionId
            ]
        ]);
        $res = Utils::jsonDecode($response->getBody()->getContents(), true);
        $userid = $res['result']['userid'];
        // 根据userId获取信息
        $response = $client2->request('POST', '/topapi/v2/user/get', [
            RequestOptions::QUERY => [
                'access_token' => ModelHasDingtalk::getToken($clientId, $clientSecret)
            ],
            RequestOptions::JSON => [
                'userid' => $userid
            ]
        ]);
        $res = Utils::jsonDecode($response->getBody()->getContents(), true);
        $dingTalk = ModelHasDingtalk::whereUserid($res['result']['userid'])
            ->where('model_type', Admin::class)
            ->first();
        if ($dingTalk !== null) {
            throw new Exception(__('auth.ding_talk.bind_failed'));
        }
        ModelHasDingtalk::insert([
            'model_type' => Admin::class,
            'model_id' => $this->id,
            'userid' => $res['result']['userid'],
            'name' => $res['result']['name'],
            'avatar' => $res['result']['avatar'],
            'admin' => $res['result']['admin'] ? 1 : 0,
            'email' => $res['result']['email'] === '' ? null : $res['result']['email'],
            'mobile' => $res['result']['mobile'] === '' ? null : $res['result']['mobile'],
            'unionid' => $res['result']['unionid'],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public static function bindWechatUrl(): array
    {
        $config = Config::getConfig('wechat');
        $redirectUri = $config->where('key', 'oplatform_redirect_uri')->value('value');
        $redirectUri = $redirectUri . '/#/profile/index';
        $redirectUri = urlencode($redirectUri);
        $appid = $config->where('key', 'oplatform_appid')->value('value');
        $state = Str::uuid()->toString();
        Cache::store('redis')->put('Wechat:state:' . $state, '1', 300);
        return [
            'url' => 'https://open.weixin.qq.com/connect/qrconnect?appid=' . $appid . '&redirect_uri=' . $redirectUri .
                '&response_type=code&scope=snsapi_login&state=' . $state . '#wechat_redirect',
            'appid' => $appid,
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ];
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function bindWechat(string $code, string $state, string $type): void
    {
        $res = ModelHasWechat::checkState($state);
        if ($res === false) {
            throw new Exception(__('auth.state_not_exists'));
        }
        Cache::store('redis')->forget('Wechat:state:' . $state);
        $config = Config::getConfig('wechat');
        $appid = $config->where('key', $type . '_appid')->value('value');
        $secret = $config->where('key', $type . '_appsecret')->value('value');

        // 第二步：通过 code 获取access_token
        $client2 = new Client([
            'base_uri' => 'https://api.weixin.qq.com',
        ]);
        $response = $client2->request('GET', '/sns/oauth2/access_token', [
            RequestOptions::QUERY => [
                'appid' => $appid,
                'secret' => $secret,
                'code' => $code,
                'grant_type' => 'authorization_code',
            ]
        ]);
        $res = Utils::jsonDecode($response->getBody()->getContents(), true);
        if (isset($res['errcode'])) {
            throw new Exception($res['errmsg']);
        }

        // 获取用户个人信息（UnionID机制）
        $response = $client2->request('GET', '/sns/userinfo', [
            RequestOptions::QUERY => [
                'access_token' => $res['access_token'],
                'openid' => $res['openid'],
            ]
        ]);
        $res = Utils::jsonDecode($response->getBody()->getContents(), true);
        if (isset($res['errcode'])) {
            throw new Exception($res['errmsg']);
        }

        $wechat = ModelHasWechat::whereUnionid($res['unionid'])
            ->where('model_type', Admin::class)
            ->first();
        if ($wechat !== null) {
            throw new Exception(__('auth.wechat.bind_failed'));
        }
        ModelHasWechat::insert([
            'model_type' => Admin::class,
            'model_id' => $this->id,
            'unionid' => $res['unionid'],
            'nickname' => $res['nickname'],
            'headimgurl' => $res['headimgurl'] === '' ? null : $res['headimgurl'],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('admin')
            ->logFillable()
            ->logUnguarded();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return ['role' => 'admin'];
    }
}
