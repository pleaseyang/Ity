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
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Psr\SimpleCache\InvalidArgumentException;
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
        $this->middleware('auth:admin')->except(['login', 'refresh', 'setting']);
    }

    /**
     * Refresh token
     *
     * @return Response
     */
    public function refresh(): Response
    {
        try {
            $token = $this->guard()->refresh();
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($this->respondWithTokenData($token))
                ->build();
        } catch (JWTException $exception) {
            return ResponseBuilder::asError(ApiCode::HTTP_TOKEN_EXPIRED)
                ->withHttpCode(ApiCode::HTTP_TOKEN_EXPIRED)
                ->build();
        }
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

    public function setting(): Response
    {
        $setting = [];
        $setting['title'] = config('app.name');
        if (file_exists(storage_path('app/public/config/logo.png'))) {
            $setting['logo'] = asset('storage/config/logo.png');
        } else {
            $setting['logo'] = asset('image/logo.png');
        }
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData($setting)
            ->build();
    }

    /**
     * Get the guard info
     *
     * @return Response
     * @throws InvalidArgumentException
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
        // 配置信息
        $user['config'] = [
            'theme' => $user->theme,
            'tagsView' => $user->tags_view === 1,
            'fixedHeader' => $user->fixed_header === 1,
            'sidebarLogo' => $user->sidebar_logo === 1,
            'supportPinyinSearch' => $user->support_pinyin_search === 1,
        ];
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
        $field = filter_var($credentials[$this->username()], FILTER_VALIDATE_EMAIL) ? 'email' : $this->username();
        $attempt[$field] = $credentials[$this->username()];
        $attempt['password'] = $credentials['password'];
        if ($token = $this->guard()->attempt($attempt)) {
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
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username(): string
    {
        return 'name';
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
}
