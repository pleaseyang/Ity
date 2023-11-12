<?php

namespace App\Models;

use AlibabaCloud\SDK\Dingtalk\Voauth2_1_0\Dingtalk;
use AlibabaCloud\SDK\Dingtalk\Voauth2_1_0\Models\GetAccessTokenRequest;
use Darabonba\OpenApi\Models\Config as DingTalkConfig;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use OSS\Core\OssException;
use OSS\OssClient;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use WeChatPay\Builder;
use WeChatPay\ClientDecoratorInterface;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Crypto\Rsa;
use ZipArchive;

/**
 * App\Models\Config
 *
 * @property string $type 类型
 * @property string $key 键
 * @property string $value 值
 * @method static \Illuminate\Database\Eloquent\Builder|Config newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Config newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Config query()
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereValue($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Activitylog\Models\Activity[] $activities
 * @property-read int|null $activities_count
 * @mixin \Eloquent
 */
class Config extends BaseModel
{
    use HasFactory, LogsActivity;

    /**
     * 主键是否主动递增
     *
     * @var bool
     */
    public $incrementing = false;
    /**
     * 是否主动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type', 'key', 'value'
    ];

    public static function aliOssConfig(array $data): Collection
    {
        try {
            $ossClient = new OssClient($data['access_key_id'], $data['access_key_secret'], $data['endpoint']);
            $res = $ossClient->doesBucketExist($data['bucket_name']);
            if ($res) {
                return self::setConfig($data, 'aliOss');
            }
            throw new Exception(__('message.config.aliOss.bucket_not_exists'));
        } catch (OssException $e) {
            throw new Exception(__('message.config.aliOss.fail', ['message' => $e->getMessage()]));
        }
    }

    public static function setConfig(array $data, string $type): Collection
    {
        Config::whereType($type)->delete();
        Cache::store('redis')->forget('Config:' . $type);
        $insert = [];
        foreach ($data as $key => $value) {
            $insert[] = [
                'type' => $type,
                'key' => $key,
                'value' => $value,
            ];
        }
        Config::insert($insert);
        return new Collection($insert);
    }

    /**
     * @throws Exception
     */
    public static function dingTalkConfig(array $data): Collection
    {
        if ($data['open'] === true) {
            // 验证
            $config = new DingTalkConfig([]);
            $config->protocol = "https";
            $config->regionId = "central";
            $client = new Dingtalk($config);
            $getAccessTokenRequest = new GetAccessTokenRequest([
                "appKey" => $data['client_id'],
                "appSecret" => $data['client_secret'],
            ]);
            try {
                $client->getAccessToken($getAccessTokenRequest);
                return self::setConfig($data, 'dingTalk');
            } catch (Exception $exception) {
                throw new Exception(__('message.config.dingTalk.fail', ['message' => $exception->getMessage()]));
            }
        } else {
            return self::setConfig($data, 'dingTalk');
        }
    }

    /**
     * @throws Exception
     */
    public static function wechatConfig(array $data): Collection
    {
        if ($data['open'] === true) {
            try {
                // 验证公众号
                $client = new Client([
                    'base_uri' => 'https://api.weixin.qq.com',
                ]);
                $response = $client->request('GET', '/cgi-bin/token', [
                    RequestOptions::QUERY => [
                        'grant_type' => 'client_credential',
                        'appid' => $data['offiaccount_appid'],
                        'secret' => $data['offiaccount_appsecret'],
                    ]
                ]);
                $res = Utils::jsonDecode($response->getBody()->getContents(), true);
                if (isset($res['errmsg'])) {
                    throw new Exception(__('message.config.wechat.fail', ['message' => $res['errmsg']]));
                }
                // 验证开放平台
                $response = $client->request('GET', '/cgi-bin/token', [
                    RequestOptions::QUERY => [
                        'grant_type' => 'client_credential',
                        'appid' => $data['oplatform_appid'],
                        'secret' => $data['oplatform_appsecret'],
                    ]
                ]);
                $res = Utils::jsonDecode($response->getBody()->getContents(), true);
                if (isset($res['errmsg'])) {
                    throw new Exception(__('message.config.wechat.fail', ['message' => $res['errmsg']]));
                }
                return self::setConfig($data, 'wechat');
            } catch (GuzzleException $e) {
                throw new Exception(__('message.config.wechat.fail', ['message' => $e->getMessage()]));
            }
        } else {
            return self::setConfig($data, 'wechat');
        }
    }

