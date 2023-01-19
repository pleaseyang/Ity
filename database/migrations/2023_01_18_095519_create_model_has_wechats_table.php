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
        Schema::create('model_has_wechats', function (Blueprint $table) {
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('unionid')->comment('unionid');
            $table->string('nickname')->comment('微信名称');
            $table->string('headimgurl')->nullable()->comment('微信头像');
            $table->timestamps();
            $table->primary(['model_id', 'model_type']);
            $table->unique(['model_id', 'model_type'], 'model_has_wechats_model_id_model_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('model_has_wechats');
    }
};
