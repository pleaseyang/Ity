<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 版本低于 5.7.7 的 MySQL 或者版本低于 10.2.2 的 MariaDB 上创建索引，需要手动配置数据库迁移的默认字符串长度
        Schema::defaultStringLength(191);
    }
}