    /**
     * @throws Exception
     */
    public static function wechatPayConfig(string $apiV3key, string $zipLocalPath, string $type): Collection
    {
        $data = self::distinguish($apiV3key, $zipLocalPath);
        return self::setConfig($data, $type);
    }

    /**
     * @throws Exception
     */
    private static function distinguish(string $apiV3key, string $zipLocalPath): array
    {
        // 解压ZIP文件夹
        $fileinfo = self::zipHandle($zipLocalPath);

        // 解密商户证书 获取 商户证书序列号、商户号、有效期、证书版本
        $certificate = self::getCertificate(Storage::path('public/' . $fileinfo['filename'] . '/apiclient_cert.pem'));

        // 微信支付平台证书
        $wechatPayCertificate = self::getWechatPayCertificatePath($apiV3key, $certificate, Storage::path('public/' . $fileinfo['filename'] . '/apiclient_key.pem'), Storage::path('public/' . $fileinfo['filename']));
        $wechatPayCertificateInfo = self::getCertificate(Storage::path('public/' . $fileinfo['filename'] . '/' . $wechatPayCertificate['serialNumber'] . '/wechatpay_cert.pem'));

        Storage::deleteDirectory('public/cert');
        Storage::makeDirectory('public/cert');
        Storage::makeDirectory('public/cert/' . $wechatPayCertificateInfo['serialNumber']);
        Storage::copy('public/' . $fileinfo['filename'] . '/apiclient_cert.pem', 'public/cert/apiclient_cert.pem');
        Storage::copy('public/' . $fileinfo['filename'] . '/apiclient_key.pem', 'public/cert/apiclient_key.pem');
        Storage::copy('public/' . $fileinfo['filename'] . '/' . $wechatPayCertificateInfo['serialNumber'] . '/wechatpay_cert.pem', 'public/cert/' . $wechatPayCertificateInfo['serialNumber'] . '/wechatpay_cert.pem');
        Storage::deleteDirectory('public/' . $fileinfo['filename']);
        // 加密商户密钥
        $merchantKey = Crypt::encryptString(Storage::path('public/cert/apiclient_key.pem'));
        $apiV3key = Crypt::encryptString($apiV3key);
        return [
            'api_v3_key' => $apiV3key,
            'merchant_key' => $merchantKey,
            'merchant_cert' => Storage::path('public/cert/apiclient_cert.pem'),
            'merchant_version' => $certificate['version'],
            'merchant_serial_number' => $certificate['serialNumber'],
            'merchant_id' => $certificate['merchantId'],
            'merchant_name' => $certificate['merchantName'],
            'merchant_not_before' => $certificate['notBefore'],
            'merchant_not_after' => $certificate['notAfter'],
            'wechat_pay_cert' => Storage::path('public/cert/' . $wechatPayCertificateInfo['serialNumber'] . '/wechatpay_cert.pem'),
            'wechat_pay_serial_umber' => $wechatPayCertificateInfo['serialNumber'],
            'wechat_pay_not_before' => $wechatPayCertificateInfo['notBefore'],
            'wechat_pay_not_after' => $wechatPayCertificateInfo['notAfter'],
        ];
    }

