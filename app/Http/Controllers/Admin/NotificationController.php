<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Notification\GetListRequest;
use App\Http\Requests\Admin\Notification\SendRequest;
use App\Http\Response\ApiCode;
use App\Models\Admin;
use App\Models\DatabaseNotification;
use App\Notifications\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    /** @var Admin */
    private $user;

    /**
     * NotificationController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
        /** @var Admin */
        $this->user = Auth::guard('admin')->user();
    }

    /**
     * 获取通知列表
     *
     * @param GetListRequest $request
     * @return Response
     */
    public function notifications(GetListRequest $request): Response
    {
        $validated = $request->validated();
        $data = DatabaseNotification::getList($this->user, $validated);
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData($data)
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 通知详情
     *
     * @param Request $request
     * @return Response
     */
    public function notification(Request $request): Response
    {
        $id = $request->post('id', '');
        $notification = DatabaseNotification::whereId($id)
            ->whereNotifiableId($this->user->id)
            ->select([
                'id',
                'data',
                'read_at',
                'created_at',
                'updated_at'
            ])
            ->first();
        if ($notification) {
            if ($notification->read_at === null) {
                $notification->read_at = now();
                $notification->save();
            }
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($notification)
                ->withMessage(__('message.common.search.success'))
                ->build();
        }

        return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
            ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
            ->withMessage(__('message.common.search.fail'))
            ->build();
    }

    /**
     * 未读通知数
     *
     * @return Response
     */
    public function unReadCount(): Response
    {
        $count = $this->user->unreadNotifications()->count('id');
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'count' => $count
            ])
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 全部已读
     *
     * @return Response
     */
    public function allRead(): Response
    {
        $this->user->unreadNotifications()->update(['read_at' => now()]);
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withMessage(__('message.common.update.success'))
            ->build();
    }

    /**
     * 发送通知
     *
     * @param SendRequest $request
     * @return Response
     */
    public function send(SendRequest $request): Response
    {
        $validated = $request->validated();
        $adminModel = Admin::whereStatus(1)->where('id', '!=', $this->user->id);
        if (count($validated['admins']) > 0) {
            $adminModel->whereIn('id', $validated['admins']);
        }

        $adminModel->get()->each(function ($admin) use ($validated) {
            $admin->notify(new Message([
                'form' => $this->user->name,
                'message' => $validated['message']
            ]));
        });

        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withMessage(__('message.common.create.success'))
            ->build();
    }

    /**
     * 获取可通知的后台管理员
     *
     * @return Response
     */
    public function admins(): Response
    {
        $admins = Admin::whereStatus(1)
            ->where('id', '!=', $this->user->id)
            ->select(['id', 'name'])
            ->get();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'admins' => $admins
            ])
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 标记已读
     *
     * @param Request $request
     * @return Response
     */
    public function read(Request $request): Response
    {
        $this->user->unreadNotifications()
            ->whereIn('id', $request->post('id'))
            ->update(['read_at' => now()]);
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withMessage(__('message.common.update.success'))
            ->build();
    }
}
