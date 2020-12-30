<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMenuToPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableNames = config('permission.table_names');
        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->bigInteger('pid')->after('id')->default(0);
            $table->string('title')->after('name');
            $table->string('icon')->after('title')->nullable();
            $table->string('path')->after('icon')->nullable()->comment('访问路径');
            $table->string('component')->after('path')->nullable()->comment('vue 对应的组件地址');
            $table->bigInteger('sort')->after('component')->default(1)->comment('排序');
            $table->tinyInteger('hidden')->after('sort')->default(1)->comment('是否隐藏 0=false|不隐藏 1=true|隐藏');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableNames = config('permission.table_names');
        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->dropColumn('pid');
            $table->dropColumn('title');
            $table->dropColumn('icon');
            $table->dropColumn('path');
            $table->dropColumn('component');
            $table->dropColumn('sort');
            $table->dropColumn('hidden');
        });
    }
}
