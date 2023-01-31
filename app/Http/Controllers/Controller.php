<?php

namespace App\Http\Controllers;

use App\Http\Response\ApiCode;
use App\Models\Config;
use Exception;
use GuzzleHttp\Utils;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;
use TypeError;
use WeChatPay\Builder;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Formatter;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function page(): \Illuminate\Contracts\View\View
    {
        View::addExtension('html', 'php');
        return view()->file(public_path('index.html'));
    }

    public function wechatPayNotify(Request $request): Response
    {
        Log::info('微信支付回调');
        try {
            $inWechatpaySignature = $request->header('Wechatpay-Signature');
            $inWechatpayTimestamp = $request->header('Wechatpay-Timestamp');
            $inWechatpaySerial = $request->header('Wechatpay-Serial');
            $inWechatpayNonce = $request->header('Wechatpay-Nonce');
            $inBody = file_get_contents('php://input');
            $wechatPayConfig = Config::getConfig('wechatPay');
            $apiv3Key = Crypt::decryptString($wechatPayConfig->where('key', 'api_v3_key')->value('value'));

            // 根据通知的平台证书序列号，查询本地平台证书文件，
            $platformPublicKeyInstance = Rsa::from('file://' . $wechatPayConfig->where('key', 'wechat_pay_cert')->value('value'), Rsa::KEY_TYPE_PUBLIC);
            // 检查通知时间偏移量，允许5分钟之内的偏移
            $timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
            $verifiedStatus = Rsa::verify(
            // 构造验签名串
                Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, $inBody),
                $inWechatpaySignature,
                $platformPublicKeyInstance
            );
            if ($timeOffsetStatus === false) {
                Log::error('时间偏移量大于5分钟');
                return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                    ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                    ->withMessage('时间偏移量大于5分钟')
                    ->build();
            }
            if ($verifiedStatus === false) {
                Log::error('签名验证失败');
                return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                    ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                    ->withMessage('签名验证失败')
                    ->build();
            }


            // 商户号
            $merchantId = $wechatPayConfig->where('key', 'merchant_id')->value('value');
            // 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
            $merchantPrivateKeyFilePath = 'file://' . Crypt::decryptString($wechatPayConfig->where('key', 'merchant_key')->value('value'));
            $merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath);

            // 「商户API证书」的「证书序列号」
            $merchantCertificateSerial = $wechatPayConfig->where('key', 'merchant_serial_number')->value('value');

            $instance = Builder::factory([
                'mchid' => $merchantId,
                'serial' => $merchantCertificateSerial,
                'privateKey' => $merchantPrivateKeyInstance,
                'certs' => [
                    $inWechatpaySerial => $platformPublicKeyInstance,
                ],
            ]);


            $inBodyArray = $request->post();
            ['resource' => [
                'ciphertext'      => $ciphertext,
                'nonce'           => $nonce,
                'associated_data' => $aad
            ]] = $inBodyArray;
            $inBodyResource = AesGcm::decrypt($ciphertext, $apiv3Key, $nonce, $aad);
            $inBodyResourceArray = Utils::jsonDecode($inBodyResource, true);
            Log::info('微信解密报文');
            Log::info($inBodyResourceArray);
            $attach = Utils::jsonDecode($inBodyResourceArray['attach'], true);
            if ($attach['type'] === 'test') {
                $notifyUrl = urldecode($attach['notifyUrl']);
                $transactionId = $inBodyResourceArray['transaction_id'];
                $response = $instance
                    ->chain('v3/pay/transactions/id/' . $transactionId)
                    ->get(['query' => ['mchid' => $merchantId]]);
                $data = Utils::jsonDecode($response->getBody()->getContents(), true);
                Log::info('微信订单详情');
                Log::info($data);
                if ($data['trade_state'] === 'SUCCESS') {
                    $response = $instance
                        ->chain('v3/refund/domestic/refunds')
                        ->post(['json' => [
                            'transaction_id' => $transactionId,
                            'out_refund_no' => 'RE_' . $data['out_trade_no'],
                            'reason' => '本次支付为测试支付，实付1分钱。付款后会退回。',
                            'notify_url' => $notifyUrl . '/api/notify/wechat/refund',
                            'amount' => [
                                'refund' => 1,
                                'total' => 1,
                                'currency' => 'CNY',
                            ]
                        ]]);
                    Log::info('已发起微信退款');
                    Log::info($response->getBody()->getContents());
                }
            }

            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withMessage(__('message.common.search.success'))
                ->build();
        } catch (TypeError $typeError) {
            Log::error('微信支付回调语法错误');
            Log::error($typeError);
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($typeError->getMessage())
                ->build();
        } catch (Exception $exception) {
            Log::error('微信支付回调异常');
            Log::error($exception);
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($exception->getMessage())
                ->build();
        }
    }

    public function wechatRefundNotify(Request $request): Response
    {
        Log::info('微信退款回调');
        try {
            $inWechatpaySignature = $request->header('Wechatpay-Signature');
            $inWechatpayTimestamp = $request->header('Wechatpay-Timestamp');
            $inWechatpaySerial = $request->header('Wechatpay-Serial');
            $inWechatpayNonce = $request->header('Wechatpay-Nonce');
            $inBody = file_get_contents('php://input');
            $wechatPayConfig = Config::getConfig('wechatPay');
            $apiv3Key = Crypt::decryptString($wechatPayConfig->where('key', 'api_v3_key')->value('value'));
            // 根据通知的平台证书序列号，查询本地平台证书文件，
            $platformPublicKeyInstance = Rsa::from('file://' . $wechatPayConfig->where('key', 'wechat_pay_cert')->value('value'), Rsa::KEY_TYPE_PUBLIC);
            // 检查通知时间偏移量，允许5分钟之内的偏移
            $timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
            $verifiedStatus = Rsa::verify(
            // 构造验签名串
                Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, $inBody),
                $inWechatpaySignature,
                $platformPublicKeyInstance
            );
            if ($timeOffsetStatus === false) {
                Log::error('时间偏移量大于5分钟');
                return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                    ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                    ->withMessage('时间偏移量大于5分钟')
                    ->build();
            }
            if ($verifiedStatus === false) {
                Log::error('签名验证失败');
                return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                    ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                    ->withMessage('签名验证失败')
                    ->build();
            }

            $inBodyArray = $request->post();
            ['resource' => [
                'ciphertext'      => $ciphertext,
                'nonce'           => $nonce,
                'associated_data' => $aad
            ]] = $inBodyArray;
            $inBodyResource = AesGcm::decrypt($ciphertext, $apiv3Key, $nonce, $aad);
            $inBodyResourceArray = Utils::jsonDecode($inBodyResource, true);
            Log::info('微信解密报文');
            Log::info($inBodyResourceArray);

            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withMessage(__('message.common.search.success'))
                ->build();
        } catch (TypeError $typeError) {
            Log::error('微信退款回调语法错误');
            Log::error($typeError);
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($typeError->getMessage())
                ->build();
        } catch (Exception $exception) {
            Log::error('微信退款回调异常');
            Log::error($exception);
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($exception->getMessage())
                ->build();
        }
    }
}
