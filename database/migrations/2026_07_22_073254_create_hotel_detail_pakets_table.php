<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotelDetailPaketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hotel_detail_pakets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('general_paket_id');
            $table->foreign('general_paket_id')->references('id')->on('general_paket_umrohs')->onDelete('cascade');

            $table->tinyInteger('total_jamaah')->default(0);
            $table->string('miqat_awal')->nullable();
            $table->string('hotel_madinah')->nullable();
            $table->tinyInteger('night_madinah')->default(0);
            $table->string('hotel_makkah')->nullable();
            $table->tinyInteger('night_makkah')->default(0);
            $table->decimal('harga', 19, 4)->default(0);
            $table->decimal('harga_triple', 19, 4)->default(0);
            $table->decimal('harga_double', 19, 4)->default(0);
            $table->text('tambahan_layanan_fasilitas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hotel_detail_pakets');
    }
}
