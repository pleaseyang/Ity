<?php


namespace App\Workerman;

use Exception;
use GatewayWorker\Lib\Gateway as LibGateWay;
use GuzzleHttp\Utils;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class GateWay extends LibGateWay
{
    /**
     * @return LoggerInterface
     */
    public static function log(): LoggerInterface
    {
        return Log::channel('workerman');
    }

    /**
     * @param string $clientId
     * @param JWTSubject $info
     * @return void
     */
    public static function bindUser(string $clientId, JWTSubject $info): void
    {
        parent::bindUid($clientId, Utils::jsonEncode([
            'model' => get_class($info),
            'id' => $info->id
        ]));
    }

    /**
     * @param string $clientId
     * @return JWTSubject|null
     */
    public static function getUserByClientId(string $clientId): ?JWTSubject
    {
        $uid = parent::getUidByClientId($clientId);
        if ($uid === null) {
            return null;
        }
        $info = Utils::jsonDecode($uid);
        $model = new $info->model;
        return $model->find($info->id);
    }

    /**
     * @param string $clientId
     * @param HttpResponse $response
     */
    public static function sendResponseToClient(string $clientId, HttpResponse $response): void
    {
        parent::sendToClient($clientId, $response->getContent());
    }

    /**
     * @param HttpResponse $response
     * @param array|null $clientId
     * @param array|null $excludeClientId
     * @throws Exception
     */
    public static function sendResponseToAll(
        HttpResponse $response,
        array $clientId = null,
        array $excludeClientId = null
    ): void {
        parent::sendToAll($response->getContent(), $clientId, $excludeClientId);
    }

    /**
     * @param string $clientId
     * @param string $routerName
     * @param Collection $collection
     */
    public static function cmd(string $clientId, string $routerName, Collection $collection): void
    {
        $route = app()->routes->getByName($routerName);
        if ($route) {
            $uses = $route->getAction('uses');
            list($controller, $method) = explode('@', $uses);
            if (class_exists($controller) && method_exists($call = new $controller($clientId), $method)) {
                $call->$method($collection);
            }
        }
    }
}
