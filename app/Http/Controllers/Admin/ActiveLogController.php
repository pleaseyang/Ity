<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Activity\GetListRequest;
use App\Http\Response\ApiCode;
use App\Models\Activity;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class ActiveLogController extends Controller
{
    /**
     * 获取列表
     *
     * @param GetListRequest $request
     * @return Response
     */
    public function logs(GetListRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData(Activity::getList($validated))
            ->withMessage(__('message.common.search.success'))
            ->build();
    }
}
