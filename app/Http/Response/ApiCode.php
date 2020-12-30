<?php


namespace App\Http\Response;


use Kayex\HttpCodes;

class ApiCode extends HttpCodes
{
    const HTTP_UNPROCESSABLE_ENTITY = 422;
    const HTTP_TOO_MANY_REQUEST = 429;
}
