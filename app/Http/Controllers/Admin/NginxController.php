<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nginx\GetListRequest;
use App\Http\Response\ApiCode;
use App\Util\NginxLog;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
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
            $logs = $logs->when(isset($validated['ip']), function (Collection $logs) use ($validated): Collection {
                return $logs->where('ip', $validated['ip']);
            });
            $logs = $logs->when(isset($validated['method']), function (Collection $logs) use ($validated): Collection {
                return $logs->where('method', $validated['method']);
            });
            $logs = $logs->when(isset($validated['http_code']), function (Collection $logs) use ($validated): Collection {
                return $logs->where('http_code', $validated['http_code']);
            });
            $logs = $logs->when(isset($validated['uri']), function (Collection $logs) use ($validated): Collection {
                return $logs->where('uri', $validated['uri']);
            });
            $warningValue = isset($validated['is_warning']) && $validated['is_warning'] !== null;
            $logs = $logs->when($warningValue, function (Collection $logs) use ($validated): Collection {
                return $logs->where('is_warning', (bool)$validated['is_warning']);
            });
            $isErrorValue = isset($validated['is_error']) && $validated['is_error'] !== null;
            $logs = $logs->when($isErrorValue, function (Collection $logs) use ($validated): Collection {
                return $logs->where('is_error', (bool)$validated['is_error']);
            });
            $isRobotValue = isset($validated['is_robot']) && $validated['is_robot'] !== null;
            $logs = $logs->when($isRobotValue, function (Collection $logs) use ($validated): Collection {
                return $logs->where('is_robot', (bool)$validated['is_robot']);
            });
            $isMobileValue = isset($validated['is_mobile']) && $validated['is_mobile'] !== null;
            $logs = $logs->when($isMobileValue, function (Collection $logs) use ($validated): Collection {
                return $logs->where('is_mobile', (bool)$validated['is_mobile']);
            });
            $timeValue = isset($validated['start_at']) && $validated['start_at'] !== null &&
                isset($validated['end_at']) && $validated['end_at'] !== null;
            $logs = $logs->when($timeValue, function (Collection $logs) use ($validated): Collection {
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
