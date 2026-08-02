<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jamaah_vaksins', function (Blueprint $table) {
            //
            $table->string('name_jamaah');
            $table->string('passport_no');
            $table->string('v_code_generate');
            $table->date('date_under_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jamaah_vaksins', function (Blueprint $table) {
            //
        });
    }
}
