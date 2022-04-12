<?php

namespace App\Workerman;

use App;
use App\Http\Response\ApiCode;
use Exception;
use GatewayWorker\BusinessWorker;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Utils;
use Illuminate\Support\Facades\Auth;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Providers\Auth\Illuminate;
use PHPOpenSourceSaver\JWTAuth\Token;

class Events
{
    /**
     * 当businessWorker进程启动时触发。每个进程生命周期内都只会触发一次
     *
     * @param BusinessWorker $businessWorker
     */
    public static function onWorkerStart(BusinessWorker $businessWorker): void
    {
        GateWay::log()->info('OnWorkerStart', [
            'registerAddress' => $businessWorker->registerAddress,
            'name' => $businessWorker->name,
            'count' => $businessWorker->count,
            'workerId' => $businessWorker->workerId,
        ]);
    }

    /**
     * 当客户端连接上gateway进程时(TCP三次握手完毕时)触发的回调函数。
     * onConnect事件仅仅代表客户端与gateway完成了TCP三次握手，这时客户端还没有发来任何数据，
     * 此时除了通过$_SERVER['REMOTE_ADDR']获得对方ip，没有其他可以鉴别客户端的数据或者信息，所以在onConnect事件里无法确认对方是谁。
     *
     * @param string $clientId 全局唯一的客户端socket连接标识
     */
    public static function onConnect(string $clientId): void
    {
    }

    /**
     * 当客户端连接上gateway完成websocket握手时触发的回调函数
     *
     * @param string $clientId 全局唯一的客户端socket连接标识
     * @param array $data websocket握手时的http头数据，包含get、server等变量
     */
    public static function onWebSocketConnect(string $clientId, array $data): void
    {
        $locale = isset($data['get']['lang']) ? $data['get']['lang'] : 'en';
        App::setLocale($locale);
        if (isset($data['get']['token'])) {
            try {
                $token = new Token($data['get']['token']);
                $jwt = JWTAuth::setToken($token);
                $JWTAuth = new \PHPOpenSourceSaver\JWTAuth\JWTAuth(
                    JWTAuth::manager(),
                    new Illuminate(Auth::guard($jwt->getClaim('role'))),
                    JWTAuth::parser()
                );
                $info = $JWTAuth->setToken($token)->authenticate(); // info
                Gateway::bindUser($clientId, $info);
                $return = ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                    ->withData([
                        'type' => __FUNCTION__,
                        'clientId' => $info->id
                    ])
                    ->withMessage(__('message.common.bind.success'))
                    ->build();
            } catch (TokenInvalidException $tokenInvalidException) {
                $return = ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                    ->withData([
                        'type' => __FUNCTION__,
                    ])
                    ->withMessage($tokenInvalidException->getMessage())
                    ->build();
            }
            Gateway::sendResponseToClient($clientId, $return);
        }
    }

    /**
     * 当客户端发来数据(Gateway进程收到数据)后触发的回调函数
     *
     * @param string $clientId 全局唯一的客户端socket连接标识
     * @param mixed $message 完整的客户端请求数据，数据类型取决于Gateway所使用协议的decode方法返的回值类型
     */
    public static function onMessage(string $clientId, $message): void
    {
        // cmd
        $type = ['type' => __FUNCTION__];
        try {
            $message = Utils::jsonDecode($message);
            if (is_object($message) && isset($message->route) && is_string($message->route)) {
                GateWay::cmd($clientId, $message->route, collect($message->data ?? null));
            } else {
                $return = ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                    ->withData($type)
                    ->withMessage(__('message.common.error.json_error'))
                    ->build();
                Gateway::sendResponseToClient($clientId, $return);
            }
        } catch (InvalidArgumentException $exception) {
            $return = ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withData($type)
                ->withMessage(__('message.common.error.json_error'))
                ->build();
            Gateway::sendResponseToClient($clientId, $return);
        }
    }

    /**
     * 客户端与Gateway进程的连接断开时触发。
     * 不管是客户端主动断开还是服务端主动断开，都会触发这个回调。一般在这里做一些数据清理工作
     *
     * @param string $clientId 全局唯一的客户端socket连接标识
     */
    public static function onClose(string $clientId): void
    {
        try {
            $count = GateWay::getAllClientCount();
            $response = ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withData([
                    'type' => __FUNCTION__,
                    'count' => $count
                ])
                ->withMessage(__('message.common.search.success'))
                ->build();
            GateWay::sendResponseToAll($response);
        } catch (Exception $e) {
        }
    }
}
