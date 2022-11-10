<?php

namespace App\Http\Controllers;

use App\Http\Response\ApiCode;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder as HttpResponse;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param int $api_code
     * @param null $data
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function output($api_code = ApiCode::HTTP_OK, $data = null)
    {
        if ($api_code == ApiCode::HTTP_OK) {
            return HttpResponse::success($data, $api_code);
        }
        return HttpResponse::error($api_code, null, $data);
    }
}
