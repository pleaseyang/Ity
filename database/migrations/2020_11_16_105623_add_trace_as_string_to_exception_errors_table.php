<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTraceAsStringToExceptionErrorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exception_errors', function (Blueprint $table) {
            $table->text('trace_as_string')->after('trace');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exception_errors', function (Blueprint $table) {
            $table->dropColumn('trace_as_string');
        });
    }
}
