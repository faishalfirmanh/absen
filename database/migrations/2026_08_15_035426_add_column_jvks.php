<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnJvks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jamaah_vaksins', function (Blueprint $table) {
            $table->string('vendor_v', 255);
            $table->date('date');
            $table->date('valid_until');
            $table->string('location_v', 255);
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

        });
    }
}