    /**
     * @throws Exception
     */
    private static function zipHandle(string $zipLocalPath): array
    {
        // 解压ZIP文件夹
        $fileinfo = pathinfo($zipLocalPath);
        $exists = Storage::exists('public/' . $fileinfo['filename']);
        if ($exists === false) {
            Storage::makeDirectory('public/' . $fileinfo['filename']);
        }
        $zip = new ZipArchive();
        if ($zip->open($zipLocalPath)) {
            $zip->extractTo(storage_path('app/public/' . $fileinfo['filename']));
            $zip->close();
        } else {
            throw new Exception(__('message.config.wechatPay.zip_fail'));
        }
        Storage::delete('public/' . $fileinfo['basename']);
        if (!Storage::fileExists('public/' . $fileinfo['filename'] . '/apiclient_cert.pem')) {
            Storage::deleteDirectory('public/' . $fileinfo['filename']);
            throw new Exception(__('message.config.wechatPay.zip_miss_file', ['data' => 'apiclient_cert.pem']));
        }
        if (!Storage::fileExists('public/' . $fileinfo['filename'] . '/apiclient_key.pem')) {
            Storage::deleteDirectory('public/' . $fileinfo['filename']);
            throw new Exception(__('message.config.wechatPay.zip_miss_file', ['data' => 'apiclient_key.pem']));
        }
        return $fileinfo;
    }

    private static function getCertificate(string $certPemPath): array
    {
        $platformCertificateInstance = openssl_x509_read(file_get_contents($certPemPath));
        $info = openssl_x509_parse($platformCertificateInstance);
        $data['version'] = $info['version'];
        $data['serialNumber'] = $info['serialNumberHex'];
        $data['merchantId'] = $info['subject']['CN'];
        $data['merchantName'] = $info['subject']['OU'];
        $data['notBefore'] = date('Y-m-d H:i:s', $info['validFrom_time_t']);;
        $data['notAfter'] = date('Y-m-d H:i:s', $info['validTo_time_t']);
        return $data;
    }

    private static function getWechatPayCertificatePath(string $apiV3key, array $certificate, string $merchantPrivateKeyFilePath, string $outputDir): array
    {
        static $certs = ['any' => null];
        // 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
        $merchantPrivateKeyInstance = Rsa::from('file://' . $merchantPrivateKeyFilePath);
        $instance = Builder::factory([
            'mchid' => $certificate['merchantId'],
            'serial' => $certificate['serialNumber'],
            'privateKey' => $merchantPrivateKeyInstance,
            'certs' => &$certs
        ]);

        /** @var HandlerStack $stack */
        $stack = $instance->getDriver()->select(ClientDecoratorInterface::JSON_BASED)->getConfig('handler');
        // The response middle stacks were executed one by one on `FILO` order.
        $stack->after('verifier', Middleware::mapResponse(self::certsInjector($apiV3key, $certs)), 'injector');
        $stack->before('verifier', Middleware::mapResponse(self::certsRecorder($outputDir, $certs)), 'recorder');

        $instance->chain('v3/certificates')->getAsync()->otherwise(static function ($exception) {
            throw new Exception($exception->getMessage());
        })->wait();

        return $certs;
    }

    public static function getConfig(string $type): Collection
    {
        try {
            $data = Cache::store('redis')->get('Config:' . $type, collect([]));
        } catch (InvalidArgumentException) {
            $data = collect([]);
        }
        if ($data->isEmpty()) {
            $data = Config::whereType($type)->select(['key', 'value'])->get();
            Cache::store('redis')->put('Config:' . $type, $data);
        }
        return $data;
    }

    /**
     * Before `verifier` executing, decrypt and put the platform certificate(s) into the `$certs` reference.
     *
     * @param string $apiv3Key
     * @param array<string,?string> $certs
     *
     * @return callable(ResponseInterface)
     */
    private static function certsInjector(string $apiv3Key, array &$certs): callable
    {
        return static function (ResponseInterface $response) use ($apiv3Key, &$certs): ResponseInterface {
            $body = (string)$response->getBody();
            /** @var object{data:array<object{encrypt_certificate:object{serial_no:string,nonce:string,associated_data:string}}>} $json */
            $json = json_decode($body);
            $data = is_object($json) && isset($json->data) && is_array($json->data) ? $json->data : [];
            array_map(static function ($row) use ($apiv3Key, &$certs) {
                $cert = $row->encrypt_certificate;
                $certs[$row->serial_no] = AesGcm::decrypt($cert->ciphertext, $apiv3Key, $cert->nonce, $cert->associated_data);
                $certs['serialNumber'] = $row->serial_no;
            }, $data);

            return $response;
        };
    }

