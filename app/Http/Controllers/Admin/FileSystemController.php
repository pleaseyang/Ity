<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FileSystem\CreateDirectoryRequest;
use App\Http\Requests\Admin\FileSystem\DeleteDirectoryRequest;
use App\Http\Requests\Admin\FileSystem\FileRequest;
use App\Http\Requests\Admin\FileSystem\UploadRequest;
use App\Http\Response\ApiCode;
use App\Util\FileSystem;
use Illuminate\Http\Request;
use LogicException;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class FileSystemController extends Controller
{
    /**
     * 获取文件列表
     *
     * @param Request $request
     * @return Response
     */
    public function files(Request $request): Response
    {
        $directory = $request->post('directory', '') ?? '';
        $search = $request->post('search', null) ?? null;
        $offset = $request->post('offset', 0) ?? 0;
        $length = $request->post('length', 100) ?? 100;
        $offset = ($offset - 1 < 0 ? 0 : $offset - 1) * $length;
        $fileSystem = new FileSystem($directory);
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData($fileSystem->lists($offset, $length, $search)->toArray())
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 创建文件夹
     *
     * @param CreateDirectoryRequest $request
     * @return Response
     */
    public function makeDirectory(CreateDirectoryRequest $request): Response
    {
        try {
            $validated = $request->validated();
            $directory = $validated['directory'];
            $fileSystem = new FileSystem($directory);
            if ($fileSystem->makeDirectory($directory)) {
                activity()
                    ->useLog('file')
                    ->causedBy($request->user())
                    ->log(':causer.name 创建了文件夹 ' . $directory);
                return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                    ->withHttpCode(ApiCode::HTTP_OK)
                    ->withMessage(__('message.common.create.success'))
                    ->build();
            }
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage(__('message.common.create.fail'))
                ->build();
        } catch (LogicException $exception) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($exception->getMessage())
                ->build();
        }
    }

    /**
     * 删除文件夹
     *
     * @param DeleteDirectoryRequest $request
     * @return Response
     */
    public function deleteDirectory(DeleteDirectoryRequest $request): Response
    {
        $validated = $request->validated();
        $directory = $validated['directory'];
        $fileSystem = new FileSystem($directory);
        if ($fileSystem->deleteDirectory($directory)) {
            activity()
                ->useLog('file')
                ->causedBy($request->user())
                ->log(':causer.name 删除了文件夹 ' . $directory);
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withMessage(__('message.common.delete.success'))
                ->build();
        }
        return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
            ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
            ->withMessage(__('message.common.delete.fail'))
            ->build();
    }

    /**
     * 上传文件
     *
     * @param UploadRequest $request
     * @return Response
     */
    public function upload(UploadRequest $request): Response
    {
        $validated = $request->validated();
        $directory = $validated['directory'];
        $fileSystem = new FileSystem($directory);
        try {
            if (isset($validated['name'])) {
                $path = $fileSystem->putFileAs($request, 'file', $validated['name']);
            } else {
                $path = $fileSystem->putFile($request);
            }
            activity()
                ->useLog('file')
                ->causedBy($request->user())
                ->withProperties($path)
                ->log(':causer.name 上传文件');
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withMessage(__('message.common.create.success'))
                ->withData([
                    'path' => $path,
                    'realPath' => asset('storage/' . $path)
                ])
                ->build();
        } catch (Throwable $exception) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($exception->getMessage())
                ->build();
        }
    }

    /**
     * 下载文件(流)
     *
     * @param FileRequest $request
     * @return mixed|Response
     */
    public function download(FileRequest $request)
    {
        $validated = $request->validated();
        $fileSystem = new FileSystem('');
        activity()
            ->useLog('file')
            ->causedBy($request->user())
            ->log(':causer.name 下载了文件 ' . $validated['file']);
        return $fileSystem->download($validated['file']);
    }

    /**
     * 删除文件
     *
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request): Response
    {
        $paths = $request->post('paths');
        $fileSystem = new FileSystem('');
        if ($fileSystem->delete($paths)) {
            activity()
                ->useLog('file')
                ->causedBy($request->user())
                ->withProperties($paths)
                ->log(':causer.name 删除了文件');
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withMessage(__('message.common.delete.success'))
                ->build();
        }
        return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
            ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
            ->withMessage(__('message.common.delete.fail'))
            ->build();
    }
}
