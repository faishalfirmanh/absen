<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropToJamaahTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_paket_umrohs', function (Blueprint $table) {
            $table->dropColumn([
                'total_jamaah',
                'miqat_awal',
                'hotel_madinah',
                'night_madinah',
                'hotel_makkah',
                'night_makkah',
                'harga',
                'harga_triple',
                'harga_double',
                'tambahan_layanan_fasilitas'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('general_paket_umrohs', function (Blueprint $table) {
            //
        });
    }
}
