<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\GetRequest;
use App\Http\Requests\Admin\System\UpdateAliOssRequest;
use App\Http\Requests\Admin\System\UpdateDingTalkRequest;
use App\Http\Requests\Admin\System\UpdateWeChatPayCheckRequest;
use App\Http\Requests\Admin\System\UpdateWeChatPayRequest;
use App\Http\Requests\Admin\System\UpdateWeChatPayTestRequest;
use App\Http\Requests\Admin\System\UpdateWeChatRequest;
use App\Http\Response\ApiCode;
use App\Models\Config;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;
use WeChatPay\Formatter;

class SystemController extends Controller
{
    public function logo(Request $request): Response
    {
        $file = $request->file('logo');
        $exists = Storage::exists('public/config');
        if ($exists === false) {
            Storage::makeDirectory('public/config');
        }
        $path = Storage::putFileAs(
            'public/config', $file, 'logo.png'
        );
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'path' => $path
            ])
            ->withMessage(__('message.common.update.success'))
            ->build();
    }

    public function getConfig(GetRequest $request): Response
    {
        $validated = $request->validated();
        $type = $validated['type'];
        $data = Config::getConfig($type);
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'config' => $data
            ])
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    public function aliOss(UpdateAliOssRequest $request): Response
    {
        $validated = $request->validated();
        try {
            $data = Config::aliOssConfig($validated);
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData([
                    'aliOss' => $data
                ])
                ->withMessage(__('message.common.update.success'))
                ->build();
        } catch (Exception $e) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($e->getMessage())
                ->build();
        }
    }

    public function dingTalk(UpdateDingTalkRequest $request): Response
    {
        $validated = $request->validated();
        try {
            $data = Config::dingTalkConfig($validated);
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData([
                    'dingTalk' => $data
                ])
                ->withMessage(__('message.common.update.success'))
                ->build();
        } catch (Exception $e) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($e->getMessage())
                ->build();
        }
    }

    public function wechat(UpdateWeChatRequest $request): Response
    {
        $validated = $request->validated();
        try {
            $data = Config::wechatConfig($validated);
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData([
                    'wechat' => $data
                ])
                ->withMessage(__('message.common.update.success'))
                ->build();
        } catch (Exception $e) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($e->getMessage())
                ->build();
        }
    }

    public function wechatPay(UpdateWeChatPayRequest $request): Response
    {
        $validated = $request->validated();
        $type = 'wechatPay';
        $apiV3key = $validated['api_v3_key'];
        /* @var UploadedFile $zipUploadedFile */
        $zipUploadedFile = $validated['zip'];
        $zipPath = storage_path('app/' . $zipUploadedFile->store('public'));
        try {
            $data = Config::wechatPayConfig($apiV3key, $zipPath, $type);
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData([
                    $type => $data
                ])
                ->withMessage(__('message.common.update.success'))
                ->build();
        } catch (Exception $e) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($e->getMessage())
                ->build();
        }
    }

    public function wechatPayCheck(UpdateWeChatPayCheckRequest $request): Response
    {
        $validated = $request->validated();
        /* @var UploadedFile $zipUploadedFile */
        $zipUploadedFile = $validated['zip'];
        $zipPath = storage_path('app/' . $zipUploadedFile->store('public'));
        try {
            $data = Config::wechatPayCheck($zipPath);
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($data)
                ->withMessage(__('message.common.search.success'))
                ->build();
        } catch (Exception $e) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($e->getMessage())
                ->build();
        }
    }

    public function wechatPayTest(UpdateWeChatPayTestRequest $request): Response
    {
        $validated = $request->validated();
        try {
            $data = Config::wechatPayTest($validated['appid'], $validated['notify_url']);
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData([
                    'code' => $data
                ])
                ->withMessage(__('message.common.search.success'))
                ->build();
        } catch (Exception $e) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($e->getMessage())
                ->build();
        }
    }

    public function randomKey(): Response
    {
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'key' => Formatter::nonce()
            ])
            ->withMessage(__('message.common.search.success'))
            ->build();
    }
}
