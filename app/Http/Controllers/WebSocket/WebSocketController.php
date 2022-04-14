<?php

namespace App\Http\Controllers\WebSocket;

use App\Http\Controllers\Controller;
use App\Workerman\GateWay;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class WebSocketController extends Controller implements WebSocket
{

    public ?string $clientId;

    /**
     * WebSocket constructor.
     * @param string|null $clientId 全局唯一的客户端socket连接标识
     */
    public function __construct(string $clientId = null)
    {
        $this->clientId = $clientId;
    }

    /**
     * 发送数据
     * @param HttpResponse $response
     */
    public function send(HttpResponse $response): void
    {
        GateWay::sendResponseToClient($this->clientId, $response);
    }
}
