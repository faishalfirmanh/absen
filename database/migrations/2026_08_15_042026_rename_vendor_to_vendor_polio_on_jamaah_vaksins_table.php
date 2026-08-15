<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameVendorToVendorPolioOnJamaahVaksinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jamaah_vaksins', function (Blueprint $table) {
            $table->string('tipe_v1');
            $table->renameColumn('vendor_v', 'v_name_1');
            $table->renameColumn('date', 'date_v1');
            $table->renameColumn('valid_until', 'valid_until_v1');
            $table->renameColumn('location_v', 'location_v1');

            $table->string('tipe_v2');
            $table->string('vendor_v2');
            $table->date('date_v2');
            $table->date('valid_until_v2');
            $table->string('location_v2');
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
