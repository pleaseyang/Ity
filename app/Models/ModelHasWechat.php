<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;

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
class ModelHasWechat extends BaseModel
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
        'model_type', 'model_id', 'unionid', 'nickname', 'headimgurl'
    ];

    public static function loginUrl(): string
    {
        $config = Config::getConfig('wechat');
        $redirectUri = $config->where('key', 'oplatform_redirect_uri')->value('value');
        $redirectUri = $redirectUri . '/#/login';
        $redirectUri = urlencode($redirectUri);
        $appid = $config->where('key', 'oplatform_appid')->value('value');
        $state = Str::uuid()->toString();
        Cache::store('redis')->put('Wechat:state:' . $state, '1', 300);
        return 'https://open.weixin.qq.com/connect/qrconnect?appid=' . $appid . '&redirect_uri=' . $redirectUri .
            '&response_type=code&scope=snsapi_login&state=' . $state . '#wechat_redirect';
    }

    public static function loginUrlOffiaccount(): string
    {
        $config = Config::getConfig('wechat');
        $redirectUri = $config->where('key', 'offiaccount_redirect_uri')->value('value');
        $redirectUri = $redirectUri . '/#/login';
        $redirectUri = urlencode($redirectUri);
        $appid = $config->where('key', 'offiaccount_appid')->value('value');
        $state = Str::uuid()->toString();
        Cache::store('redis')->put('Wechat:state:' . $state, '1', 300);
        return 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . $redirectUri .
            '&response_type=code&scope=snsapi_userinfo&state=' . $state . '#wechat_redirect';
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public static function login(string $code, string $state, string $type): Admin
    {
        $res = self::checkState($state);
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

        return self::getByWechat($res);
    }

    public static function checkState(string $state): bool
    {
        try {
            return Cache::store('redis')->has('Wechat:state:' . $state);
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * @throws Exception
     */
    private static function getByWechat(array $user): Admin
    {
        $wechat = ModelHasWechat::whereUnionid($user['unionid'])
            ->where('model_type', Admin::class)
            ->first();
        if ($wechat === null) {
            throw new Exception(__('auth.wechat.login_failed'));
        }
        ModelHasWechat::whereUnionid($user['unionid'])
            ->where('model_type', Admin::class)
            ->update([
                'nickname' => $user['nickname'],
                'headimgurl' => $user['headimgurl'] === '' ? null : $user['headimgurl'],
                'updated_at' => Carbon::now(),
            ]);
        return Admin::find($wechat->model_id);
    }
}
