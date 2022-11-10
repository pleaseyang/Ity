<?php


namespace App\Http\Response;


use Kayex\HttpCodes;

class ApiCode extends HttpCodes
{
    const HTTP_UNPROCESSABLE_ENTITY = 422;
    const HTTP_TOO_MANY_REQUEST = 429;
    const HTTP_TOKEN_EXPIRED = 430;

    public static $map = [
        self::HTTP_OK => 'api_code.ok',
        self::HTTP_NO_CONTENT => '',
        self::HTTP_BAD_REQUEST => 'api_code.bad_request',
        self::HTTP_UNAUTHORIZED => 'api_code.unauthorized',
        self::HTTP_FORBIDDEN => 'api_code.forbidden',
        self::HTTP_UNPROCESSABLE_ENTITY => 'api_code.unprocessable_entity',
        self::HTTP_SERVICE_UNAVAILABLE => 'api_code.service_unavailable',
        self::HTTP_INTERNAL_SERVER_ERROR => 'api_code.server_error',
        self::HTTP_TOO_MANY_REQUEST => 'api_code.too_many_request',
        self::HTTP_NOT_FOUND => 'api_code.not_found',
        self::HTTP_METHOD_NOT_ALLOWED => 'api_code.method_not_allowed',
        self::HTTP_TOKEN_EXPIRED => 'api_code.token_expired',
    ];
}
