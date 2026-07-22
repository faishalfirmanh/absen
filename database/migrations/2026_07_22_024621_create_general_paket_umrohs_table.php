<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneralPaketUmrohsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('general_paket_umrohs', function (Blueprint $table) {
            $table->id();
            $table->date('tgl_keberangkatan');
            $table->string('nama_program');
            $table->string('nama_maskapai')->nullable();
            $table->string('rute')->nullable();
            $table->integer('program_hari')->nullable();
            $table->integer('total_seat');
            $table->integer('total_jamaah')->nullable();
            $table->integer('available')->nullable();
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
        Schema::dropIfExists('general_paket_umrohs');
    }
}
