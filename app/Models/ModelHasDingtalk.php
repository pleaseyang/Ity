<?php

namespace App\Models;

use AlibabaCloud\SDK\Dingtalk\Vcontact_1_0\Dingtalk as DingTalkV1;
use AlibabaCloud\SDK\Dingtalk\Vcontact_1_0\Models\GetUserHeaders;
use AlibabaCloud\SDK\Dingtalk\Voauth2_1_0\Dingtalk;
use AlibabaCloud\SDK\Dingtalk\Voauth2_1_0\Models\GetAccessTokenRequest;
use AlibabaCloud\SDK\Dingtalk\Voauth2_1_0\Models\GetUserTokenRequest;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use Darabonba\OpenApi\Models\Config as DingTalkConfig;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * App\Models\ModelHasDingtalk
 *
 * @property string $model_type
 * @property int $model_id
 * @property string $userid userid
 * @property string $name 钉钉名称
 * @property string|null $avatar 钉钉头像
 * @property int $admin 是否为管理员
 * @property string|null $email 钉钉邮箱
 * @property string|null $mobile 钉钉手机号
 * @property string $unionid unionid
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasDingtalk newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasDingtalk newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasDingtalk query()
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasDingtalk whereAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasDingtalk whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasDingtalk whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasDingtalk whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasDingtalk whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasDingtalk whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasDingtalk whereModelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasDingtalk whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasDingtalk whereUnionid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasDingtalk whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasDingtalk whereUserid($value)
 * @mixin \Eloquent
 */
class ModelHasDingtalk extends Model
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
        $config = Config::getConfig('dingTalk');
        $redirectUri = $config->where('key', 'redirect_uri')->value('value');
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
     * @throws InvalidArgumentException
     * @throws GuzzleException
     */
    public static function login(string $code, string $state): Admin
    {
        $res = self::checkState($state);
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
                'access_token' => self::getToken($clientId, $clientSecret)
            ],
            RequestOptions::JSON => [
                'unionid' => $response->body->unionId
            ]
        ]);
        $res = Utils::jsonDecode($response->getBody()->getContents(), true);
        $userid = $res['result']['userid'];
        return self::getAdminByUserId($userid, $clientId, $clientSecret);
    }

    public static function checkState(string $state): bool
    {
        try {
            return Cache::store('redis')->has('DingTalk:state:' . $state);
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getToken(string $clientId, string $clientSecret): string
    {
        if (Cache::store('redis')->has('DingTalk:' . $clientId . ':token')) {
            return Cache::store('redis')->get('DingTalk:' . $clientId . ':token');
        } else {
            $config = new DingTalkConfig([]);
            $config->protocol = "https";
            $config->regionId = "central";
            $client = new Dingtalk($config);
            $getAccessTokenRequest = new GetAccessTokenRequest([
                "appKey" => $clientId,
                "appSecret" => $clientSecret,
            ]);
            $response = $client->getAccessToken($getAccessTokenRequest);
            $accessToken = $response->body->accessToken;
            $expireIn = $response->body->expireIn;
            Cache::store('redis')->put('DingTalk:' . $clientId . ':token', $accessToken, $expireIn);
            return $accessToken;
        }
    }

    /**
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private static function getAdminByUserId(string $userid, string $clientId, string $clientSecret): Admin
    {
        $client2 = new Client([
            'base_uri' => 'https://oapi.dingtalk.com',
        ]);
        // 根据userId获取信息
        $response = $client2->request('POST', '/topapi/v2/user/get', [
            RequestOptions::QUERY => [
                'access_token' => self::getToken($clientId, $clientSecret)
            ],
            RequestOptions::JSON => [
                'userid' => $userid
            ]
        ]);
        $res = Utils::jsonDecode($response->getBody()->getContents(), true);
        return Admin::getByDingTalk($res);
    }

    /**
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public static function loginDD(string $code): Admin
    {
        $config = Config::getConfig('dingTalk');
        $clientId = $config->where('key', 'client_id')->value('value');
        $clientSecret = $config->where('key', 'client_secret')->value('value');
        $client2 = new Client([
            'base_uri' => 'https://oapi.dingtalk.com',
        ]);
        $response = $client2->request('POST', '/topapi/v2/user/getuserinfo', [
            RequestOptions::QUERY => [
                'access_token' => self::getToken($clientId, $clientSecret)
            ],
            RequestOptions::JSON => [
                'code' => $code
            ]
        ]);
        $res = Utils::jsonDecode($response->getBody()->getContents(), true);
        $userid = $res['result']['userid'];
        return self::getAdminByUserId($userid, $clientId, $clientSecret);
    }

    public static function dingTalkCorpId(): string
    {
        $config = Config::getConfig('dingTalk');
        return $config->where('key', 'corp_id')->value('value');
    }
}
