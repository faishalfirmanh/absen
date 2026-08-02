<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDVsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('d_vs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('j_id')
                ->constrained('jamaah_vaksins', 'id')
                ->onDelete('cascade');

            $table->string('vendor_v');
            $table->date('date');
            $table->date('valid_until');
            $table->string('location_v');
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
        Schema::dropIfExists('d_vs');
    }
}
