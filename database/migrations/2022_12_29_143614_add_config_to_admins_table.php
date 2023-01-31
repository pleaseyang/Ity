<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('theme')->default('#409EFF')->comment('主题色')->after('remember_token');
            $table->tinyInteger('tags_view')->default(1)->comment('开启 Tags-View 0关闭 1开启')->after('theme');
            $table->tinyInteger('fixed_header')->default(1)->comment('固定 Header 0关闭 1开启')->after('tags_view');
            $table->tinyInteger('sidebar_logo')->default(1)->comment('侧边栏 Logo 0关闭 1开启')->after('fixed_header');
            $table->tinyInteger('support_pinyin_search')->default(1)->comment('菜单支持拼音搜索 0关闭 1开启')->after('sidebar_logo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('theme');
            $table->dropColumn('tags_view');
            $table->dropColumn('fixed_header');
            $table->dropColumn('sidebar_logo');
            $table->dropColumn('support_pinyin_search');
        });
    }
};
