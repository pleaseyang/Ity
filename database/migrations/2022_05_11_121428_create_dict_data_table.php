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
        Schema::create('dict_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dict_type_id');
            $table->tinyInteger('sort')->default(0)->comment('字典排序');
            $table->string('label', 100)->comment('字典标签');
            $table->string('value', 100)->comment('字典键值');
            $table->string('list_class', 100)->comment('表格回显样式')->nullable();
            $table->tinyInteger('default')->default(0)->comment('是否默认 1:是 0:否');
            $table->tinyInteger('status')->default(1)->comment('状态 1:正常 0:禁止');
            $table->string('remark', 500)->comment('备注')->nullable();
            $table->timestamps();

            $table->foreign('dict_type_id')
                ->references('id')
                ->on('dict_types')
                ->onDelete('cascade');

            $table->engine = 'InnoDB';
        });
        DB::statement("ALTER TABLE dict_data comment '字典数据表';");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dict_data');
    }
};
