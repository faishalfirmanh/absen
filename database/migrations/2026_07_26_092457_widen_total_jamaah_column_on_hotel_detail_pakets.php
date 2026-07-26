<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WidenTotalJamaahColumnOnHotelDetailPakets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hotel_detail_pakets', function (Blueprint $table) {
            $table->unsignedInteger('total_jamaah')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hotel_detail_pakets', function (Blueprint $table) {
            $table->unsignedTinyInteger('total_jamaah')->change();
        });
    }
}
