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

    'failed' => 'These credentials do not match our records.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'refresh' => 'Login status is invalid. Please try again',
    'state_not_exists' => 'state Parameter verification failed, please try again',
    'bind_success' => 'Binding succeeded',
    'unbind_success' => 'Unbinding succeeded',
    'ding_talk' => [
        'login_failed' => 'Login failed. This DingTalk is not bound to an account',
        'bind_failed' => 'Binding failed. This DingTalk has been bound to an account'
    ],
    'wechat' => [
        'login_failed' => 'Login failed. This WeChat account is not bound',
        'bind_failed' => 'Binding failed. This WeChat has been bound to an account'
    ]
];
