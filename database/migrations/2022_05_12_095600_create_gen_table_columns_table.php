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
        Schema::create('gen_table_columns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gen_table_id');
            $table->string('name', 200)->comment('名');
            $table->string('type', 100)->comment('类型');
            $table->integer('precision')->comment('长度');
            $table->integer('scale')->comment('小数点');
            $table->tinyInteger('notnull')->comment('不是NULL 1:是 0:否');
            $table->tinyInteger('primary')->comment('主键 1:是 0:否');
            $table->string('comment', 500)->nullable()->comment('注释');
            $table->string('default', 500)->nullable()->comment('默认值');
            $table->tinyInteger('autoincrement')->comment('自动递增 1:是 0:否');
            $table->tinyInteger('unsigned')->comment('无符号 1:是 0:否');
            $table->tinyInteger('_insert')->comment('新增 1:是 0:否');
            $table->tinyInteger('_update')->comment('更新 1:是 0:否');
            $table->tinyInteger('_list')->comment('列表 1:是 0:否');
            $table->tinyInteger('_select')->comment('查询 1:是 0:否');
            $table->string('_query', 100)->comment('查询方式');
            $table->tinyInteger('_required')->comment('必填 1:是 0:否');
            $table->string('_show', 100)->comment('新增类型');
            $table->string('_validate', 100)->comment('验证类型');
            $table->unsignedBigInteger('dict_type_id')->nullable()->comment('字典');
            $table->tinyInteger('_unique')->comment('唯一 1:是 0:否');
            $table->tinyInteger('_foreign')->comment('外键 1:是 0:否');
            $table->string('_foreign_table', 100)->nullable()->comment('外键表');
            $table->string('_foreign_column', 100)->nullable()->comment('外键字段');
            $table->timestamps();
            $table->foreign('gen_table_id')
                ->references('id')
                ->on('gen_tables')
                ->onDelete('cascade');
            $table->foreign('dict_type_id')
                ->references('id')
                ->on('dict_types')
                ->onDelete('restrict');
            $table->engine = 'InnoDB';
        });
        DB::statement("ALTER TABLE gen_table_columns comment '代码生成字段表';");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gen_table_columns');
    }
};
