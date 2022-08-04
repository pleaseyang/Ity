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
        Schema::create('gen_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->comment('表名称');
            $table->string('comment', 200)->nullable()->comment('表描述');
            $table->string('engine', 100)->comment('表引擎');
            $table->string('charset', 100)->comment('字符集');
            $table->string('collation', 100)->comment('排序规则');
            $table->timestamps();
            $table->engine = 'InnoDB';
        });
        DB::statement("ALTER TABLE gen_tables comment '代码生成表';");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gen_tables');
    }
};
