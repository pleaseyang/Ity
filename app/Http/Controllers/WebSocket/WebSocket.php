<?php

namespace App\Http\Controllers\WebSocket;

use Symfony\Component\HttpFoundation\Response as HttpResponse;

interface WebSocket
{
    /**
     * WebSocket constructor.
     * @param string $clientId 全局唯一的客户端socket连接标识
     */
    public function __construct(string $clientId);

    /**
     * 发送数据
     * @param HttpResponse $response
     */
    public function send(HttpResponse $response): void;
}
