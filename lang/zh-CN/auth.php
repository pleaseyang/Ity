<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed'   => '用户名或密码错误',
    'throttle' => '您尝试的登录次数过多，请 :seconds 秒后再试。',
    'refresh' => '登陆状态已失效 请重试',
    'state_not_exists' => 'state 参数校验失败，请重试',
    'bind_success' => '绑定成功',
    'unbind_success' => '解绑成功',
    'ding_talk' => [
        'login_failed' => '登录失败，此钉钉没有绑定账号',
        'bind_failed' => '绑定失败，此钉钉已绑定账号'
    ],
    'wechat' => [
        'login_failed' => '登录失败，此微信没有绑定账号',
        'bind_failed' => '绑定失败，此微信已绑定账号'
    ]
];
