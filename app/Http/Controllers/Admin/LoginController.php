<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Response\ApiCode;
use App\Models\Admin;
use App\Util\Routes;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:admin')->except('login');
    }

    /**
     * Refresh token
     *
     * @return Response
     */
    public function refresh(): Response
    {
        $token = $this->guard()->refresh();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData($this->respondWithTokenData($token))
            ->build();
    }

    /**
     * Get the guard info
     *
     * @return Response
     */
    public function me(): Response
    {
        /** @var Admin $user */
        $user = $this->guard()->user();
        // 对应路由
        $accessedRoutes = (new Routes($user))->routes();
        $roles = $user->roles->mapWithKeys(function ($role, $key) {
            return [$key => $role->id];
        })->prepend('App\Models\Admin\\' . $user->id);
        unset($user->roles);
        $user['accessedRoutes'] = $accessedRoutes;
        // 对应角色
        $user['roles'] = $roles;
        // 未读消息数
        $user['unreadNotificationCount'] = $user->unreadNotifications()->count('id');
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData($user)
            ->build();
    }

    /**
     * Handle a login request to the application.
     *
     * @param LoginRequest $request
     * @return Response
     */
    public function login(LoginRequest $request): Response
    {
        $credentials = $this->credentials($request);
        if ($token = $this->guard()->attempt($credentials)) {
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($this->respondWithTokenData($token))
                ->build();
        }
        return ResponseBuilder::asError(ApiCode::HTTP_UNPROCESSABLE_ENTITY)
            ->withHttpCode(ApiCode::HTTP_UNPROCESSABLE_ENTITY)
            ->withMessage(__('auth.failed'))
            ->withData([
                $this->username() => [__('auth.failed')]
            ])
            ->build();
    }

    /**
     * Log the user out of the application.
     *
     * @return Response
     */
    public function logout(): Response
    {
        $this->guard()->logout();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_NO_CONTENT)
            ->withData()
            ->build();
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username(): string
    {
        return 'name';
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return Guard
     */
    protected function guard(): Guard
    {
        return Auth::guard('admin');
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return array
     */
    protected function respondWithTokenData(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ];
    }
}