    /**
     * After `verifier` executed, wrote the platform certificate(s) onto disk.
     *
     * @param string $outputDir
     * @param array<string,?string> $certs
     *
     * @return callable(ResponseInterface)
     */
    private static function certsRecorder(string $outputDir, array &$certs): callable
    {
        return static function (ResponseInterface $response) use ($outputDir, &$certs): ResponseInterface {
            $body = (string)$response->getBody();
            /** @var object{data:array<object{effective_time:string,expire_time:string $body :serial_no:string}>} $json */
            $json = json_decode($body);
            $data = is_object($json) && isset($json->data) && is_array($json->data) ? $json->data : [];
            array_walk($data, static function ($row, $index, $certs) use ($outputDir) {
                $serialNo = $row->serial_no;
                mkdir($outputDir . '/' . $serialNo);
                $outpath = $outputDir . '/' . $serialNo . '/wechatpay_cert.pem';
                file_put_contents($outpath, $certs[$serialNo]);
            }, $certs);

            return $response;
        };
    }

    /**
     * @throws Exception
     */
    public static function wechatPayCheck(string $zipLocalPath): array
    {
        // 解压ZIP文件夹
        $fileinfo = self::zipHandle($zipLocalPath);
        // 解密商户证书 获取 商户证书序列号、商户号、有效期、证书版本
        $certificate = self::getCertificate(Storage::path('public/' . $fileinfo['filename'] . '/apiclient_cert.pem'));
        Storage::deleteDirectory('public/' . $fileinfo['filename']);
        return $certificate;
    }

    public static function wechatPayTest(string $appid, string $notifyUrl): string
    {
        $wechatPayConfig = Config::getConfig('wechatPay');
        // 商户号
        $merchantId = $wechatPayConfig->where('key', 'merchant_id')->value('value');
        // 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
        $merchantPrivateKeyFilePath = 'file://' . Crypt::decryptString($wechatPayConfig->where('key', 'merchant_key')->value('value'));
        $merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath);

        // 「商户API证书」的「证书序列号」
        $merchantCertificateSerial = $wechatPayConfig->where('key', 'merchant_serial_number')->value('value');

        // 从本地文件中加载「微信支付平台证书」，用来验证微信支付应答的签名
        $platformCertificateFilePath = 'file://' . $wechatPayConfig->where('key', 'wechat_pay_cert')->value('value');
        $platformPublicKeyInstance = Rsa::from($platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);

        // 从「微信支付平台证书」中获取「证书序列号」
        $platformCertificateSerial = $wechatPayConfig->where('key', 'wechat_pay_serial_umber')->value('value');


        $instance = Builder::factory([
            'mchid' => $merchantId,
            'serial' => $merchantCertificateSerial,
            'privateKey' => $merchantPrivateKeyInstance,
            'certs' => [
                $platformCertificateSerial => $platformPublicKeyInstance,
            ],
        ]);

        $attach = [
            'type' => 'test',
            'notifyUrl' => urlencode($notifyUrl)
        ];

        $json = [
            'appid' => $appid,
            'mchid' => $merchantId,
            'description' => '本次支付为测试支付，实付1分钱。付款后会退回。',
            'out_trade_no' => 'JSB_TEST_' . time(),
            'notify_url' => $notifyUrl . '/api/notify/wechat/prepay',
            'amount' => [
                'total' => 1,
                'currency' => 'CNY'
            ],
            'attach' => Utils::jsonEncode($attach),
        ];

        $response = $instance
            ->chain('v3/pay/transactions/native')
            ->post(['json' => $json]);

        $data = Utils::jsonDecode($response->getBody()->getContents(), true);

        return $data['code_url'];
    }

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('config')
            ->logFillable()
            ->logUnguarded();
    }
}
