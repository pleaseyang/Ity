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
        Schema::create('model_has_dingtalks', function (Blueprint $table) {
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('userid')->comment('userid');
            $table->string('name')->comment('钉钉名称');
            $table->string('avatar')->nullable()->comment('钉钉头像');
            $table->tinyInteger('admin')->default(0)->comment('是否为管理员');
            $table->string('email')->nullable()->comment('钉钉邮箱');
            $table->string('mobile')->nullable()->comment('钉钉手机号');
            $table->string('unionid')->comment('unionid');
            $table->timestamps();
            $table->primary(['model_id', 'model_type']);
            $table->unique(['model_id', 'model_type'], 'model_has_dingtalks_model_id_model_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('model_has_dingtalks');
    }
};
