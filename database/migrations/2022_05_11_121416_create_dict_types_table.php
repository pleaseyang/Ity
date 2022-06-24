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
    public function up()
    {
        Schema::create('dict_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('字典名称');
            $table->string('type', 100)->comment('字典类型')->unique();
            $table->tinyInteger('status')->default(1)->comment('状态 1:正常 0:禁止');
            $table->string('remark', 500)->comment('备注')->nullable();
            $table->timestamps();
            $table->engine = 'InnoDB';
        });
        DB::statement("ALTER TABLE dict_types comment '字典类型表';");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dict_types');
    }
};
