<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nginx\GetListRequest;
use App\Http\Response\ApiCode;
use App\Util\NginxLog;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class NginxController extends Controller
{
    /**
     * @param GetListRequest $request
     * @return Response
     */
    public function logs(GetListRequest $request): Response
    {
        try {
            $validated = $request->validated();
            $nginxLog = new NginxLog($validated['file']);
            $logs = $nginxLog->get();
            // WHERE
            $logs = $logs->when(isset($validated['ip']), function ($logs) use ($validated) {
                return $logs->where('ip', $validated['ip']);
            });
            $logs = $logs->when(isset($validated['method']), function ($logs) use ($validated) {
                return $logs->where('method', $validated['method']);
            });
            $logs = $logs->when(isset($validated['http_code']), function ($logs) use ($validated) {
                return $logs->where('http_code', $validated['http_code']);
            });
            $logs = $logs->when(isset($validated['uri']), function ($logs) use ($validated) {
                return $logs->where('uri', $validated['uri']);
            });
            $warningValue = isset($validated['is_warning']) && $validated['is_warning'] !== null;
            $logs = $logs->when($warningValue, function ($logs) use ($validated) {
                return $logs->where('is_warning', $validated['is_warning'] ? true : false);
            });
            $isErrorValue = isset($validated['is_error']) && $validated['is_error'] !== null;
            $logs = $logs->when($isErrorValue, function ($logs) use ($validated) {
                return $logs->where('is_error', $validated['is_error'] ? true : false);
            });
            $isRobotValue = isset($validated['is_robot']) && $validated['is_robot'] !== null;
            $logs = $logs->when($isRobotValue, function ($logs) use ($validated) {
                return $logs->where('is_robot', $validated['is_robot'] ? true : false);
            });
            $isMobileValue = isset($validated['is_mobile']) && $validated['is_mobile'] !== null;
            $logs = $logs->when($isMobileValue, function ($logs) use ($validated) {
                return $logs->where('is_mobile', $validated['is_mobile'] ? true : false);
            });
            $timeValue = isset($validated['start_at']) && $validated['start_at'] !== null &&
                isset($validated['end_at']) && $validated['end_at'] !== null;
            $logs = $logs->when($timeValue, function ($logs) use ($validated) {
                return $logs->whereBetween('time', [$validated['start_at'], $validated['end_at']]);
            });
            $total = $logs->count();
            $offset = $validated['offset'];
            $length = $validated['limit'];
            $offset = ($offset - 1 < 0 ? 0 : $offset - 1) * $length;
            $data = $logs->slice($offset, $length);
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData([
                    'total' => $total,
                    'logs' => $data->values()
                ])
                ->withMessage(__('message.common.search.success'))
                ->build();
        } catch (FileNotFoundException $e) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($e->getMessage())
                ->build();
        }
    }
}
