<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CheckStateRequest;
use App\Http\Requests\Admin\CodeLoginRequest;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\WechatCodeLoginRequest;
use App\Http\Response\ApiCode;
use App\Models\Admin;
use App\Models\ModelHasDingtalk;
use App\Models\ModelHasWechat;
use App\Util\Routes;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
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
        $this->middleware('auth:admin')->except([
            'login', 'refresh',
            'setting',
            'dingTalkUrl', 'dingTalkCheckState', 'dingTalk', 'dingTalkCorpId', 'dingTalkDD',
            'wechatUrl', 'wechatCheckState', 'wechat', 'wechatUrlOffiaccount', 'wechatOffiaccount'
        ]);
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

    public function dingTalkUrl(): Response
    {
        $url = ModelHasDingtalk::loginUrl();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'url' => $url
            ])
            ->build();
    }

    public function dingTalkCheckState(CheckStateRequest $request): Response
    {
        $validated = $request->validated();
        $state = $validated['state'];
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'check' => ModelHasDingtalk::checkState($state)
            ])
            ->build();
    }

    public function dingTalk(CodeLoginRequest $request): Response
    {
        $validated = $request->validated();
        $state = $validated['state'];
        $code = $validated['code'];
        try {
            $admin = ModelHasDingtalk::login($code, $state);
            $token = $this->guard()->login($admin);
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($this->respondWithTokenData($token))
                ->build();
        } catch (Exception|GuzzleException|InvalidArgumentException $exception) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($exception->getMessage())
                ->build();
        }
    }

    public function dingTalkCorpId(): Response
    {
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'corpId' => ModelHasDingtalk::dingTalkCorpId()
            ])
            ->build();
    }

    public function dingTalkDD(CodeLoginRequest $request)
    {
        $validated = $request->validated();
        $code = $validated['code'];
        try {
            $admin = ModelHasDingtalk::loginDD($code);
            $token = $this->guard()->login($admin);
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($this->respondWithTokenData($token))
                ->build();
        } catch (Exception|GuzzleException|InvalidArgumentException $exception) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($exception->getMessage())
                ->build();
        }
    }

    public function wechatUrl(): Response
    {
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData(ModelHasWechat::loginUrl())
            ->build();
    }

    public function wechatCheckState(CheckStateRequest $request): Response
    {
        $validated = $request->validated();
        $state = $validated['state'];
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'check' => ModelHasWechat::checkState($state)
            ])
            ->build();
    }

    public function wechat(WechatCodeLoginRequest $request): Response
    {
        $validated = $request->validated();
        $state = $validated['state'];
        $code = $validated['code'];
        $type = $validated['type'];
        try {
            $admin = ModelHasWechat::login($code, $state, $type);
            $token = $this->guard()->login($admin);
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData($this->respondWithTokenData($token))
                ->build();
        } catch (Exception|GuzzleException|InvalidArgumentException $exception) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($exception->getMessage())
                ->build();
        }
    }

    public function wechatUrlOffiaccount(): Response
    {
        $url = ModelHasWechat::loginUrlOffiaccount();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'url' => $url
            ])
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
}
